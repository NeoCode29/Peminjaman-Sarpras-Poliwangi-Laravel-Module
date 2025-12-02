<?php

namespace Modules\MarkingManagement\Services;

use Modules\MarkingManagement\Entities\Marking;
use Modules\MarkingManagement\Repositories\Interfaces\MarkingRepositoryInterface;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MarkingService
{
    public function __construct(
        private readonly MarkingRepositoryInterface $markingRepository,
        private readonly DatabaseManager $database
    ) {}

    /**
     * Get all markings with filters
     */
    public function getMarkings(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->markingRepository->getAll($filters, $perPage);
    }

    /**
     * Get markings for specific user
     */
    public function getMarkingsForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->markingRepository->getByUser($userId, $filters, $perPage);
    }

    /**
     * Get marking by ID
     */
    public function getMarkingById(int $id): ?Marking
    {
        return $this->markingRepository->findById($id);
    }

    /**
     * Create a new marking
     */
    public function createMarking(array $data): Marking
    {
        return $this->database->transaction(function () use ($data) {
            // Calculate expiration time
            $markingDuration = $this->getMarkingDuration();
            $expiresAt = now()->addDays($markingDuration);
            
            // If planned_submit_by is set and earlier than expires_at, use that as expires_at
            if (!empty($data['planned_submit_by'])) {
                $plannedSubmitBy = Carbon::parse($data['planned_submit_by']);
                if ($plannedSubmitBy->lt($expiresAt)) {
                    $expiresAt = $plannedSubmitBy;
                }
            }

            // Create marking
            $marking = $this->markingRepository->create([
                'user_id' => Auth::id(),
                'ukm_id' => $data['ukm_id'] ?? null,
                'prasarana_id' => $data['prasarana_id'] ?? null,
                'lokasi_custom' => $data['lokasi_custom'] ?? null,
                'start_datetime' => $data['start_datetime'],
                'end_datetime' => $data['end_datetime'],
                'jumlah_peserta' => $data['jumlah_peserta'] ?? null,
                'expires_at' => $expiresAt,
                'planned_submit_by' => $data['planned_submit_by'] ?? null,
                'status' => Marking::STATUS_ACTIVE,
                'event_name' => $data['event_name'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $marking->fresh(['user', 'ukm', 'prasarana']);
        });
    }

    /**
     * Update an existing marking
     */
    public function updateMarking(Marking $marking, array $data): Marking
    {
        if (!$marking->isActive()) {
            throw new RuntimeException('Marking tidak dapat diedit karena sudah tidak aktif.');
        }

        if ($marking->isExpired()) {
            throw new RuntimeException('Marking tidak dapat diedit karena sudah kadaluarsa.');
        }

        return $this->database->transaction(function () use ($marking, $data) {
            // Update marking
            $marking = $this->markingRepository->update($marking, [
                'ukm_id' => $data['ukm_id'] ?? null,
                'prasarana_id' => $data['prasarana_id'] ?? null,
                'lokasi_custom' => $data['lokasi_custom'] ?? null,
                'start_datetime' => $data['start_datetime'],
                'end_datetime' => $data['end_datetime'],
                'jumlah_peserta' => $data['jumlah_peserta'] ?? null,
                'planned_submit_by' => $data['planned_submit_by'] ?? null,
                'event_name' => $data['event_name'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $marking->fresh(['user', 'ukm', 'prasarana']);
        });
    }

    /**
     * Cancel a marking
     */
    public function cancelMarking(Marking $marking): Marking
    {
        if (!$marking->isActive()) {
            throw new RuntimeException('Marking tidak dapat dibatalkan karena sudah tidak aktif.');
        }

        return $this->database->transaction(function () use ($marking) {
            return $this->markingRepository->update($marking, [
                'status' => Marking::STATUS_CANCELLED
            ]);
        });
    }

    /**
     * Extend marking expiration
     */
    public function extendMarking(Marking $marking, int $days): Marking
    {
        if (!$marking->isActive()) {
            throw new RuntimeException('Marking tidak dapat diperpanjang karena sudah tidak aktif.');
        }

        if ($marking->isExpired()) {
            throw new RuntimeException('Marking tidak dapat diperpanjang karena sudah kadaluarsa.');
        }

        $maxExtension = config('markingmanagement.max_extension_days', 7);
        if ($days > $maxExtension) {
            throw new RuntimeException("Perpanjangan maksimal {$maxExtension} hari.");
        }

        return $this->database->transaction(function () use ($marking, $days) {
            $newExpiresAt = $marking->expires_at->addDays($days);
            return $this->markingRepository->update($marking, [
                'expires_at' => $newExpiresAt
            ]);
        });
    }

    /**
     * Mark as converted (when converted to peminjaman)
     */
    public function markAsConverted(Marking $marking): Marking
    {
        if (!$marking->canBeConverted()) {
            throw new RuntimeException('Marking tidak dapat dikonversi.');
        }

        return $this->database->transaction(function () use ($marking) {
            return $this->markingRepository->update($marking, [
                'status' => Marking::STATUS_CONVERTED
            ]);
        });
    }

    /**
     * Check for conflicts with existing markings
     */
    public function checkConflicts(array $data, ?int $excludeId = null): ?string
    {
        $conflict = $this->markingRepository->checkConflicts($data, $excludeId);
        
        if ($conflict) {
            if (!empty($data['prasarana_id'])) {
                return "Prasarana sudah di-marking pada periode tersebut oleh {$conflict->user->name}.";
            }
            if (!empty($data['lokasi_custom'])) {
                return "Lokasi custom sudah di-marking pada periode tersebut oleh {$conflict->user->name}.";
            }
        }

        return null;
    }

    /**
     * Auto expire markings that have passed their expiration date
     */
    public function autoExpireMarkings(): int
    {
        $expiredCount = 0;
        
        try {
            $expiredMarkings = $this->markingRepository->getExpiredMarkings();
            
            foreach ($expiredMarkings as $marking) {
                $this->markingRepository->update($marking, [
                    'status' => Marking::STATUS_EXPIRED
                ]);
                $expiredCount++;
            }
        } catch (\Exception $e) {
            Log::error('Error auto expiring markings: ' . $e->getMessage());
        }
        
        return $expiredCount;
    }

    /**
     * Get markings expiring soon
     */
    public function getExpiringSoon(int $hours = 24): Collection
    {
        return $this->markingRepository->getExpiringSoon($hours);
    }

    /**
     * Get marking duration from system settings or config
     */
    private function getMarkingDuration(): int
    {
        // Try to get from SystemSetting if exists
        if (class_exists(SystemSetting::class) && method_exists(SystemSetting::class, 'get')) {
            $duration = SystemSetting::get('marking_duration_days');
            if ($duration) {
                return (int) $duration;
            }
        }

        return config('markingmanagement.marking_duration_days', 3);
    }
}
