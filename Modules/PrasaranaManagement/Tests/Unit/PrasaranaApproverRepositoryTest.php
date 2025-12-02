<?php

namespace Modules\PrasaranaManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Entities\PrasaranaApprover;
use Modules\PrasaranaManagement\Repositories\PrasaranaApproverRepository;
use Tests\TestCase;

class PrasaranaApproverRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected PrasaranaApproverRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PrasaranaApproverRepository();
    }

    public function test_create_and_find_by_id_loads_relations(): void
    {
        $prasarana = Prasarana::factory()->create();
        $user = User::factory()->create();

        $created = $this->repository->create([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        $found = $this->repository->findById($created->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->relationLoaded('prasarana'));
        $this->assertTrue($found->relationLoaded('approver'));
    }

    public function test_update_returns_fresh_instance_with_relations(): void
    {
        $prasarana = Prasarana::factory()->create();
        $user = User::factory()->create();

        $approver = PrasaranaApprover::factory()->create([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
        ]);

        $updated = $this->repository->update($approver, [
            'approval_level' => 2,
        ]);

        $this->assertEquals(2, $updated->approval_level);
        $this->assertTrue($updated->relationLoaded('prasarana'));
        $this->assertTrue($updated->relationLoaded('approver'));
    }

    public function test_delete_removes_record(): void
    {
        $approver = PrasaranaApprover::factory()->create();

        $this->assertTrue($this->repository->delete($approver));

        $this->assertDatabaseMissing('prasarana_approvers', [
            'id' => $approver->id,
        ]);
    }

    public function test_get_by_prasarana_applies_filters_and_pagination(): void
    {
        $prasarana = Prasarana::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PrasaranaApprover::factory()->create([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user1->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        PrasaranaApprover::factory()->create([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user2->id,
            'approval_level' => 2,
            'is_active' => false,
        ]);

        $paginator = $this->repository->getByPrasarana($prasarana->id, ['is_active' => true], 10);

        $this->assertSame(1, $paginator->total());
        $this->assertTrue($paginator->items()[0]->is_active);
    }

    public function test_get_active_by_prasarana_returns_only_active_sorted_by_level(): void
    {
        $prasarana = Prasarana::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PrasaranaApprover::factory()->create([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user2->id,
            'approval_level' => 2,
            'is_active' => true,
        ]);

        PrasaranaApprover::factory()->create([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user1->id,
            'approval_level' => 1,
            'is_active' => true,
        ]);

        PrasaranaApprover::factory()->create([
            'prasarana_id' => $prasarana->id,
            'is_active' => false,
        ]);

        $list = $this->repository->getActiveByPrasarana($prasarana->id);

        $this->assertCount(2, $list);
        $this->assertEquals(1, $list[0]->approval_level);
        $this->assertEquals(2, $list[1]->approval_level);
    }

    public function test_exists_for_prasarana_and_user_checks_duplicates(): void
    {
        $prasarana = Prasarana::factory()->create();
        $user = User::factory()->create();

        $approver = PrasaranaApprover::factory()->create([
            'prasarana_id' => $prasarana->id,
            'approver_id' => $user->id,
            'approval_level' => 1,
        ]);

        $this->assertTrue($this->repository->existsForPrasaranaAndUser($prasarana->id, $user->id, 1));
        $this->assertFalse($this->repository->existsForPrasaranaAndUser($prasarana->id, $user->id, 2));
        $this->assertFalse($this->repository->existsForPrasaranaAndUser($prasarana->id, $user->id, 1, $approver->id));
    }
}
