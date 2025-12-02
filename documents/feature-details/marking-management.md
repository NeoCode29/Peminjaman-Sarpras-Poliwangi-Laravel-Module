# Marking Management Feature

> **Status**: Implemented  
> **Module**: `Modules/MarkingManagement`  
> **Created**: 2025-12-02

## Overview

Fitur Marking memungkinkan user untuk melakukan reservasi sementara (marking) prasarana dan sarana sebelum mengajukan peminjaman resmi. Marking berfungsi sebagai "placeholder" yang mencegah konflik jadwal dengan user lain.

## Konsep Utama

### Apa itu Marking?

Marking adalah reservasi sementara yang:
- Memiliki masa berlaku terbatas (default: 3 hari)
- Dapat diperpanjang jika diperlukan
- Harus dikonversi menjadi peminjaman resmi sebelum kadaluarsa
- Otomatis expired jika tidak dikonversi

### Alur Penggunaan

```
1. User membuat Marking
   ↓
2. Marking aktif (status: active)
   ↓
3. User dapat:
   - Edit marking
   - Perpanjang marking
   - Batalkan marking
   - Konversi ke peminjaman
   ↓
4. Jika tidak dikonversi → Marking expired
```

## Komponen Teknis

### Database Schema

**Table: markings**
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | FK ke users |
| prasarana_id | bigint | FK ke prasarana (nullable) |
| lokasi_custom | varchar(255) | Lokasi custom jika tidak pakai prasarana |
| start_datetime | datetime | Waktu mulai acara |
| end_datetime | datetime | Waktu selesai acara |
| jumlah_peserta | int | Perkiraan jumlah peserta |
| expires_at | timestamp | Waktu kadaluarsa marking |
| planned_submit_by | timestamp | Rencana submit pengajuan |
| status | enum | active, expired, converted, cancelled |
| event_name | varchar(255) | Nama acara |
| notes | text | Catatan tambahan |

**Table: marking_items**
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| marking_id | bigint | FK ke markings |
| sarana_id | bigint | FK ke saranas |
| jumlah | int | Jumlah sarana yang di-marking |

### Layer Architecture

```
Controller (MarkingController)
    ↓
Service (MarkingService)
    ↓
Repository (MarkingRepository)
    ↓
Model (Marking, MarkingItem)
```

### Authorization

**Permissions:**
- `marking.manage` - Akses manajemen (lihat semua marking)
- `marking.create` - Membuat marking
- `marking.override` - Override marking user lain

**Policy Rules:**
- User dapat melihat/edit/hapus marking miliknya sendiri
- User dengan `marking.manage` dapat melihat semua marking
- User dengan `marking.override` dapat edit/hapus marking user lain

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /marking | Daftar marking |
| GET | /marking/create | Form buat marking |
| POST | /marking | Simpan marking baru |
| GET | /marking/{id} | Detail marking |
| GET | /marking/{id}/edit | Form edit marking |
| PUT | /marking/{id} | Update marking |
| DELETE | /marking/{id} | Batalkan marking |
| POST | /marking/{id}/convert | Konversi ke peminjaman |
| POST | /marking/{id}/extend | Perpanjang marking |

## Conflict Checking

Sistem akan mengecek konflik sebelum membuat/update marking:

1. **Prasarana Conflict**: Cek apakah prasarana sudah di-marking pada periode yang sama
2. **Lokasi Custom Conflict**: Cek apakah lokasi custom yang sama sudah di-marking

```php
$conflict = $markingService->checkConflicts([
    'prasarana_id' => 1,
    'start_datetime' => '2025-12-10 09:00',
    'end_datetime' => '2025-12-10 12:00',
]);
```

## Auto-Expire

Marking yang melewati `expires_at` akan otomatis berubah status menjadi `expired`.

### Via Artisan Command

```bash
php artisan marking:expire
```

### Via Scheduler (Recommended)

```php
// app/Console/Kernel.php
$schedule->command('marking:expire')->hourly();
```

## Views

| View | Description |
|------|-------------|
| `marking/index.blade.php` | Daftar marking dengan filter |
| `marking/create.blade.php` | Form buat marking |
| `marking/edit.blade.php` | Form edit marking |
| `marking/show.blade.php` | Detail marking |

## Konfigurasi

File: `Modules/MarkingManagement/Config/config.php`

```php
return [
    'marking_duration_days' => 3,      // Durasi default
    'max_extension_days' => 7,         // Maks perpanjangan
    'expiration_warning_hours' => 24,  // Warning sebelum expire
];
```

## Integrasi dengan Module Lain

### PrasaranaManagement
- Marking dapat memilih prasarana dari daftar prasarana yang tersedia
- Relasi: `Marking belongsTo Prasarana`

### SaranaManagement
- Marking dapat memilih sarana yang akan digunakan
- Relasi: `Marking hasMany MarkingItem`, `MarkingItem belongsTo Sarana`

### Peminjaman (Future)
- Marking dapat dikonversi menjadi pengajuan peminjaman
- Data marking akan di-copy ke form peminjaman

## Testing

```bash
# Run all marking tests
php artisan test --filter=MarkingManagement

# Run specific test
php artisan test --filter=MarkingServiceTest
```

## Troubleshooting

### Marking tidak muncul di menu
1. Pastikan seeder sudah dijalankan
2. Pastikan user memiliki permission `marking.create`
3. Clear cache: `php artisan cache:clear`

### Error "This action is unauthorized"
1. Pastikan policy sudah terdaftar di AuthServiceProvider
2. Pastikan user memiliki permission yang diperlukan

### Marking tidak auto-expire
1. Pastikan scheduler sudah dikonfigurasi
2. Jalankan manual: `php artisan marking:expire`
