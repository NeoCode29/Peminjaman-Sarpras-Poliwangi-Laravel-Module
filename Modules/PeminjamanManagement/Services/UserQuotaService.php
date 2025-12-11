<?php

namespace Modules\PeminjamanManagement\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Entities\UserQuota;

class UserQuotaService
{
    /**
     * Get or create quota for user.
     */
    public function getOrCreateQuota(int $userId): UserQuota
    {
        $maxBorrowings = SystemSetting::get('max_active_borrowings', 3);
        return UserQuota::getOrCreateForUser($userId, $maxBorrowings);
    }

    /**
     * Check if user has quota available.
     */
    public function hasQuotaAvailable(int $userId): bool
    {
        $quota = $this->getOrCreateQuota($userId);
        return $quota->hasQuotaAvailable();
    }

    /**
     * Get remaining quota for user.
     */
    public function getRemainingQuota(int $userId): int
    {
        $quota = $this->getOrCreateQuota($userId);
        return $quota->remaining_quota;
    }

    /**
     * Increment quota when peminjaman is created.
     */
    public function incrementQuota(int $userId): void
    {
        $quota = $this->getOrCreateQuota($userId);
        $quota->increment();

        Log::info('User quota incremented', [
            'user_id' => $userId,
            'active_borrowings' => $quota->active_borrowings,
        ]);
    }

    /**
     * Decrement quota when peminjaman is finished.
     */
    public function decrementQuota(int $userId): void
    {
        $quota = $this->getOrCreateQuota($userId);
        $quota->decrement();

        Log::info('User quota decremented', [
            'user_id' => $userId,
            'active_borrowings' => $quota->active_borrowings,
        ]);
    }

    /**
     * Decrement quota if peminjaman becomes inactive.
     */
    public function decrementIfInactive(Peminjaman $peminjaman): void
    {
        $inactiveStatuses = [
            Peminjaman::STATUS_RETURNED,
            Peminjaman::STATUS_CANCELLED,
            Peminjaman::STATUS_REJECTED,
        ];

        if (in_array($peminjaman->status, $inactiveStatuses, true)) {
            $this->decrementQuota($peminjaman->user_id);
        }
    }

    /**
     * Recalculate quota for user based on actual active peminjaman.
     */
    public function recalculateQuota(int $userId): void
    {
        $activeCount = Peminjaman::forUser($userId)->active()->count();

        $quota = $this->getOrCreateQuota($userId);
        $quota->active_borrowings = $activeCount;
        $quota->save();

        Log::info('User quota recalculated', [
            'user_id' => $userId,
            'active_borrowings' => $activeCount,
        ]);
    }

    /**
     * Update max borrowings for user.
     */
    public function updateMaxBorrowings(int $userId, int $maxBorrowings): void
    {
        $quota = $this->getOrCreateQuota($userId);
        $quota->max_borrowings = $maxBorrowings;
        $quota->save();
    }
}
