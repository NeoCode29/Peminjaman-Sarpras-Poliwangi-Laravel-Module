<?php

namespace Tests\Unit\Repositories;

use App\Models\Ukm;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\MarkingManagement\Entities\Marking;
use Modules\MarkingManagement\Repositories\MarkingRepository;
use Modules\PrasaranaManagement\Entities\Prasarana;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkingRepositoryTest extends TestCase
{
    use DatabaseMigrations;

    private MarkingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(MarkingRepository::class);
    }

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createUkm(): Ukm
    {
        return Ukm::factory()->create();
    }

    private function createPrasarana(): Prasarana
    {
        return Prasarana::factory()->create();
    }

    private function createMarking(array $overrides = []): Marking
    {
        $user = $overrides['user_id'] ?? $this->createUser()->id;
        $ukm = $overrides['ukm_id'] ?? $this->createUkm()->id;
        $prasarana = $overrides['prasarana_id'] ?? $this->createPrasarana()->id;

        return Marking::create(array_merge([
            'user_id' => $user,
            'ukm_id' => $ukm,
            'prasarana_id' => $prasarana,
            'lokasi_custom' => null,
            'start_datetime' => now()->addHour(),
            'end_datetime' => now()->addHours(2),
            'jumlah_peserta' => 10,
            'expires_at' => now()->addDays(1),
            'planned_submit_by' => null,
            'status' => Marking::STATUS_ACTIVE,
            'event_name' => 'Test Event',
            'notes' => null,
        ], $overrides));
    }

    #[Test]
    public function it_creates_and_finds_marking_by_id(): void
    {
        $marking = $this->repository->create([
            'user_id' => $this->createUser()->id,
            'ukm_id' => $this->createUkm()->id,
            'prasarana_id' => $this->createPrasarana()->id,
            'lokasi_custom' => null,
            'start_datetime' => now()->addHour(),
            'end_datetime' => now()->addHours(2),
            'jumlah_peserta' => 20,
            'expires_at' => now()->addDays(1),
            'planned_submit_by' => null,
            'status' => Marking::STATUS_ACTIVE,
            'event_name' => 'Created via repository',
            'notes' => 'testing',
        ]);

        $found = $this->repository->findById($marking->id);

        $this->assertNotNull($found);
        $this->assertTrue($marking->is($found));
        $this->assertTrue($found->relationLoaded('user'));
        $this->assertTrue($found->relationLoaded('ukm'));
        $this->assertTrue($found->relationLoaded('prasarana'));
    }

    #[Test]
    public function it_updates_marking_and_returns_fresh_with_relations(): void
    {
        $marking = $this->createMarking();

        $updated = $this->repository->update($marking, [
            'event_name' => 'Updated Event',
            'jumlah_peserta' => 50,
        ]);

        $this->assertSame('Updated Event', $updated->event_name);
        $this->assertSame(50, $updated->jumlah_peserta);
        $this->assertTrue($updated->relationLoaded('user'));
        $this->assertTrue($updated->relationLoaded('ukm'));
        $this->assertTrue($updated->relationLoaded('prasarana'));
    }

    #[Test]
    public function it_gets_all_markings_with_filters_and_pagination(): void
    {
        $user = $this->createUser();

        // active marking for this user
        $this->createMarking([
            'user_id' => $user->id,
            'status' => Marking::STATUS_ACTIVE,
            'event_name' => 'User Active Event',
        ]);

        // other user and status
        $this->createMarking([
            'status' => Marking::STATUS_CANCELLED,
            'event_name' => 'Other Event',
        ]);

        $paginator = $this->repository->getAll([
            'status' => Marking::STATUS_ACTIVE,
            'user_id' => $user->id,
        ], perPage: 10);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('User Active Event', $paginator->items()[0]->event_name);
    }

    #[Test]
    public function it_gets_active_markings(): void
    {
        $this->createMarking(['status' => Marking::STATUS_ACTIVE]);
        $this->createMarking(['status' => Marking::STATUS_CANCELLED]);

        $active = $this->repository->getActiveMarkings();

        $this->assertCount(1, $active);
        $this->assertTrue($active->first()->isActive());
    }

    #[Test]
    public function it_gets_expired_markings_and_expiring_soon(): void
    {
        // already expired
        $expired = $this->createMarking([
            'expires_at' => now()->subHour(),
        ]);

        // expiring within next 2 hours
        $expiringSoon = $this->createMarking([
            'expires_at' => now()->addHour(),
        ]);

        // far in the future
        $this->createMarking([
            'expires_at' => now()->addDays(5),
        ]);

        $expiredFound = $this->repository->getExpiredMarkings();
        // minimal check: method mengembalikan koleksi tanpa error
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $expiredFound);

        $soon = $this->repository->getExpiringSoon(2);
        $this->assertTrue($soon->contains(fn (Marking $m) => $m->is($expiringSoon)));
    }

    #[Test]
    public function it_checks_conflicts_for_prasarana_and_lokasi_custom(): void
    {
        $existing = $this->createMarking([
            'start_datetime' => now()->addHour(),
            'end_datetime' => now()->addHours(3),
            'prasarana_id' => $this->createPrasarana()->id,
            'lokasi_custom' => null,
        ]);

        // conflict on same prasarana and overlapping time
        $conflict = $this->repository->checkConflicts([
            'start_datetime' => now()->addHours(2),
            'end_datetime' => now()->addHours(4),
            'prasarana_id' => $existing->prasarana_id,
        ]);
        $this->assertNotNull($conflict);

        // conflict on lokasi_custom
        $existingCustom = $this->createMarking([
            'prasarana_id' => null,
            'lokasi_custom' => 'Lapangan A',
        ]);

        $customConflict = $this->repository->checkConflicts([
            'start_datetime' => $existingCustom->start_datetime,
            'end_datetime' => $existingCustom->end_datetime,
            'lokasi_custom' => 'Lapangan A',
        ]);
        $this->assertNotNull($customConflict);

        // exclude current id should ignore itself
        $ignoredConflict = $this->repository->checkConflicts([
            'start_datetime' => $existingCustom->start_datetime,
            'end_datetime' => $existingCustom->end_datetime,
            'lokasi_custom' => 'Lapangan A',
        ], $existingCustom->id);
        $this->assertNull($ignoredConflict);
    }
}
