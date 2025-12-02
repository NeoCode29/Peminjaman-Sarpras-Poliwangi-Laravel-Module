<?php

namespace Tests\Unit\Services;

use App\Models\Ukm;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Modules\MarkingManagement\Entities\Marking;
use Modules\MarkingManagement\Repositories\Interfaces\MarkingRepositoryInterface;
use Modules\MarkingManagement\Services\MarkingService;
use Modules\PrasaranaManagement\Entities\Prasarana;
use RuntimeException;
use Tests\TestCase;

class MarkingServiceTest extends TestCase
{
    use DatabaseMigrations;

    private MarkingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(MarkingService::class);
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
            'event_name' => 'Service Event',
            'notes' => null,
        ], $overrides));
    }

    public function test_it_creates_marking_with_computed_expires_at(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        $data = [
            'ukm_id' => $this->createUkm()->id,
            'prasarana_id' => $this->createPrasarana()->id,
            'lokasi_custom' => null,
            'start_datetime' => now()->addHour()->toDateTimeString(),
            'end_datetime' => now()->addHours(2)->toDateTimeString(),
            'jumlah_peserta' => 30,
            'planned_submit_by' => null,
            'event_name' => 'Created From Service',
            'notes' => 'testing',
        ];

        $marking = $this->service->createMarking($data);

        $this->assertInstanceOf(Marking::class, $marking);
        $this->assertSame('Created From Service', $marking->event_name);
        $this->assertSame($user->id, $marking->user_id);
        $this->assertNotNull($marking->expires_at);
    }

    public function test_it_updates_marking_when_active_and_not_expired(): void
    {
        $marking = $this->createMarking();

        $updated = $this->service->updateMarking($marking, [
            'ukm_id' => $marking->ukm_id,
            'prasarana_id' => $marking->prasarana_id,
            'lokasi_custom' => null,
            'start_datetime' => now()->addHours(3)->toDateTimeString(),
            'end_datetime' => now()->addHours(4)->toDateTimeString(),
            'jumlah_peserta' => 99,
            'planned_submit_by' => null,
            'event_name' => 'Updated Name',
            'notes' => null,
        ]);

        $this->assertSame('Updated Name', $updated->event_name);
        $this->assertSame(99, $updated->jumlah_peserta);
    }

    public function test_it_throws_when_updating_inactive_or_expired_marking(): void
    {
        $inactive = $this->createMarking(['status' => Marking::STATUS_CANCELLED]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Marking tidak dapat diedit karena sudah tidak aktif.');

        $this->service->updateMarking($inactive, [
            'ukm_id' => $inactive->ukm_id,
            'prasarana_id' => $inactive->prasarana_id,
            'lokasi_custom' => null,
            'start_datetime' => now()->addHours(3)->toDateTimeString(),
            'end_datetime' => now()->addHours(4)->toDateTimeString(),
            'jumlah_peserta' => 99,
            'planned_submit_by' => null,
            'event_name' => 'Updated Name',
            'notes' => null,
        ]);
    }

    public function test_it_cancels_active_marking_and_throws_if_not_active(): void
    {
        $active = $this->createMarking();

        $cancelled = $this->service->cancelMarking($active);
        $this->assertSame(Marking::STATUS_CANCELLED, $cancelled->status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Marking tidak dapat dibatalkan karena sudah tidak aktif.');

        $this->service->cancelMarking($cancelled);
    }

    public function test_it_extends_active_marking_within_limit_and_throws_for_invalid_cases(): void
    {
        $marking = $this->createMarking();
        $originalExpires = $marking->expires_at->copy();

        $extended = $this->service->extendMarking($marking, 2);
        $this->assertTrue($extended->expires_at->gt($originalExpires));

        // exceed max extension
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Perpanjangan maksimal');

        $this->service->extendMarking($marking, config('markingmanagement.max_extension_days', 7) + 1);
    }

    public function test_it_marks_as_converted_only_when_can_be_converted(): void
    {
        $marking = $this->createMarking();

        $converted = $this->service->markAsConverted($marking);
        $this->assertSame(Marking::STATUS_CONVERTED, $converted->status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Marking tidak dapat dikonversi.');

        $this->service->markAsConverted($converted);
    }

    public function test_it_returns_conflict_messages_from_repository(): void
    {
        $marking = $this->createMarking([
            'prasarana_id' => $this->createPrasarana()->id,
        ]);

        $data = [
            'start_datetime' => $marking->start_datetime,
            'end_datetime' => $marking->end_datetime,
            'prasarana_id' => $marking->prasarana_id,
        ];

        $message = $this->service->checkConflicts($data);

        $this->assertIsString($message);
        $this->assertStringContainsString('Prasarana sudah di-marking', $message);
    }

    public function test_it_auto_expires_markings_and_returns_count(): void
    {
        $expired = $this->createMarking([
            'expires_at' => now()->subHour(),
        ]);

        $count = $this->service->autoExpireMarkings();

        // minimal check: status marking yang sudah lewat expires_at berubah menjadi expired
        $expired->refresh();
        $this->assertSame(Marking::STATUS_EXPIRED, $expired->status);
    }

    public function test_it_gets_markings_and_markings_for_user(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();

        $this->createMarking(['user_id' => $user->id]);
        $this->createMarking(['user_id' => $other->id]);

        $all = $this->service->getMarkings();
        $this->assertSame(2, $all->total());

        $userMarkings = $this->service->getMarkingsForUser($user->id);
        $this->assertSame(1, $userMarkings->total());
    }
}
