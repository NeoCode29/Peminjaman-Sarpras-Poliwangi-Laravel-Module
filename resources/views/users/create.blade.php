@extends('layouts.app')

@section('title', 'Tambah User')
@section('page-title', 'Tambah User Baru')
@section('page-subtitle', 'Buat akun pengguna baru dalam sistem')

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

    <form action="{{ route('user-management.store') }}" method="POST" class="form-section">
        @csrf

        <x-form-group
            title="Informasi User"
            description="Lengkapi data dasar untuk akun pengguna baru."
            icon="heroicon-o-user-plus"
        >
            <x-form-section>
                <div class="form-section__grid">
                    {{-- Baris 1: Nama & Username --}}
                    <x-input.text
                        label="Nama Lengkap"
                        name="name"
                        id="name"
                        :value="old('name')"
                        placeholder="Masukkan nama lengkap"
                        :required="true"
                        :error="$errors->first('name')"
                    />

                    <x-input.text
                        label="Username"
                        name="username"
                        id="username"
                        :value="old('username')"
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
                            :value="old('email')"
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
                        <option value="mahasiswa" {{ old('user_type') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                        <option value="staff" {{ old('user_type') === 'staff' ? 'selected' : '' }}>Staff</option>
                    </x-input.select>

                    <x-input.select
                        label="Role"
                        name="role_id"
                        id="role_id"
                        placeholder="Pilih Role"
                        :required="true"
                        :error="$errors->first('role_id')"
                    >
                        @foreach($roles ?? [] as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </x-input.select>

                    {{-- Baris 4: Password & Konfirmasi Password --}}
                    <x-input.text
                        label="Password"
                        name="password"
                        id="password"
                        type="password"
                        placeholder="Minimal 8 karakter"
                        :required="true"
                        :with-toggle="true"
                        minlength="8"
                        helper="Minimal 8 karakter dan mengandung huruf besar, huruf kecil, angka, serta simbol."
                        :error="$errors->first('password')"
                    />

                    <x-input.text
                        label="Konfirmasi Password"
                        name="password_confirmation"
                        id="password_confirmation"
                        type="password"
                        placeholder="Ulangi password"
                        :required="true"
                        :with-toggle="true"
                        minlength="8"
                        helper="Ulangi password baru dengan aturan yang sama untuk konfirmasi."
                    />

                    {{-- Baris 5: Status (full width) --}}
                    <div class="form-field form-field--full">
                        <x-input.select
                            label="Status"
                            name="status"
                            id="status"
                            :error="$errors->first('status')"
                        >
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                            <option value="blocked" {{ old('status') === 'blocked' ? 'selected' : '' }}>Diblokir</option>
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
                Simpan User
            </x-button>
        </div>
    </form>
</div>
@endsection
