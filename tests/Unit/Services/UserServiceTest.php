<?php

namespace Tests\Unit\Services;

use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use DatabaseMigrations;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(UserService::class);
    }

    /** @test */
    public function it_creates_user_with_valid_role_and_assigns_role_properly(): void
    {
        $role = Role::factory()->create([
            'name' => 'Peminjam Mahasiswa',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $data = [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'phone' => '081234567890',
            'user_type' => 'mahasiswa',
            'status' => 'active',
            'role_id' => $role->id,
        ];

        $user = $this->service->createUser($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($role->id, $user->role_id);
        $this->assertTrue($user->roles->contains('name', $role->name));
    }

    /** @test */
    public function it_throws_exception_when_creating_user_with_invalid_role(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role tidak valid atau tidak aktif.');

        $data = [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'user_type' => 'mahasiswa',
            'status' => 'active',
            'role_id' => 9999,
        ];

        $this->service->createUser($data);
    }

    /** @test */
    public function it_updates_user_and_optionally_changes_role(): void
    {
        $oldRole = Role::factory()->create(['is_active' => true]);
        $newRole = Role::factory()->create(['is_active' => true]);

        $user = User::factory()->create([
            'name' => 'Old Name',
            'user_type' => 'staff',
            'status' => 'active',
            'role_id' => $oldRole->id,
        ]);
        $user->assignRole($oldRole);

        $data = [
            'name' => 'New Name',
            'role_id' => $newRole->id,
        ];

        $updated = $this->service->updateUser($user, $data);

        $this->assertSame('New Name', $updated->name);
        $this->assertEquals($newRole->id, $updated->role_id);
        $this->assertTrue($updated->roles->contains('name', $newRole->name));
    }

    /** @test */
    public function it_prevents_deleting_self_user(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anda tidak dapat menghapus akun sendiri.');

        $this->service->deleteUser($user);
    }

    /** @test */
    public function it_deletes_user_and_detaches_roles(): void
    {
        $currentUser = User::factory()->create();
        Auth::login($currentUser);

        $role = Role::factory()->create(['is_active' => true]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $user->assignRole($role);

        $this->service->deleteUser($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function it_throws_exception_when_blocking_self(): void
    {
        $currentUser = User::factory()->create();
        Auth::login($currentUser);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anda tidak dapat memblokir akun sendiri.');

        $this->service->blockUser($currentUser, now()->addDay()->toDateTimeString(), 'Testing');
    }

    /** @test */
    public function it_blocks_other_user_successfully(): void
    {
        $currentUser = User::factory()->create();
        Auth::login($currentUser);

        $other = User::factory()->create();

        $blockedUntil = now()->addDay()->toDateTimeString();
        $reason = 'Violation';

        $blocked = $this->service->blockUser($other, $blockedUntil, $reason);

        $this->assertSame('blocked', $blocked->status);
        $this->assertSame($reason, $blocked->blocked_reason);
        $this->assertNotNull($blocked->blocked_until);
    }

    /** @test */
    public function it_unblocks_blocked_user_and_throws_if_not_blocked(): void
    {
        $user = User::factory()->create([
            'status' => 'blocked',
            'blocked_until' => now()->addDay(),
            'blocked_reason' => 'Test',
        ]);

        // happy path
        $unblocked = $this->service->unblockUser($user);

        $this->assertSame('active', $unblocked->status);
        $this->assertNull($unblocked->blocked_until);
        $this->assertNull($unblocked->blocked_reason);

        // not blocked => should throw
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User tidak dalam status diblokir.');

        $this->service->unblockUser($unblocked);
    }

    /** @test */
    public function it_toggles_status_between_active_and_inactive_but_not_from_blocked(): void
    {
        $activeUser = User::factory()->create(['status' => 'active']);
        $inactiveUser = User::factory()->create(['status' => 'inactive']);
        $blockedUser = User::factory()->create(['status' => 'blocked']);

        $toggledToInactive = $this->service->toggleStatus($activeUser);
        $this->assertSame('inactive', $toggledToInactive->status);

        $toggledToActive = $this->service->toggleStatus($inactiveUser);
        $this->assertSame('active', $toggledToActive->status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Status pengguna saat ini diblokir dan tidak dapat diubah secara otomatis.');

        $this->service->toggleStatus($blockedUser);
    }

    /** @test */
    public function it_changes_password_and_updates_password_changed_at(): void
    {
        $user = User::factory()->create([
            'password' => 'old',
            'password_changed_at' => null,
        ]);

        $this->service->changePassword($user, ['password' => 'NewPassword1!']);

        $user->refresh();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword1!', $user->password));
        $this->assertNotNull($user->password_changed_at);
    }
}
