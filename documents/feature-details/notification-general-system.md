# General Notification System - Listener-Friendly Architecture

**Tanggal:** 20 November 2024  
**Versi:** 2.0 - General Purpose

---

## 1. Overview

### 1.1 Tujuan
Sistem notifikasi **general-purpose** yang dapat dipanggil dari **listener manapun** tanpa terikat pada event atau context tertentu.

### 1.2 Prinsip Desain General System
- **Loose Coupling**: Notification system tidak tahu tentang business logic
- **Listener-Friendly**: Mudah dipanggil dari listener apapun
- **Generic Builder**: Gunakan builder pattern untuk flexibility
- **Template-Based**: Support template reusable
- **Event-Agnostic**: Tidak terikat pada event spesifik

---

## 2. Core Architecture

### 2.1 General Notification Builder

**File:** `app/Services/NotificationBuilder.php`

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Collection;

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

        foreach ($users as $user) {
            if ($user instanceof User && $user->isActive()) {
                $user->notify($notification);
            }
        }

        $this->clearCache($users);
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
            'title' => $this->title,
            'message' => $this->message,
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
     * Clear notification cache for users
     */
    protected function clearCache(array $users): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                \Cache::forget("notifications.unread.{$user->id}");
                \Cache::forget("notifications.recent.{$user->id}");
            }
        }
    }
}
```

---

## 3. General Notification Class

### 3.1 Base General Notification

**File:** `app/Notifications/GeneralNotification.php`

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

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
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification
     */
    public function toDatabase($notifiable): array
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
     * Get notification data
     */
    public function getData(): array
    {
        return $this->data;
    }
}
```

---

## 4. Notification Templates

### 4.1 Template System

**File:** `app/Services/NotificationTemplate.php`

```php
<?php

namespace App\Services;

class NotificationTemplate
{
    /**
     * Get all available templates
     */
    public static function all(): array
    {
        return [
            'success' => [
                'icon' => 'check-circle',
                'color' => 'success',
                'priority' => 'normal',
            ],
            'error' => [
                'icon' => 'x-circle',
                'color' => 'danger',
                'priority' => 'high',
            ],
            'warning' => [
                'icon' => 'exclamation-triangle',
                'color' => 'warning',
                'priority' => 'normal',
            ],
            'info' => [
                'icon' => 'information-circle',
                'color' => 'info',
                'priority' => 'normal',
            ],
            'urgent' => [
                'icon' => 'exclamation-circle',
                'color' => 'danger',
                'priority' => 'urgent',
            ],
        ];
    }

    /**
     * Get template configuration
     */
    public static function get(string $template): array
    {
        return self::all()[$template] ?? self::all()['info'];
    }

    /**
     * Apply template to builder
     */
    public static function apply(NotificationBuilder $builder, string $template): NotificationBuilder
    {
        $config = self::get($template);
        
        return $builder
            ->icon($config['icon'])
            ->color($config['color'])
            ->priority($config['priority']);
    }
}
```

---

## 5. Usage in Listeners

### 5.1 Basic Usage

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;

class SendUserCreatedNotification
{
    /**
     * Handle the event
     */
    public function handle($event): void
    {
        $user = $event->user;
        
        // Simple notification
        NotificationBuilder::make()
            ->title('Akun Berhasil Dibuat')
            ->message("Selamat datang, {$user->name}!")
            ->icon('user-plus')
            ->color('success')
            ->category('system')
            ->sendTo($user);
    }
}
```

### 5.2 With Action Button

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;

class SendPeminjamanApprovedNotification
{
    public function handle($event): void
    {
        $peminjaman = $event->peminjaman;
        
        NotificationBuilder::make()
            ->title('Peminjaman Disetujui')
            ->message("Peminjaman {$peminjaman->kode_peminjaman} telah disetujui")
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('check-circle')
            ->color('success')
            ->priority('high')
            ->category('peminjaman')
            ->addMeta('peminjaman_id', $peminjaman->id)
            ->addMeta('kode', $peminjaman->kode_peminjaman)
            ->sendTo($peminjaman->user);
    }
}
```

### 5.3 With Template

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;
use App\Services\NotificationTemplate;

class SendApprovalOverdueNotification
{
    public function handle($event): void
    {
        $approval = $event->approval;
        
        $builder = NotificationBuilder::make()
            ->title('Approval Melewati SLA')
            ->message("Approval untuk {$approval->peminjaman->kode_peminjaman} sudah overdue")
            ->action('Review Sekarang', route('approvals.show', $approval))
            ->category('approval');
        
        // Apply urgent template
        NotificationTemplate::apply($builder, 'urgent');
        
        $builder->sendTo($approval->approver);
    }
}
```

### 5.4 Send to Multiple Users

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;

class SendRoleUpdatedNotification
{
    public function handle($event): void
    {
        $role = $event->role;
        $affectedUsers = $role->users; // Collection of users
        
        NotificationBuilder::make()
            ->title('Role Diupdate')
            ->message("Role {$role->name} telah diperbarui")
            ->icon('shield-check')
            ->color('warning')
            ->category('system')
            ->sendToUsers($affectedUsers); // Support Collection
    }
}
```

