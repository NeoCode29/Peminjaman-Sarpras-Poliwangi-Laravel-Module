<?php

namespace Modules\PeminjamanManagement\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Entities\PeminjamanItem;
use Modules\PeminjamanManagement\Services\SlotConflictService;
use Modules\SaranaManagement\Entities\Sarana;
use Tests\TestCase;

class SlotConflictServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_check_sarana_conflict_fails_when_requested_qty_exceeds_available(): void
    {
        /** @var Sarana $sarana */
        $sarana = Sarana::factory()->create([
            'type' => 'pooled',
            'jumlah_total' => 0,
            'jumlah_tersedia' => 0,
        ]);

        $baseStart = now()->format('Y-m-d');
        $baseEnd = now()->format('Y-m-d');

        /** @var SlotConflictService $service */
        $service = $this->app->make(SlotConflictService::class);

        $conflictMessage = $service->checkSaranaConflict(
            $sarana->id,
            5,
            $baseStart,
            $baseEnd,
            null
        );

        $this->assertIsString($conflictMessage);
        $this->assertStringContainsString($sarana->nama, $conflictMessage);
    }

    public function test_check_sarana_conflict_passes_when_requested_qty_within_available(): void
    {
        /** @var Sarana $sarana */
        $sarana = Sarana::factory()->create([
            'type' => 'pooled',
            'jumlah_total' => 10,
            'jumlah_tersedia' => 10,
        ]);

        $baseStart = now()->format('Y-m-d');
        $baseEnd = now()->format('Y-m-d');

        /** @var SlotConflictService $service */
        $service = $this->app->make(SlotConflictService::class);

        $conflictMessage = $service->checkSaranaConflict(
            $sarana->id,
            5,
            $baseStart,
            $baseEnd,
            null
        );

        $this->assertNull($conflictMessage);
    }
}
