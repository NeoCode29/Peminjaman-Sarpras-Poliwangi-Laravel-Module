<?php

namespace App\Services;

use App\Models\UploadedFile as UploadedFileModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class FileUploadService
{
    /**
     * Upload file dengan validasi dan optimasi
     *
     * @param  UploadedFile  $file  File yang akan diupload
     * @param  string  $type  Tipe file: 'image', 'document', 'identity', 'avatar'
     * @param  string  $category  Kategori/folder: 'sarpras', 'documents', 'avatars', etc.
     * @param  string|null  $disk  Storage disk (null = gunakan config default)
     * @param  array  $options  Options: ['optimize' => bool, 'thumbnail' => bool, 'old_file' => string]
     * @return array ['path' => string, 'url' => string|null, 'filename' => string, 'thumbnail' => string|null]
     */
    public function upload(
        UploadedFile $file,
        string $type,
        string $category,
        ?string $disk = null,
        array $options = []
    ): array {
        // Validasi tipe file
        $this->validateFileType($file, $type);

        // Validasi ukuran file
        $this->validateFileSize($file, $type);

        // Validasi image dimensions jika gambar
        if ($this->isImage($type)) {
            $this->validateImageDimensions($file);
        }

        // Tentukan disk
        $disk = $disk ?? config('upload.disk', 'local');

        // Generate nama file
        $filename = $this->generateFilename($file);

        // Tentukan path penyimpanan
        $path = $this->buildStoragePath($category, $filename);

        // Simpan file
        $storedPath = Storage::disk($disk)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        if (! $storedPath) {
            throw new RuntimeException('Gagal menyimpan file ke storage.');
        }

        // Log upload
        $this->logUpload($file, $type, $category, $storedPath, $disk);

        // Prepare result
        $result = [
            'path' => $storedPath,
            'filename' => $filename,
            'url' => $this->getFileUrl($storedPath, $disk),
            'thumbnail' => null,
        ];

        // Optimasi gambar jika dibutuhkan
        if ($this->isImage($type) && ($options['optimize'] ?? true)) {
            $this->optimizeImage($disk, $storedPath);
        }

        // Generate thumbnail jika dibutuhkan
        if ($this->isImage($type) && ($options['thumbnail'] ?? false)) {
            $result['thumbnail'] = $this->generateThumbnail($disk, $storedPath);
        }

        // Hapus file lama jika ada
        if (! empty($options['old_file'])) {
            $this->deleteFile($options['old_file'], $disk);
        }

        // Auto cleanup temp files
        if (config('upload.cleanup.auto_cleanup_temp', true)) {
            $this->cleanupTempFiles($disk);
        }

        return $result;
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(
        array $files,
        string $type,
        string $category,
        ?string $disk = null,
        array $options = []
    ): array {
        $results = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $results[] = $this->upload($file, $type, $category, $disk, $options);
            }
        }

        return $results;
    }

    /**
     * Delete file dari storage
     */
    public function deleteFile(string $path, ?string $disk = null): bool
    {
        $disk = $disk ?? config('upload.disk', 'local');

        if (Storage::disk($disk)->exists($path)) {
            $deleted = Storage::disk($disk)->delete($path);

            if ($deleted && config('upload.logging.log_deletions', true)) {
                Log::channel(config('upload.logging.channel', 'daily'))->info('File deleted', [
                    'path' => $path,
                    'disk' => $disk,
                    'deleted_by' => auth()->id(),
                ]);
            }

            return $deleted;
        }

        return false;
    }

    /**
     * Delete multiple files
     */
    public function deleteMultiple(array $paths, ?string $disk = null): int
    {
        $deleted = 0;

        foreach ($paths as $path) {
            if ($this->deleteFile($path, $disk)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get file URL (untuk file public)
     */
    public function getFileUrl(string $path, ?string $disk = null): ?string
    {
        $disk = $disk ?? config('upload.disk', 'local');

        // Hanya return URL untuk public disk
        if ($disk === 'public') {
            return Storage::disk($disk)->url($path);
        }

        // Untuk private disk, return null (harus via controller dengan authorization)
        return null;
    }

    /**
     * Generate temporary signed URL (untuk private files)
     */
    public function getTemporaryUrl(string $path, int $expiresInMinutes = 60, ?string $disk = null): string
    {
        $disk = $disk ?? config('upload.disk', 'local');

        if (! Storage::disk($disk)->exists($path)) {
            throw new InvalidArgumentException('File tidak ditemukan.');
        }

        // Untuk disk yang support temporary URLs (s3, etc.)
        if (method_exists(Storage::disk($disk), 'temporaryUrl')) {
            return Storage::disk($disk)->temporaryUrl(
                $path,
                now()->addMinutes($expiresInMinutes)
            );
        }

        // Untuk local disk, generate signed route
        return route('file.download', [
            'path' => encrypt($path),
            'disk' => $disk,
            'expires' => now()->addMinutes($expiresInMinutes)->timestamp,
        ]);
    }

    /**
     * Cleanup file temporary yang sudah kadaluarsa
     */
    public function cleanupTempFiles(?string $disk = null): int
    {
        $disk = $disk ?? config('upload.disk', 'local');
        $tempPath = config('upload.paths.temp', 'temp');
        $lifetime = config('upload.cleanup.temp_files_lifetime', 24); // hours

        $files = Storage::disk($disk)->files($tempPath);
        $deleted = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk($disk)->lastModified($file);

            if (now()->timestamp - $lastModified > ($lifetime * 3600)) {
                if (Storage::disk($disk)->delete($file)) {
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            Log::channel(config('upload.logging.channel', 'daily'))->info('Temp files cleaned up', [
                'count' => $deleted,
                'disk' => $disk,
            ]);
        }

        return $deleted;
    }

    /**
     * Validasi tipe file
     */
    protected function validateFileType(UploadedFile $file, string $type): void
    {
        $allowedTypes = config("upload.allowed_types.{$type}");

        if (! $allowedTypes) {
            throw new InvalidArgumentException("Tipe file '{$type}' tidak valid.");
        }

        // Validasi MIME type (lebih aman daripada extension)
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, $allowedTypes['mime_types'])) {
            throw new InvalidArgumentException(
                "Tipe file tidak diizinkan. Hanya menerima: {$allowedTypes['description']}"
            );
        }

        // Validasi extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $allowedTypes['extensions'])) {
            throw new InvalidArgumentException(
                "Ekstensi file tidak diizinkan. Hanya menerima: ".implode(', ', $allowedTypes['extensions'])
            );
        }
    }

    /**
     * Validasi ukuran file
     */
    protected function validateFileSize(UploadedFile $file, string $type): void
    {
        $maxSize = config("upload.allowed_types.{$type}.max_size"); // KB
        $fileSize = $file->getSize() / 1024; // Convert to KB

        if ($fileSize > $maxSize) {
            $maxSizeMB = round($maxSize / 1024, 2);
            throw new InvalidArgumentException("Ukuran file maksimal {$maxSizeMB}MB.");
        }
    }

    /**
     * Validasi image dimensions (prevent decompression bomb)
     */
    protected function validateImageDimensions(UploadedFile $file): void
    {
        if (! config('upload.security.validate_dimensions', true)) {
            return;
        }

        $imageSize = @getimagesize($file->getRealPath());

        if (! $imageSize) {
            throw new InvalidArgumentException('File gambar tidak valid.');
        }

        [$width, $height] = $imageSize;
        $pixelCount = $width * $height;
        $maxPixels = config('upload.security.max_pixel_count', 25000000);

        if ($pixelCount > $maxPixels) {
            throw new InvalidArgumentException(
                'Dimensi gambar terlalu besar. Maksimal '.number_format($maxPixels).' pixels.'
            );
        }
    }

    /**
     * Generate nama file yang aman
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $strategy = config('upload.naming_strategy', 'uuid');
        $extension = $file->getClientOriginalExtension();

        return match ($strategy) {
            'uuid' => Str::uuid()->toString().'.'.$extension,
            'hash' => hash('sha256', $file->getClientOriginalName().time()).'.'.$extension,
            'timestamp' => time().'_'.Str::random(8).'.'.$extension,
            default => Str::uuid()->toString().'.'.$extension,
        };
    }

    /**
     * Build storage path berdasarkan struktur yang dikonfigurasi
     */
    protected function buildStoragePath(string $category, string $filename): string
    {
        $structure = config('upload.storage_structure', 'date');
        $basePath = config("upload.paths.{$category}", $category);

        return match ($structure) {
            'date' => $basePath.'/'.date('Y').'/'.date('m').'/'.$filename,
            'user' => $basePath.'/'.auth()->id().'/'.$filename,
            'flat' => $basePath.'/'.$filename,
            default => $basePath.'/'.date('Y').'/'.date('m').'/'.$filename,
        };
    }

    /**
     * Check apakah tipe adalah image
     */
    protected function isImage(string $type): bool
    {
        return in_array($type, ['image', 'avatar', 'identity']);
    }

    /**
     * Optimasi gambar (placeholder - butuh intervention/image)
     */
    protected function optimizeImage(string $disk, string $path): void
    {
        // TODO: Implement dengan intervention/image jika diperlukan
        // Contoh:
        // $image = Image::make(Storage::disk($disk)->get($path));
        // $image->resize(2000, 2000, function ($constraint) {
        //     $constraint->aspectRatio();
        //     $constraint->upsize();
        // });
        // $image->save(Storage::disk($disk)->path($path), 85);
    }

    /**
     * Generate thumbnail (placeholder - butuh intervention/image)
     */
    protected function generateThumbnail(string $disk, string $path): ?string
    {
        // TODO: Implement dengan intervention/image jika diperlukan
        return null;
    }

    /**
     * Log aktivitas upload
     */
    protected function logUpload(
        UploadedFile $file,
        string $type,
        string $category,
        string $path,
        string $disk
    ): void {
        if (! config('upload.logging.enabled', true)) {
            return;
        }

        Log::channel(config('upload.logging.channel', 'daily'))->info('File uploaded', [
            'type' => $type,
            'category' => $category,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'disk' => $disk,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Save file metadata to database (optional tracking)
     *
     * @param  UploadedFile  $file  Original uploaded file
     * @param  string  $type  File type
     * @param  string  $category  Category/folder
     * @param  string  $storedPath  Stored file path
     * @param  string  $filename  Generated filename
     * @param  string  $disk  Storage disk
     * @param  array  $options  Additional options: ['uploadable_type' => string, 'uploadable_id' => int, 'is_public' => bool]
     * @return UploadedFileModel
     */
    public function saveMetadata(
        UploadedFile $file,
        string $type,
        string $category,
        string $storedPath,
        string $filename,
        string $disk,
        array $options = []
    ): UploadedFileModel {
        return UploadedFileModel::create([
            'user_id' => auth()->id(),
            'uploadable_type' => $options['uploadable_type'] ?? null,
            'uploadable_id' => $options['uploadable_id'] ?? null,
            'file_type' => $type,
            'category' => $category,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $filename,
            'file_path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => $disk,
            'is_public' => $options['is_public'] ?? ($disk === 'public'),
        ]);
    }

    /**
     * Upload file dengan metadata tracking (all-in-one method)
     *
     * @param  UploadedFile  $file  File to upload
     * @param  string  $type  File type
     * @param  string  $category  Category/folder
     * @param  string|null  $disk  Storage disk
     * @param  array  $options  Options with metadata tracking
     * @return array ['path', 'url', 'filename', 'thumbnail', 'model' => UploadedFileModel]
     */
    public function uploadWithTracking(
        UploadedFile $file,
        string $type,
        string $category,
        ?string $disk = null,
        array $options = []
    ): array {
        // Upload file
        $result = $this->upload($file, $type, $category, $disk, $options);

        // Save metadata to database
        $fileModel = $this->saveMetadata(
            $file,
            $type,
            $category,
            $result['path'],
            $result['filename'],
            $disk ?? config('upload.disk', 'local'),
            $options
        );

        // Add model to result
        $result['model'] = $fileModel;

        return $result;
    }
}
