<?php

namespace Modules\PrasaranaManagement\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Repositories\PrasaranaRepository;
use Tests\TestCase;

class PrasaranaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected PrasaranaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PrasaranaRepository();
    }

    public function test_create_and_find_by_id_loads_relations(): void
    {
        $kategori = KategoriPrasarana::factory()->create();

        $created = $this->repository->create([
            'name' => 'Ruang Rapat',
            'kategori_id' => $kategori->id,
            'description' => 'Ruang rapat lantai 2',
            'lokasi' => 'Gedung Utama Lt. 2',
            'kapasitas' => 20,
            'status' => 'tersedia',
        ]);

        $found = $this->repository->findById($created->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->relationLoaded('kategori'));
        $this->assertEquals($kategori->id, $found->kategori_id);
    }

    public function test_update_returns_fresh_instance_with_kategori_relation(): void
    {
        $kategori = KategoriPrasarana::factory()->create();

        $prasarana = Prasarana::factory()->create([
            'kategori_id' => $kategori->id,
            'name' => 'Ruang Lama',
        ]);

        $updated = $this->repository->update($prasarana, [
            'name' => 'Ruang Baru',
        ]);

        $this->assertEquals('Ruang Baru', $updated->name);
        $this->assertTrue($updated->relationLoaded('kategori'));
    }

    public function test_delete_removes_record(): void
    {
        $prasarana = Prasarana::factory()->create();

        $this->assertTrue($this->repository->delete($prasarana));

        $this->assertDatabaseMissing('prasarana', [
            'id' => $prasarana->id,
        ]);
    }

    public function test_get_all_applies_filters_and_pagination(): void
    {
        $kategoriA = KategoriPrasarana::factory()->create(['name' => 'Gedung']);
        $kategoriB = KategoriPrasarana::factory()->create(['name' => 'Lapangan']);

        Prasarana::factory()->create([
            'name' => 'Ruang Rapat Utama',
            'kategori_id' => $kategoriA->id,
            'lokasi' => 'Gedung A',
            'status' => 'tersedia',
        ]);

        Prasarana::factory()->create([
            'name' => 'Lapangan Basket',
            'kategori_id' => $kategoriB->id,
            'lokasi' => 'Area Olahraga',
            'status' => 'maintenance',
        ]);

        $paginator = $this->repository->getAll([
            'search' => 'Rapat',
            'kategori_id' => $kategoriA->id,
            'status' => 'tersedia',
            'lokasi' => 'Gedung',
        ], 10);

        $this->assertSame(1, $paginator->total());
        $this->assertEquals('Ruang Rapat Utama', $paginator->items()[0]->name);
    }
}
