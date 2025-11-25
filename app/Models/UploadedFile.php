<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class UploadedFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'uploadable_type',
        'uploadable_id',
        'file_type',
        'category',
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'size',
        'disk',
        'is_public',
        'downloaded_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'downloaded_count' => 'integer',
        'size' => 'integer',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the user who uploaded the file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the owning uploadable model (polymorphic)
     */
    public function uploadable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if file exists in storage
     */
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->file_path);
    }

    /**
     * Get file URL (untuk public files)
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->is_public && $this->disk === 'public') {
            return Storage::disk($this->disk)->url($this->file_path);
        }

        return null;
    }

    /**
     * Get file size in human readable format
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get file extension
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->stored_name, PATHINFO_EXTENSION);
    }

    /**
     * Increment download counter
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('downloaded_count');
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            return Storage::disk($this->disk)->delete($this->file_path);
        }

        return false;
    }

    /**
     * Get temporary signed URL (for private files)
     */
    public function getTemporaryUrl(int $expiresInMinutes = 60): string
    {
        if (method_exists(Storage::disk($this->disk), 'temporaryUrl')) {
            return Storage::disk($this->disk)->temporaryUrl(
                $this->file_path,
                now()->addMinutes($expiresInMinutes)
            );
        }

        // For local disk, use signed route
        return route('file.download', [
            'id' => $this->id,
            'expires' => now()->addMinutes($expiresInMinutes)->timestamp,
            'signature' => $this->generateSignature($expiresInMinutes),
        ]);
    }

    /**
     * Generate signature for file download
     */
    protected function generateSignature(int $expiresInMinutes): string
    {
        $expires = now()->addMinutes($expiresInMinutes)->timestamp;

        return hash_hmac('sha256', $this->id.$expires, config('app.key'));
    }

    /**
     * Verify download signature
     */
    public function verifySignature(string $signature, int $expires): bool
    {
        if (now()->timestamp > $expires) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $this->id.$expires, config('app.key'));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Scope: Only public files
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: Only private files
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope: By file type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope: By category
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Auto delete file from storage when model is deleted (hard delete)
        static::forceDeleted(function ($file) {
            $file->deleteFile();
        });
    }
}
