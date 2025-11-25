<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use DatabaseMigrations;

    public function test_guest_can_view_register_page(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    public function test_authenticated_user_is_redirected_from_register_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_can_register_with_valid_data(): void
    {
        RateLimiter::clear('register|127.0.0.1');

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);

        $authService = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $authService);

        $user = User::factory()->make();

        $authService->shouldReceive('register')
            ->once()
            ->andReturn($user);

        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'user_type' => 'mahasiswa',
            'phone' => '081234567890',
        ]);

        $response->assertRedirect(route('profile.setup'));
        $response->assertSessionHas('success');
    }

    public function test_registration_is_rate_limited_after_too_many_attempts(): void
    {
        $ip = '127.0.0.1';
        $key = 'register|'.$ip;

        RateLimiter::clear($key);
        RateLimiter::hit($key, 3600);
        RateLimiter::hit($key, 3600);
        RateLimiter::hit($key, 3600);

        $this->withServerVariables(['REMOTE_ADDR' => $ip]);

        $response = $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Test User',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'user_type' => 'mahasiswa',
                'phone' => '081234567890',
            ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
    }

    public function test_registration_validation_errors(): void
    {
        $response = $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => '',
                'username' => 'x',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
                'user_type' => 'invalid',
                'phone' => 'abc',
            ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'name',
            'username',
            'email',
            'password',
            'user_type',
            'phone',
        ]);
    }
}
