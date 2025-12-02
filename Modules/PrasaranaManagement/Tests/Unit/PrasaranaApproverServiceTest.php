<?php

namespace Modules\PrasaranaManagement\Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Entities\PrasaranaApprover;
use Modules\PrasaranaManagement\Services\PrasaranaApproverService;
use Tests\TestCase;

class PrasaranaApproverServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PrasaranaApproverService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var PrasaranaApproverService $service */
        $this->service = $this->app->make(PrasaranaApproverService::class);
    }

    public function test_create_update_delete_approver_and_audit_logs_written(): void
    {
        $prasarana = Prasarana::factory()->create();
        $user = User::factory()->create();

        // create
        $approver = $this->service->createApprover([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('prasarana_approvers', ['id' => $approver->id]);
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => PrasaranaApprover::class,
            'model_id' => $approver->id,
            'action' => 'created',
        ]);

        // update
        $updated = $this->service->updateApprover($approver, [
            'approval_level' => 2,
            'is_active' => false,
        ]);

        $this->assertEquals(2, $updated->approval_level);
        $this->assertFalse($updated->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => PrasaranaApprover::class,
            'model_id' => $approver->id,
            'action' => 'updated',
        ]);

        // delete
        $this->service->deleteApprover($updated);

        $this->assertDatabaseMissing('prasarana_approvers', ['id' => $approver->id]);
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => PrasaranaApprover::class,
            'model_id' => $approver->id,
            'action' => 'deleted',
        ]);
    }

    public function test_duplicate_combination_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);

        $prasarana = Prasarana::factory()->create();
        $user = User::factory()->create();

        $this->service->createApprover([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        // Duplicate: same prasarana, user, level
        $this->service->createApprover([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);
    }

    public function test_getters_delegate_to_repository(): void
    {
        $prasarana = Prasarana::factory()->create();
        $user = User::factory()->create();

        PrasaranaApprover::factory()->create([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $paginator = $this->service->getApproversForPrasarana($prasarana->id, [], 10);
        $this->assertSame(1, $paginator->total());

        $active = $this->service->getActiveApproversForPrasarana($prasarana->id);
        $this->assertCount(1, $active);
    }
}
