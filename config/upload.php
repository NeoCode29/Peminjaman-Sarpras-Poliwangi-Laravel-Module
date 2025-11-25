<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Upload Disk
    |--------------------------------------------------------------------------
    |
    | Disk yang digunakan untuk menyimpan file upload.
    | Options: 'local' (private), 'public', 's3'
    |
    */

    'disk' => env('UPLOAD_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi tipe file yang diizinkan untuk upload.
    | Setiap tipe memiliki: extensions, mime_types, dan max_size (KB)
    |
    */

    'allowed_types' => [
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'mime_types' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
            ],
            'max_size' => 5120, // 5MB in KB
            'description' => 'Gambar (JPG, PNG, GIF, WebP)',
        ],

        'document' => [
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'max_size' => 10240, // 10MB in KB
            'description' => 'Dokumen (PDF, DOC, DOCX, XLS, XLSX)',
        ],

        'identity' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
            'mime_types' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'application/pdf',
            ],
            'max_size' => 2048, // 2MB in KB
            'description' => 'Kartu Identitas (JPG, PNG, PDF)',
        ],

        'avatar' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'mime_types' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/webp',
            ],
            'max_size' => 1024, // 1MB in KB
            'description' => 'Foto Profil (JPG, PNG, WebP)',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Paths
    |--------------------------------------------------------------------------
    |
    | Path untuk menyimpan berbagai jenis file.
    | Path relatif terhadap storage disk.
    |
    */

    'paths' => [
        'avatars' => 'avatars',
        'documents' => 'documents',
        'identities' => 'identities',
        'sarpras' => 'sarpras',
        'temp' => 'temp',
        'thumbnails' => 'thumbnails',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimization
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk optimasi gambar otomatis.
    | Requires: intervention/image package
    |
    */

    'image_optimization' => [
        'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', true),

        // Kualitas kompresi (1-100)
        'quality' => env('IMAGE_OPTIMIZATION_QUALITY', 85),

        // Dimensi maksimal (auto-resize jika lebih besar)
        'max_width' => env('IMAGE_MAX_WIDTH', 2000),
        'max_height' => env('IMAGE_MAX_HEIGHT', 2000),

        // Thumbnail generation
        'thumbnail' => [
            'enabled' => true,
            'width' => 300,
            'height' => 300,
            'quality' => 80,
        ],

        // Strip metadata (EXIF, IPTC, etc.) untuk security
        'strip_metadata' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk membersihkan file temporary dan file lama.
    |
    */

    'cleanup' => [
        // Waktu hidup file temporary (dalam jam)
        'temp_files_lifetime' => 24,

        // Waktu hidup file yang sudah dihapus soft delete (dalam hari)
        'deleted_files_lifetime' => 30,

        // Auto cleanup file temporary saat upload baru
        'auto_cleanup_temp' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Pengaturan keamanan untuk file upload.
    |
    */

    'security' => [
        // Generate nama file random (UUID)
        'randomize_filename' => true,

        // Validasi image dimensions (prevent decompression bomb)
        'validate_dimensions' => true,
        'max_pixel_count' => 25000000, // 25 megapixels

        // Rate limiting (uploads per hour per user)
        'rate_limit' => [
            'enabled' => true,
            'max_uploads' => 50,
            'window_minutes' => 60,
        ],

        // Virus scanning (requires ClamAV)
        'virus_scan' => [
            'enabled' => env('VIRUS_SCAN_ENABLED', false),
            'socket' => env('CLAMAV_SOCKET', '/var/run/clamav/clamd.ctl'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Naming Strategy
    |--------------------------------------------------------------------------
    |
    | Strategi penamaan file: 'uuid', 'hash', 'timestamp'
    |
    */

    'naming_strategy' => env('FILE_NAMING_STRATEGY', 'uuid'),

    /*
    |--------------------------------------------------------------------------
    | Storage Structure
    |--------------------------------------------------------------------------
    |
    | Struktur folder: 'flat', 'date' (year/month), 'user' (user_id/...)
    |
    */

    'storage_structure' => env('FILE_STORAGE_STRUCTURE', 'date'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log aktivitas upload/download untuk audit trail.
    |
    */

    'logging' => [
        'enabled' => true,
        'channel' => env('UPLOAD_LOG_CHANNEL', 'daily'),
        'log_downloads' => true,
        'log_deletions' => true,
    ],

];
