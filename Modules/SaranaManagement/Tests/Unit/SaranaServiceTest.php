<?php

namespace Modules\SaranaManagement\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaUnit;
use Modules\SaranaManagement\Services\SaranaService;
use Tests\TestCase;

class SaranaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SaranaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var SaranaService $service */
        $this->service = $this->app->make(SaranaService::class);
    }

    public function test_create_sarana_generates_kode_when_missing_and_uploads_foto(): void
    {
        Storage::fake('public');

        $kategori = KategoriSarana::factory()->create();
        $file = UploadedFile::fake()->image('laptop.jpg');

        $sarana = $this->service->createSarana([
            'nama' => 'Laptop',
            'kategori_id' => $kategori->id,
            'kondisi' => 'baik',
            'status_ketersediaan' => 'tersedia',
            'jumlah_total' => 5,
            'foto' => $file,
        ]);

        $this->assertNotNull($sarana->kode_sarana);
        $this->assertStringStartsWith('SRN-', $sarana->kode_sarana);
        $this->assertNotNull($sarana->foto);
        Storage::disk('public')->assertExists($sarana->foto);
    }

    public function test_update_sarana_handles_foto_replacement_and_delete_flag(): void
    {
        Storage::fake('public');

        $kategori = KategoriSarana::factory()->create();
        $oldFile = UploadedFile::fake()->image('old.jpg');
        $oldPath = $oldFile->store('saranas', 'public');

        $sarana = Sarana::factory()->create([
            'kategori_id' => $kategori->id,
            'foto' => $oldPath,
        ]);

        $newFile = UploadedFile::fake()->image('new.jpg');

        $updated = $this->service->updateSarana($sarana, [
            'nama' => 'Laptop Baru',
            'kategori_id' => $kategori->id,
            'kondisi' => 'baik',
            'status_ketersediaan' => 'tersedia',
            'jumlah_total' => 5,
            'foto' => $newFile,
            'hapus_foto' => false,
        ]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($updated->foto);

        // Sekarang tes hapus foto tanpa upload baru
        $updated2 = $this->service->updateSarana($updated, [
            'nama' => 'Laptop Tanpa Foto',
            'kategori_id' => $kategori->id,
            'kondisi' => 'baik',
            'status_ketersediaan' => 'tersedia',
            'jumlah_total' => 5,
            'hapus_foto' => true,
        ]);

        $this->assertNull($updated2->foto);
    }

    public function test_delete_sarana_removes_record_and_foto_if_exists(): void
    {
        Storage::fake('public');

        $kategori = KategoriSarana::factory()->create();
        $file = UploadedFile::fake()->image('laptop.jpg');
        $path = $file->store('saranas', 'public');

        $sarana = Sarana::factory()->create([
            'kategori_id' => $kategori->id,
            'foto' => $path,
        ]);

        Storage::disk('public')->assertExists($path);

        $this->service->deleteSarana($sarana);

        $this->assertDatabaseMissing('saranas', ['id' => $sarana->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_get_saranas_and_filters_and_finders_delegate_to_repository(): void
    {
        $kategori = KategoriSarana::factory()->create();

        $sarana = Sarana::factory()->create([
            'kategori_id' => $kategori->id,
            'kode_sarana' => 'SRN-1234',
            'nama' => 'Laptop Test',
        ]);

        $paginator = $this->service->getSaranas(['search' => 'Laptop'], 10);
        $this->assertSame(1, $paginator->total());

        $byId = $this->service->getSaranaById($sarana->id);
        $this->assertNotNull($byId);

        $byKode = $this->service->findSaranaByKode('SRN-1234');
        $this->assertNotNull($byKode);

        $byKategori = $this->service->getSaranasByKategori($kategori->id, 10);
        $this->assertSame(1, $byKategori->total());
    }

    public function test_unit_operations_update_sarana_stats(): void
    {
        $kategori = KategoriSarana::factory()->create();

        /** @var Sarana $sarana */
        $sarana = Sarana::factory()->create([
            'kategori_id' => $kategori->id,
            'type' => 'serialized',
            'jumlah_total' => 0,
            'jumlah_tersedia' => 0,
        ]);

        // addUnit
        $unit = $this->service->addUnit($sarana, 'UNIT-001', 'tersedia');
        $sarana->refresh();
        $this->assertInstanceOf(SaranaUnit::class, $unit);
        $this->assertEquals(1, $sarana->units()->count());

        // updateUnit
        $this->service->updateUnit($unit, ['unit_status' => 'rusak']);
        $sarana->refresh();
        $this->assertEquals('rusak', $unit->fresh()->unit_status);

        // addBulkUnits
        $this->service->addBulkUnits($sarana, ['UNIT-002', 'UNIT-003'], 'tersedia');
        $sarana->refresh();
        $this->assertTrue($sarana->units()->where('unit_code', 'UNIT-002')->exists());

        // updateBulkUnitStatus
        $unitIds = $sarana->units()->pluck('id')->all();
        $updatedCount = $this->service->updateBulkUnitStatus($sarana, $unitIds, 'maintenance');
        $this->assertGreaterThanOrEqual(1, $updatedCount);

        // deleteUnit
        $firstUnit = $sarana->units()->first();
        $this->service->deleteUnit($firstUnit);
        $this->assertDatabaseMissing('sarana_units', ['id' => $firstUnit->id]);
    }
}
