# Notification System Architecture

**General Purpose & Listener-Friendly Design**

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     EVENT SYSTEM                             │
│  (UserCreated, PeminjamanApproved, RoleUpdated, etc.)      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ triggers
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    EVENT LISTENERS                           │
│  - SendUserCreatedNotification                              │
│  - SendPeminjamanApprovedNotification                       │
│  - SendApprovalOverdueNotification                          │
│  - (Any Custom Listener)                                    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ uses
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              NOTIFICATION BUILDER (Core)                     │
│                                                              │
│  NotificationBuilder::make()                                │
│    ->title('...')                                           │
│    ->message('...')                                         │
│    ->action('...', '...')                                   │
│    ->icon('...')                                            │
│    ->color('...')                                           │
│    ->category('...')                                        │
│    ->sendTo($user)                                          │
│                                                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ creates & dispatches
                     ▼
┌─────────────────────────────────────────────────────────────┐
│            GENERAL NOTIFICATION CLASS                        │
│                                                              │
│  GeneralNotification (Laravel Notification)                 │
│    - implements ShouldQueue                                 │
│    - via(['database'])                                      │
│    - toDatabase($notifiable)                                │
│                                                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ queued via Redis
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  QUEUE WORKER                                │
│                                                              │
│  php artisan queue:work                                     │
│    - Processes notification jobs                            │
│    - Stores to database                                     │
│                                                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ stores to
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              DATABASE (notifications table)                  │
│                                                              │
│  - id, type, notifiable_type, notifiable_id                │
│  - data (JSON), read_at, timestamps                         │
│                                                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ accessed by
                     ▼
┌─────────────────────────────────────────────────────────────┐
│           NOTIFICATION REPOSITORY & SERVICE                  │
│                                                              │
│  NotificationRepository:                                    │
│    - getUserNotifications() with filters                    │
│    - getUnreadCount() with cache                            │
│    - getRecentUnread() for dropdown                         │
│                                                              │
│  NotificationService:                                       │
│    - markAsRead()                                           │
│    - markAllAsRead()                                        │
│    - deleteOldNotifications()                               │
│                                                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ used by
                     ▼
┌─────────────────────────────────────────────────────────────┐
│             NOTIFICATION CONTROLLER                          │
│                                                              │
│  Routes:                                                     │
│    GET  /notifications              → index()               │
│    GET  /notifications/recent       → recent() [AJAX]       │
│    POST /notifications/{id}/read    → markAsRead()          │
│    POST /notifications/mark-all-read→ markAllAsRead()       │
│    GET  /notifications/count        → count() [polling]     │
│                                                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ renders
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  FRONTEND UI                                 │
│                                                              │
│  Views:                                                      │
│    - notifications/index.blade.php (inbox)                  │
│    - partials/notification-dropdown.blade.php (header)      │
│                                                              │
│  JavaScript:                                                 │
│    - NotificationManager class                              │
│    - Auto-polling (30s)                                     │
│    - Badge update                                           │
│    - Mark as read                                           │
│                                                              │
│  CSS:                                                        │
│    - notification.css (vanilla CSS)                         │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 File Structure

```
project_fix/
├── app/
│   ├── Events/
│   │   ├── UserCreated.php
│   │   ├── PeminjamanApproved.php
│   │   └── ... (domain events)
│   │
│   ├── Listeners/
│   │   ├── SendUserCreatedNotification.php
│   │   ├── SendPeminjamanApprovedNotification.php
│   │   └── ... (notification listeners)
│   │
│   ├── Notifications/
│   │   └── GeneralNotification.php         # ⭐ Core notification class
│   │
│   ├── Services/
│   │   ├── NotificationBuilder.php          # ⭐ Main builder class
│   │   └── NotificationTemplate.php         # Template configs
│   │
│   ├── Repositories/
│   │   └── NotificationRepository.php       # Data access
│   │
│   ├── Http/
│   │   └── Controllers/
│   │       └── NotificationController.php   # API & views
│   │
│   └── Providers/
│       └── EventServiceProvider.php         # Event registration
│
├── database/
│   └── migrations/
│       └── xxxx_create_notifications_table.php
│
├── resources/
│   └── views/
│       └── notifications/
│           ├── index.blade.php              # Inbox page
│           ├── empty.blade.php              # Empty state
│           └── partials/
│               ├── notification-item.blade.php
│               └── notification-dropdown.blade.php
│
├── public/
│   ├── js/
│   │   └── notifications.js                 # Frontend logic
│   │
│   └── css/
│       └── notifications.css                # Styling
│
├── routes/
│   └── web.php                              # Notification routes
│
├── tests/
│   ├── Unit/
│   │   └── Services/
│   │       └── NotificationBuilderTest.php
│   │
│   └── Feature/
│       └── NotificationControllerTest.php
│
└── documents/
    ├── NOTIFICATION_QUICK_START.md          # 📖 Quick start guide
    └── feature-details/
        ├── notification-general-system.md    # 📖 Complete implementation
        ├── notification-system.md            # 📖 Requirements
        └── notification-architecture.md      # 📖 This file
```

