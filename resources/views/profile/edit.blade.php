@extends('layouts.app')

@section('title', 'Edit Profil')
@section('page-title', 'Edit Profil')
@section('page-subtitle', 'Perbarui informasi profil Anda')

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

    <form method="POST" action="{{ route('profile.update') }}" class="form-section">
        @csrf
        @method('PUT')

        {{-- Informasi Dasar --}}
        <x-form-group
            title="Informasi Dasar"
            description="Data dasar akun dan kontak Anda."
            icon="heroicon-o-identification"
        >
            <x-form-section>
                <div class="form-section__grid">
                    <x-input.text
                        name="username"
                        label="Username"
                        :value="$user->username"
                        readonly
                        icon="heroicon-o-at-symbol"
                        helper="Username tidak dapat diubah"
                    />

                    <x-input.text
                        name="name"
                        label="Nama Lengkap"
                        placeholder="Masukkan nama lengkap"
                        :value="old('name', $user->name)"
                        required
                        icon="heroicon-o-identification"
                        :error="$errors->first('name')"
                    />

                    <x-input.text
                        type="email"
                        name="email"
                        label="Email"
                        placeholder="contoh@email.com"
                        :value="old('email', $user->email)"
                        required
                        icon="heroicon-o-envelope"
                        :error="$errors->first('email')"
                    />

                    <x-input.text
                        type="tel"
                        name="phone"
                        label="Nomor Handphone"
                        placeholder="08xxxxxxxxxx"
                        :value="old('phone', $user->phone)"
                        required
                        icon="heroicon-o-phone"
                        :error="$errors->first('phone')"
                    />
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Informasi Spesifik --}}
        <x-form-group
            title="Informasi {{ $user->user_type === 'mahasiswa' ? 'Akademik' : 'Kepegawaian' }}"
            description="Data {{ $user->user_type === 'mahasiswa' ? 'akademik mahasiswa' : 'kepegawaian staff' }}."
            icon="{{ $user->user_type === 'mahasiswa' ? 'heroicon-o-academic-cap' : 'heroicon-o-briefcase' }}"
        >
            <x-form-section>
                <div class="form-section__grid">
                    @if($user->user_type === 'mahasiswa')
                        @if(isset($student))
                        <x-input.text
                            name="nim"
                            label="NIM"
                            :value="$student->nim"
                            readonly
                            icon="heroicon-o-hashtag"
                        />

                        <x-input.text
                            name="angkatan"
                            label="Angkatan"
                            :value="$student->angkatan"
                            readonly
                            icon="heroicon-o-calendar"
                        />

                        <x-input.select
                            name="jurusan_id"
                            label="Jurusan"
                            required
                            icon="heroicon-o-building-library"
                            :error="$errors->first('jurusan_id')"
                            placeholder="Pilih Jurusan"
                        >
                            @foreach($jurusans as $jurusan)
                                <option value="{{ $jurusan->id }}" {{ old('jurusan_id', $student->jurusan_id) == $jurusan->id ? 'selected' : '' }}>
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
                                <option value="{{ $prodi->id }}" {{ old('prodi_id', $student->prodi_id) == $prodi->id ? 'selected' : '' }}>
                                    {{ $prodi->nama_prodi }}
                                </option>
                            @endforeach
                        </x-input.select>
                        @endif

                    @elseif($user->user_type === 'staff')
                        @if(isset($staff))
                        <x-input.text
                            name="nip"
                            label="NIP (Opsional)"
                            placeholder="Masukkan NIP jika ada"
                            :value="old('nip', $staff->nip)"
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
                                <option value="{{ $unit->id }}" {{ old('unit_id', $staff->unit_id) == $unit->id ? 'selected' : '' }}>
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
                                <option value="{{ $position->id }}" {{ old('position_id', $staff->position_id) == $position->id ? 'selected' : '' }}>
                                    {{ $position->nama }}
                                </option>
                            @endforeach
                        </x-input.select>
                        @endif
                    @endif
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
                Simpan Perubahan
            </x-button>
        </div>
    </form>
</div>

@if($user->user_type === 'mahasiswa')
<script>
    // Dynamic Prodi filter berdasarkan Jurusan
    document.addEventListener('DOMContentLoaded', function() {
        const jurusanSelect = document.querySelector('select[name="jurusan_id"]');
        const prodiSelect = document.querySelector('select[name="prodi_id"]');

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
@endsection
