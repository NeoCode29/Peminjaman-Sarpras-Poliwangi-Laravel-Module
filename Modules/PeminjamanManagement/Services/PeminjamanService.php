<?php

namespace Modules\PeminjamanManagement\Services;

use App\Models\GlobalApprover;
use App\Models\SystemSetting;
use App\Services\NotificationBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Entities\PeminjamanApprovalStatus;
use Modules\PeminjamanManagement\Entities\PeminjamanApprovalWorkflow;
use Modules\PeminjamanManagement\Entities\PeminjamanItem;
use Modules\PeminjamanManagement\Events\PeminjamanStatusChanged;
use Modules\PeminjamanManagement\Repositories\Interfaces\PeminjamanRepositoryInterface;
use Modules\PrasaranaManagement\Entities\PrasaranaApprover;
use Modules\SaranaManagement\Entities\SaranaApprover;

class PeminjamanService
{
    public function __construct(
        private readonly PeminjamanRepositoryInterface $peminjamanRepository,
        private readonly DatabaseManager $database
    ) {}

    /**
     * Get all peminjaman with filters.
     */
    public function getPeminjaman(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->peminjamanRepository->getAll($filters, $perPage);
    }

    /**
     * Get peminjaman by ID.
     */
    public function findById(int $id): ?Peminjaman
    {
        return $this->peminjamanRepository->findById($id);
    }

    /**
     * Get peminjaman by ID with all relations.
     */
    public function findByIdWithRelations(int $id): ?Peminjaman
    {
        return $this->peminjamanRepository->findByIdWithRelations($id);
    }

