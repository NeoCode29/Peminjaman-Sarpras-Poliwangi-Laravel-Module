# Rencana Implementasi Fitur Peminjaman

> **Tanggal**: 3 Desember 2025  
> **Status**: Planning  
> **Referensi**: `project_baru` → `project_fix`

---

## 📋 Ringkasan Analisa

### Resource yang Sudah Ada di `project_fix`

| Resource | Lokasi | Status |
|----------|--------|--------|
| **User Management** | `app/Models/User.php` | ✅ Lengkap |
| **Role & Permission** | `spatie/laravel-permission` | ✅ Lengkap |
| **Sarana Management** | `Modules/SaranaManagement/` | ✅ Lengkap |
| **SaranaApprover** | `Modules/SaranaManagement/Entities/SaranaApprover.php` | ✅ Lengkap |
| **SaranaUnit** | `Modules/SaranaManagement/Entities/SaranaUnit.php` | ✅ Lengkap |
| **Prasarana Management** | `Modules/PrasaranaManagement/` | ✅ Lengkap |
| **PrasaranaApprover** | `Modules/PrasaranaManagement/Entities/PrasaranaApprover.php` | ✅ Lengkap |
| **Marking Management** | `Modules/MarkingManagement/` | ✅ Lengkap |
| **Global Approvers** | `app/Models/GlobalApprover.php` | ✅ Baru selesai |
| **UKM** | `app/Models/Ukm.php` | ✅ Ada |
| **System Settings** | `app/Models/SystemSetting.php` | ✅ Lengkap |
| **Notifications** | Core | ✅ Ada |

### Permissions yang Sudah Ada (dari RolePermissionSeeder)

```php
'peminjaman' => [
    'view', 'create', 'edit', 'cancel', 
    'approve_global', 'reject_global', 
    'approve_specific', 'reject_specific', 
    'validate_pickup', 'validate_return', 
    'adjust_sarpras', 'assign_global_approver',
    'override',
],
```

---

## 🏗️ Struktur Module PeminjamanManagement

Sesuai dengan `Panduan Modules Arsitektur.md`, fitur Peminjaman akan diimplementasikan sebagai **MODULE** karena:
- Domain-specific feature
- Dapat standalone/independent
- Potential untuk di-enable/disable

### Struktur Direktori

```
Modules/
└── PeminjamanManagement/
    ├── Config/
    │   └── config.php
    ├── Database/
    │   ├── Migrations/
    │   │   ├── 2025_12_03_200100_create_peminjaman_table.php
    │   │   ├── 2025_12_03_200200_create_peminjaman_items_table.php
    │   │   ├── 2025_12_03_200300_create_peminjaman_item_units_table.php
    │   │   ├── 2025_12_03_200400_create_peminjaman_approval_status_table.php
    │   │   ├── 2025_12_03_200500_create_peminjaman_approval_workflows_table.php
    │   │   └── 2025_12_03_200600_create_user_quotas_table.php
    │   ├── Seeders/
    │   │   ├── PeminjamanManagementDatabaseSeeder.php
    │   │   └── PeminjamanMenuSeeder.php
    │   └── factories/
    │       ├── PeminjamanFactory.php
    │       ├── PeminjamanItemFactory.php
    │       └── PeminjamanApprovalWorkflowFactory.php
    ├── Entities/
    │   ├── Peminjaman.php
    │   ├── PeminjamanItem.php
    │   ├── PeminjamanItemUnit.php
    │   ├── PeminjamanApprovalStatus.php
    │   ├── PeminjamanApprovalWorkflow.php
    │   └── UserQuota.php
    ├── Http/
    │   ├── Controllers/
    │   │   ├── PeminjamanController.php
    │   │   └── PeminjamanApprovalController.php
    │   └── Requests/
    │       ├── StorePeminjamanRequest.php
    │       ├── UpdatePeminjamanRequest.php
    │       └── ApprovalActionRequest.php
    ├── Policies/
    │   └── PeminjamanPolicy.php
    ├── Providers/
    │   ├── PeminjamanManagementServiceProvider.php
    │   └── RouteServiceProvider.php
    ├── Repositories/
    │   ├── Interfaces/
    │   │   └── PeminjamanRepositoryInterface.php
    │   └── PeminjamanRepository.php
    ├── Resources/
    │   └── views/
    │       └── peminjaman/
    │           ├── index.blade.php
    │           ├── create.blade.php
    │           ├── show.blade.php
    │           └── edit.blade.php
    ├── Routes/
    │   ├── web.php
    │   └── api.php
    ├── Services/
    │   ├── PeminjamanService.php
    │   ├── PeminjamanApprovalService.php
    │   ├── SlotConflictService.php
    │   ├── PickupReturnService.php
    │   └── UserQuotaService.php
    ├── Tests/
    │   ├── Feature/
    │   └── Unit/
    ├── composer.json
    └── module.json
```

