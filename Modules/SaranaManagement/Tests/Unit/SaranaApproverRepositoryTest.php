<?php

namespace Modules\SaranaManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaApprover;
use Modules\SaranaManagement\Repositories\SaranaApproverRepository;
use Tests\TestCase;

class SaranaApproverRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected SaranaApproverRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new SaranaApproverRepository();
    }

    public function test_create_and_find_by_id_loads_relations(): void
    {
        $sarana = Sarana::factory()->create();
        $user = User::factory()->create();

        $created = $this->repository->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $found = $this->repository->findById($created->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->relationLoaded('sarana'));
        $this->assertTrue($found->relationLoaded('approver'));
        $this->assertEquals($sarana->id, $found->sarana_id);
        $this->assertEquals($user->id, $found->approver_id);
    }

    public function test_update_returns_fresh_instance_with_relations(): void
    {
        $sarana = Sarana::factory()->create();
        $user = User::factory()->create();

        $approver = SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $updated = $this->repository->update($approver, [
            'approval_level' => 2,
            'is_active' => false,
        ]);

        $this->assertEquals(2, $updated->approval_level);
        $this->assertFalse((bool) $updated->is_active);
        $this->assertTrue($updated->relationLoaded('sarana'));
        $this->assertTrue($updated->relationLoaded('approver'));
    }

    public function test_delete_removes_record(): void
    {
        $sarana = Sarana::factory()->create();
        $user = User::factory()->create();

        $approver = SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $user->id,
        ]);

        $this->assertTrue($this->repository->delete($approver));

        $this->assertDatabaseMissing('sarana_approvers', [
            'id' => $approver->id,
        ]);
    }

    public function test_get_by_sarana_with_filters_and_pagination(): void
    {
        $sarana = Sarana::factory()->create();
        $otherSarana = Sarana::factory()->create();
        $users = User::factory()->count(3)->create();

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

        SaranaApprover::factory()->create([
            'sarana_id' => $otherSarana->id,
            'approver_id' => $users[2]->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $paginator = $this->repository->getBySarana($sarana->id, ['is_active' => true], 10);

        $this->assertSame(1, $paginator->total());
        $this->assertEquals($users[0]->id, $paginator->items()[0]->approver_id);
    }

    public function test_get_active_by_sarana_returns_only_active_for_given_sarana(): void
    {
        $sarana = Sarana::factory()->create();
        $otherSarana = Sarana::factory()->create();
        $users = User::factory()->count(3)->create();

        SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $users[0]->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        // Non-active on same sarana
        SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $users[1]->id,
            'approval_level' => 2,
            'is_active' => false,
        ]);

        // Active but different sarana
        SaranaApprover::factory()->create([
            'sarana_id' => $otherSarana->id,
            'approver_id' => $users[2]->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $result = $this->repository->getActiveBySarana($sarana->id);

        $this->assertCount(1, $result);
        $this->assertEquals($users[0]->id, $result[0]->approver_id);
    }

    public function test_exists_for_sarana_and_user_detects_duplicates_and_respects_ignore_id(): void
    {
        $sarana = Sarana::factory()->create();
        $user = User::factory()->create();

        $approver = SaranaApprover::factory()->create([
            'sarana_id' => $sarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $this->assertTrue(
            $this->repository->existsForSaranaAndUser($sarana->id, $user->id, 1)
        );

        $this->assertFalse(
            $this->repository->existsForSaranaAndUser($sarana->id, $user->id, 1, $approver->id)
        );
    }
}
