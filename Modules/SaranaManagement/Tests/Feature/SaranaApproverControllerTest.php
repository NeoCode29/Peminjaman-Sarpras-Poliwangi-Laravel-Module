<?php

namespace Modules\SaranaManagement\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaApprover;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SaranaApproverControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'sarpras.assign_specific_approver', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'status' => 'active',
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
        $this->adminUser->givePermissionTo('sarpras.assign_specific_approver');

        $this->regularUser = User::factory()->create([
            'status' => 'active',
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
    }

    /** @test */
    public function admin_can_create_update_and_delete_sarana_approver(): void
    {
        $sarana = Sarana::factory()->create();
        $approverUser = User::factory()->create();

        // Store
        $storePayload = [
            'approver_id' => $approverUser->id,
            'approval_level' => 1,
            'is_active' => true,
        ];

        $this->actingAs($this->adminUser)
            ->post(route('sarana.approvers.store', $sarana), $storePayload)
            ->assertRedirect(route('sarana.show', $sarana))
            ->assertSessionHas('sarpras_success');

        $approver = SaranaApprover::first();
        $this->assertNotNull($approver);

        // Update
        $updatePayload = [
            'approval_level' => 2,
            'is_active' => false,
        ];

        $this->actingAs($this->adminUser)
            ->put(route('sarana.approvers.update', [$sarana, $approver]), $updatePayload)
            ->assertRedirect(route('sarana.show', $sarana))
            ->assertSessionHas('sarpras_success');

        $this->assertDatabaseHas('sarana_approvers', [
            'id' => $approver->id,
            'approval_level' => 2,
            'is_active' => false,
        ]);

        // Destroy
        $this->actingAs($this->adminUser)
            ->delete(route('sarana.approvers.destroy', [$sarana, $approver]))
            ->assertRedirect(route('sarana.show', $sarana))
            ->assertSessionHas('sarpras_success');

        $this->assertDatabaseMissing('sarana_approvers', ['id' => $approver->id]);
    }

    /** @test */
    public function regular_user_cannot_manage_sarana_approvers(): void
    {
        $sarana = Sarana::factory()->create();
        $approverUser = User::factory()->create();
        $approver = SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $approverUser->id,
        ]);

        $this->actingAs($this->regularUser)
            ->post(route('sarana.approvers.store', $sarana), [
                'approver_id' => $approverUser->id,
                'approval_level' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->put(route('sarana.approvers.update', [$sarana, $approver]), [
                'approval_level' => 2,
            ])
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->delete(route('sarana.approvers.destroy', [$sarana, $approver]))
            ->assertForbidden();
    }
}
