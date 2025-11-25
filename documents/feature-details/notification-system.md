# Fitur Notifikasi - Detail Requirement & Implementation Guide

**Tanggal:** 20 November 2024  
**Versi:** 1.0

> **⚠️ IMPORTANT:** Untuk implementasi yang lebih general dan listener-friendly, lihat dokumen:
> **[notification-general-system.md](./notification-general-system.md)**
>
> Dokumen ini berisi requirement umum. Untuk implementasi praktis gunakan General Notification System.

---

## 1. Overview

### 1.1 Tujuan
Sistem notifikasi real-time untuk menyampaikan informasi tentang peminjaman, approval, konflik slot, dan event sistem lainnya.

### 1.2 Prinsip Desain
- **In-App First**: Notifikasi via database & UI internal
- **Queue-Based**: Menggunakan Redis untuk performa
- **Permission-Aware**: Hanya ke user yang berwenang
- **Audit Trail**: Semua notifikasi tercatat
- **General Purpose**: System yang reusable dan listener-friendly (lihat general-system.md)
- **Builder Pattern**: Flexible notification creation

---

## 2. Database Structure

### 2.1 Table: `notifications` (Laravel Default)
Menggunakan Laravel notification table:

```sql
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data JSON NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Data JSON Structure:**
```json
{
    "title": "Peminjaman Disetujui",
    "message": "Peminjaman #PEM-001 telah disetujui",
    "action_text": "Lihat Detail",
    "action_url": "/peminjaman/PEM-001",
    "icon": "check-circle",
    "color": "success",
    "priority": "normal",
    "category": "peminjaman",
    "metadata": {}
}
```

### 2.2 Migration Command
```bash
php artisan notifications:table
php artisan migrate
```

---

## 3. Backend Components

### 3.1 Notification Classes Structure

```
app/Notifications/
├── Peminjaman/
│   ├── PeminjamanSubmitted.php
│   ├── PeminjamanApproved.php
│   ├── PeminjamanRejected.php
│   ├── PeminjamanOverridden.php
│   └── PeminjamanReminder.php
├── Approval/
│   ├── NewApprovalRequired.php
│   └── ApprovalOverdue.php
└── System/
    ├── UserAccountBlocked.php
    └── RoleChanged.php
```

### 3.2 Service Layer

**File:** `app/Services/NotificationService.php`

**Key Methods:**
- `sendToUser(User $user, $notification)` - Kirim ke single user
- `sendToUsers(array $users, $notification)` - Kirim ke multiple users
- `sendToUsersWithPermission(string $permission, $notification)` - Kirim berdasarkan permission
- `markAsRead(User $user, string $notificationId)` - Mark as read
- `markAllAsRead(User $user)` - Mark all as read

### 3.3 Repository Layer

**File:** `app/Repositories/NotificationRepository.php`

**Key Methods:**
- `getUserNotifications(User $user, array $filters, int $perPage)` - Get dengan filter & pagination
- `getUnreadCount(User $user)` - Count unread
- `getRecentUnread(User $user, int $limit)` - Get recent untuk dropdown
- `getStatistics(User $user)` - Get statistik

### 3.4 Controller Layer

**File:** `app/Http/Controllers/NotificationController.php`

**Routes:**
```php
GET  /notifications              → index()          # Inbox page
GET  /notifications/recent       → recent()         # AJAX recent unread
POST /notifications/{id}/read    → markAsRead()     # Mark single
POST /notifications/mark-all-read→ markAllAsRead()  # Mark all
GET  /notifications/count        → count()          # Polling count
```

---

## 4. Notification Examples

### 4.1 PeminjamanApproved Notification

**File:** `app/Notifications/Peminjaman/PeminjamanApproved.php`

```php
<?php

