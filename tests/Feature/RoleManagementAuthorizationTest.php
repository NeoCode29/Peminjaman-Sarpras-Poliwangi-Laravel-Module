<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleManagementAuthorizationTest extends TestCase
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
            'name' => 'role.manage',
            'guard_name' => 'web',
            'display_name' => 'Manage Roles',
            'description' => 'Access role management page',
            'category' => 'role',
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
    public function user_with_manage_permission_can_view_any_roles(): void
    {
        $this->assertTrue(
            $this->adminUser->can('viewAny', Role::class)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_view_any_roles(): void
    {
        $this->assertFalse(
            $this->regularUser->can('viewAny', Role::class)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_view_role(): void
    {
        $targetRole = Role::factory()->create();

        $this->assertTrue(
            $this->adminUser->can('view', $targetRole)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_view_role(): void
    {
        $targetRole = Role::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('view', $targetRole)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_create_role(): void
    {
        $this->assertTrue(
            $this->adminUser->can('create', Role::class)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_create_role(): void
    {
        $this->assertFalse(
            $this->regularUser->can('create', Role::class)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_update_role(): void
    {
        $targetRole = Role::factory()->create();

        $this->assertTrue(
            $this->adminUser->can('update', $targetRole)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_update_role(): void
    {
        $targetRole = Role::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('update', $targetRole)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_delete_role(): void
    {
        $targetRole = Role::factory()->create([
            'name' => 'deletable_role',
        ]);

        $this->assertTrue(
            $this->adminUser->can('delete', $targetRole)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_delete_role(): void
    {
        $targetRole = Role::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('delete', $targetRole)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_toggle_status(): void
    {
        $targetRole = Role::factory()->create();

        $this->assertTrue(
            $this->adminUser->can('toggleStatus', $targetRole)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_toggle_status(): void
    {
        $targetRole = Role::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('toggleStatus', $targetRole)
        );
    }

    protected function forgetPermissionCache(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
