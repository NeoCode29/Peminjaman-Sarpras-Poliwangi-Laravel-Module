# 🚀 Hybrid File Upload Pattern - Setup Guide

## 📋 Setup Instructions

Panduan setup untuk menggunakan Hybrid File Upload Pattern dengan contoh module Sarpras.

---

## Step 1: Register Repository Binding

Edit `app/Providers/AppServiceProvider.php`:

```php
use App\Repositories\Interfaces\SarprasRepositoryInterface;
use App\Repositories\SarprasRepository;

public function register(): void
{
    // Existing bindings...
    
    // Register Sarpras Repository
    $this->app->bind(SarprasRepositoryInterface::class, SarprasRepository::class);
}
```

---

## Step 2: Register Observer

Edit `app/Providers/AppServiceProvider.php`:

```php
use App\Models\Sarpras;
use App\Observers\SarprasObserver;

public function boot(): void
{
    // Existing observers...
    
    // Register Sarpras Observer
    Sarpras::observe(SarprasObserver::class);
}
```

---

## Step 3: Register Event Listener

Edit `app/Providers/EventServiceProvider.php`:

```php
use App\Events\SarprasAuditLogged;
use App\Listeners\StoreSarprasAudit;

protected $listen = [
    // Existing listeners...
    
    SarprasAuditLogged::class => [
        StoreSarprasAudit::class,
    ],
];
```

---

## Step 4: Register Policy

Edit `app/Providers/AuthServiceProvider.php`:

```php
use App\Models\Sarpras;
use App\Policies\SarprasPolicy;

protected $policies = [
    // Existing policies...
    
    Sarpras::class => SarprasPolicy::class,
];
```

---

## Step 5: Create Migrations

### Migration: kategori_sarpras table

```bash
php artisan make:migration create_kategori_sarpras_table
```

```php
public function up(): void
{
    Schema::create('kategori_sarpras', function (Blueprint $table) {
        $table->id();
        $table->string('nama_kategori');
        $table->text('deskripsi')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}
```

### Migration: sarpras table

```bash
php artisan make:migration create_sarpras_table
```

```php
public function up(): void
{
    Schema::create('sarpras', function (Blueprint $table) {
        $table->id();
        $table->string('nama_sarpras');
        $table->string('kode_sarpras')->unique();
        $table->foreignId('kategori_id')->constrained('kategori_sarpras')->cascadeOnDelete();
        $table->text('deskripsi')->nullable();
        $table->integer('jumlah_total')->default(1);
        $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat'])->default('baik');
        $table->string('lokasi_penyimpanan')->nullable();
        $table->enum('status', ['tersedia', 'dipinjam', 'maintenance', 'archived'])->default('tersedia');
        $table->softDeletes();
        $table->timestamps();
        
        $table->index('kode_sarpras');
        $table->index('kategori_id');
        $table->index('status');
    });
}
```

Run migrations:

```bash
php artisan migrate
```

---

## Step 6: Create Permissions & Seeds

### Create Permissions

```bash
php artisan tinker
```

```php
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'sarpras.manage', 'guard_name' => 'web']);
Permission::create(['name' => 'sarpras.view', 'guard_name' => 'web']);
```

### Assign to Role

```php
use Spatie\Permission\Models\Role;

$adminRole = Role::findByName('Admin Sarpras');
$adminRole->givePermissionTo(['sarpras.manage', 'sarpras.view']);
```

### Create Kategori Seeder

```bash
php artisan make:seeder KategoriSarprasSeeder
```

```php
public function run(): void
{
    KategoriSarpras::create([
        'nama_kategori' => 'Peralatan Olahraga',
        'deskripsi' => 'Kategori untuk peralatan olahraga',
        'is_active' => true,
    ]);
    
    KategoriSarpras::create([
        'nama_kategori' => 'Peralatan Laboratorium',
        'deskripsi' => 'Kategori untuk peralatan laboratorium',
        'is_active' => true,
    ]);
    
    // Add more categories...
}
```

Run seeder:

```bash
php artisan db:seed --class=KategoriSarprasSeeder
```

---

## Step 7: Add Routes

Create atau edit `routes/sarpras.php`:

```php
use App\Http\Controllers\Examples\SarprasExampleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Resource routes for sarpras
    Route::resource('sarpras', SarprasExampleController::class)
        ->except(['edit', 'create']); // API-only, no views
    
    // Custom route for deleting specific files
    Route::delete('sarpras/{sarpras}/files', [SarprasExampleController::class, 'deleteFiles'])
        ->name('sarpras.files.delete');
});
```

