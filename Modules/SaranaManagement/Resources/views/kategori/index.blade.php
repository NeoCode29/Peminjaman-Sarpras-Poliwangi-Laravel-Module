@extends('layouts.app')

@section('title', 'Kategori Sarana')
@section('page-title', 'Kategori Sarana')
@section('page-subtitle', 'Kelola kategori untuk pengelompokan sarana')

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
    <section class="data-control" aria-label="Kontrol Data Kategori Sarana">
        <form method="GET" action="{{ route('kategori-sarana.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    :value="$filters['search'] ?? ''"
                    placeholder="Cari kategori sarana..."
                    icon="heroicon-o-magnifying-glass"
                    autocomplete="off"
                />
            </div>
        </form>

        <div class="data-control__actions">
            <a href="{{ route('kategori-sarana.create') }}" class="c-button c-button--primary c-button--with-icon data-control__action" style="width:auto;text-decoration:none;">
                <span class="c-button__icon" aria-hidden="true">
                    <x-heroicon-o-plus />
                </span>
                Tambah Kategori
            </a>
        </div>
    </section>

    {{-- Data Table --}}
    <section class="data-table" aria-label="Tabel Kategori Sarana">
        <div class="data-table__container">
            <table class="data-table__table">
                <x-table.head class="data-table__head">
                    <tr class="data-table__row">
                        <x-table.th align="center" class="data-table__cell data-table__cell--number">#</x-table.th>
                        <x-table.th class="data-table__cell">Nama Kategori</x-table.th>
                        <x-table.th class="data-table__cell">Deskripsi</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Jumlah Sarana</x-table.th>
                        <x-table.th align="center" class="data-table__cell data-table__cell--action">Aksi</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($kategoris as $index => $kategori)
                        <tr class="data-table__row" data-href="{{ route('kategori-sarana.show', $kategori) }}" style="cursor:pointer;">
                            <x-table.td align="center" class="data-table__cell data-table__cell--number">{{ $index + 1 }}</x-table.td>
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $kategori->nama }}</strong>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                <small style="color: var(--text-muted);">{{ $kategori->deskripsi ?? '-' }}</small>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <x-badge variant="default" size="sm" rounded>
                                    {{ $kategori->saranas_count ?? $kategori->saranas()->count() }} sarana
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--action">
                                <div style="display:flex;gap:8px;justify-content:flex-end;">
                                    <a href="{{ route('kategori-sarana.edit', $kategori) }}" style="text-decoration:none;" class="js-row-action">
                                        <x-button type="button" variant="secondary" size="sm">Edit</x-button>
                                    </a>
                                    <x-button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        class="js-row-action"
                                        onclick="document.getElementById('deleteKategoriModal-{{ $kategori->id }}').open()"
                                    >
                                        Hapus
                                    </x-button>
                                </div>
                            </x-table.td>
                        </tr>
                        <x-modal id="deleteKategoriModal-{{ $kategori->id }}" title="Hapus Kategori" size="sm">
                            <form
                                id="delete-kategori-form-{{ $kategori->id }}"
                                action="{{ route('kategori-sarana.destroy', $kategori) }}"
                                method="POST"
                                style="display:flex;flex-direction:column;gap:1rem;"
                            >
                                @csrf
                                @method('DELETE')

                                <p style="font-size:0.9rem;color:var(--text-muted);">
                                    Yakin ingin menghapus kategori <strong>{{ $kategori->nama }}</strong>? Tindakan ini tidak dapat dibatalkan.
                                </p>
                            </form>

                            <x-slot:footer>
                                <x-button type="button" variant="secondary" data-modal-close>
                                    Batal
                                </x-button>
                                <x-button
                                    type="submit"
                                    variant="danger"
                                    form="delete-kategori-form-{{ $kategori->id }}"
                                >
                                    Hapus
                                </x-button>
                            </x-slot:footer>
                        </x-modal>
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="5" class="data-table__cell" align="center" style="padding:40px;color:var(--text-muted);">
                                Belum ada data kategori sarana
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Pagination --}}
    <div class="data-table__pagination">
        {{ $kategoris->withQueryString()->links() }}
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
