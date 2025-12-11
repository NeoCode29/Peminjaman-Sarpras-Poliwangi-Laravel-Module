<?php

namespace Modules\PeminjamanManagement\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Entities\PeminjamanApprovalStatus;
use Modules\PeminjamanManagement\Entities\PeminjamanApprovalWorkflow;
use Modules\PeminjamanManagement\Entities\PeminjamanItem;
use Modules\PeminjamanManagement\Events\PeminjamanStatusChanged;
use Modules\SaranaManagement\Entities\Sarana;

class PeminjamanApprovalService
{
    /**
     * Approve a workflow item and recalculate overall status.
     */
    public function approveWorkflow(PeminjamanApprovalWorkflow $workflow, ?string $notes = null): void
    {
        DB::transaction(function () use ($workflow, $notes) {
            $workflow->approve($notes);
            $this->recalculateOverallStatus($workflow->peminjaman_id);
        });
    }

    /**
     * Reject a workflow item and recalculate overall status.
     */
    public function rejectWorkflow(PeminjamanApprovalWorkflow $workflow, ?string $notes = null): void
    {
        DB::transaction(function () use ($workflow, $notes) {
            $workflow->reject($notes);
            $this->recalculateOverallStatus($workflow->peminjaman_id);
        });
    }

    /**
     * Override workflow with higher-level approver decision.
     */
    public function overrideWorkflow(PeminjamanApprovalWorkflow $workflow, string $action, ?string $reason = null): void
    {
        DB::transaction(function () use ($workflow, $action, $reason) {
            $overrideUserId = Auth::id();

            if ($action === 'approve') {
                $workflow->approve($reason);
            } else {
                $workflow->reject($reason);
            }

            $workflow->fill([
                'overridden_by' => $overrideUserId,
                'overridden_at' => now(),
            ])->save();

            $this->recalculateOverallStatus($workflow->peminjaman_id);
        });
    }

    /**
     * Recalculate and persist overall status for a peminjaman.
     */
    public function recalculateOverallStatus(int $peminjamanId): void
    {
        $peminjaman = Peminjaman::findOrFail($peminjamanId);
        $oldStatus = $peminjaman->status;

        /** @var PeminjamanApprovalStatus $status */
        $status = PeminjamanApprovalStatus::firstOrCreate([
            'peminjaman_id' => $peminjaman->id,
        ], [
            'overall_status' => PeminjamanApprovalStatus::OVERALL_PENDING,
            'global_approval_status' => PeminjamanApprovalStatus::GLOBAL_PENDING,
        ]);

        $status->updateOverallStatus();

        // Sync peminjaman.status when finalized
        if ($status->overall_status === PeminjamanApprovalStatus::OVERALL_APPROVED) {
            $peminjaman->update(['status' => Peminjaman::STATUS_APPROVED]);
            $peminjaman->refresh();

            if ($oldStatus !== $peminjaman->status) {
                PeminjamanStatusChanged::dispatch($peminjaman, $oldStatus, $peminjaman->status);
            }

            $this->resolveKonflikGroup($peminjaman, true);
        } elseif ($status->overall_status === PeminjamanApprovalStatus::OVERALL_REJECTED) {
            $peminjaman->update(['status' => Peminjaman::STATUS_REJECTED]);
            $peminjaman->refresh();

            if ($oldStatus !== $peminjaman->status) {
                PeminjamanStatusChanged::dispatch($peminjaman, $oldStatus, $peminjaman->status);
            }

            $this->resolveKonflikGroup($peminjaman, false);
        }
    }

