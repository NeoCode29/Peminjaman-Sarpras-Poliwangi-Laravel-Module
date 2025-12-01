<?php

namespace Modules\SaranaManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaApprover;
use Modules\SaranaManagement\Policies\SaranaApproverPolicy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SaranaApproverPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected SaranaApproverPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new SaranaApproverPolicy();

        Permission::firstOrCreate([
            'name' => 'sarpras.assign_specific_approver',
            'guard_name' => 'web',
        ]);
    }

    protected function makeUserWithPermission(bool $hasPermission): User
    {
        /** @var User $user */
        $user = User::factory()->create();

        if ($hasPermission) {
            $user->givePermissionTo('sarpras.assign_specific_approver');
        }

        return $user;
    }

    public function test_user_with_permission_can_view_create_update_delete_approver(): void
    {
        $user = $this->makeUserWithPermission(true);
        $sarana = Sarana::factory()->make();
        $approver = SaranaApprover::factory()->make();

        $this->assertTrue($this->policy->viewAny($user, $sarana));
        $this->assertTrue($this->policy->create($user, $sarana));
        $this->assertTrue($this->policy->update($user, $approver));
        $this->assertTrue($this->policy->delete($user, $approver));
    }

    public function test_user_without_permission_cannot_manage_approvers(): void
    {
        $user = $this->makeUserWithPermission(false);
        $sarana = Sarana::factory()->make();
        $approver = SaranaApprover::factory()->make();

        $this->assertFalse($this->policy->viewAny($user, $sarana));
        $this->assertFalse($this->policy->create($user, $sarana));
        $this->assertFalse($this->policy->update($user, $approver));
        $this->assertFalse($this->policy->delete($user, $approver));
    }
}
