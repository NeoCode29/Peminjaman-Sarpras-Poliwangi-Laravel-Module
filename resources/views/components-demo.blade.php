<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blade Components Demo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: var(--surface-base); min-height: 100vh; padding: 2rem;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h1 style="margin-bottom: 2rem; color: var(--text-main);">Blade Components Demo</h1>

        {{-- Button Examples --}}
        <section class="component-demo">
            <h2 class="component-demo__title">Button Components</h2>
            
            <div class="c-button-group">
                <x-button variant="primary" icon="heroicon-o-plus">Tambah Data</x-button>
                <x-button variant="secondary">Batal</x-button>
                <x-button variant="danger" icon="heroicon-o-trash">Hapus</x-button>
                <x-button variant="outline-primary">Outline</x-button>
                <x-button variant="primary" :loading="true">Loading...</x-button>
            </div>

            <div style="margin-top: 1rem;">
                <x-button variant="primary" :block="true">Block Button</x-button>
            </div>
        </section>

        {{-- Input Text Examples --}}
        <section class="component-demo" style="margin-top: 2rem;">
            <h2 class="component-demo__title">Input Text Components</h2>
            
            <div class="c-input-grid">
                <x-input.text 
                    name="name" 
                    label="Nama Lengkap" 
                    badge="Required"
                    placeholder="Masukkan nama lengkap"
                    helper="Gunakan nama resmi yang tertera di identitas"
                />

                <x-input.text 
                    type="email"
                    name="email" 
                    label="Email" 
                    icon="heroicon-o-envelope"
                    placeholder="user@example.com"
                    helper="Email akan digunakan untuk login"
                />

                <x-input.text 
                    type="password"
                    name="password" 
                    label="Password" 
                    :with-toggle="true"
                    placeholder="Minimal 8 karakter"
                />

                <x-input.text 
                    type="number"
                    name="quantity" 
                    label="Jumlah" 
                    placeholder="0"
                    min="0"
                />

                <x-input.text 
                    type="date"
                    name="return_date" 
                    label="Tanggal Pengembalian"
                />
            </div>
        </section>

        {{-- Select Examples --}}
        <section class="component-demo" style="margin-top: 2rem;">
            <h2 class="component-demo__title">Select Components</h2>
            
            <div class="c-input-grid">
                <x-input.select 
                    name="role_id" 
                    label="Role" 
                    badge="Required"
                    placeholder="Pilih Role"
                >
                    <option value="1">Administrator</option>
                    <option value="2">Manager</option>
                    <option value="3">Staff</option>
                </x-input.select>

                <x-input.select 
                    name="status" 
                    label="Status"
                >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </x-input.select>
            </div>
        </section>

        {{-- Textarea Examples --}}
        <section class="component-demo" style="margin-top: 2rem;">
            <h2 class="component-demo__title">Textarea Components</h2>
            
            <x-input.textarea 
                name="notes" 
                label="Catatan" 
                placeholder="Tambahkan catatan tambahan"
                helper="Maksimal 200 karakter"
                :rows="4"
            />
        </section>

        {{-- Checkbox & Radio Examples --}}
        <section class="component-demo" style="margin-top: 2rem;">
            <h2 class="component-demo__title">Checkbox & Radio Components</h2>
            
            <div class="c-choice-stack">
                <div>
                    <p class="component-demo__label">Checkbox</p>
                    <x-input.checkbox 
                        name="notifications" 
                        label="Aktifkan notifikasi email"
                        description="Kami akan mengirimkan ringkasan harian"
                        :checked="true"
                    />
                    <x-input.checkbox 
                        name="terms" 
                        label="Saya menyetujui syarat dan ketentuan"
                    />
                </div>

                <div>
                    <p class="component-demo__label">Radio</p>
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
                    <x-input.radio 
                        name="loan_type"
                        id="loan_trial"
                        value="trial"
                        label="Peminjaman Percobaan"
                    />
                </div>
            </div>
        </section>

        {{-- File Upload Examples --}}
        <section class="component-demo" style="margin-top: 2rem;">
            <h2 class="component-demo__title">File Upload Components</h2>
            
            <div class="c-input-grid">
                <x-input.file 
                    name="document" 
                    label="Upload Dokumen"
                    helper="Unggah bukti pendukung (PDF, maks 2MB)"
                    accept=".pdf"
                />

                <x-input.file 
                    name="attachments" 
                    label="Upload Lampiran Multiple"
                    button-text="Upload Lampiran"
                    button-icon="heroicon-o-arrow-path"
                    helper="Pilih beberapa file sekaligus"
                    :multiple="true"
                />
            </div>
        </section>

        {{-- Card Components --}}
        <section class="component-demo" style="margin-top: 2rem;">
            <h2 class="component-demo__title">Card Components</h2>
            <p class="component-demo__description">Gunakan komponen kartu untuk menampilkan ringkasan informasi, status, atau tindakan.</p>

            <div class="c-card-group">
                <x-card 
                    title="Ringkasan Peminjaman" 
                    description="Total permohonan menunggu persetujuan."
                >
                    <x-slot:headerActions>
                        <x-button variant="outline-primary" size="sm" icon="heroicon-o-arrow-path">Refresh</x-button>
                    </x-slot:headerActions>

                    <div class="c-card__detail-list">
                        <x-card.detail-item label="Menunggu" value="12 permohonan" />
                        <x-card.detail-item label="Diproses" value="5 permohonan" />
                        <x-card.detail-item label="Selesai hari ini" value="3 permohonan" />
                    </div>

                    <x-slot:footer>
                        <x-button variant="primary" icon="heroicon-o-document-text">Lihat Detail</x-button>
                        <x-button variant="secondary">Kelola</x-button>
                    </x-slot:footer>
                </x-card>

                <x-card 
                    icon="heroicon-o-information-circle"
                    title="Maintenance Gedung"
                    description="Gedung A akan ditutup pada 12-14 November untuk perawatan AC."
                    variant="info"
                >
                    <p>Pastikan semua peminjaman ruang dialihkan ke Gedung B selama periode ini.</p>
                </x-card>

                <x-card 
                    layout="centered"
                    icon="heroicon-o-sparkles"
                    title="Status Peminjam"
                    description="Ringkasan singkat keaktifan peminjam minggu ini."
                >
                    <div class="c-card__detail-list">
                        <x-card.detail-item label="Peminjam Aktif" value="128" />
                        <x-card.detail-item label="Peminjaman Baru" value="42" />
                        <x-card.detail-item label="Tingkat Kepuasan" value="4.8/5" />
                    </div>

                    <x-slot:footer>
                        <x-button variant="primary" size="sm" icon="heroicon-o-chart-bar">Lihat Statistik</x-button>
                    </x-slot:footer>
                </x-card>

                <x-card 
                    icon="heroicon-o-clipboard-document-check"
                    title="Formulir Peminjaman"
                    description="Lengkapi informasi peminjaman sarpras."
                    class="c-card--form"
                >
                    <form class="c-card-form">
                        <div class="c-card__form-grid">
                            <x-input.text name="borrower_name" label="Nama Peminjam" placeholder="Nama lengkap" />
                            <x-input.select name="asset_type" label="Jenis Sarpras">
                                <option value="">Pilih jenis</option>
                                <option value="ruang">Ruang Rapat</option>
                                <option value="perangkat">Perangkat IT</option>
                            </x-input.select>
                            <x-input.text type="date" name="borrow_date" label="Tanggal Pinjam" />
                            <x-input.text type="date" name="return_date" label="Tanggal Kembali" />
                        </div>

                        <x-input.textarea name="purpose" label="Tujuan" placeholder="Tuliskan tujuan peminjaman" :rows="3" />
                    </form>

                    <x-slot:footer>
                        <x-button variant="primary" icon="heroicon-o-paper-airplane">Kirim Permohonan</x-button>
                        <x-button variant="secondary">Batal</x-button>
                    </x-slot:footer>
                </x-card>
            </div>
        </section>

        {{-- Section Components --}}
        <section class="component-demo" style="margin-top: 2rem;">
            <h2 class="component-demo__title">Section Components</h2>
            <p class="component-demo__description">Gunakan section untuk membungkus area konten (filter, tabel, atau form panjang) di layout utama.</p>

            <div class="c-section" style="padding: 0; border: none; gap: 24px;">
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
                            <option value="pending">Menunggu</option>
                            <option value="processing">Diproses</option>
                        </x-input.select>
                        <x-input.text type="date" name="start_date" label="Tanggal Mulai" />
                        <x-input.text type="date" name="end_date" label="Tanggal Akhir" />
                        <x-input.text name="keyword" label="Kata Kunci" placeholder="Cari peminjam atau kode" />
                    </div>

                    <x-slot:footer>
                        <x-button variant="secondary" size="sm">Reset</x-button>
                        <x-button variant="primary" size="sm">Cari</x-button>
                    </x-slot:footer>
                </x-section>

                <x-section 
                    title="Info Pengingat"
                    description="Bagikan catatan penting untuk tim layanan."
                    variant="muted"
                >
                    <p>Pastikan semua peminjam mengunggah bukti penggunaan ruangan sebelum tanggal pengembalian.</p>
                </x-section>
            </div>
        </section>
    </div>
</body>
</html>
