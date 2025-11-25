# Panduan Testing Project Sarpras

Dokumen ini mendefinisikan pola testing yang **konsisten** untuk seluruh project:

- Struktur folder test
- Batasan antara **Unit Test** dan **Feature Test**
- Template per modul (User, Role, Permission, Sarpras, dll.)
- Checklist saat menambah fitur baru

---

## 1. Struktur Folder & Penamaan

### 1.1. Unit Tests (`tests/Unit`)

Gunakan untuk menguji **logika murni** pada level kecil:

- `tests/Unit/Services/*ServiceTest.php`
- `tests/Unit/Repositories/*RepositoryTest.php`
- `tests/Unit/Policies/*PolicyTest.php`
- `tests/Unit/Http/Requests/*RequestTest.php` (opsional)

**Contoh yang sudah ada:**

- `tests/Unit/Services/AuthServiceTest.php`
- `tests/Unit/Services/UserServiceTest.php`
- `tests/Unit/Repositories/UserRepositoryTest.php`
- `tests/Unit/Policies/RolePolicyTest.php`
- `tests/Unit/Policies/PermissionPolicyTest.php`

### 1.2. Feature Tests (`tests/Feature`)

Gunakan untuk menguji **flow HTTP + integrasi**:

- Per modul/area fitur:
  - `tests/Feature/Auth/*`
  - `tests/Feature/User/*`
  - `tests/Feature/Role/*`
  - `tests/Feature/Permission/*`
  - `tests/Feature/Sarpras/*`
  - `tests/Feature/FileUpload/*`

**Contoh yang sudah ada:**

- `tests/Feature/Auth/LoginTest.php`
- `tests/Feature/Auth/RegisterTest.php`
- `tests/Feature/Auth/OAuthTest.php`
- `tests/Feature/UserManagementAuthorizationTest.php`
- `tests/Feature/RoleManagementAuthorizationTest.php`
- `tests/Feature/PermissionManagementTest.php`
- `tests/Feature/NotificationSystemTest.php`

### 1.3. Penamaan Kelas Test

- **Unit**: `NamaKelasYangDites + Test`
  - `UserServiceTest`, `UserRepositoryTest`, `UserPolicyTest`.
- **Feature**: `NamaFitur + Test`
  - `UserManagementAuthorizationTest`, `RoleManagementTest`, `LoginTest`, `OAuthTest`.

---

## 2. Batasan Tanggung Jawab Unit vs Feature

### 2.1. Unit Test – Logika & Komponen Kecil

**Service (`tests/Unit/Services`)**

- Menguji business logic di service:
  - Input → output (return value)
  - Efek ke repository
  - Exception (`RuntimeException`, `ModelNotFoundException`, dsb.)
- Boleh pakai DB in-memory (sqlite) kalau lebih praktis, tetapi fokus utamanya adalah **perilaku service**.

**Contoh:**

- `UserServiceTest`:
  - create user dengan role valid/invalid
  - update user & ganti role
  - delete user (tidak boleh hapus diri sendiri)
  - block/unblock user
  - toggle status active/inactive (tidak boleh dari blocked)
  - change password (update `password_changed_at`)

- `AuthServiceTest`:
  - login sukses/gagal
  - account locked
  - user blocked
  - register user baru + assign default role

---

**Repository (`tests/Unit/Repositories`)**

- Menguji query, filter, pagination, dan helper.
- Selalu menggunakan DB in-memory.

**Contoh:**

- `UserRepositoryTest`:
  - create + find by id/email/username
  - update dan return fresh dengan relasi
  - `getAll()` dengan filter status/user_type/role
  - `getActive()`
  - `block()` & `unblock()` user

- `RoleRepositoryTest`, `PermissionRepositoryTest`:
  - filter, pagination, grouping, count relasi.

---

**Policy (`tests/Unit/Policies`)**

- Menguji aturan akses per resource **tanpa HTTP**.
- Pola:
  - Instantiate policy: `$policy = new UserPolicy();`
  - Panggil method langsung: `$policy->viewAny($user)`, `$policy->update($user, $target)`, dll.
  - Uji:
    - Pengguna dengan permission vs tanpa permission.
    - Self-access (boleh/tidak).
    - Edge case (protected resource, self-delete, self-block, dsb.).

**Contoh pola:**

- `RolePolicyTest`, `PermissionPolicyTest` (sudah ada)
- `UserPolicyTest` (disarankan ditambah, mirror dari `UserManagementAuthorizationTest`).

---

**Form Request (`tests/Unit/Http/Requests`, opsional)**

- Menguji aturan validasi spesifik yang kompleks/penting.
- Pola:
  - `Validator::make($data, (new StoreUserRequest)->rules())`
  - Assert kasus valid & invalid.

---

### 2.2. Feature Test – HTTP & Integrasi

**Fokus:**

- Route + middleware + policy + view/JSON
- Integrasi permission (`user.manage`, `role.manage`, dll.)
- Response code, redirect, flash message, struktur JSON, nama view.

**Contoh pola:**

- **Auth** (`tests/Feature/Auth`):
  - `LoginTest` → GET login, POST login sukses/gagal, redirect profile setup, logout.
  - `RegisterTest` → GET register, POST register, rate limit, validasi.
  - `OAuthTest` → SSO redirect, callback (sukses/gagal), logout SSO.

- **User Management Authorization**:
  - `UserManagementAuthorizationTest`:
    - Menguji integrasi `UserPolicy` + permission `user.manage` + Gate:
      - `viewAny`, `view`, `create`, `update`, `delete`, `changePassword`, `block`, `unblock`.
      - Self-access vs manage-permission.

