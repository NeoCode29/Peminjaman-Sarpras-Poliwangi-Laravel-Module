<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->forgetPermissionCache();
    }

    protected function tearDown(): void
    {
        $this->forgetPermissionCache();

        parent::tearDown();
    }

    public function test_it_can_list_users_for_admin(): void
    {
        $admin = $this->createAdminUser();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('user-management.index'));

        $response->assertOk();
        $response->assertViewIs('users.index');
        $response->assertViewHas('users');
    }

    public function test_it_can_create_user(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::factory()->create(['is_active' => true]);

        $payload = [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'user_type' => 'mahasiswa',
            'status' => 'active',
            'role_id' => $role->id,
        ];

        $response = $this->actingAs($admin)->post(route('user-management.store'), $payload);

        $response->assertRedirect(route('user-management.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'user_type' => 'mahasiswa',
        ]);
    }

    public function test_it_can_update_user(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::factory()->create(['is_active' => true]);

        $user = User::factory()->create([
            'name' => 'Old Name',
            'username' => 'olduser',
            'email' => 'old@example.com',
            'user_type' => 'mahasiswa',
            'status' => 'active',
            'role_id' => $role->id,
        ]);

        $payload = [
            'name' => 'Updated Name',
            'username' => 'updateduser',
            'email' => 'updated@example.com',
            'user_type' => 'staff',
            'status' => 'inactive',
            'role_id' => $role->id,
        ];

        $response = $this->actingAs($admin)->put(route('user-management.update', $user), $payload);

        $response->assertRedirect(route('user-management.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'user_type' => 'staff',
            'status' => 'inactive',
        ]);
    }

    public function test_it_can_delete_other_user_but_not_self(): void
    {
        $admin = $this->createAdminUser();
        $otherUser = User::factory()->create();

        // delete other user
        $response = $this->actingAs($admin)->delete(route('user-management.destroy', $otherUser));
        $response->assertRedirect(route('user-management.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $otherUser->id]);

        // delete self should redirect back with errors
        $response = $this->actingAs($admin)->delete(route('user-management.destroy', $admin));
        $response->assertForbidden();
    }

    private function createAdminUser(): User
    {
        $this->forgetPermissionCache();

        $permission = Permission::query()->firstOrCreate([
            'name' => 'user.manage',
            'guard_name' => 'web',
        ]);

        /** @var Role $role */
        $role = Role::query()->firstOrCreate([
            'name' => 'Admin Sarpras',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);

        /** @var User $user */
        $user = User::factory()->create([
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
        $user->assignRole($role);

        $this->forgetPermissionCache();

        return $user;
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
