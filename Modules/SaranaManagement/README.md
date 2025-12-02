# Module Manajemen Sarana

> **Status**: ✅ Backend Implementation Complete  
> **Created**: 25 November 2025  
> **Module Type**: Independent Domain Module  

## 📋 Overview

Module untuk mengelola sarana dan kategori sarana di sistem peminjaman sarpras. Implementasi mengikuti **PANDUAN_MODULE_ARCHITECTURE.md** dengan pattern:
- Repository Pattern
- Service Layer
- Policy-Based Authorization
- Form Request Validation

---

## 🎯 Features Implemented

### 1. Kategori Sarana Management
- ✅ CRUD Kategori Sarana
- ✅ Icon support untuk kategori
- ✅ Validation dengan unique constraint
- ✅ Prevent delete kategori yang masih memiliki sarana

### 2. Sarana Management
- ✅ CRUD Sarana
- ✅ Auto-generate kode sarana (SRN-0001, SRN-0002, ...)
- ✅ Multiple filters: search, kategori, kondisi, status ketersediaan
- ✅ Image upload support untuk foto sarana
- ✅ QR code field untuk future scanning feature
- ✅ Comprehensive validation rules

---

## 🗄️ Database Schema

### Tabel: `kategori_saranas`
```sql
- id (PK)
- nama (unique)
- deskripsi (nullable)
- timestamps
```

### Tabel: `saranas`
```sql
- id (PK)
- kode_sarana (unique) - Auto-generated
- nama
- kategori_id (FK → kategori_saranas)
- merk (nullable)
- spesifikasi (text, nullable)
- kondisi (enum: baik, rusak_ringan, rusak_berat, dalam_perbaikan)
- status_ketersediaan (enum: tersedia, dipinjam, dalam_perbaikan, tidak_tersedia)
- jumlah (integer, default: 1)
- tahun_perolehan (nullable)
- nilai_perolehan (decimal, nullable)
- lokasi_penyimpanan (nullable)
- foto (nullable)
- qr_code (nullable)
- keterangan (text, nullable)
- timestamps
- indexes: kode_sarana, kategori_id, kondisi, status_ketersediaan
```

---

## 🔐 Permissions

| Permission | Description | Assigned To |
|------------|-------------|-------------|
| `sarana.manage` | Full access to sarana & kategori management | Admin Sarpras |
| `sarana.view` | View sarana (for borrowing users) | - |

---

## 🛣️ Routes

### Sarana Routes
```
GET    /sarana              → index   (list all saranas)
GET    /sarana/create       → create  (show create form)
POST   /sarana              → store   (create new sarana)
GET    /sarana/{sarana}     → show    (view sarana detail)
GET    /sarana/{sarana}/edit → edit   (show edit form)
PUT    /sarana/{sarana}     → update  (update sarana)
DELETE /sarana/{sarana}     → destroy (delete sarana)
```

### Kategori Sarana Routes
```
GET    /kategori-sarana              → index
GET    /kategori-sarana/create       → create
POST   /kategori-sarana              → store
GET    /kategori-sarana/{kategori_sarana} → show
GET    /kategori-sarana/{kategori_sarana}/edit → edit
PUT    /kategori-sarana/{kategori_sarana} → update
DELETE /kategori-sarana/{kategori_sarana} → destroy
```

**Note**: Routes menggunakan `->parameters(['kategori-sarana' => 'kategori_sarana'])` untuk parameter mapping.

---

## 📂 Module Structure

```
Modules/SaranaManagement/
├── Config/
│   └── config.php
├── Database/
│   ├── Migrations/
│   │   ├── 2025_11_25_164853_create_kategori_saranas_table.php
│   │   └── 2025_11_25_164858_create_saranas_table.php
│   └── Seeders/
│       ├── SaranaManagementDatabaseSeeder.php (Main)
│       ├── SaranaPermissionSeeder.php
│       ├── SaranaMenuSeeder.php
│       ├── KategoriSaranaSeeder.php (5 sample categories)
│       └── SaranaSeeder.php (6 sample saranas)
├── Entities/ (Models)
│   ├── KategoriSarana.php
│   └── Sarana.php
├── Http/
│   ├── Controllers/
│   │   ├── SaranaController.php
│   │   └── KategoriSaranaController.php
│   └── Requests/
│       ├── StoreSaranaRequest.php
│       ├── UpdateSaranaRequest.php
│       ├── StoreKategoriSaranaRequest.php
│       └── UpdateKategoriSaranaRequest.php
├── Policies/
│   ├── SaranaPolicy.php
│   └── KategoriSaranaPolicy.php
├── Repositories/
│   ├── Interfaces/
│   │   ├── SaranaRepositoryInterface.php
│   │   └── KategoriSaranaRepositoryInterface.php
│   ├── SaranaRepository.php
│   └── KategoriSaranaRepository.php
├── Resources/
│   └── views/ (TODO: Create views)
├── Routes/
│   └── web.php
├── Services/
│   ├── SaranaService.php
│   └── KategoriSaranaService.php
├── Providers/
│   ├── SaranaManagementServiceProvider.php
│   └── RouteServiceProvider.php
├── module.json
└── README.md (this file)
```

---

## 🚀 Installation & Setup

### 1. Run Migrations
```bash
# Migrate module migrations
php artisan module:migrate SaranaManagement

# Or migrate all modules
php artisan module:migrate
```

### 2. Run Seeders
```bash
# Seed module data (permissions, menu, sample data)
php artisan module:seed SaranaManagement
```

### 3. Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## 🔧 Key Implementation Details

