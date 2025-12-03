<?php

namespace Tests\Unit\Policies;

use App\Models\GlobalApprover;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\GlobalApproverPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalApproverPolicyTest extends TestCase
{
    use RefreshDatabase;

    private GlobalApproverPolicy $policy;
    private User $userWithPermission;
    private User $userWithoutPermission;
    private GlobalApprover $globalApprover;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new GlobalApproverPolicy();

        // Create permission
        $permission = Permission::firstOrCreate(
            ['name' => 'global_approver.manage'],
            [
                'guard_name' => 'web',
                'display_name' => 'Manage Global Approvers',
                'description' => 'Can manage global approvers',
            ]
        );

        // Create role with permission
        $roleWithPermission = Role::firstOrCreate(
            ['name' => 'Admin Sarpras'],
            ['guard_name' => 'web', 'display_name' => 'Admin Sarpras']
        );
        $roleWithPermission->givePermissionTo($permission);

        // Create role without permission
        $roleWithoutPermission = Role::firstOrCreate(
            ['name' => 'Peminjam Mahasiswa'],
            ['guard_name' => 'web', 'display_name' => 'Peminjam Mahasiswa']
        );

        // Create users
        $this->userWithPermission = User::factory()->create();
        $this->userWithPermission->assignRole($roleWithPermission);

        $this->userWithoutPermission = User::factory()->create();
        $this->userWithoutPermission->assignRole($roleWithoutPermission);

        // Create global approver for testing
        $approverUser = User::factory()->create();
        $this->globalApprover = GlobalApprover::factory()->create([
            'user_id' => $approverUser->id,
        ]);
    }

    // ==========================================
    // viewAny Tests
    // ==========================================

    public function test_view_any_returns_true_for_user_with_permission(): void
    {
        $result = $this->policy->viewAny($this->userWithPermission);

        $this->assertTrue($result);
    }

    public function test_view_any_returns_false_for_user_without_permission(): void
    {
        $result = $this->policy->viewAny($this->userWithoutPermission);

        $this->assertFalse($result);
    }

    // ==========================================
    // view Tests
    // ==========================================

    public function test_view_returns_true_for_user_with_permission(): void
    {
        $result = $this->policy->view($this->userWithPermission, $this->globalApprover);

        $this->assertTrue($result);
    }

    public function test_view_returns_false_for_user_without_permission(): void
    {
        $result = $this->policy->view($this->userWithoutPermission, $this->globalApprover);

        $this->assertFalse($result);
    }

    // ==========================================
    // create Tests
    // ==========================================

    public function test_create_returns_true_for_user_with_permission(): void
    {
        $result = $this->policy->create($this->userWithPermission);

        $this->assertTrue($result);
    }

    public function test_create_returns_false_for_user_without_permission(): void
    {
        $result = $this->policy->create($this->userWithoutPermission);

        $this->assertFalse($result);
    }

    // ==========================================
    // update Tests
    // ==========================================

    public function test_update_returns_true_for_user_with_permission(): void
    {
        $result = $this->policy->update($this->userWithPermission, $this->globalApprover);

        $this->assertTrue($result);
    }

    public function test_update_returns_false_for_user_without_permission(): void
    {
        $result = $this->policy->update($this->userWithoutPermission, $this->globalApprover);

        $this->assertFalse($result);
    }

    // ==========================================
    // delete Tests
    // ==========================================

    public function test_delete_returns_true_for_user_with_permission(): void
    {
        $result = $this->policy->delete($this->userWithPermission, $this->globalApprover);

        $this->assertTrue($result);
    }

    public function test_delete_returns_false_for_user_without_permission(): void
    {
        $result = $this->policy->delete($this->userWithoutPermission, $this->globalApprover);

        $this->assertFalse($result);
    }

    // ==========================================
    // toggleStatus Tests
    // ==========================================

    public function test_toggle_status_returns_true_for_user_with_permission(): void
    {
        $result = $this->policy->toggleStatus($this->userWithPermission, $this->globalApprover);

        $this->assertTrue($result);
    }

    public function test_toggle_status_returns_false_for_user_without_permission(): void
    {
        $result = $this->policy->toggleStatus($this->userWithoutPermission, $this->globalApprover);

        $this->assertFalse($result);
    }

    // ==========================================
    // Authorization via Gate Tests
    // ==========================================

    public function test_user_with_permission_can_access_via_gate(): void
    {
        $this->actingAs($this->userWithPermission);

        $this->assertTrue($this->userWithPermission->can('viewAny', GlobalApprover::class));
        $this->assertTrue($this->userWithPermission->can('view', $this->globalApprover));
        $this->assertTrue($this->userWithPermission->can('create', GlobalApprover::class));
        $this->assertTrue($this->userWithPermission->can('update', $this->globalApprover));
        $this->assertTrue($this->userWithPermission->can('delete', $this->globalApprover));
    }

    public function test_user_without_permission_cannot_access_via_gate(): void
    {
        $this->actingAs($this->userWithoutPermission);

        $this->assertFalse($this->userWithoutPermission->can('viewAny', GlobalApprover::class));
        $this->assertFalse($this->userWithoutPermission->can('view', $this->globalApprover));
        $this->assertFalse($this->userWithoutPermission->can('create', GlobalApprover::class));
        $this->assertFalse($this->userWithoutPermission->can('update', $this->globalApprover));
        $this->assertFalse($this->userWithoutPermission->can('delete', $this->globalApprover));
    }
}
