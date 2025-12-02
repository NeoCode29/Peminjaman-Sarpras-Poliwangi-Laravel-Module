<?php

namespace Modules\PrasaranaManagement\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;
use Modules\PrasaranaManagement\Services\KategoriPrasaranaService;
use Tests\TestCase;

class KategoriPrasaranaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected KategoriPrasaranaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var KategoriPrasaranaService $service */
        $this->service = $this->app->make(KategoriPrasaranaService::class);
    }

    public function test_create_update_delete_kategori_prasarana(): void
    {
        // create
        $kategori = $this->service->createKategori([
            'name' => 'Gedung',
            'description' => 'Kategori gedung',
        ]);

        $this->assertDatabaseHas('kategori_prasarana', ['id' => $kategori->id]);

        // update
        $updated = $this->service->updateKategori($kategori, [
            'name' => 'Gedung Utama',
        ]);

        $this->assertEquals('Gedung Utama', $updated->name);

        // delete (tanpa prasarana terkait)
        $this->service->deleteKategori($updated);
        $this->assertDatabaseMissing('kategori_prasarana', ['id' => $updated->id]);
    }

    public function test_delete_kategori_with_prasarana_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);

        $kategori = KategoriPrasarana::factory()->create();
        $kategori->prasarana()->create([
            'name' => 'Ruang A',
            'description' => 'Ruang kecil',
            'status' => 'tersedia',
        ]);

        $this->service->deleteKategori($kategori);
    }

    public function test_getters_delegate_to_repository(): void
    {
        $kategori = KategoriPrasarana::factory()->create(['name' => 'Kategori X']);

        $list = $this->service->getKategori(['search' => 'Kategori'], 10);
        $this->assertSame(1, $list->total());

        $single = $this->service->getKategoriById($kategori->id);
        $this->assertNotNull($single);

        $all = $this->service->getAllKategori();
        $this->assertCount(1, $all);
    }
}
