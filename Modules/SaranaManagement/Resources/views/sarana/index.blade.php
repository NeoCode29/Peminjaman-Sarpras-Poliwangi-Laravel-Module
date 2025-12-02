@extends('layouts.app')

@section('title', 'Manajemen Sarana')
@section('page-title', 'Manajemen Sarana')
@section('page-subtitle', 'Kelola data sarana dan status ketersediaannya')

@section('content')
<div class="page-content page-content--data">
    {{-- Management Tabs: Sarana / Kategori Sarana --}}
    <nav aria-label="Manajemen Sarana" style="display:flex;gap:16px;margin-bottom:16px;border-bottom:1px solid var(--border-subtle);padding-bottom:4px;">
        <a href="{{ route('sarana.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('sarana.*') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            Sarana
        </a>
        <a href="{{ route('kategori-sarana.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('kategori-sarana.*') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            Kategori Sarana
        </a>
    </nav>

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

    {{-- Data Control --}}
    <section class="data-control" aria-label="Kontrol Data Sarana">
        <form method="GET" action="{{ route('sarana.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    :value="$filters['search'] ?? ''"
                    placeholder="Cari sarana (kode / nama / merk)"
                    icon="heroicon-o-magnifying-glass"
                    autocomplete="off"
                />
            </div>

            <button class="data-control__filter-toggle" type="button" aria-expanded="false" data-filter-toggle
                    style="background: var(--surface-card); border: 1px solid var(--border-default); cursor: pointer;display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px;">
                <x-heroicon-o-funnel style="width: 18px; height: 18px;" />
            </button>
        </form>

        <div class="data-control__actions">
            <a href="{{ route('sarana.create') }}" class="c-button c-button--primary c-button--with-icon data-control__action" style="width:auto;text-decoration:none;">
                <span class="c-button__icon" aria-hidden="true">
                    <x-heroicon-o-plus />
                </span>
                Tambah Sarana
            </a>
        </div>
    </section>

    {{-- Filters Panel --}}
    <section class="data-filters" id="sarana-data-filters" aria-label="Filter Data Sarana" data-filter-panel hidden>
        <form method="GET" action="{{ route('sarana.index') }}" class="data-filters__grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;">
            {{-- Pertahankan pencarian saat mengubah filter --}}
            <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">

            <div>
                <x-input.select
                    label="Kategori"
                    name="kategori_id"
                    id="filter_kategori_id"
                    placeholder="Semua Kategori"
                    onchange="this.form.submit()"
                >
                    @foreach($kategoris as $kategori)
                        <option value="{{ $kategori->id }}" {{ (string)($filters['kategori_id'] ?? '') === (string)$kategori->id ? 'selected' : '' }}>
                            {{ $kategori->nama }}
                        </option>
                    @endforeach
                </x-input.select>
            </div>

            <div>
                <x-input.select
                    label="Kondisi"
                    name="kondisi"
                    id="filter_kondisi"
                    placeholder="Semua Kondisi"
                    onchange="this.form.submit()"
                >
                    <option value="baik" {{ ($filters['kondisi'] ?? '') === 'baik' ? 'selected' : '' }}>Baik</option>
                    <option value="rusak_ringan" {{ ($filters['kondisi'] ?? '') === 'rusak_ringan' ? 'selected' : '' }}>Rusak Ringan</option>
                    <option value="rusak_berat" {{ ($filters['kondisi'] ?? '') === 'rusak_berat' ? 'selected' : '' }}>Rusak Berat</option>
                    <option value="dalam_perbaikan" {{ ($filters['kondisi'] ?? '') === 'dalam_perbaikan' ? 'selected' : '' }}>Dalam Perbaikan</option>
                </x-input.select>
            </div>

            <div>
                <x-input.select
                    label="Status Ketersediaan"
                    name="status_ketersediaan"
                    id="filter_status_ketersediaan"
                    placeholder="Semua Status"
                    onchange="this.form.submit()"
                >
                    <option value="tersedia" {{ ($filters['status_ketersediaan'] ?? '') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                    <option value="dipinjam" {{ ($filters['status_ketersediaan'] ?? '') === 'dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                    <option value="dalam_perbaikan" {{ ($filters['status_ketersediaan'] ?? '') === 'dalam_perbaikan' ? 'selected' : '' }}>Dalam Perbaikan</option>
                    <option value="tidak_tersedia" {{ ($filters['status_ketersediaan'] ?? '') === 'tidak_tersedia' ? 'selected' : '' }}>Tidak Tersedia</option>
                </x-input.select>
            </div>
        </form>
    </section>

    {{-- Data Table --}}
    <section class="data-table" aria-label="Tabel Data Sarana">
        <div class="data-table__container">
            <table class="data-table__table">
                <x-table.head class="data-table__head">
                    <tr class="data-table__row">
                        <x-table.th class="data-table__cell">Kode</x-table.th>
                        <x-table.th class="data-table__cell">Nama</x-table.th>
                        <x-table.th class="data-table__cell">Kategori</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Kondisi</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Status</x-table.th>
                        <x-table.th align="center" class="data-table__cell data-table__cell--number">Jumlah</x-table.th>
                        <x-table.th align="center" class="data-table__cell data-table__cell--action">Aksi</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($saranas as $sarana)
                        <tr class="data-table__row" data-href="{{ route('sarana.show', $sarana) }}" style="cursor:pointer;">
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $sarana->kode_sarana }}</strong>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $sarana->nama }}</strong>
                                    <small style="color: var(--text-muted);">{{ $sarana->merk ?? '-' }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">{{ $sarana->kategori->nama ?? '-' }}</x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <x-badge variant="default" size="sm">
                                    {{ ucfirst(str_replace('_', ' ', $sarana->kondisi)) }}
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                @php
                                    $statusVariant = $sarana->status_ketersediaan === 'tersedia' ? 'success'
                                        : ($sarana->status_ketersediaan === 'dipinjam' ? 'warning'
                                        : ($sarana->status_ketersediaan === 'dalam_perbaikan' ? 'primary' : 'default'));
                                @endphp
                                <x-badge :variant="$statusVariant" size="sm">
                                    {{ ucfirst(str_replace('_', ' ', $sarana->status_ketersediaan)) }}
                                </x-badge>
                            </x-table.td>
                            <x-table.td align="center" class="data-table__cell data-table__cell--number">
                                @if($sarana->type === 'serialized')
                                    {{ $sarana->jumlah_tersedia }} / {{ $sarana->jumlah_total }}
                                @else
                                    {{ $sarana->jumlah_total }}
                                @endif
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--action">
                                <div style="display:flex;gap:8px;justify-content:flex-end;">
                                    <a href="{{ route('sarana.edit', $sarana) }}" style="text-decoration:none;" class="js-row-action">
                                        <x-button type="button" variant="secondary" size="sm">
                                            Edit
                                        </x-button>
                                    </a>

                                    <x-button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        class="js-row-action"
                                        onclick="document.getElementById('deleteSaranaModal-{{ $sarana->id }}').open()"
                                    >
                                        Hapus
                                    </x-button>
                                </div>
                            </x-table.td>
                        </tr>
                        <x-modal id="deleteSaranaModal-{{ $sarana->id }}" title="Hapus Sarana" size="sm">
                            <form
                                id="delete-sarana-form-{{ $sarana->id }}"
                                action="{{ route('sarana.destroy', $sarana) }}"
                                method="POST"
                                style="display:flex;flex-direction:column;gap:1rem;"
                            >
                                @csrf
                                @method('DELETE')

                                <p style="font-size:0.9rem;color:var(--text-muted);">
                                    Yakin ingin menghapus sarana <strong>{{ $sarana->nama }}</strong>? Tindakan ini tidak dapat dibatalkan.
                                </p>
                            </form>

                            <x-slot:footer>
                                <x-button type="button" variant="secondary" data-modal-close>
                                    Batal
                                </x-button>
                                <x-button
                                    type="submit"
                                    variant="danger"
                                    form="delete-sarana-form-{{ $sarana->id }}"
                                >
                                    Hapus
                                </x-button>
                            </x-slot:footer>
                        </x-modal>
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="7" class="data-table__cell" align="center" style="padding:40px;color:var(--text-muted);">
                                Belum ada data sarana
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Pagination --}}
    <div class="data-table__pagination">
        {{ $saranas->withQueryString()->links() }}
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var rows = document.querySelectorAll('.data-table__table .data-table__row[data-href]');

        rows.forEach(function (row) {
            row.addEventListener('click', function (event) {
                if (event.target.closest('.js-row-action')) {
                    return;
                }

                var href = row.getAttribute('data-href');
                if (href) {
                    window.location.href = href;
                }
            });
        });
    });
    </script>
</div>
@endsection

