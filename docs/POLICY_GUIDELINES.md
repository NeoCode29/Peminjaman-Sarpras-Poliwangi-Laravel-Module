# Policy Guidelines & Best Practices

**Panduan Pembuatan Policy untuk Laravel Authorization**

Dokumentasi ini berisi aturan dan best practice untuk membuat dan maintain Policy di aplikasi ini, berdasarkan lesson learned dari refactoring authorization system.

---

## 📋 Table of Contents

1. [Prinsip Dasar](#prinsip-dasar)
2. [Struktur Policy](#struktur-policy)
3. [Aturan Authorization](#aturan-authorization)
4. [Do's and Don'ts](#dos-and-donts)
5. [Template Policy](#template-policy)
6. [Contoh Kasus](#contoh-kasus)
7. [Testing](#testing)

---

## Prinsip Dasar

### 1. Single Source of Truth
**Policy adalah satu-satunya tempat untuk logika authorization.**

✅ **BENAR:**
```php
// Policy
class UserPolicy {
    public function create(User $user): bool {
        return $this->isSarprasAdmin($user);
    }
}

// FormRequest
class StoreUserRequest extends FormRequest {
    public function authorize(): bool {
        return true; // Delegate ke Policy
    }
}
```

❌ **SALAH:**
```php
// FormRequest - jangan taruh logika authorization di sini!
class StoreUserRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->can('user.create') ?? false;
    }
}
```

**Alasan:**
- Menghindari duplikasi logika
- Mudah maintain (hanya update 1 tempat)
- Konsisten di seluruh aplikasi
- Mencegah bypass authorization

---

### 2. Role-Based untuk Fitur Kritis
**Gunakan role check untuk fitur yang hanya boleh diakses role tertentu.**

✅ **BENAR - Manajemen System (Admin Only):**
```php
class UserPolicy {
    protected function isSarprasAdmin(User $user): bool {
        return $user->hasRole('Admin Sarpras');
    }

    public function viewAny(User $user): bool {
        // Manajemen user hanya untuk Admin Sarpras
        return $this->isSarprasAdmin($user);
    }
}
```

✅ **BENAR - Fitur Umum (Permission-Based):**
```php
class PeminjamanPolicy {
    public function create(User $user): bool {
        // Peminjaman bisa dilakukan siapa saja yang punya permission
        return $user->can('peminjaman.create');
    }
}
```

**Kapan pakai Role Check:**
- ✅ Manajemen User, Role, Permission (admin only)
- ✅ System Settings (admin only)
- ✅ Fitur yang sifatnya administrative

**Kapan pakai Permission Check:**
- ✅ Fitur operasional (peminjaman, sarpras, dll)
- ✅ Fitur yang bisa di-grant ke berbagai role
- ✅ Fitur yang permission-nya bisa berubah dinamis

---

### 3. Self-Access Exemption
**User selalu bisa akses data dirinya sendiri.**

✅ **BENAR:**
```php
public function view(User $user, User $target): bool {
    // User bisa lihat profil sendiri
    if ($user->id === $target->id) {
        return true;
    }
    
    // Admin Sarpras bisa lihat profil semua orang
    return $this->isSarprasAdmin($user);
}

public function update(User $user, User $target): bool {
    // User bisa edit profil sendiri
    if ($user->id === $target->id) {
        return true;
    }
    
    // Admin Sarpras bisa edit profil orang lain
    return $this->isSarprasAdmin($user);
}
```

**Exception - Tidak boleh self-action untuk keamanan:**
```php
public function delete(User $user, User $target): bool {
    // Tidak boleh hapus diri sendiri
    if ($user->id === $target->id) {
        return false;
    }
    
    return $this->isSarprasAdmin($user);
}
```

---

## Struktur Policy

### File Structure
```
app/
├── Policies/
│   ├── UserPolicy.php
│   ├── RolePolicy.php
│   ├── PermissionPolicy.php
│   ├── PeminjamanPolicy.php
│   └── SarprasPolicy.php
```

### Policy Class Structure

**Template Standar:**
```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Model; // Ganti dengan model yang sesuai

class ModelPolicy
{
    /**
     * Helper untuk cek role khusus jika diperlukan.
     * Dokumentasikan dengan jelas kapan role check diperlukan.
     */
    protected function isSarprasAdmin(User $user): bool
    {
        return $user->hasRole('Admin Sarpras');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Logic here
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $model): bool
    {
        // Logic here
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Logic here
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $model): bool
    {
        // Logic here
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $model): bool
    {
        // Logic here
    }
}
```

**Urutan Method (Konsisten):**
1. Helper methods (private/protected) di atas
2. `viewAny()`
3. `view()`
4. `create()`
5. `update()`
6. `delete()`
7. Custom methods (alphabetical)

---

## Aturan Authorization

### 1. Controller Setup

**Gunakan `authorizeResource()` untuk resource controller:**
```php
class UserManagementController extends Controller
{
    public function __construct() {
        $this->authorizeResource(User::class, 'user');
    }
    
    // index() → viewAny policy
    // show() → view policy
    // create() → create policy
    // store() → create policy
    // edit() → update policy
    // update() → update policy
    // destroy() → delete policy
}
```

**Untuk method custom, explicit authorize:**
```php
public function block(User $user) {
    $this->authorize('block', $user);
    // ...
}
```

### 2. Route Parameter Naming

**PENTING:** Jika route resource name ≠ model singular, harus declare parameter mapping:

```php
// routes/web.php
Route::resource('user-management', UserManagementController::class)
    ->parameters(['user-management' => 'user']); // ⚠️ WAJIB!
```

Tanpa ini, `show()`, `edit()`, `update()`, `destroy()` akan error "This action is unauthorized".

### 3. FormRequest Authorization

**Semua FormRequest harus return true, delegate ke Policy:**
```php
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by UserPolicy
        return true;
    }
    
    public function rules(): array
    {
        // Validation rules only
    }
}
```

**Exception - Profile requests (cukup cek login):**
```php
class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // OK untuk profile
    }
}
```

---

## Do's and Don'ts

### ✅ DO

1. **Gunakan helper method untuk role check yang konsisten**
   ```php
   protected function isSarprasAdmin(User $user): bool {
       return $user->hasRole('Admin Sarpras');
   }
   ```

2. **Beri comment yang jelas di setiap policy method**
   ```php
   public function delete(User $user, User $target): bool {
       // Tidak bisa hapus diri sendiri
       if ($user->id === $target->id) {
           return false;
       }
       
       // Hanya Admin Sarpras yang boleh hapus user
       return $this->isSarprasAdmin($user);
   }
   ```

3. **Handle edge case dengan jelas**
   ```php
   // Protect from self-actions
   if ($user->id === $target->id) {
       return false; // atau true, tergantung logic
   }
   ```

4. **Buat policy method untuk setiap action**
   ```php
   public function block(User $user, User $target): bool { }
   public function unblock(User $user, User $target): bool { }
   ```

5. **Register policy di AuthServiceProvider**
   ```php
   protected $policies = [
       User::class => UserPolicy::class,
   ];
   ```

### ❌ DON'T

1. **Jangan taruh authorization di FormRequest**
   ```php
   // ❌ SALAH
   public function authorize(): bool {
       return $this->user()?->can('user.create');
   }
   ```

2. **Jangan hardcode role name berulang-ulang**
   ```php
   // ❌ SALAH
   public function create(User $user): bool {
       return $user->hasRole('Admin Sarpras');
   }
   
   public function update(User $user): bool {
       return $user->hasRole('Admin Sarpras'); // Duplikasi!
   }
   ```

3. **Jangan mix role dan permission check tanpa alasan jelas**
   ```php
   // ❌ SALAH - tidak konsisten
   public function create(User $user): bool {
       return $user->hasRole('Admin Sarpras'); // Role check
   }
   
   public function update(User $user): bool {
       return $user->can('user.edit'); // Permission check
   }
   ```

4. **Jangan buat helper method yang tidak dipakai**
   ```php
   // ❌ SALAH - dead code
   private function canManageUsers(User $user): bool {
       // Method ini tidak dipanggil di mana-mana
   }
   ```

5. **Jangan skip authorization check di controller**
   ```php
   // ❌ SALAH
   public function destroy(User $user) {
       // Langsung delete tanpa $this->authorize()
       $this->userService->deleteUser($user);
   }
   ```

---

## Template Policy

### 1. Admin-Only Resource (User, Role, Permission)

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ResourceModel;

class ResourcePolicy
{
    /**
     * Helper untuk cek Admin Sarpras.
     * Hanya Admin Sarpras yang boleh akses manajemen resource ini.
     */
    protected function isSarprasAdmin(User $user): bool
    {
        return $user->hasRole('Admin Sarpras');
    }

    public function viewAny(User $user): bool
    {
        return $this->isSarprasAdmin($user);
    }

    public function view(User $user, ResourceModel $model): bool
    {
        return $this->isSarprasAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isSarprasAdmin($user);
    }

    public function update(User $user, ResourceModel $model): bool
    {
        return $this->isSarprasAdmin($user);
    }

    public function delete(User $user, ResourceModel $model): bool
    {
        return $this->isSarprasAdmin($user);
    }
}
```

### 2. User Resource dengan Self-Access (Profile, dll)

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ResourceModel;

class ResourcePolicy
{
    public function view(User $user, ResourceModel $model): bool
    {
        // User bisa lihat resource sendiri
        if ($model->user_id === $user->id) {
            return true;
        }
        
        // Admin bisa lihat semua
        return $user->can('resource.view');
    }

    public function update(User $user, ResourceModel $model): bool
    {
        // User bisa edit resource sendiri
        if ($model->user_id === $user->id) {
            return true;
        }
        
        // Admin bisa edit semua
        return $user->can('resource.edit');
    }

    public function delete(User $user, ResourceModel $model): bool
    {
        // Hanya admin yang bisa hapus
        return $user->can('resource.delete');
    }
}
```

### 3. Permission-Based Resource (Peminjaman, Sarpras)

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ResourceModel;

class ResourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('resource.view');
    }

    public function view(User $user, ResourceModel $model): bool
    {
        // User bisa lihat resource sendiri atau punya permission view
        if ($model->user_id === $user->id) {
            return true;
        }
        
        return $user->can('resource.view');
    }

    public function create(User $user): bool
    {
        return $user->can('resource.create');
    }

    public function update(User $user, ResourceModel $model): bool
    {
        // Hanya owner atau yang punya permission edit
        if ($model->user_id === $user->id && $model->status === 'draft') {
            return true;
        }
        
        return $user->can('resource.edit');
    }

    public function delete(User $user, ResourceModel $model): bool
    {
        return $user->can('resource.delete');
    }
}
```

### 4. Protected Resource (dengan ProtectedRoles pattern)

```php
<?php

namespace App\Policies;

use App\Constants\ProtectedResources;
use App\Models\User;
use App\Models\ResourceModel;

class ResourcePolicy
{
    public function delete(User $user, ResourceModel $model): bool
    {
        // Cek permission dulu
        if (! $user->can('resource.delete')) {
            return false;
        }
        
        // Cek apakah resource protected
        if (ProtectedResources::isProtected($model->name)) {
            return false;
        }
        
        return true;
    }
}
```

---

## Contoh Kasus

### Case 1: User Management Policy

**Requirement:**
- Hanya Admin Sarpras yang bisa akses manajemen user
- User biasa bisa lihat dan edit profil sendiri
- User tidak bisa hapus atau block diri sendiri

**Solution:**
```php
class UserPolicy
{
    protected function isSarprasAdmin(User $user): bool
    {
        return $user->hasRole('Admin Sarpras');
    }

    public function viewAny(User $user): bool
    {
        // List user hanya untuk admin
        return $this->isSarprasAdmin($user);
    }

    public function view(User $user, User $target): bool
    {
        // User bisa lihat profil sendiri
        if ($user->id === $target->id) {
            return true;
        }
        
        // Admin bisa lihat semua profil
        return $this->isSarprasAdmin($user);
    }

    public function update(User $user, User $target): bool
    {
        // User bisa edit profil sendiri
        if ($user->id === $target->id) {
            return true;
        }
        
        // Admin bisa edit profil orang lain
        return $this->isSarprasAdmin($user);
    }

    public function delete(User $user, User $target): bool
    {
        // Tidak bisa hapus diri sendiri
        if ($user->id === $target->id) {
            return false;
        }
        
        return $this->isSarprasAdmin($user);
    }

    public function block(User $user, User $target): bool
    {
        // Tidak bisa block diri sendiri
        if ($user->id === $target->id) {
            return false;
        }
        
        return $this->isSarprasAdmin($user);
    }
}
```

### Case 2: Role Management dengan Protected Roles

**Requirement:**
- Hanya Admin Sarpras yang bisa manage role
- Role tertentu tidak boleh dihapus (protected)

**Solution:**
```php
class RolePolicy
{
    protected function isSarprasAdmin(User $user): bool
    {
        return $user->hasRole('Admin Sarpras');
    }

    public function delete(User $user, Role $role): bool
    {
        // Hanya Admin Sarpras yang boleh hapus
        if (! $this->isSarprasAdmin($user)) {
            return false;
        }
        
        // Role protected tidak boleh dihapus
        if (ProtectedRoles::isProtected($role->name)) {
            return false;
        }
        
        return true;
    }
}
```

### Case 3: FormRequest Delegation

**SEBELUM (Duplikasi):**
```php
// ❌ Authorization logic terpecah
class StoreUserRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->can('user.create') ?? false;
    }
}

class UserPolicy {
    public function create(User $user): bool {
        return $this->isSarprasAdmin($user);
    }
}
```

**SESUDAH (Single Source of Truth):**
```php
// ✅ Authorization hanya di Policy
class StoreUserRequest extends FormRequest {
    public function authorize(): bool {
        return true; // Delegate ke Policy
    }
}

class UserPolicy {
    public function create(User $user): bool {
        return $this->isSarprasAdmin($user);
    }
}

// Controller
class UserManagementController {
    public function __construct() {
        $this->authorizeResource(User::class, 'user');
    }
}
```

---

## Testing

### Unit Test untuk Policy

```php
use Tests\TestCase;
use App\Models\User;
use App\Policies\UserPolicy;

class UserPolicyTest extends TestCase
{
    protected UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
    }

    /** @test */
    public function admin_sarpras_can_view_any_users()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Sarpras');

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function regular_user_cannot_view_any_users()
    {
        $user = User::factory()->create();
        $user->assignRole('Peminjam Mahasiswa');

        $this->assertFalse($this->policy->viewAny($user));
    }

    /** @test */
    public function user_can_view_own_profile()
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->view($user, $user));
    }

    /** @test */
    public function user_cannot_view_other_profile()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->assertFalse($this->policy->view($user, $other));
    }

    /** @test */
    public function user_cannot_delete_self()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Sarpras');

        $this->assertFalse($this->policy->delete($admin, $admin));
    }
}
```

### Feature Test dengan Authorization

```php
use Tests\TestCase;
use App\Models\User;

