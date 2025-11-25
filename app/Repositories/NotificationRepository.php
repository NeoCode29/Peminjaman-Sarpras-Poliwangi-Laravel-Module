<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Get user notifications with pagination and filters
     */
    public function getUserNotifications(
        User $user,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $user->notifications();

        // Filter by read status
        if (isset($filters['read'])) {
            if ($filters['read'] === 'unread') {
                $query->whereNull('read_at');
            } elseif ($filters['read'] === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        // Filter by category
        if (isset($filters['category']) && $filters['category'] !== 'all') {
            $query->where('data->category', $filters['category']);
        }

        // Filter by priority
        if (isset($filters['priority'])) {
            $query->where('data->priority', $filters['priority']);
        }

        // Filter by date range
        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        // Search in title or message
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('data->title', 'like', "%{$search}%")
                    ->orWhere('data->message', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get unread count with caching
     */
    public function getUnreadCount(User $user): int
    {
        return Cache::remember(
            "notifications.unread.{$user->id}",
            now()->addMinutes(5),
            fn () => $user->unreadNotifications()->count()
        );
    }

    /**
     * Get recent unread notifications (for header dropdown)
     */
    public function getRecentUnread(User $user, int $limit = 5): Collection
    {
        return Cache::remember(
            "notifications.recent.{$user->id}",
            now()->addMinutes(2),
            fn () => $user->unreadNotifications()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get statistics
     */
    public function getStatistics(User $user): array
    {
        return [
            'total' => $user->notifications()->count(),
            'unread' => $this->getUnreadCount($user),
            'today' => $user->notifications()
                ->whereDate('created_at', today())
                ->count(),
            'this_week' => $user->notifications()
                ->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])->count(),
            'by_category' => $this->getCountByCategory($user),
        ];
    }

    /**
     * Get count grouped by category
     */
    public function getCountByCategory(User $user): array
    {
        $notifications = $user->notifications()->get();

        $grouped = $notifications->groupBy(function ($notification) {
            return $notification->data['category'] ?? 'other';
        });

        return $grouped->map->count()->toArray();
    }

    /**
     * Get available categories
     */
    public function getCategories(): array
    {
        return [
            'all' => 'Semua',
            'peminjaman' => 'Peminjaman',
            'approval' => 'Approval',
            'system' => 'Sistem',
            'reminder' => 'Pengingat',
            'conflict' => 'Konflik',
            'other' => 'Lainnya',
        ];
    }
}
