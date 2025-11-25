# 📦 File Upload Implementation Guide

## 🎯 Overview

Implementasi lengkap file upload yang **aman**, **reusable**, dan **production-ready** untuk Website Peminjaman Sarpras.

## ✅ Komponen yang Sudah Dibuat

### 1. **Configuration**
- ✅ `config/upload.php` - Konfigurasi file upload (types, paths, security, dll)

### 2. **Backend**
- ✅ `app/Services/FileUploadService.php` - Service untuk handle upload
- ✅ `app/Http/Requests/FileUploadRequest.php` - Form request validation
- ✅ `app/Models/UploadedFile.php` - Model untuk tracking metadata
- ✅ `app/Http/Controllers/FileController.php` - Controller untuk download/delete
- ✅ `app/Http/Controllers/Examples/FileUploadExampleController.php` - Contoh implementasi
- ✅ `database/migrations/xxxx_create_uploaded_files_table.php` - Migration table

### 3. **Routes**
- ✅ `routes/file-upload-routes.php` - Route definitions

### 4. **Frontend**
- ✅ `public/js/file-upload.js` - Vanilla JS utility untuk upload
- ✅ `resources/views/examples/file-upload-example.blade.php` - Contoh HTML

### 5. **Documentation**
- ✅ `documents/feature-details/file-upload-best-practices.md` - Best practices lengkap
- ✅ `documents/FILE_UPLOAD_IMPLEMENTATION_GUIDE.md` - Guide ini

---

## 🚀 Setup Instructions

### Step 1: Run Migration

```bash
php artisan migrate
```

Migration akan membuat table `uploaded_files` untuk tracking metadata file.

### Step 2: Include Routes

Tambahkan di `routes/web.php`:

```php
require __DIR__.'/file-upload-routes.php';
```

### Step 3: Storage Link (untuk public files)

```bash
php artisan storage:link
```

### Step 4: Set Permissions

Pastikan folder storage dapat ditulis:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Step 5: Update .env (Optional)

```env
# Upload Configuration
UPLOAD_DISK=local
IMAGE_OPTIMIZATION_ENABLED=true
IMAGE_OPTIMIZATION_QUALITY=85
IMAGE_MAX_WIDTH=2000
IMAGE_MAX_HEIGHT=2000
FILE_NAMING_STRATEGY=uuid
FILE_STORAGE_STRUCTURE=date
```

---

## 📖 Usage Examples

### Backend: Upload File Sederhana

```php
use App\Services\FileUploadService;

public function store(Request $request, FileUploadService $fileUploadService)
{
    $result = $fileUploadService->upload(
        file: $request->file('image'),
        type: 'image',
        category: 'sarpras',
        disk: 'public',
        options: [
            'optimize' => true,
            'thumbnail' => true,
        ]
    );
    
    // $result = [
    //     'path' => 'sarpras/2025/11/uuid.jpg',
    //     'url' => 'http://domain.com/storage/sarpras/2025/11/uuid.jpg',
    //     'filename' => 'uuid.jpg',
    //     'thumbnail' => null
    // ]
}
```

### Backend: Upload dengan Database Tracking

```php
$result = $fileUploadService->uploadWithTracking(
    file: $request->file('document'),
    type: 'document',
    category: 'documents',
    disk: 'local',
    options: [
        'uploadable_type' => 'App\Models\Sarpras',
        'uploadable_id' => $sarpras->id,
        'is_public' => false,
    ]
);

// $result['model'] = UploadedFile model instance
// $fileId = $result['model']->id;
```

### Frontend: Upload dengan Progress Bar

```javascript
const file = document.getElementById('myFile').files[0];

FileUpload.upload(file, {
    url: '/api/upload/sarpras-image',
    type: 'image',
    onProgress: (percent) => {
        console.log(`Progress: ${percent}%`);
    },
    onSuccess: (response) => {
        console.log('Success!', response);
    },
    onError: (error) => {
        console.error('Error:', error);
    }
});
```

### Download Private File

```php
// Generate signed URL
$file = UploadedFile::find($id);
$downloadUrl = $file->getTemporaryUrl(60); // Valid 60 menit

// Di view
<a href="{{ $downloadUrl }}">Download File</a>
```

---

## 🎨 Frontend Integration

### Include Script di Layout

```blade
<!-- resources/views/layouts/app.blade.php -->
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('js/file-upload.js') }}"></script>
</head>
```

### Contoh Form Upload

```html
<form>
    <input type="file" id="fileInput" accept="image/*">
    <button type="button" onclick="handleUpload()">Upload</button>
    <div id="progress"></div>
    <div id="message"></div>
</form>

<script>
function handleUpload() {
    const file = document.getElementById('fileInput').files[0];
    const progressBar = FileUpload.createProgressBar('progress');
    
    FileUpload.upload(file, {
        url: '/api/upload/sarpras-image',
        type: 'image',
        onProgress: (percent) => progressBar.update(percent),
        onSuccess: (response) => {
            progressBar.complete();
            document.getElementById('message').innerHTML = 
                '<div class="success">Upload berhasil!</div>';
        },
        onError: (error) => {
            progressBar.error();
            document.getElementById('message').innerHTML = 
                '<div class="error">' + error + '</div>';
        }
    });
}
</script>
```

