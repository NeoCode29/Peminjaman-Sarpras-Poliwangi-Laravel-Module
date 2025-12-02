@extends('layouts.app')

@section('title', 'Tambah Prasarana')
@section('page-title', 'Tambah Prasarana Baru')
@section('page-subtitle', 'Lengkapi informasi prasarana yang akan dikelola')

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

    <form action="{{ route('prasarana.store') }}" method="POST" enctype="multipart/form-data" class="form-section">
        @csrf

        <x-form-group
            title="Informasi Prasarana"
            description="Lengkapi data dasar untuk prasarana baru."
            icon="heroicon-o-building-office"
        >
            <x-form-section>
                <div class="form-section__grid">
                    <x-input.text
                        label="Nama Prasarana"
                        name="name"
                        id="name"
                        :value="old('name')"
                        placeholder="Masukkan nama prasarana (misal: Aula Serbaguna)"
                        :required="true"
                        :error="$errors->first('name')"
                    />

                    <x-input.select
                        label="Kategori"
                        name="kategori_id"
                        id="kategori_id"
                        placeholder="Pilih kategori"
                        :required="true"
                        :error="$errors->first('kategori_id')"
                    >
                        @foreach($kategoriPrasarana as $kategori)
                            <option value="{{ $kategori->id }}" {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->name }}
                            </option>
                        @endforeach
                    </x-input.select>

                    <x-input.text
                        label="Lokasi"
                        name="lokasi"
                        id="lokasi"
                        :value="old('lokasi')"
                        placeholder="Contoh: Gedung Utama Lt. 2"
                        :error="$errors->first('lokasi')"
                    />

                    <x-input.text
                        label="Kapasitas (Opsional)"
                        name="kapasitas"
                        id="kapasitas"
                        :value="old('kapasitas')"
                        placeholder="Contoh: 100"
                        :error="$errors->first('kapasitas')"
                    />

                    <x-input.select
                        label="Status"
                        name="status"
                        id="status"
                        placeholder="Pilih status"
                        :required="true"
                        :error="$errors->first('status')"
                    >
                        <option value="tersedia" {{ old('status') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="rusak" {{ old('status') === 'rusak' ? 'selected' : '' }}>Rusak</option>
                        <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    </x-input.select>

                    <div class="form-field form-field--full">
                        <div class="c-input">
                            <label for="description" class="c-input__label">Deskripsi</label>
                            <div class="c-input__control">
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="3"
                                    class="c-input__element"
                                    placeholder="Tulis deskripsi penggunaan, fasilitas, atau catatan penting lain"
                                >{{ old('description') }}</textarea>
                            </div>
                            @error('description')
                                <p class="c-input__helper is-invalid">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </x-form-section>
        </x-form-group>

        <x-form-group
            title="Gambar Prasarana"
            description="Tambahkan foto prasarana untuk memudahkan identifikasi."
            icon="heroicon-o-photo"
        >
            <x-form-section>
                <div class="form-section__grid form-section__grid--single">
                    <div class="form-field form-field--full">
                        <x-input.file
                            label="Gambar Prasarana"
                            name="images[]"
                            id="images"
                            accept="image/*"
                            :multiple="true"
                            :helper="'Dapat mengunggah lebih dari satu file. Format JPG / PNG, maksimal 5MB per file.'"
                        />
                        @error('images')
                            <p class="c-input__helper is-invalid">{{ $message }}</p>
                        @enderror
                        @error('images.*')
                            <p class="c-input__helper is-invalid">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Actions --}}
        <div class="form-section__actions form-section__actions--footer">
            <a href="{{ route('prasarana.index') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Batal
                </x-button>
            </a>
            <x-button type="submit" variant="primary" icon="heroicon-o-check-circle">
                Simpan Prasarana
            </x-button>
        </div>
    </form>
</div>
@endsection