### 5.5 Send by Permission

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;

class SendSystemMaintenanceNotification
{
    public function handle($event): void
    {
        // Notify all users with permission
        NotificationBuilder::make()
            ->title('Maintenance Terjadwal')
            ->message('Sistem akan maintenance pada ' . $event->scheduledAt)
            ->icon('wrench')
            ->color('warning')
            ->priority('high')
            ->category('system')
            ->sendToPermission('system.view');
    }
}
```

### 5.6 Send by Role

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;

class SendNewPeminjamanNotification
{
    public function handle($event): void
    {
        $peminjaman = $event->peminjaman;
        
        // Notify all admins
        NotificationBuilder::make()
            ->title('Peminjaman Baru')
            ->message("Peminjaman baru dari {$peminjaman->user->name}")
            ->action('Lihat', route('peminjaman.show', $peminjaman))
            ->icon('document-plus')
            ->color('info')
            ->category('peminjaman')
            ->sendToRole('Admin Sarpras');
    }
}
```

---

## 6. Advanced Usage

### 6.1 Conditional Notification

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;

class SendConditionalNotification
{
    public function handle($event): void
    {
        $user = $event->user;
        
        $builder = NotificationBuilder::make()
            ->title('Update Profil')
            ->message('Profil Anda telah diperbarui')
            ->category('profile');
        
        // Conditional styling
        if ($event->isImportantChange) {
            $builder->priority('high')
                    ->color('warning')
                    ->icon('exclamation-triangle');
        } else {
            $builder->priority('normal')
                    ->color('success')
                    ->icon('check-circle');
        }
        
        $builder->sendTo($user);
    }
}
```

### 6.2 Bulk Notification with Filtering

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;
use App\Models\User;

class SendBulkNotification
{
    public function handle($event): void
    {
        // Get users with custom filter
        $users = User::where('status', 'active')
            ->where('user_type', 'mahasiswa')
            ->whereHas('student', function ($q) {
                $q->where('prodi_id', 1);
            })
            ->get();
        
        NotificationBuilder::make()
            ->title('Pengumuman Prodi')
            ->message('Ada pengumuman baru untuk mahasiswa Prodi Anda')
            ->action('Lihat', url('/announcements'))
            ->icon('megaphone')
            ->color('info')
            ->category('announcement')
            ->sendToUsers($users);
    }
}
```

### 6.3 Notification with Rich Metadata

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;

class SendRichNotification
{
    public function handle($event): void
    {
        $peminjaman = $event->peminjaman;
        
        NotificationBuilder::make()
            ->title('Peminjaman Dikembalikan')
            ->message("Peminjaman {$peminjaman->kode_peminjaman} telah dikembalikan")
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('arrow-uturn-left')
            ->color('success')
            ->category('peminjaman')
            ->metadata([
                'peminjaman_id' => $peminjaman->id,
                'kode_peminjaman' => $peminjaman->kode_peminjaman,
                'returned_at' => now()->toDateTimeString(),
                'items_count' => $peminjaman->items->count(),
                'condition' => $peminjaman->return_condition,
                'notes' => $peminjaman->return_notes,
            ])
            ->sendTo($peminjaman->user);
    }
}
```

---

## 7. Integration with Existing Events

### 7.1 Register in EventServiceProvider

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // User Management
        \App\Events\UserCreated::class => [
            \App\Listeners\StoreUserAudit::class,
            \App\Listeners\SendUserCreatedNotification::class,
        ],
        
        \App\Events\UserBlocked::class => [
            \App\Listeners\SendUserBlockedNotification::class,
        ],
        
        \App\Events\RoleUpdated::class => [
            \App\Listeners\StoreRoleAudit::class,
            \App\Listeners\ClearPermissionCache::class,
            \App\Listeners\SendRoleUpdatedNotification::class,
        ],
        
        // Peminjaman
        \App\Events\PeminjamanSubmitted::class => [
            \App\Listeners\SendPeminjamanSubmittedNotification::class,
        ],
        
        \App\Events\PeminjamanApproved::class => [
            \App\Listeners\SendPeminjamanApprovedNotification::class,
        ],
        
        \App\Events\PeminjamanRejected::class => [
            \App\Listeners\SendPeminjamanRejectedNotification::class,
        ],
        
        // Approval
        \App\Events\ApprovalCreated::class => [
            \App\Listeners\SendNewApprovalNotification::class,
        ],
        
        \App\Events\ApprovalOverdue::class => [
            \App\Listeners\SendApprovalOverdueNotification::class,
        ],
    ];
}
```

---

## 8. Helper Functions (Optional)

### 8.1 Global Helper

**File:** `app/Helpers/notification_helpers.php`

