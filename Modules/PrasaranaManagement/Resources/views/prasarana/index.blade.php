@extends('layouts.app')

@section('title', 'Manajemen Prasarana')
@section('page-title', 'Manajemen Prasarana')
@section('page-subtitle', 'Kelola data prasarana dan status ketersediaannya')

@section('content')
<div class="page-content page-content--data">
    {{-- Management Tabs: Prasarana / Kategori Prasarana --}}
    <nav aria-label="Manajemen Prasarana" style="display:flex;gap:16px;margin-bottom:16px;border-bottom:1px solid var(--border-subtle);padding-bottom:4px;">
        <a href="{{ route('prasarana.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('prasarana.*') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            Prasarana
        </a>
        <a href="{{ route('kategori-prasarana.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('kategori-prasarana.*') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            Kategori Prasarana
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
    <section class="data-control" aria-label="Kontrol Data Prasarana">
        <form method="GET" action="{{ route('prasarana.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    :value="$filters['search'] ?? ''"
                    placeholder="Cari prasarana (nama / lokasi / deskripsi)"
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
            <a href="{{ route('prasarana.create') }}" class="c-button c-button--primary c-button--with-icon data-control__action" style="width:auto;text-decoration:none;">
                <span class="c-button__icon" aria-hidden="true">
                    <x-heroicon-o-plus />
                </span>
                Tambah Prasarana
            </a>
        </div>
    </section>

    {{-- Filters Panel --}}
    <section class="data-filters" id="prasarana-data-filters" aria-label="Filter Data Prasarana" data-filter-panel hidden>
        <form method="GET" action="{{ route('prasarana.index') }}" class="data-filters__grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;">
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
                    @foreach($kategoriPrasarana as $kategori)
                        <option value="{{ $kategori->id }}" {{ (string)($filters['kategori_id'] ?? '') === (string)$kategori->id ? 'selected' : '' }}>
                            {{ $kategori->name }}
                        </option>
                    @endforeach
                </x-input.select>
            </div>

            <div>
                <x-input.select
                    label="Status"
                    name="status"
                    id="filter_status"
                    placeholder="Semua Status"
                    onchange="this.form.submit()"
                >
                    <option value="tersedia" {{ ($filters['status'] ?? '') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                    <option value="rusak" {{ ($filters['status'] ?? '') === 'rusak' ? 'selected' : '' }}>Rusak</option>
                    <option value="maintenance" {{ ($filters['status'] ?? '') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </x-input.select>
            </div>

            <div>
                <x-input.text
                    label="Lokasi"
                    name="lokasi"
                    id="filter_lokasi"
                    :value="$filters['lokasi'] ?? ''"
                    placeholder="Filter berdasarkan lokasi"
                    onblur="this.form.submit()"
                />
            </div>
        </form>
    </section>

    {{-- Data Table --}}
    <section class="data-table" aria-label="Tabel Prasarana">
        <div class="data-table__container">
            <table class="data-table__table">
                <x-table.head class="data-table__head">
                    <tr class="data-table__row">
                        <x-table.th align="center" class="data-table__cell data-table__cell--number">#</x-table.th>
                        <x-table.th class="data-table__cell">Nama</x-table.th>
                        <x-table.th class="data-table__cell">Kategori</x-table.th>
                        <x-table.th class="data-table__cell">Lokasi</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Kapasitas</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Status</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Gambar</x-table.th>
                        <x-table.th align="center" class="data-table__cell data-table__cell--action">Aksi</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($prasarana as $index => $item)
                        <tr class="data-table__row" data-href="{{ route('prasarana.show', $item) }}" style="cursor:pointer;">
                            <x-table.td align="center" class="data-table__cell data-table__cell--number">{{ $prasarana->firstItem() + $index }}</x-table.td>
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $item->name }}</strong>
                                    <div style="font-size:0.8rem;color:var(--text-muted);">
                                        Dibuat oleh: {{ optional($item->createdBy)->name ?? '-' }}
                                    </div>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                <small style="color: var(--text-muted);">{{ optional($item->kategori)->name ?? '-' }}</small>
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                <small style="color: var(--text-muted);">{{ $item->lokasi ?? '-' }}</small>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <span>{{ $item->kapasitas ?? '-' }}</span>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                @php
                                    $statusVariant = match($item->status) {
                                        'tersedia' => 'success',
                                        'rusak' => 'danger',
                                        'maintenance' => 'warning',
                                        default => 'default',
                                    };
                                @endphp
                                <x-badge :variant="$statusVariant" size="sm" rounded>
                                    {{ ucfirst($item->status) }}
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <x-badge variant="default" size="sm" rounded>
                                    {{ $item->images->count() }} file
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--action">
                                <div style="display:flex;gap:8px;justify-content:flex-end;">
                                    <a href="{{ route('prasarana.edit', $item) }}" style="text-decoration:none;" class="js-row-action">
                                        <x-button type="button" variant="secondary" size="sm">Edit</x-button>
                                    </a>
                                    <x-button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        class="js-row-action"
                                        onclick="document.getElementById('deletePrasaranaModal-{{ $item->id }}').open()"
                                    >
                                        Hapus
                                    </x-button>
                                </div>
                            </x-table.td>
                        </tr>
                        <x-modal id="deletePrasaranaModal-{{ $item->id }}" title="Hapus Prasarana" size="sm">
                            <form
                                id="delete-prasarana-form-{{ $item->id }}"
                                action="{{ route('prasarana.destroy', $item) }}"
                                method="POST"
                                style="display:flex;flex-direction:column;gap:1rem;"
                            >
                                @csrf
                                @method('DELETE')

                                <p style="font-size:0.9rem;color:var(--text-muted);">
                                    Yakin ingin menghapus prasarana <strong>{{ $item->name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                                </p>
                            </form>

                            <x-slot:footer>
                                <x-button type="button" variant="secondary" data-modal-close>
                                    Batal
                                </x-button>
                                <x-button
                                    type="submit"
                                    variant="danger"
                                    form="delete-prasarana-form-{{ $item->id }}"
                                >
                                    Hapus
                                </x-button>
                            </x-slot:footer>
                        </x-modal>
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="8" class="data-table__cell" align="center" style="padding:40px;color:var(--text-muted);">
                                Belum ada data prasarana
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Pagination --}}
    <div class="data-table__pagination">
        {{ $prasarana->withQueryString()->links() }}
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
