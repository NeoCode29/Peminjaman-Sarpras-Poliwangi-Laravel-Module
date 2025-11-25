<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lengkapi Profil - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="c-app-body">
    <div class="c-app-container" style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;">
        
        <x-card 
            class="profile-setup-card c-card--centered"
            style="max-width:600px;width:100%;padding:36px;border-radius:22px;box-shadow:0 28px 70px rgba(148, 163, 184, 0.25);"
            icon="heroicon-o-user-plus"
            title="Lengkapi Profil Anda"
            description="Silakan lengkapi profil Anda untuk dapat menggunakan sistem peminjaman sarana dan prasarana."
        >
            <form method="POST" action="{{ route('profile.complete-setup') }}" style="display:flex;flex-direction:column;gap:20px;">
                @csrf

                {{-- Informasi Dasar --}}
                <div style="padding:20px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
                    <div style="display:flex;flex-direction:column;gap:16px;">
                        <x-input.text
                            name="name"
                            label="Nama Lengkap"
                            :value="$user->name"
                            readonly
                            icon="heroicon-o-identification"
                        />

                        <x-input.text
                            type="email"
                            name="email"
                            label="Email"
                            :value="$user->email"
                            readonly
                            icon="heroicon-o-envelope"
                        />

                        @if(!empty($user->phone))
                            {{-- Phone sudah ada (dari registrasi/SSO) --}}
                            <x-input.text
                                type="tel"
                                name="phone"
                                label="Nomor Handphone"
                                :value="$user->phone"
                                readonly
                                icon="heroicon-o-phone"
                                helper="Nomor handphone dari data registrasi/SSO"
                            />
                        @else
                            {{-- Phone belum ada (SSO tanpa phone) --}}
                            <x-input.text
                                type="tel"
                                name="phone"
                                label="Nomor Handphone"
                                placeholder="081234567890"
                                :value="old('phone')"
                                required
                                inputmode="tel"
                                pattern="[0-9]{10,15}"
                                icon="heroicon-o-phone"
                                helper="Masukkan nomor handphone aktif Anda"
                                :error="$errors->first('phone')"
                            />
                        @endif

                        <x-input.text
                            name="user_type_display"
                            label="Tipe User"
                            :value="$user->user_type_display"
                            readonly
                            icon="heroicon-o-user-circle"
                        />
                    </div>
                </div>

                {{-- Informasi Spesifik --}}
                <div style="padding:20px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
                    <div style="display:flex;flex-direction:column;gap:16px;">
                        @if($user->user_type === 'mahasiswa')
                            @php
                                // Check if username is a valid NIM (12 digits) - from SSO
                                $isNimFromSso = preg_match('/^\d{12}$/', $user->username);
                            @endphp

                            @if($isNimFromSso)
                                {{-- SSO User: NIM dari username --}}
                                <x-input.text
                                    name="nim"
                                    label="NIM"
                                    :value="$user->username"
                                    readonly
                                    icon="heroicon-o-hashtag"
                                    helper="NIM dari SSO"
                                />

                                <x-input.text
                                    name="angkatan"
                                    label="Angkatan"
                                    :value="'20' . substr($user->username, 2, 2)"
                                    readonly
                                    icon="heroicon-o-calendar"
                                    helper="Angkatan dari NIM (digit 3-4)"
                                />
                            @else
                                {{-- Manual Registration: Input NIM manual --}}
                                <x-input.text
                                    name="nim"
                                    label="NIM"
                                    placeholder="Contoh: 224567890123"
                                    :value="old('nim')"
                                    required
                                    maxlength="12"
                                    pattern="\d{12}"
                                    icon="heroicon-o-hashtag"
                                    helper="Masukkan NIM 12 digit"
                                    :error="$errors->first('nim')"
                                    id="nim-input"
                                />

                                <x-input.text
                                    name="angkatan"
                                    label="Angkatan"
                                    value="-"
                                    readonly
                                    icon="heroicon-o-calendar"
                                    helper="Otomatis terisi dari NIM (digit 3-4)"
                                    id="angkatan-input"
                                />
                            @endif

                            <x-input.select
                                name="jurusan_id"
                                label="Jurusan"
                                required
                                icon="heroicon-o-building-library"
                                :error="$errors->first('jurusan_id')"
                                placeholder="Pilih Jurusan"
                            >
                                @foreach($jurusans as $jurusan)
                                    <option value="{{ $jurusan->id }}" {{ old('jurusan_id') == $jurusan->id ? 'selected' : '' }}>
                                        {{ $jurusan->nama_jurusan }}
                                    </option>
                                @endforeach
                            </x-input.select>

                            <x-input.select
                                name="prodi_id"
                                label="Program Studi"
                                required
                                icon="heroicon-o-academic-cap"
                                :error="$errors->first('prodi_id')"
                                placeholder="Pilih Program Studi"
                            >
                                @foreach($prodis as $prodi)
                                    <option value="{{ $prodi->id }}" {{ old('prodi_id') == $prodi->id ? 'selected' : '' }}>
                                        {{ $prodi->nama_prodi }}
                                    </option>
                                @endforeach
                            </x-input.select>

                        @elseif($user->user_type === 'staff')
                            <x-input.text
                                name="nip"
                                label="NIP (Opsional)"
                                placeholder="Masukkan NIP jika ada"
                                :value="old('nip')"
                                icon="heroicon-o-identification"
                                helper="NIP dapat dikosongkan jika belum memiliki"
                                :error="$errors->first('nip')"
                            />

                            <x-input.select
                                name="unit_id"
                                label="Unit Kerja"
                                required
                                icon="heroicon-o-building-office"
                                :error="$errors->first('unit_id')"
                                placeholder="Pilih Unit Kerja"
                            >
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->nama }}
                                    </option>
                                @endforeach
                            </x-input.select>

                            <x-input.select
                                name="position_id"
                                label="Jabatan"
                                required
                                icon="heroicon-o-briefcase"
                                :error="$errors->first('position_id')"
                                placeholder="Pilih Jabatan"
                            >
                                @foreach($positions as $position)
                                    <option value="{{ $position->id }}" {{ old('position_id') == $position->id ? 'selected' : '' }}>
                                        {{ $position->nama }}
                                    </option>
                                @endforeach
                            </x-input.select>
                        @endif
                    </div>
                </div>

                <x-button type="submit" variant="primary" block icon="heroicon-o-check-circle">
                    Simpan & Lanjutkan
                </x-button>
            </form>
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
        // Toast animations
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

    @if($user->user_type === 'mahasiswa')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-fill angkatan from NIM (ONLY for manual registration)
            const nimInput = document.getElementById('nim-input');
            const angkatanInput = document.getElementById('angkatan-input');

            if (nimInput && angkatanInput) {
                // Function to update angkatan
                function updateAngkatan() {
                    const nim = nimInput.value.replace(/\D/g, ''); // Remove non-digits
                    
                    if (nim.length >= 4) {
                        // Extract digits 3-4 (index 2-3) for year
                        const angkatanDigits = nim.substring(2, 4);
                        const angkatan = 2000 + parseInt(angkatanDigits, 10);
                        angkatanInput.value = angkatan;
                    } else {
                        angkatanInput.value = '-';
                    }
                }
                
                // Attach event listeners
                nimInput.addEventListener('input', updateAngkatan);
                nimInput.addEventListener('change', updateAngkatan);
                
                // Initial update if has value
                if (nimInput.value) {
                    updateAngkatan();
                }
            }

            // Dynamic Prodi filter berdasarkan Jurusan
            const jurusanSelect = document.querySelector('select[name="jurusan_id"]');
            const prodiSelect = document.querySelector('select[name="prodi_id"]');

            // Dynamic Prodi filter
            if (jurusanSelect && prodiSelect) {
                jurusanSelect.addEventListener('change', function() {
                    const jurusanId = this.value;
                    
                    // Clear prodi options except placeholder
                    prodiSelect.innerHTML = '<option value="">Pilih Program Studi</option>';
                    
                    if (jurusanId) {
                        // Fetch prodis via AJAX
                        fetch(`{{ route('profile.get-prodis') }}?jurusan_id=${jurusanId}`)
                            .then(response => response.json())
                            .then(data => {
                                data.forEach(prodi => {
                                    const option = document.createElement('option');
                                    option.value = prodi.id;
                                    option.textContent = prodi.nama_prodi;
                                    prodiSelect.appendChild(option);
                                });
                            })
                            .catch(error => console.error('Error fetching prodis:', error));
                    }
                });
            }
        });
    </script>
    @endif
</body>
</html>
