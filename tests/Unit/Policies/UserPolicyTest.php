<?php

namespace Tests\Unit\Policies;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use DatabaseMigrations;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy();
        $this->forgetPermissionCache();

        // Ensure the core manage-user permission exists so hasPermissionTo does not throw
        Permission::query()->firstOrCreate([
            'name' => 'user.manage',
            'guard_name' => 'web',
        ]);
    }

    protected function tearDown(): void
    {
        $this->forgetPermissionCache();

        parent::tearDown();
    }

    /** @test */
    public function admin_with_manage_permission_can_manage_other_users_but_not_self_delete_or_block(): void
    {
        $admin = $this->createAdminWithManagePermission();
        $otherUser = User::factory()->create();

        // viewAny
        $this->assertTrue($this->policy->viewAny($admin));

        // view
        $this->assertTrue($this->policy->view($admin, $otherUser));

        // create
        $this->assertTrue($this->policy->create($admin));

        // update another user
        $this->assertTrue($this->policy->update($admin, $otherUser));

        // delete another user
        $this->assertTrue($this->policy->delete($admin, $otherUser));

        // block / unblock another user
        $this->assertTrue($this->policy->block($admin, $otherUser));
        $this->assertTrue($this->policy->unblock($admin, $otherUser));

        // cannot delete or block/unblock self
        $this->assertFalse($this->policy->delete($admin, $admin));
        $this->assertFalse($this->policy->block($admin, $admin));
        $this->assertFalse($this->policy->unblock($admin, $admin));
    }

    /** @test */
    public function user_without_manage_permission_cannot_manage_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $otherUser));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $otherUser));
        $this->assertFalse($this->policy->delete($user, $otherUser));
        $this->assertFalse($this->policy->block($user, $otherUser));
        $this->assertFalse($this->policy->unblock($user, $otherUser));
    }

    /** @test */
    public function user_can_view_and_update_and_change_their_own_profile(): void
    {
        $user = User::factory()->create();

        // Self view/update allowed even without manage permission
        $this->assertTrue($this->policy->view($user, $user));
        $this->assertTrue($this->policy->update($user, $user));

        // Self change password allowed
        $this->assertTrue($this->policy->changePassword($user, $user));
    }

    /** @test */
    public function user_without_manage_permission_cannot_change_other_users_password(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->changePassword($user, $otherUser));
    }

    /** @test */
    public function admin_with_manage_permission_can_change_other_users_password(): void
    {
        $admin = $this->createAdminWithManagePermission();
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->changePassword($admin, $otherUser));
    }

    private function createAdminWithManagePermission(): User
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
