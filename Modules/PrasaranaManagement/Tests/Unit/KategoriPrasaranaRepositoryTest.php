<?php

namespace Modules\PrasaranaManagement\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;
use Modules\PrasaranaManagement\Repositories\KategoriPrasaranaRepository;
use Tests\TestCase;

class KategoriPrasaranaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected KategoriPrasaranaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new KategoriPrasaranaRepository();
    }

    public function test_create_and_find_by_id_loads_prasarana_count(): void
    {
        $created = $this->repository->create([
            'name' => 'Gedung',
            'description' => 'Kategori gedung',
        ]);

        $found = $this->repository->findById($created->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->relationLoaded('prasarana') === false); // only count loaded
        $this->assertNotNull($found->prasarana_count);
    }

    public function test_update_returns_fresh_instance(): void
    {
        $kategori = KategoriPrasarana::factory()->create([
            'name' => 'Lama',
        ]);

        $updated = $this->repository->update($kategori, [
            'name' => 'Baru',
        ]);

        $this->assertEquals('Baru', $updated->name);
    }

    public function test_delete_removes_record(): void
    {
        $kategori = KategoriPrasarana::factory()->create();

        $this->assertTrue($this->repository->delete($kategori));

        $this->assertDatabaseMissing('kategori_prasarana', [
            'id' => $kategori->id,
        ]);
    }

    public function test_get_all_applies_search_filter_and_pagination(): void
    {
        KategoriPrasarana::factory()->create([
            'name' => 'Gedung Utama',
            'description' => 'Gedung pusat kegiatan',
        ]);

        KategoriPrasarana::factory()->create([
            'name' => 'Lapangan',
            'description' => 'Area olahraga',
        ]);

        $paginator = $this->repository->getAll([
            'search' => 'Gedung',
        ], 10);

        $this->assertSame(1, $paginator->total());
        $this->assertEquals('Gedung Utama', $paginator->items()[0]->name);
    }

    public function test_get_all_without_pagination_returns_ordered_collection(): void
    {
        KategoriPrasarana::factory()->create(['name' => 'B']);
        KategoriPrasarana::factory()->create(['name' => 'A']);

        $all = $this->repository->getAllWithoutPagination();

        $this->assertCount(2, $all);
        $this->assertEquals(['A', 'B'], $all->pluck('name')->all());
    }
}
