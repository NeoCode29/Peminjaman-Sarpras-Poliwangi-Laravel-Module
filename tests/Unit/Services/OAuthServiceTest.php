<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\Interfaces\AuthRepositoryInterface;
use App\Repositories\Interfaces\OAuthTokenRepositoryInterface;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Services\OAuthService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class OAuthServiceTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_checks_if_sso_is_enabled_from_config(): void
    {
        config()->set('services.oauth_server', ['sso_enable' => true]);

        $service = $this->makeService();

        $this->assertTrue($service->isEnabled());

        config()->set('services.oauth_server', ['sso_enable' => false]);

        $service = $this->makeService();

        $this->assertFalse($service->isEnabled());
    }

    /** @test */
    public function it_throws_exception_when_sso_user_data_is_incomplete(): void
    {
        config()->set('services.oauth_server', ['sso_enable' => true]);

        $service = $this->makeService();

        $this->expectException(InvalidArgumentException::class);

        $service->loginOrRegisterFromSso([
            'username' => '',
            'name' => '',
        ], [
            'access_token' => 'token',
            'expires_in' => 3600,
        ]);
    }

    /** @test */
    public function it_logs_out_by_deleting_oauth_tokens_for_user(): void
    {
        config()->set('services.oauth_server', ['sso_enable' => true]);

        $authRepository = Mockery::mock(AuthRepositoryInterface::class);
        $tokenRepository = Mockery::mock(OAuthTokenRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $database = Mockery::mock(DatabaseManager::class);

        $tokenRepository->shouldReceive('deleteByUserId')
            ->once()
            ->with(1);

        $service = new OAuthService(
            $authRepository,
            $tokenRepository,
            $roleRepository,
            $database,
        );

        $user = User::factory()->create(['id' => 1]);

        $service->logout($user);
    }

    private function makeService(): OAuthService
    {
        $authRepository = Mockery::mock(AuthRepositoryInterface::class);
        $tokenRepository = Mockery::mock(OAuthTokenRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $database = Mockery::mock(DatabaseManager::class);

        return new OAuthService(
            $authRepository,
            $tokenRepository,
            $roleRepository,
            $database,
        );
    }
}
