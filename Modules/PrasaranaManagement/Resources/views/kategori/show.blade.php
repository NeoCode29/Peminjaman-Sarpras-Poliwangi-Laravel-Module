@extends('layouts.app')

@section('title', 'Detail Kategori Prasarana')
@section('page-title', 'Detail Kategori Prasarana')
@section('page-subtitle', 'Informasi lengkap kategori dan ringkasan prasarana yang terkait')

@section('content')
<div class="page-content">
    <x-detail-section
        :title="$kategoriPrasarana->name"
        :description="'Jumlah prasarana: ' . ($kategoriPrasarana->prasarana_count ?? $kategoriPrasarana->prasarana()->count())"
    >
        <x-detail-list :columns="2" variant="bordered">
            <x-detail-item label="Nama Kategori">
                {{ $kategoriPrasarana->name }}
            </x-detail-item>

            <x-detail-item label="Jumlah Prasarana">
                <x-badge variant="default" size="sm" rounded>
                    {{ $kategoriPrasarana->prasarana_count ?? $kategoriPrasarana->prasarana()->count() }} prasarana
                </x-badge>
            </x-detail-item>

            <x-detail-item label="Deskripsi" :full="true">
                {{ $kategoriPrasarana->description ?: 'Belum ada deskripsi.' }}
            </x-detail-item>
        </x-detail-list>

        <div style="margin-top: 1.5rem; display:flex; flex-wrap:wrap; gap:0.75rem;">
            <a href="{{ route('kategori-prasarana.index') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Kembali ke Daftar
                </x-button>
            </a>
            <a href="{{ route('kategori-prasarana.edit', $kategoriPrasarana) }}" style="text-decoration: none;">
                <x-button type="button" variant="primary">
                    Edit Kategori
                </x-button>
            </a>
        </div>
    </x-detail-section>
</div>
@endsection
