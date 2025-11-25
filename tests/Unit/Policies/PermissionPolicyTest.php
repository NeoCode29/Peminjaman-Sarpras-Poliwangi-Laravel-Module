<?php

namespace Tests\Unit\Policies;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\PermissionPolicy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionPolicyTest extends TestCase
{
    use DatabaseMigrations;

    private PermissionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PermissionPolicy();
        $this->forgetPermissionCache();

        // Ensure the core manage-permission exists so hasPermissionTo does not throw
        Permission::query()->firstOrCreate([
            'name' => 'permission.manage',
            'guard_name' => 'web',
        ]);
    }

    /** @test */
    public function admin_sarpras_can_manage_permissions(): void
    {
        $admin = $this->createAdminSarpras();
        $permission = Permission::factory()->create();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $permission));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $permission));
        $this->assertTrue($this->policy->delete($admin, $permission));
        $this->assertTrue($this->policy->toggleStatus($admin, $permission));
    }

    /** @test */
    public function non_admin_sarpras_cannot_manage_permissions(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $permission));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $permission));
        $this->assertFalse($this->policy->delete($user, $permission));
        $this->assertFalse($this->policy->toggleStatus($user, $permission));
    }

    private function createAdminSarpras(): User
    {
        $this->forgetPermissionCache();

        $permission = Permission::query()->firstOrCreate([
            'name' => 'permission.manage',
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
