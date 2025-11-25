# File Upload Best Practices & Implementation

## 📋 Overview

Implementasi file upload yang aman, reusable, dan mengikuti Laravel best practices untuk Website Peminjaman Sarpras.

## 🛡️ Security Best Practices

### 1. **Validasi File**
- ✅ **MIME Type Validation**: Validasi berdasarkan content, bukan hanya extension
- ✅ **File Size Limit**: Batasi ukuran maksimal file
- ✅ **Extension Whitelist**: Hanya izinkan ekstensi yang dibutuhkan
- ✅ **File Name Sanitization**: Hindari karakter berbahaya dalam nama file
- ❌ **JANGAN percaya user input**: Selalu validasi ulang di server

### 2. **Storage Security**
- ✅ **Private Storage**: Simpan file sensitif di `storage/app/private`
- ✅ **Public Storage**: Simpan file publik (gambar produk) di `storage/app/public`
- ✅ **Random Filename**: Generate nama file unik (UUID/hash)
- ✅ **Directory Structure**: Organisir file berdasarkan tipe/tanggal
- ❌ **JANGAN simpan di public**: Hindari menyimpan langsung di folder public

### 3. **Access Control**
- ✅ **Authentication Required**: File sensitif butuh login
- ✅ **Authorization Check**: Pastikan user berhak akses file
- ✅ **Temporary URLs**: Gunakan signed URLs untuk download aman
- ✅ **Rate Limiting**: Batasi jumlah download per waktu

### 4. **File Processing**
- ✅ **Image Optimization**: Compress & resize otomatis
- ✅ **Virus Scanning**: Scan file upload (opsional, butuh ClamAV)
- ✅ **Metadata Stripping**: Hapus EXIF data dari gambar
- ✅ **Format Conversion**: Konversi ke format aman (webp untuk gambar)

### 5. **Performance**
- ✅ **Chunk Upload**: Untuk file besar (>10MB)
- ✅ **Queue Processing**: Process image di background
- ✅ **CDN Integration**: Serve file statis via CDN
- ✅ **Lazy Loading**: Load gambar saat dibutuhkan

---

## 🏗️ Architecture

```
┌─────────────────┐
│   Controller    │ → Terima request, validasi permission
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  FormRequest    │ → Validasi file (size, type, extension)
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ FileUploadSvc   │ → Handle upload, sanitization, storage
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│   FileModel     │ → Track metadata di database (opsional)
└─────────────────┘
```

---

## 📁 Directory Structure

```
storage/
├── app/
│   ├── private/              # File sensitif (KTP, dokumen)
│   │   ├── documents/
│   │   │   └── {year}/{month}/
│   │   ├── identities/
│   │   │   └── {year}/{month}/
│   │   └── temp/            # File temporary
│   └── public/              # File publik (avatar, sarpras)
│       ├── avatars/
│       ├── sarpras/
│       │   └── {year}/{month}/
│       └── thumbnails/
└── logs/
    └── file-uploads.log
```

---

## 🔧 Implementation Components

### 1. Config File: `config/upload.php`

```php
return [
    'disk' => env('UPLOAD_DISK', 'local'),
    
    'allowed_types' => [
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'max_size' => 5120, // KB (5MB)
        ],
        'document' => [
            'extensions' => ['pdf', 'doc', 'docx'],
            'mime_types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'max_size' => 10240, // KB (10MB)
        ],
        'identity' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
            'mime_types' => ['image/jpeg', 'image/png', 'application/pdf'],
            'max_size' => 2048, // KB (2MB)
        ],
    ],
    
    'paths' => [
        'avatars' => 'avatars',
        'documents' => 'documents',
        'identities' => 'identities',
        'sarpras' => 'sarpras',
        'temp' => 'temp',
    ],
    
    'image_optimization' => [
        'enabled' => true,
        'quality' => 85,
        'max_width' => 2000,
        'max_height' => 2000,
        'thumbnail' => [
            'enabled' => true,
            'width' => 300,
            'height' => 300,
        ],
    ],
    
    'cleanup' => [
        'temp_files_lifetime' => 24, // hours
        'old_files_lifetime' => 365, // days
    ],
];
```

### 2. Service: `FileUploadService`
- Handle upload logic
- File sanitization
- Storage management
- Metadata tracking

### 3. Form Request: `FileUploadRequest`
- Validasi tipe file
- Validasi ukuran
- Custom rules

### 4. Model: `UploadedFile` (Optional)
- Track file metadata
- Soft deletes untuk file cleanup
- Relations dengan models lain