namespace App\Notifications\Peminjaman;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class PeminjamanApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $peminjaman,
        public string $approverName
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Peminjaman Disetujui',
            'message' => "Peminjaman {$this->peminjaman->kode_peminjaman} telah disetujui",
            'action_text' => 'Lihat Detail',
            'action_url' => route('peminjaman.show', $this->peminjaman),
            'icon' => 'check-circle',
            'color' => 'success',
            'priority' => 'high',
            'category' => 'peminjaman',
        ];
    }
}
```

### 4.2 Usage in Event Listener

```php
// app/Listeners/SendPeminjamanNotification.php
public function handle(PeminjamanApproved $event): void
{
    $this->notificationService->sendToUser(
        $event->peminjaman->user,
        new PeminjamanApprovedNotification($event->peminjaman, $event->approver->name)
    );
}
```

---

## 5. Frontend Components

### 5.1 JavaScript (Vanilla JS)

**File:** `public/js/notifications.js`

**Class:** `NotificationManager`

**Key Features:**
- Auto-refresh setiap 30 detik
- Dropdown toggle
- Mark as read on click
- Mark all as read
- Time ago formatter

### 5.2 CSS Styling

**File:** `public/css/notifications.css`

**Components:**
- `.notification-dropdown` - Container
- `.notification-bell` - Bell icon dengan badge
- `.notification-dropdown-menu` - Dropdown panel
- `.notification-item` - Single notification
- `.notification-icon` - Icon dengan color variants

### 5.3 View Structure

```
resources/views/notifications/
├── index.blade.php              # Inbox page
├── partials/
│   ├── notification-item.blade.php
│   └── notification-dropdown.blade.php
└── empty.blade.php
```

---

## 6. Integration Points

### 6.1 Event-Based Notifications

**Register in EventServiceProvider:**
```php
protected $listen = [
    PeminjamanApproved::class => [SendPeminjamanNotification::class],
    PeminjamanRejected::class => [SendPeminjamanNotification::class],
    // ... etc
];
```

### 6.2 Direct Service Usage

```php
// Di service layer
$this->notificationService->sendToUser(
    $user,
    new UserAccountBlocked($user, $reason)
);
```

---

## 7. Authorization

**No special permissions required** - Semua user dapat:
- Melihat notifikasi mereka sendiri
- Mark as read notifikasi sendiri
- Melihat history notifikasi sendiri

---

## 8. Testing Requirements

### 8.1 Unit Tests
- Service: send notification, filter by status, mark as read
- Repository: pagination, filtering, statistics

### 8.2 Feature Tests
- Controller: index page, mark as read, mark all
- API: recent notifications, count endpoint

---

## 9. Implementation Checklist

### Phase 1: Backend Core
- [ ] Run `php artisan notifications:table` dan migrate
- [ ] Buat `NotificationService.php`
- [ ] Buat `NotificationRepository.php`
- [ ] Buat `NotificationController.php`
- [ ] Setup routes

### Phase 2: Notification Classes
- [ ] Buat base notification classes (minimal 5 types)
- [ ] Setup event listeners
- [ ] Test notification dispatch

### Phase 3: Frontend
- [ ] Buat view `notifications/index.blade.php`
- [ ] Buat dropdown component untuk header
- [ ] Implementasi `notifications.js`
- [ ] Styling `notifications.css`

### Phase 4: Integration
- [ ] Integrate dengan peminjaman flow
- [ ] Integrate dengan user management
- [ ] Setup queue worker untuk production

### Phase 5: Testing
- [ ] Unit tests (service & repository)
- [ ] Feature tests (controller)
- [ ] Manual testing end-to-end

---

## 10. Queue Configuration

### 10.1 .env Settings
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 10.2 Run Queue Worker
```bash
# Development
php artisan queue:work

# Production (dengan supervisor)
php artisan queue:work --tries=3 --timeout=60
```

---

## 11. Performance Considerations

### 11.1 Caching Strategy
- Cache unread count (5 menit)
- Invalidate on mark as read

### 11.2 Cleanup Old Notifications
```php
// Schedule in Kernel.php
$schedule->call(function () {
    app(NotificationService::class)->deleteOldNotifications(90);
})->daily();
```

---

## 12. Future Enhancements

- [ ] Email notifications (via mail channel)
- [ ] Push notifications (web push)
- [ ] SMS notifications (untuk SLA critical)
- [ ] User notification preferences
- [ ] Notification templates
- [ ] Notification analytics

---

## 13. Resources & References

- Laravel Notifications: https://laravel.com/docs/notifications
- Queue Configuration: https://laravel.com/docs/queues
- Database Notifications: https://laravel.com/docs/notifications#database-notifications
