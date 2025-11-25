<?php

namespace Tests\Feature;

use App\Constants\ProtectedRoles;
use App\Events\RoleAuditLogged;
use App\Events\RoleCreated;
use App\Events\RoleDeleted;
use App\Events\RoleUpdated;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use DatabaseMigrations;

    private RoleService $roleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleService = $this->app->make(RoleService::class);

        $this->forgetPermissionCache();

        Event::fake([
            RoleCreated::class,
            RoleUpdated::class,
            RoleDeleted::class,
            RoleAuditLogged::class,
        ]);
    }

    public function test_it_can_list_roles_with_filters(): void
    {
        Role::factory()->count(2)->create();
        Role::factory()->inactive()->create([
            'name' => 'archived_role',
            'display_name' => 'Archived Role',
            'category' => 'archive',
        ]);
        Role::factory()->create([
            'name' => 'inventory_manager',
            'display_name' => 'Inventory Manager',
            'description' => 'Manage inventory',
            'category' => 'inventory',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $this->forgetPermissionCache();

        $result = $this->roleService->getRoles([
            'search' => 'inventory',
            'category' => 'inventory',
            'guard_name' => 'web',
            'is_active' => true,
        ], perPage: 10);

        $this->assertSame(1, $result->total());
        $this->assertSame('inventory_manager', $result->items()[0]->name);
    }

    public function test_it_can_create_role_with_permissions(): void
    {
        $permissions = Permission::factory()->count(2)->create();

        $this->forgetPermissionCache();

        $payload = [
            'name' => 'Logistic Manager',
            'display_name' => 'Logistic Manager',
            'description' => 'Responsible for logistic workflow',
            'category' => 'logistic',
            'guard_name' => 'web',
            'is_active' => true,
            'permissions' => $permissions->pluck('id')->all(),
        ];

        $role = $this->roleService->createRole($payload);

        $this->assertDatabaseHas('roles', [
            'name' => 'Logistic Manager',
            'display_name' => 'Logistic Manager',
            'category' => 'logistic',
            'is_active' => true,
        ]);
        $this->assertEqualsCanonicalizing(
            $permissions->pluck('id')->all(),
            $role->permissions->pluck('id')->all()
        );

        Event::assertDispatched(RoleCreated::class);
        Event::assertDispatched(RoleAuditLogged::class, function (RoleAuditLogged $event) use ($role) {
            return $event->role->is($role) && $event->action === 'created';
        });
    }

    public function test_it_can_update_role_and_permissions(): void
    {
        $role = Role::factory()->create([
            'name' => 'dispatcher',
            'display_name' => 'Dispatcher',
            'description' => 'Initial description',
            'category' => 'logistic',
        ]);
        $initialPermission = Permission::factory()->create();
        $role->syncPermissions([$initialPermission->id]);

        $newPermissions = Permission::factory()->count(2)->create();

        $this->forgetPermissionCache();

        $payload = [
            'name' => 'dispatcher',
            'display_name' => 'Dispatcher Updated',
            'description' => 'Updated description',
            'category' => 'logistic',
            'guard_name' => 'web',
            'is_active' => true,
            'permissions' => $newPermissions->pluck('id')->all(),
        ];

        $updatedRole = $this->roleService->updateRole($role, $payload);

        $this->assertSame('Dispatcher Updated', $updatedRole->display_name);
        $this->assertEqualsCanonicalizing(
            $newPermissions->pluck('id')->all(),
            $updatedRole->permissions->pluck('id')->all()
        );

        Event::assertDispatched(RoleUpdated::class, function (RoleUpdated $event) use ($role) {
            return $event->role->is($role);
        });
        Event::assertDispatched(RoleAuditLogged::class, function (RoleAuditLogged $event) use ($role) {
            return $event->role->is($role) && $event->action === 'updated';
        });
    }

    public function test_it_prevents_deleting_protected_role(): void
    {
        $protectedName = Arr::first(ProtectedRoles::ROLES);

        $role = Role::withoutEvents(fn () => Role::create([
            'name' => $protectedName,
            'guard_name' => 'web',
            'display_name' => Str::headline($protectedName),
            'description' => 'System protected role',
            'category' => 'system',
            'is_active' => true,
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role sistem tidak dapat dihapus.');

        $this->roleService->deleteRole($role);
    }

    public function test_it_prevents_deleting_role_in_use(): void
    {
        $role = Role::factory()->create([
            'name' => 'operator',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->forgetPermissionCache();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role masih digunakan oleh user.');

        $this->roleService->deleteRole($role);
    }

    public function test_it_can_delete_role_not_in_use(): void
    {
        $role = Role::factory()->create([
            'name' => 'report_viewer',
        ]);

        $this->roleService->deleteRole($role);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);

        Event::assertDispatched(RoleDeleted::class, function (RoleDeleted $event) use ($role) {
            return $event->roleId === $role->id;
        });
        Event::assertDispatched(RoleAuditLogged::class, function (RoleAuditLogged $event) use ($role) {
            return $event->role->getKey() === $role->getKey() && $event->action === 'deleted';
        });
    }

    public function test_it_prevents_disabling_role_in_use_when_toggling_status(): void
    {
        $role = Role::factory()->create([
            'name' => 'reviewer',
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->forgetPermissionCache();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role tidak dapat dinonaktifkan karena masih digunakan oleh user.');

        $this->roleService->toggleStatus($role);
    }

    public function test_it_can_toggle_role_status(): void
    {
        $role = Role::factory()->inactive()->create([
            'name' => 'approver',
        ]);

        $updatedRole = $this->roleService->toggleStatus($role);

        $this->assertTrue($updatedRole->is_active);
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'is_active' => true,
        ]);

        Event::assertDispatched(RoleUpdated::class, function (RoleUpdated $event) use ($role) {
            return $event->role->is($role);
        });
        Event::assertDispatched(RoleAuditLogged::class, function (RoleAuditLogged $event) use ($role) {
            return $event->role->is($role) && $event->action === 'updated';
        });
    }

    public function test_it_assigns_role_to_user(): void
    {
        $role = Role::factory()->create([
            'name' => 'assignment_role',
        ]);
        $user = User::factory()->create();

        $this->roleService->assignRoleToUser($user, $role->id);

        $user->refresh();

        $this->forgetPermissionCache();

        $this->assertSame($role->id, $user->role_id);
        $this->assertTrue($user->hasRole($role));
    }

    public function test_it_rejects_assigning_inactive_role_to_user(): void
    {
        $role = Role::factory()->inactive()->create([
            'name' => 'inactive_role_test',
        ]);
        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Role tidak valid atau tidak aktif.');

        $this->roleService->assignRoleToUser($user, $role->id);
    }

    public function test_it_rejects_syncing_inactive_permissions(): void
    {
        $role = Role::factory()->create([
            'name' => 'compliance_officer',
        ]);
        $activePermission = Permission::factory()->create();
        $inactivePermission = Permission::factory()->inactive()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Beberapa permission tidak valid atau tidak aktif.');

        $this->roleService->syncPermissions($role, [
            $activePermission->id,
            $inactivePermission->id,
        ]);
    }

    public function test_it_can_get_role_by_id(): void
    {
        $role = Role::factory()->create();

        $found = $this->roleService->getRoleById($role->id);

        $this->assertTrue($role->is($found));
    }

    public function test_it_throws_when_role_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->roleService->getRoleById(999);
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
