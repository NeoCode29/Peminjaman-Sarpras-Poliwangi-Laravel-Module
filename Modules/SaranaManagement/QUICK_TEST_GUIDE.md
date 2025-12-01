# Quick Testing Guide - SaranaManagement Module

> **Quick Reference** untuk menulis dan menjalankan tests

---

## 🚀 Running Tests

```bash
# Run all module tests
php artisan test Modules/SaranaManagement/Tests

# Run specific test file
php artisan test Modules/SaranaManagement/Tests/Feature/SaranaCRUDTest.php

# Run single test method
php artisan test --filter=admin_can_create_sarana

# Detailed output
php artisan test --testdox Modules/SaranaManagement/Tests

# Stop on first failure
php artisan test --stop-on-failure Modules/SaranaManagement/Tests
```

---

## 📝 Test Template

### Feature Test Template

```php
<?php

namespace Modules\SaranaManagement\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permission
        Permission::create(['name' => 'sarana.manage', 'group' => 'sarana']);

        // Create admin user
        $this->adminUser = User::factory()->create([
            'status' => 'active',
            'profile_completed' => true,
            'profile_completed_at' => now(), // REQUIRED for middleware
        ]);
        $this->adminUser->givePermissionTo('sarana.manage');
    }

    /** @test */
    public function it_does_something()
    {
        // Arrange
        $data = ['key' => 'value'];
        
        // Act
        $response = $this->actingAs($this->adminUser)
            ->post(route('route.name'), $data);
        
        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('table', $data);
    }
}
```

### Unit Test Template

```php
<?php

namespace Modules\SaranaManagement\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyUnitTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MyService::class);
    }

    /** @test */
    public function it_does_something()
    {
        // Arrange
        $input = 'test';
        
        // Act
        $result = $this->service->doSomething($input);
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

---

## 🎯 Common Test Scenarios

### 1. Authorization Test

```php
/** @test */
public function guest_cannot_access()
{
    $response = $this->get(route('sarana.index'));
    $response->assertRedirect(route('login'));
}

/** @test */
public function user_without_permission_cannot_access()
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->get(route('sarana.index'));
    
    $response->assertForbidden();
}

/** @test */
public function user_with_permission_can_access()
{
    $user = User::factory()->create();
    $user->givePermissionTo('sarana.manage');
    
    $response = $this->actingAs($user)
        ->get(route('sarana.index'));
    
    $response->assertOk();
}
```

### 2. CRUD Test

```php
/** @test */
public function admin_can_create()
{
    $data = ['nama' => 'Test', 'kategori_id' => 1];
    
    $response = $this->actingAs($admin)
        ->post(route('sarana.store'), $data);
    
    $response->assertRedirect(route('sarana.index'));
    $this->assertDatabaseHas('saranas', ['nama' => 'Test']);
}

/** @test */
public function admin_can_update()
{
    $sarana = Sarana::create([...]);
    
    $response = $this->actingAs($admin)
        ->put(route('sarana.update', $sarana), ['nama' => 'Updated']);
    
    $this->assertDatabaseHas('saranas', ['id' => $sarana->id, 'nama' => 'Updated']);
}

/** @test */
public function admin_can_delete()
{
    $sarana = Sarana::create([...]);
    
    $response = $this->actingAs($admin)
        ->delete(route('sarana.destroy', $sarana));
    
    $this->assertDatabaseMissing('saranas', ['id' => $sarana->id]);
}
```

### 3. Validation Test

```php
/** @test */
public function it_validates_required_fields()
{
    $response = $this->actingAs($admin)
        ->post(route('sarana.store'), []);
    
    $response->assertSessionHasErrors(['nama', 'kategori_id']);
}

/** @test */
public function it_validates_unique_field()
{
    Sarana::create(['kode_sarana' => 'SRN-001', ...]);
    
    $response = $this->actingAs($admin)
        ->post(route('sarana.store'), ['kode_sarana' => 'SRN-001', ...]);
    
    $response->assertSessionHasErrors('kode_sarana');
}
```

### 4. File Upload Test

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** @test */
public function admin_can_upload_file()
{
    Storage::fake('public');
    
    $file = UploadedFile::fake()->image('photo.jpg', 800, 600)->size(1024);
    
    $data = [
        'nama' => 'Test',
        'foto' => $file,
    ];
    
    $response = $this->actingAs($admin)
        ->post(route('sarana.store'), $data);
    
    $sarana = Sarana::first();
    Storage::disk('public')->assertExists($sarana->foto);
}

/** @test */
public function it_validates_file_type()
{
    Storage::fake('public');
    
    $file = UploadedFile::fake()->create('document.pdf', 1024);
    
    $response = $this->actingAs($admin)
        ->post(route('sarana.store'), ['foto' => $file, ...]);
    
    $response->assertSessionHasErrors('foto');
}
```

### 5. Relationship Test

```php
/** @test */
public function it_cannot_delete_kategori_with_saranas()
{
    $kategori = KategoriSarana::create([...]);
    Sarana::create(['kategori_id' => $kategori->id, ...]);
    
    $response = $this->actingAs($admin)
        ->delete(route('kategori-sarana.destroy', $kategori));
    
    $response->assertSessionHasErrors();
    $this->assertDatabaseHas('kategori_saranas', ['id' => $kategori->id]);
}
```

---

## 🔍 Common Assertions

### HTTP Assertions

```php
$response->assertOk();                    // 200
$response->assertCreated();               // 201
$response->assertForbidden();             // 403
$response->assertNotFound();              // 404
$response->assertRedirect($url);
$response->assertViewIs('view.name');
$response->assertSee('text');
```

### Session Assertions

```php
$response->assertSessionHas('key');
$response->assertSessionHas('success', 'Message');
$response->assertSessionHasErrors(['field']);
$response->assertSessionHasNoErrors();
```

### Database Assertions

```php
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseMissing('table', ['column' => 'value']);
$this->assertDatabaseCount('table', 5);
$this->assertModelExists($model);
$this->assertSoftDeleted($model);
```

### Value Assertions

```php
$this->assertEquals($expected, $actual);
$this->assertTrue($value);
$this->assertFalse($value);
$this->assertNull($value);
$this->assertNotNull($value);
$this->assertCount(5, $array);
$this->assertContains('value', $array);
$this->assertInstanceOf(Class::class, $object);
```

---

## ⚠️ Common Fixes

### Fix 1: View Not Found (302 Error)

**Problem**: `Expected 200 but got 302`

**Solution**: Create stub views

```bash
# Windows PowerShell
"Test view" | Out-File Modules/SaranaManagement/Resources/views/sarana/index.blade.php
```

### Fix 2: Permission Not Found

**Problem**: `There is no permission named 'sarana.manage'`

**Solution**: Create in setUp()

```php
protected function setUp(): void
{
    parent::setUp();
    Permission::create(['name' => 'sarana.manage', 'group' => 'sarana']);
}
```

### Fix 3: Foreign Key Constraint

**Problem**: `Integrity constraint violation`

**Solution**: Create related models first

```php
$kategori = KategoriSarana::create([...]); // Create first
$sarana = Sarana::create(['kategori_id' => $kategori->id, ...]); // Then use
```

---

## 📋 Before Commit Checklist

- [ ] All tests passing: `php artisan test Modules/SaranaManagement/Tests`
- [ ] No skipped tests
- [ ] Authorization tests included
- [ ] Validation tests included
- [ ] CRUD operations tested
- [ ] Edge cases covered

---

## 📚 Full Documentation

See `documents/MODULE_TESTING_GUIDE.md` for comprehensive guide.

---

**Updated**: 26 November 2025