---

## 🔄 Data Flow

### 1. Creating Notification (from Listener)

```php
// Event occurs
event(new PeminjamanApproved($peminjaman, $approver));

// Listener handles
class SendPeminjamanApprovedNotification
{
    public function handle($event)
    {
        // Build notification
        NotificationBuilder::make()
            ->title('Approved')
            ->message('Your request approved')
            ->sendTo($event->peminjaman->user);
        
        // Behind the scenes:
        // 1. Builder creates GeneralNotification instance
        // 2. Notification dispatched to queue
        // 3. Queue worker processes job
        // 4. Stored to database
        // 5. Cache invalidated
    }
}
```

### 2. Displaying Notifications (Frontend)

```javascript
// On page load or timer (30s)
fetch('/notifications/recent')
    .then(response => response.json())
    .then(data => {
        // Update badge
        badge.textContent = data.count;
        
        // Render notifications
        renderNotifications(data.notifications);
    });

// User clicks notification
markAsRead(notificationId)
    .then(() => {
        // Redirect to action URL
        window.location.href = actionUrl;
    });
```

### 3. Database Storage

```sql
-- notifications table
INSERT INTO notifications (
    id,                  -- UUID
    type,                -- App\Notifications\GeneralNotification
    notifiable_type,     -- App\Models\User
    notifiable_id,       -- User ID
    data,                -- JSON payload
    read_at,             -- NULL (unread)
    created_at,
    updated_at
) VALUES (...);
```

**JSON Data Structure:**
```json
{
    "title": "Peminjaman Disetujui",
    "message": "Peminjaman ABC-001 telah disetujui",
    "action_text": "Lihat Detail",
    "action_url": "/peminjaman/1",
    "icon": "check-circle",
    "color": "success",
    "priority": "high",
    "category": "peminjaman",
    "metadata": {
        "peminjaman_id": 1,
        "kode": "ABC-001"
    }
}
```

---

## 🎯 Key Design Decisions

### 1. Why Builder Pattern?

**Problem:** Banyak parameter untuk notification
```php
// ❌ Bad: Too many parameters
new Notification($title, $message, $icon, $color, $priority, $category, ...)
```

**Solution:** Builder pattern untuk flexibility
```php
// ✅ Good: Fluent & readable
NotificationBuilder::make()
    ->title('Title')
    ->message('Message')
    ->icon('check')
    ->sendTo($user);
```

### 2. Why General Notification Class?

**Problem:** Banyak notification class untuk maintain
```php
// ❌ Bad: Banyak class
PeminjamanApproved.php
PeminjamanRejected.php
UserBlocked.php
... (20+ files)
```

**Solution:** Single general class dengan data payload
```php
// ✅ Good: One class, dynamic data
GeneralNotification($data)
```

### 3. Why Service Layer?

**Problem:** Business logic di listener atau controller
```php
// ❌ Bad: Logic scattered
$user->notify(new Notification(...));
```

**Solution:** Centralized service dengan features
```php
// ✅ Good: Service layer
NotificationBuilder::make()
    ->sendTo($user);           // Auto filter active users
                               // Auto queue
                               // Auto cache invalidation
```

### 4. Why Queue-Based?

**Problem:** Slow response time
```php
// ❌ Bad: Synchronous (slow)
$user->notify($notification); // Blocks request
```

**Solution:** Queue untuk async processing
```php
// ✅ Good: Async (fast)
implements ShouldQueue
// Processed in background
```

---

## 🔌 Extension Points

### 1. Add New Channel (Future)

```php
// GeneralNotification.php
public function via($notifiable): array
{
    return ['database', 'mail']; // Add email
}

public function toMail($notifiable): MailMessage
{
    return (new MailMessage)
        ->subject($this->data['title'])
        ->line($this->data['message']);
}
```