class UserManagementTest extends TestCase
{
    /** @test */
    public function admin_can_access_user_management()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Sarpras');

        $response = $this->actingAs($admin)->get('/user-management');

        $response->assertOk();
    }

    /** @test */
    public function regular_user_cannot_access_user_management()
    {
        $user = User::factory()->create();
        $user->assignRole('Peminjam Mahasiswa');

        $response = $this->actingAs($user)->get('/user-management');

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_edit_own_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put("/user-management/{$user->id}", [
                'name' => 'New Name',
                // ... other fields
            ]);

        $response->assertRedirect();
        $this->assertEquals('New Name', $user->fresh()->name);
    }
}
```

---

## Checklist untuk Review Policy

Saat membuat atau review policy, pastikan:

- [ ] Policy terdaftar di `AuthServiceProvider`
- [ ] Tidak ada authorization logic di FormRequest (kecuali profile)
- [ ] Controller menggunakan `authorizeResource()` atau explicit `authorize()`
- [ ] Route parameter mapping sudah benar
- [ ] Helper method untuk role check dibuat jika perlu
- [ ] Self-access dihandle dengan benar
- [ ] Edge case (self-delete, self-block) ditangani
- [ ] Comment jelas di setiap method
- [ ] Tidak ada dead code/unused helper
- [ ] Konsisten dengan policy lain yang sejenis
- [ ] Ada test untuk happy path dan edge case

---

## Reference

- **Laravel Authorization Docs:** https://laravel.com/docs/authorization
- **Spatie Permission:** https://spatie.be/docs/laravel-permission
- **Project Constant:** `app/Constants/ProtectedRoles.php`
- **Auth Service Provider:** `app/Providers/AuthServiceProvider.php`

---

**Last Updated:** 18 November 2025  
**Maintainer:** Development Team
