<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class NotificationBuilder
{
    protected string $title;
    protected string $message;
    protected ?string $actionText = null;
    protected ?string $actionUrl = null;
    protected string $icon = 'bell';
    protected string $color = 'info';
    protected string $priority = 'normal';
    protected string $category = 'general';
    protected array $metadata = [];

    /**
     * Create new notification builder
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Set notification title
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set notification message
     */
    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set action button
     */
    public function action(string $text, string $url): self
    {
        $this->actionText = $text;
        $this->actionUrl = $url;

        return $this;
    }

    /**
     * Set icon
     */
    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set color (success, danger, warning, info)
     */
    public function color(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Set priority (low, normal, high, urgent)
     */
    public function priority(string $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Set category
     */
    public function category(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Set metadata
     */
    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Add single metadata
     */
    public function addMeta(string $key, $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Build and send to user
     */
    public function sendTo(User $user): void
    {
        $this->sendToUsers([$user]);
    }

    /**
     * Build and send to multiple users
     */
    public function sendToUsers(array|Collection $users): void
    {
        $notification = $this->build();

        if ($users instanceof Collection) {
            $users = $users->all();
        }

        $validUsers = collect($users)->filter(function ($user) {
            return $user instanceof User && $this->shouldSendNotification($user);
        });

        if ($validUsers->isEmpty()) {
            return;
        }

        Notification::send($validUsers, $notification);

        $this->clearCache($validUsers->all());
    }

    /**
     * Build and send to users with permission
     */
    public function sendToPermission(string $permission): void
    {
        $users = User::permission($permission)->active()->get();
        $this->sendToUsers($users);
    }

    /**
     * Build and send to users with role
     */
    public function sendToRole(string $role): void
    {
        $users = User::role($role)->active()->get();
        $this->sendToUsers($users);
    }

    /**
     * Build notification instance
     */
    protected function build(): GeneralNotification
    {
        return new GeneralNotification([
            'title' => $this->title ?? 'Notifikasi',
            'message' => $this->message ?? '',
            'action_text' => $this->actionText,
            'action_url' => $this->actionUrl,
            'icon' => $this->icon,
            'color' => $this->color,
            'priority' => $this->priority,
            'category' => $this->category,
            'metadata' => $this->metadata,
        ]);
    }

    /**
     * Check if notification should be sent
     */
    protected function shouldSendNotification(User $user): bool
    {
        // Only send to active users
        if (! $user->isActive()) {
            return false;
        }

        // Future: check user notification preferences
        return true;
    }

    /**
     * Clear notification cache for users
     */
    protected function clearCache(array $users): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                Cache::forget("notifications.unread.{$user->id}");
                Cache::forget("notifications.recent.{$user->id}");
            }
        }
    }
}
