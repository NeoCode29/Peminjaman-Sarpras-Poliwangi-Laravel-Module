<?php

namespace Tests\Unit\Repositories;

use App\Models\GlobalApprover;
use App\Models\User;
use App\Repositories\GlobalApproverRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalApproverRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private GlobalApproverRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new GlobalApproverRepository();
    }

    // ==========================================
    // getAll Tests
    // ==========================================

    public function test_get_all_returns_paginated_global_approvers(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GlobalApprover::factory()->create(['user_id' => $user1->id, 'approval_level' => 1]);
        GlobalApprover::factory()->create(['user_id' => $user2->id, 'approval_level' => 2]);

        $result = $this->repository->getAll();

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result->first()->approval_level);
    }

    public function test_get_all_filters_by_is_active(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GlobalApprover::factory()->create(['user_id' => $user1->id, 'is_active' => true]);
        GlobalApprover::factory()->create(['user_id' => $user2->id, 'is_active' => false]);

        $activeResult = $this->repository->getAll(['is_active' => true]);
        $inactiveResult = $this->repository->getAll(['is_active' => false]);

        $this->assertCount(1, $activeResult);
        $this->assertTrue($activeResult->first()->is_active);

        $this->assertCount(1, $inactiveResult);
        $this->assertFalse($inactiveResult->first()->is_active);
    }

    public function test_get_all_filters_by_approval_level(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GlobalApprover::factory()->create(['user_id' => $user1->id, 'approval_level' => 1]);
        GlobalApprover::factory()->create(['user_id' => $user2->id, 'approval_level' => 2]);

        $result = $this->repository->getAll(['approval_level' => 1]);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result->first()->approval_level);
    }

    public function test_get_all_filters_by_search(): void
    {
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        GlobalApprover::factory()->create(['user_id' => $user1->id]);
        GlobalApprover::factory()->create(['user_id' => $user2->id]);

        $result = $this->repository->getAll(['search' => 'John']);

        $this->assertCount(1, $result);
        $this->assertEquals($user1->id, $result->first()->user_id);
    }

    // ==========================================
    // getActive Tests
    // ==========================================

    public function test_get_active_returns_only_active_approvers(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GlobalApprover::factory()->create(['user_id' => $user1->id, 'is_active' => true]);
        GlobalApprover::factory()->create(['user_id' => $user2->id, 'is_active' => false]);

        $result = $this->repository->getActive();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is_active);
    }

    // ==========================================
    // findById Tests
    // ==========================================

    public function test_find_by_id_returns_global_approver(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create(['user_id' => $user->id]);

        $result = $this->repository->findById($approver->id);

        $this->assertNotNull($result);
        $this->assertEquals($approver->id, $result->id);
        $this->assertNotNull($result->user);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    // ==========================================
    // findByUserId Tests
    // ==========================================

    public function test_find_by_user_id_returns_global_approver(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create(['user_id' => $user->id]);

        $result = $this->repository->findByUserId($user->id);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->user_id);
    }

    public function test_find_by_user_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findByUserId(999);

        $this->assertNull($result);
    }

    // ==========================================
    // create Tests
    // ==========================================

    public function test_create_creates_global_approver(): void
    {
        $user = User::factory()->create();

        $result = $this->repository->create([
            'user_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(GlobalApprover::class, $result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals(1, $result->approval_level);
        $this->assertTrue($result->is_active);
        $this->assertDatabaseHas('global_approvers', [
            'user_id' => $user->id,
            'approval_level' => 1,
        ]);
    }

    // ==========================================
    // update Tests
    // ==========================================

    public function test_update_updates_global_approver(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $result = $this->repository->update($approver, [
            'approval_level' => 2,
            'is_active' => false,
        ]);

        $this->assertEquals(2, $result->approval_level);
        $this->assertFalse($result->is_active);
        $this->assertDatabaseHas('global_approvers', [
            'id' => $approver->id,
            'approval_level' => 2,
            'is_active' => false,
        ]);
    }

    // ==========================================
    // delete Tests
    // ==========================================

    public function test_delete_removes_global_approver(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create(['user_id' => $user->id]);

        $result = $this->repository->delete($approver);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('global_approvers', ['id' => $approver->id]);
    }

    // ==========================================
    // isUserApprover Tests
    // ==========================================

    public function test_is_user_approver_returns_true_when_user_is_approver(): void
    {
        $user = User::factory()->create();
        GlobalApprover::factory()->create(['user_id' => $user->id]);

        $result = $this->repository->isUserApprover($user->id);

        $this->assertTrue($result);
    }

    public function test_is_user_approver_returns_false_when_user_is_not_approver(): void
    {
        $user = User::factory()->create();

        $result = $this->repository->isUserApprover($user->id);

        $this->assertFalse($result);
    }

    // ==========================================
    // existsCombination Tests
    // ==========================================

    public function test_exists_combination_returns_true_when_combination_exists(): void
    {
        $user = User::factory()->create();
        GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'approval_level' => 1,
        ]);

        $result = $this->repository->existsCombination($user->id, 1);

        $this->assertTrue($result);
    }

    public function test_exists_combination_returns_false_when_combination_not_exists(): void
    {
        $user = User::factory()->create();
        GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'approval_level' => 1,
        ]);

        $result = $this->repository->existsCombination($user->id, 2);

        $this->assertFalse($result);
    }

    public function test_exists_combination_excludes_specified_id(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'approval_level' => 1,
        ]);

        $result = $this->repository->existsCombination($user->id, 1, $approver->id);

        $this->assertFalse($result);
    }

    // ==========================================
    // getByLevel Tests
    // ==========================================

    public function test_get_by_level_returns_approvers_with_specific_level(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GlobalApprover::factory()->create(['user_id' => $user1->id, 'approval_level' => 1, 'is_active' => true]);
        GlobalApprover::factory()->create(['user_id' => $user2->id, 'approval_level' => 2, 'is_active' => true]);

        $result = $this->repository->getByLevel(1);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result->first()->approval_level);
    }

    public function test_get_by_level_returns_only_active_approvers(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GlobalApprover::factory()->create(['user_id' => $user1->id, 'approval_level' => 1, 'is_active' => true]);
        GlobalApprover::factory()->create(['user_id' => $user2->id, 'approval_level' => 1, 'is_active' => false]);

        $result = $this->repository->getByLevel(1);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is_active);
    }

    // ==========================================
    // toggleActive Tests
    // ==========================================

    public function test_toggle_active_toggles_status_from_true_to_false(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $result = $this->repository->toggleActive($approver);

        $this->assertFalse($result->is_active);
        $this->assertDatabaseHas('global_approvers', [
            'id' => $approver->id,
            'is_active' => false,
        ]);
    }

    public function test_toggle_active_toggles_status_from_false_to_true(): void
    {
        $user = User::factory()->create();
        $approver = GlobalApprover::factory()->create([
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        $result = $this->repository->toggleActive($approver);

        $this->assertTrue($result->is_active);
        $this->assertDatabaseHas('global_approvers', [
            'id' => $approver->id,
            'is_active' => true,
        ]);
    }
}
