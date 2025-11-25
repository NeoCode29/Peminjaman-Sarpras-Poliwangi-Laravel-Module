<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    /**
     * Mark notification as read
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
            $this->clearUserCache($user);

            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(User $user): int
    {
        $count = $user->unreadNotifications()->count();
        $user->unreadNotifications()->update(['read_at' => now()]);
        $this->clearUserCache($user);

        return $count;
    }

    /**
     * Delete notification
     */
    public function delete(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if ($notification) {
            $notification->delete();
            $this->clearUserCache($user);

            return true;
        }

        return false;
    }

    /**
     * Delete old notifications
     */
    public function deleteOldNotifications(int $daysOld = 90): int
    {
        return \Illuminate\Notifications\DatabaseNotification::where(
            'created_at',
            '<',
            now()->subDays($daysOld)
        )->delete();
    }

    /**
     * Clear user notification cache
     */
    protected function clearUserCache(User $user): void
    {
        Cache::forget("notifications.unread.{$user->id}");
        Cache::forget("notifications.recent.{$user->id}");
    }
}
