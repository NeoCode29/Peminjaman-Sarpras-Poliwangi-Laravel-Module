<?php

namespace Modules\SaranaManagement\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Services\KategoriSaranaService;
use Tests\TestCase;

class KategoriSaranaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected KategoriSaranaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var KategoriSaranaService $service */
        $this->service = $this->app->make(KategoriSaranaService::class);
    }

    public function test_get_kategori_and_get_all_kategori_delegate_to_repository(): void
    {
        KategoriSarana::factory()->create(['nama' => 'Elektronik']);
        KategoriSarana::factory()->create(['nama' => 'Olahraga']);

        $paginator = $this->service->getKategori(['search' => 'Elektronik'], 10);
        $this->assertSame(1, $paginator->total());

        $all = $this->service->getAllKategori();
        $this->assertCount(2, $all);
    }

    public function test_create_and_update_kategori_use_transactions(): void
    {
        $kategori = $this->service->createKategori([
            'nama' => 'Elektronik',
            'deskripsi' => 'Peralatan elektronik',
        ]);

        $this->assertDatabaseHas('kategori_saranas', [
            'id' => $kategori->id,
            'nama' => 'Elektronik',
        ]);

        $updated = $this->service->updateKategori($kategori, [
            'nama' => 'Elektronik Kantor',
        ]);

        $this->assertEquals('Elektronik Kantor', $updated->nama);
    }

    public function test_delete_kategori_without_sarana_succeeds(): void
    {
        $kategori = KategoriSarana::factory()->create();

        $this->service->deleteKategori($kategori);

        $this->assertDatabaseMissing('kategori_saranas', [
            'id' => $kategori->id,
        ]);
    }

    public function test_delete_kategori_with_sarana_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);

        $kategori = KategoriSarana::factory()->create();

        Sarana::factory()->create([
            'kategori_id' => $kategori->id,
        ]);

        $this->service->deleteKategori($kategori);
    }
}