- **Role/Permission Management**:
  - `RoleManagementAuthorizationTest`, `PermissionManagementAuthorizationTest`.
  - `RoleManagementTest`, `PermissionManagementTest` (service-level behaviour + efek ke DB & event).

- **Notifications**:
  - `NotificationSystemTest`:
    - builder notifikasi, filtering, endpoint `notifications.*`.

Untuk modul lain (Sarpras, File Upload, Profile, Settings), gunakan pola yang sama.

---

## 3. Template Per Modul Resource

Untuk setiap resource besar (User, Role, Permission, Sarpras, dst.), idealnya ada:

1. **Policy Test (Unit)**

   - File: `tests/Unit/Policies/<Resource>PolicyTest.php`
   - Uji semua method policy:
     - `viewAny`, `view`, `create`, `update`, `delete`, dan custom method lain.
   - Uji:
     - Pengguna dengan permission manage vs tanpa permission.
     - Self-access (boleh/tidak).
     - Aturan khusus (misal: tidak boleh hapus role protected, tidak boleh block diri sendiri).

2. **Repository Test (Unit)**

   - File: `tests/Unit/Repositories/<Resource>RepositoryTest.php`
   - Uji:
     - Filter/pagination.
     - Relasi yang di-load.
     - Helper method (count, block/unblock, toggle, dsb.).

3. **Service Test (Unit)**

   - File: `tests/Unit/Services/<Resource>ServiceTest.php`
   - Uji:
     - List/filter data.
     - create/update/delete dengan aturan bisnis.
     - Toggle status (aktif/nonaktif).
     - Constraint bisnis: tidak boleh hapus jika masih dipakai, status tertentu tidak boleh diubah, dsb.

4. **Authorization Feature Test**

   - File: `tests/Feature/<Resource>/<Resource>AuthorizationTest.php` (atau langsung di root `Feature` seperti sekarang).
   - Uji:
     - User dengan permission yang tepat → bisa akses halaman & action.
     - User tanpa permission → dapat 403 / redirect / error sesuai aturan.

5. **Controller Feature Test**

   - File: `tests/Feature/<Resource>/<Resource>ControllerTest.php`.
   - Uji endpoint utama:
     - `index`, `show`, `create`, `store`, `edit`, `update`, `destroy`.
     - Action tambahan: `toggleStatus`, `block`, `unblock`, dsb.
   - Fokus: HTTP response (status, view, JSON, redirect, flash message).

---

## 4. Praktik Teknis & Gaya Test

### 4.1. Database & Migrations

- Gunakan `DatabaseMigrations` atau `RefreshDatabase` untuk semua test yang menyentuh DB.
- Pastikan `phpunit.xml` sudah mengatur `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:` (sudah ada di project ini).

### 4.2. Permission Cache (Spatie)

Untuk test yang melibatkan role/permission:

```php
protected function forgetPermissionCache(): void
{
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
}
```

Panggil di `setUp()` dan/atau `tearDown()` seperti di test Role/Permission/User Management.

### 4.3. Mocking vs Real

- **Unit Service**:
  - Boleh mock repository jika ingin isolasi murni.
  - Pola di project ini cenderung menggunakan implementasi repository nyata + DB in-memory (boleh dipertahankan untuk konsistensi).

- **Feature**:
  - Minimal mocking.
  - Utamakan fake untuk:
    - `Http::fake()` (external API/SSO).
    - `Notification::fake()` (notifikasi Laravel).
    - `Event::fake()` (untuk cek event ter-dispatch).

### 4.4. Penulisan Test

- Gunakan nama method yang jelas dan deskriptif, misalnya:
  - `public function test_user_with_manage_permission_can_view_any_users()`
  - atau dengan attribute PHPUnit 10/12: `#[Test] public function it_creates_user_with_valid_role(): void`.
- Gunakan bahasa yang konsisten (Inggris/Indonesia) untuk pesan assertion, sesuai pattern yang sudah ada.

---

## 5. Checklist Saat Menambah Fitur Baru

Saat menambahkan fitur/resource baru atau mengubah logic penting, gunakan checklist berikut:

1. **Ada Policy baru / method baru di Policy?**

   - [ ] Tambah/ubah `tests/Unit/Policies/<Resource>PolicyTest.php`.
   - [ ] Tambah/ubah feature test authorization bila perlu.

2. **Ada Service baru / method baru penting?**

   - [ ] Tambah/ubah `tests/Unit/Services/<NamaService>Test.php`.
   - [ ] Cek semua branch (sukses, gagal, exception, edge case).

3. **Ada Repository baru / query filter baru?**

   - [ ] Tambah/ubah `tests/Unit/Repositories/<Repo>Test.php`.
   - [ ] Uji kombinasi filter & pagination yang penting.

4. **Ada Route/Controller baru?**

   - [ ] Tambah feature test di `tests/Feature/<Modul>/...`.
   - [ ] Uji semua endpoint utama + error handling (403, 422, 500 custom, dsb.).

5. **Ada Form Request baru dengan validasi kompleks?**

   - [ ] (Opsional tapi dianjurkan) Tambah test request di `tests/Unit/Http/Requests/...`.

6. **Jalankan seluruh test sebelum commit/merge**

   - [ ] `php artisan test` (atau minimal suites yang relevan).

Dengan mengikuti panduan ini, struktur testing di project akan konsisten, mudah dipahami, dan lebih aman saat menambah fitur baru atau melakukan refactor besar.
