@php($ssoEnabled = filter_var(config('services.oauth_server.sso_enable'), FILTER_VALIDATE_BOOLEAN))

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar - {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page" style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);padding:24px;">
    <div class="auth-shell" style="width:100%;max-width:560px;">
        <x-card
            class="auth-card c-card--centered"
            style="padding:36px;border-radius:22px;box-shadow:0 28px 70px rgba(148, 163, 184, 0.25);"
            icon="heroicon-o-sparkles"
            title="Buat Akun Baru"
            description="Aktifkan akun Anda untuk memulai peminjaman sarana prasarana."
        >

            <form method="POST" action="{{ route('register.store') }}" class="auth-form" style="display:flex;flex-direction:column;gap:20px;">
                @csrf

                <div style="display:flex;flex-direction:column;gap:20px;">
                    <x-input.text
                        name="name"
                        label="Nama Lengkap"
                        placeholder="Masukkan nama lengkap"
                        icon="heroicon-o-identification"
                        :value="old('name')"
                        required
                        autofocus
                        autocomplete="name"
                        :error="$errors->first('name')"
                    />

                    <x-input.text
                        name="username"
                        label="Username"
                        placeholder="Contoh: johndoe123"
                        icon="heroicon-o-at-symbol"
                        :value="old('username')"
                        required
                        pattern="^[a-zA-Z0-9_]+$"
                        helper="Gunakan huruf, angka, atau underscore."
                        :error="$errors->first('username')"
                    />

                    <x-input.text
                        type="email"
                        name="email"
                        label="Email"
                        placeholder="nama@contoh.com"
                        icon="heroicon-o-envelope"
                        :value="old('email')"
                        required
                        autocomplete="email"
                        :error="$errors->first('email')"
                    />

                    <x-input.text
                        name="phone"
                        label="Nomor Handphone"
                        placeholder="081234567890"
                        icon="heroicon-o-phone"
                        :value="old('phone')"
                        required
                        inputmode="tel"
                        helper="Gunakan format angka saja tanpa spasi."
                        :error="$errors->first('phone')"
                    />

                    <x-input.select
                        name="user_type"
                        label="Tipe Pengguna"
                        required
                        :error="$errors->first('user_type')"
                        placeholder="Pilih tipe pengguna"
                    >
                        <option value="mahasiswa" {{ old('user_type') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                        <option value="staff" {{ old('user_type') === 'staff' ? 'selected' : '' }}>Staff</option>
                    </x-input.select>
                </div>

                <div style="display:flex;flex-direction:column;gap:20px;">
                    <x-input.text
                        type="password"
                        name="password"
                        label="Password"
                        placeholder="Minimal 8 karakter"
                        with-toggle
                        required
                        autocomplete="new-password"
                        minlength="8"
                        helper="Minimal 8 karakter dan mengandung huruf besar, huruf kecil, angka, serta simbol."
                        :error="$errors->first('password')"
                    />

                    <x-input.text
                        type="password"
                        name="password_confirmation"
                        label="Konfirmasi Password"
                        placeholder="Ulangi password"
                        with-toggle
                        required
                        autocomplete="new-password"
                        minlength="8"
                        helper="Harus sama dengan password di atas."
                        :error="$errors->first('password_confirmation')"
                    />
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;margin-top:12px;">
                    <x-button type="submit" variant="primary" block icon="heroicon-o-user-plus">
                        Daftar Sekarang
                    </x-button>

                    @if($ssoEnabled)
                        <x-button type="button" variant="secondary" block icon="heroicon-o-building-library"
                            onclick="window.location='{{ route('oauth.login') }}'"
                        >
                            Daftar dengan SSO Poliwangi
                        </x-button>
                    @endif
                </div>
            </form>

            <x-slot name="footer">
                <div class="auth-footer" style="width:100%;display:flex;flex-direction:column;gap:6px;text-align:center;font-size:0.9rem;color:var(--text-muted);">
                    <p style="margin:0;">Sudah punya akun? <a href="{{ route('login') }}" style="color:var(--brand-accent);font-weight:600;">Masuk di sini</a></p>
                    <p style="margin:0;">© {{ now()->year }} {{ config('app.name') }}. Semua hak dilindungi.</p>
                </div>
            </x-slot>
        </x-card>
    </div>

    {{-- Toast Notifications --}}
    <div id="toast-container" style="position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:12px;max-width:400px;">
        @if(session('success'))
            <x-toast type="success" title="Berhasil!">{{ session('success') }}</x-toast>
        @endif

        @if(session('error'))
            <x-toast type="danger" title="Error!">{{ session('error') }}</x-toast>
        @endif

        @if(session('warning'))
            <x-toast type="warning" title="Perhatian!">{{ session('warning') }}</x-toast>
        @endif

        @if(session('info'))
            <x-toast type="info" title="Informasi">{{ session('info') }}</x-toast>
        @endif

        @if($errors->any())
            <x-toast type="danger" title="Terjadi Kesalahan!">
                <ul style="margin:0;padding-left:20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-toast>
        @endif
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toasts = document.querySelectorAll('[data-toast]');
        
        toasts.forEach(toast => {
            // Show with animation
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                toast.style.transition = 'all 0.3s ease';
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            }, 10);

            // Close button
            const closeBtn = toast.querySelector('[data-toast-close]');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => toast.remove(), 300);
                });
            }

            // Auto hide after duration
            const duration = toast.dataset.toastDuration || 5000;
            if (duration > 0) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }
        });
    });
    </script>
</body>
</html>
