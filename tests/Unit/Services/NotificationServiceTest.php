<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use DatabaseMigrations;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(NotificationService::class);
    }

    /** @test */
    public function it_marks_single_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\GeneralNotification::class,
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'data' => ['foo' => 'bar'],
            'read_at' => null,
        ]);

        $result = $this->service->markAsRead($user, $notification->id);

        $this->assertTrue($result);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_returns_false_when_notification_not_found_or_already_read(): void
    {
        $user = User::factory()->create();

        $notification = DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\GeneralNotification::class,
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'data' => ['foo' => 'bar'],
            'read_at' => now(),
        ]);

        $this->assertFalse($this->service->markAsRead($user, 'non-existent'));
        $this->assertFalse($this->service->markAsRead($user, $notification->id));
    }

    /** @test */
    public function it_marks_all_notifications_as_read_and_returns_count(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            DatabaseNotification::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => \App\Notifications\GeneralNotification::class,
                'notifiable_id' => $user->id,
                'notifiable_type' => User::class,
                'data' => ['foo' => 'bar'],
                'read_at' => null,
            ]);
        }

        $count = $this->service->markAllAsRead($user);

        $this->assertSame(3, $count);
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    /** @test */
    public function it_deletes_single_notification(): void
    {
        $user = User::factory()->create();

        $notification = DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\GeneralNotification::class,
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'data' => ['foo' => 'bar'],
        ]);

        $result = $this->service->delete($user, $notification->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_notification(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->service->delete($user, 'non-existent'));
    }

    /** @test */
    public function it_deletes_old_notifications(): void
    {
        DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\GeneralNotification::class,
            'notifiable_id' => User::factory()->create()->id,
            'notifiable_type' => User::class,
            'data' => ['foo' => 'bar'],
            'created_at' => now()->subDays(91),
        ]);

        DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\GeneralNotification::class,
            'notifiable_id' => User::factory()->create()->id,
            'notifiable_type' => User::class,
            'data' => ['foo' => 'bar'],
            'created_at' => now(),
        ]);

        $deleted = $this->service->deleteOldNotifications(90);

        $this->assertSame(1, $deleted);
        $this->assertEquals(1, DatabaseNotification::count());
    }
}
