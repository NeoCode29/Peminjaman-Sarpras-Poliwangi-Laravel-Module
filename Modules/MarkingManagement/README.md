# Marking Management Module

Module untuk mengelola marking (reservasi sementara) prasarana dan sarana sebelum dikonversi menjadi pengajuan peminjaman resmi.

## Fitur

- **Buat Marking**: Reservasi sementara prasarana dan sarana untuk acara
- **Edit Marking**: Perbarui informasi marking yang masih aktif
- **Batalkan Marking**: Batalkan marking yang tidak jadi digunakan
- **Perpanjang Marking**: Perpanjang masa berlaku marking
- **Konversi ke Peminjaman**: Konversi marking menjadi pengajuan peminjaman resmi
- **Auto-Expire**: Marking otomatis kadaluarsa setelah masa berlaku habis

## Struktur Module

```
Modules/MarkingManagement/
├── Config/
│   └── config.php              # Konfigurasi module
├── Console/
│   └── Commands/
│       └── ExpireMarkingsCommand.php
├── Database/
│   ├── Migrations/
│   │   ├── 2025_12_02_100000_create_markings_table.php
│   │   └── 2025_12_02_100001_create_marking_items_table.php
│   └── Seeders/
│       ├── MarkingManagementDatabaseSeeder.php
│       ├── MarkingMenuSeeder.php
│       └── MarkingPermissionSeeder.php
├── Entities/
│   ├── Marking.php
│   └── MarkingItem.php
├── Http/
│   ├── Controllers/
│   │   └── MarkingController.php
│   └── Requests/
│       ├── ExtendMarkingRequest.php
│       ├── StoreMarkingRequest.php
│       └── UpdateMarkingRequest.php
├── Policies/
│   └── MarkingPolicy.php
├── Providers/
│   ├── MarkingManagementServiceProvider.php
│   └── RouteServiceProvider.php
├── Repositories/
│   ├── Interfaces/
│   │   └── MarkingRepositoryInterface.php
│   └── MarkingRepository.php
├── Resources/
│   └── views/
│       └── marking/
│           ├── create.blade.php
│           ├── edit.blade.php
│           ├── index.blade.php
│           └── show.blade.php
├── Routes/
│   ├── api.php
│   └── web.php
├── Services/
│   └── MarkingService.php
└── Tests/
```

## Instalasi

### 1. Jalankan Migration

```bash
php artisan migrate
```

### 2. Jalankan Seeder

```bash
php artisan db:seed --class=Modules\\MarkingManagement\\Database\\Seeders\\MarkingManagementDatabaseSeeder
```

Atau jalankan seeder individual:

```bash
# Permission seeder
php artisan db:seed --class=Modules\\MarkingManagement\\Database\\Seeders\\MarkingPermissionSeeder

# Menu seeder
php artisan db:seed --class=Modules\\MarkingManagement\\Database\\Seeders\\MarkingMenuSeeder
```

### 3. Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Permissions

Module ini menggunakan permission-based authorization:

| Permission | Deskripsi |
|------------|-----------|
| `marking.manage` | Akses manajemen marking (lihat semua marking) |
| `marking.create` | Membuat marking baru |
| `marking.override` | Override marking milik user lain |

### Default Role Assignment

- **Admin Sarpras**: `marking.manage`, `marking.create`, `marking.override`
- **Mahasiswa**: `marking.create`
- **Staff**: `marking.create`

## Routes

| Method | URI | Action | Permission |
|--------|-----|--------|------------|
| GET | `/marking` | index | marking.create |
| GET | `/marking/create` | create | marking.create |
| POST | `/marking` | store | marking.create |
| GET | `/marking/{marking}` | show | owner / marking.manage |
| GET | `/marking/{marking}/edit` | edit | owner / marking.override |
| PUT | `/marking/{marking}` | update | owner / marking.override |
| DELETE | `/marking/{marking}` | destroy | owner / marking.override |
| POST | `/marking/{marking}/convert` | convert | owner / marking.override |
| POST | `/marking/{marking}/extend` | extend | owner / marking.override |

## Status Marking

| Status | Deskripsi |
|--------|-----------|
| `active` | Marking aktif dan dapat digunakan |
| `expired` | Marking sudah melewati masa berlaku |
| `converted` | Marking sudah dikonversi menjadi peminjaman |
| `cancelled` | Marking dibatalkan oleh user |

## Konfigurasi

File: `Config/config.php`

```php
return [
    'name' => 'MarkingManagement',
    
    // Durasi default marking dalam hari
    'marking_duration_days' => 3,
    
    // Maksimal perpanjangan dalam hari
    'max_extension_days' => 7,
    
    // Jam sebelum kadaluarsa untuk warning
    'expiration_warning_hours' => 24,
];
```

## Artisan Commands

### Auto-Expire Markings

```bash
php artisan marking:expire
```

Command ini akan mengubah status marking yang sudah melewati `expires_at` menjadi `expired`. Disarankan untuk menjalankan command ini via scheduler.

### Scheduler Setup

Tambahkan di `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('marking:expire')->hourly();
}
```

## Usage

### Membuat Marking via Service

```php
use Modules\MarkingManagement\Services\MarkingService;

$markingService = app(MarkingService::class);

$marking = $markingService->createMarking([
    'event_name' => 'Rapat Organisasi',
    'prasarana_id' => 1,
    'start_datetime' => '2025-12-10 09:00:00',
    'end_datetime' => '2025-12-10 12:00:00',
    'jumlah_peserta' => 50,
    'sarana_items' => [1, 2, 3], // ID sarana
    'notes' => 'Catatan tambahan',
]);
```

### Check Conflicts

```php
$conflict = $markingService->checkConflicts([
    'prasarana_id' => 1,
    'start_datetime' => '2025-12-10 09:00:00',
    'end_datetime' => '2025-12-10 12:00:00',
]);

if ($conflict) {
    // Ada konflik dengan marking lain
    echo $conflict;
}
```

### Extend Marking

```php
$marking = $markingService->extendMarking($marking, 3); // Perpanjang 3 hari
```

### Cancel Marking

```php
$marking = $markingService->cancelMarking($marking);
```

## Testing

```bash
php artisan test --filter=MarkingManagement
```

## Dependencies

- `Modules\PrasaranaManagement` - Untuk relasi dengan Prasarana
- `Modules\SaranaManagement` - Untuk relasi dengan Sarana
- `spatie/laravel-permission` - Untuk permission management

## Changelog

### v1.0.0 (2025-12-02)
- Initial release
- Basic CRUD operations
- Permission-based authorization
- Auto-expire functionality
- Conflict checking
