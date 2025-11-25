@extends('layouts.app')

@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Kelola informasi profil dan pengaturan akun Anda')

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

    @if(session('info'))
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="info" title="Informasi" :duration="5000">
                {{ session('info') }}
            </x-toast>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr;gap:24px;">
        {{-- Ringkasan Profil --}}
        <x-detail-section 
            :title="$user->name"
            description="Ringkasan singkat informasi akun dan status profil Anda"
        >
            <x-slot name="headerActions">
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <a href="{{ route('profile.edit') }}" style="text-decoration: none;">
                        <x-button variant="secondary" size="small" icon="heroicon-o-pencil">
                            Edit Profil
                        </x-button>
                    </a>
                    @if(!$is_sso_user)
                        <a href="{{ route('profile.password.edit') }}" style="text-decoration: none;">
                            <x-button variant="secondary" size="small" icon="heroicon-o-lock-closed">
                                Ganti Password
                            </x-button>
                        </a>
                    @endif
                </div>
            </x-slot>

            <x-detail-list :columns="2" variant="bordered">
                <x-detail-item label="Nama Lengkap">
                    {{ $user->name }}
                </x-detail-item>

                <x-detail-item label="Username">
                    {{ $user->username }}
                </x-detail-item>

                <x-detail-item label="Email">
                    {{ $user->email }}
                </x-detail-item>

                <x-detail-item label="Nomor Handphone">
                    {{ $user->phone ?? 'Tidak diisi' }}
                </x-detail-item>

                <x-detail-item label="Tipe User">
                    @if($user->user_type)
                        <x-badge variant="primary" size="sm">
                            {{ ucfirst($user->user_type) }}
                        </x-badge>
                    @else
                        -
                    @endif
                </x-detail-item>

                <x-detail-item label="Role">
                    @if(method_exists($user, 'getRoleDisplayName') && $user->getRoleDisplayName())
                        <x-badge variant="success" size="sm">
                            {{ $user->getRoleDisplayName() }}
                        </x-badge>
                    @else
                        -
                    @endif
                </x-detail-item>

                <x-detail-item label="Status Profil">
                    @if($user->profile_completed)
                        <x-badge variant="success" size="sm">Lengkap</x-badge>
                    @else
                        <x-badge variant="warning" size="sm">Belum Lengkap</x-badge>
                    @endif
                </x-detail-item>

                <x-detail-item label="Tanggal Dibuat">
                    {{ $user->created_at?->format('d/m/Y H:i') }}
                </x-detail-item>

                <x-detail-item label="Terakhir Diperbarui">
                    {{ $user->updated_at?->format('d/m/Y H:i') }}
                </x-detail-item>

                @if($user->profile_completed_at)
                    <x-detail-item label="Profil Diselesaikan">
                        {{ $user->profile_completed_at->format('d/m/Y H:i') }}
                    </x-detail-item>
                @endif

                @if($user->sso_id)
                    <x-detail-item label="SSO ID">
                        {{ $user->sso_id }}
                    </x-detail-item>

                    <x-detail-item label="SSO Provider">
                        {{ ucfirst($user->sso_provider ?? 'poliwangi') }}
                    </x-detail-item>
                @endif
            </x-detail-list>

            @if($is_sso_user)
                <x-slot name="footer">
                    <x-alert variant="warning" icon="heroicon-o-information-circle">
                        <strong>Akun SSO</strong><br>
                        Akun Anda dikelola melalui SSO {{ $user->sso_provider ?? 'Poliwangi' }}. Ubah password melalui portal SSO.
                    </x-alert>
                </x-slot>
            @endif
        </x-detail-section>

        {{-- Informasi Spesifik --}}
        @if($user->user_type === 'mahasiswa' && isset($student))
        <x-detail-section 
            title="Informasi Mahasiswa" 
            description="Detail data akademik untuk pengguna dengan tipe mahasiswa"
        >
            <x-detail-list :columns="2" variant="bordered">
                <x-detail-item label="NIM">
                    {{ $student->nim }}
                </x-detail-item>

                <x-detail-item label="Angkatan">
                    {{ $student->angkatan ?? '-' }}
                </x-detail-item>

                <x-detail-item label="Jurusan">
                    {{ $jurusan->nama_jurusan ?? '-' }}
                </x-detail-item>

                <x-detail-item label="Program Studi">
                    {{ $prodi->nama_prodi ?? '-' }}
                </x-detail-item>

                <x-detail-item label="Status Mahasiswa">
                    @if(($student->status_mahasiswa ?? 'aktif') === 'aktif')
                        <x-badge variant="success" size="sm">
                            {{ ucfirst($student->status_mahasiswa ?? 'aktif') }}
                        </x-badge>
                    @else
                        <x-badge variant="warning" size="sm">
                            {{ ucfirst($student->status_mahasiswa ?? 'aktif') }}
                        </x-badge>
                    @endif
                </x-detail-item>
            </x-detail-list>
        </x-detail-section>

        @elseif($user->user_type === 'staff' && isset($staff))
        <x-detail-section 
            title="Informasi Staff" 
            description="Detail data pegawai/staff untuk pengguna tipe staff"
        >
            <x-detail-list :columns="2" variant="bordered">
                @if($staff->nip)
                <x-detail-item label="NIP">
                    {{ $staff->nip }}
                </x-detail-item>
                @endif

                <x-detail-item label="Unit Kerja">
                    {{ $unit->nama ?? '-' }}
                </x-detail-item>

                <x-detail-item label="Jabatan">
                    {{ $position->nama ?? '-' }}
                </x-detail-item>
            </x-detail-list>
        </x-detail-section>
        @endif

        {{-- Role & Permission --}}
        <x-detail-section 
            title="Role & Akses" 
            description="Role dan permission yang dimiliki"
        >
            <x-detail-list :columns="1">
                <x-detail-item label="Role Utama">
                    <x-badge variant="primary">{{ $user->getRoleDisplayName() }}</x-badge>
                </x-detail-item>
            </x-detail-list>
        </x-detail-section>

    </div>

</div>
@endsection
