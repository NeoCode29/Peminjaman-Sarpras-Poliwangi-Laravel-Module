<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'status' => 'active',
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
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
        $inactiveUser = User::factory()->create(['status' => 'inactive']);

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