---

## 📊 Database Schema

### 1. Tabel `peminjaman`

```php
Schema::create('peminjaman', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('prasarana_id')->nullable()->constrained('prasarana')->onDelete('set null');
    $table->string('lokasi_custom', 150)->nullable();
    $table->integer('jumlah_peserta')->nullable();
    $table->foreignId('ukm_id')->nullable()->constrained('ukm')->onDelete('set null');
    $table->string('event_name');
    $table->date('start_date');
    $table->date('end_date');
    $table->time('start_time')->nullable();
    $table->time('end_time')->nullable();
    $table->enum('status', ['pending', 'approved', 'rejected', 'picked_up', 'returned', 'cancelled'])->default('pending');
    $table->string('konflik')->nullable()->comment('Kode grup konflik jadwal');
    $table->string('surat_path')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('pickup_validated_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('pickup_validated_at')->nullable();
    $table->foreignId('return_validated_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('return_validated_at')->nullable();
    $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
    $table->text('cancelled_reason')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->string('foto_pickup_path')->nullable();
    $table->string('foto_return_path')->nullable();
    $table->timestamps();

    // Indexes
    $table->index(['user_id']);
    $table->index(['prasarana_id']);
    $table->index(['status']);
    $table->index(['start_date', 'end_date']);
    $table->index(['konflik']);
});
```

### 2. Tabel `peminjaman_items`

```php
Schema::create('peminjaman_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('peminjaman_id')->constrained('peminjaman')->onDelete('cascade');
    $table->foreignId('sarana_id')->constrained('saranas')->onDelete('cascade');
    $table->unsignedInteger('qty_requested')->default(0);
    $table->unsignedInteger('qty_approved')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['peminjaman_id']);
    $table->index(['sarana_id']);
});
```

### 3. Tabel `peminjaman_item_units`

```php
Schema::create('peminjaman_item_units', function (Blueprint $table) {
    $table->id();
    $table->foreignId('peminjaman_id')->constrained('peminjaman')->onDelete('cascade');
    $table->foreignId('peminjaman_item_id')->constrained('peminjaman_items')->onDelete('cascade');
    $table->foreignId('unit_id')->constrained('sarana_units')->onDelete('cascade');
    $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('assigned_at')->nullable();
    $table->enum('status', ['active', 'released'])->default('active');
    $table->foreignId('released_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('released_at')->nullable();
    $table->timestamps();

    $table->index(['peminjaman_id']);
    $table->index(['peminjaman_item_id']);
    $table->index(['unit_id']);
    $table->index(['status']);
    $table->unique(['peminjaman_id', 'unit_id']);
});
```

### 4. Tabel `peminjaman_approval_status`

