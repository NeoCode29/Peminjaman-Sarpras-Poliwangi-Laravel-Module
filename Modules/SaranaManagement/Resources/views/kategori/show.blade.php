@extends('layouts.app')

@section('title', 'Detail Kategori Sarana')
@section('page-title', 'Detail Kategori Sarana')
@section('page-subtitle', 'Informasi lengkap kategori dan ringkasan sarana yang terkait')

@section('content')
<div class="page-content">
    <x-detail-section
        :title="$kategoriSarana->nama"
        :description="'Jumlah sarana: ' . ($kategoriSarana->saranas_count ?? $kategoriSarana->saranas()->count())"
    >
        <x-detail-list :columns="2" variant="bordered">
            <x-detail-item label="Nama Kategori">
                {{ $kategoriSarana->nama }}
            </x-detail-item>

            <x-detail-item label="Jumlah Sarana">
                <x-badge variant="default" size="sm" rounded>
                    {{ $kategoriSarana->saranas_count ?? $kategoriSarana->saranas()->count() }} sarana
                </x-badge>
            </x-detail-item>

            <x-detail-item label="Deskripsi" :full="true">
                {{ $kategoriSarana->deskripsi ?: 'Belum ada deskripsi.' }}
            </x-detail-item>
        </x-detail-list>

        <div style="margin-top: 1.5rem; display:flex; flex-wrap:wrap; gap:0.75rem;">
            <a href="{{ route('kategori-sarana.index') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Kembali ke Daftar
                </x-button>
            </a>
            <a href="{{ route('kategori-sarana.edit', $kategoriSarana) }}" style="text-decoration: none;">
                <x-button type="button" variant="primary">
                    Edit Kategori
                </x-button>
            </a>
        </div>
    </x-detail-section>
</div>
@endsection

