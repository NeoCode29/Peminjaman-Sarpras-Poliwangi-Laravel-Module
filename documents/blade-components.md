# Blade Components Documentation

## Overview

Komponen Blade reusable telah dibuat untuk implementasi DRY (Don't Repeat Yourself) pada aplikasi. Semua komponen menggunakan design tokens dari `documents/style-guide.json` dan CSS global di `resources/css/app.css`.

## Demo Page

Kunjungi `/components-demo` untuk melihat semua komponen dalam aksi.

---

## Button Component

### Lokasi
`resources/views/components/button.blade.php`

### Props
- `type`: string (default: `'button'`) - Type HTML button
- `variant`: string (default: `'primary'`) - Varian tombol
  - `primary`, `secondary`, `danger`, `warning`, `info`
  - `outline-primary`, `outline-secondary`, `outline-danger`, `outline-success`, `outline-warning`, `outline-info`
- `size`: string (default: `'default'`) - Ukuran tombol
  - `default`, `sm`, `tablet`, `mobile`
- `block`: boolean (default: `false`) - Full width button
- `loading`: boolean (default: `false`) - Loading state
- `disabled`: boolean (default: `false`) - Disabled state
- `icon`: string (default: `null`) - Nama komponen Blade Icon

### Contoh Penggunaan

```blade
{{-- Basic --}}
<x-button variant="primary">Simpan</x-button>

{{-- With icon --}}
<x-button variant="primary" icon="heroicon-o-plus">Tambah User</x-button>

{{-- Loading state --}}
<x-button variant="primary" :loading="true">Menyimpan...</x-button>

{{-- Block button --}}
<x-button variant="secondary" :block="true">Batal</x-button>

{{-- Small button --}}
<x-button variant="outline-danger" size="sm" icon="heroicon-o-trash">Hapus</x-button>
```

---

## Input Text Component

### Lokasi
`resources/views/components/input/text.blade.php`

### Props
- `label`: string - Label input
- `name`: string - Name attribute
- `id`: string (default: `$name`) - ID attribute
- `type`: string (default: `'text'`) - Type input (text, email, password, number, date)
- `placeholder`: string - Placeholder text
- `helper`: string - Helper text
- `required`: boolean (default: `false`)
- `badge`: string - Badge label (mis. "Required")
- `icon`: string - Nama komponen Blade Icon
- `withToggle`: boolean (default: `false`) - Password visibility toggle
- `error`: string - Error message
- `valid`: boolean (default: `false`) - Valid state

### Contoh Penggunaan

```blade
{{-- Basic text input --}}
<x-input.text 
    name="name" 
    label="Nama Lengkap"
    placeholder="Masukkan nama"
    :value="old('name')"
    :error="$errors->first('name')"
/>

{{-- Email with icon --}}
<x-input.text 
    type="email"
    name="email" 
    label="Email" 
    icon="heroicon-o-envelope"
    placeholder="user@example.com"
/>

{{-- Password with toggle --}}
<x-input.text 
    type="password"
    name="password" 
    label="Password" 
    :with-toggle="true"
    placeholder="Minimal 8 karakter"
    :error="$errors->first('password')"
/>

{{-- Number input --}}
<x-input.text 
    type="number"
    name="quantity" 
    label="Jumlah"
    min="0"
    step="1"
/>

{{-- Date input --}}
<x-input.text 
    type="date"
    name="return_date" 
    label="Tanggal Pengembalian"
/>
```

---

## Select Component

### Lokasi
`resources/views/components/input/select.blade.php`

### Props
- `label`: string - Label select
- `name`: string - Name attribute
- `id`: string (default: `$name`)
- `placeholder`: string (default: `'Pilih...'`)
- `helper`: string - Helper text
- `required`: boolean (default: `false`)
- `badge`: string - Badge label
- `error`: string - Error message

### Contoh Penggunaan

```blade
<x-input.select 
    name="role_id" 
    label="Role" 
    placeholder="Pilih Role"
    :error="$errors->first('role_id')"
>
    @foreach($roles as $role)
        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
            {{ $role->name }}
        </option>
    @endforeach
</x-input.select>
```

---

## Textarea Component

### Lokasi
`resources/views/components/input/textarea.blade.php`

### Props
- `label`: string
- `name`: string
- `id`: string (default: `$name`)
- `placeholder`: string
- `helper`: string
- `required`: boolean (default: `false`)
- `badge`: string
- `error`: string
- `rows`: integer (default: `4`)

### Contoh Penggunaan

```blade
<x-input.textarea 
    name="notes" 
    label="Catatan" 
    placeholder="Tambahkan catatan"
    helper="Maksimal 200 karakter"
    :rows="4"
/>
```

---

## Checkbox Component

### Lokasi
`resources/views/components/input/checkbox.blade.php`

### Props
- `name`: string
- `id`: string (default: `$name`)
- `value`: string (default: `'1'`)
- `checked`: boolean (default: `false`)
- `disabled`: boolean (default: `false`)
- `label`: string
- `description`: string

### Contoh Penggunaan

```blade
<x-input.checkbox 
    name="notifications" 
    label="Aktifkan notifikasi email"
    description="Kami akan mengirimkan ringkasan harian"
    :checked="true"
/>
```

---

## Radio Component

### Lokasi
`resources/views/components/input/radio.blade.php`

### Props
- `name`: string
- `id`: string
- `value`: string
- `checked`: boolean (default: `false`)
- `disabled`: boolean (default: `false`)
- `label`: string
- `description`: string

### Contoh Penggunaan

```blade
<x-input.radio 
    name="loan_type" 
    id="loan_temporary"
    value="temporary"
    label="Peminjaman Sementara"
    description="Durasi maksimal 7 hari kerja"
    :checked="true"
/>

<x-input.radio 
    name="loan_type" 
    id="loan_permanent"
    value="permanent"
    label="Peminjaman Tetap"
    description="Disetujui melalui evaluasi kebutuhan"
/>
```

---

## File Upload Component

### Lokasi
`resources/views/components/input/file.blade.php`

### Props
- `label`: string
- `name`: string
- `id`: string (default: `$name`)
- `helper`: string
- `required`: boolean (default: `false`)
- `multiple`: boolean (default: `false`)
- `accept`: string
- `buttonText`: string (default: `'Upload File'`)
- `buttonIcon`: string (default: `'heroicon-o-arrow-up-tray'`)

### Contoh Penggunaan

```blade
{{-- Single file --}}
<x-input.file 
    name="document" 
    label="Upload Dokumen"
    helper="Unggah bukti pendukung (PDF, maks 2MB)"
    accept=".pdf"
/>

{{-- Multiple files --}}
<x-input.file 
    name="attachments" 
    label="Upload Lampiran"
    button-text="Upload Lampiran"
    :multiple="true"
    accept="image/*,.pdf"
/>
```

---

## Card Component

### Lokasi
`resources/views/components/card.blade.php`

### Props
- `title`: string - Judul card
- `description`: string - Deskripsi singkat di bawah judul
- `icon`: string - Nama komponen Blade Icon untuk ikon header
- `variant`: string (default: `default`) - Varian tema card (`default`, `info`, `warning`, `danger`, `success`)
- `headerActions`: slot - Area di sisi kanan header (contoh: tombol tindakan cepat)
- `footer`: slot - Konten footer (umumnya tombol atau metadata)

### Slots
- `default`: Konten utama card
- `headerActions`: Tindakan tambahan di kanan header, gunakan `<x-slot:headerActions>`
- `footer`: Konten footer, gunakan `<x-slot:footer>`

### Contoh Penggunaan

```blade
{{-- Card dasar --}}
<x-card title="Ringkasan Peminjaman" description="Total permohonan menunggu persetujuan.">
    <p>Ada 12 permohonan baru yang perlu dicek hari ini.</p>

    <x-slot:headerActions>
        <x-button variant="outline-primary" size="sm" icon="heroicon-o-arrow-path">Refresh</x-button>
    </x-slot:headerActions>

    <x-slot:footer>
        <x-button variant="primary" size="sm">Kelola</x-button>
        <x-button variant="secondary" size="sm">Lihat Detail</x-button>
    </x-slot:footer>
</x-card>

{{-- Card dengan ikon & varian status --}}
<x-card 
    icon="heroicon-o-exclamation-triangle"
    title="Pengembalian Terlambat"
    description="Ada 3 aset terlambat lebih dari 7 hari."
    variant="warning"
>
    <p>Segera hubungi peminjam untuk mengatur pengembalian.</p>
</x-card>

{{-- Card detail list --}}
<x-card title="Detail Peminjaman">
    <div class="c-card__detail-list">
        <x-card.detail-item label="Kode" value="PMN-2025-0012" />
        <x-card.detail-item label="Status" value="Menunggu Persetujuan" />
        <x-card.detail-item label="Tanggal" value="09 Nov 2025" />
    </div>
</x-card>

{{-- Card form --}}
<x-card title="Formulir Peminjaman" class="c-card--form">
    <form class="c-card-form">
        <div class="c-card__form-grid">
            <x-input.text name="borrower_name" label="Nama Peminjam" />
            <x-input.select name="asset_type" label="Jenis Sarpras">
                <option value="">Pilih jenis</option>
            </x-input.select>
            <x-input.text type="date" name="borrow_date" label="Tanggal Pinjam" />
            <x-input.text type="date" name="return_date" label="Tanggal Kembali" />
        </div>

        <x-input.textarea name="purpose" label="Tujuan" :rows="3" />
    </form>

    <x-slot:footer>
        <x-button variant="primary" size="sm">Simpan</x-button>
        <x-button variant="secondary" size="sm">Batal</x-button>
    </x-slot:footer>
</x-card>

### Sub-Component: Detail Item

- Lokasi: `resources/views/components/card/detail-item.blade.php`
- Gunakan untuk menampilkan pasangan label-nilai dalam card detail list.

```blade
<x-card.detail-item label="Kode" value="PMN-2025-0012" />
```

---

## Section Component

### Lokasi
`resources/views/components/section.blade.php`

### Props
- `title`: string - Judul section
- `description`: string - Deskripsi singkat section
- `variant`: string (default: `default`) - Varian tampilan (`default`, `flush`, `muted`)
- `footer`: slot - Konten footer section (mis. tombol)

### Slots
- `default`: Konten utama section
- `headerActions`: Aksi tambahan di header (mis. tombol Reset/Filter)
- `meta`: Informasi singkat di samping judul (mis. badge jumlah item)
- `footer`: Konten footer, gunakan `<x-slot:footer>`

### Contoh Penggunaan

```blade
{{-- Section filter --}}
<x-section 
    title="Filter Peminjaman" 
    description="Atur parameter pencarian sebelum memproses permohonan."
>
    <x-slot:headerActions>
        <x-button variant="outline-primary" size="sm">Reset</x-button>
        <x-button variant="primary" size="sm">Terapkan</x-button>
    </x-slot:headerActions>

    <div class="c-card__form-grid">
        <x-input.select name="status" label="Status">
            <option value="">Semua status</option>
        </x-input.select>
        <x-input.text type="date" name="start_date" label="Tanggal Mulai" />
        <x-input.text type="date" name="end_date" label="Tanggal Akhir" />
        <x-input.text name="keyword" label="Kata Kunci" />
    </div>

    <x-slot:footer>
        <x-button variant="secondary" size="sm">Reset</x-button>
        <x-button variant="primary" size="sm">Cari</x-button>
    </x-slot:footer>
</x-section>

{{-- Section muted/info --}}
<x-section 
    title="Info Pengingat" 
    description="Bagikan catatan penting untuk tim layanan."
    variant="muted"
>
    <p>Pastikan semua peminjam mengunggah bukti penggunaan ruangan sebelum tanggal pengembalian.</p>
</x-section>
```

---

## Layout Helpers

### c-input-grid
Grid layout untuk input fields

```blade
<div class="c-input-grid">
    <x-input.text name="first_name" label="Nama Depan" />
    <x-input.text name="last_name" label="Nama Belakang" />
</div>
```

### c-button-group
Group layout untuk buttons

```blade
<div class="c-button-group">
    <x-button variant="primary" type="submit">Simpan</x-button>
    <x-button variant="secondary">Batal</x-button>
</div>
```

### c-choice-stack
Stack layout untuk checkbox/radio

```blade
<div class="c-choice-stack">
    <x-input.checkbox name="opt1" label="Option 1" />
    <x-input.checkbox name="opt2" label="Option 2" />
</div>
```

---

## Migration Strategy

### Fase 1: Test Components (CURRENT)
1. Kunjungi `/components-demo` untuk verifikasi semua komponen
2. Test interaksi (password toggle, file preview, dll)
3. Test responsive behavior

### Fase 2: Refactor Priority Pages
1. `users/create.blade.php` - Form user baru
2. `users/edit.blade.php` - Form edit user
3. `roles/create.blade.php` - Form role baru
4. `roles/edit.blade.php` - Form edit role

### Fase 3: Refactor Index Pages
1. `users/index.blade.php`
2. `roles/index.blade.php`
3. `dashboard.blade.php`

### Fase 4: Cleanup & Documentation
1. Hapus inline styles yang tidak dipakai
2. Update dokumentasi project
3. Create pull request template

---

## Best Practices

✅ **DO:**
- Gunakan komponen untuk semua form baru
- Pass Laravel validation errors via `:error="$errors->first('field')"`
- Gunakan `:value="old('field')"` untuk old input
- Merge additional attributes dengan `{{ $attributes }}`

❌ **DON'T:**
- Hardcode style inline di view
- Duplikasi HTML yang sudah ada komponennya
- Ignore error states dan validation

---

## JavaScript Dependencies

Komponen berikut membutuhkan JS di `resources/js/app.js`:

1. **Password Toggle**: Otomatis aktif untuk input dengan `data-password-field`
2. **File Preview**: Otomatis aktif untuk input dengan `data-file-input`

Pastikan `@vite(['resources/css/app.css', 'resources/js/app.js'])` ada di layout.
