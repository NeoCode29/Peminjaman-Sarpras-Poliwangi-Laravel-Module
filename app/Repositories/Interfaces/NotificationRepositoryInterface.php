<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    /**
     * Get user notifications with pagination and filters
     */
    public function getUserNotifications(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * Get unread count with caching
     */
    public function getUnreadCount(User $user): int;

    /**
     * Get recent unread notifications (for header dropdown)
     */
    public function getRecentUnread(User $user, int $limit = 5): Collection;

    /**
     * Get statistics
     */
    public function getStatistics(User $user): array;

    /**
     * Get count grouped by category
     */
    public function getCountByCategory(User $user): array;

    /**
     * Get available categories
     */
    public function getCategories(): array;
}
