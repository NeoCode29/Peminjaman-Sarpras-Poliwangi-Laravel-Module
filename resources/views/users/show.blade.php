@extends('layouts.app')

@section('title', 'Detail User')
@section('page-title', 'Detail User')
@section('page-subtitle', 'Informasi lengkap pengguna')

@section('content')
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

    {{-- Section: Header & Ringkasan User --}}
    <x-detail-section
        :title="$user->name"
        description="Ringkasan singkat informasi akun dan status pengguna"
    >
        {{-- Detail utama dalam 2 kolom --}}
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
                    @if($user->role)
                        <x-badge variant="success" size="sm">
                            {{ optional($user->role)->display_name ?? optional($user->role)->name }}
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
                    {{ $user->created_at->format('d/m/Y H:i') }}
                </x-detail-item>

                <x-detail-item label="Terakhir Diperbarui">
                    {{ $user->updated_at->format('d/m/Y H:i') }}
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
                        {{ ucfirst($user->sso_provider) }}
                    </x-detail-item>

                    @if($user->last_sso_login)
                        <x-detail-item label="Login SSO Terakhir">
                            {{ $user->last_sso_login->format('d/m/Y H:i') }}
                        </x-detail-item>
                    @endif
                @endif
            </x-detail-list>

        {{-- Aksi utama --}}
        <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem;">
            @can('update', $user)
                <a href="{{ route('user-management.edit', $user->id) }}">
                    <x-button type="button" variant="primary" icon="heroicon-o-pencil-square">
                        Edit User
                    </x-button>
                </a>

                <x-button
                    type="button"
                    variant="secondary"
                    icon="heroicon-o-key"
                    onclick="document.getElementById('changePasswordModal').open()"
                >
                    Ubah Password
                </x-button>

                @if(! $user->isBlocked())
                    <x-button
                        type="button"
                        variant="danger"
                        icon="heroicon-o-lock-closed"
                        onclick="document.getElementById('blockUserModal').open()"
                    >
                        Blokir User
                    </x-button>
                @else
                    <x-button
                        type="button"
                        variant="success"
                        icon="heroicon-o-lock-open"
                        onclick="document.getElementById('unblockUserModal').open()"
                    >
                        Buka Blokir
                    </x-button>
                @endif
            @endcan

            @can('delete', $user)
                <x-button
                    type="button"
                    variant="danger"
                    icon="heroicon-o-trash"
                    onclick="document.getElementById('deleteUserModal').open()"
                >
                    Hapus User
                </x-button>
            @endcan
        </div>
    </x-detail-section>

    @can('update', $user)
        @if(! $user->isBlocked())
            <x-modal id="blockUserModal" title="Blokir User" size="sm">
                <form id="block-user-form" method="POST" action="{{ route('user-management.block', $user) }}" style="display: flex; flex-direction: column; gap: 1rem;">
                    @csrf

                    <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                        Anda akan memblokir akun <strong>{{ $user->name }}</strong>. User yang diblokir tidak dapat login ke sistem.
                    </p>

                    <div class="c-input">
                        <label for="blocked_until" class="c-input__label">Blokir sampai tanggal (opsional)</label>
                        <div class="c-input__control">
                            <input
                                type="date"
                                id="blocked_until"
                                name="blocked_until"
                                class="c-input__element c-input__element--date"
                            >
                        </div>
                        <p class="c-input__helper">Biarkan kosong jika blokir tanpa batas waktu.</p>
                    </div>

                    <div class="c-input">
                        <label for="blocked_reason" class="c-input__label">Alasan blokir (opsional)</label>
                        <div class="c-input__control">
                            <textarea
                                id="blocked_reason"
                                name="blocked_reason"
                                class="c-input__element c-input__element--textarea"
                                rows="3"
                                placeholder="Tuliskan alasan singkat pemblokiran user ini"
                            ></textarea>
                        </div>
                    </div>
                </form>

                <x-slot:footer>
                    <x-button type="button" variant="secondary" data-modal-close>
                        Batal
                    </x-button>
                    <x-button type="submit" variant="danger" icon="heroicon-o-lock-closed" form="block-user-form">
                        Blokir User
                    </x-button>
                </x-slot:footer>
            </x-modal>
        @else
            <x-modal id="unblockUserModal" title="Buka Blokir User" size="sm">
                <form id="unblock-user-form" method="POST" action="{{ route('user-management.unblock', $user) }}" style="display: flex; flex-direction: column; gap: 1rem;">
                    @csrf

                    <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                        Anda akan membuka blokir akun <strong>{{ $user->name }}</strong>. User akan dapat login kembali jika status lain terpenuhi.
                    </p>

                    @if($user->blocked_until)
                        <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                            Saat ini user diblokir hingga <strong>{{ $user->blocked_until->format('d/m/Y H:i') }}</strong>.
                        </p>
                    @endif

                    @if($user->blocked_reason)
                        <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                            Alasan blokir sebelumnya: <strong>{{ $user->blocked_reason }}</strong>
                        </p>
                    @endif
                </form>

                <x-slot:footer>
                    <x-button type="button" variant="secondary" data-modal-close>
                        Batal
                    </x-button>
                    <x-button type="submit" variant="success" icon="heroicon-o-lock-open" form="unblock-user-form">
                        Buka Blokir
                    </x-button>
                </x-slot:footer>
            </x-modal>
        @endif

        <x-modal id="changePasswordModal" title="Ubah Password User" size="sm">
            <form
                id="change-password-form"
                method="POST"
                action="{{ route('user-management.change-password', $user) }}"
                style="display: flex; flex-direction: column; gap: 1rem;"
            >
                @csrf

                <div class="c-input">
                    <label for="password" class="c-input__label">Password Baru</label>
                    <div class="c-input__control">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="c-input__element"
                            required
                            minlength="8"
                        >
                    </div>
                    <p class="c-input__helper">
                        Minimal 8 karakter dan mengandung huruf besar, huruf kecil, angka, serta simbol.
                    </p>
                </div>

                <div class="c-input">
                    <label for="password_confirmation" class="c-input__label">Konfirmasi Password Baru</label>
                    <div class="c-input__control">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="c-input__element"
                            required
                            minlength="8"
                        >
                    </div>
                    <p class="c-input__helper">
                        Ulangi password baru untuk konfirmasi.
                    </p>
                </div>
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" icon="heroicon-o-key" form="change-password-form">
                    Simpan Password
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan

    @can('delete', $user)
        <x-modal id="deleteUserModal" title="Hapus User" size="sm">
            <form
                id="delete-user-form"
                method="POST"
                action="{{ route('user-management.destroy', $user->id) }}"
                style="display: flex; flex-direction: column; gap: 1rem;"
            >
                @csrf
                @method('DELETE')

                <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                    Anda akan menghapus akun <strong>{{ $user->name }}</strong> secara permanen dari sistem.
                </p>

                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                    Tindakan ini tidak dapat dibatalkan. Data terkait seperti relasi mungkin juga akan terpengaruh.
                </p>
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button type="submit" variant="danger" icon="heroicon-o-trash" form="delete-user-form">
                    Hapus User
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan

    {{-- Section: Informasi Mahasiswa --}}
    @if($user->user_type === 'mahasiswa' && $user->student)
        <x-detail-section
            title="Informasi Mahasiswa"
            description="Detail data akademik untuk pengguna dengan tipe mahasiswa"
        >
            <x-detail-list :columns="2" variant="bordered">
                    <x-detail-item label="NIM">
                        {{ $user->student->nim }}
                    </x-detail-item>

                    <x-detail-item label="Angkatan">
                        {{ $user->student->angkatan }}
                    </x-detail-item>

                    @if($user->student->jurusan)
                        <x-detail-item label="Jurusan">
                            {{ $user->student->jurusan->nama_jurusan }}
                        </x-detail-item>
                    @endif

                    @if($user->student->prodi)
                        <x-detail-item label="Program Studi">
                            {{ $user->student->prodi->nama_prodi }}
                        </x-detail-item>
                    @endif

                    <x-detail-item label="Semester">
                        {{ $user->student->semester ?? 'Tidak diisi' }}
                    </x-detail-item>

                    <x-detail-item label="Status Mahasiswa">
                        @if($user->student->status_mahasiswa === 'aktif')
                            <x-badge variant="success" size="sm">
                                {{ ucfirst($user->student->status_mahasiswa) }}
                            </x-badge>
                        @else
                            <x-badge variant="warning" size="sm">
                                {{ ucfirst($user->student->status_mahasiswa) }}
                            </x-badge>
                        @endif
                    </x-detail-item>
                </x-detail-list>
        </x-detail-section>
    @endif

    {{-- Section: Informasi Staff/Pegawai --}}
    @if($user->user_type === 'staff' && $user->staffEmployee)
        <x-detail-section
            title="Informasi Staff"
            description="Detail data pegawai/staff untuk pengguna tipe staff"
        >
            <x-detail-list :columns="2" variant="bordered">
                    <x-detail-item label="NIP">
                        {{ $user->staffEmployee->nip }}
                    </x-detail-item>

                    @if($user->staffEmployee->unit)
                        <x-detail-item label="Unit">
                            {{ $user->staffEmployee->unit->nama }}
                        </x-detail-item>
                    @endif

                    @if($user->staffEmployee->position)
                        <x-detail-item label="Posisi/Jabatan">
                            {{ $user->staffEmployee->position->nama }}
                        </x-detail-item>
                    @endif
                </x-detail-list>
        </x-detail-section>
    @endif

    {{-- Section: Permissions berdasarkan Role --}}
    @if($user->role && $user->role->permissions && $user->role->permissions->count() > 0)
        <x-detail-section
            title="Permission yang Dimiliki"
            description="Hak akses yang melekat pada role pengguna ini"
        >
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                @foreach($user->role->permissions->groupBy('category') as $category => $permissions)
                    <x-card
                        :title="ucfirst($category)"
                        description="Daftar permission dalam kategori ini"
                    >
                        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                            @foreach($permissions as $permission)
                                <li style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                                    <x-badge variant="success" size="sm">Allow</x-badge>
                                    <span>{{ $permission->display_name ?? $permission->name }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endforeach
            </div>
        </x-detail-section>
    @endif
@endsection
