<?php

namespace Tests\Unit\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use DatabaseMigrations;

    private PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PermissionService::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        parent::tearDown();
    }

    /** @test */
    public function it_creates_permission_with_valid_payload(): void
    {
        $data = [
            'name' => 'user.manage',
            'guard_name' => 'web',
            'display_name' => 'User Manage',
            'description' => 'Manage users',
            'category' => 'user',
            'is_active' => true,
        ];

        $permission = $this->service->createPermission($data);

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertSame('user.manage', $permission->name);
        $this->assertTrue($permission->is_active);
    }

    /** @test */
    public function it_throws_exception_when_creating_permission_with_empty_name(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nama permission wajib diisi.');

        $this->service->createPermission([
            'name' => '',
        ]);
    }

    /** @test */
    public function it_updates_permission_and_sanitizes_name(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'user.view',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $updated = $this->service->updatePermission($permission, [
            'name' => '  USER.VIEW  ',
            'display_name' => 'User View Updated',
        ]);

        $this->assertSame('user.view', $updated->name);
        $this->assertSame('User View Updated', $updated->display_name);
    }

    /** @test */
    public function it_gets_permissions_and_active_permissions_grouped(): void
    {
        Permission::factory()->count(2)->create([
            'is_active' => true,
            'category' => 'user',
        ]);

        $paginator = $this->service->getPermissions();
        $this->assertGreaterThanOrEqual(2, $paginator->total());

        $grouped = $this->service->getActivePermissionsGrouped();

        $this->assertTrue($grouped->has('user'));
        $this->assertGreaterThanOrEqual(2, $grouped['user']->count());
    }

    /** @test */
    public function it_gets_permission_by_id_or_throws_when_not_found(): void
    {
        $permission = Permission::factory()->create();

        $found = $this->service->getPermissionById($permission->id);
        $this->assertTrue($found->is($permission));

        $this->expectException(ModelNotFoundException::class);

        $this->service->getPermissionById(999999);
    }

    /** @test */
    public function it_prevents_deleting_permission_if_still_used_by_roles(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'role.used',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $role = Role::factory()->create([
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $role->givePermissionTo($permission);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Permission tidak dapat dihapus karena masih digunakan oleh role.');

        $this->service->deletePermission($permission);
    }

    /** @test */
    public function it_deletes_permission_when_not_used_by_any_role(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'role.free',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $this->service->deletePermission($permission);

        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    /** @test */
    public function it_toggles_permission_status(): void
    {
        $permission = Permission::factory()->create([
            'is_active' => true,
        ]);

        $toggled = $this->service->toggleStatus($permission);
        $this->assertFalse($toggled->is_active);

        $toggledAgain = $this->service->toggleStatus($toggled);
        $this->assertTrue($toggledAgain->is_active);
    }
}
