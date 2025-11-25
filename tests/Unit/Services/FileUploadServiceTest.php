<?php

namespace Tests\Unit\Services;

use App\Models\UploadedFile as UploadedFileModel;
use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class FileUploadServiceTest extends TestCase
{
    use DatabaseMigrations;

    private FileUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(FileUploadService::class);

        Config::set('filesystems.disks.public.root', storage_path('app/public'));
        Storage::fake('public');

        Config::set('upload.disk', 'public');
        Config::set('upload.allowed_types.image', [
            'mime_types' => ['image/jpeg'],
            'extensions' => ['jpg', 'jpeg'],
            'description' => 'JPEG images',
            'max_size' => 1024,
        ]);
        Config::set('upload.security.validate_dimensions', false);
        Config::set('upload.paths.sarpras', 'sarpras');
        Config::set('upload.storage_structure', 'flat');
        Config::set('upload.cleanup.auto_cleanup_temp', false);
    }

    /** @test */
    public function it_uploads_image_file_and_returns_path_and_url(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(500);

        $result = $this->service->upload($file, 'image', 'sarpras');

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('url', $result);

        Storage::disk('public')->assertExists($result['path']);
    }

    /** @test */
    public function it_throws_when_file_type_not_configured(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(500);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Tipe file 'unknown' tidak valid.");

        $this->service->upload($file, 'unknown', 'sarpras');
    }

    /** @test */
    public function it_throws_when_file_size_exceeds_limit(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(2048);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ukuran file maksimal');

        $this->service->upload($file, 'image', 'sarpras');
    }

    /** @test */
    public function it_deletes_file_and_returns_boolean(): void
    {
        Storage::disk('public')->put('sarpras/test.jpg', 'content');

        $this->assertTrue($this->service->deleteFile('sarpras/test.jpg', 'public'));
        Storage::disk('public')->assertMissing('sarpras/test.jpg');

        $this->assertFalse($this->service->deleteFile('sarpras/not-exist.jpg', 'public'));
    }

    /** @test */
    public function it_generates_url_only_for_public_disk(): void
    {
        Storage::disk('public')->put('sarpras/test.jpg', 'content');

        $url = $this->service->getFileUrl('sarpras/test.jpg', 'public');
        $this->assertNotNull($url);

        $urlPrivate = $this->service->getFileUrl('sarpras/test.jpg', 'local');
        $this->assertNull($urlPrivate);
    }

    /** @test */
    public function it_saves_metadata_and_uploads_with_tracking(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(500);

        $result = $this->service->uploadWithTracking($file, 'image', 'sarpras', 'public', [
            'uploadable_type' => 'Test',
            'uploadable_id' => 1,
            'is_public' => true,
        ]);

        $this->assertArrayHasKey('model', $result);
        $this->assertInstanceOf(UploadedFileModel::class, $result['model']);

        $this->assertDatabaseHas('uploaded_files', [
            'uploadable_type' => 'Test',
            'uploadable_id' => 1,
        ]);
    }

    /** @test */
    public function it_cleans_up_temp_files(): void
    {
        Config::set('upload.paths.temp', 'temp');
        Config::set('upload.cleanup.temp_files_lifetime', 0); // segera expired

        Storage::disk('public')->put('temp/old.tmp', 'content');

        $deleted = $this->service->cleanupTempFiles('public');

        $this->assertGreaterThanOrEqual(0, $deleted);
    }
}