---

## 🔐 Security Features

### ✅ Implemented

1. **MIME Type Validation** - Validasi berdasarkan content, bukan extension
2. **File Size Limit** - Batasi ukuran per tipe file
3. **Extension Whitelist** - Hanya allow ekstensi yang diizinkan
4. **Random Filename** - Generate UUID untuk nama file
5. **Private Storage** - File sensitif di `storage/app/private`
6. **Access Control** - Authorization check sebelum download
7. **Rate Limiting** - Batasi upload per user (config)
8. **Signed URLs** - Temporary download URLs dengan expiry
9. **Image Dimension Validation** - Prevent decompression bomb
10. **Audit Logging** - Log semua aktivitas upload/download

### 🔜 Optional Enhancements

1. **Image Optimization** - Perlu install `intervention/image`
2. **Virus Scanning** - Perlu install ClamAV + `xenolope/quahog`
3. **Thumbnail Generation** - Perlu install `intervention/image`

---

## 📊 File Types Configuration

| Type | Extensions | MIME Types | Max Size |
|------|-----------|-----------|----------|
| `image` | jpg, jpeg, png, gif, webp | image/* | 5MB |
| `document` | pdf, doc, docx, xls, xlsx | application/* | 10MB |
| `identity` | jpg, jpeg, png, pdf | image/*, application/pdf | 2MB |
| `avatar` | jpg, jpeg, png, webp | image/* | 1MB |

Bisa diubah di `config/upload.php`

---

## 🗂️ Storage Structure

Default: **Date-based** (`storage_structure => 'date'`)

```
storage/app/
├── private/
│   ├── documents/2025/11/uuid.pdf
│   ├── identities/2025/11/uuid.jpg
│   └── temp/uuid.jpg
└── public/
    ├── avatars/2025/11/uuid.jpg
    ├── sarpras/2025/11/uuid.jpg
    └── thumbnails/uuid_thumb.jpg
```

Alternatif:
- `flat` - Semua file di satu folder
- `user` - Group by user_id

---

## 🧪 Testing

### Test Upload

```bash
# Akses example page
http://localhost:8000/examples/file-upload
```

### Manual Test via cURL

```bash
# Upload image
curl -X POST http://localhost:8000/api/upload/sarpras-image \
  -H "X-CSRF-TOKEN: your-token" \
  -F "file=@/path/to/image.jpg" \
  -F "type=image"
```

### Unit Test (Create later)

```php
public function test_upload_image_success()
{
    Storage::fake('public');
    
    $file = UploadedFile::fake()->image('test.jpg');
    
    $result = $this->fileUploadService->upload(
        $file, 'image', 'sarpras', 'public'
    );
    
    $this->assertArrayHasKey('path', $result);
    Storage::disk('public')->assertExists($result['path']);
}
```

---

## 🔧 Troubleshooting

### Error: "The file "xxx" was not uploaded due to an unknown error"

**Fix:**
```bash
chmod -R 775 storage
php artisan storage:link
```

### Error: "Maximum upload size exceeded"

**Fix di `.env`:**
```env
UPLOAD_MAX_FILESIZE=10M
POST_MAX_SIZE=10M
```

**Fix di `php.ini`:**
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

### Error: "CSRF token mismatch"

**Fix:** Pastikan ada `<meta name="csrf-token">` di layout:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

---

## 📝 Customization

### Tambah Tipe File Baru

Edit `config/upload.php`:

```php
'allowed_types' => [
    // ... existing types
    'video' => [
        'extensions' => ['mp4', 'avi', 'mov'],
        'mime_types' => ['video/mp4', 'video/x-msvideo', 'video/quicktime'],
        'max_size' => 51200, // 50MB
        'description' => 'Video (MP4, AVI, MOV)',
    ],
],
```

### Custom Storage Path

Edit `config/upload.php`:

```php
'paths' => [
    // ... existing paths
    'videos' => 'videos',
    'contracts' => 'contracts',
],
```

### Custom Validation Rules

Extend `FileUploadRequest`:

```php
public function rules(): array
{
    $rules = parent::rules();
    
    // Tambah custom validation
    $rules['file'][] = 'dimensions:min_width=100,min_height=100';
    
    return $rules;
}
```

---

## 🎯 Best Practices Checklist

- [x] Validasi MIME type di server
- [x] Generate nama file random (UUID)
- [x] Simpan file di storage (bukan public)
- [x] Implementasi access control
- [x] Rate limiting upload
- [x] Log aktivitas upload/download
- [x] Auto cleanup temp files
- [ ] Image optimization (optional)
- [ ] Virus scanning (optional)
- [ ] Backup strategy

---

## 📚 Related Documentation

- [File Upload Best Practices](./feature-details/file-upload-best-practices.md)
- [Laravel File Storage](https://laravel.com/docs/filesystem)
- [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)

---

## 🆘 Support

Jika ada masalah atau pertanyaan:

1. Check dokumentasi di `documents/feature-details/file-upload-best-practices.md`
2. Lihat contoh di `app/Http/Controllers/Examples/FileUploadExampleController.php`
3. Test di `/examples/file-upload`

---

**Created**: 2025-11-23  
**Version**: 1.0.0  
**Status**: ✅ Ready for Production
