# Setup Storage untuk File Upload Sarana

## 📁 Konfigurasi Storage

Module SaranaManagement menggunakan Laravel Storage untuk menyimpan foto sarana.

---

## 🚀 Setup Instructions

### 1. Create Storage Symbolic Link

Jalankan command berikut untuk membuat symbolic link dari `storage/app/public` ke `public/storage`:

```bash
php artisan storage:link
```

**Output yang diharapkan:**
```
The [public/storage] link has been connected to [storage/app/public].
The links have been created.
```

### 2. Verify Directory Structure

Pastikan direktori berikut ada:

```
storage/
└── app/
    └── public/
        └── saranas/  (akan dibuat otomatis saat upload)

public/
└── storage/  (symbolic link ke storage/app/public)
```

### 3. Set Directory Permissions (Linux/Mac)

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

## 📸 Cara Kerja Upload Foto

### File Upload Flow

1. **User upload file** melalui form
2. **Validation** di `StoreSaranaRequest`:
   - Type: `image` (jpg, jpeg, png, gif, bmp, svg, webp)
   - Max size: `2048 KB` (2 MB)

3. **Service handles upload** di `SaranaService`:
   ```php
   private function uploadFoto(UploadedFile $file): string
   {
       // Generate unique filename
       $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
       
       // Store in public/storage/saranas directory
       $path = $file->storeAs('saranas', $filename, 'public');
       
       return $path; // Returns: saranas/1732550400_abc123.jpg
   }
   ```

4. **Path disimpan ke database**:
   - Column: `foto`
   - Value: `saranas/1732550400_abc123.jpg`

5. **Access file via URL**:
   - Database: `saranas/1732550400_abc123.jpg`
   - URL: `http://localhost/storage/saranas/1732550400_abc123.jpg`

### Model Accessor

Model Sarana memiliki accessor `foto_url` untuk mendapatkan full URL:

```php
// In Blade template
<img src="{{ $sarana->foto_url }}" alt="{{ $sarana->nama }}">

// Returns: http://localhost/storage/saranas/1732550400_abc123.jpg
```

---

## 🗑️ Auto-Delete Old Files

Service otomatis menghapus file lama dalam kondisi:

### 1. Update Sarana (dengan foto baru)
```php
// Delete old foto if exists
if ($sarana->foto) {
    $this->deleteFoto($sarana->foto);
}

// Upload new foto
$data['foto'] = $this->uploadFoto($data['foto']);
```

### 2. Delete Sarana
```php
// Delete foto file if exists
if ($sarana->foto) {
    $this->deleteFoto($sarana->foto);
}

$this->saranaRepository->delete($sarana);
```

---

## 📝 Form Implementation Example

### Create/Edit Form (Blade)

```blade
<form action="{{ route('sarana.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    
    {{-- Other fields... --}}
    
    <div class="form-group">
        <label for="foto">Foto Sarana</label>
        <input type="file" 
               name="foto" 
               id="foto" 
               accept="image/*"
               class="form-control @error('foto') is-invalid @enderror">
        
        @error('foto')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        
        <small class="text-muted">Max 2MB. Format: JPG, PNG, GIF, dll.</small>
    </div>
    
    <button type="submit">Simpan</button>
</form>
```

### Edit Form (dengan preview existing foto)

```blade
@if($sarana->foto)
    <div class="mb-3">
        <label>Foto Saat Ini:</label><br>
        <img src="{{ $sarana->foto_url }}" 
             alt="{{ $sarana->nama }}" 
             style="max-width: 200px; height: auto;">
    </div>
@endif

<div class="form-group">
    <label for="foto">
        {{ $sarana->foto ? 'Ganti Foto' : 'Upload Foto' }}
    </label>
    <input type="file" name="foto" id="foto" accept="image/*">
    <small class="text-muted">
        {{ $sarana->foto ? 'Biarkan kosong jika tidak ingin mengganti foto' : 'Opsional' }}
    </small>
</div>
```

---

## 🔍 Storage Disk Configuration

File disimpan menggunakan disk `public` yang dikonfigurasi di `config/filesystems.php`:

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

---

## 🛠️ Troubleshooting

### Issue 1: File tidak bisa diakses via URL

**Solusi**: Pastikan symbolic link sudah dibuat
```bash
php artisan storage:link
```

### Issue 2: Permission denied saat upload

**Solusi**: Set permission yang benar
```bash
# Linux/Mac
chmod -R 775 storage
sudo chown -R www-data:www-data storage  # sesuaikan dengan user webserver

# Windows (run as administrator)
icacls "storage" /grant Users:F /T
```

### Issue 3: File ter-upload tapi tidak tersimpan di database

**Solusi**: Pastikan field `foto` ada di `$fillable` model Sarana
```php
protected $fillable = [
    // ...
    'foto',
    // ...
];
```

### Issue 4: Error "Call to a member function storeAs() on string"

**Solusi**: Pastikan form memiliki attribute `enctype="multipart/form-data"`
```html
<form method="POST" enctype="multipart/form-data">
```

---

## 📊 File Naming Convention

Format nama file yang di-generate:
```
{timestamp}_{unique_id}.{extension}

Example:
1732550400_6564a2f1b8c9d.jpg
1732550401_6564a2f2c1d3e.png
```

**Benefits**:
- ✅ Unique filename (no collision)
- ✅ Sortable by time
- ✅ Original extension preserved
- ✅ Safe for web

---

## 🔐 Security Best Practices

1. **Validation is mandatory**
   - Always validate file type and size
   - Use `image` rule, not just `mimes`

2. **Generate unique filenames**
   - Never use original filename directly
   - Prevent directory traversal attacks

3. **Store outside public root** (optional)
   - Use `local` disk instead of `public`
   - Serve files through controller with authorization

4. **File size limits**
   - Laravel: `upload_max_filesize` in php.ini
   - Application: validation rule `max:2048`

---

**Last Updated**: 26 November 2025  
**Module**: SaranaManagement v1.0.0