```php
<?php

use App\Services\NotificationBuilder;
use App\Models\User;

if (!function_exists('notify')) {
    /**
     * Quick notification helper
     */
    function notify(User $user): NotificationBuilder
    {
        return NotificationBuilder::make();
    }
}

if (!function_exists('notify_success')) {
    /**
     * Send success notification
     */
    function notify_success(User $user, string $title, string $message, ?string $url = null): void
    {
        $builder = NotificationBuilder::make()
            ->title($title)
            ->message($message)
            ->icon('check-circle')
            ->color('success')
            ->category('system');
        
        if ($url) {
            $builder->action('Lihat', $url);
        }
        
        $builder->sendTo($user);
    }
}

if (!function_exists('notify_error')) {
    /**
     * Send error notification
     */
    function notify_error(User $user, string $title, string $message): void
    {
        NotificationBuilder::make()
            ->title($title)
            ->message($message)
            ->icon('x-circle')
            ->color('danger')
            ->priority('high')
            ->category('system')
            ->sendTo($user);
    }
}
```

**Usage in Listener:**
```php
public function handle($event): void
{
    notify_success(
        $event->user,
        'Berhasil',
        'Akun Anda telah dibuat',
        route('profile.show')
    );
}
```

---

## 9. Testing General Notification

### 9.1 Unit Test

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Services\NotificationBuilder;
use Illuminate\Support\Facades\Notification;

class NotificationBuilderTest extends TestCase
{
    public function test_build_notification_with_all_fields()
    {
        $user = User::factory()->create();
        
        Notification::fake();
        
        NotificationBuilder::make()
            ->title('Test Title')
            ->message('Test Message')
            ->action('Click Here', '/test')
            ->icon('test-icon')
            ->color('success')
            ->priority('high')
            ->category('test')
            ->sendTo($user);
        
        Notification::assertSentTo($user, function ($notification) {
            $data = $notification->getData();
            
            return $data['title'] === 'Test Title' &&
                   $data['message'] === 'Test Message' &&
                   $data['icon'] === 'test-icon' &&
                   $data['color'] === 'success';
        });
    }
    
    public function test_send_to_multiple_users()
    {
        $users = User::factory()->count(3)->create();
        
        Notification::fake();
        
        NotificationBuilder::make()
            ->title('Broadcast')
            ->message('Message for all')
            ->sendToUsers($users);
        
        Notification::assertSentTimes(\App\Notifications\GeneralNotification::class, 3);
    }
    
    public function test_send_to_users_with_permission()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $user1->givePermissionTo('test.permission');
        
        Notification::fake();
        
        NotificationBuilder::make()
            ->title('Test')
            ->message('Test')
            ->sendToPermission('test.permission');
        
        Notification::assertSentTo($user1);
        Notification::assertNotSentTo($user2);
    }
}
```

---

## 10. Best Practices

### 10.1 Do's ✅
- **Always use builder pattern** untuk consistency
- **Set category** untuk setiap notifikasi
- **Add metadata** untuk tracking dan debugging
- **Use templates** untuk common notification types
- **Test notification** in listener tests
- **Keep messages short** dan descriptive

### 10.2 Don'ts ❌
- Jangan hardcode notification data di multiple places
- Jangan send notification directly tanpa builder
- Jangan lupa handle inactive users
- Jangan kirim notifikasi tanpa title/message
- Jangan overuse urgent priority

---

## 11. Complete Example: Listener Implementation

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;
use App\Services\NotificationTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPeminjamanNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle peminjaman approved event
     */
    public function handleApproved($event): void
    {
        $peminjaman = $event->peminjaman;
        
        NotificationBuilder::make()
            ->title('Peminjaman Disetujui')
            ->message("Peminjaman {$peminjaman->kode_peminjaman} telah disetujui oleh {$event->approver->name}")
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('check-circle')
            ->color('success')
            ->priority('high')
            ->category('peminjaman')
            ->metadata([
                'peminjaman_id' => $peminjaman->id,
                'kode' => $peminjaman->kode_peminjaman,
                'approver_id' => $event->approver->id,
            ])
            ->sendTo($peminjaman->user);
    }

    /**
     * Handle peminjaman rejected event
     */
    public function handleRejected($event): void
    {
        $peminjaman = $event->peminjaman;
        
        $builder = NotificationBuilder::make()
            ->title('Peminjaman Ditolak')
            ->message("Peminjaman {$peminjaman->kode_peminjaman} ditolak. Alasan: {$event->reason}")
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->category('peminjaman')
            ->addMeta('peminjaman_id', $peminjaman->id)
            ->addMeta('reason', $event->reason);
        
        NotificationTemplate::apply($builder, 'error');
        
        $builder->sendTo($peminjaman->user);
    }

    /**
     * Handle failed job
     */
    public function failed($event, \Throwable $exception): void
    {
        \Log::error('Failed to send peminjaman notification', [
            'event' => get_class($event),
            'error' => $exception->getMessage(),
        ]);
    }
}
```

---

## 12. Summary

### Key Benefits
✅ **Reusable** - Satu system untuk semua jenis notifikasi  
✅ **Flexible** - Easy to customize per use case  
✅ **Testable** - Simple to unit test  
✅ **Maintainable** - Centralized notification logic  
✅ **Scalable** - Support berbagai channel di masa depan  

### Quick Start from Listener
```php
use App\Services\NotificationBuilder;

NotificationBuilder::make()
    ->title('Your Title')
    ->message('Your Message')
    ->sendTo($user);
```

Sesimple itu! 🎉
