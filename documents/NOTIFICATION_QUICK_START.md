# 🔔 Notification System - Quick Start Guide

**General Purpose Notification System untuk Listener**

---

## 🎯 Konsep Utama

Sistem notifikasi di project ini dirancang **general-purpose** sehingga:
- ✅ Bisa dipanggil dari **listener manapun**
- ✅ Tidak terikat pada event atau context spesifik
- ✅ Menggunakan **Builder Pattern** untuk flexibility
- ✅ Support **template** untuk consistency
- ✅ **Queue-based** untuk performa

---

## 🚀 Quick Usage

### 1. Basic Notification dari Listener

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;

class YourListener
{
    public function handle($event): void
    {
        $user = $event->user;
        
        NotificationBuilder::make()
            ->title('Judul Notifikasi')
            ->message('Pesan notifikasi Anda')
            ->sendTo($user);
    }
}
```

### 2. Notification dengan Action Button

```php
NotificationBuilder::make()
    ->title('Peminjaman Disetujui')
    ->message('Peminjaman Anda telah disetujui')
    ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
    ->icon('check-circle')
    ->color('success')
    ->sendTo($user);
```

### 3. Send ke Multiple Users

```php
// Dari Collection
NotificationBuilder::make()
    ->title('Pengumuman')
    ->message('Ada pengumuman penting')
    ->sendToUsers($users); // Collection atau Array

// Berdasarkan Permission
NotificationBuilder::make()
    ->title('System Update')
    ->message('System akan di-update')
    ->sendToPermission('system.view');

// Berdasarkan Role
NotificationBuilder::make()
    ->title('Info Admin')
    ->message('Informasi untuk admin')
    ->sendToRole('Admin Sarpras');
```

---

## 🎨 Available Options

### Icons
- `check-circle` - Success
- `x-circle` - Error
- `exclamation-triangle` - Warning
- `information-circle` - Info
- `bell` - General
- `user-plus` - User action
- `document-plus` - Document
- `shield-check` - Security

### Colors
- `success` - Hijau (sukses)
- `danger` - Merah (error/urgent)
- `warning` - Kuning (peringatan)
- `info` - Biru (informasi)

### Priority
- `low` - Prioritas rendah
- `normal` - Normal (default)
- `high` - Tinggi
- `urgent` - Sangat urgent

### Category
- `peminjaman` - Terkait peminjaman
- `approval` - Terkait approval
- `system` - System notification
- `reminder` - Pengingat
- `conflict` - Konflik slot
- `general` - Umum

---

## 📚 Complete Example

```php
<?php

namespace App\Listeners;

use App\Services\NotificationBuilder;

class SendPeminjamanApprovedNotification
{
    public function handle($event): void
    {
        $peminjaman = $event->peminjaman;
        $approver = $event->approver;
        
        NotificationBuilder::make()
            ->title('Peminjaman Disetujui')
            ->message("Peminjaman {$peminjaman->kode_peminjaman} telah disetujui oleh {$approver->name}")
            ->action('Lihat Detail', route('peminjaman.show', $peminjaman))
            ->icon('check-circle')
            ->color('success')
            ->priority('high')
            ->category('peminjaman')
            ->metadata([
                'peminjaman_id' => $peminjaman->id,
                'kode' => $peminjaman->kode_peminjaman,
                'approver_id' => $approver->id,
            ])
            ->sendTo($peminjaman->user);
    }
}
```

---

## 📋 Implementation Checklist

### Phase 1: Setup (30 menit)
```bash
# 1. Create notification table
php artisan notifications:table
php artisan migrate

# 2. Create core files
# - app/Services/NotificationBuilder.php
# - app/Notifications/GeneralNotification.php
# - app/Services/NotificationTemplate.php (optional)
```

### Phase 2: Integration (Per Listener)
```php
// 3. Add to your listener
use App\Services\NotificationBuilder;

