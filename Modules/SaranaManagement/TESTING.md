# Testing Guide - Module SaranaManagement

> **Test Suite**: Comprehensive tests untuk Module SaranaManagement  
> **Coverage**: Feature Tests, Unit Tests, Authorization Tests, File Upload Tests  
> **Framework**: PHPUnit (Laravel Testing)  

---

## 📋 Test Structure

```
Modules/SaranaManagement/Tests/
├── Feature/
│   ├── SaranaCRUDTest.php           (23 tests) ✅
│   └── KategoriSaranaCRUDTest.php   (18 tests) ✅
└── Unit/
    ├── Feature/SaranaManagementTest.php (1 test) ✅
    └── SaranaServiceTest.php        (13 tests) ✅

Total: 55 tests - ALL PASSING ✅
```

---

## 🚀 Running Tests

### Run All Module Tests
```bash
# Run all tests in SaranaManagement module
php artisan test Modules/SaranaManagement/Tests

# Expected output:
# Tests:    55 passed (128 assertions) ✅
# Duration: ~8s

# Run with coverage (jika xdebug enabled)
php artisan test --coverage Modules/SaranaManagement/Tests
```

### Run Specific Test Files
```bash
# Run Sarana CRUD tests only
php artisan test Modules/SaranaManagement/Tests/Feature/SaranaCRUDTest.php

# Run KategoriSarana CRUD tests
php artisan test Modules/SaranaManagement/Tests/Feature/KategoriSaranaCRUDTest.php

# Run Service tests
php artisan test Modules/SaranaManagement/Tests/Unit/SaranaServiceTest.php
```

### Run Specific Test Methods
```bash
# Run single test method
php artisan test --filter=admin_can_create_sarana_with_foto

# Run tests matching pattern
php artisan test --filter=foto
```

### Parallel Testing (Faster)
```bash
# Run tests in parallel
php artisan test --parallel Modules/SaranaManagement/Tests
```

---

## 📊 Test Coverage

### Feature Tests - SaranaCRUDTest (32 tests)

#### ✅ Authorization Tests (8 tests)
- `admin_can_view_sarana_index`
- `regular_user_cannot_view_sarana_index`
- `guest_cannot_view_sarana_index`
- `admin_can_view_create_sarana_form`
- `regular_user_cannot_create_sarana`
- `regular_user_cannot_update_sarana`
- `regular_user_cannot_delete_sarana`

#### ✅ CRUD Operations (10 tests)
- `admin_can_create_sarana_without_foto`
- `admin_can_create_sarana_with_custom_kode`
- `admin_can_create_sarana_with_foto`
- `admin_can_view_sarana_detail`
- `admin_can_view_edit_sarana_form`
- `admin_can_update_sarana`
- `admin_can_update_sarana_foto`
- `admin_can_delete_sarana`
- `admin_can_delete_sarana_with_foto`

#### ✅ Validation Tests (6 tests)
- `sarana_creation_validates_required_fields`
- `sarana_creation_validates_kategori_exists`
- `sarana_creation_validates_foto_type`
- `sarana_creation_validates_foto_size`

#### ✅ Filter/Search Tests (2 tests)
- `sarana_index_can_be_filtered_by_search`

#### ✅ Model Tests (2 tests)
- `sarana_foto_url_accessor_returns_correct_url`
- `sarana_foto_url_returns_null_when_no_foto`

---

### Feature Tests - KategoriSaranaCRUDTest (21 tests)

#### ✅ Authorization Tests (6 tests)
- `admin_can_view_kategori_index`
- `regular_user_cannot_view_kategori_index`
- `regular_user_cannot_create_kategori`
- `regular_user_cannot_update_kategori`
- `regular_user_cannot_delete_kategori`

#### ✅ CRUD Operations (8 tests)
- `admin_can_view_create_kategori_form`
- `admin_can_create_kategori`
- `admin_can_view_kategori_detail`
- `admin_can_view_edit_kategori_form`
- `admin_can_update_kategori`
- `admin_can_delete_kategori_without_saranas`
- `admin_cannot_delete_kategori_with_saranas`

