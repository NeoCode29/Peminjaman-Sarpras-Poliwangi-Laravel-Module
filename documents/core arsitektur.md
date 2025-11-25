# Core Architecture Guide

Dokumen ini menjelaskan pola arsitektur core yang digunakan untuk fitur manajemen User, Role, dan Permission dalam project ini. Tujuan utamanya adalah memberikan panduan konsisten bagi pengembang selanjutnya agar mudah memahami struktur kode, alur data, dan alasan di balik keputusan arsitektur.

---

## Tujuan Arsitektur

1. **Monolithic Core dengan Layered Pattern** – Business logic utama diletakkan dalam struktur inti aplikasi (`app/`) dengan pola Controller → Service → Repository → Model.
2. **Separation of Concerns** – Setiap layer punya tanggung jawab kecil dan spesifik untuk memudahkan perawatan, testing, serta pengembangan fitur baru.
3. **Observer Khusus Logging** – Observer hanya bertugas memicu event logging/audit, tidak memuat validasi atau business logic.
4. **Extensibility** – Ke depannya, modul tambahan dapat dibangun menggunakan `nwidart/laravel-modules`, tetapi core tetap berada pada monolit.
5. **Tanpa View Sementara** – Semua controller core saat ini merespon JSON untuk memudahkan integrasi API/internal tooling.

---

## Struktur Direktori Utama

```
app/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Policies/
├── Services/
├── Repositories/
│   └── Interfaces/
├── Events/
├── Listeners/
├── Observers/
└── Constants/
documents/
└── CORE_ARCHITECTURE.md (dokumen ini)
```

---

## Alur Layer (Controller → Service → Repository → Model)

### 1. Controller (`app/Http/Controllers`)
- Contoh: `UserManagementController`, `RoleManagementController`, `PermissionManagementController`.
- Tanggung jawab: menangani request HTTP, memanggil Service, mengembalikan response JSON.
- Authorization menggunakan `authorizeResource()` untuk menghubungkan ke Policy model terkait.
- **Tidak** mengandung business logic selain orkestrasi sederhana (misal, mapping request ke service).

### 2. Form Request (`app/Http/Requests`)
- Contoh: `StoreUserRequest`, `UpdateUserRequest`, `ChangePasswordRequest`, `StoreRoleRequest`, `StorePermissionRequest`.
- Tanggung jawab: validasi data dan otorisasi berbasis permission sebelum masuk ke Service.
- Menjamin aturan seperti format field, unique constraints, enumerasi status, dsb.

### 3. Policy (`app/Policies`)
- Contoh: `UserPolicy`, `RolePolicy`, `PermissionPolicy`.
- Tanggung jawab: mengatur otorisasi berbasis permission user.
- **Tidak** memuat business rule (misal pengecekan relasi atau query ke database). Business rule diserahkan ke Service.
- Menggunakan `App\Constants\ProtectedRoles` untuk konsistensi daftar role yang diproteksi.

### 4. Service (`app/Services`)
- Contoh: `UserService`, `RoleService`, `PermissionService`.
- Tanggung jawab: menjalankan business logic utama seperti proses CRUD, sanitasi data, orchestrasi transaksi, pemanggilan repository, assignment role, constraint bisnis.
- Memastikan operasi berjalan dalam transaksi database (`$this->database->transaction`).
- Menghitung perubahan status, validasi khusus, penanganan edge case (misal role dipakai user lain).

### 5. Repository (`app/Repositories`)
- Interface berada di `app/Repositories/Interfaces`.
- Implementasi berada di `app/Repositories`.
- Tanggung jawab: mengkapsulasi akses data Eloquent/Query Builder dan menyediakan method reuseable (filtering, pagination, relasi).
- Membantu menjaga service tetap fokus ke business logic.

### 6. Model (`app/Models`)
- Tetap menggunakan Eloquent Active Record.
- Contoh: `User`, `Role`, `Permission`.
- Model menyimpan relasi, accessor/mutator, scope, cast (contoh: `password` auto hashed, `metadata` cast ke array).

---

## Observers, Events, dan Audit Logging

### Observer (`app/Observers`)
- Contoh: `UserObserver`, `RoleObserver`, `PermissionObserver`.
- Tanggung jawab: memonitor event Eloquent (`created`, `updated`, `deleted`) lalu **hanya** memicu event domain dan event audit.
- Tidak ada business logic atau validasi di observer.
- Observer didaftarkan di `App\Providers\AppServiceProvider::boot()`.

### Events (`app/Events`)
- Dua kategori event:
  - **Domain Events**: `UserCreated`, `RoleUpdated`, dll. Dipakai untuk operasi lanjutan (contoh: clearing cache, notifikasi).
  - **Audit Events**: `UserAuditLogged`, `RoleAuditLogged`, `PermissionAuditLogged`. Fokus untuk audit logging.
- Event menyimpan data yang relevan (model, atribut lama/baru, user yang melakukan aksi).

### Listeners (`app/Listeners`)
- Contoh: `StoreUserAudit`, `StoreRoleAudit`, `StorePermissionAudit`, `ClearPermissionCache`.
- Didafarkan di `App\Providers\EventServiceProvider` pada property `$listen`.
- `Store*Audit` menuliskan data audit ke tabel `audit_logs`.
- `ClearPermissionCache` subscribe ke event role & permission untuk menjaga konsistensi cache (Spatie Permission).

