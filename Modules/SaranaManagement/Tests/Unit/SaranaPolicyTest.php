<?php

namespace Modules\SaranaManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Policies\SaranaPolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SaranaPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected SaranaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new SaranaPolicy();

        Permission::firstOrCreate([
            'name' => 'sarpras.manage',
            'guard_name' => 'web',
        ]);

        Permission::firstOrCreate([
            'name' => 'sarpras.unit_manage',
            'guard_name' => 'web',
        ]);
    }

    protected function makeUserWith(array $permissions = []): User
    {
        /** @var User $user */
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    public function test_user_with_manage_permission_can_view_create_update_delete_sarana(): void
    {
        $user = $this->makeUserWith(['sarpras.manage']);
        $sarana = Sarana::factory()->make();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $sarana));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $sarana));
        $this->assertTrue($this->policy->delete($user, $sarana));
    }

    public function test_user_without_manage_permission_cannot_access_sarana(): void
    {
        $user = $this->makeUserWith();
        $sarana = Sarana::factory()->make();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $sarana));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $sarana));
        $this->assertFalse($this->policy->delete($user, $sarana));
    }

    public function test_manage_units_requires_both_manage_and_unit_permissions(): void
    {
        $sarana = Sarana::factory()->make();

        $userWithNone = $this->makeUserWith();
        $this->assertFalse($this->policy->manageUnits($userWithNone, $sarana));

        $userWithManageOnly = $this->makeUserWith(['sarpras.manage']);
        $this->assertFalse($this->policy->manageUnits($userWithManageOnly, $sarana));

        $userWithBoth = $this->makeUserWith(['sarpras.manage', 'sarpras.unit_manage']);
        $this->assertTrue($this->policy->manageUnits($userWithBoth, $sarana));
    }
}
