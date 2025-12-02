@extends('layouts.app')

@section('title', 'Edit Prasarana')
@section('page-title', 'Edit Prasarana: ' . ($prasarana->name ?? 'Prasarana'))
@section('page-subtitle', 'Perbarui informasi prasarana')

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

    <form action="{{ route('prasarana.update', $prasarana) }}" method="POST" enctype="multipart/form-data" class="form-section">
        @csrf
        @method('PUT')

        <x-form-group
            title="Informasi Prasarana"
            description="Perbarui informasi dasar prasarana."
            icon="heroicon-o-building-office"
        >
            <x-form-section>
                <div class="form-section__grid">
                    <x-input.text
                        label="Nama Prasarana"
                        name="name"
                        id="name"
                        :value="old('name', $prasarana->name)"
                        placeholder="Masukkan nama prasarana"
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
                            <option value="{{ $kategori->id }}" {{ old('kategori_id', $prasarana->kategori_id) == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->name }}
                            </option>
                        @endforeach
                    </x-input.select>

                    <x-input.text
                        label="Lokasi"
                        name="lokasi"
                        id="lokasi"
                        :value="old('lokasi', $prasarana->lokasi)"
                        placeholder="Contoh: Gedung Utama Lt. 2"
                        :error="$errors->first('lokasi')"
                    />

                    <x-input.text
                        label="Kapasitas (Opsional)"
                        name="kapasitas"
                        id="kapasitas"
                        :value="old('kapasitas', $prasarana->kapasitas)"
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
                        <option value="tersedia" {{ old('status', $prasarana->status) === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="rusak" {{ old('status', $prasarana->status) === 'rusak' ? 'selected' : '' }}>Rusak</option>
                        <option value="maintenance" {{ old('status', $prasarana->status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
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
                                >{{ old('description', $prasarana->description) }}</textarea>
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
            description="Kelola foto prasarana untuk memudahkan identifikasi."
            icon="heroicon-o-photo"
        >
            <x-form-section>
                <div class="form-section__grid form-section__grid--single">
                    @php
                        $existingFiles = $prasarana->images->map(function ($image) {
                            return [
                                'url' => asset('storage/' . $image->image_url),
                                'removeName' => 'remove_images[' . $image->id . ']',
                                'label' => basename($image->image_url),
                            ];
                        })->toArray();
                    @endphp

                    <div class="form-field form-field--full">
                        <x-input.file
                            label="Gambar Prasarana"
                            name="images[]"
                            id="images"
                            accept="image/*"
                            :multiple="true"
                            :helper="'Dapat mengunggah lebih dari satu file. Format JPG / PNG, maksimal 5MB per file.'"
                            :existing-files="$existingFiles"
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
                Update Prasarana
            </x-button>
        </div>
    </form>
</div>
@endsection