### Audit Logging
- Observer memanggil `*AuditLogged::dispatch()` dengan data atribut lama & baru.
- Listener `Store*Audit` menyimpan log ke `AuditLog` model.
- Data sensitif seperti password dihapus sebelum logging untuk keamanan.
- Format data audit: `model_type`, `model_id`, `action`, `old_values`, `new_values`, `performed_by`, `context`, dan `metadata` (termasuk field yang berubah).

---

## Keputusan Arsitektur Penting

1. **Repository Pattern** dipilih untuk menjaga pemisahan query dari service, memudahkan testing, dan memfasilitasi reuse filtering/pagination.
2. **Observer hanya logging** sesuai requirement, sehingga business logic terpusat di Service.
3. **Event & Listener** memudahkan extensibility (audit logging, clear cache, notifikasi) tanpa mengubah core flow.
4. **Protected Roles** dikonsolidasikan dalam `App\Constants\ProtectedRoles` agar konsisten di Policy dan Service.
5. **Cache Clearing** hanya dilakukan via listener agar tidak duplikat dengan service layer.
6. **Password Hashing** mengandalkan cast model (`'password' => 'hashed'`) untuk menghindari double hash.
7. **Response Format**: controller core mengembalikan JSON karena view belum diprioritaskan.
8. **Tidak Menggunakan Bootstrap/JQuery** – Mengikuti ketentuan proyek menggunakan CSS custom dan Vanilla JS.

---

## Alur CRUD Singkat (Contoh: Create User)

1. Controller menerima request `POST /user-management` dan mem-validasi via `StoreUserRequest`.
2. Policy memastikan user memiliki permission `user.create`.
3. Service men-sanitize payload, memastikan role valid, menjalankan transaksi create via repository.
4. Repository mengeksekusi query Eloquent, mengembalikan model yang sudah fresh dengan relasi.
5. Observer `UserObserver@created` dipicu otomatis oleh Eloquent, lalu dispatch event audit & domain.
6. Listener `StoreUserAudit` menulis audit log, `ClearPermissionCache` menjaga konsistensi permission.

---

## Tips Untuk Pengembang Selanjutnya

- Tambah komponen baru (misal manajemen lain) mengikuti pola yang sama agar konsisten.
- Jika menambahkan data baru yang perlu diaudit, buat event `*AuditLogged` dan listener `Store*Audit` sesuai pola existing.
- Jika validasi spesifik diperlukan, selalu letakkan di Form Request (validation) ataupun Service (business rule), bukan di Observer.
- Pastikan mendaftarkan binding repository baru di `App\Providers\AppServiceProvider`.
- Ubah policy jika ada permission baru, tetapi tetap hindari query berat di policy.
- Untuk operasi asynchronous (email/notifikasi), gunakan listener tambahan sehingga core tetap bersih.

---

## Common Issues & Solutions

### Issue #1: Authorization Error pada Resource Controller (Route Parameter Mismatch)

**Gejala:**
- Error "This action is unauthorized." muncul pada method `show`, `edit`, `update`, `delete` di resource controller
- Method `index` dan `create` berfungsi normal
- Policy sudah dikonfigurasi dengan benar

**Root Cause:**
Mismatch antara nama parameter yang diharapkan oleh `authorizeResource()` di controller dan nama parameter yang dibuat secara default oleh route resource.

**Contoh Kasus:**
```php
// Di UserManagementController
$this->authorizeResource(User::class, 'user'); // Mengharapkan parameter {user}

// Di routes/web.php
Route::resource('user-management', UserManagementController::class);
// Laravel membuat parameter {user_management} secara default
```

**Penjelasan:**
- Route resource Laravel menggunakan nama singular dari resource name dengan underscore untuk parameter binding
- Resource `user-management` → parameter `{user_management}`
- Method yang tidak butuh parameter model (`index`, `create`) tetap berfungsi
- Method yang butuh parameter model (`show`, `edit`, `update`, `delete`) gagal karena Laravel tidak bisa meng-inject model dengan nama parameter yang salah

**Solusi:**
Override nama parameter route menggunakan method `->parameters()`:

```php
Route::resource('user-management', UserManagementController::class)
    ->parameters(['user-management' => 'user'])
    ->except(['destroy']);
```

**Best Practice:**
- Selalu pastikan nama parameter di route resource sesuai dengan yang diharapkan oleh `authorizeResource()`
- Gunakan `->parameters()` untuk override jika nama resource tidak match dengan model
- Alternatif: gunakan nama resource yang sama dengan model singular (misal: `users` untuk `User` model)

**Tanggal Ditemukan:** 16 November 2024

---

## Kontak & Referensi

- Gaya arsitektur mengacu pada `documents/Aturan Penerapan Laravel Module.md` untuk module usage di masa depan.
- Paket penting: `spatie/laravel-permission`, `nwidart/laravel-modules`.
- Untuk pertanyaan lebih lanjut, cek folder `app/` sesuai struktur di atas atau hubungi maintainers terakhir.
