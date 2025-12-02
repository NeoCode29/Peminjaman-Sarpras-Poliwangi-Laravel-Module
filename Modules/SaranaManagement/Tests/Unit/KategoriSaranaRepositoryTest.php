<?php

namespace Modules\SaranaManagement\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Repositories\KategoriSaranaRepository;
use Tests\TestCase;

class KategoriSaranaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected KategoriSaranaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new KategoriSaranaRepository();
    }

    public function test_create_and_find_by_id_loads_saranas_count(): void
    {
        $kategori = $this->repository->create([
            'nama' => 'Elektronik',
            'deskripsi' => 'Peralatan elektronik',
        ]);

        Sarana::factory()->count(2)->create(['kategori_id' => $kategori->id]);

        $found = $this->repository->findById($kategori->id);

        $this->assertNotNull($found);
        $this->assertTrue(isset($found->saranas_count));
        $this->assertEquals(2, $found->saranas_count);
    }

    public function test_update_returns_fresh_instance(): void
    {
        $kategori = KategoriSarana::factory()->create([
            'nama' => 'Elektronik',
        ]);

        $updated = $this->repository->update($kategori, [
            'nama' => 'Elektronik Kantor',
        ]);

        $this->assertEquals('Elektronik Kantor', $updated->nama);
    }

    public function test_delete_removes_record(): void
    {
        $kategori = KategoriSarana::factory()->create();

        $this->assertTrue($this->repository->delete($kategori));

        $this->assertDatabaseMissing('kategori_saranas', [
            'id' => $kategori->id,
        ]);
    }

    public function test_get_all_applies_search_filter_and_pagination(): void
    {
        KategoriSarana::factory()->create([
            'nama' => 'Elektronik',
        ]);

        KategoriSarana::factory()->create([
            'nama' => 'Olahraga',
        ]);

        $paginator = $this->repository->getAll(['search' => 'Elektronik'], 10);

        $this->assertSame(1, $paginator->total());
        $this->assertEquals('Elektronik', $paginator->items()[0]->nama);
    }

    public function test_get_all_without_pagination_returns_all_sorted_by_name(): void
    {
        KategoriSarana::factory()->create(['nama' => 'Zeta']);
        KategoriSarana::factory()->create(['nama' => 'Alpha']);

        $all = $this->repository->getAllWithoutPagination();

        $this->assertCount(2, $all);
        $this->assertEquals('Alpha', $all->first()->nama);
    }
}