```php
Schema::create('peminjaman_approval_status', function (Blueprint $table) {
    $table->id();
    $table->foreignId('peminjaman_id')->constrained('peminjaman')->onDelete('cascade');
    $table->enum('overall_status', ['pending', 'approved', 'partially_approved', 'rejected'])->default('pending');
    $table->enum('global_approval_status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->foreignId('global_approved_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('global_approved_at')->nullable();
    $table->foreignId('global_rejected_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('global_rejected_at')->nullable();
    $table->text('global_rejection_reason')->nullable();
    $table->json('specific_approval_summary')->nullable();
    $table->timestamps();

    $table->unique(['peminjaman_id']);
    $table->index(['overall_status']);
    $table->index(['global_approval_status']);
});
```

### 5. Tabel `peminjaman_approval_workflow`

```php
Schema::create('peminjaman_approval_workflow', function (Blueprint $table) {
    $table->id();
    $table->foreignId('peminjaman_id')->constrained('peminjaman')->onDelete('cascade');
    $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
    $table->enum('approval_type', ['global', 'sarana', 'prasarana']);
    $table->foreignId('sarana_id')->nullable()->constrained('saranas')->onDelete('cascade');
    $table->foreignId('prasarana_id')->nullable()->constrained('prasarana')->onDelete('cascade');
    $table->integer('approval_level')->default(1);
    $table->enum('status', ['pending', 'approved', 'rejected', 'overridden'])->default('pending');
    $table->text('notes')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->timestamp('rejected_at')->nullable();
    $table->foreignId('overridden_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('overridden_at')->nullable();
    $table->timestamps();

    $table->index(['peminjaman_id']);
    $table->index(['approver_id']);
    $table->index(['approval_type']);
    $table->index(['status']);
});
```

### 6. Tabel `user_quotas`

```php
Schema::create('user_quotas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->integer('active_borrowings')->default(0);
    $table->integer('max_borrowings')->default(3);
    $table->timestamps();

    $table->unique(['user_id']);
});
```

---

## 🔄 Alur Peminjaman