Include di `routes/web.php`:

```php
require __DIR__.'/sarpras.php';
```

**IMPORTANT:** Jika menggunakan route name yang berbeda (misal `sarpras-management`), tambahkan parameter mapping:

```php
Route::resource('sarpras-management', SarprasExampleController::class)
    ->parameters(['sarpras-management' => 'sarpras']) // ⚠️ CRITICAL!
    ->except(['edit', 'create']);
```

---

## Step 8: Test API Endpoints

### Create Sarpras with Files

```bash
POST /sarpras
Content-Type: multipart/form-data

{
    "nama_sarpras": "Bola Basket Nike",
    "kode_sarpras": "OLH-001",
    "kategori_id": 1,
    "deskripsi": "Bola basket official size 7",
    "jumlah_total": 10,
    "kondisi": "baik",
    "lokasi_penyimpanan": "Gudang Olahraga",
    "files[]": [file1.jpg, file2.jpg]
}
```

### Update Sarpras with New Files

```bash
PUT /sarpras/1
Content-Type: multipart/form-data

{
    "nama_sarpras": "Bola Basket Nike Premium",
    "jumlah_total": 15,
    "new_files[]": [file3.jpg],
    "delete_file_ids[]": [1, 2]
}
```

### Get Sarpras List

```bash
GET /sarpras?search=basket&kategori_id=1&status=tersedia&per_page=15
```

### Delete Sarpras

```bash
DELETE /sarpras/1
```

---

## Step 9: Verify Audit Logs

Check `audit_logs` table:

```sql
SELECT * FROM audit_logs 
WHERE model_type = 'App\\Models\\Sarpras' 
ORDER BY created_at DESC;
```

Check observer logs:

```bash
tail -f storage/logs/laravel.log | grep "Sarpras"
```

---

## Step 10: Verify File Storage

Check uploaded files:

```bash
# Public files
ls -lah storage/app/public/sarpras/

# Database records
SELECT * FROM uploaded_files 
WHERE uploadable_type = 'App\\Models\\Sarpras';
```

---

## 🧪 Testing Checklist

- [ ] Repository binding registered
- [ ] Observer registered dan firing
- [ ] Event listener storing audit logs
- [ ] Policy authorization working
- [ ] Migrations run successfully
- [ ] Permissions created dan assigned
- [ ] Routes accessible
- [ ] File upload working (physical + metadata)
- [ ] File delete working (physical + metadata)
- [ ] Business rule validation (max 5 files)
- [ ] Audit logs being created
- [ ] Polymorphic relation working

---

## 🔍 Troubleshooting

### Error: "This action is unauthorized"

**Fix:** Check route parameter mapping jika menggunakan custom route name:

```php
Route::resource('sarpras-management', SarprasExampleController::class)
    ->parameters(['sarpras-management' => 'sarpras']); // ✅
```

### Error: "Class SarprasRepository does not exist"

**Fix:** Register binding di `AppServiceProvider::register()`:

```php
$this->app->bind(SarprasRepositoryInterface::class, SarprasRepository::class);
```

### Error: "Undefined relationship files"

**Fix:** Ensure `uploaded_files` migration sudah run dan model UploadedFile exist.

### Files not uploading

**Fix:** 
1. Check `storage/app/public/sarpras/` exists dan writable
2. Run `php artisan storage:link`
3. Check `config/upload.php` configuration

### Audit logs not created

**Fix:**
1. Ensure Observer registered di `AppServiceProvider::boot()`
2. Ensure Listener registered di `EventServiceProvider`
3. Check `audit_logs` table exists

---

## 📚 Next Steps

1. **Add Frontend** - Buat form upload dengan preview
2. **Add Tests** - Unit test untuk Service, Repository, Policy
3. **Add Validation** - Custom validation rules sesuai business logic
4. **Add Peminjaman Module** - Implementasi module peminjaman dengan pattern yang sama
5. **Optimize** - Add image optimization dengan intervention/image

---

## 📖 Related Documentation

- [Hybrid File Upload Pattern](./HYBRID_FILE_UPLOAD_PATTERN.md)
- [File Upload Implementation Guide](./FILE_UPLOAD_IMPLEMENTATION_GUIDE.md)
- [Core Architecture](./core%20arsitektur.md)

---

**Created**: 2025-11-23  
**Version**: 1.0.0  
**Status**: ✅ Ready to Use
