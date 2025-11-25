@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User: ' . ($user->name ?? 'User'))
@section('page-subtitle', 'Ubah informasi pengguna')

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

    <form action="{{ route('user-management.update', $user->id) }}" method="POST" class="form-section">
        @csrf
        @method('PUT')

        <x-form-group
            title="Informasi User"
            description="Perbarui data dasar akun pengguna."
            icon="heroicon-o-user-circle"
        >
            <x-form-section>
                <div class="form-section__grid">
                    {{-- Baris 1: Nama & Username --}}
                    <x-input.text
                        label="Nama Lengkap"
                        name="name"
                        id="name"
                        :value="old('name', $user->name)"
                        placeholder="Masukkan nama lengkap"
                        :required="true"
                        :error="$errors->first('name')"
                    />

                    <x-input.text
                        label="Username"
                        name="username"
                        id="username"
                        :value="old('username', $user->username)"
                        placeholder="Masukkan username"
                        :required="true"
                        :error="$errors->first('username')"
                    />

                    {{-- Baris 2: Email (full width) --}}
                    <div class="form-field form-field--full">
                        <x-input.text
                            label="Email"
                            name="email"
                            id="email"
                            type="email"
                            :value="old('email', $user->email)"
                            placeholder="email@example.com"
                            :required="true"
                            :error="$errors->first('email')"
                        />
                    </div>

                    {{-- Baris 3: Tipe User & Role --}}
                    <x-input.select
                        label="Tipe User"
                        name="user_type"
                        id="user_type"
                        placeholder="Pilih tipe user"
                        :required="true"
                        :error="$errors->first('user_type')"
                    >
                        @php($oldUserType = old('user_type', $user->user_type))
                        <option value="mahasiswa" {{ $oldUserType === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                        <option value="staff" {{ $oldUserType === 'staff' ? 'selected' : '' }}>Staff</option>
                    </x-input.select>

                    <x-input.select
                        label="Role"
                        name="role_id"
                        id="role_id"
                        placeholder="Pilih Role"
                        :required="true"
                        :error="$errors->first('role_id')"
                    >
                        @php($currentRoleId = old('role_id', $user->role_id ?? $user->roles->first()->id ?? null))
                        @foreach($roles ?? [] as $role)
                            <option value="{{ $role->id }}" {{ (string) $currentRoleId === (string) $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </x-input.select>

                    {{-- Baris 4: Password & Konfirmasi Password (opsional) --}}
                    <x-input.text
                        label="Password Baru (Opsional)"
                        name="password"
                        id="password"
                        type="password"
                        placeholder="Minimal 8 karakter"
                        :with-toggle="true"
                        minlength="8"
                        helper="Minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol. Kosongkan jika tidak ingin mengubah password."
                        :error="$errors->first('password')"
                    />

                    <x-input.text
                        label="Konfirmasi Password Baru"
                        name="password_confirmation"
                        id="password_confirmation"
                        type="password"
                        placeholder="Ulangi password baru"
                        :with-toggle="true"
                        minlength="8"
                        helper="Ulangi password baru untuk konfirmasi."
                    />

                    {{-- Baris 5: Status (full width) --}}
                    <div class="form-field form-field--full">
                        <x-input.select
                            label="Status"
                            name="status"
                            id="status"
                            :error="$errors->first('status')"
                        >
                            @php($oldStatus = old('status', $user->status))
                            <option value="active" {{ $oldStatus === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ $oldStatus === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                            <option value="blocked" {{ $oldStatus === 'blocked' ? 'selected' : '' }}>Diblokir</option>
                        </x-input.select>
                    </div>
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Actions --}}
        <div class="form-section__actions form-section__actions--footer">
            <a href="{{ route('user-management.index') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Batal
                </x-button>
            </a>
            <x-button type="submit" variant="primary" icon="heroicon-o-check-circle">
                Update User
            </x-button>
        </div>
    </form>
</div>
@endsection
