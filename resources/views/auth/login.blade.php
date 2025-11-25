{{-- SSO enabled check from controller --}}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page" style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);padding:24px;">
    <div class="auth-shell" style="width:100%;max-width:480px;">
        <x-card
            class="auth-card c-card--centered"
            style="padding:32px;border-radius:20px;box-shadow:0 24px 60px rgba(148, 163, 184, 0.25);"
            icon="heroicon-o-lock-closed"
            title="Selamat Datang Kembali"
            description="Masuk untuk mengelola peminjaman sarana prasarana."
        >

            <form method="POST" action="{{ route('login.store') }}" class="auth-form" style="display:flex;flex-direction:column;gap:12px;margin-top:16px;">
                @csrf

                <x-input.text
                    name="username"
                    label="Email atau Username"
                    placeholder="Masukkan email atau username"
                    icon="heroicon-o-user"
                    :value="old('username')"
                    required
                    autofocus
                    autocomplete="username"
                    :error="$errors->first('username')"
                />

                <x-input.text
                    type="password"
                    name="password"
                    label="Password"
                    placeholder="Masukkan password"
                    with-toggle
                    required
                    autocomplete="current-password"
                    :error="$errors->first('password')"
                />

                <div style="display:flex;flex-direction:column;gap:12px;margin-top:12px;">
                    <x-button type="submit" variant="primary" block icon="heroicon-o-arrow-right-circle">
                        Masuk ke Dashboard
                    </x-button>

                    @if($ssoEnabled)
                        <x-button type="button" variant="secondary" block icon="heroicon-o-building-library"
                            onclick="window.location='{{ route('oauth.login') }}'"
                        >
                            Masuk dengan SSO Poliwangi
                        </x-button>
                    @endif
                </div>
            </form>

            <x-slot name="footer">
                <div class="auth-footer" style="width:100%;display:flex;flex-direction:column;gap:6px;text-align:center;font-size:0.88rem;color:var(--text-muted);">
                    @php($registrationEnabled = \App\Models\SystemSetting::get('enable_manual_registration', '1'))
                    @if(filter_var($registrationEnabled, FILTER_VALIDATE_BOOLEAN))
                        <p style="margin:0;">Belum punya akun? <a href="{{ route('register') }}" style="color:var(--brand-accent);font-weight:600;">Daftar di sini</a></p>
                    @endif
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