### 2. Add User Preferences

```php
// NotificationBuilder.php
protected function shouldSendNotification(User $user): bool
{
    // Check user preferences
    $settings = $user->notificationSettings;
    return $settings->isEnabled($this->category);
}
```

### 3. Add Notification Templates

```php
// Use predefined templates
NotificationTemplate::apply($builder, 'success');
NotificationTemplate::apply($builder, 'urgent');
```

### 4. Add Rich Notifications

```php
// Add images, attachments, etc.
NotificationBuilder::make()
    ->title('New Document')
    ->message('You have new document')
    ->addMeta('image_url', $url)
    ->addMeta('file_size', $size)
    ->sendTo($user);
```

---

## 🧪 Testing Strategy

### Unit Tests
- ✅ NotificationBuilder functionality
- ✅ GeneralNotification data structure
- ✅ NotificationRepository queries
- ✅ NotificationService logic

### Feature Tests
- ✅ NotificationController endpoints
- ✅ Mark as read functionality
- ✅ Pagination and filtering

### Integration Tests
- ✅ Event → Listener → Notification flow
- ✅ Queue processing
- ✅ Cache invalidation

### Manual Tests
- ✅ UI dropdown functionality
- ✅ Real-time updates
- ✅ Badge counter accuracy

---

## 📊 Performance Considerations

### Caching Strategy
```php
// Unread count cached for 5 minutes
Cache::remember("notifications.unread.{$user->id}", 300, fn() => 
    $user->unreadNotifications()->count()
);

// Recent notifications cached for 2 minutes
Cache::remember("notifications.recent.{$user->id}", 120, fn() => 
    $user->unreadNotifications()->latest()->limit(5)->get()
);
```

### Database Indexes
```sql
INDEX idx_notifiable (notifiable_type, notifiable_id)
INDEX idx_read_at (read_at)
INDEX idx_created_at (created_at)
```

### Queue Configuration
```bash
# Dedicated queue for notifications
QUEUE_CONNECTION=redis
REDIS_QUEUE_NOTIFICATIONS=notifications

# Worker
php artisan queue:work --queue=notifications,default
```

### Cleanup Strategy
```php
// Delete old notifications (90 days)
$schedule->call(function () {
    Notification::where('created_at', '<', now()->subDays(90))->delete();
})->daily();
```

---

## 🔒 Security Considerations

### 1. User Privacy
```php
// Users only see their own notifications
$query = auth()->user()->notifications(); // Scoped to user
```

### 2. XSS Prevention
```blade
{{-- Auto-escaped in Blade --}}
<div>{{ $notification->data['message'] }}</div>
```

### 3. CSRF Protection
```javascript
// CSRF token in AJAX requests
headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

### 4. Rate Limiting
```php
// Prevent notification spam
RateLimiter::for('notifications', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id);
});
```

---

## 📈 Monitoring & Logging

### Queue Monitoring
```bash
# Monitor queue size
php artisan queue:monitor notifications

# Check failed jobs
php artisan queue:failed
```

### Logging
```php
// Failed notifications
Log::error('Failed to send notification', [
    'user_id' => $user->id,
    'notification_type' => get_class($notification),
    'error' => $exception->getMessage(),
]);
```

### Metrics to Track
- Notification sent count per day
- Average delivery time
- Failed notification rate
- Unread notification average per user
- Most common notification categories

---

## 🎓 Learning Resources

1. **Laravel Notifications**: https://laravel.com/docs/notifications
2. **Queue System**: https://laravel.com/docs/queues
3. **Builder Pattern**: https://refactoring.guru/design-patterns/builder
4. **Event-Driven Architecture**: https://martinfowler.com/articles/201701-event-driven.html

---

## 🚀 Next Steps

1. ✅ Review architecture
2. ✅ Implement core components (Builder, GeneralNotification)
3. ✅ Setup database & queue
4. ✅ Create listeners for existing events
5. ✅ Build frontend UI
6. ✅ Write tests
7. ✅ Deploy & monitor

---

**Architecture berhasil dirancang untuk:**
- ⚡ Performance (queue-based, caching)
- 🔧 Maintainability (single general class)
- 🎨 Flexibility (builder pattern)
- 📦 Scalability (easy to extend)
- 🧪 Testability (isolated components)

Ready to implement! 🎉
