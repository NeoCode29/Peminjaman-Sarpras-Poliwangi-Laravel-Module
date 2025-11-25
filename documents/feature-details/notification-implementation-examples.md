# Notification System - Implementation Examples

Dokumen ini berisi contoh kode lengkap untuk implementasi sistem notifikasi.

---

## 1. Complete Service Implementation

### NotificationService.php

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    /**
     * Kirim notifikasi ke user spesifik
     */
    public function sendToUser(User $user, $notification): void
    {
        if ($this->shouldSendNotification($user, $notification)) {
            $user->notify($notification);
            $this->clearUserCache($user);
        }
    }

    /**
     * Kirim notifikasi ke multiple users
     */
    public function sendToUsers(array $users, $notification): void
    {
        $recipients = collect($users)->filter(
            fn($user) => $this->shouldSendNotification($user, $notification)
        );

        Notification::send($recipients, $notification);
        
        foreach ($recipients as $user) {
            $this->clearUserCache($user);
        }
    }

    /**
     * Kirim notifikasi ke users dengan permission
     */
    public function sendToUsersWithPermission(string $permission, $notification): void
    {
        $users = User::permission($permission)->active()->get();
        $this->sendToUsers($users->all(), $notification);
    }

    /**
     * Kirim notifikasi ke users dengan role
     */
    public function sendToUsersWithRole(string $role, $notification): void
    {
        $users = User::role($role)->active()->get();
        $this->sendToUsers($users->all(), $notification);
    }

    /**
     * Kirim notifikasi broadcast (all users)
     */
    public function broadcast($notification): void
    {
        $users = User::active()->get();
        $this->sendToUsers($users->all(), $notification);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);
        
        if ($notification && !$notification->read_at) {
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
            'created_at', '<', now()->subDays($daysOld)
        )->delete();
    }

    /**
     * Cek apakah notifikasi harus dikirim
     */
    protected function shouldSendNotification(User $user, $notification): bool
    {
        // Cek user aktif
        if (!$user->isActive()) {
            return false;
        }

        // Future: cek notification preferences
        return true;
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
```

---

## 2. Complete Repository Implementation

### NotificationRepository.php

```php
<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NotificationRepository
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
        if (isset($filters['search'])) {
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
            fn() => $user->unreadNotifications()->count()
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
            fn() => $user->unreadNotifications()
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
                    now()->endOfWeek()
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
            'peminjaman' => 'Peminjaman',
            'approval' => 'Approval',
            'system' => 'Sistem',
            'reminder' => 'Pengingat',
            'conflict' => 'Konflik',
            'other' => 'Lainnya',
        ];
    }
}
```

---

## 3. Complete Controller Implementation

### NotificationController.php

```php
<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use App\Repositories\NotificationRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected NotificationRepository $notificationRepository
    ) {}

    /**
     * Display notification inbox
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['read', 'category', 'priority', 'from', 'to', 'search']);
        
        $notifications = $this->notificationRepository->getUserNotifications(
            auth()->user(),
            $filters,
            $request->get('per_page', 15)
        );
        
        $stats = $this->notificationRepository->getStatistics(auth()->user());
        $categories = $this->notificationRepository->getCategories();

        return view('notifications.index', compact(
            'notifications', 
            'stats', 
            'filters', 
            'categories'
        ));
    }

    /**
     * Get recent unread notifications for header dropdown (AJAX)
     */
    public function recent(): JsonResponse
    {
        $notifications = $this->notificationRepository->getRecentUnread(auth()->user());
        $count = $this->notificationRepository->getUnreadCount(auth()->user());

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'count' => $count,
        ]);
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(string $id): JsonResponse
    {
        $success = $this->notificationService->markAsRead(auth()->user(), $id);

        return response()->json([
            'success' => $success,
            'message' => $success 
                ? 'Notifikasi ditandai sebagai dibaca' 
                : 'Notifikasi tidak ditemukan',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(auth()->user());

        return response()->json([
            'success' => true,
            'message' => "{$count} notifikasi ditandai sebagai dibaca",
            'count' => $count,
        ]);
    }

    /**
     * Get unread count (for polling)
     */
    public function count(): JsonResponse
    {
        $count = $this->notificationRepository->getUnreadCount(auth()->user());

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(string $id): JsonResponse
    {
        $success = $this->notificationService->delete(auth()->user(), $id);

        return response()->json([
            'success' => $success,
            'message' => $success 
                ? 'Notifikasi dihapus' 
                : 'Notifikasi tidak ditemukan',
        ]);
    }
}
```

---

## 4. Notification Class Examples

### PeminjamanApproved.php

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
    ) {
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Peminjaman Disetujui',
            'message' => "Peminjaman {$this->peminjaman->kode_peminjaman} telah disetujui oleh {$this->approverName}",
            'action_text' => 'Lihat Detail',
            'action_url' => route('peminjaman.show', $this->peminjaman),
            'icon' => 'check-circle',
            'color' => 'success',
            'priority' => 'high',
            'category' => 'peminjaman',
            'metadata' => [
                'peminjaman_id' => $this->peminjaman->id,
                'kode_peminjaman' => $this->peminjaman->kode_peminjaman,
                'approver_name' => $this->approverName,
                'approved_at' => now()->toDateTimeString(),
            ],
        ];
    }
}
```

### ApprovalOverdue.php

```php
<?php

namespace App\Notifications\Approval;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class ApprovalOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $approval,
        public int $hoursOverdue
    ) {
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Approval Melewati SLA',
            'message' => "Approval untuk peminjaman {$this->approval->peminjaman->kode_peminjaman} sudah melewati SLA {$this->hoursOverdue} jam",
            'action_text' => 'Review Sekarang',
            'action_url' => route('approvals.show', $this->approval),
            'icon' => 'exclamation-triangle',
            'color' => 'danger',
            'priority' => 'urgent',
            'category' => 'approval',
            'metadata' => [
                'approval_id' => $this->approval->id,
                'peminjaman_id' => $this->approval->peminjaman_id,
                'hours_overdue' => $this->hoursOverdue,
            ],
        ];
    }
}
```

---

## 5. Event Listener Example

### SendPeminjamanNotification.php

```php
<?php

namespace App\Listeners;

use App\Events\PeminjamanApproved;
use App\Notifications\Peminjaman\PeminjamanApproved as PeminjamanApprovedNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPeminjamanNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(PeminjamanApproved $event): void
    {
        $peminjaman = $event->peminjaman;
        $approver = $event->approver;

        // Kirim notifikasi ke peminjam
        $this->notificationService->sendToUser(
            $peminjaman->user,
            new PeminjamanApprovedNotification($peminjaman, $approver->name)
        );

        // Optional: Kirim notifikasi ke admin untuk tracking
        $this->notificationService->sendToUsersWithPermission(
            'peminjaman.monitor',
            new PeminjamanApprovedNotification($peminjaman, $approver->name)
        );
    }

    /**
     * Handle failed job
     */
    public function failed(PeminjamanApproved $event, \Throwable $exception): void
    {
        \Log::error('Failed to send peminjaman notification', [
            'peminjaman_id' => $event->peminjaman->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

---

## 6. Routes Definition

### web.php

```php
<?php

use App\Http\Controllers\NotificationController;

Route::middleware(['auth', 'user.not.blocked'])->group(function () {
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])
            ->name('index');
        
        Route::get('/recent', [NotificationController::class, 'recent'])
            ->name('recent');
        
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])
            ->name('mark-as-read');
        
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])
            ->name('mark-all-read');
        
        Route::get('/count', [NotificationController::class, 'count'])
            ->name('count');
        
        Route::delete('/{id}', [NotificationController::class, 'destroy'])
            ->name('destroy');
    });
});
```

---

## 7. Service Provider Bindings

### AppServiceProvider.php

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Service bindings
        $this->app->singleton(\App\Services\NotificationService::class);
        
        // Repository bindings
        $this->app->bind(
            \App\Repositories\Interfaces\NotificationRepositoryInterface::class,
            \App\Repositories\NotificationRepository::class
        );
    }
}
```

