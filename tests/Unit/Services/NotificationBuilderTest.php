<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Services\NotificationBuilder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationBuilderTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_builds_and_sends_notification_to_single_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        NotificationBuilder::make()
            ->title('Title')
            ->message('Message')
            ->action('View', '/url')
            ->icon('bell')
            ->color('success')
            ->priority('high')
            ->category('test')
            ->metadata(['foo' => 'bar'])
            ->sendTo($user);

        Notification::assertSentTo(
            $user,
            GeneralNotification::class,
            function (GeneralNotification $notification, array $channels) {
                $data = $notification->getData();

                return $data['title'] === 'Title'
                    && $data['message'] === 'Message'
                    && $data['action_text'] === 'View'
                    && $data['action_url'] === '/url'
                    && $data['icon'] === 'bell'
                    && $data['color'] === 'success'
                    && $data['priority'] === 'high'
                    && $data['category'] === 'test'
                    && $data['metadata']['foo'] === 'bar';
            }
        );
    }

    /** @test */
    public function it_sends_only_to_active_users(): void
    {
        Notification::fake();

        $activeUser = User::factory()->create(['status' => 'active']);
        $inactiveUser = User::factory()->create(['status' => 'inactive']);

        NotificationBuilder::make()
            ->title('Hello')
            ->message('World')
            ->sendToUsers([$activeUser, $inactiveUser]);

        Notification::assertSentTo($activeUser, GeneralNotification::class);
        Notification::assertNotSentTo($inactiveUser, GeneralNotification::class);
    }
}
