# Aturan Approval Peminjaman

Dokumen ini menjelaskan aturan approval peminjaman untuk global approver, approver sarana, dan approver prasarana, termasuk perilaku level (urutan validasi) dan kasus multiple approver pada satu resource.

---

## 1. Entity dan Istilah Utama

- **Peminjaman**  
  Pengajuan peminjaman sarana/prasarana oleh user (mis. mahasiswa).

- **PeminjamanApprovalWorkflow**  
  Representasi *step approval* per approver:
  - `peminjaman_id` → peminjaman yang divalidasi.
  - `approval_type` → jenis approval:
    - `global` → approval keseluruhan peminjaman.
    - `sarana` → approval per sarana.
    - `prasarana` → approval untuk prasarana.
  - `approver_id` → user yang menjadi approver di step ini.
  - `approval_level` → urutan/hirarki validasi (1, 2, 3, ...).
  - `status` → status step approval: `pending`, `approved`, `rejected`, `overridden`.
  - `approved_at`, `rejected_at`, `overridden_at` → timestamp tindakan.

- **PeminjamanApprovalStatus**  
  Ringkasan status approval untuk satu peminjaman:
  - `overall_status`:
    - `pending`
    - `approved`
    - `partially_approved`
    - `rejected`
  - `global_approval_status`:
    - `pending`
    - `approved`
    - `rejected`

- **PeminjamanApprovalService**  
  Service yang menangani aksi approver (approve, reject, override), menghitung ulang `overall_status` dan `global_approval_status`, mengubah `peminjaman.status`, dan mengelola efek ke konflik (`konflik`) dan stok sarana pooled.

---

## 2. Arti Level Approval

- Field `approval_level` **menentukan urutan validasi**, bukan bobot.
- Aturan umum:
  - Approver di **level terendah yang masih pending** adalah pihak yang berhak memproses berikutnya.
  - Approver di level yang lebih tinggi **tidak boleh** melakukan validasi sebelum semua approver di level yang lebih rendah untuk workflow yang sama selesai (tidak `pending`).

Secara praktis:

- Untuk setiap kombinasi (`peminjaman_id`, `approval_type`, dan resource terkait kalau ada):
  - Cari level terkecil yang memiliki workflow `pending`.
  - Hanya workflow di level tersebut yang boleh di-approve/reject.

Contoh:

- Global approval:
  - Level 1: Wakil Dekan.
  - Level 2: Dekan.
  - Dekan baru boleh approve jika semua global di level 1 sudah approve/reject (tidak ada yang pending).

- Approval sarana/prasarana:
  - Level 1: Penanggung jawab sarana/prasarana.
  - Level 2: Kepala unit.
  - Kepala unit baru boleh memproses jika level 1 untuk item tersebut sudah selesai.

---

## 3. Global Approval

### 3.1. Tujuan Global Approval

Global approval berfungsi sebagai "gerbang utama" untuk pengajuan peminjaman. Tanpa global approval yang jelas, peminjaman tidak boleh dinyatakan disetujui penuh.

### 3.2. Perhitungan `global_approval_status`

Jika ada satu atau lebih workflow `approval_type = global` untuk suatu peminjaman:

- Jika **ada SATU saja** global dengan `status = rejected`:
  - `global_approval_status = rejected`.
  - `overall_status = rejected`.

- Jika **tidak ada yang rejected**, dan **minimal satu** global `approved`:
  - `global_approval_status = approved`.

- Jika semua global masih `pending`:
  - `global_approval_status = pending`.

### 3.3. Dampak ke `overall_status` dan `peminjaman.status`

- Jika **TIDAK ada workflow specific (sarana/prasarana)**:
  - Jika `global_approval_status = approved`:
    - `overall_status = approved`.
    - `peminjaman.status = STATUS_APPROVED`.
  - Jika `global_approval_status = rejected`:
    - `overall_status = rejected`.
    - `peminjaman.status = STATUS_REJECTED`.

- Jika **ADA workflow specific (sarana/prasarana)**:
  - `overall_status` dihitung dari kombinasi specific (lihat bagian 4).
  - Namun jika `global_approval_status` **bukan** `approved`, maka `overall_status` **tidak boleh** menjadi `approved`; minimal akan tetap `pending`.

**Intuisi:**

- Global approver bisa "menggagalkan" pengajuan secara keseluruhan (satu reject di global → pengajuan rejected penuh).
- Global approver **tidak menentukan detail sarana/prasarana mana yang dipakai**; itu diatur oleh specific approver.

---

## 4. Specific Approval untuk Sarana dan Prasarana

Specific approval mencakup semua workflow dengan `approval_type = sarana` atau `approval_type = prasarana`.

### 4.1. Level dan Urutan Validasi

- Untuk setiap sarana atau prasarana pada satu peminjaman:
  - Bisa memiliki satu atau beberapa approver (multiple approver pada resource yang sama).
  - Masing-masing approver punya `approval_level`.

Aturan:

- Hanya approver pada **level terendah yang masih pending** untuk sarana/prasarana tersebut yang boleh memproses.
- Approver di level lebih tinggi baru boleh memproses jika semua approver di level lebih rendah untuk resource tersebut sudah menyelesaikan (status bukan `pending`).

