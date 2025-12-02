# Panduan Testing untuk Laravel Module

> **Comprehensive Guide** untuk testing module menggunakan Laravel Testing Framework  
> **Target**: Module yang dibangun dengan nwidart/laravel-modules  
> **Framework**: PHPUnit + Laravel TestCase  

---

## 📋 Table of Contents

1. [Pengenalan Testing](#pengenalan-testing)
2. [Setup Testing Environment](#setup-testing-environment)
3. [Struktur Test dalam Module](#struktur-test-dalam-module)
4. [Jenis-jenis Test](#jenis-jenis-test)
5. [Writing Tests](#writing-tests)
6. [Running Tests](#running-tests)
7. [Common Issues & Solutions](#common-issues--solutions)
8. [Best Practices](#best-practices)

---

## 📖 Pengenalan Testing

### Mengapa Testing Penting?

1. **Confidence** - Yakin bahwa code berjalan sesuai harapan
2. **Refactoring Safety** - Aman untuk mengubah code
3. **Documentation** - Test adalah dokumentasi code yang selalu up-to-date
4. **Bug Prevention** - Catch bugs sebelum production
5. **Quality Assurance** - Maintain code quality

### Test Pyramid

```
        ╱‾‾‾‾‾‾‾‾‾╲
       ╱   E2E     ╲     ← Sedikit, lambat, comprehensive
      ╱─────────────╲
     ╱  Integration  ╲   ← Medium, test interaction
    ╱─────────────────╲
   ╱   Unit Tests     ╲  ← Banyak, cepat, specific
  ╱─────────────────────╲
```

---

## 🛠️ Setup Testing Environment

### 1. Konfigurasi Database Testing

**File: `.env.testing`**

```env
APP_ENV=testing
APP_DEBUG=true
APP_KEY=base64:YOUR_KEY_HERE

# Option 1: SQLite in-memory (fastest)
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Option 2: Dedicated testing database
# DB_CONNECTION=mysql
# DB_DATABASE=testing_db
# DB_USERNAME=root
# DB_PASSWORD=
```

### 2. PHPUnit Configuration

**File: `phpunit.xml`** (sudah ada di root project)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        
        <!-- Module Tests -->
        <testsuite name="Module">
            <directory>Modules/*/Tests</directory>
        </testsuite>
    </testsuites>
    
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

### 3. Create Test Database (Jika Pakai MySQL)

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE testing_db;"

# Grant privileges
mysql -u root -p -e "GRANT ALL ON testing_db.* TO 'root'@'localhost';"
```

---

## 📂 Struktur Test dalam Module

```
Modules/SaranaManagement/
└── Tests/
    ├── Feature/                    # Integration/Feature tests
    │   ├── SaranaCRUDTest.php     # Test CRUD operations
    │   └── KategoriSaranaCRUDTest.php
    │
    └── Unit/                       # Unit tests
        ├── SaranaServiceTest.php  # Test service logic
        └── SaranaRepositoryTest.php
```

### Naming Convention

| Type | Naming | Example |
|------|--------|---------|
| **Feature Test** | `{Feature}Test.php` | `SaranaCRUDTest.php` |
| **Unit Test** | `{Class}Test.php` | `SaranaServiceTest.php` |
| **Test Method** | `test_it_does_something()` atau `/** @test */ public function it_does_something()` | `test_admin_can_create_sarana()` |

---

## 🧪 Jenis-jenis Test

### 1. Feature Test (Integration Test)

**Tujuan**: Test full request-response cycle dengan database

**Karakteristik**:
- Test HTTP routes
- Test dengan database real
- Test authentication & authorization
- Test validation
- Test view rendering

**Contoh**:
```php
<?php

namespace Modules\SaranaManagement\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaranaCRUDTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_create_sarana()
    {
        // Arrange: Setup user & data
        $admin = User::factory()->create();
        $admin->givePermissionTo('sarana.manage');
        
        $data = [
            'nama' => 'Laptop',
            'kategori_id' => 1,
            'kondisi' => 'baik',
            'status_ketersediaan' => 'tersedia',
            'jumlah' => 1,
        ];

        // Act: Make HTTP request
        $response = $this->actingAs($admin)
            ->post(route('sarana.store'), $data);

        // Assert: Check response & database
        $response->assertRedirect(route('sarana.index'));
        $this->assertDatabaseHas('saranas', ['nama' => 'Laptop']);
    }
}
```

### 2. Unit Test

**Tujuan**: Test single class/method in isolation

**Karakteristik**:
- Test service methods
- Test repository methods
- Test model methods
- Tidak test HTTP layer
- Bisa pakai mocking

**Contoh**:
```php
<?php

namespace Modules\SaranaManagement\Tests\Unit;

use Tests\TestCase;
use Modules\SaranaManagement\Services\SaranaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaranaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SaranaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SaranaService::class);
    }

    /** @test */
    public function it_can_create_sarana_with_auto_generated_kode()
    {
        $data = ['nama' => 'Laptop', /* ... */];
        
        $sarana = $this->service->createSarana($data);
        
        $this->assertStringStartsWith('SRN-', $sarana->kode_sarana);
    }
}
```

---

## ✍️ Writing Tests

### Test Structure (AAA Pattern)

```php
/** @test */
public function it_does_something()
{
    // 1. ARRANGE - Setup preconditions & inputs
    $user = User::factory()->create();
    $data = ['name' => 'Test'];
    
    // 2. ACT - Execute the code under test
    $result = $this->service->doSomething($data);
    
    // 3. ASSERT - Verify the result
    $this->assertEquals('expected', $result);
    $this->assertDatabaseHas('table', $data);
}
```

### Common Assertions

#### HTTP Response Assertions

```php
// Status codes
$response->assertStatus(200);
$response->assertOk();               // 200
$response->assertCreated();          // 201
$response->assertNoContent();        // 204
$response->assertNotFound();         // 404
$response->assertForbidden();        // 403
$response->assertUnauthorized();     // 401

// Redirects
$response->assertRedirect('/path');
$response->assertRedirect(route('route.name'));

// Session
$response->assertSessionHas('key');
$response->assertSessionHas('success', 'Message');
$response->assertSessionHasErrors(['field']);
$response->assertSessionHasNoErrors();

// View
$response->assertViewIs('view.name');
$response->assertViewHas('variable');
$response->assertSee('text');
$response->assertDontSee('text');

// JSON
$response->assertJson(['key' => 'value']);
$response->assertJsonStructure(['data' => ['id', 'name']]);
```

#### Database Assertions

```php
// Check data exists
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseMissing('table', ['column' => 'value']);

// Count records
$this->assertDatabaseCount('table', 5);

// Model exists
$this->assertModelExists($model);
$this->assertModelMissing($model);

// Soft deletes
$this->assertSoftDeleted($model);
```

#### General Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual);    // Strict ===
$this->assertNotEquals($expected, $actual);

// Type checks
$this->assertInstanceOf(Class::class, $object);
$this->assertTrue($value);
$this->assertFalse($value);
$this->assertNull($value);
$this->assertNotNull($value);

// Arrays & Collections
$this->assertCount(5, $array);
$this->assertContains('value', $array);
$this->assertEmpty($array);
$this->assertNotEmpty($array);

// Strings
$this->assertStringContainsString('needle', 'haystack');
$this->assertStringStartsWith('prefix', 'string');
$this->assertStringEndsWith('suffix', 'string');
```

### Testing File Upload

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** @test */
public function it_can_upload_file()
{
    // Fake storage (tidak save file real)
    Storage::fake('public');
    
    // Create fake file
    $file = UploadedFile::fake()->image('photo.jpg', 600, 400)->size(1024);
    
    $data = [
        'foto' => $file,
        // ... other data
    ];
    
    $response = $this->actingAs($user)
        ->post(route('sarana.store'), $data);
    
    $sarana = Sarana::first();
    
    // Assert file tersimpan
    Storage::disk('public')->assertExists($sarana->foto);
}
```

### Testing Authentication & Authorization

```php
/** @test */
public function guest_cannot_access_page()
{
    $response = $this->get(route('sarana.index'));
    
    $response->assertRedirect(route('login'));
}

/** @test */
public function user_without_permission_cannot_access()
{
    $user = User::factory()->create();
    // User tanpa permission
    
    $response = $this->actingAs($user)
        ->get(route('sarana.index'));
    
    $response->assertForbidden(); // 403
}

/** @test */
public function user_with_permission_can_access()
{
    $user = User::factory()->create();
    $user->givePermissionTo('sarana.manage');
    
    $response = $this->actingAs($user)
        ->get(route('sarana.index'));
    
    $response->assertOk(); // 200
}
```

### Testing Validation

```php
/** @test */
public function it_validates_required_fields()
{
    $response = $this->actingAs($admin)
        ->post(route('sarana.store'), []); // Empty data
    
    $response->assertSessionHasErrors([
        'nama',
        'kategori_id',
        'kondisi',
    ]);
}

/** @test */
public function it_validates_file_type()
{
    Storage::fake('public');
    
    $file = UploadedFile::fake()->create('document.pdf', 1024);
    
    $data = [
        'nama' => 'Test',
        'foto' => $file, // PDF, not image
        // ...
    ];
    
    $response = $this->actingAs($admin)
        ->post(route('sarana.store'), $data);
    
    $response->assertSessionHasErrors('foto');
}
```

---

## 🚀 Running Tests

### Basic Commands

```bash
# Run all tests (core + modules)
php artisan test

# Run only module tests
php artisan test Modules/SaranaManagement/Tests

# Run specific test file
php artisan test Modules/SaranaManagement/Tests/Feature/SaranaCRUDTest.php

# Run specific test method
php artisan test --filter=admin_can_create_sarana
```

### Advanced Options

```bash
# Stop on first failure
php artisan test --stop-on-failure

# Run tests in parallel (faster)
php artisan test --parallel

# Show detailed output
php artisan test --testdox

# Run with coverage (requires Xdebug)
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature
```

### Verbose Output

```bash
# Simple output
php artisan test

# Detailed output (testdox)
php artisan test --testdox

# Example output:
#   ✓ Admin can view sarana index
#   ✓ Admin can create sarana with foto
#   ✓ Regular user cannot create sarana
```

---

## ⚠️ Common Issues & Solutions

### Issue 1: Views Not Found (302 Redirect)

**Error**:
```
Expected response status code [200] but received 302.
```

**Penyebab**: View file belum dibuat.

**Solusi A**: Buat stub views untuk testing

```bash
# Create directories
mkdir -p Modules/SaranaManagement/Resources/views/sarana
mkdir -p Modules/SaranaManagement/Resources/views/kategori

# Create stub files (Windows PowerShell)
"Test view" | Out-File Modules/SaranaManagement/Resources/views/sarana/index.blade.php
"Test view" | Out-File Modules/SaranaManagement/Resources/views/sarana/create.blade.php
"Test view" | Out-File Modules/SaranaManagement/Resources/views/sarana/edit.blade.php
"Test view" | Out-File Modules/SaranaManagement/Resources/views/sarana/show.blade.php

# Ulangi untuk kategori
```

**Solusi B**: Skip view assertions

```php
/** @test */
public function admin_can_view_sarana_index()
{
    $response = $this->actingAs($admin)
        ->get(route('sarana.index'));
    
    // Jangan assert view
    // $response->assertViewIs('saranamanagement::sarana.index');
    
    // Cukup assert status OK
    $response->assertOk();
}
```

### Issue 2: Database Constraints Error

**Error**:
```
SQLSTATE[23000]: Integrity constraint violation
```

**Penyebab**: Foreign key constraint atau unique constraint.

**Solusi**: Pastikan data setup lengkap

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Create kategori BEFORE creating sarana
    $this->kategori = KategoriSarana::create([
        'nama' => 'Elektronik',
        'deskripsi' => 'Test',
    ]);
}
```

### Issue 3: Storage Fake Assertion Fails

**Error**:
```
Failed asserting that file exists
```

**Penyebab**: File path tidak match.

**Solusi**: Check path yang disimpan vs yang di-assert

```php
Storage::fake('public');

$file = UploadedFile::fake()->image('test.jpg');

$sarana = $service->createSarana(['foto' => $file, ...]);

// Path di database: saranas/1234567890_abc.jpg
// Assert dengan path yang sama
Storage::disk('public')->assertExists($sarana->foto);
```

### Issue 4: Permission Not Found

**Error**:
```
There is no permission named `sarana.manage`
```

**Solusi**: Create permissions di setUp()

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Create permissions first!
    Permission::create(['name' => 'sarana.manage', 'group' => 'sarana']);
    Permission::create(['name' => 'sarana.view', 'group' => 'sarana']);
    
    // Then create user & assign
    $user = User::factory()->create();
    $user->givePermissionTo('sarana.manage');
}
```

### Issue 5: All Requests Redirect to `/setup` (302)

**Error**:
```
Expected response status code [200] but received 302.
Redirected to: http://localhost/setup
```

**Penyebab**: User profile belum complete. Middleware `profile.completed` check 2 fields:
- `profile_completed === true`
- `profile_completed_at !== null`

**Solusi**: Set BOTH fields di factory

```php
$user = User::factory()->create([
    'status' => 'active',
    'profile_completed' => true,
    'profile_completed_at' => now(), // MUST INCLUDE THIS!
]);
```

### Issue 6: RefreshDatabase Slow

**Solusi**: Gunakan SQLite in-memory

```env
# .env.testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Atau gunakan transactions (faster tapi less realistic):

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MyTest extends TestCase
{
    use DatabaseTransactions; // Instead of RefreshDatabase
}
```

---

## 💡 Best Practices

### 1. Test Isolation

✅ **DO**: Setiap test independent

```php
/** @test */
public function test_a()
{
    $user = User::factory()->create(); // Create own data
    // Test...
}

/** @test */
public function test_b()
{
    $user = User::factory()->create(); // Create own data, jangan depend on test_a
    // Test...
}
```

❌ **DON'T**: Test depend on test lain

```php
protected $user; // DON'T share state

/** @test */
public function test_a()
{
    $this->user = User::factory()->create();
}

/** @test */
public function test_b()
{
    $this->user->update(...); // FAIL jika test_a tidak run!
}
```

### 2. Use Factories

✅ **DO**: Use factories untuk create models

```php
$user = User::factory()->create(['name' => 'John']);
```

❌ **DON'T**: Manual create dengan semua fields

```php
$user = User::create([
    'name' => 'John',
    'email' => 'john@test.com',
    'password' => bcrypt('password'),
    'status' => 'active',
    // 20 fields lainnya...
]);
```

### 3. Descriptive Test Names

✅ **DO**: Test names yang jelas

```php
/** @test */
public function admin_with_permission_can_create_sarana_with_foto()
{
    // Clear what is tested
}
```

❌ **DON'T**: Generic names

```php
/** @test */
public function test_create()
{
    // Unclear what scenario
}
```

### 4. Test One Thing

✅ **DO**: One assertion per test (atau related assertions)

```php
/** @test */
public function it_creates_sarana()
{
    $sarana = $service->createSarana($data);
    
    $this->assertInstanceOf(Sarana::class, $sarana);
    $this->assertDatabaseHas('saranas', ['nama' => $data['nama']]);
}
```

❌ **DON'T**: Test multiple unrelated things

```php
/** @test */
public function it_does_everything()
{
    // Test create
    // Test update
    // Test delete
    // Test filter
    // Too much!
}
```

### 5. Setup & Teardown

✅ **DO**: Use setUp() untuk common setup

```php
protected User $admin;
protected KategoriSarana $kategori;

protected function setUp(): void
{
    parent::setUp();
    
    Permission::create(['name' => 'sarana.manage']);
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('sarana.manage');
    
    $this->kategori = KategoriSarana::create([...]);
}
```

### 6. Test Coverage Priority

Priority untuk testing:

1. **Critical Path** (80%) - Happy path yang sering digunakan
2. **Edge Cases** (15%) - Validation, error handling
3. **Nice to Have** (5%) - Minor features

Jangan aim 100% coverage, focus pada code yang penting.

### 7. Keep Tests Fast

- Use SQLite in-memory
- Minimize database queries
- Use `DatabaseTransactions` untuk read-only tests
- Run tests in parallel

---

## 📊 Example Test Suite

### Complete Feature Test Example

```php
<?php

namespace Modules\SaranaManagement\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SaranaCRUDTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected KategoriSarana $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'sarana.manage', 'group' => 'sarana']);

        // Create roles
        $adminRole = Role::create(['name' => 'Admin Sarpras']);
        $adminRole->givePermissionTo('sarana.manage');

        // Create users
        $this->adminUser = User::factory()->create([
            'status' => 'active',
            'profile_completed' => true,
            'profile_completed_at' => now(), // CRITICAL: Required by middleware!
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->regularUser = User::factory()->create([
            'status' => 'active',
            'profile_completed' => true,
        ]);

        // Create kategori
        $this->kategori = KategoriSarana::create([
            'nama' => 'Elektronik',
            'deskripsi' => 'Test',
        ]);
    }

    /** @test */
    public function admin_can_create_sarana_with_foto()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('laptop.jpg')->size(1024);

        $data = [
            'nama' => 'Laptop Dell',
            'kategori_id' => $this->kategori->id,
            'kondisi' => 'baik',
            'status_ketersediaan' => 'tersedia',
            'jumlah' => 1,
            'foto' => $file,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('sarana.store'), $data);

        $response->assertRedirect(route('sarana.index'));

        $sarana = Sarana::first();
        $this->assertNotNull($sarana->foto);
        Storage::disk('public')->assertExists($sarana->foto);
    }

    /** @test */
    public function regular_user_cannot_create_sarana()
    {
        $data = [
            'nama' => 'Laptop',
            'kategori_id' => $this->kategori->id,
            'kondisi' => 'baik',
            'status_ketersediaan' => 'tersedia',
            'jumlah' => 1,
        ];

        $response = $this->actingAs($this->regularUser)
            ->post(route('sarana.store'), $data);

        $response->assertForbidden();
    }
}
```

---

## 🎯 Checklist Testing Module Baru

Saat membuat module baru, ensure test coverage untuk:

- [ ] **Authorization**
  - [ ] Guest cannot access
  - [ ] User without permission cannot access
  - [ ] User with permission can access

- [ ] **CRUD Operations**
  - [ ] Can view list
  - [ ] Can view detail
  - [ ] Can create
  - [ ] Can update
  - [ ] Can delete

- [ ] **Validation**
  - [ ] Required fields validated
  - [ ] Unique fields validated
  - [ ] Foreign keys validated
  - [ ] File upload validated (type, size)

- [ ] **Business Logic**
  - [ ] Auto-generated fields work
  - [ ] Relationships work correctly
  - [ ] Cascade deletes/constraints work

- [ ] **Edge Cases**
  - [ ] Cannot delete with dependencies
  - [ ] File cleanup on update/delete
  - [ ] Filtering & search work

---

## 📚 Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Dusk (Browser Testing)](https://laravel.com/docs/dusk)
- [Pest PHP (Alternative Test Framework)](https://pestphp.com/)

---

**Guide Version**: 1.0  
**Last Updated**: 26 November 2025  
**Author**: Development Team
