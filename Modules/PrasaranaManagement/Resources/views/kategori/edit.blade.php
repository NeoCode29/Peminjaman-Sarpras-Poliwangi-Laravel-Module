@extends('layouts.app')

@section('title', 'Edit Kategori Prasarana')
@section('page-title', 'Edit Kategori: ' . ($kategoriPrasarana->name ?? 'Kategori'))
@section('page-subtitle', 'Perbarui informasi kategori prasarana')

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

    <form action="{{ route('kategori-prasarana.update', $kategoriPrasarana) }}" method="POST" class="form-section">
        @csrf
        @method('PUT')

        <x-form-group
            title="Informasi Kategori Prasarana"
            description="Perbarui informasi dasar kategori prasarana."
            icon="heroicon-o-tag"
        >
            <x-form-section>
                <div class="form-section__grid form-section__grid--single">
                    <x-input.text
                        label="Nama Kategori"
                        name="name"
                        id="name"
                        :value="old('name', $kategoriPrasarana->name)"
                        placeholder="Contoh: Aula, Ruang Kelas, Laboratorium"
                        :required="true"
                        :error="$errors->first('name')"
                    />

                    <div class="form-field form-field--full">
                        <div class="c-input">
                            <label for="description" class="c-input__label">Deskripsi</label>
                            <div class="c-input__control">
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="3"
                                    class="c-input__element"
                                    placeholder="Jelaskan jenis prasarana yang termasuk dalam kategori ini (opsional)"
                                >{{ old('description', $kategoriPrasarana->description) }}</textarea>
                            </div>
                            @error('description')
                                <p class="c-input__helper is-invalid">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Actions --}}
        <div class="form-section__actions form-section__actions--footer">
            <a href="{{ route('kategori-prasarana.index') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Batal
                </x-button>
            </a>
            <x-button type="submit" variant="primary" icon="heroicon-o-check-circle">
                Update Kategori
            </x-button>
        </div>
    </form>
</div>
@endsection
