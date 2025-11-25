<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use RuntimeException;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use DatabaseMigrations;

    private PermissionService $permissionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = $this->app->make(PermissionService::class);

        // Pastikan cache permission bersih sebelum tiap pengujian.
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_list_permissions_with_filters(): void
    {
        Permission::factory()->count(5)->create();
        Permission::factory()->count(3)->inactive()->create();
        Permission::factory()->create([
            'name' => 'user.create',
            'display_name' => 'Create User',
            'category' => 'user',
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $result = $this->permissionService->getPermissions([
            'search' => 'create',
            'category' => 'user',
            'is_active' => true,
        ], perPage: 10);

        $this->assertSame(1, $result->total());
        $this->assertSame('user.create', $result->items()[0]->name);
    }

    /** @test */
    public function it_can_create_permission(): void
    {
        $payload = [
            'name' => 'inventory.manage',
            'display_name' => 'Manage Inventory',
            'description' => 'Manage inventory items',
            'category' => 'inventory',
            'guard_name' => 'web',
            'is_active' => true,
        ];

        $permission = $this->permissionService->createPermission($payload);

        $this->assertDatabaseHas('permissions', [
            'name' => 'inventory.manage',
            'display_name' => 'Manage Inventory',
            'category' => 'inventory',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_update_permission(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'inventory.manage',
            'display_name' => 'Manage Inventory',
            'description' => 'Manage inventory items',
            'category' => 'inventory',
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $payload = [
            'name' => 'inventory.manage',
            'display_name' => 'Manage Inventory Updated',
            'description' => 'Updated description',
            'category' => 'inventory',
            'guard_name' => 'web',
            'is_active' => true,
        ];

        $updated = $this->permissionService->updatePermission($permission, $payload);

        $this->assertSame('Manage Inventory Updated', $updated->display_name);
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'display_name' => 'Manage Inventory Updated',
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function it_prevents_deleting_permission_in_use(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'user.delete',
            'display_name' => 'Delete User',
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::factory()->create();
        $role->givePermissionTo($permission);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Permission tidak dapat dihapus karena masih digunakan oleh role.');

        $this->permissionService->deletePermission($permission);
    }

    /** @test */
    public function it_can_delete_permission_not_in_use(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'report.generate',
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->permissionService->deletePermission($permission);

        $this->assertDatabaseMissing('permissions', [
            'id' => $permission->id,
        ]);
    }

    /** @test */
    public function it_can_toggle_permission_status(): void
    {
        $permission = Permission::factory()->inactive()->create([
            'name' => 'approval.review',
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $updated = $this->permissionService->toggleStatus($permission);

        $this->assertTrue($updated->is_active);
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_throws_exception_when_name_is_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nama permission wajib diisi.');

        $this->permissionService->createPermission([
            'name' => '',
            'display_name' => 'Invalid Permission',
            'category' => 'test',
        ]);
    }
}