    /**
     * Approve global approval.
     */
    public function approveGlobal(int $peminjamanId, int $approverId, ?string $notes = null, ?string $conflictDecision = null): bool
    {
        try {
            DB::beginTransaction();

            $workflow = $this->findGlobalWorkflow($peminjamanId, $approverId);

            if (!$workflow) {
                throw new \RuntimeException('Tidak ada workflow global pending untuk disetujui.');
            }

            $workflow->approve($notes);

            // Update approval status
            $approvalStatus = PeminjamanApprovalStatus::where('peminjaman_id', $peminjamanId)->first();
            if ($approvalStatus) {
                $approvalStatus->setGlobalApproval($approverId, $notes);
                $approvalStatus->updateOverallStatus();
            }

            // Batalkan anggota konflik jika semua approval global sudah disetujui,
            // kecuali approver memilih untuk tetap mempertahankan peminjaman lain.
            $globalPending = PeminjamanApprovalWorkflow::forPeminjaman($peminjamanId)
                ->global()
                ->pending()
                ->exists();

            if (!$globalPending) {
                $peminjaman = Peminjaman::find($peminjamanId);

                if ($peminjaman && !empty($peminjaman->konflik)) {
                    if ($conflictDecision !== 'keep_others') {
                        $this->cancelKonflikMembers($peminjaman);
                    }
                    // Jika conflictDecision === 'keep_others', tidak membatalkan anggota konflik.
                }
            }

            DB::commit();

            Log::info('Global approval approved', [
                'peminjaman_id' => $peminjamanId,
                'approver_id' => $approverId,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving global', [
                'peminjaman_id' => $peminjamanId,
                'approver_id' => $approverId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject global approval.
     */
    public function rejectGlobal(int $peminjamanId, int $approverId, ?string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            $workflow = $this->findGlobalWorkflow($peminjamanId, $approverId);

            if (!$workflow) {
                throw new \RuntimeException('Tidak ada workflow global pending untuk ditolak.');
            }

            $workflow->reject($reason);

            // Update approval status
            $approvalStatus = PeminjamanApprovalStatus::where('peminjaman_id', $peminjamanId)->first();
            if ($approvalStatus) {
                $approvalStatus->setGlobalRejection($approverId, $reason);
                $approvalStatus->updateOverallStatus();
            }

            DB::commit();

            Log::info('Global approval rejected', [
                'peminjaman_id' => $peminjamanId,
                'approver_id' => $approverId,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting global', [
                'peminjaman_id' => $peminjamanId,
                'approver_id' => $approverId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Approve specific sarana.
     */
    public function approveSpecificSarana(int $peminjamanId, int $saranaId, int $approverId, ?string $notes = null): bool
    {
        try {
            DB::beginTransaction();

            $workflow = $this->findSaranaWorkflow($peminjamanId, $saranaId, $approverId);

            if (!$workflow) {
                throw new \RuntimeException('Tidak ada workflow sarana pending untuk disetujui.');
            }

            $workflow->approve($notes);

            // Sync pooled sarana quantity
            $this->syncPooledSaranaQuantity($peminjamanId, $saranaId, 'approve');

            $approvalStatus = PeminjamanApprovalStatus::where('peminjaman_id', $peminjamanId)->first();
            if ($approvalStatus) {
                $approvalStatus->updateOverallStatus();
            }

            DB::commit();

            Log::info('Specific sarana approved', [
                'peminjaman_id' => $peminjamanId,
                'sarana_id' => $saranaId,
                'approver_id' => $approverId,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving specific sarana', [
                'peminjaman_id' => $peminjamanId,
                'sarana_id' => $saranaId,
                'approver_id' => $approverId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject specific sarana.
     */
    public function rejectSpecificSarana(int $peminjamanId, int $saranaId, int $approverId, ?string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            $workflow = $this->findSaranaWorkflow($peminjamanId, $saranaId, $approverId);

            if (!$workflow) {
                throw new \RuntimeException('Tidak ada workflow sarana pending untuk ditolak.');
            }

            $workflow->reject($reason);

            // Sync pooled sarana quantity
            $this->syncPooledSaranaQuantity($peminjamanId, $saranaId, 'reject');

            $approvalStatus = PeminjamanApprovalStatus::where('peminjaman_id', $peminjamanId)->first();
            if ($approvalStatus) {
                $approvalStatus->updateOverallStatus();
            }

            DB::commit();

            Log::info('Specific sarana rejected', [
                'peminjaman_id' => $peminjamanId,
                'sarana_id' => $saranaId,
                'approver_id' => $approverId,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting specific sarana', [
                'peminjaman_id' => $peminjamanId,
                'sarana_id' => $saranaId,
                'approver_id' => $approverId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Approve specific prasarana.
     */
    public function approveSpecificPrasarana(int $peminjamanId, int $prasaranaId, int $approverId, ?string $notes = null): bool
    {
        try {
            DB::beginTransaction();

            $workflow = $this->findPrasaranaWorkflow($peminjamanId, $prasaranaId, $approverId);

            if (!$workflow) {
                throw new \RuntimeException('Tidak ada workflow prasarana pending untuk disetujui.');
            }

            $workflow->approve($notes);

            $approvalStatus = PeminjamanApprovalStatus::where('peminjaman_id', $peminjamanId)->first();
            if ($approvalStatus) {
                $approvalStatus->updateOverallStatus();
            }

            DB::commit();

            Log::info('Specific prasarana approved', [
                'peminjaman_id' => $peminjamanId,
                'prasarana_id' => $prasaranaId,
                'approver_id' => $approverId,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving specific prasarana', [
                'peminjaman_id' => $peminjamanId,
                'prasarana_id' => $prasaranaId,
                'approver_id' => $approverId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject specific prasarana.
     */
    public function rejectSpecificPrasarana(int $peminjamanId, int $prasaranaId, int $approverId, ?string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            $workflow = $this->findPrasaranaWorkflow($peminjamanId, $prasaranaId, $approverId);

            if (!$workflow) {
                throw new \RuntimeException('Tidak ada workflow prasarana pending untuk ditolak.');
            }

            $workflow->reject($reason);

            $approvalStatus = PeminjamanApprovalStatus::where('peminjaman_id', $peminjamanId)->first();
            if ($approvalStatus) {
                $approvalStatus->updateOverallStatus();
            }

            DB::commit();

            Log::info('Specific prasarana rejected', [
                'peminjaman_id' => $peminjamanId,
                'prasarana_id' => $prasaranaId,
                'approver_id' => $approverId,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting specific prasarana', [
                'peminjaman_id' => $peminjamanId,
                'prasarana_id' => $prasaranaId,
                'approver_id' => $approverId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get approval status for peminjaman.
     */
    public function getApprovalStatus(int $peminjamanId): ?array
    {
        $approvalStatus = PeminjamanApprovalStatus::where('peminjaman_id', $peminjamanId)->first();

        if (!$approvalStatus) {
            return null;
        }

        $workflows = PeminjamanApprovalWorkflow::forPeminjaman($peminjamanId)
            ->with(['approver', 'sarana', 'prasarana'])
            ->get();

        return [
            'approval_status' => $approvalStatus,
            'workflows' => $workflows,
            'global_workflows' => $workflows->where('approval_type', 'global'),
            'sarana_workflows' => $workflows->where('approval_type', 'sarana'),
            'prasarana_workflows' => $workflows->where('approval_type', 'prasarana'),
        ];
    }

    /**
     * Get pending approvals for approver.
     */
    public function getPendingApprovals(int $approverId): Collection
    {
        $workflows = PeminjamanApprovalWorkflow::forApprover($approverId)
            ->pending()
            ->with(['peminjaman', 'sarana', 'prasarana'])
            ->get();

        // Hanya kembalikan workflow yang berada pada level terendah yang masih pending
        return $workflows->filter(function (PeminjamanApprovalWorkflow $workflow) {
            return $this->isLowestPendingLevel($workflow);
        })->values();
    }

    /**
     * Check whether the given workflow is at the lowest pending level
     * for its (peminjaman, approval_type, resource) context.
     */
    protected function isLowestPendingLevel(PeminjamanApprovalWorkflow $workflow): bool
    {
        $query = PeminjamanApprovalWorkflow::forPeminjaman($workflow->peminjaman_id)
            ->byType($workflow->approval_type)
            ->pending();

        if ($workflow->isSpecificSarana() && $workflow->sarana_id) {
            $query->forSarana($workflow->sarana_id);
        } elseif ($workflow->isSpecificPrasarana() && $workflow->prasarana_id) {
            $query->forPrasarana($workflow->prasarana_id);
        }

        $minLevel = $query->min('approval_level');

        if ($minLevel === null) {
            return false;
        }

        return (int) $workflow->approval_level === (int) $minLevel;
    }

    /**
     * Resolve konflik group.
     */
    protected function resolveKonflikGroup(Peminjaman $peminjaman, bool $approved): void
    {
        if (empty($peminjaman->konflik)) {
            return;
        }

        $konflikCode = $peminjaman->konflik;

        $konflikMembers = Peminjaman::where('konflik', $konflikCode)
            ->where('id', '!=', $peminjaman->id)
            ->get();

        if ($konflikMembers->isEmpty()) {
            $peminjaman->forceFill(['konflik' => null])->save();
            return;
        }

        if ($approved) {
            $this->cancelKonflikMembers($peminjaman);
            return;
        }

        $pendingLeft = $konflikMembers->where('status', Peminjaman::STATUS_PENDING);

        if ($pendingLeft->isEmpty()) {
            $konflikMembers->each(fn ($member) => $member->forceFill(['konflik' => null])->save());
            $peminjaman->forceFill(['konflik' => null])->save();
        }
    }

    /**
     * Cancel konflik members.
     */
    protected function cancelKonflikMembers(?Peminjaman $peminjaman): void
    {
        if (!$peminjaman || empty($peminjaman->konflik)) {
            return;
        }

        $konflikCode = $peminjaman->konflik;
        $members = Peminjaman::where('konflik', $konflikCode)
            ->where('id', '!=', $peminjaman->id)
            ->get();

        if ($members->isEmpty()) {
            $peminjaman->forceFill(['konflik' => null])->save();
            return;
        }

        $cancelledBy = Auth::id();
        $now = now();

        foreach ($members as $member) {
            if ($member->status === Peminjaman::STATUS_PENDING) {
                $member->update([
                    'status' => Peminjaman::STATUS_CANCELLED,
                    'cancelled_by' => $cancelledBy,
                    'cancelled_reason' => trim(($member->cancelled_reason ?? '') . "\nDibatalkan otomatis karena konflik dengan peminjaman {$peminjaman->id}."),
                ]);
                $member->forceFill(['cancelled_at' => $now])->save();
            } else {
                $member->forceFill(['konflik' => null])->save();
            }
        }

        $peminjaman->forceFill(['konflik' => null])->save();
    }

    /**
     * Find global workflow.
     */
    protected function findGlobalWorkflow(int $peminjamanId, int $approverId): ?PeminjamanApprovalWorkflow
    {
        $workflow = PeminjamanApprovalWorkflow::forPeminjaman($peminjamanId)
            ->forApprover($approverId)
            ->global()
            ->pending()
            ->first();

        if (!$workflow) {
            // Fallback: check if user has permission
            $user = User::find($approverId);
            if ($user && $user->can('peminjaman.approve_global')) {
                $workflow = PeminjamanApprovalWorkflow::forPeminjaman($peminjamanId)
                    ->global()
                    ->pending()
                    ->orderBy('approval_level')
                    ->first();
            }
        }

        if ($workflow && !$this->isLowestPendingLevel($workflow)) {
            return null;
        }

        return $workflow;
    }

    /**
     * Find sarana workflow.
     */
    protected function findSaranaWorkflow(int $peminjamanId, int $saranaId, int $approverId): ?PeminjamanApprovalWorkflow
    {
        $workflow = PeminjamanApprovalWorkflow::forPeminjaman($peminjamanId)
            ->forApprover($approverId)
            ->specificSarana()
            ->forSarana($saranaId)
            ->pending()
            ->first();

        if (!$workflow) {
            $user = User::find($approverId);
            if ($user && $user->can('peminjaman.approve_specific')) {
                $workflow = PeminjamanApprovalWorkflow::forPeminjaman($peminjamanId)
                    ->specificSarana()
                    ->forSarana($saranaId)
                    ->pending()
                    ->orderBy('approval_level')
                    ->first();
            }
        }

        if ($workflow && !$this->isLowestPendingLevel($workflow)) {
            return null;
        }

        return $workflow;
    }

    /**
     * Find prasarana workflow.
     */
    protected function findPrasaranaWorkflow(int $peminjamanId, int $prasaranaId, int $approverId): ?PeminjamanApprovalWorkflow
    {
        $workflow = PeminjamanApprovalWorkflow::forPeminjaman($peminjamanId)
            ->forApprover($approverId)
            ->specificPrasarana()
            ->forPrasarana($prasaranaId)
            ->pending()
            ->first();

        if (!$workflow) {
            $user = User::find($approverId);
            if ($user && $user->can('peminjaman.approve_specific')) {
                $workflow = PeminjamanApprovalWorkflow::forPeminjaman($peminjamanId)
                    ->specificPrasarana()
                    ->forPrasarana($prasaranaId)
                    ->pending()
                    ->orderBy('approval_level')
                    ->first();
            }
        }

        if ($workflow && !$this->isLowestPendingLevel($workflow)) {
            return null;
        }

        return $workflow;
    }

    /**
     * Sync pooled sarana quantity.
     */
    protected function syncPooledSaranaQuantity(int $peminjamanId, ?int $saranaId, string $mode = 'approve'): void
    {
        if (!$saranaId) {
            return;
        }

        $sarana = Sarana::find($saranaId);
        if (!$sarana || $sarana->type !== 'pooled') {
            return;
        }

        $item = PeminjamanItem::where('peminjaman_id', $peminjamanId)
            ->where('sarana_id', $saranaId)
            ->first();

        if (!$item) {
            return;
        }

        if ($mode === 'approve') {
            if (in_array($item->qty_approved, [null, 0], true)) {
                $item->qty_approved = $item->qty_requested;
                $item->save();
            }
        } else {
            if ($item->qty_approved !== 0) {
                $item->qty_approved = 0;
                $item->save();
            }
        }

        $sarana->updateStats();
    }
}
