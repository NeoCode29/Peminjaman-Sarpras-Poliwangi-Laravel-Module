<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionManagementAuthorizationTest extends TestCase
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
            'name' => 'permission.manage',
            'guard_name' => 'web',
            'display_name' => 'Manage Permissions',
            'description' => 'Access permission management page',
            'category' => 'permission',
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
    public function user_with_manage_permission_can_view_any_permissions(): void
    {
        $this->assertTrue(
            $this->adminUser->can('viewAny', Permission::class)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_view_any_permissions(): void
    {
        $this->assertFalse(
            $this->regularUser->can('viewAny', Permission::class)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_view_permission(): void
    {
        $targetPermission = Permission::factory()->create();

        $this->assertTrue(
            $this->adminUser->can('view', $targetPermission)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_view_permission(): void
    {
        $targetPermission = Permission::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('view', $targetPermission)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_create_permission(): void
    {
        $this->assertTrue(
            $this->adminUser->can('create', Permission::class)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_create_permission(): void
    {
        $this->assertFalse(
            $this->regularUser->can('create', Permission::class)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_update_permission(): void
    {
        $targetPermission = Permission::factory()->create();

        $this->assertTrue(
            $this->adminUser->can('update', $targetPermission)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_update_permission(): void
    {
        $targetPermission = Permission::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('update', $targetPermission)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_delete_permission(): void
    {
        $targetPermission = Permission::factory()->create([
            'name' => 'deletable.permission',
        ]);

        $this->assertTrue(
            $this->adminUser->can('delete', $targetPermission)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_delete_permission(): void
    {
        $targetPermission = Permission::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('delete', $targetPermission)
        );
    }

    /** @test */
    public function user_with_manage_permission_can_toggle_status(): void
    {
        $targetPermission = Permission::factory()->create();

        $this->assertTrue(
            $this->adminUser->can('toggleStatus', $targetPermission)
        );
    }

    /** @test */
    public function user_without_manage_permission_cannot_toggle_status(): void
    {
        $targetPermission = Permission::factory()->create();

        $this->assertFalse(
            $this->regularUser->can('toggleStatus', $targetPermission)
        );
    }

    protected function forgetPermissionCache(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
