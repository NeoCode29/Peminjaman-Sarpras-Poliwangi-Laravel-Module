<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>UI Playground - {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body style="background-color: var(--surface-base); min-height: 100vh;">
    <div id="ui-playground" style="max-width: 1200px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem;">
        <header style="margin-bottom: 2.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <div>
                <span style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--text-muted); font-size: 0.95rem;">
                    <span style="width: 8px; height: 8px; border-radius: 999px; background-color: var(--brand-accent);"></span>
                    UI Playground
                </span>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <h1 style="font-size: 1.85rem; font-weight: 700; color: var(--text-main); margin: 0;">
                    Komponen Sandbox
                </h1>
                <a href="{{ route('dashboard') }}" style="text-decoration: none; font-weight: 600; color: var(--brand-accent);">
                    &larr; Kembali ke Dashboard
                </a>
            </div>
            <p style="max-width: 760px; font-size: 0.95rem; color: var(--text-muted); line-height: 1.6;">
                Halaman ini memuat eksperimen UI menggunakan asset utama aplikasi (<code>app.css</code> &amp; <code>app.js</code>). Bangun komponen secara modular di sini terlebih dahulu. Jika sudah siap untuk produksi, pindahkan kode relevan ke berkas utama dan integrasikan ke halaman yang membutuhkan.
            </p>
        </header>

        <main style="display: grid; gap: 2rem;">
            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Input Text & Email</h2>
                    <p class="component-demo__description">
                        Text field dengan berbagai state (default, sukses, error, disabled, readonly, dan dengan ikon). Gunakan kelas <code>.c-input</code> untuk konsistensi antar form.
                    </p>
                </header>

                <div class="component-demo__preview c-input-grid">
                    <div class="c-input">
                        <label for="playground-input-name" class="c-input__label">
                            Nama Lengkap
                            <span class="c-input__badge">Required</span>
                        </label>
                        <div class="c-input__control">
                            <input type="text" id="playground-input-name" class="c-input__element" placeholder="Masukkan nama lengkap">
                        </div>
                        <p class="c-input__helper">Gunakan nama resmi yang tertera di identitas.</p>
                        <div class="c-input__meta">
                            <span>Max 60 karakter</span>
                            <span class="c-input__counter">0 / 60</span>
                        </div>
                    </div>

                    <div class="c-input c-input--with-icon">
                        <label for="playground-input-email" class="c-input__label">Email</label>
                        <div class="c-input__control">
                            <span class="c-input__icon" aria-hidden="true">
                                <x-heroicon-o-envelope />
                            </span>
                            <input type="email" id="playground-input-email" class="c-input__element" placeholder="user@example.com">
                        </div>
                        <p class="c-input__helper">Kami akan mengirimkan tautan aktivasi ke alamat ini.</p>
                    </div>

                    <div class="c-input c-input--valid">
                        <label for="playground-input-username" class="c-input__label">Username tersedia</label>
                        <div class="c-input__control">
                            <input type="text" id="playground-input-username" class="c-input__element is-valid" value="neo.code29">
                        </div>
                        <p class="c-input__helper">Username dapat digunakan.</p>
                    </div>

                    <div class="c-input c-input--invalid">
                        <label for="playground-input-password" class="c-input__label">Password</label>
                        <div class="c-input__control c-input__control--with-toggle" data-password-field>
                            <input type="password" id="playground-input-password" class="c-input__element is-invalid" placeholder="Minimal 8 karakter" data-password-input>
                            <button type="button" class="c-input__toggle" data-password-toggle aria-label="Tampilkan password">
                                <x-heroicon-o-eye-slash />
                            </button>
                        </div>
                        <p class="c-input__helper is-invalid">Password terlalu pendek. Gunakan kombinasi huruf dan angka.</p>
                    </div>

                    <div class="c-input">
                        <label for="playground-input-password-confirm" class="c-input__label">Konfirmasi Password</label>
                        <div class="c-input__control c-input__control--with-toggle" data-password-field>
                            <input type="password" id="playground-input-password-confirm" class="c-input__element" placeholder="Ulangi password" data-password-input>
                            <button type="button" class="c-input__toggle" data-password-toggle aria-label="Tampilkan password">
                                <x-heroicon-o-eye-slash />
                            </button>
                        </div>
                        <p class="c-input__helper">Pastikan cocok dengan password utama.</p>
                    </div>

                    <div class="c-input c-input--disabled">
                        <label for="playground-input-disabled" class="c-input__label">Kode Promo</label>
                        <div class="c-input__control">
                            <input type="text" id="playground-input-disabled" class="c-input__element" value="OFF50" disabled>
                        </div>
                        <p class="c-input__helper">Kolom dinonaktifkan untuk sesi ini.</p>
                    </div>

                    <div class="c-input">
                        <label for="playground-input-readonly" class="c-input__label">Token Akses</label>
                        <div class="c-input__control">
                            <input type="text" id="playground-input-readonly" class="c-input__element" value="2e7f-991c-4ae1" readonly>
                        </div>
                        <p class="c-input__helper">Nilai hanya dapat dilihat oleh admin.</p>
                    </div>

                    <div class="c-input">
                        <label for="playground-input-number" class="c-input__label">Jumlah Unit</label>
                        <div class="c-input__control">
                            <input type="number" id="playground-input-number" class="c-input__element c-input__element--number" placeholder="0" min="0" step="1" value="3">
                        </div>
                        <p class="c-input__helper">Masukkan angka bulat, gunakan tanda panah atau ketik langsung.</p>
                    </div>

                    <div class="c-input">
                        <label for="playground-input-date" class="c-input__label">Tanggal Pengembalian</label>
                        <div class="c-input__control">
                            <input type="date" id="playground-input-date" class="c-input__element c-input__element--date" value="2025-11-15">
                        </div>
                        <p class="c-input__helper">Gunakan kalender untuk memilih tanggal yang tersedia.</p>
                    </div>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Textarea & Select</h2>
                    <p class="component-demo__description">
                        Variasi kontrol untuk input multi-baris dan pilihan dropdown dengan gaya konsisten.</p>
                </header>

                <div class="component-demo__preview c-input-grid">
                    <div class="c-input">
                        <label for="playground-input-notes" class="c-input__label">Catatan</label>
                        <div class="c-input__control">
                            <textarea id="playground-input-notes" class="c-input__element c-input__element--textarea" placeholder="Tambahkan detail tambahan di sini"></textarea>
                        </div>
                        <p class="c-input__helper">Gunakan maksimal 200 karakter.</p>
                    </div>

                    <div class="c-input">
                        <label for="playground-input-role" class="c-input__label">Role Pengguna</label>
                        <div class="c-input__control">
                            <select id="playground-input-role" class="c-input__element c-input__element--select">
                                <option value="">Pilih role</option>
                                <option>Administrator</option>
                                <option>Manajer</option>
                                <option>Staff</option>
                            </select>
                        </div>
                        <p class="c-input__helper">Role menentukan hak akses pengguna.</p>
                    </div>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Checkbox & Radio Group</h2>
                    <p class="component-demo__description">Gunakan <code>.c-choice</code> untuk membuat stack opsi yang rapi dan mudah di-scan.</p>
                </header>

                <div class="component-demo__preview c-choice-stack">
                    <div>
                        <p class="component-demo__label">Checkbox</p>
                        <label class="c-choice">
                            <input type="checkbox" class="c-choice__input" checked>
                            <span class="c-choice__box">
                                <span class="c-choice__icon" aria-hidden="true"><x-heroicon-o-check /></span>
                            </span>
                            <div>
                                <div class="c-choice__label">Aktifkan notifikasi email</div>
                                <div class="c-choice__description">Kami akan mengirimkan ringkasan harian.</div>
                            </div>
                        </label>
                        <label class="c-choice is-disabled">
                            <input type="checkbox" class="c-choice__input" disabled>
                            <span class="c-choice__box">
                                <span class="c-choice__icon" aria-hidden="true"><x-heroicon-o-check /></span>
                            </span>
                            <div>
                                <div class="c-choice__label">Mode maintenance</div>
                                <div class="c-choice__description">Hanya tersedia untuk admin utama.</div>
                            </div>
                        </label>
                    </div>

                    <div>
                        <p class="component-demo__label">Radio</p>
                        <label class="c-choice c-choice--radio">
                            <input type="radio" name="playground-radio" class="c-choice__input" checked>
                            <span class="c-choice__box">
                                <span class="c-choice__indicator" aria-hidden="true"></span>
                            </span>
                            <div>
                                <div class="c-choice__label">Peminjaman Sementara</div>
                                <div class="c-choice__description">Durasi maksimal 7 hari kerja.</div>
                            </div>
                        </label>
                        <label class="c-choice c-choice--radio">
                            <input type="radio" name="playground-radio" class="c-choice__input">
                            <span class="c-choice__box">
                                <span class="c-choice__indicator" aria-hidden="true"></span>
                            </span>
                            <div>
                                <div class="c-choice__label">Peminjaman Tetap</div>
                                <div class="c-choice__description">Disetujui melalui evaluasi kebutuhan.</div>
                            </div>
                        </label>
                    </div>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Switch & File Upload</h2>
                    <p class="component-demo__description">Kontrol interaktif tambahan untuk kebutuhan form lanjutan.</p>
                </header>

                <div class="component-demo__preview c-input-grid">
                    <label class="c-switch">
                        <input type="checkbox" class="c-switch__input" checked>
                        <span class="c-switch__track">
                            <span class="c-switch__thumb"></span>
                        </span>
                        <span class="c-switch__label">Aktifkan pengingat otomatis</span>
                    </label>

                    <label class="c-switch c-switch is-disabled">
                        <input type="checkbox" class="c-switch__input" disabled>
                        <span class="c-switch__track">
                            <span class="c-switch__thumb"></span>
                        </span>
                        <span class="c-switch__label">Notifikasi SMS (Coming Soon)</span>
                    </label>

                    <div class="c-file">
                        <div class="c-file__button">
                            <x-heroicon-o-arrow-up-tray />
                            Upload Dokumen
                        </div>
                        <p class="c-file__text">Unggah bukti pendukung (PDF, maks 2MB).</p>
                        <input type="file" class="c-file__input" name="playground-file" data-file-input>
                        <div class="c-file__preview" data-file-preview hidden></div>
                    </div>

                    <div class="c-file">
                        <div class="c-file__button">
                            <x-heroicon-o-arrow-path />
                            Upload Lampiran (Multiple)
                        </div>
                        <p class="c-file__text">Pilih beberapa file sekaligus untuk diunggah (gambar atau PDF).</p>
                        <input type="file" class="c-file__input" name="playground-file-multiple" data-file-input multiple>
                        <div class="c-file__preview" data-file-preview hidden></div>
                    </div>
                </div>
            </section>

            <!-- KOMPONEN BARU -->
            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Alert</h2>
                    <p class="component-demo__description">Komponen notifikasi dengan berbagai tipe dan dismissible option.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; flex-direction: column; gap: 1rem;">
                    <x-alert type="info" title="Informasi">
                        Ini adalah alert informasi dengan judul.
                    </x-alert>

                    <x-alert type="success" :dismissible="true">
                        Data berhasil disimpan ke database!
                    </x-alert>

                    <x-alert type="warning" title="Peringatan" :dismissible="true">
                        Akun Anda akan kedaluwarsa dalam 7 hari.
                    </x-alert>

                    <x-alert type="danger" title="Error" icon="heroicon-o-exclamation-triangle">
                        Terjadi kesalahan saat memproses permintaan Anda.
                    </x-alert>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Badge</h2>
                    <p class="component-demo__description">Label status dengan berbagai varian dan ukuran.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                    <x-badge variant="default">Default</x-badge>
                    <x-badge variant="primary">Primary</x-badge>
                    <x-badge variant="success">Aktif</x-badge>
                    <x-badge variant="warning">Pending</x-badge>
                    <x-badge variant="danger">Nonaktif</x-badge>
                    <x-badge variant="info">Info</x-badge>
                    
                    <x-divider orientation="vertical" style="height: 24px;" />
                    
                    <x-badge variant="success" size="sm">Small</x-badge>
                    <x-badge variant="primary">Default</x-badge>
                    <x-badge variant="danger" size="lg">Large</x-badge>
                    
                    <x-divider orientation="vertical" style="height: 24px;" />
                    
                    <x-badge variant="success" :rounded="true">Rounded</x-badge>
                    <x-badge variant="info" :dot="true">With Dot</x-badge>
                    <x-badge variant="primary" :rounded="true" :dot="true">Both</x-badge>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Breadcrumb</h2>
                    <p class="component-demo__description">Navigasi breadcrumb dengan berbagai separator.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div>
                        <p style="margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted);">Chevron Separator</p>
                        <x-breadcrumb separator="chevron">
                            <x-breadcrumb.item href="/" icon="heroicon-o-home">Home</x-breadcrumb.item>
                            <x-breadcrumb.item href="/users">Users</x-breadcrumb.item>
                            <x-breadcrumb.item href="/users/management">Management</x-breadcrumb.item>
                            <x-breadcrumb.item :active="true">Detail</x-breadcrumb.item>
                        </x-breadcrumb>
                    </div>

                    <div>
                        <p style="margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted);">Slash Separator</p>
                        <x-breadcrumb separator="slash">
                            <x-breadcrumb.item href="/">Dashboard</x-breadcrumb.item>
                            <x-breadcrumb.item href="/settings">Settings</x-breadcrumb.item>
                            <x-breadcrumb.item :active="true">Profile</x-breadcrumb.item>
                        </x-breadcrumb>
                    </div>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Spinner</h2>
                    <p class="component-demo__description">Loading indicator dengan berbagai ukuran dan warna.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; gap: 2rem; align-items: center; flex-wrap: wrap;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <x-spinner size="sm" />
                        <x-spinner />
                        <x-spinner size="lg" />
                    </div>
                    
                    <x-divider orientation="vertical" style="height: 40px;" />
                    
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <x-spinner variant="primary" />
                        <x-spinner variant="secondary" />
                        <div style="background: #333; padding: 1rem; border-radius: 8px;">
                            <x-spinner variant="white" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Divider</h2>
                    <p class="component-demo__description">Pembatas visual horizontal dan vertikal.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div>
                        <p>Content di atas</p>
                        <x-divider />
                        <p>Content di bawah</p>
                    </div>

                    <div>
                        <p>Content di atas</p>
                        <x-divider text="atau" />
                        <p>Content di bawah</p>
                    </div>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Empty State</h2>
                    <p class="component-demo__description">Placeholder untuk kondisi tidak ada data.</p>
                </header>
                <div class="component-demo__preview" style="background: white; border: 1px solid var(--border-default); border-radius: 8px;">
                    <x-empty-state 
                        icon="heroicon-o-users" 
                        title="Belum ada pengguna"
                        description="Mulai dengan menambahkan pengguna pertama Anda untuk memulai."
                    >
                        <x-slot:action>
                            <x-button variant="primary" icon="heroicon-o-plus">Tambah Pengguna</x-button>
                        </x-slot:action>
                    </x-empty-state>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Dropdown</h2>
                    <p class="component-demo__description">Menu dropdown dengan berbagai alignment.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-start;">
                    <x-dropdown align="left">
                        <x-slot:trigger>
                            <x-button variant="secondary">Left Aligned</x-button>
                        </x-slot:trigger>
                        
                        <x-dropdown.item icon="heroicon-o-user">Profile</x-dropdown.item>
                        <x-dropdown.item icon="heroicon-o-cog-6-tooth">Settings</x-dropdown.item>
                        <x-dropdown.divider />
                        <x-dropdown.item :danger="true" icon="heroicon-o-arrow-right-on-rectangle">Logout</x-dropdown.item>
                    </x-dropdown>

                    <x-dropdown align="right">
                        <x-slot:trigger>
                            <x-button variant="secondary">Right Aligned</x-button>
                        </x-slot:trigger>
                        
                        <x-dropdown.item :active="true">Dashboard</x-dropdown.item>
                        <x-dropdown.item>Users</x-dropdown.item>
                        <x-dropdown.item>Settings</x-dropdown.item>
                    </x-dropdown>

                    <x-dropdown width="lg">
                        <x-slot:trigger>
                            <x-button variant="secondary">Large Width</x-button>
                        </x-slot:trigger>
                        
                        <x-dropdown.item>
                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                <div style="font-weight: 600;">Administrator</div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">Full access to all features</div>
                            </div>
                        </x-dropdown.item>
                        <x-dropdown.item>
                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                <div style="font-weight: 600;">Manager</div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">Can manage users and content</div>
                            </div>
                        </x-dropdown.item>
                    </x-dropdown>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Notification Card</h2>
                    <p class="component-demo__description">Kartu notifikasi untuk inbox atau ringkasan aktivitas penting.</p>
                </header>
                <div class="component-demo__preview" style="display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); max-width: 720px;">
                    <x-notification.card
                        title="Pengajuan peminjaman disetujui"
                        message="Ruangan Laboratorium A-101 pada tanggal 20 Nov 2025 pukul 09.00–11.00 telah disetujui oleh Admin Sarpras."
                        icon="heroicon-o-check-circle"
                        category="Peminjaman"
                        categoryVariant="primary"
                        time="Baru saja"
                        :unread="true"
                        priority="high"
                    >
                        <x-slot:actions>
                            <x-button size="sm" variant="primary">Lihat Detail</x-button>
                        </x-slot:actions>
                    </x-notification.card>

                    <x-notification.card
                        title="Pengajuan baru menunggu persetujuan"
                        message="Terdapat pengajuan peminjaman baru untuk Ruang Meeting B-203 yang perlu Anda review."
                        icon="heroicon-o-information-circle"
                        category="Approval"
                        categoryVariant="warning"
                        time="10 menit yang lalu"
                        :unread="true"
                        priority="normal"
                    >
                        <x-slot:actions>
                            <x-button size="sm" variant="secondary">Tinjau</x-button>
                        </x-slot:actions>
                    </x-notification.card>

                    <x-notification.card
                        title="Sinkronisasi data master berhasil"
                        message="Data master Poliwangi berhasil disinkronisasi tanpa kendala pada pukul 06.30."
                        icon="heroicon-o-bell"
                        category="Sistem"
                        categoryVariant="info"
                        time="Kemarin"
                        :unread="false"
                        priority="low"
                    />
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Table</h2>
                    <p class="component-demo__description">Tabel data dengan berbagai varian dan fitur sorting.</p>
                </header>
                <div class="component-demo__preview">
                    <x-table :striped="true" :hoverable="true">
                        <x-table.head>
                            <x-table.row>
                                <x-table.th :sortable="true" sortColumn="name" sortDirection="asc">Nama</x-table.th>
                                <x-table.th :sortable="true" sortColumn="email">Email</x-table.th>
                                <x-table.th align="center">Role</x-table.th>
                                <x-table.th align="center">Status</x-table.th>
                                <x-table.th align="right">Aksi</x-table.th>
                            </x-table.row>
                        </x-table.head>
                        
                        <x-table.body>
                            <x-table.row>
                                <x-table.td>John Doe</x-table.td>
                                <x-table.td>john@example.com</x-table.td>
                                <x-table.td align="center">
                                    <x-badge variant="primary">Admin</x-badge>
                                </x-table.td>
                                <x-table.td align="center">
                                    <x-badge variant="success">Aktif</x-badge>
                                </x-table.td>
                                <x-table.td align="right">
                                    <x-button size="sm" variant="secondary">Edit</x-button>
                                </x-table.td>
                            </x-table.row>
                            <x-table.row>
                                <x-table.td>Jane Smith</x-table.td>
                                <x-table.td>jane@example.com</x-table.td>
                                <x-table.td align="center">
                                    <x-badge variant="info">User</x-badge>
                                </x-table.td>
                                <x-table.td align="center">
                                    <x-badge variant="success">Aktif</x-badge>
                                </x-table.td>
                                <x-table.td align="right">
                                    <x-button size="sm" variant="secondary">Edit</x-button>
                                </x-table.td>
                            </x-table.row>
                            <x-table.row>
                                <x-table.td>Bob Johnson</x-table.td>
                                <x-table.td>bob@example.com</x-table.td>
                                <x-table.td align="center">
                                    <x-badge variant="warning">Manager</x-badge>
                                </x-table.td>
                                <x-table.td align="center">
                                    <x-badge variant="danger">Nonaktif</x-badge>
                                </x-table.td>
                                <x-table.td align="right">
                                    <x-button size="sm" variant="secondary">Edit</x-button>
                                </x-table.td>
                            </x-table.row>
                        </x-table.body>
                    </x-table>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Tabs</h2>
                    <p class="component-demo__description">Tab navigation dengan berbagai variant.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; flex-direction: column; gap: 2rem;">
                    <div>
                        <p style="margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted);">Default Tabs</p>
                        <x-tabs variant="default">
                            <x-tabs.list>
                                <x-tabs.tab target="tab1" :active="true" icon="heroicon-o-home">Overview</x-tabs.tab>
                                <x-tabs.tab target="tab2" icon="heroicon-o-user">Profile</x-tabs.tab>
                                <x-tabs.tab target="tab3" icon="heroicon-o-cog-6-tooth">Settings</x-tabs.tab>
                            </x-tabs.list>
                            
                            <x-tabs.panels>
                                <x-tabs.panel id="tab1" :active="true">
                                    <div style="padding: 1.5rem; background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 8px;">
                                        <h3 style="margin: 0 0 0.5rem; font-size: 1.125rem; font-weight: 600;">Overview</h3>
                                        <p style="margin: 0; color: var(--text-muted);">Dashboard overview dengan statistik utama.</p>
                                    </div>
                                </x-tabs.panel>
                                <x-tabs.panel id="tab2">
                                    <div style="padding: 1.5rem; background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 8px;">
                                        <h3 style="margin: 0 0 0.5rem; font-size: 1.125rem; font-weight: 600;">Profile</h3>
                                        <p style="margin: 0; color: var(--text-muted);">Informasi profil dan data pengguna.</p>
                                    </div>
                                </x-tabs.panel>
                                <x-tabs.panel id="tab3">
                                    <div style="padding: 1.5rem; background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 8px;">
                                        <h3 style="margin: 0 0 0.5rem; font-size: 1.125rem; font-weight: 600;">Settings</h3>
                                        <p style="margin: 0; color: var(--text-muted);">Pengaturan aplikasi dan preferensi.</p>
                                    </div>
                                </x-tabs.panel>
                            </x-tabs.panels>
                        </x-tabs>
                    </div>

                    <div>
                        <p style="margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted);">Pills Variant</p>
                        <x-tabs variant="pills">
                            <x-tabs.list>
                                <x-tabs.tab target="pill1" :active="true">Dashboard</x-tabs.tab>
                                <x-tabs.tab target="pill2">Analytics</x-tabs.tab>
                                <x-tabs.tab target="pill3">Reports</x-tabs.tab>
                            </x-tabs.list>
                            
                            <x-tabs.panels>
                                <x-tabs.panel id="pill1" :active="true">
                                    <div style="padding: 1.5rem; background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 8px;">
                                        Dashboard content
                                    </div>
                                </x-tabs.panel>
                                <x-tabs.panel id="pill2">
                                    <div style="padding: 1.5rem; background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 8px;">
                                        Analytics content
                                    </div>
                                </x-tabs.panel>
                                <x-tabs.panel id="pill3">
                                    <div style="padding: 1.5rem; background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 8px;">
                                        Reports content
                                    </div>
                                </x-tabs.panel>
                            </x-tabs.panels>
                        </x-tabs>
                    </div>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Modal</h2>
                    <p class="component-demo__description">Dialog modal dengan berbagai ukuran. Klik tombol untuk membuka modal.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <x-button variant="primary" onclick="document.getElementById('modalSmall').open()">
                        Small Modal
                    </x-button>
                    <x-button variant="primary" onclick="document.getElementById('modalDefault').open()">
                        Default Modal
                    </x-button>
                    <x-button variant="primary" onclick="document.getElementById('modalLarge').open()">
                        Large Modal
                    </x-button>
                    <x-button variant="secondary" onclick="document.getElementById('modalStatic').open()">
                        Static Backdrop
                    </x-button>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Toast</h2>
                    <p class="component-demo__description">Notifikasi toast (klik tombol untuk melihat).</p>
                </header>
                <div class="component-demo__preview" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <x-button variant="primary" onclick="showToast('info')">Info Toast</x-button>
                    <x-button variant="primary" onclick="showToast('success')">Success Toast</x-button>
                    <x-button variant="primary" onclick="showToast('warning')">Warning Toast</x-button>
                    <x-button variant="danger" onclick="showToast('danger')">Danger Toast</x-button>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Detail Section</h2>
                    <p class="component-demo__description">Komponen section dengan border, header, dan footer (opsional) untuk menampilkan detail data.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; flex-direction: column; gap: 2rem;">
                    <x-detail-section 
                        title="Informasi Pengguna" 
                        description="Detail lengkap informasi pengguna"
                        icon="heroicon-o-user-circle"
                    >
                        <x-slot:headerActions>
                            <x-button size="sm" variant="secondary" icon="heroicon-o-pencil">Edit</x-button>
                        </x-slot:headerActions>

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
                            <x-detail-item label="Alamat" icon="heroicon-o-map-pin">
                                Jl. Contoh No. 123, Jakarta Selatan
                            </x-detail-item>
                            <x-detail-item label="Tanggal Bergabung" icon="heroicon-o-calendar">
                                15 November 2025
                            </x-detail-item>
                            <x-detail-item label="Status" icon="heroicon-o-check-circle">
                                <x-badge variant="success">Aktif</x-badge>
                            </x-detail-item>
                        </x-detail-list>

                        <x-slot:footer>
                            <x-button variant="secondary" size="sm">Reset Password</x-button>
                            <x-button variant="danger" size="sm">Nonaktifkan Akun</x-button>
                        </x-slot:footer>
                    </x-detail-section>

                    <x-detail-section 
                        title="Data Akademik" 
                        description="Informasi akademik mahasiswa"
                        icon="heroicon-o-academic-cap"
                    >
                        <x-detail-list>
                            <x-detail-item label="NIM">2024101234</x-detail-item>
                            <x-detail-item label="Program Studi">Teknik Informatika</x-detail-item>
                            <x-detail-item label="Fakultas">Fakultas Teknik</x-detail-item>
                            <x-detail-item label="Semester">5 (Lima)</x-detail-item>
                            <x-detail-item label="IPK">3.75</x-detail-item>
                            <x-detail-item label="SKS Lulus">110 / 144</x-detail-item>
                        </x-detail-list>
                    </x-detail-section>

                    <x-detail-section title="Alamat Lengkap" icon="heroicon-o-map">
                        <x-detail-list>
                            <x-detail-item label="Jalan">Jl. Contoh No. 123</x-detail-item>
                            <x-detail-item label="Kelurahan">Kebayoran Baru</x-detail-item>
                            <x-detail-item label="Kecamatan">Kebayoran Baru</x-detail-item>
                            <x-detail-item label="Kota">Jakarta Selatan</x-detail-item>
                            <x-detail-item label="Provinsi">DKI Jakarta</x-detail-item>
                            <x-detail-item label="Kode Pos">12345</x-detail-item>
                        </x-detail-list>
                    </x-detail-section>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Detail List (Standalone)</h2>
                    <p class="component-demo__description">Komponen list untuk menampilkan detail data dengan <strong>label di kiri, nilai di kanan</strong>, dipisahkan divider dotted.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; flex-direction: column; gap: 2rem;">
                    <div>
                        <p style="margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted);">Default Layout</p>
                        <x-detail-list>
                            <x-detail-item label="Nama">John Doe</x-detail-item>
                            <x-detail-item label="Email">john@example.com</x-detail-item>
                            <x-detail-item label="Status">
                                <x-badge variant="success">Aktif</x-badge>
                            </x-detail-item>
                        </x-detail-list>
                    </div>

                    <div>
                        <p style="margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted);">Dengan Border</p>
                        <x-detail-list variant="bordered">
                            <x-detail-item label="NIM">2024101234</x-detail-item>
                            <x-detail-item label="Program Studi">Teknik Informatika</x-detail-item>
                            <x-detail-item label="Fakultas">Fakultas Teknik</x-detail-item>
                        </x-detail-list>
                    </div>

                    <div>
                        <p style="margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted);">Dengan Striped</p>
                        <x-detail-list variant="striped">
                            <x-detail-item label="Kota">Jakarta</x-detail-item>
                            <x-detail-item label="Provinsi">DKI Jakarta</x-detail-item>
                            <x-detail-item label="Kode Pos">12345</x-detail-item>
                        </x-detail-list>
                    </div>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Stat Card</h2>
                    <p class="component-demo__description">Komponen card statistik dengan icon dan value untuk menampilkan metrics/KPI di dashboard.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; flex-direction: column; gap: 2rem;">
                    <div>
                        <p style="margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted);">Grid 4 Columns</p>
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
                    </div>

                    <div>
                        <p style="margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted);">Grid 3 Columns dengan Variant Berbeda</p>
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
                                label="Rata-rata Waktu" 
                                value="2.5 Jam" 
                                icon="heroicon-o-chart-bar"
                                variant="secondary"
                            />
                        </x-stat-grid>
                    </div>

                    <div>
                        <p style="margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted);">Grid 2 Columns</p>
                        <x-stat-grid :columns="2">
                            <x-stat-card 
                                label="Revenue" 
                                value="Rp 125M" 
                                icon="heroicon-o-currency-dollar"
                                variant="success"
                            />
                            
                            <x-stat-card 
                                label="Growth" 
                                value="+15.3%" 
                                icon="heroicon-o-arrow-trending-up"
                                variant="primary"
                            />
                        </x-stat-grid>
                    </div>
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Calendar Dashboard</h2>
                    <p class="component-demo__description">Kalender dashboard dengan two-column layout untuk menampilkan events/agenda dengan panel detail interaktif.</p>
                </header>
                <div class="component-demo__preview">
                    <x-calendar-dashboard title="Peminjaman" />
                </div>
            </section>

            <section class="component-demo">
                <header class="component-demo__header">
                    <h2 class="component-demo__title">Form Group & Form Section</h2>
                    <p class="component-demo__description">Komponen untuk mengorganisir form yang kompleks dengan style konsisten seperti detail section.</p>
                </header>
                <div class="component-demo__preview" style="display: flex; flex-direction: column; gap: 2rem;">
                    <x-form-group 
                        title="Informasi Pribadi" 
                        description="Masukkan data pribadi Anda dengan lengkap dan benar."
                        icon="heroicon-o-user"
                    >
                        <x-form-section title="Data Diri" description="Informasi identitas utama." :required="true">
                            <x-input.text name="full_name" label="Nama Lengkap" placeholder="Masukkan nama lengkap" :required="true" />
                            <x-input.text type="email" name="email" label="Email" placeholder="email@example.com" :required="true" />
                            <x-input.text name="phone" label="Nomor Telepon" placeholder="+62" />
                        </x-form-section>

                        <x-form-section title="Alamat" description="Informasi alamat tempat tinggal.">
                            <x-input.text name="street" label="Alamat Lengkap" placeholder="Jl. Nama Jalan No. XX" />
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg);">
                                <x-input.select name="province" label="Provinsi">
                                    <option value="">Pilih Provinsi</option>
                                    <option>DKI Jakarta</option>
                                    <option>Jawa Barat</option>
                                    <option>Jawa Tengah</option>
                                </x-input.select>
                                
                                <x-input.select name="city" label="Kota">
                                    <option value="">Pilih Kota</option>
                                    <option>Jakarta Selatan</option>
                                    <option>Bandung</option>
                                </x-input.select>
                                
                                <x-input.text name="postal_code" label="Kode Pos" placeholder="12345" />
                            </div>
                        </x-form-section>

                        <x-slot:footer>
                            <x-button variant="secondary">Batal</x-button>
                            <x-button variant="primary">Simpan Data Pribadi</x-button>
                        </x-slot:footer>
                    </x-form-group>

                    <x-form-group 
                        title="Informasi Akun" 
                        description="Pengaturan untuk akun Anda."
                        icon="heroicon-o-key"
                    >
                        <x-slot:headerActions>
                            <x-button size="sm" variant="secondary" icon="heroicon-o-arrow-path">Reset</x-button>
                        </x-slot:headerActions>

                        <x-form-section title="Keamanan" :required="true">
                            <x-input.text type="password" name="password" label="Password" placeholder="Minimal 8 karakter" :withToggle="true" :required="true" />
                            <x-input.text type="password" name="password_confirmation" label="Konfirmasi Password" placeholder="Ulangi password" :withToggle="true" :required="true" />
                        </x-form-section>

                        <x-form-section title="Preferensi">
                            <x-input.checkbox name="notifications" label="Terima notifikasi email" />
                            <x-input.checkbox name="newsletter" label="Subscribe newsletter" />
                            <x-input.checkbox name="sms_notifications" label="Notifikasi SMS" />
                        </x-form-section>

                        <x-slot:footer>
                            <x-button variant="secondary">Batal</x-button>
                            <x-button variant="primary">Simpan Pengaturan</x-button>
                        </x-slot:footer>
                    </x-form-group>
                </div>
            </section>

        </main>
    </div>

    <!-- Modal Components -->
    <x-modal id="modalSmall" title="Small Modal" size="sm">
        <p>Ini adalah modal dengan ukuran kecil.</p>
        
        <x-slot:footer>
            <x-button variant="secondary" data-modal-close>Batal</x-button>
            <x-button variant="primary">Simpan</x-button>
        </x-slot:footer>
    </x-modal>

    <x-modal id="modalDefault" title="Default Modal">
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <p>Ini adalah modal dengan ukuran default.</p>
            <x-input.text name="name" label="Nama" placeholder="Masukkan nama" />
            <x-input.text type="email" name="email" label="Email" placeholder="email@example.com" />
        </div>
        
        <x-slot:footer>
            <x-button variant="secondary" data-modal-close>Batal</x-button>
            <x-button variant="primary">Simpan</x-button>
        </x-slot:footer>
    </x-modal>

    <x-modal id="modalLarge" title="Large Modal" size="lg">
        <div style="height: 400px; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
            <div style="text-align: center;">
                <p style="font-size: 1.125rem; margin-bottom: 0.5rem;">Large Modal Content</p>
                <p>Modal ini memiliki lebar yang lebih besar untuk konten yang kompleks.</p>
            </div>
        </div>
        
        <x-slot:footer>
            <x-button variant="secondary" data-modal-close>Tutup</x-button>
        </x-slot:footer>
    </x-modal>

    <x-modal id="modalStatic" title="Static Backdrop Modal" :staticBackdrop="true">
        <p>Modal ini tidak bisa ditutup dengan klik di luar area modal (static backdrop).</p>
        <p>Hanya bisa ditutup dengan tombol close atau tombol Batal di bawah.</p>
        
        <x-slot:footer>
            <x-button variant="secondary" data-modal-close>Batal</x-button>
            <x-button variant="primary">OK</x-button>
        </x-slot:footer>
    </x-modal>

    <script>
        function showToast(type) {
            const messages = {
                info: { title: 'Informasi', message: 'Ini adalah notifikasi informasi.' },
                success: { title: 'Berhasil!', message: 'Data berhasil disimpan ke database.' },
                warning: { title: 'Peringatan', message: 'Akun Anda akan kedaluwarsa dalam 7 hari.' },
                danger: { title: 'Error', message: 'Terjadi kesalahan saat memproses permintaan.' }
            };

            const config = messages[type];
            window.createToast({
                type: type,
                title: config.title,
                message: config.message,
                duration: 5000,
                dismissible: true
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