    /**
     * Get peminjaman for user.
     */
    public function getPeminjamanForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->peminjamanRepository->getForUser($userId, $filters, $perPage);
    }

    /**
     * Get active peminjaman count for user.
     */
    public function getActiveCountForUser(int $userId): int
    {
        return $this->peminjamanRepository->countActiveForUser($userId);
    }

    /**
     * Check if user has quota available.
     */
    public function hasQuotaAvailable(int $userId): bool
    {
        $maxBorrowings = SystemSetting::get('max_active_borrowings', 3);
        $currentCount = $this->getActiveCountForUser($userId);

        return $currentCount < $maxBorrowings;
    }

    /**
     * Create new peminjaman.
     */
    public function createPeminjaman(array $data, array $saranaItems = [], ?UploadedFile $suratFile = null): Peminjaman
    {
        return $this->database->transaction(function () use ($data, $saranaItems, $suratFile) {
            // Upload surat if provided
            if ($suratFile) {
                $data['surat_path'] = $this->storeFile($suratFile, 'peminjaman/surat');
            }

            // Set default status
            $data['status'] = Peminjaman::STATUS_PENDING;

            // Create peminjaman
            $peminjaman = $this->peminjamanRepository->create($data);

            // Create peminjaman items
            if (!empty($saranaItems)) {
                foreach ($saranaItems as $item) {
                    PeminjamanItem::create([
                        'peminjaman_id' => $peminjaman->id,
                        'sarana_id' => $item['sarana_id'],
                        'qty_requested' => $item['qty_requested'],
                        'qty_approved' => null,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            // Initialize approval status
            PeminjamanApprovalStatus::create([
                'peminjaman_id' => $peminjaman->id,
                'overall_status' => PeminjamanApprovalStatus::OVERALL_PENDING,
                'global_approval_status' => PeminjamanApprovalStatus::GLOBAL_PENDING,
            ]);

            // Create approval workflows
            $this->createApprovalWorkflows($peminjaman);

            Log::info('Peminjaman created', [
                'peminjaman_id' => $peminjaman->id,
                'user_id' => $peminjaman->user_id,
            ]);

            PeminjamanStatusChanged::dispatch($peminjaman, null, $peminjaman->status);

            return $peminjaman->fresh(['items', 'approvalStatus', 'approvalWorkflow']);
        });
    }

    /**
     * Update peminjaman.
     */
    public function updatePeminjaman(Peminjaman $peminjaman, array $data, array $saranaItems = [], ?UploadedFile $suratFile = null): Peminjaman
    {
        return $this->database->transaction(function () use ($peminjaman, $data, $saranaItems, $suratFile) {
            // Upload new surat if provided
            if ($suratFile) {
                // Delete old file
                $this->deleteFile($peminjaman->surat_path);
                $data['surat_path'] = $this->storeFile($suratFile, 'peminjaman/surat');
            }

            // Update peminjaman
            $peminjaman = $this->peminjamanRepository->update($peminjaman, $data);

            // Update items if provided
            if (!empty($saranaItems)) {
                // Delete existing items
                $peminjaman->items()->delete();

                // Create new items
                foreach ($saranaItems as $item) {
                    PeminjamanItem::create([
                        'peminjaman_id' => $peminjaman->id,
                        'sarana_id' => $item['sarana_id'],
                        'qty_requested' => $item['qty_requested'],
                        'qty_approved' => null,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                // Recreate approval workflows for new items
                $this->recreateApprovalWorkflows($peminjaman);
            }

            Log::info('Peminjaman updated', [
                'peminjaman_id' => $peminjaman->id,
            ]);

            // Kirim notifikasi ke approver terkait jika peminjaman masih pending
            if ($peminjaman->status === Peminjaman::STATUS_PENDING) {
                $this->notifyApproversPeminjamanUpdated($peminjaman);
            }

            return $peminjaman->fresh(['items', 'approvalStatus', 'approvalWorkflow']);
        });
    }

    /**
     * Send notification to all related approvers when a pending peminjaman is updated.
     */
    protected function notifyApproversPeminjamanUpdated(Peminjaman $peminjaman): void
    {
        $workflows = $peminjaman->approvalWorkflow()
            ->with('approver')
            ->pending()
            ->get();

        $approvers = $workflows
            ->pluck('approver')
            ->filter()
            ->unique('id')
            ->values();

        if ($approvers->isEmpty()) {
            return;
        }

        NotificationBuilder::make()
            ->title('Pengajuan Peminjaman Diperbarui')
            ->message('Pengajuan peminjaman "' . $peminjaman->event_name . '" telah diperbarui dan menunggu persetujuan Anda.')
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('document-plus')
            ->color('info')
            ->priority('normal')
            ->category('approval')
            ->metadata([
                'peminjaman_id' => $peminjaman->id,
                'status' => $peminjaman->status,
            ])
            ->sendToUsers($approvers);
    }

    /**
     * Cancel peminjaman.
     */
    public function cancelPeminjaman(Peminjaman $peminjaman, int $cancelledBy, ?string $reason = null): Peminjaman
    {
        return $this->database->transaction(function () use ($peminjaman, $cancelledBy, $reason) {
            $oldStatus = $peminjaman->status;

            $peminjaman = $this->peminjamanRepository->updateStatus($peminjaman, Peminjaman::STATUS_CANCELLED, [
                'cancelled_by' => $cancelledBy,
                'cancelled_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            if ($oldStatus !== $peminjaman->status) {
                PeminjamanStatusChanged::dispatch($peminjaman, $oldStatus, $peminjaman->status);
            }

            Log::info('Peminjaman cancelled', [
                'peminjaman_id' => $peminjaman->id,
                'cancelled_by' => $cancelledBy,
            ]);

            return $peminjaman;
        });
    }

    /**
     * Delete peminjaman.
     */
    public function deletePeminjaman(Peminjaman $peminjaman): bool
    {
        return $this->database->transaction(function () use ($peminjaman) {
            // Delete uploaded files
            $this->deleteFile($peminjaman->surat_path);
            $this->deleteFile($peminjaman->foto_pickup_path);
            $this->deleteFile($peminjaman->foto_return_path);

            $result = $this->peminjamanRepository->delete($peminjaman);

            Log::info('Peminjaman deleted', [
                'peminjaman_id' => $peminjaman->id,
            ]);

            return $result;
        });
    }

    /**
     * Get pending peminjaman for approver.
     */
    public function getPendingForApprover(int $approverId): Collection
    {
        return $this->peminjamanRepository->getPendingForApprover($approverId);
    }

    /**
     * Get data for create/edit form.
     */
    public function getFormData(int $userId): array
    {
        $maxDuration = SystemSetting::get('max_duration_days', 7);
        $maxActiveBorrowings = SystemSetting::get('max_active_borrowings', 3);
        $currentBorrowings = $this->getActiveCountForUser($userId);

        return [
            'maxDuration' => $maxDuration,
            'maxActiveBorrowings' => $maxActiveBorrowings,
            'currentBorrowings' => $currentBorrowings,
            'canCreate' => $currentBorrowings < $maxActiveBorrowings,
        ];
    }

    /**
     * Create approval workflows for peminjaman.
     */
    protected function createApprovalWorkflows(Peminjaman $peminjaman): void
    {
        // 1. Global approvers (use GlobalApprover::user_id as approver_id)
        $globalApprovers = GlobalApprover::active()->get();
        foreach ($globalApprovers as $ga) {
            // Safety: skip if user_id is missing
            if (!$ga->user_id) {
                continue;
            }

            PeminjamanApprovalWorkflow::firstOrCreate([
                'peminjaman_id' => $peminjaman->id,
                'approver_id' => $ga->user_id,
                'approval_type' => PeminjamanApprovalWorkflow::TYPE_GLOBAL,
                'sarana_id' => null,
                'prasarana_id' => null,
            ], [
                'approval_level' => $ga->approval_level,
                'status' => PeminjamanApprovalWorkflow::STATUS_PENDING,
            ]);
        }

        // 2. Prasarana approvers (if prasarana selected)
        if ($peminjaman->prasarana_id) {
            $prasaranaApprovers = PrasaranaApprover::active()
                ->forPrasarana($peminjaman->prasarana_id)
                ->get();

            foreach ($prasaranaApprovers as $pa) {
                PeminjamanApprovalWorkflow::firstOrCreate([
                    'peminjaman_id' => $peminjaman->id,
                    'approver_id' => $pa->approver_id,
                    'approval_type' => PeminjamanApprovalWorkflow::TYPE_PRASARANA,
                    'sarana_id' => null,
                    'prasarana_id' => $peminjaman->prasarana_id,
                ], [
                    'approval_level' => $pa->approval_level,
                    'status' => PeminjamanApprovalWorkflow::STATUS_PENDING,
                ]);
            }
        }

        // 3. Sarana approvers for each requested sarana
        $saranaIds = $peminjaman->items()->pluck('sarana_id')->unique();
        foreach ($saranaIds as $saranaId) {
            $saranaApprovers = SaranaApprover::active()
                ->forSarana($saranaId)
                ->get();

            foreach ($saranaApprovers as $sa) {
                PeminjamanApprovalWorkflow::firstOrCreate([
                    'peminjaman_id' => $peminjaman->id,
                    'approver_id' => $sa->approver_id,
                    'approval_type' => PeminjamanApprovalWorkflow::TYPE_SARANA,
                    'sarana_id' => $saranaId,
                    'prasarana_id' => null,
                ], [
                    'approval_level' => $sa->approval_level,
                    'status' => PeminjamanApprovalWorkflow::STATUS_PENDING,
                ]);
            }
        }
    }

    /**
     * Recreate approval workflows (for update).
     */
    protected function recreateApprovalWorkflows(Peminjaman $peminjaman): void
    {
        // Delete existing workflows
        $peminjaman->approvalWorkflow()->delete();

        // Reset approval status
        $approvalStatus = $peminjaman->approvalStatus;
        if ($approvalStatus) {
            $approvalStatus->update([
                'overall_status' => PeminjamanApprovalStatus::OVERALL_PENDING,
                'global_approval_status' => PeminjamanApprovalStatus::GLOBAL_PENDING,
                'global_approved_by' => null,
                'global_approved_at' => null,
                'global_rejected_by' => null,
                'global_rejected_at' => null,
                'global_rejection_reason' => null,
            ]);
        }

        // Create new workflows
        $this->createApprovalWorkflows($peminjaman);
    }

    /**
     * Sync konflik group.
     */
    public function syncKonflikGroup(Peminjaman $peminjaman, Collection $pendingConflicts): void
    {
        if ($pendingConflicts->isEmpty()) {
            if (!empty($peminjaman->konflik)) {
                $remaining = Peminjaman::where('konflik', $peminjaman->konflik)
                    ->where('id', '!=', $peminjaman->id)
                    ->exists();

                if (!$remaining) {
                    $peminjaman->forceFill(['konflik' => null])->save();
                }
            }
            return;
        }

        $existingKonflik = $pendingConflicts->first(function ($conflict) {
            return !empty($conflict->konflik);
        })?->konflik;

        $konflikCode = $existingKonflik ?: 'KNF-' . strtoupper(Str::random(10));

        $ids = $pendingConflicts->pluck('id')->push($peminjaman->id);

        Peminjaman::whereIn('id', $ids)->update(['konflik' => $konflikCode]);
    }

    /**
     * Store uploaded file.
     */
    protected function storeFile(UploadedFile $file, string $baseDir): string
    {
        $dir = trim($baseDir, '/') . '/' . date('Y/m');
        return $file->store($dir, 'public');
    }

    /**
     * Delete file from storage.
     */
    protected function deleteFile(?string $path): void
    {
        if (!empty($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
