@extends('layouts.app')

@section('title', 'Peminjaman')
@section('page-title', 'Daftar Peminjaman')
@section('page-subtitle', 'Kelola pengajuan peminjaman prasarana dan sarana')

@section('content')
<div class="page-content page-content--data">
    {{-- Toast Notifications --}}
    @if(session('success'))
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="success" title="Berhasil" :duration="5000">
                {{ session('success') }}
            </x-toast>
        </div>
    @endif

    @if(session('error'))
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="danger" title="Gagal" :duration="5000">
                {{ session('error') }}
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
    <section class="data-control" aria-label="Kontrol Data Peminjaman">
        <form method="GET" action="{{ route('peminjaman.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    :value="$filters['search'] ?? ''"
                    placeholder="Cari peminjaman (nama acara / peminjam / lokasi)"
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
            @can('create', \Modules\PeminjamanManagement\Entities\Peminjaman::class)
                <a href="{{ route('peminjaman.create') }}" class="c-button c-button--primary c-button--with-icon data-control__action" style="width:auto;text-decoration:none;">
                    <span class="c-button__icon" aria-hidden="true">
                        <x-heroicon-o-plus />
                    </span>
                    Buat Peminjaman
                </a>
            @endcan
        </div>
    </section>

    {{-- Filters Panel --}}
    <section class="data-filters" id="peminjaman-data-filters" aria-label="Filter Data Peminjaman" data-filter-panel hidden>
        <form method="GET" action="{{ route('peminjaman.index') }}" class="data-filters__grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;">
            {{-- Pertahankan pencarian saat mengubah filter --}}
            <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">

            <div>
                <x-input.select
                    label="Status"
                    name="status"
                    id="filter_status"
                    placeholder="Semua Status"
                    onchange="this.form.submit()"
                >
                    <option value="">Semua Status</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                    <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    <option value="picked_up" {{ ($filters['status'] ?? '') === 'picked_up' ? 'selected' : '' }}>Sedang Dipinjam</option>
                    <option value="returned" {{ ($filters['status'] ?? '') === 'returned' ? 'selected' : '' }}>Dikembalikan</option>
                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    <option value="conflicted" {{ ($filters['status'] ?? '') === 'conflicted' ? 'selected' : '' }}>Termasuk Konflik</option>
                </x-input.select>
            </div>

            <div>
                <x-input.text
                    label="Tanggal Mulai"
                    name="start_date"
                    id="filter_start_date"
                    type="date"
                    :value="$filters['start_date'] ?? ''"
                    onchange="this.form.submit()"
                />
            </div>

            <div>
                <x-input.text
                    label="Tanggal Selesai"
                    name="end_date"
                    id="filter_end_date"
                    type="date"
                    :value="$filters['end_date'] ?? ''"
                    onchange="this.form.submit()"
                />
            </div>
        </form>
    </section>

    {{-- Data Table --}}
    <section class="data-table" aria-label="Tabel Data Peminjaman">
        <div class="data-table__container">
            <table class="data-table__table">
                <x-table.head class="data-table__head">
                    <tr class="data-table__row">
                        <x-table.th class="data-table__cell">Nama Acara</x-table.th>
                        <x-table.th class="data-table__cell">Peminjam</x-table.th>
                        <x-table.th class="data-table__cell">Lokasi</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Jadwal</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Status</x-table.th>
                        <x-table.th align="center" class="data-table__cell data-table__cell--action">Aksi</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($peminjaman as $item)
                        <tr class="data-table__row" data-href="{{ route('peminjaman.show', $item) }}" style="cursor:pointer;">
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $item->event_name }}</strong>
                                    <small style="color: var(--text-muted);">
                                        {{ $item->ukm->nama ?? $item->user->name ?? '-' }}
                                    </small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                {{ $item->user->name ?? '-' }}
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                @if($item->prasarana)
                                    {{ $item->prasarana->name }}
                                @elseif($item->lokasi_custom)
                                    {{ $item->lokasi_custom }}
                                @else
                                    -
                                @endif
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <div class="data-table__data">
                                    <span>{{ $item->start_date->format('d/m/Y') }}</span>
                                    <small style="color: var(--text-muted);">s/d {{ $item->end_date->format('d/m/Y') }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                @php
                                    $statusVariant = match($item->status) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'picked_up' => 'primary',
                                        'returned' => 'default',
                                        'rejected' => 'danger',
                                        'cancelled' => 'default',
                                        default => 'default'
                                    };
                                @endphp
                                <div style="display:flex;flex-wrap:wrap;gap:4px;align-items:center;">
                                    <x-badge :variant="$statusVariant" size="sm">
                                        {{ $item->status_label }}
                                    </x-badge>

                                    @if(!empty($item->konflik))
                                        <x-badge variant="danger" size="sm">
                                            Konflik
                                        </x-badge>
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--action">
                                <div style="display:flex;gap:8px;justify-content:flex-end;">
                                    @can('update', $item)
                                        <a href="{{ route('peminjaman.edit', $item) }}" style="text-decoration:none;" class="js-row-action">
                                            <x-button type="button" variant="secondary" size="sm">
                                                Edit
                                            </x-button>
                                        </a>
                                    @endcan

                                    @can('cancel', $item)
                                        @if($item->isPending() || $item->isApproved())
                                            <x-button
                                                type="button"
                                                variant="danger"
                                                size="sm"
                                                class="js-row-action"
                                                onclick="document.getElementById('cancelPeminjamanModal-{{ $item->id }}').open()"
                                            >
                                                Batalkan
                                            </x-button>
                                        @endif
                                    @endcan
                                </div>
                            </x-table.td>
                        </tr>

                        {{-- Modal Cancel --}}
                        @can('cancel', $item)
                            <x-modal id="cancelPeminjamanModal-{{ $item->id }}" title="Batalkan Peminjaman" size="sm">
                                <form
                                    id="cancel-peminjaman-form-{{ $item->id }}"
                                    action="{{ route('peminjaman.cancel', $item) }}"
                                    method="POST"
                                    style="display:flex;flex-direction:column;gap:1rem;"
                                >
                                    @csrf

                                    <p style="font-size:0.9rem;color:var(--text-muted);">
                                        Yakin ingin membatalkan peminjaman <strong>{{ $item->event_name }}</strong>?
                                    </p>

                                    <x-input.text
                                        label="Alasan Pembatalan (Opsional)"
                                        name="reason"
                                        id="cancel_reason_{{ $item->id }}"
                                        :value="old('reason')"
                                    />
                                </form>

                                <x-slot:footer>
                                    <x-button type="button" variant="secondary" data-modal-close>
                                        Tidak
                                    </x-button>
                                    <x-button
                                        type="submit"
                                        variant="danger"
                                        form="cancel-peminjaman-form-{{ $item->id }}"
                                    >
                                        Ya, Batalkan
                                    </x-button>
                                </x-slot:footer>
                            </x-modal>
                        @endcan
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="6" class="data-table__cell" align="center" style="padding:40px;color:var(--text-muted);">
                                Belum ada data peminjaman
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Pagination --}}
    <div class="data-table__pagination">
        @if(isset($peminjaman) && method_exists($peminjaman, 'links'))
            {{ $peminjaman->withQueryString()->links('components.pagination') }}
        @endif
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

        var filterToggle = document.querySelector('[data-filter-toggle]');
        var filterPanel = document.querySelector('[data-filter-panel]');
        if (filterToggle && filterPanel) {
            filterToggle.addEventListener('click', function () {
                var isHidden = filterPanel.hasAttribute('hidden');
                if (isHidden) {
                    filterPanel.removeAttribute('hidden');
                    filterToggle.setAttribute('aria-expanded', 'true');
                } else {
                    filterPanel.setAttribute('hidden', 'hidden');
                    filterToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
    </script>
</div>
@endsection