### Auto-Generated Kode Sarana
Service automatically generates unique kode sarana with pattern `SRN-XXXX`:
```php
// In SaranaService::generateKodeSarana()
$lastSarana = Sarana::orderBy('id', 'desc')->first();
$nextNumber = $lastSarana ? (int) substr($lastSarana->kode_sarana, 4) + 1 : 1;
return 'SRN-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
```

### Policy Authorization
Menggunakan permission `sarana.manage` untuk semua operasi CRUD:
```php
protected function canManageSarana(User $user): bool
{
    return $user->hasPermissionTo('sarana.manage');
}
```

### Repository Pattern
All database queries isolated in repositories:
- `SaranaRepository` → handle sarana data access
- `KategoriSaranaRepository` → handle kategori data access

### Service Layer
Business logic in services:
- `SaranaService` → sarana operations & transactions
- `KategoriSaranaService` → kategori operations & transactions

### Controller Pattern
Thin controllers, delegate to services:
```php
public function __construct(
    private readonly SaranaService $saranaService,
    private readonly KategoriSaranaService $kategoriService
) {
    $this->middleware('auth');
    $this->middleware('profile.completed');
    $this->authorizeResource(Sarana::class, 'sarana');
}
```

---

## 📊 Sample Data

### Kategori Sarana (5 categories)
1. **Elektronik** - Peralatan elektronik
2. **Olahraga** - Peralatan olahraga
3. **Alat Musik** - Peralatan musik
4. **Furniture** - Furniture
5. **Alat Tulis** - Alat tulis

### Sarana (6 sample items)
1. **SRN-0001** - Laptop Asus ROG (Elektronik)
2. **SRN-0002** - Proyektor Epson (Elektronik)
3. **SRN-0003** - Bola Basket Molten (Olahraga)
4. **SRN-0004** - Raket Badminton Yonex (Olahraga)
5. **SRN-0005** - Gitar Akustik Yamaha (Alat Musik)
6. **SRN-0006** - Keyboard Casio (Alat Musik)

---

## 🎨 TODO: Frontend Views

Views belum diimplementasikan. Ketika membuat views, gunakan namespace module:

```blade
{{-- resources/views akan di-load dari: --}}
Modules/SaranaManagement/Resources/views/

{{-- View namespace dalam controller: --}}
return view('saranamanagement::sarana.index', compact('saranas'));

{{-- File structure yang disarankan: --}}
Resources/views/
├── sarana/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
└── kategori/
    ├── index.blade.php
    ├── create.blade.php
    ├── edit.blade.php
    └── show.blade.php
```

---

## 🧪 Testing

### ✅ Test Suite Implemented

**Test Coverage**: 55 tests (128 assertions) - **ALL PASSING** ✅

```
Tests/
├── Feature/
│   ├── SaranaCRUDTest.php           (23 tests) ✅
│   └── KategoriSaranaCRUDTest.php   (18 tests) ✅
└── Unit/
    ├── Feature/SaranaManagementTest.php (1 test) ✅
    └── SaranaServiceTest.php        (13 tests) ✅
```

### Run Tests

```bash
# Run all module tests
php artisan test Modules/SaranaManagement/Tests

# Run specific test file
php artisan test Modules/SaranaManagement/Tests/Feature/SaranaCRUDTest.php

# Run with filter
php artisan test --filter=foto

# Run in parallel (faster)
php artisan test --parallel Modules/SaranaManagement/Tests
```

### Test Coverage

✅ **Authorization Tests** - Permission-based access control  
✅ **CRUD Operations** - Full create, read, update, delete  
✅ **File Upload** - Image upload, update, delete  
✅ **Validation** - All form validation rules  
✅ **Business Logic** - Auto-generate kode, prevent delete dengan relasi  
✅ **Filtering** - Search dan filter functionality  
✅ **Service Layer** - All service methods  
✅ **Relationships** - Model relations  

**Dokumentasi lengkap**: 
- `TESTING.md` - Test suite details & coverage
- `QUICK_TEST_GUIDE.md` - Quick reference untuk menulis test
- `documents/MODULE_TESTING_GUIDE.md` - Comprehensive testing guide

---

## 📝 Notes

1. **Image Upload**: ✅ **IMPLEMENTED** - Full file upload dengan auto-delete old files. See `STORAGE_SETUP.md`
2. **QR Code**: Field tersedia untuk future implementation
3. **Soft Delete**: Belum diimplementasikan, tambahkan `SoftDeletes` trait jika diperlukan
4. **Peminjaman Integration**: TODO untuk integrasi dengan module peminjaman

## 📸 File Upload Features

### Upload Implementation
- ✅ Validation: `image`, max 2MB
- ✅ Unique filename generation: `{timestamp}_{uniqid}.{ext}`
- ✅ Storage: `storage/app/public/saranas/`
- ✅ Auto-delete old file on update
- ✅ Auto-delete file on delete sarana
- ✅ Model accessor: `$sarana->foto_url` untuk full URL

### Setup Required
```bash
# Create storage link
php artisan storage:link

# Set permissions (Linux/Mac)
chmod -R 775 storage
```

**Dokumentasi lengkap**: See `STORAGE_SETUP.md`

---

## 🔗 Related Documentation

- `documents/PANDUAN_MODULE_ARCHITECTURE.md` - Module architecture guidelines
- `documents/core arsitektur.md` - Core architecture
- `docs/POLICY_GUIDELINES.md` - Policy implementation guidelines

---

**Module Version**: 1.0.0  
**Last Updated**: 25 November 2025
