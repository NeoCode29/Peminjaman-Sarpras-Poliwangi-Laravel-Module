<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\OAuthService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use DatabaseMigrations;

    public function test_redirect_fails_when_sso_not_enabled(): void
    {
        $oauthService = Mockery::mock(OAuthService::class);
        $oauthService->shouldReceive('isEnabled')->once()->andReturn(false);
        $this->app->instance(OAuthService::class, $oauthService);

        $response = $this->get(route('oauth.login'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
    }

    public function test_redirect_builds_authorization_url_when_enabled(): void
    {
        config()->set('services.oauth_server', [
            'client_id' => 'test-client',
            'redirect' => 'https://app.test/oauth/callback',
            'uri' => 'https://sso.test',
            'sso_enable' => true,
        ]);

        $oauthService = Mockery::mock(OAuthService::class);
        $oauthService->shouldReceive('isEnabled')->once()->andReturn(true);
        $this->app->instance(OAuthService::class, $oauthService);

        $response = $this->get(route('oauth.login'));

        $response->assertRedirect();
        $this->assertStringContainsString('/oauth/authorize', $response->headers->get('Location'));
        $this->assertStringContainsString('client_id=test-client', $response->headers->get('Location'));
        $this->assertStringContainsString('redirect_uri='.urlencode('https://app.test/oauth/callback'), $response->headers->get('Location'));
    }

    public function test_callback_fails_when_sso_not_enabled(): void
    {
        $oauthService = Mockery::mock(OAuthService::class);
        $oauthService->shouldReceive('isEnabled')->once()->andReturn(false);
        $this->app->instance(OAuthService::class, $oauthService);

        $response = $this->get(route('oauth.callback', ['code' => 'abc']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
    }

    public function test_callback_fails_when_code_missing(): void
    {
        $oauthService = Mockery::mock(OAuthService::class);
        $oauthService->shouldReceive('isEnabled')->once()->andReturn(true);
        $this->app->instance(OAuthService::class, $oauthService);

        $response = $this->get(route('oauth.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
    }

    public function test_callback_fails_when_access_token_not_returned(): void
    {
        config()->set('services.oauth_server', [
            'client_id' => 'test-client',
            'client_secret' => 'secret',
            'redirect' => 'https://app.test/oauth/callback',
            'uri' => 'https://sso.test',
            'sso_enable' => true,
        ]);

        $oauthService = Mockery::mock(OAuthService::class);
        $oauthService->shouldReceive('isEnabled')->once()->andReturn(true);
        $this->app->instance(OAuthService::class, $oauthService);

        Http::fake([
            'https://sso.test/oauth/token' => Http::response(['error' => 'invalid_request'], 400),
        ]);

        $response = $this->get(route('oauth.callback', ['code' => 'abc']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
    }

    public function test_callback_success_redirects_to_dashboard_when_profile_completed(): void
    {
        config()->set('services.oauth_server', [
            'client_id' => 'test-client',
            'client_secret' => 'secret',
            'redirect' => 'https://app.test/oauth/callback',
            'uri' => 'https://sso.test',
            'sso_enable' => true,
        ]);

        $oauthService = Mockery::mock(OAuthService::class);
        $oauthService->shouldReceive('isEnabled')->once()->andReturn(true);

        $user = User::factory()->create([
            'profile_completed' => true,
        ]);

        $oauthService->shouldReceive('loginOrRegisterFromSso')
            ->once()
            ->andReturn($user);

        $this->app->instance(OAuthService::class, $oauthService);

        Http::fake([
            'https://sso.test/oauth/token' => Http::response([
                'access_token' => 'token',
                'refresh_token' => 'refresh',
                'expires_in' => 3600,
            ], 200),
            'https://sso.test/api/user' => Http::response([
                'id' => '123',
                'username' => 'jdoe',
                'name' => 'John Doe',
                'email' => 'jdoe@example.com',
            ], 200),
        ]);

        $response = $this->get(route('oauth.callback', ['code' => 'abc']));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_callback_redirects_to_profile_setup_when_profile_not_completed(): void
    {
        config()->set('services.oauth_server', [
            'client_id' => 'test-client',
            'client_secret' => 'secret',
            'redirect' => 'https://app.test/oauth/callback',
            'uri' => 'https://sso.test',
            'sso_enable' => true,
        ]);

        $oauthService = Mockery::mock(OAuthService::class);
        $oauthService->shouldReceive('isEnabled')->once()->andReturn(true);

        $user = User::factory()->create([
            'profile_completed' => false,
        ]);

        $oauthService->shouldReceive('loginOrRegisterFromSso')
            ->once()
            ->andReturn($user);

        $this->app->instance(OAuthService::class, $oauthService);

        Http::fake([
            'https://sso.test/oauth/token' => Http::response([
                'access_token' => 'token',
                'refresh_token' => 'refresh',
                'expires_in' => 3600,
            ], 200),
            'https://sso.test/api/user' => Http::response([
                'id' => '123',
                'username' => 'jdoe',
                'name' => 'John Doe',
                'email' => 'jdoe@example.com',
            ], 200),
        ]);

        $response = $this->get(route('oauth.callback', ['code' => 'abc']));

        $response->assertRedirect(route('profile.setup'));
        $response->assertSessionHas('info');
    }

    public function test_callback_handles_exception_and_logs_out(): void
    {
        config()->set('services.oauth_server', [
            'client_id' => 'test-client',
            'client_secret' => 'secret',
            'redirect' => 'https://app.test/oauth/callback',
            'uri' => 'https://sso.test',
            'sso_enable' => true,
        ]);

        $oauthService = Mockery::mock(OAuthService::class);
        $oauthService->shouldReceive('isEnabled')->once()->andReturn(true);
        $oauthService->shouldReceive('loginOrRegisterFromSso')
            ->once()
            ->andThrow(new \RuntimeException('SSO error'));

        $this->app->instance(OAuthService::class, $oauthService);

        Http::fake([
            'https://sso.test/oauth/token' => Http::response([
                'access_token' => 'token',
                'refresh_token' => 'refresh',
                'expires_in' => 3600,
            ], 200),
            'https://sso.test/api/user' => Http::response([
                'id' => '123',
                'username' => 'jdoe',
                'name' => 'John Doe',
                'email' => 'jdoe@example.com',
            ], 200),
        ]);

        $response = $this->get(route('oauth.callback', ['code' => 'abc']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
    }

    public function test_oauth_logout_deletes_sessions_and_revokes_tokens_and_redirects_to_logout_uri(): void
    {
        config()->set('services.oauth_server.uri_logout', 'https://sso.test/logout');

        // User with sso_id (logged in via SSO)
        $user = User::factory()->create([
            'sso_id' => 'sso-user-123', // SSO user
        ]);
        $this->be($user);

        $oauthService = Mockery::mock(OAuthService::class);
        $oauthService->shouldReceive('logout')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->is($user)));

        $this->app->instance(OAuthService::class, $oauthService);

        $response = $this->post(route('oauth.logout'));

        $response->assertRedirect('https://sso.test/logout');
    }

    public function test_oauth_logout_redirects_to_login_when_user_is_not_sso_user(): void
    {
        config()->set('services.oauth_server.uri_logout', 'https://sso.test/logout');

        // Manual registration user (no sso_id)
        $user = User::factory()->create([
            'sso_id' => null, // Manual user, not from SSO
        ]);
        $this->be($user);

        $oauthService = Mockery::mock(OAuthService::class);
        // OAuthService->logout should NOT be called for non-SSO users
        $oauthService->shouldNotReceive('logout');

        $this->app->instance(OAuthService::class, $oauthService);

        $response = $this->post(route('oauth.logout'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
    }
}