public function handle($event): void
{
    // Your notification code
}
```

### Phase 3: Register Events
```php
// 4. Register di EventServiceProvider
protected $listen = [
    YourEvent::class => [
        YourListener::class,
    ],
];
```

---

## 🔧 Core Components

### 1. NotificationBuilder
**File:** `app/Services/NotificationBuilder.php`

Main class untuk membuat dan mengirim notifikasi.

**Methods:**
- `make()` - Create builder instance
- `title(string)` - Set title
- `message(string)` - Set message
- `action(string, string)` - Set action button
- `icon(string)` - Set icon
- `color(string)` - Set color
- `priority(string)` - Set priority
- `category(string)` - Set category
- `metadata(array)` - Set metadata
- `sendTo(User)` - Send to single user
- `sendToUsers(array|Collection)` - Send to multiple
- `sendToPermission(string)` - Send by permission
- `sendToRole(string)` - Send by role

### 2. GeneralNotification
**File:** `app/Notifications/GeneralNotification.php`

Laravel notification class yang menerima data dari builder.

### 3. NotificationTemplate (Optional)
**File:** `app/Services/NotificationTemplate.php`

Template untuk common notification types:
- `success`
- `error`
- `warning`
- `info`
- `urgent`

---

## 💡 Best Practices

### ✅ Do's
```php
// Good: Clear and descriptive
NotificationBuilder::make()
    ->title('Peminjaman Disetujui')
    ->message('Peminjaman ABC-001 telah disetujui')
    ->action('Lihat', $url)
    ->category('peminjaman')
    ->sendTo($user);

// Good: Add metadata for tracking
NotificationBuilder::make()
    ->title('Alert')
    ->message('Something happened')
    ->metadata(['event_id' => 123, 'type' => 'critical'])
    ->sendTo($user);
```

### ❌ Don'ts
```php
// Bad: Missing category
NotificationBuilder::make()
    ->title('Test')
    ->sendTo($user);

// Bad: Too vague message
NotificationBuilder::make()
    ->title('Update')
    ->message('Something updated')
    ->sendTo($user);

// Bad: Wrong priority
NotificationBuilder::make()
    ->title('Info')
    ->priority('urgent') // Don't overuse urgent!
    ->sendTo($user);
```

---

## 🧪 Testing

### Unit Test Example
```php
<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\Models\User;
use App\Listeners\YourListener;
use Illuminate\Support\Facades\Notification;

class YourListenerTest extends TestCase
{
    public function test_sends_notification()
    {
        $user = User::factory()->create();
        $event = new YourEvent($user);
        
        Notification::fake();
        
        $listener = new YourListener();
        $listener->handle($event);
        
        Notification::assertSentTo($user, \App\Notifications\GeneralNotification::class);
    }
}
```

---

## 📖 Dokumentasi Lengkap

Untuk detail implementation dan advanced usage:

1. **[notification-general-system.md](./feature-details/notification-general-system.md)**  
   Complete guide untuk general notification system

2. **[notification-system.md](./feature-details/notification-system.md)**  
   Overview dan requirement umum

3. **[notification-implementation-examples.md](./feature-details/notification-implementation-examples.md)**  
   Complete code examples untuk semua komponen

---

## 🆘 Common Issues

### Issue: Notification tidak terkirim
**Solution:**
```php
// Pastikan user aktif
$user->status === 'active'

// Pastikan queue worker running
php artisan queue:work
```

### Issue: Badge count tidak update
**Solution:**
```php
// Cache akan auto-clear, atau manual:
Cache::forget("notifications.unread.{$user->id}");
```

### Issue: Notification lambat
**Solution:**
```bash
# Pastikan queue connection = redis
QUEUE_CONNECTION=redis

# Monitor queue
php artisan queue:monitor notifications
```

---

## 🎉 Ready to Use!

Sekarang Anda bisa menambahkan notifikasi ke **listener manapun** dengan mudah:

```php
NotificationBuilder::make()
    ->title('Your Title')
    ->message('Your Message')
    ->sendTo($user);
```

Simple, reusable, dan maintainable! 🚀

---

**Questions?** Check the complete documentation atau lihat contoh di:
- `app/Listeners/` - Existing listener examples
- `tests/Unit/Services/NotificationBuilderTest.php` - Test examples
