<?php

namespace Modules\SaranaManagement\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Repositories\SaranaRepository;
use Tests\TestCase;

class SaranaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected SaranaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new SaranaRepository();
    }

    public function test_create_and_find_by_id_loads_relations(): void
    {
        $kategori = KategoriSarana::factory()->create();

        $created = $this->repository->create([
            'kode_sarana' => 'SRN-0001',
            'nama' => 'Laptop',
            'kategori_id' => $kategori->id,
            'kondisi' => 'baik',
            'status_ketersediaan' => 'tersedia',
            'jumlah_total' => 5,
            'jumlah_tersedia' => 5,
            'type' => 'pooled',
        ]);

        $found = $this->repository->findById($created->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->relationLoaded('kategori'));
        $this->assertEquals($kategori->id, $found->kategori_id);
    }

    public function test_update_returns_fresh_instance_with_kategori_relation(): void
    {
        $kategori = KategoriSarana::factory()->create();

        $sarana = Sarana::factory()->create([
            'kategori_id' => $kategori->id,
            'nama' => 'Laptop',
        ]);

        $updated = $this->repository->update($sarana, [
            'nama' => 'Laptop Baru',
        ]);

        $this->assertEquals('Laptop Baru', $updated->nama);
        $this->assertTrue($updated->relationLoaded('kategori'));
    }

    public function test_delete_removes_record(): void
    {
        $sarana = Sarana::factory()->create();

        $this->assertTrue($this->repository->delete($sarana));

        $this->assertDatabaseMissing('saranas', [
            'id' => $sarana->id,
        ]);
    }

    public function test_get_all_applies_filters_and_pagination(): void
    {
        $kategoriA = KategoriSarana::factory()->create(['nama' => 'Elektronik']);
        $kategoriB = KategoriSarana::factory()->create(['nama' => 'Furniture']);

        Sarana::factory()->create([
            'kode_sarana' => 'SRN-0001',
            'nama' => 'Laptop Asus',
            'kategori_id' => $kategoriA->id,
            'kondisi' => 'baik',
            'status_ketersediaan' => 'tersedia',
        ]);

        Sarana::factory()->create([
            'kode_sarana' => 'SRN-0002',
            'nama' => 'Kursi Kantor',
            'kategori_id' => $kategoriB->id,
            'kondisi' => 'rusak_ringan',
            'status_ketersediaan' => 'tidak_tersedia',
        ]);

        $paginator = $this->repository->getAll([
            'search' => 'Laptop',
            'kategori_id' => $kategoriA->id,
            'kondisi' => 'baik',
            'status_ketersediaan' => 'tersedia',
        ], 10);

        $this->assertSame(1, $paginator->total());
        $this->assertEquals('Laptop Asus', $paginator->items()[0]->nama);
    }

    public function test_find_by_kode_loads_kategori(): void
    {
        $kategori = KategoriSarana::factory()->create();

        $sarana = Sarana::factory()->create([
            'kode_sarana' => 'SRN-9999',
            'kategori_id' => $kategori->id,
        ]);

        $found = $this->repository->findByKode('SRN-9999');

        $this->assertNotNull($found);
        $this->assertTrue($found->relationLoaded('kategori'));
        $this->assertEquals($sarana->id, $found->id);
    }

    public function test_get_by_kategori_returns_only_that_category(): void
    {
        $kategoriA = KategoriSarana::factory()->create();
        $kategoriB = KategoriSarana::factory()->create();

        Sarana::factory()->count(2)->create(['kategori_id' => $kategoriA->id]);
        Sarana::factory()->count(3)->create(['kategori_id' => $kategoriB->id]);

        $paginator = $this->repository->getByKategori($kategoriA->id, 10);

        $this->assertSame(2, $paginator->total());
        foreach ($paginator->items() as $item) {
            $this->assertEquals($kategoriA->id, $item->kategori_id);
        }
    }
}
