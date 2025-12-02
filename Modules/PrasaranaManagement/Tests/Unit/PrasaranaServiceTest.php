<?php

namespace Modules\PrasaranaManagement\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Entities\PrasaranaImage;
use Modules\PrasaranaManagement\Services\PrasaranaService;
use Tests\TestCase;

class PrasaranaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PrasaranaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var PrasaranaService $service */
        $this->service = $this->app->make(PrasaranaService::class);
    }

    public function test_create_prasarana_stores_images_when_provided(): void
    {
        Storage::fake('public');

        $kategori = KategoriPrasarana::factory()->create();
        $creator = User::factory()->create();
        $file1 = UploadedFile::fake()->image('ruang1.jpg');
        $file2 = UploadedFile::fake()->image('ruang2.jpg');

        $prasarana = $this->service->create([
            'name' => 'Ruang Rapat',
            'kategori_id' => $kategori->id,
            'description' => 'Ruang rapat utama',
            'lokasi' => 'Gedung A',
            'kapasitas' => 30,
            'status' => 'tersedia',
        ], $creator->id, [$file1, $file2]);

        $this->assertCount(2, $prasarana->images);
        foreach ($prasarana->images as $image) {
            Storage::disk('public')->assertExists($image->image_url);
        }
    }

    public function test_update_prasarana_can_add_new_images_and_remove_selected(): void
    {
        Storage::fake('public');

        $kategori = KategoriPrasarana::factory()->create();
        $creator = User::factory()->create();
        $initialFiles = [
            UploadedFile::fake()->image('old1.jpg'),
            UploadedFile::fake()->image('old2.jpg'),
        ];

        $prasarana = $this->service->create([
            'name' => 'Ruang Serbaguna',
            'kategori_id' => $kategori->id,
            'description' => 'Ruang besar',
            'lokasi' => 'Gedung B',
            'kapasitas' => 100,
            'status' => 'tersedia',
        ], $creator->id, $initialFiles);

        $this->assertCount(2, $prasarana->images);

        $imageToRemove = $prasarana->images->first();

        $newFile = UploadedFile::fake()->image('new.jpg');

        $updated = $this->service->update($prasarana, [
            'name' => 'Ruang Serbaguna Update',
            'kategori_id' => $kategori->id,
            'description' => 'Ruang besar update',
            'lokasi' => 'Gedung B',
            'kapasitas' => 120,
            'status' => 'tersedia',
        ], [$newFile], [$imageToRemove->id]);

        $this->assertEquals('Ruang Serbaguna Update', $updated->name);
        $this->assertCount(2, $updated->images);
        $this->assertDatabaseMissing('prasarana_images', ['id' => $imageToRemove->id]);
    }

    public function test_delete_prasarana_removes_record_and_images(): void
    {
        Storage::fake('public');

        $kategori = KategoriPrasarana::factory()->create();
        $creator = User::factory()->create();
        $file = UploadedFile::fake()->image('ruang.jpg');

        $prasarana = $this->service->create([
            'name' => 'Ruang Lab',
            'kategori_id' => $kategori->id,
            'description' => 'Lab komputer',
            'lokasi' => 'Gedung C',
            'kapasitas' => 40,
            'status' => 'tersedia',
        ], $creator->id, [$file]);

        $imagePath = $prasarana->images->first()->image_url;
        Storage::disk('public')->assertExists($imagePath);

        $this->service->delete($prasarana);

        $this->assertDatabaseMissing('prasarana', ['id' => $prasarana->id]);
        $this->assertDatabaseMissing('prasarana_images', ['prasarana_id' => $prasarana->id]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_list_and_get_by_id_delegate_to_repository(): void
    {
        $kategori = KategoriPrasarana::factory()->create();

        $prasarana = Prasarana::factory()->create([
            'kategori_id' => $kategori->id,
            'name' => 'Ruang Test',
            'status' => 'tersedia',
        ]);

        $paginator = $this->service->list(['search' => 'Ruang'], 10);
        $this->assertSame(1, $paginator->total());

        $byId = $this->service->getById($prasarana->id);
        $this->assertNotNull($byId);
    }
}