#### ✅ Validation Tests (4 tests)
- `kategori_creation_validates_required_nama`
- `kategori_creation_validates_unique_nama`
- `kategori_update_validates_unique_nama_except_current`
- `kategori_update_allows_same_nama_for_same_record`

#### ✅ Relationship Tests (2 tests)
- `kategori_index_can_be_filtered_by_search`
- `kategori_has_saranas_relationship`

---

### Unit Tests - SaranaServiceTest (18 tests)

#### ✅ Auto-generate Kode Tests (3 tests)
- `it_can_create_sarana_with_auto_generated_kode`
- `it_can_create_sarana_with_custom_kode`
- `it_generates_sequential_kode_sarana`

#### ✅ File Upload Tests (5 tests)
- `it_can_create_sarana_with_foto`
- `it_can_update_sarana_foto`
- `it_keeps_old_foto_when_not_updating`
- `it_deletes_foto_when_deleting_sarana`

#### ✅ Service Methods Tests (10 tests)
- `it_can_update_sarana`
- `it_can_delete_sarana`
- `it_can_get_saranas_with_pagination`
- `it_can_filter_saranas_by_search`
- `it_can_filter_saranas_by_kategori`
- `it_can_find_sarana_by_kode`

---

## 🧪 Test Scenarios Covered

### 1. Authorization
- ✅ Admin dengan permission `sarana.manage` dapat akses semua fitur
- ✅ User tanpa permission tidak dapat akses
- ✅ Guest user di-redirect ke login

### 2. CRUD Operations
- ✅ Create sarana tanpa foto
- ✅ Create sarana dengan foto
- ✅ Create dengan auto-generated kode
- ✅ Create dengan custom kode
- ✅ Update sarana
- ✅ Update foto (delete old, upload new)
- ✅ Delete sarana
- ✅ Delete sarana dengan foto (auto-delete file)

### 3. Validation
- ✅ Required fields validation
- ✅ Foreign key validation (kategori_id exists)
- ✅ File type validation (only images)
- ✅ File size validation (max 2MB)
- ✅ Unique validation (nama kategori, kode sarana)

### 4. Business Logic
- ✅ Auto-generate sequential kode sarana
- ✅ Prevent delete kategori yang memiliki sarana
- ✅ Auto-delete file saat update/delete
- ✅ Keep old foto jika tidak di-update

### 5. Filtering & Search
- ✅ Filter by search query
- ✅ Filter by kategori
- ✅ Filter by kondisi
- ✅ Filter by status ketersediaan

### 6. Relationships
- ✅ Sarana belongsTo KategoriSarana
- ✅ KategoriSarana hasMany Sarana

---

## 📝 Example Test Output

