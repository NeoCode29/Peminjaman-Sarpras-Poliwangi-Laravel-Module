# Aturan Testing Project Fix

## 1. Hirarki Pengujian Wajib
1. **Feature Tests (Service Layer)**
   - Uji CRUD lengkap: create, list/filter, update + sinkronisasi relasi, delete dengan guard.
   - Validasi aturan bisnis: status toggle, role/permission in-use, data terlindungi.
   - Verifikasi event & transaksi: `Event::fake()`, `Event::assertDispatched()`, rollback saat gagal.
   - Gunakan `DatabaseMigrations` + factory untuk data uji.
2. **Policy Tests**
   - Sediakan skenario `allows` dan `denies` untuk setiap aksi (`viewAny`, `view`, `create`, `update`, `delete`).
   - Pastikan permission cache dibersihkan sebelum/selesai pengujian.
3. **Repository Tests (Direkomendasikan)**
   - Pastikan query filter, `findById`, `getActive`, dan penghitung relasi (mis. `countUsers`) berjalan benar.

## 2. Pengujian Pelengkap (Opsional Tapi Disarankan)
1. **Request Validation Tests** – cek field wajib, unique, format, serta authorisasi.
2. **Observer Tests** – pastikan audit log tercatat untuk aksi `created`, `updated`, `deleted` dengan payload benar.
3. **Controller/Integration Tests** – validasi response view/redirect, middleware, dan flash message.

## 3. Pola Penulisan Test
- Gunakan nama metode deskriptif: `test_it_can_create_role_with_permissions`, `test_it_prevents_deleting_role_in_use`.
- Sertakan helper `forgetPermissionCache()` bila memakai Spatie Permission.
- Pisahkan skenario positif dan negatif dalam test berbeda.
- Hindari bergantung pada state antar test; setiap test mempersiapkan data sendiri.

## 4. Target Coverage & Monitoring
- Service Layer ≥ 90%
- Policy & Authorization 100%
- Repository ≥ 85%
- Eksekusi rutin: `php artisan test --parallel --coverage --min=80`

## 5. Workflow TDD yang Direkomendasikan
1. Tulis Feature Test (RED).
2. Implement Service minimal (GREEN).
3. Refactor + tambahkan aturan bisnis.
4. Tambahkan Policy Test.
5. (Opsional) Tambahkan Repository/Request/Observer Test.
6. Jalankan seluruh suite: `php artisan test --parallel`.

## 6. Checklist Per Fitur
- [ ] Feature Test mencakup CRUD + guard + event.
- [ ] Policy Test mencakup semua aksi.
- [ ] Repository Test untuk query kritikal.
- [ ] Request Validation Test (bila ada form/input baru).
- [ ] Observer Test (bila ada audit/log otomatis).

Dokumen ini wajib diperbaharui jika ada perubahan arsitektur atau lapisan baru yang ditambahkan dalam core testing project.
