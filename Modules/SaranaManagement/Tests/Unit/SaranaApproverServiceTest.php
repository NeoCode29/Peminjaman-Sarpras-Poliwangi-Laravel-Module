<?php

namespace Modules\SaranaManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaApprover;
use Modules\SaranaManagement\Services\SaranaApproverService;
use Tests\TestCase;

class SaranaApproverServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SaranaApproverService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var SaranaApproverService $service */
        $this->service = $this->app->make(SaranaApproverService::class);
    }

    public function test_get_approvers_for_sarana_returns_paginator(): void
    {
        $sarana = Sarana::factory()->create();
        $users = User::factory()->count(3)->create();

        foreach ($users as $index => $user) {
            SaranaApprover::factory()->create([
                'sarana_id' => $sarana->id,
                'approver_id' => $user->id,
                'approval_level' => $index + 1,
            ]);
        }

        $paginator = $this->service->getApproversForSarana($sarana->id, [], 2);

        $this->assertSame(2, $paginator->perPage());
        $this->assertSame(3, $paginator->total());
    }

    public function test_get_active_approvers_for_sarana_uses_repository_filter(): void
    {
        $sarana = Sarana::factory()->create();
        $users = User::factory()->count(2)->create();

        SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $users[0]->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $users[1]->id,
            'approval_level' => 2,
            'is_active' => false,
        ]);

        $active = $this->service->getActiveApproversForSarana($sarana->id);

        $this->assertCount(1, $active);
        $this->assertEquals($users[0]->id, $active[0]->approver_id);
    }

    public function test_create_approver_persists_record_and_prevents_duplicate(): void
    {
        $sarana = Sarana::factory()->create();
        $user = User::factory()->create();

        $created = $this->service->createApprover([
            'sarana_id' => $sarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(SaranaApprover::class, $created);
        $this->assertDatabaseHas('sarana_approvers', [
            'id' => $created->id,
            'sarana_id' => $sarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
        ]);

        $this->expectException(\RuntimeException::class);

        $this->service->createApprover([
            'sarana_id' => $sarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);
    }

    public function test_update_approver_applies_changes_and_prevents_duplicate_level(): void
    {
        $sarana = Sarana::factory()->create();
        $user = User::factory()->create();

        $approverA = SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        // Approver B: sama sarana, sama user, level berbeda
        $approverB = SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $user->id,
            'approval_level' => 2,
            'is_active' => true,
        ]);

        // Update approver A: ubah level dan status berhasil
        $updated = $this->service->updateApprover($approverA, [
            'approval_level' => 3,
            'is_active' => false,
        ]);

        $this->assertEquals(3, $updated->approval_level);
        $this->assertFalse((bool) $updated->is_active);

        // Coba jadikan approver B ke level 3 (yang sudah dipakai A untuk user & sarana yang sama) harus gagal
        $this->expectException(\RuntimeException::class);

        $this->service->updateApprover($approverB, [
            'approval_level' => 3,
        ]);
    }

    public function test_delete_approver_removes_record(): void
    {
        $sarana = Sarana::factory()->create();
        $user = User::factory()->create();

        $approver = SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $user->id,
        ]);

        $this->service->deleteApprover($approver);

        $this->assertDatabaseMissing('sarana_approvers', [
            'id' => $approver->id,
        ]);
    }
}