```
┌─────────────────────────────────────────────────────────────────────┐
│                         ALUR PEMINJAMAN                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. PENGAJUAN                                                       │
│     User → Create Peminjaman → Status: PENDING                      │
│     └─ Create PeminjamanApprovalStatus (overall: pending)           │
│     └─ Create PeminjamanApprovalWorkflow (global + specific)        │
│                                                                     │
│  2. APPROVAL GLOBAL (Parallel)                                      │
│     GlobalApprover → Approve/Reject                                 │
│     └─ Update global_approval_status                                │
│     └─ Jika REJECT → Status: REJECTED                               │
│                                                                     │
│  3. APPROVAL SPECIFIC (Parallel per Sarana/Prasarana)               │
│     SaranaApprover/PrasaranaApprover → Approve/Reject               │
│     └─ Update workflow status                                       │
│     └─ Recalculate overall_status                                   │
│                                                                     │
│  4. FINAL STATUS                                                    │
│     All Approved → Status: APPROVED                                 │
│     Any Rejected → Status: REJECTED / PARTIALLY_APPROVED            │
│                                                                     │
│  5. PICKUP                                                          │
│     Admin → Validate Pickup → Status: PICKED_UP                     │
│     └─ Assign serialized units (if any)                             │
│     └─ Upload foto pickup                                           │
│                                                                     │
│  6. RETURN                                                          │
│     Admin → Validate Return → Status: RETURNED                      │
│     └─ Release serialized units                                     │
│     └─ Upload foto return                                           │
│                                                                     │
│  CANCEL (Any time before PICKED_UP)                                 │
│     User/Admin → Cancel → Status: CANCELLED                         │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🔗 Integrasi dengan Resource Existing

### 1. Integrasi dengan Sarana Module

```php
// Di Modules/SaranaManagement/Entities/Sarana.php
// Tambah relasi ke PeminjamanItem
public function peminjamanItems()
{
    return $this->hasMany(\Modules\PeminjamanManagement\Entities\PeminjamanItem::class, 'sarana_id');
}
```

### 2. Integrasi dengan Prasarana Module

```php
// Di Modules/PrasaranaManagement/Entities/Prasarana.php
// Tambah relasi ke Peminjaman
public function peminjaman()
{
    return $this->hasMany(\Modules\PeminjamanManagement\Entities\Peminjaman::class, 'prasarana_id');
}
```

### 3. Integrasi dengan GlobalApprover

```php
// GlobalApprover sudah ada di app/Models/GlobalApprover.php
// Akan digunakan untuk create workflow global approval
```

### 4. Integrasi dengan User

```php
// Di app/Models/User.php
// Tambah relasi ke Peminjaman
public function peminjaman()
{
    return $this->hasMany(\Modules\PeminjamanManagement\Entities\Peminjaman::class, 'user_id');
}
```

---

## 📝 Langkah Implementasi

### Phase 1: Database & Models
1. [ ] Buat module dengan `php artisan module:make PeminjamanManagement`
2. [ ] Buat migrations untuk semua tabel
3. [ ] Buat Entities (Models) dengan relasi dan scopes
4. [ ] Buat Factories untuk testing

### Phase 2: Repository & Service Layer
1. [ ] Buat Repository Interface dan Implementation
2. [ ] Buat PeminjamanService (CRUD, business logic)
3. [ ] Buat PeminjamanApprovalService (workflow approval)
4. [ ] Buat SlotConflictService (cek bentrok jadwal)
5. [ ] Buat PickupReturnService (validasi pickup/return)
6. [ ] Buat UserQuotaService (manajemen kuota)

### Phase 3: Policy & Form Requests
1. [ ] Buat PeminjamanPolicy dengan permission-based authorization
2. [ ] Buat Form Requests untuk validasi input

### Phase 4: Controllers & Routes
1. [ ] Buat PeminjamanController (resource methods)
2. [ ] Buat PeminjamanApprovalController (approval actions)
3. [ ] Setup routes dengan middleware

### Phase 5: Views
1. [ ] Buat views untuk index, create, show, edit
2. [ ] Buat komponen approval workflow UI
3. [ ] Buat komponen unit assignment UI

### Phase 6: Testing
1. [ ] Unit tests untuk Services
2. [ ] Unit tests untuk Repositories
3. [ ] Unit tests untuk Policies
4. [ ] Feature tests untuk Controllers

### Phase 7: Integration
1. [ ] Update relasi di Sarana, Prasarana, User models
2. [ ] Buat menu seeder
3. [ ] Update dokumentasi

---

## ⚠️ Penyesuaian dari `project_baru`

| Aspek | project_baru | project_fix |
|-------|--------------|-------------|
| **Arsitektur** | Monolithic (app/) | Module (Modules/PeminjamanManagement/) |
| **Sarana Model** | `App\Models\Sarana` | `Modules\SaranaManagement\Entities\Sarana` |
| **Prasarana Model** | `App\Models\Prasarana` | `Modules\PrasaranaManagement\Entities\Prasarana` |
| **SaranaApprover** | `App\Models\SaranaApprover` | `Modules\SaranaManagement\Entities\SaranaApprover` |
| **PrasaranaApprover** | `App\Models\PrasaranaApprover` | `Modules\PrasaranaManagement\Entities\PrasaranaApprover` |
| **GlobalApprover** | `App\Models\GlobalApprover` | `App\Models\GlobalApprover` (sama) |
| **Repository Pattern** | Tidak ada | Wajib ada (sesuai panduan) |
| **Service Layer** | Ada tapi tidak konsisten | Wajib ada dengan DI |
| **Frontend** | Bootstrap + jQuery | Vanilla CSS + JS |

---

## 🎯 Prioritas Implementasi

1. **HIGH**: Migrations, Models, Repository, Service
2. **MEDIUM**: Policy, Form Requests, Controller, Routes
3. **LOW**: Views, Testing, Documentation

---

**Catatan**: Implementasi akan mengikuti pattern yang sudah ada di `project_fix` untuk konsistensi arsitektur.
