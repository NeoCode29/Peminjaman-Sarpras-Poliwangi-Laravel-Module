<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationBuilder;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Menggunakan factory make agar tidak menyentuh database testing sqlite
        $this->user = User::factory()->make([
            'status' => 'active',
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);

        // Set id manual agar relasi notifikasi menggunakan notifiable_id bekerja
        $this->user->id = 1;

        // Binding fake NotificationRepository agar endpoint notifikasi tidak menyentuh DB
        $this->app->bind(NotificationRepositoryInterface::class, function () {
            return new class implements NotificationRepositoryInterface {
                public function getUserNotifications(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
                {
                    return new LengthAwarePaginator([], 0, $perPage, 1);
                }

                public function getUnreadCount(User $user): int
                {
                    return 0;
                }

                public function getRecentUnread(User $user, int $limit = 5): Collection
                {
                    return collect();
                }

                public function getStatistics(User $user): array
                {
                    return [
                        'total' => 0,
                        'unread' => 0,
                        'today' => 0,
                        'this_week' => 0,
                        'by_category' => [],
                    ];
                }

                public function getCountByCategory(User $user): array
                {
                    return [];
                }

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
            };
        });
    }

    /** @test */
    public function it_can_send_notification_to_user()
    {
        Notification::fake();

        NotificationBuilder::make()
            ->title('Test Notification')
            ->message('This is a test message')
            ->sendTo($this->user);

        Notification::assertSentTo(
            $this->user,
            \App\Notifications\GeneralNotification::class
        );
    }

    /** @test */
    public function it_can_send_notification_with_action()
    {
        Notification::fake();

        NotificationBuilder::make()
            ->title('Test')
            ->message('Test')
            ->action('Click Here', '/test-url')
            ->sendTo($this->user);

        Notification::assertSentTo($this->user, function (\App\Notifications\GeneralNotification $notification) {
            $data = $notification->getData();

            return $data['action_text'] === 'Click Here' &&
                $data['action_url'] === '/test-url';
        });
    }

    /** @test */
    public function it_can_build_notification_with_all_properties()
    {
        Notification::fake();

        NotificationBuilder::make()
            ->title('Complete Test')
            ->message('Complete Message')
            ->action('View', '/url')
            ->icon('check-circle')
            ->color('success')
            ->priority('high')
            ->category('test')
            ->metadata(['key' => 'value'])
            ->sendTo($this->user);

        Notification::assertSentTo($this->user, function (\App\Notifications\GeneralNotification $notification) {
            $data = $notification->getData();

            return $data['title'] === 'Complete Test' &&
                $data['message'] === 'Complete Message' &&
                $data['icon'] === 'check-circle' &&
                $data['color'] === 'success' &&
                $data['priority'] === 'high' &&
                $data['category'] === 'test' &&
                isset($data['metadata']['key']);
        });
    }

    /** @test */
    public function it_does_not_send_to_inactive_users()
    {
        // Tidak menyentuh database, cukup make dan beri id berbeda
        $inactiveUser = User::factory()->make(['status' => 'inactive']);
        $inactiveUser->id = 2;

        Notification::fake();

        NotificationBuilder::make()
            ->title('Test')
            ->message('Test')
            ->sendTo($inactiveUser);

        Notification::assertNothingSentTo($inactiveUser);
    }

    /** @test */
    public function it_can_access_notifications_index_page()
    {
        // Pastikan tabel roles dan model_has_roles ada di sqlite testing untuk kebutuhan sidebar
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('model_id');
                $table->string('model_type');
            });
        }

        // Pastikan tabel menus ada karena sidebar memuat menu dari DB
        if (! Schema::hasTable('menus')) {
            Schema::create('menus', function (Blueprint $table) {
                $table->id();
                $table->string('label');
                $table->string('route')->nullable();
                $table->string('icon')->nullable();
                $table->string('permission')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('notifications.index');
        $response->assertViewHas(['notifications', 'stats', 'categories']);
    }

    /** @test */
    public function it_can_get_recent_notifications_via_ajax()
    {
        $response = $this->actingAs($this->user)->get(route('notifications.recent'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'notifications',
            'count',
        ]);
    }

    /** @test */
    public function it_can_get_notification_count()
    {
        $response = $this->actingAs($this->user)->get(route('notifications.count'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['count']);
    }
}
