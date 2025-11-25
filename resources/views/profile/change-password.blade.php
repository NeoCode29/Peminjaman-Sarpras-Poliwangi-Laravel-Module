@extends('layouts.app')

@section('title', 'Ganti Password')
@section('page-title', 'Ganti Password')
@section('page-subtitle', 'Perbarui password Anda untuk keamanan akun')

@section('content')
<div class="page-content">
    {{-- Toast Notifications --}}
    @if(session('success'))
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="success" title="Berhasil" :duration="5000">
                {{ session('success') }}
            </x-toast>
        </div>
    @endif

    @if($errors->any())
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="danger" title="Terjadi Kesalahan" :duration="7000">
                {{ $errors->first() }}
            </x-toast>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.password.update') }}" class="form-section">
        @csrf
        @method('PUT')

        <x-form-group
            title="Ubah Password"
            description="Perbarui password Anda untuk menjaga keamanan akun."
            icon="heroicon-o-lock-closed"
        >
            <x-form-section>
                {{-- Info Security --}}
                <div style="padding:16px;background:#eff6ff;border:1px solid #93c5fd;border-radius:10px;display:flex;align-items:start;gap:12px;margin-bottom:20px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:20px;height:20px;color:#2563eb;flex-shrink:0;margin-top:2px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <div>
                        <p style="font-size:13px;color:#1e40af;font-weight:600;margin-bottom:4px;">Tips Keamanan Password</p>
                        <ul style="font-size:13px;color:#1e40af;margin:0;padding-left:20px;">
                            <li>Gunakan minimal 8 karakter</li>
                            <li>Kombinasikan huruf besar, huruf kecil, dan angka</li>
                            <li>Hindari password yang mudah ditebak</li>
                            <li>Jangan gunakan password yang sama di aplikasi lain</li>
                        </ul>
                    </div>
                </div>

                <div class="form-section__grid">

            <x-input.text
                type="password"
                name="current_password"
                label="Password Lama"
                placeholder="Masukkan password lama"
                required
                with-toggle
                icon="heroicon-o-lock-closed"
                helper="Masukkan password lama Anda untuk verifikasi"
                :error="$errors->first('current_password')"
            />

            <x-input.text
                type="password"
                name="password"
                label="Password Baru"
                placeholder="Minimal 8 karakter"
                required
                with-toggle
                icon="heroicon-o-key"
                helper="Gunakan kombinasi huruf besar, kecil, dan angka"
                :error="$errors->first('password')"
            />

            <x-input.text
                type="password"
                name="password_confirmation"
                label="Konfirmasi Password Baru"
                placeholder="Ulangi password baru"
                required
                with-toggle
                icon="heroicon-o-shield-check"
                helper="Masukkan ulang password baru untuk konfirmasi"
                :error="$errors->first('password_confirmation')"
            />
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Actions --}}
        <div class="form-section__actions form-section__actions--footer">
            <a href="{{ route('profile.show') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Batal
                </x-button>
            </a>
            <x-button type="submit" variant="primary" icon="heroicon-o-check-circle">
                Simpan Password Baru
            </x-button>
        </div>
    </form>
</div>
@endsection
