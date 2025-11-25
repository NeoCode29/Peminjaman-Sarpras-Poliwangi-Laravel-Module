<?php

namespace Tests\Unit\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use DatabaseMigrations;

    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(RoleService::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        parent::tearDown();
    }

    /** @test */
    public function it_creates_role_with_permissions(): void
    {
        $permission = Permission::factory()->create([
            'is_active' => true,
        ]);

        $data = [
            'name' => 'New Role',
            'guard_name' => 'web',
            'display_name' => 'New Role Display',
            'description' => 'Test role',
            'category' => 'test',
            'is_active' => true,
            'permissions' => [$permission->id],
        ];

        $role = $this->service->createRole($data);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertSame('New Role', $role->name);
        $this->assertTrue($role->permissions->contains('id', $permission->id));
    }

    /** @test */
    public function it_throws_exception_when_creating_role_with_empty_name(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nama role wajib diisi.');

        $this->service->createRole([
            'name' => '',
        ]);
    }

    /** @test */
    public function it_throws_exception_when_creating_role_with_duplicate_name(): void
    {
        Role::factory()->create([
            'name' => 'Admin Custom',
            'guard_name' => 'web',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nama role sudah digunakan.');

        $this->service->createRole([
            'name' => 'Admin Custom',
            'guard_name' => 'web',
        ]);
    }

    /** @test */
    public function it_updates_role_and_can_sync_permissions(): void
    {
        $role = Role::factory()->create([
            'name' => 'Editor',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $permission = Permission::factory()->create([
            'is_active' => true,
        ]);

        $updated = $this->service->updateRole($role, [
            'display_name' => 'Editor Updated',
            'permissions' => [$permission->id],
        ]);

        $this->assertSame('Editor Updated', $updated->display_name);
        $this->assertTrue($updated->permissions->contains('id', $permission->id));
    }

    /** @test */
    public function it_prevents_changing_name_for_protected_role(): void
    {
        $role = Role::factory()->create([
            'name' => 'Admin Sarpras',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nama role sistem tidak dapat diubah.');

        $this->service->updateRole($role, [
            'name' => 'Something Else',
        ]);
    }

    /** @test */
    public function it_deletes_role_when_not_protected_and_not_used(): void
    {
        $role = Role::factory()->create([
            'name' => 'Temporary Role',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $this->service->deleteRole($role);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /** @test */
    public function it_prevents_deleting_protected_role(): void
    {
        $role = Role::factory()->create([
            'name' => 'Admin Sarpras',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role sistem tidak dapat dihapus.');

        $this->service->deleteRole($role);
    }

    /** @test */
    public function it_prevents_deleting_role_if_still_used_by_users(): void
    {
        $role = Role::factory()->create([
            'name' => 'Used Role',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $user->assignRole($role);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role masih digunakan oleh user.');

        $this->service->deleteRole($role);
    }

    /** @test */
    public function it_toggles_status_and_prevents_disabling_role_with_users(): void
    {
        $roleWithoutUsers = Role::factory()->create([
            'name' => 'Alone Role',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $toggled = $this->service->toggleStatus($roleWithoutUsers);
        $this->assertFalse($toggled->is_active);

        $roleWithUsers = Role::factory()->create([
            'name' => 'Busy Role',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role_id' => $roleWithUsers->id,
        ]);
        $user->assignRole($roleWithUsers);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role tidak dapat dinonaktifkan karena masih digunakan oleh user.');

        $this->service->toggleStatus($roleWithUsers);
    }

    /** @test */
    public function it_gets_roles_and_active_roles(): void
    {
        Role::factory()->count(2)->create(['is_active' => true]);
        Role::factory()->create(['is_active' => false]);

        $paginator = $this->service->getRoles();
        $this->assertGreaterThanOrEqual(3, $paginator->total());

        $active = $this->service->getActiveRoles();
        $this->assertCount(2, $active);
    }

    /** @test */
    public function it_gets_role_by_id_or_throws_when_not_found(): void
    {
        $role = Role::factory()->create();

        $found = $this->service->getRoleById($role->id);
        $this->assertTrue($found->is($role));

        $this->expectException(ModelNotFoundException::class);

        $this->service->getRoleById(999999);
    }

    /** @test */
    public function it_assigns_role_to_user_and_validates_role_status(): void
    {
        $activeRole = Role::factory()->create([
            'is_active' => true,
        ]);

        $inactiveRole = Role::factory()->create([
            'is_active' => false,
        ]);

        $user = User::factory()->create();

        $this->service->assignRoleToUser($user, $activeRole->id);

        $user->refresh();
        $this->assertEquals($activeRole->id, $user->role_id);
        $this->assertTrue($user->roles->contains('id', $activeRole->id));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role tidak valid atau tidak aktif.');

        $this->service->assignRoleToUser($user, $inactiveRole->id);
    }
}
