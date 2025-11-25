# Dokumentasi Komponen UI

Dokumentasi lengkap untuk sistem komponen Blade UI yang dibangun dengan CSS vanilla dan JavaScript.

## Daftar Komponen

- [Alert](#alert)
- [Badge](#badge)
- [Breadcrumb](#breadcrumb)
- [Button](#button)
- [Calendar Dashboard](#calendar-dashboard)
- [Card](#card)
- [Detail Section](#detail-section)
- [Detail List](#detail-list)
- [Divider](#divider)
- [Dropdown](#dropdown)
- [Empty State](#empty-state)
- [Form Group](#form-group)
- [Form Section](#form-section)
- [Input](#input)
- [Modal](#modal)
- [Pagination](#pagination)
- [Spinner](#spinner)
- [Stat Card](#stat-card)
- [Table](#table)
- [Tabs](#tabs)
- [Toast](#toast)

---

## Alert

Komponen untuk menampilkan pesan pemberitahuan kepada user.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `type` | string | `'info'` | Jenis alert: `info`, `success`, `warning`, `danger` |
| `title` | string | `null` | Judul alert (opsional) |
| `dismissible` | boolean | `false` | Apakah bisa ditutup |
| `icon` | string | `null` | Icon component (gunakan default jika null) |

### Contoh Penggunaan

```blade
<x-alert type="success" title="Berhasil!" dismissible>
    Data berhasil disimpan.
</x-alert>

<x-alert type="danger" :dismissible="true">
    Terjadi kesalahan saat memproses data.
</x-alert>
```

---

## Badge

Komponen untuk menampilkan label status atau kategori.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `variant` | string | `'default'` | Warna badge: `default`, `primary`, `success`, `warning`, `danger`, `info` |
| `size` | string | `'default'` | Ukuran: `sm`, `default`, `lg` |
| `rounded` | boolean | `false` | Bentuk lebih rounded |
| `dot` | boolean | `false` | Tampilkan dot indicator |

### Contoh Penggunaan

```blade
<x-badge variant="success">Aktif</x-badge>
<x-badge variant="danger" size="sm">Nonaktif</x-badge>
<x-badge variant="primary" :rounded="true" :dot="true">Online</x-badge>
```

---

## Breadcrumb

Komponen navigasi breadcrumb.

### Props (Breadcrumb)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `separator` | string | `'chevron'` | Separator: `chevron`, `slash`, `arrow` |

### Props (Breadcrumb Item)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `href` | string | `null` | URL link |
| `active` | boolean | `false` | Item aktif (current page) |
| `icon` | string | `null` | Icon component |

### Contoh Penggunaan

```blade
<x-breadcrumb separator="chevron">
    <x-breadcrumb.item href="/" icon="heroicon-o-home">Home</x-breadcrumb.item>
    <x-breadcrumb.item href="/users">Users</x-breadcrumb.item>
    <x-breadcrumb.item :active="true">Detail</x-breadcrumb.item>
</x-breadcrumb>
```

---

## Button

Komponen tombol dengan berbagai varian.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `type` | string | `'button'` | HTML button type |
| `variant` | string | `'primary'` | Varian: `primary`, `secondary`, `danger`, dll |
| `size` | string | `'default'` | Ukuran: `sm`, `default`, `tablet`, `mobile` |
| `block` | boolean | `false` | Full width |
| `loading` | boolean | `false` | State loading |
| `disabled` | boolean | `false` | Disabled state |
| `icon` | string | `null` | Icon component |

### Contoh Penggunaan

```blade
<x-button variant="primary">Simpan</x-button>
<x-button variant="secondary" icon="heroicon-o-plus">Tambah</x-button>
<x-button variant="danger" :loading="true">Processing...</x-button>
```

---

## Calendar Dashboard

Kalender dashboard dengan **two-column layout** untuk menampilkan events/agenda dengan panel detail interaktif. Cocok untuk dashboard dan halaman overview.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | string | `'Kalender'` | Judul untuk panel detail |
| `apiUrl` | string | `null` | URL API untuk load events (opsional) |

### Fitur

- ✅ **Two-column layout** - Kalender grid (kiri) + Detail panel (kanan)
- ✅ **Event indicators** - Tanggal dengan event ditandai dengan badge count
- ✅ **Interactive detail** - Klik tanggal untuk lihat detail events
- ✅ **Navigation** - Prev/Next month
- ✅ **Highlight today** - Hari ini dengan dot indicator
- ✅ **Demo data** - Built-in demo events jika tanpa API
- ✅ **Responsive** - Stack vertikal di mobile

### Data Format API

Jika menggunakan `apiUrl`, API harus return array events dengan format:

```javascript
[
    {
        "date": "2025-11-20",  // YYYY-MM-DD
        "title": "Event Title",
        "time": "09:00 - 12:00",  // optional
        "location": "Room 301",    // optional
        "description": "...",      // optional
        "url": "/link/to/detail"   // optional
    }
]
```

### Contoh Penggunaan

```blade
<!-- Dengan demo data (tanpa API) -->
<x-calendar-dashboard title="Peminjaman" />

<!-- Dengan API -->
<x-calendar-dashboard 
    title="Agenda" 
    apiUrl="/api/calendar/events"
/>
```

### JavaScript API

```javascript
// API akan dipanggil otomatis dengan query params:
// GET /api/calendar/events?start=2025-11-01&end=2025-11-30
```

---

## Card

Komponen card container.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | string | `null` | Judul card |
| `description` | string | `null` | Deskripsi card |
| `icon` | string | `null` | Icon component |
| `variant` | string | `'default'` | Varian: `default`, `info`, `warning`, `danger`, `success` |
| `layout` | string | `'default'` | Layout: `default`, `centered` |

### Contoh Penggunaan

```blade
<x-card title="Data Pengguna" description="Daftar semua pengguna">
    <!-- Card content -->
</x-card>

<x-card variant="info" icon="heroicon-o-information-circle">
    <x-slot:headerActions>
        <x-button variant="secondary" size="sm">Action</x-button>
    </x-slot:headerActions>
    
    <!-- Card content -->
    
    <x-slot:footer>
        <x-button>OK</x-button>
    </x-slot:footer>
</x-card>
```

---

## Detail Section

Komponen section dengan border, header, dan footer (opsional) untuk menampilkan detail data. Mirip dengan Form Group tapi untuk display data.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | string | `null` | Judul section |
| `description` | string | `null` | Deskripsi section |
| `icon` | string | `null` | Icon component untuk header |

### Slots

- `slot` (default) - Konten utama section
- `headerActions` - Tombol atau aksi di header (kanan)
- `footer` - Footer section (opsional)

### Contoh Penggunaan

```blade
<!-- Dengan header, content, dan footer -->
<x-detail-section 
    title="Informasi Pengguna" 
    description="Detail lengkap informasi pengguna"
    icon="heroicon-o-user-circle"
>
    <x-slot:headerActions>
        <x-button size="sm" variant="secondary" icon="heroicon-o-pencil">
            Edit
        </x-button>
    </x-slot:headerActions>

    <x-detail-list>
        <x-detail-item label="Nama Lengkap">John Doe</x-detail-item>
        <x-detail-item label="Email">john.doe@example.com</x-detail-item>
        <x-detail-item label="Status">
            <x-badge variant="success">Aktif</x-badge>
        </x-detail-item>
    </x-detail-list>

    <x-slot:footer>
        <x-button variant="secondary" size="sm">Reset Password</x-button>
        <x-button variant="danger" size="sm">Nonaktifkan Akun</x-button>
    </x-slot:footer>
</x-detail-section>

<!-- Tanpa footer (lebih sederhana) -->
<x-detail-section title="Data Akademik" icon="heroicon-o-academic-cap">
    <x-detail-list>
        <x-detail-item label="NIM">2024101234</x-detail-item>
        <x-detail-item label="Program Studi">Teknik Informatika</x-detail-item>
        <x-detail-item label="IPK">3.75</x-detail-item>
    </x-detail-list>
</x-detail-section>
```

---

## Detail List

Komponen untuk menampilkan data detail dalam format list dengan **label di kiri dan nilai di kanan**, dipisahkan oleh **divider dotted**.

### Props (Detail List)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `variant` | string | `'default'` | Style: `default`, `bordered`, `striped` |

### Props (Detail Item)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | `null` | Label item (muncul di kiri) |
| `icon` | string | `null` | Icon component |

### Layout

- Label akan muncul di **kiri** (min-width: 150px)
- Value akan muncul di **kanan** (text-align: right)
- Setiap item dipisahkan dengan **border dotted**
- Item terakhir tidak memiliki border
- Responsive: di mobile akan stack vertikal

### Contoh Penggunaan

```blade
<!-- Default -->
<x-detail-list>
    <x-detail-item label="Nama Lengkap" icon="heroicon-o-user">
        John Doe
    </x-detail-item>
    <x-detail-item label="Email" icon="heroicon-o-envelope">
        john.doe@example.com
    </x-detail-item>
    <x-detail-item label="Nomor Telepon" icon="heroicon-o-phone">
        +62 812-3456-7890
    </x-detail-item>
    <x-detail-item label="Status">
        <x-badge variant="success">Aktif</x-badge>
    </x-detail-item>
</x-detail-list>

<!-- Dengan border -->
<x-detail-list variant="bordered">
    <x-detail-item label="NIM">2024101234</x-detail-item>
    <x-detail-item label="Program Studi">Teknik Informatika</x-detail-item>
    <x-detail-item label="Fakultas">Fakultas Teknik</x-detail-item>
</x-detail-list>

<!-- Dengan striped (background alternating) -->
<x-detail-list variant="striped">
    <x-detail-item label="Kota">Jakarta</x-detail-item>
    <x-detail-item label="Provinsi">DKI Jakarta</x-detail-item>
    <x-detail-item label="Kode Pos">12345</x-detail-item>
</x-detail-list>
```

---

## Divider

Komponen pembatas visual.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `text` | string | `null` | Text di tengah divider |
| `orientation` | string | `'horizontal'` | Orientasi: `horizontal`, `vertical` |

### Contoh Penggunaan

```blade
<x-divider />
<x-divider text="atau" />
<x-divider orientation="vertical" />
```

---

## Dropdown

Komponen menu dropdown.

### Props (Dropdown)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `align` | string | `'left'` | Alignment: `left`, `right`, `center` |
| `width` | string | `'default'` | Width: `sm`, `default`, `md`, `lg`, `full` |

### Props (Dropdown Item)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `href` | string | `null` | URL (render as link) |
| `icon` | string | `null` | Icon component |
| `active` | boolean | `false` | Active state |
| `danger` | boolean | `false` | Danger styling |

### Contoh Penggunaan

```blade
<x-dropdown align="right">
    <x-slot:trigger>
        <x-button>Menu</x-button>
    </x-slot:trigger>
    
    <x-dropdown.item href="/profile" icon="heroicon-o-user">Profile</x-dropdown.item>
    <x-dropdown.item href="/settings">Settings</x-dropdown.item>
    <x-dropdown.divider />
    <x-dropdown.item :danger="true">Logout</x-dropdown.item>
</x-dropdown>
```

---

## Empty State

Komponen untuk menampilkan state kosong.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `icon` | string | `'heroicon-o-inbox'` | Icon component |
| `title` | string | `'Tidak ada data'` | Judul |
| `description` | string | `null` | Deskripsi |

### Contoh Penggunaan

```blade
<x-empty-state 
    icon="heroicon-o-users" 
    title="Belum ada pengguna"
    description="Mulai dengan menambahkan pengguna pertama Anda"
>
    <x-slot:action>
        <x-button variant="primary">Tambah Pengguna</x-button>
    </x-slot:action>
</x-empty-state>
```

---

## Form Group

Komponen container untuk mengelompokkan beberapa section form dengan style konsisten seperti Detail Section.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | string | `null` | Judul form group |
| `description` | string | `null` | Deskripsi form group |
| `icon` | string | `null` | Icon component untuk header |

### Slots

- `slot` (default) - Konten form sections
- `headerActions` - Tombol atau aksi di header (kanan)
- `footer` - Footer untuk form actions (opsional)

### Contoh Penggunaan

```blade
<!-- Dengan icon, header actions, dan footer -->
<x-form-group 
    title="Informasi Pribadi" 
    description="Masukkan data pribadi Anda."
    icon="heroicon-o-user"
>
    <x-slot:headerActions>
        <x-button size="sm" variant="secondary" icon="heroicon-o-arrow-path">
            Reset
        </x-button>
    </x-slot:headerActions>

    <x-form-section title="Data Diri" :required="true">
        <x-input.text name="name" label="Nama" :required="true" />
        <x-input.text name="email" label="Email" :required="true" />
    </x-form-section>
    
    <x-form-section title="Alamat">
        <x-input.text name="address" label="Alamat Lengkap" />
    </x-form-section>
    
    <x-slot:footer>
        <x-button variant="secondary">Batal</x-button>
        <x-button variant="primary">Simpan</x-button>
    </x-slot:footer>
</x-form-group>

<!-- Form sederhana tanpa icon dan header actions -->
<x-form-group title="Data Tambahan">
    <x-form-section title="Informasi Lainnya">
        <x-input.text name="notes" label="Catatan" />
    </x-form-section>
    
    <x-slot:footer>
        <x-button variant="primary">Simpan</x-button>
    </x-slot:footer>
</x-form-group>
```

---

## Form Section

Komponen untuk membagi form menjadi section-section yang terorganisir.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | string | `null` | Judul section |
| `description` | string | `null` | Deskripsi section |
| `required` | boolean | `false` | Tampilkan tanda required (*) |

### Contoh Penggunaan

```blade
<x-form-section 
    title="Data Diri" 
    description="Informasi identitas utama." 
    :required="true"
>
    <x-input.text name="full_name" label="Nama Lengkap" :required="true" />
    <x-input.text type="email" name="email" label="Email" :required="true" />
    <x-input.text name="phone" label="Nomor Telepon" />
</x-form-section>

<!-- Grid layout untuk multiple inputs dalam satu baris -->
<x-form-section title="Alamat">
    <x-input.text name="street" label="Alamat Lengkap" />
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <x-input.select name="province" label="Provinsi">
            <option>DKI Jakarta</option>
        </x-input.select>
        <x-input.select name="city" label="Kota">
            <option>Jakarta Selatan</option>
        </x-input.select>
        <x-input.text name="postal_code" label="Kode Pos" />
    </div>
</x-form-section>
```

---

## Input

Komponen input form dengan berbagai tipe.

### Input Text

```blade
<x-input.text 
    name="name" 
    label="Nama Lengkap" 
    placeholder="Masukkan nama"
    :required="true"
    helper="Nama sesuai KTP"
/>

<x-input.text 
    type="password" 
    name="password" 
    label="Password" 
    :withToggle="true"
/>
```

### Input Select

```blade
<x-input.select name="role" label="Role">
    <option value="">Pilih Role</option>
    <option value="admin">Admin</option>
    <option value="user">User</option>
</x-input.select>
```

### Input Checkbox

```blade
<x-input.checkbox name="agree" label="Setuju dengan syarat dan ketentuan" />
```

### Input Radio

```blade
<x-input.radio name="gender" value="L" label="Laki-laki" />
<x-input.radio name="gender" value="P" label="Perempuan" />
```

---

## Modal

Komponen dialog modal.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `id` | string | auto | Modal ID |
| `title` | string | `null` | Judul modal |
| `size` | string | `'default'` | Ukuran: `sm`, `default`, `lg`, `xl`, `fullscreen` |
| `closable` | boolean | `true` | Bisa ditutup |
| `staticBackdrop` | boolean | `false` | Tidak bisa ditutup dengan click backdrop |

### Contoh Penggunaan

```blade
<x-modal id="confirmModal" title="Konfirmasi" size="sm">
    <p>Apakah Anda yakin ingin menghapus data ini?</p>
    
    <x-slot:footer>
        <x-button variant="secondary" data-modal-close>Batal</x-button>
        <x-button variant="danger">Hapus</x-button>
    </x-slot:footer>
</x-modal>

<!-- Trigger modal -->
<x-button onclick="document.getElementById('confirmModal').open()">
    Buka Modal
</x-button>
```

### JavaScript API

```javascript
const modal = document.getElementById('confirmModal');
modal.open();  // Buka modal
modal.close(); // Tutup modal
```

---

## Pagination

Komponen pagination (sudah ada sebelumnya).

---

## Spinner

Komponen loading spinner.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `size` | string | `'default'` | Ukuran: `sm`, `default`, `lg` |
| `variant` | string | `'primary'` | Warna: `primary`, `secondary`, `white` |

### Contoh Penggunaan

```blade
<x-spinner />
<x-spinner size="lg" variant="white" />
```

---

## Stat Card

Komponen card statistik dengan icon dan value untuk menampilkan metrics/KPI di dashboard. Mirip dengan snapshot card di dashboard.

### Props (Stat Card)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | `null` | Label/nama metric |
| `value` | string | `null` | Nilai metric |
| `icon` | string | `null` | Icon component |
| `variant` | string | `'primary'` | Variant warna: `primary`, `success`, `warning`, `info`, `danger`, `secondary`, `purple` |

### Props (Stat Grid)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `columns` | integer | `4` | Jumlah kolom: `2`, `3`, atau `4` |

### Fitur

- ✅ **Gradient icon** - Icon dengan gradient background
- ✅ **Hover effect** - Transform up + shadow saat hover
- ✅ **Responsive grid** - Auto-fit dengan min-width
- ✅ **7 Variant warna** - Primary, success, warning, info, danger, secondary, purple
- ✅ **Large value display** - Font besar untuk value (28px)
- ✅ **Uppercase label** - Label dengan letter-spacing

### Contoh Penggunaan

```blade
<!-- Grid 4 columns untuk dashboard metrics -->
<x-stat-grid :columns="4">
    <x-stat-card 
        label="Total Pengajuan" 
        value="245" 
        icon="heroicon-o-clipboard-document-list"
        variant="primary"
    />
    
    <x-stat-card 
        label="Pending" 
        value="32" 
        icon="heroicon-o-clock"
        variant="warning"
    />
    
    <x-stat-card 
        label="Aktif" 
        value="58" 
        icon="heroicon-o-arrow-trending-up"
        variant="info"
    />
    
    <x-stat-card 
        label="Selesai" 
        value="155" 
        icon="heroicon-o-check-circle"
        variant="success"
    />
</x-stat-grid>

<!-- Grid 3 columns -->
<x-stat-grid :columns="3">
    <x-stat-card 
        label="Total User" 
        value="1,234" 
        icon="heroicon-o-users"
        variant="purple"
    />
    
    <x-stat-card 
        label="Ditolak" 
        value="12" 
        icon="heroicon-o-x-circle"
        variant="danger"
    />
    
    <x-stat-card 
        label="Growth" 
        value="+15.3%" 
        icon="heroicon-o-arrow-trending-up"
        variant="success"
    />
</x-stat-grid>

<!-- Grid 2 columns untuk summary -->
<x-stat-grid :columns="2">
    <x-stat-card 
        label="Revenue" 
        value="Rp 125M" 
        icon="heroicon-o-currency-dollar"
        variant="success"
    />
    
    <x-stat-card 
        label="Avg. Time" 
        value="2.5 Jam" 
        icon="heroicon-o-chart-bar"
        variant="secondary"
    />
</x-stat-grid>
```

### Variant Colors

- **Primary**: Blue gradient - untuk metrics utama
- **Success**: Green gradient - untuk data positif/selesai
- **Warning**: Yellow gradient - untuk pending/perhatian
- **Info**: Cyan gradient - untuk informasi
- **Danger**: Red gradient - untuk error/ditolak
- **Secondary**: Gray gradient - untuk data sekunder
- **Purple**: Purple gradient - untuk data special

---

## Table

Komponen table dengan fitur sorting.

### Props (Table)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `striped` | boolean | `false` | Baris bergaris |
| `hoverable` | boolean | `true` | Hover effect |
| `bordered` | boolean | `false` | Border semua sel |
| `responsive` | boolean | `true` | Responsive wrapper |

### Contoh Penggunaan

```blade
<x-table :striped="true" :hoverable="true">
    <x-table.head>
        <x-table.row>
            <x-table.th :sortable="true" sortColumn="name" sortDirection="asc">
                Nama
            </x-table.th>
            <x-table.th>Email</x-table.th>
            <x-table.th align="center">Status</x-table.th>
        </x-table.row>
    </x-table.head>
    
    <x-table.body>
        @foreach($users as $user)
        <x-table.row>
            <x-table.td>{{ $user->name }}</x-table.td>
            <x-table.td>{{ $user->email }}</x-table.td>
            <x-table.td align="center">
                <x-badge variant="success">Aktif</x-badge>
            </x-table.td>
        </x-table.row>
        @endforeach
    </x-table.body>
</x-table>
```

---

## Tabs

Komponen tab navigation.

### Props (Tabs)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `variant` | string | `'default'` | Style: `default`, `pills`, `underline` |

### Contoh Penggunaan

```blade
<x-tabs variant="default">
    <x-tabs.list>
        <x-tabs.tab target="tab1" :active="true" icon="heroicon-o-home">
            Tab 1
        </x-tabs.tab>
        <x-tabs.tab target="tab2">Tab 2</x-tabs.tab>
        <x-tabs.tab target="tab3">Tab 3</x-tabs.tab>
    </x-tabs.list>
    
    <x-tabs.panels>
        <x-tabs.panel id="tab1" :active="true">
            Konten tab 1
        </x-tabs.panel>
        <x-tabs.panel id="tab2">
            Konten tab 2
        </x-tabs.panel>
        <x-tabs.panel id="tab3">
            Konten tab 3
        </x-tabs.panel>
    </x-tabs.panels>
</x-tabs>
```

---

## Toast

Komponen notifikasi toast.

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `type` | string | `'info'` | Tipe: `info`, `success`, `warning`, `danger` |
| `title` | string | `null` | Judul toast |
| `dismissible` | boolean | `true` | Bisa ditutup |
| `duration` | number | `5000` | Durasi tampil (ms), 0 = infinite |

### Contoh Penggunaan (Blade)

```blade
<x-toast type="success" title="Berhasil!" :duration="3000">
    Data berhasil disimpan.
</x-toast>
```

### JavaScript API

```javascript
// Membuat toast secara programmatic
window.createToast({
    type: 'success',
    title: 'Berhasil!',
    message: 'Data berhasil disimpan.',
    duration: 5000,
    dismissible: true
});

// Contoh penggunaan
window.createToast({
    type: 'danger',
    message: 'Terjadi kesalahan',
    duration: 0 // Infinite duration
});
```

---

## Design Tokens (CSS Variables)

### Colors

```css
--brand-primary: #333333
--brand-primary-hover: #555555
--text-main: #333333
--text-muted: #666666
--surface-card: #ffffff
--surface-base: #f8f9fa
--border-default: #e0e0e0
```

### Spacing

```css
--spacing-xs: 4px
--spacing-sm: 8px
--spacing-md: 12px
--spacing-lg: 16px
--spacing-xl: 20px
--spacing-2xl: 24px
```

---

## Accessibility

Semua komponen dibangun dengan memperhatikan aksesibilitas:

- Menggunakan semantic HTML
- ARIA attributes yang tepat
- Keyboard navigation support
- Focus management
- Screen reader friendly

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Tech Stack

- **CSS**: Vanilla CSS dengan CSS Variables
- **JavaScript**: Vanilla JavaScript (ES6+)
- **Blade**: Laravel Blade Components
- **Icons**: Heroicons (optional, bisa diganti)
