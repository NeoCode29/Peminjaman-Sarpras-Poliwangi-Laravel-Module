@extends('layouts.app')

@section('title', 'Tambah Kategori Sarana')
@section('page-title', 'Tambah Kategori Sarana Baru')
@section('page-subtitle', 'Buat kategori untuk mengelompokkan sarana')

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

    <form action="{{ route('kategori-sarana.store') }}" method="POST" class="form-section">
        @csrf

        <x-form-group
            title="Informasi Kategori Sarana"
            description="Lengkapi informasi dasar kategori sarana."
            icon="heroicon-o-tag"
        >
            <x-form-section>
                <div class="form-section__grid form-section__grid--single">
                    <x-input.text
                        label="Nama Kategori"
                        name="nama"
                        id="nama"
                        :value="old('nama')"
                        placeholder="Contoh: Elektronik, Olahraga, Alat Musik"
                        :required="true"
                        :error="$errors->first('nama')"
                    />

                    <div class="form-field form-field--full">
                        <div class="c-input">
                            <label for="deskripsi" class="c-input__label">Deskripsi</label>
                            <div class="c-input__control">
                                <textarea
                                    id="deskripsi"
                                    name="deskripsi"
                                    rows="3"
                                    class="c-input__element"
                                    placeholder="Jelaskan jenis sarana yang termasuk dalam kategori ini (opsional)"
                                >{{ old('deskripsi') }}</textarea>
                            </div>
                            @error('deskripsi')
                                <p class="c-input__helper is-invalid">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Actions --}}
        <div class="form-section__actions form-section__actions--footer">
            <a href="{{ route('kategori-sarana.index') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Batal
                </x-button>
            </a>
            <x-button type="submit" variant="primary" icon="heroicon-o-check-circle">
                Simpan Kategori
            </x-button>
        </div>
    </form>
</div>
@endsection

