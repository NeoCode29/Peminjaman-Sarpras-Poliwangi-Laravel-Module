<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementAuthorizationTest extends TestCase
{
    use DatabaseMigrations;

    private User $adminUser;
    private User $regularUser;
    private Permission $managePermission;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permission
        $this->managePermission = Permission::create([
            'name' => 'user.manage',
            'guard_name' => 'web',
            'display_name' => 'Manage Users',
            'description' => 'Access user management page',
            'category' => 'user',
            'is_active' => true,
        ]);

        // Create role with manage permission
        $adminRole = Role::create([
            'name' => 'Admin Sarpras',
            'guard_name' => 'web',
            'display_name' => 'Admin Sarpras',
            'is_active' => true,
        ]);
        $adminRole->givePermissionTo($this->managePermission);

        // Create regular role without manage permission
        $regularRole = Role::create([
            'name' => 'Peminjam Staff',
            'guard_name' => 'web',
            'display_name' => 'Peminjam Staff',
            'is_active' => true,
        ]);

        // Create users
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole($regularRole);

        $this->forgetPermissionCache();
    }

    /** @test */
    public function user_with_manage_permission_can_view_any_users(): void
    {
        $this->assertTrue(
            $this->adminUser->can('viewAny', User::class)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_view_any_users(): void
    {
        $this->assertFalse(
            $this->regularUser->can('viewAny', User::class)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_create_user(): void
    {
        $this->assertTrue(
            $this->adminUser->can('create', User::class)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_create_user(): void
    {
        $this->assertFalse(
            $this->regularUser->can('create', User::class)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_delete_other_user(): void
    {
        $targetUser = User::factory()->create();

        $this->assertTrue(
            $this->adminUser->can('delete', $targetUser)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_delete_user(): void
    {
        $targetUser = User::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('delete', $targetUser)
        );
    }

    /** @test */
    public function user_cannot_delete_themselves(): void
    {
        $this->assertFalse(
            $this->adminUser->can('delete', $this->adminUser)
        );
    }

    /** @test */
    public function user_can_view_their_own_profile(): void
    {
        // Even without manage permission, user can view own profile
        $this->assertTrue(
            $this->regularUser->can('view', $this->regularUser)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_view_other_profile(): void
    {
        $this->assertTrue(
            $this->adminUser->can('view', $this->regularUser)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_view_other_profile(): void
    {
        $otherUser = User::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('view', $otherUser)
        );
    }

    /** @test */
    public function user_can_update_their_own_profile(): void
    {
        // Even without manage permission, user can update own profile
        $this->assertTrue(
            $this->regularUser->can('update', $this->regularUser)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_update_other_user(): void
    {
        $this->assertTrue(
            $this->adminUser->can('update', $this->regularUser)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_update_other_user(): void
    {
        $otherUser = User::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('update', $otherUser)
        );
    }

    /** @test */
    public function user_can_change_their_own_password(): void
    {
        $this->assertTrue(
            $this->regularUser->can('changePassword', $this->regularUser)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_change_other_password(): void
    {
        $this->assertTrue(
            $this->adminUser->can('changePassword', $this->regularUser)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_change_other_password(): void
    {
        $otherUser = User::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('changePassword', $otherUser)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_block_user(): void
    {
        $targetUser = User::factory()->create();

        $this->assertTrue(
            $this->adminUser->can('block', $targetUser)
        );
    }

    /** @test */
    public function user_cannot_block_themselves(): void
    {
        $this->assertFalse(
            $this->adminUser->can('block', $this->adminUser)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_block_user(): void
    {
        $targetUser = User::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('block', $targetUser)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_unblock_user(): void
    {
        $targetUser = User::factory()->create();

        $this->assertTrue(
            $this->adminUser->can('unblock', $targetUser)
        );
    }

    /** @test */
    public function user_cannot_unblock_themselves(): void
    {
        $this->assertFalse(
            $this->adminUser->can('unblock', $this->adminUser)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_unblock_user(): void
    {
        $targetUser = User::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('unblock', $targetUser)
        );
    }

    protected function forgetPermissionCache(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