### 4.2. Multiple Approver pada Satu Sarana/Prasarana

- Jika ada **lebih dari satu approver** untuk satu sarana/prasarana (mis. satu Aula punya 2 approver):
  - Sarana/prasarana tersebut baru dianggap **`approved`** jika **SEMUA** workflow approver untuk resource itu berstatus `approved`.
  - Jika **ada SATU saja** approver untuk resource itu yang `rejected`:
    - Resource (sarana/prasarana) tersebut dianggap **`rejected`** (tidak boleh dipinjam),
    - Walaupun approver lain di resource yang sama `approved`.

### 4.3. Kombinasi Specific ke `overall_status`

Setelah status per sarana/prasarana ditentukan dari kombinasi approver-nya:

- Jika **semua** sarana/prasarana dalam peminjaman berakhir `approved`:
  - `overall_status` dari sisi specific = `approved`.

- Jika **semua** sarana/prasarana `rejected`:
  - `overall_status` dari sisi specific = `rejected`.

- Jika ada **campuran** (sebagian approved, sebagian rejected):
  - `overall_status = partially_approved`.

- Jika masih ada workflow specific yang `pending`:
  - `overall_status = pending`.

### 4.4. Pengaruh Global ke Specific

- Jika tidak ada global workflow:
  - `overall_status` diambil langsung dari hasil specific di atas.
  - `overall_status = approved` hanya jika semua sarana/prasarana `approved`.

- Jika ada global workflow:
  - Syarat `overall_status = approved`:
    - `global_approval_status = approved`, **dan**
    - semua sarana/prasarana `approved`.
  - Jika specific campuran (sebagian approved, sebagian rejected):
    - `overall_status = partially_approved`.

---

## 5. Status Peminjaman dan Efek ke Konflik

Perhitungan akhir dilakukan di `PeminjamanApprovalService::recalculateOverallStatus()`:

- Jika `overall_status = OVERALL_APPROVED`:
  - `peminjaman.status = STATUS_APPROVED`.
  - Event `PeminjamanStatusChanged` dikirim.
  - `resolveKonflikGroup($peminjaman, true)`:
    - Peminjaman lain dalam grup konflik yang masih `pending` → otomatis `cancelled`.
    - Field `konflik` dibersihkan untuk semua anggota.

- Jika `overall_status = OVERALL_REJECTED`:
  - `peminjaman.status = STATUS_REJECTED`.
  - Event `PeminjamanStatusChanged` dikirim.
  - `resolveKonflikGroup($peminjaman, false)`:
    - Jika tidak ada lagi anggota grup konflik yang `pending`, field `konflik` dibersihkan.

- Jika `overall_status = OVERALL_PARTIALLY_APPROVED`:
  - `peminjaman.status` tidak dipaksa menjadi approved atau rejected penuh.
  - Pengajuan dianggap **"disetujui sebagian"**:
    - Beberapa sarana/prasarana boleh dipinjam (approved).
    - Beberapa sarana/prasarana tidak boleh dipinjam (rejected).

---

## 6. Contoh Skenario

### 6.1. Global dan Specific Semua Approved

- Global:
  - Semua global approver `approved` → `global_approval_status = approved`.
- Sarana & Prasarana:
  - Semua approver untuk setiap sarana/prasarana `approved`.

Hasil:

- Semua resource approved → `overall_status = approved`.
- `peminjaman.status = STATUS_APPROVED`.
- Jika ada konflik, peminjaman lain yang pending di grup konflik otomatis `cancelled`.

### 6.2. Global Ada yang Rejected

- Satu global approver `rejected`.

Hasil:

- `global_approval_status = rejected`.
- `overall_status = rejected`.
- `peminjaman.status = STATUS_REJECTED`.
- Specific (sarana/prasarana) tidak mengubah keputusan akhir ini.

### 6.3. Specific Campuran (Disetujui Sebagian)

- Global:
  - Semua global approver `approved` → `global_approval_status = approved`.
- Sarana/Prasarana:
  - Beberapa resource approved (semua approver resource itu approve).
  - Beberapa resource rejected (ada approver resource itu yang reject).

Hasil:

- `overall_status = partially_approved`.
- Pengajuan dianggap **disetujui sebagian**:
  - Mahasiswa tetap bisa meminjam resource yang approved.
  - Resource yang rejected tidak boleh digunakan.

---

## 7. Ringkasan Singkat

- `approval_level` = **urutan validasi**. Approver di level lebih tinggi baru boleh memproses jika semua approver di level yang lebih rendah untuk workflow tersebut sudah tidak pending.
- Global approval:
  - Satu reject di global → pengajuan ditolak penuh.
  - Global harus approved agar pengajuan bisa approved penuh.
- Specific approval (sarana & prasarana):
  - Satu resource bisa punya banyak approver.
  - Resource dianggap approved hanya jika semua approver untuk resource tersebut `approved`.
  - Jika ada approver resource yang `rejected`, resource tersebut dianggap rejected.
  - Kombinasi approved/rejected antar resource menghasilkan `overall_status = approved`, `rejected`, atau `partially_approved`.
- Status `partially_approved` berarti pengajuan berjalan dengan **badge "disetujui sebagian"** dan hanya resource yang approved yang boleh dipinjam.
