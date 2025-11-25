<?php

namespace Tests\Unit\Services;

use App\Events\UserAuditLogged;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use DatabaseMigrations;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AuthService::class);

        // Pastikan cache permission/role bersih untuk setiap test
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_logs_in_user_with_valid_credentials_and_assigns_default_role_when_missing(): void
    {
        // Siapkan role default yang dipakai logic AuthService
        $role = Role::factory()->create([
            'name' => 'Peminjam Mahasiswa',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'username' => 'mahasiswa1',
            'email' => 'mahasiswa1@example.com',
            'password' => Hash::make('Password1!'),
            'user_type' => 'mahasiswa',
            'profile_completed' => true,
        ]);

        // Pastikan user belum punya role
        $this->assertFalse($user->roles()->exists());

        Event::fake([UserAuditLogged::class]);

        $result = $this->service->login('mahasiswa1', 'Password1!');

        // Harus ter-authenticate
        $this->assertAuthenticatedAs($result['user']);

        // Profile sudah complete, tidak butuh completion lagi
        $this->assertFalse($result['requires_profile_completion']);

        // Role default harus ter-assign
        $user = $user->fresh(['roles']);
        $this->assertTrue($user->roles->contains('name', $role->name));

        // Audit event terkirim
        Event::assertDispatched(UserAuditLogged::class, function (UserAuditLogged $event) use ($user) {
            return $event->user->is($user) && $event->action === 'auth.login';
        });
    }

    /** @test */
    public function it_throws_authentication_exception_when_password_invalid(): void
    {
        User::factory()->create([
            'username' => 'tester',
            'password' => Hash::make('CorrectPass1!'),
        ]);

        $this->expectException(AuthenticationException::class);

        try {
            $this->service->login('tester', 'WrongPass');
        } finally {
            $this->assertGuest();
        }
    }

    /** @test */
    public function it_throws_authentication_exception_when_account_locked(): void
    {
        $user = User::factory()->create([
            'username' => 'locked',
            'password' => Hash::make('Password1!'),
            'locked_until' => now()->addMinutes(10),
        ]);

        $this->expectException(AuthenticationException::class);

        try {
            $this->service->login('locked', 'Password1!');
        } finally {
            $this->assertGuest();
        }
    }

    /** @test */
    public function it_registers_user_with_hashed_password_and_default_values(): void
    {
        $role = Role::factory()->create([
            'name' => 'Peminjam Mahasiswa',
            'guard_name' => 'web',
        ]);

        Event::fake([UserAuditLogged::class]);

        $data = [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'Password1!',
            'user_type' => 'mahasiswa',
        ];

        $user = $this->service->register($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('active', $user->status);
        $this->assertFalse((bool) $user->profile_completed);
        $this->assertTrue(Hash::check('Password1!', $user->password));
        $this->assertNotNull($user->password_changed_at);

        // Role default ter-set
        $user = $user->fresh(['roles']);
        $this->assertTrue($user->roles->contains('name', $role->name));

        Event::assertDispatched(UserAuditLogged::class, function (UserAuditLogged $event) use ($user) {
            return $event->user->is($user) && $event->action === 'auth.register';
        });
    }

    /** @test */
    public function dispatch_logout_audit_emits_user_audit_logged_event(): void
    {
        $user = User::factory()->create();

        Event::fake([UserAuditLogged::class]);

        $this->service->dispatchLogoutAudit($user);

        Event::assertDispatched(UserAuditLogged::class, function (UserAuditLogged $event) use ($user) {
            return $event->user->is($user) && $event->action === 'auth.logout';
        });
    }
}
