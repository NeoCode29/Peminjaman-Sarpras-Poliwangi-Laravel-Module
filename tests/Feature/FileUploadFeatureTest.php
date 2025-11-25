<?php

namespace Tests\Feature;

use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadFeatureTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_authenticated_user_can_download_own_private_file(): void
    {
        $user = User::factory()->create();

        Storage::disk('local')->put('documents/test.pdf', 'dummy');

        $file = UploadedFile::create([
            'user_id' => $user->id,
            'uploadable_type' => null,
            'uploadable_id' => null,
            'file_type' => 'document',
            'category' => 'documents',
            'original_name' => 'test.pdf',
            'stored_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'disk' => 'local',
            'is_public' => false,
        ]);

        $signature = hash_hmac('sha256', $file->id . now()->addMinutes(60)->timestamp, config('app.key'));
        $expires = now()->addMinutes(60)->timestamp;

        $response = $this->actingAs($user)->get(route('file.download', [
            'file' => $file->id,
            'signature' => $signature,
            'expires' => $expires,
        ]));

        $response->assertOk();
    }

    public function test_blocked_user_cannot_access_file_download(): void
    {
        $user = User::factory()->create([
            'status' => 'blocked',
        ]);

        Storage::disk('local')->put('documents/test.pdf', 'dummy');

        $file = UploadedFile::create([
            'user_id' => $user->id,
            'uploadable_type' => null,
            'uploadable_id' => null,
            'file_type' => 'document',
            'category' => 'documents',
            'original_name' => 'test.pdf',
            'stored_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'disk' => 'local',
            'is_public' => false,
        ]);

        $signature = hash_hmac('sha256', $file->id . now()->addMinutes(60)->timestamp, config('app.key'));
        $expires = now()->addMinutes(60)->timestamp;

        $response = $this->actingAs($user)->get(route('file.download', [
            'file' => $file->id,
            'signature' => $signature,
            'expires' => $expires,
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'status' => 'Akun Anda diblokir. Silakan hubungi administrator.',
        ]);
    }
}
