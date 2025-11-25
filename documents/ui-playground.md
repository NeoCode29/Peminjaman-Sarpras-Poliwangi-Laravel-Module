# UI Playground (`/playground-ui`)

Halaman ini dipakai untuk mengembangkan komponen sebelum dipasang ke fitur utama.

## Cara Pakai

1. **Siapkan komponen**
   - Simpan partial/komponen Blade di `resources/views/components/...` atau folder eksperimental lain.
   - Gunakan data dummy melalui array di view, view model sederhana, atau service mock agar tidak memodifikasi database.

2. **Render di sandbox**
   - Buka `resources/views/playground/ui.blade.php`.
   - Tambahkan section baru dan panggil komponen dengan `@include` atau `<x-component>`.
   - Jika butuh styling/JS tambahan, simpan sementara di `@push('styles')` / `@push('scripts')` pada view sandbox.

3. **Evaluasi**
   - Akses `/playground-ui` untuk melihat hasil.
   - Uji interaksi memakai asset utama (`app.css` dan `app.js`) agar behavior konsisten dengan aplikasi.

4. **Promosi ke produksi**
   - Setelah desain/JS disetujui, pindahkan kode ke berkas utama:
     - **CSS:** gabungkan ke `resources/css/app.css`.
     - **JS:** gabungkan ke `resources/js/app.js`.
   - Hapus sementara `@push` di sandbox untuk mencegah duplikasi.
   - Integrasikan komponen ke halaman target.

   > **Status Tombol:** Komponen tombol (`.c-button` beserta variannya) sudah dipindahkan ke `resources/css/app.css` dan digunakan di halaman produksi (dashboard, manajemen user/role, login). Gunakan kelas tersebut langsung, dan manfaatkan Blade Icons (`blade-ui-kit/blade-heroicons`) untuk ikon tombol.

   > **Status Input:** Komponen form (`.c-input`, `.c-choice`, `.c-switch`, `.c-file`) kini tersedia di `resources/css/app.css`. Gunakan kelas tersebut pada halaman form produksi agar tampilan dan interaksi konsisten dengan pedoman desain.

5. **Kebersihan kode**
   - Hapus section sandbox yang sudah dibawa ke produksi.
   - Jangan gunakan Bootstrap atau jQuery; tetap gunakan CSS kustom dan JavaScript vanilla sesuai aturan proyek.

## Catatan Tambahan

- Buat branch Git khusus saat mengembangkan banyak komponen.
- Sertakan catatan singkat di PR terkait komponen yang siap dipromosikan.
- Jika perlu variasi data, gunakan dependency injection atau factory lokal yang tidak memengaruhi state aplikasi.
