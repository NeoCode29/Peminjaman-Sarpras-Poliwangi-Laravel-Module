@extends('layouts.app')

@section('title', 'Tambah Role')
@section('page-title', 'Tambah Role Baru')
@section('page-subtitle', 'Buat role baru dengan permission yang sesuai')

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

    <form action="{{ route('role-management.store') }}" method="POST" class="form-section">
        @csrf

        <x-form-group
            title="Informasi Role"
            description="Lengkapi informasi dasar untuk role baru dan atur permission yang terkait."
            icon="heroicon-o-key"
        >
            <x-form-section>
                <div class="form-section__grid form-section__grid--single">
                    {{-- Nama Role --}}
                    <x-input.text
                        label="Nama Role"
                        name="name"
                        id="name"
                        :value="old('name')"
                        placeholder="Contoh: Admin Sarpras, Peminjam Staff"
                        :required="true"
                        :error="$errors->first('name')"
                    />

                    {{-- Deskripsi --}}
                    <div class="form-field form-field--full">
                        <div class="c-input">
                            <label for="description" class="c-input__label">Deskripsi</label>
                            <div class="c-input__control">
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="3"
                                    class="c-input__element"
                                    placeholder="Jelaskan fungsi dan tanggung jawab role ini"
                                >{{ old('description') }}</textarea>
                            </div>
                            @error('description')
                                <p class="c-input__helper is-invalid">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Permissions --}}
                <div class="form-section__fieldset" style="margin-top: 1.5rem;">
                    <h3 style="margin: 0 0 8px 0; font-size: 1rem; font-weight: 700; color: var(--text-main);">
                        Pilih Permissions <span style="color: var(--button-danger-bg);">*</span>
                    </h3>
                    <p style="margin: 0 0 16px 0; font-size: 0.85rem; color: var(--text-muted);">
                        Centang permission yang ingin diberikan kepada role ini.
                    </p>

                    <div class="permission-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px;">
                        @foreach($permissions ?? [] as $permission)
                            <label class="permission-card" style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 16px; border: 1px solid var(--border-default); border-radius: 10px; cursor: pointer; transition: all 0.2s ease;">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->id }}"
                                    {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}
                                    style="width: 18px; height: 18px; cursor: pointer;"
                                >
                                <div style="flex: 1;">
                                    <div class="permission-card__name" style="font-weight: 600; color: var(--text-main); font-size: 0.9rem;">
                                        {{ $permission->name }}
                                    </div>
                                    @if($permission->description)
                                        <div class="permission-card__description" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                            {{ $permission->description }}
                                        </div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('permissions')
                        <small style="color: var(--button-danger-bg); font-size: 0.85rem; margin-top: 8px; display: block;">{{ $message }}</small>
                    @enderror
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Actions --}}
        <div class="form-section__actions form-section__actions--footer">
            <a href="{{ route('role-management.index') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Batal
                </x-button>
            </a>
            <x-button type="submit" variant="primary" icon="heroicon-o-check-circle">
                Simpan Role
            </x-button>
        </div>
    </form>
</div>
@endsection
