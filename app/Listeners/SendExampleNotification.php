<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Example listener untuk demonstrasi General Notification System
 * 
 * Cara penggunaan:
 * 1. Create event yang memicu listener ini
 * 2. Dispatch event di controller/service
 * 3. Listener otomatis send notification ke user
 * 
 * Example usage di listener lain:
 * 
 * use App\Services\NotificationBuilder;
 * 
 * public function handle($event): void
 * {
 *     NotificationBuilder::make()
 *         ->title('Judul Notifikasi')
 *         ->message('Pesan notifikasi')
 *         ->action('Lihat', route('...'))
 *         ->icon('check-circle')
 *         ->color('success')
 *         ->category('system')
 *         ->sendTo($event->user);
 * }
 */
class SendExampleNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event
     */
    public function handle($event): void
    {
        // Example: Simple notification
        NotificationBuilder::make()
            ->title('Welcome!')
            ->message('This is an example notification')
            ->icon('bell')
            ->color('info')
            ->category('general')
            ->sendTo($event->user);

        // Example: With action button
        NotificationBuilder::make()
            ->title('Action Required')
            ->message('Please complete your profile')
            ->action('Complete Profile', route('profile.edit'))
            ->icon('user-circle')
            ->color('warning')
            ->priority('high')
            ->category('system')
            ->sendTo($event->user);

        // Example: Send to multiple users
        NotificationBuilder::make()
            ->title('Broadcast Message')
            ->message('Important announcement for all users')
            ->icon('megaphone')
            ->color('info')
            ->category('system')
            ->sendToPermission('user.view');

        // Example: With metadata
        NotificationBuilder::make()
            ->title('Item Approved')
            ->message('Your request has been approved')
            ->action('View Details', route('dashboard'))
            ->icon('check-circle')
            ->color('success')
            ->priority('high')
            ->category('approval')
            ->metadata([
                'request_id' => 123,
                'approved_by' => 'Admin',
                'approved_at' => now()->toDateTimeString(),
            ])
            ->sendTo($event->user);
    }

    /**
     * Handle a job failure
     */
    public function failed($event, \Throwable $exception): void
    {
        \Log::error('Failed to send example notification', [
            'error' => $exception->getMessage(),
            'event' => get_class($event),
        ]);
    }
}