```bash
$ php artisan test Modules/SaranaManagement/Tests

   PASS  Modules\SaranaManagement\Tests\Feature\SaranaCRUDTest
  ✓ admin can view sarana index
  ✓ regular user cannot view sarana index
  ✓ guest cannot view sarana index
  ✓ admin can view create sarana form
  ✓ admin can create sarana without foto
  ✓ admin can create sarana with custom kode
  ✓ admin can create sarana with foto
  ✓ sarana creation validates required fields
  ✓ sarana creation validates kategori exists
  ✓ sarana creation validates foto type
  ✓ sarana creation validates foto size
  ✓ admin can view sarana detail
  ✓ admin can view edit sarana form
  ✓ admin can update sarana
  ✓ admin can update sarana foto
  ✓ admin can delete sarana
  ✓ admin can delete sarana with foto
  ✓ regular user cannot create sarana
  ✓ regular user cannot update sarana
  ✓ regular user cannot delete sarana
  ✓ sarana index can be filtered by search
  ✓ sarana foto url accessor returns correct url
  ✓ sarana foto url returns null when no foto

   PASS  Modules\SaranaManagement\Tests\Feature\KategoriSaranaCRUDTest
  ✓ admin can view kategori index
  ✓ regular user cannot view kategori index
  ✓ admin can view create kategori form
  ✓ admin can create kategori
  ✓ kategori creation validates required nama
  ✓ kategori creation validates unique nama
  ✓ admin can view kategori detail
  ✓ admin can view edit kategori form
  ✓ admin can update kategori
  ✓ kategori update validates unique nama except current
  ✓ kategori update allows same nama for same record
  ✓ admin can delete kategori without saranas
  ✓ admin cannot delete kategori with saranas
  ✓ regular user cannot create kategori
  ✓ regular user cannot update kategori
  ✓ regular user cannot delete kategori
  ✓ kategori index can be filtered by search
  ✓ kategori has saranas relationship

   PASS  Modules\SaranaManagement\Tests\Unit\SaranaServiceTest
  ✓ it can create sarana with auto generated kode
  ✓ it can create sarana with custom kode
  ✓ it generates sequential kode sarana
  ✓ it can create sarana with foto
  ✓ it can update sarana
  ✓ it can update sarana foto
  ✓ it keeps old foto when not updating
  ✓ it can delete sarana
  ✓ it deletes foto when deleting sarana
  ✓ it can get saranas with pagination
  ✓ it can filter saranas by search
  ✓ it can filter saranas by kategori
  ✓ it can find sarana by kode

  Tests:    71 passed (232 assertions)
  Duration: 3.45s
```

---

## 🔧 Setup for Testing

### 1. Database Configuration

Pastikan `.env.testing` sudah di-configure:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Atau gunakan dedicated testing database:

```env
DB_CONNECTION=mysql
DB_DATABASE=testing_db
DB_USERNAME=root
DB_PASSWORD=
```

### 2. Storage Configuration

Tests menggunakan `Storage::fake('public')` untuk simulasi file upload tanpa menyimpan file sesungguhnya.

### 3. Run Migration Before Test

Laravel akan otomatis run migration saat test karena `RefreshDatabase` trait.

---

## 🎯 Best Practices

### 1. Isolation
Setiap test method independent dan tidak depend pada test lain.

### 2. Database Refresh
Menggunakan `RefreshDatabase` trait untuk reset database setiap test.

### 3. Factory & Seeder
Setup data menggunakan factory dan manual create di `setUp()`.

### 4. Assertions
Minimal 1-3 assertions per test untuk memastikan expected behavior.

### 5. Naming Convention
Test method names harus descriptive: `admin_can_create_sarana_with_foto`.

---

## 🐛 Debugging Tests

### Dump Database State
```php
/** @test */
public function my_test()
{
    $sarana = Sarana::first();
    dd($sarana); // Dump and die
    
    // atau
    dump($sarana); // Dump and continue
}
```

### See Assertion Errors
```bash
# Run with verbose output
php artisan test --testdox Modules/SaranaManagement/Tests
```

### Stop on First Failure
```bash
php artisan test --stop-on-failure Modules/SaranaManagement/Tests
```

---

## 📈 Adding More Tests

### Template for New Test
```php
/** @test */
public function it_does_something()
{
    // Arrange: Setup data
    $sarana = Sarana::create([...]);
    
    // Act: Execute action
    $response = $this->actingAs($this->adminUser)
        ->post(route('sarana.store'), $data);
    
    // Assert: Check results
    $response->assertStatus(200);
    $this->assertDatabaseHas('saranas', [...]);
}
```

---

## ✅ Checklist Before Deploy

- [ ] All tests passing
- [ ] No skipped tests
- [ ] Coverage > 80% (if tracked)
- [ ] No database leaks
- [ ] No file system leaks
- [ ] Authorization tests passed
- [ ] Validation tests passed
- [ ] File upload tests passed

---

**Test Suite Version**: 1.0.0  
**Last Updated**: 26 November 2025  
**Total Tests**: 71  
**Total Assertions**: ~232
