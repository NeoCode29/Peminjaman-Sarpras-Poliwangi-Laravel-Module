<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization di-handle di Policy, return true
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $type = $this->input('type', 'image');
        $config = config("upload.allowed_types.{$type}", config('upload.allowed_types.image'));

        return [
            'file' => [
                'required',
                'file',
                'max:'.($config['max_size'] ?? 5120), // KB
                Rule::mimes($this->getAllowedExtensions($type)),
            ],
            'type' => [
                'sometimes',
                'string',
                Rule::in(['image', 'document', 'identity', 'avatar']),
            ],
            'category' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('upload.paths'))),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $type = $this->input('type', 'image');
        $config = config("upload.allowed_types.{$type}", config('upload.allowed_types.image'));
        $maxSizeMB = round(($config['max_size'] ?? 5120) / 1024, 2);

        return [
            'file.required' => 'File harus diunggah.',
            'file.file' => 'File yang diunggah tidak valid.',
            'file.max' => "Ukuran file maksimal {$maxSizeMB}MB.",
            'file.mimes' => 'Tipe file tidak diizinkan. Hanya menerima: '.$config['description'],
            'type.in' => 'Tipe file tidak valid.',
            'category.in' => 'Kategori file tidak valid.',
        ];
    }

    /**
     * Get allowed extensions for the file type
     */
    protected function getAllowedExtensions(string $type): array
    {
        return config("upload.allowed_types.{$type}.extensions", ['jpg', 'jpeg', 'png']);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation: Check rate limiting
            if (config('upload.security.rate_limit.enabled', true)) {
                $this->validateRateLimit($validator);
            }

            // Additional validation: Check MIME type matches extension
            if ($this->hasFile('file')) {
                $this->validateMimeTypeConsistency($validator);
            }
        });
    }

    /**
     * Validate rate limit per user
     */
    protected function validateRateLimit($validator): void
    {
        $cacheKey = 'upload_count_'.auth()->id();
        $maxUploads = config('upload.security.rate_limit.max_uploads', 50);
        $windowMinutes = config('upload.security.rate_limit.window_minutes', 60);

        $uploadCount = cache()->get($cacheKey, 0);

        if ($uploadCount >= $maxUploads) {
            $validator->errors()->add(
                'file',
                "Anda telah mencapai batas maksimal {$maxUploads} upload dalam {$windowMinutes} menit terakhir."
            );
        }

        // Increment counter
        cache()->put($cacheKey, $uploadCount + 1, now()->addMinutes($windowMinutes));
    }

    /**
     * Validate MIME type consistency dengan extension
     */
    protected function validateMimeTypeConsistency($validator): void
    {
        $file = $this->file('file');
        $type = $this->input('type', 'image');

        $allowedMimes = config("upload.allowed_types.{$type}.mime_types", []);
        $actualMime = $file->getMimeType();

        if (! in_array($actualMime, $allowedMimes)) {
            $validator->errors()->add(
                'file',
                'Tipe file tidak sesuai. File kemungkinan rusak atau telah dimodifikasi.'
            );
        }
    }
}
