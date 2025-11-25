<?php

namespace Tests\Unit\Policies;

use App\Constants\ProtectedRoles;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    use DatabaseMigrations;

    private RolePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new RolePolicy();
        $this->forgetPermissionCache();

        // Ensure the core manage-role permission exists so hasPermissionTo does not throw
        Permission::query()->firstOrCreate([
            'name' => 'role.manage',
            'guard_name' => 'web',
        ]);
    }

    protected function tearDown(): void
    {
        $this->forgetPermissionCache();

        parent::tearDown();
    }

    /** @test */
    public function admin_sarpras_can_manage_non_protected_roles(): void
    {
        $admin = $this->createAdminSarpras();
        $role = Role::factory()->create([
            'name' => 'temporary_role',
        ]);

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $role));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $role));
        $this->assertTrue($this->policy->delete($admin, $role));
        $this->assertTrue($this->policy->toggleStatus($admin, $role));
    }

    /** @test */
    public function admin_sarpras_cannot_delete_protected_roles(): void
    {
        $admin = $this->createAdminSarpras();
        $protectedName = ProtectedRoles::ROLES[0];
        $role = Role::factory()->create([
            'name' => $protectedName,
        ]);

        $this->assertFalse($this->policy->delete($admin, $role));
    }

    /** @test */
    public function non_admin_sarpras_cannot_manage_roles(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create([
            'name' => 'standard_role',
        ]);

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $role));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $role));
        $this->assertFalse($this->policy->delete($user, $role));
        $this->assertFalse($this->policy->toggleStatus($user, $role));
    }

    private function createAdminSarpras(): User
    {
        $this->forgetPermissionCache();

        $permission = Permission::query()->firstOrCreate([
            'name' => 'role.manage',
            'guard_name' => 'web',
        ]);

        /** @var Role $role */
        $role = Role::query()->firstOrCreate([
            'name' => 'Admin Sarpras',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->forgetPermissionCache();

        return $user;
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
