<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $data;

    /**
     * Create a new notification instance
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->data['title'] ?? 'Notifikasi',
            'message' => $this->data['message'] ?? '',
            'action_text' => $this->data['action_text'] ?? null,
            'action_url' => $this->data['action_url'] ?? null,
            'icon' => $this->data['icon'] ?? 'bell',
            'color' => $this->data['color'] ?? 'info',
            'priority' => $this->data['priority'] ?? 'normal',
            'category' => $this->data['category'] ?? 'general',
            'metadata' => $this->data['metadata'] ?? [],
        ];
    }

    /**
     * Get notification data (for testing)
     */
    public function getData(): array
    {
        return $this->data;
    }
}