### 5. Middleware: `ValidateFileUpload`
- Pre-validation sebelum masuk controller
- Rate limiting per user

---

## 📊 File Metadata Tracking

```sql
CREATE TABLE uploaded_files (
    id BIGINT UNSIGNED PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    uploadable_type VARCHAR(255),  -- Polymorphic
    uploadable_id BIGINT UNSIGNED,
    file_type ENUM('image', 'document', 'identity'),
    original_name VARCHAR(255),
    stored_name VARCHAR(255),
    file_path VARCHAR(500),
    mime_type VARCHAR(100),
    size BIGINT,                   -- bytes
    disk VARCHAR(50),
    is_public BOOLEAN DEFAULT FALSE,
    downloaded_count INT DEFAULT 0,
    last_accessed_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_uploadable (uploadable_type, uploadable_id),
    INDEX idx_user (user_id)
);
```

---

## 🎯 Usage Examples

### Controller

```php
use App\Services\FileUploadService;
use App\Http\Requests\FileUploadRequest;

class SarprasController extends Controller
{
    public function __construct(
        private FileUploadService $fileUploadService
    ) {}

    public function store(FileUploadRequest $request)
    {
        $file = $this->fileUploadService->upload(
            file: $request->file('image'),
            type: 'image',
            category: 'sarpras',
            disk: 'public',
            options: [
                'optimize' => true,
                'thumbnail' => true,
            ]
        );
        
        // $file = ['path' => '...', 'url' => '...', 'thumbnail' => '...']
    }
}
```

### View (Vanilla JS)

```javascript
// Upload dengan progress bar
const uploadFile = async (file, category) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('category', category);
    
    try {
        const response = await fetch('/api/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });
        
        return await response.json();
    } catch (error) {
        console.error('Upload failed:', error);
        throw error;
    }
};
```

---

## ✅ Security Checklist

- [ ] Validasi MIME type di server (jangan percaya client)
- [ ] Batasi ukuran file maksimal
- [ ] Generate nama file random (UUID)
- [ ] Simpan file di luar webroot
- [ ] Implementasi access control untuk download
- [ ] Scan virus untuk file upload (opsional)
- [ ] Rate limiting upload per user
- [ ] Log semua aktivitas upload/download
- [ ] Cleanup file temporary otomatis
- [ ] Backup file penting secara berkala
- [ ] CSP header untuk mencegah XSS via file
- [ ] Validasi image dimension untuk prevent DoS

---

## 🧪 Testing

```php
// Feature Test
public function test_upload_image_with_valid_file()
{
    Storage::fake('public');
    
    $file = UploadedFile::fake()->image('sarpras.jpg', 1000, 1000);
    
    $response = $this->actingAs($user)->post('/sarpras', [
        'image' => $file,
    ]);
    
    Storage::disk('public')->assertExists('sarpras/...');
}

public function test_reject_invalid_file_type()
{
    $file = UploadedFile::fake()->create('malware.exe', 1000);
    
    $response = $this->post('/upload', ['file' => $file]);
    
    $response->assertSessionHasErrors('file');
}
```

---

## 📦 Required Packages

```bash
# Image processing (opsional)
composer require intervention/image

# Virus scanning (opsional, butuh ClamAV installed)
composer require xenolope/quahog
```

---

## 🔄 File Lifecycle

1. **Upload** → Validate → Sanitize → Store → Log
2. **Access** → Authenticate → Authorize → Serve → Track
3. **Cleanup** → Soft Delete → Archive → Hard Delete

---

## ⚠️ Common Pitfalls

### ❌ JANGAN LAKUKAN INI:
```php
// Gunakan original filename
$file->move($path, $file->getClientOriginalName());

// Simpan di public tanpa validasi
$file->move(public_path('uploads'), $filename);

// Percaya extension dari user
if (pathinfo($file, PATHINFO_EXTENSION) === 'jpg') { }
```

### ✅ LAKUKAN INI:
```php
// Generate nama unik
$filename = Str::uuid() . '.' . $file->extension();

// Simpan di storage dengan validasi
Storage::disk('private')->put($path, $file);

// Validasi MIME type
if ($file->getMimeType() === 'image/jpeg') { }
```

---

## 📚 References

- [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)
- [Laravel File Storage Documentation](https://laravel.com/docs/filesystem)
- [Laravel File Upload Best Practices](https://laravel.com/docs/requests#files)

---

**Created**: 2025-01-23  
**Last Updated**: 2025-01-23  
**Version**: 1.0.0
