<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use DatabaseMigrations;

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_authenticated_user_is_redirected_from_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'profile_completed' => true,
        ]);

        $authService = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $authService);

        $authService->shouldReceive('login')
            ->once()
            ->andReturn([
                'user' => $user,
                'requires_profile_completion' => false,
            ]);

        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $authService = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $authService);

        $authService->shouldReceive('login')
            ->once()
            ->andThrow(new AuthenticationException(__('auth.failed')));

        $response = $this->from(route('login'))
            ->post(route('login.store'), [
                'username' => $user->username,
                'password' => 'wrong-password',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
    }

    public function test_login_redirects_to_profile_setup_when_profile_not_completed(): void
    {
        $user = User::factory()->create([
            'profile_completed' => false,
        ]);

        $authService = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $authService);

        $authService->shouldReceive('login')
            ->once()
            ->andReturn([
                'user' => $user,
                'requires_profile_completion' => true,
            ]);

        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('profile.setup'));
        $response->assertSessionHas('warning');
    }

    public function test_logout_clears_sessions_and_logs_out_when_sso_disabled(): void
    {
        config()->set('services.oauth_server.sso_enable', false);

        $user = User::factory()->create();
        $this->be($user);

        DB::table('sessions')->insert([
            'id' => 'test-session-id',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent',
            'payload' => 'test',
            'last_activity' => time(),
        ]);

        /** @var AuthService $authService */
        $authService = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $authService);

        $authService->shouldReceive('dispatchLogoutAudit')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->is($user)));

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertGuest();
    }

    public function test_logout_redirects_to_sso_logout_url_for_sso_user_when_enabled(): void
    {
        config()->set('services.oauth_server.sso_enable', true);
        config()->set('services.oauth_server.uri_logout', 'https://sso.example.com/logout');

        // User with sso_id (logged in via SSO)
        $user = User::factory()->create([
            'status' => 1,
            'sso_id' => 'sso-user-123', // SSO user has sso_id
        ]);
        $this->be($user);

        $authService = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $authService);

        $authService->shouldReceive('dispatchLogoutAudit')
            ->once();

        $response = $this->post(route('logout'));

        $response->assertRedirect('https://sso.example.com/logout');
    }

    public function test_logout_redirects_to_login_for_local_user_when_sso_enabled(): void
    {
        config()->set('services.oauth_server.sso_enable', true);

        $user = User::factory()->create([
            'status' => 2,
        ]);
        $this->be($user);

        $authService = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $authService);

        $authService->shouldReceive('dispatchLogoutAudit')
            ->once();

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
    }
}
