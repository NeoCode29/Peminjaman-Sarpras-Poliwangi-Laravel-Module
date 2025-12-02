<?php

namespace Modules\PrasaranaManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Entities\PrasaranaApprover;
use Modules\PrasaranaManagement\Policies\PrasaranaApproverPolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PrasaranaApproverPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected PrasaranaApproverPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PrasaranaApproverPolicy();

        Permission::firstOrCreate([
            'name' => 'sarpras.assign_specific_approver',
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

    public function test_user_with_assign_permission_can_manage_approvers(): void
    {
        $user = $this->makeUserWith(['sarpras.assign_specific_approver']);
        $prasarana = Prasarana::factory()->make();
        $approver = PrasaranaApprover::factory()->make();

        $this->assertTrue($this->policy->viewAny($user, $prasarana));
        $this->assertTrue($this->policy->create($user, $prasarana));
        $this->assertTrue($this->policy->update($user, $approver));
        $this->assertTrue($this->policy->delete($user, $approver));
    }

    public function test_user_without_assign_permission_cannot_manage_approvers(): void
    {
        $user = $this->makeUserWith();
        $prasarana = Prasarana::factory()->make();
        $approver = PrasaranaApprover::factory()->make();

        $this->assertFalse($this->policy->viewAny($user, $prasarana));
        $this->assertFalse($this->policy->create($user, $prasarana));
        $this->assertFalse($this->policy->update($user, $approver));
        $this->assertFalse($this->policy->delete($user, $approver));
    }
}