---

## 8. Scheduled Tasks

### Kernel.php

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Cleanup old notifications every day
        $schedule->call(function () {
            app(\App\Services\NotificationService::class)->deleteOldNotifications(90);
        })->daily()->at('02:00');

        // Check for overdue approvals every hour
        $schedule->command('approvals:check-overdue')->hourly();
        
        // Send peminjaman reminders
        $schedule->command('peminjaman:send-reminders')->daily()->at('08:00');
    }
}
```

---

## 9. Console Commands Example

### CheckOverdueApprovals.php

```php
<?php

namespace App\Console\Commands;

use App\Models\PeminjamanApproval;
use App\Notifications\Approval\ApprovalOverdue;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckOverdueApprovals extends Command
{
    protected $signature = 'approvals:check-overdue';
    protected $description = 'Check for overdue approvals and send notifications';

    public function handle(NotificationService $notificationService): int
    {
        $slaHours = config('peminjaman.approval_sla_hours', 24);
        $slaThreshold = now()->subHours($slaHours);

        $overdueApprovals = PeminjamanApproval::where('status', 'pending')
            ->where('created_at', '<', $slaThreshold)
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('type', ApprovalOverdue::class)
                      ->where('created_at', '>', now()->subHours(6));
            })
            ->get();

        foreach ($overdueApprovals as $approval) {
            $hoursOverdue = $approval->created_at->diffInHours(now()) - $slaHours;
            
            $notificationService->sendToUser(
                $approval->approver,
                new ApprovalOverdue($approval, $hoursOverdue)
            );
            
            $this->info("Sent overdue notification for approval #{$approval->id}");
        }

        $this->info("Checked {$overdueApprovals->count()} overdue approvals");
        
        return Command::SUCCESS;
    }
}
```

---

## 10. Complete Menu Integration

### Menu Seeder Addition

```php
// database/seeders/MenuSeeder.php

Menu::create([
    'label' => 'Notifikasi',
    'route' => 'notifications.index',
    'icon' => 'heroicon-o-bell',
    'permission' => null, // All users can access
    'order' => 85,
    'active' => true,
]);
```
