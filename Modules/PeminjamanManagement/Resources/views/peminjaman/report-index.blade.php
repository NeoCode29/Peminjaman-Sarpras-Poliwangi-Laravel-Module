@extends('layouts.app')

@section('title', 'Laporan Peminjaman')
@section('page-title', 'Laporan Peminjaman')
@section('page-subtitle', 'Ringkasan dan daftar peminjaman untuk keperluan laporan')

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

    {{-- Summary --}}
    @if(!empty($summary))
    <section style="margin-bottom: 1rem; display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem;">
        <x-stat-card 
            label="Total Peminjaman" 
            :value="$summary['total_records'] ?? 0"
            icon="heroicon-o-clipboard-document-list"
            variant="primary"
        />
        <x-stat-card 
            label="Total Peserta" 
            :value="$summary['total_participants'] ?? 0"
            icon="heroicon-o-user-group"
            variant="secondary"
        />
        <x-stat-card 
            label="Total Item Disetujui" 
            :value="$summary['total_items_approved'] ?? 0"
            icon="heroicon-o-archive-box"
            variant="success"
        />
    </section>
    @endif

    {{-- Data Control --}}
    <section class="data-control" aria-label="Kontrol Data Laporan Peminjaman">
        <form method="GET" action="{{ route('peminjaman.reports.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    :value="$filters['search'] ?? ''"
                    placeholder="Cari (nama acara / peminjam / lokasi / UKM)"
                    icon="heroicon-o-magnifying-glass"
                    autocomplete="off"
                />
            </div>

            <div style="width: 160px;">
                <x-input.select
                    name="status"
                    id="filter_status"
                    placeholder="Semua Status"
                    onchange="this.form.submit()"
                >
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ (($filters['status'] ?? null) === (string) $value) ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-input.select>
            </div>

            <div style="width: 130px;">
                <x-input.text
                    name="start_date"
                    id="filter_start_date"
                    type="date"
                    :value="$filters['start_date'] ?? ''"
                    onchange="this.form.submit()"
                />
            </div>

            <div style="width: 130px;">
                <x-input.text
                    name="end_date"
                    id="filter_end_date"
                    type="date"
                    :value="$filters['end_date'] ?? ''"
                    onchange="this.form.submit()"
                />
            </div>

            @php
                $exportFilters = $filters ?? [];
                $exportUrl = route('peminjaman.export.pdf', $exportFilters);
            @endphp

            <div class="data-control__actions">
                <a href="{{ $exportUrl }}" class="c-button c-button--secondary c-button--with-icon data-control__action" style="width:auto;text-decoration:none;">
                    <span class="c-button__icon" aria-hidden="true">
                        <x-heroicon-o-arrow-down-tray />
                    </span>
                    Export PDF
                </a>
            </div>
        </form>
    </section>

    {{-- Data Table --}}
    <section class="data-table" aria-label="Tabel Laporan Peminjaman">
        <div class="data-table__container">
            <table class="data-table__table">
                <x-table.head class="data-table__head">
                    <tr class="data-table__row">
                        <x-table.th class="data-table__cell">Nama Acara</x-table.th>
                        <x-table.th class="data-table__cell">Peminjam</x-table.th>
                        <x-table.th class="data-table__cell">UKM / Unit</x-table.th>
                        <x-table.th class="data-table__cell">Prasarana</x-table.th>
                        <x-table.th class="data-table__cell">Sarana</x-table.th>
                        <x-table.th class="data-table__cell">Lokasi</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Jadwal</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Peserta</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Status</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($paginator as $item)
                        <tr class="data-table__row">
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $item->event_name }}</strong>
                                    <small style="color: var(--text-muted);">#{{ $item->id }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                {{ $item->user->name ?? '-' }}
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                {{ $item->ukm->nama ?? '-' }}
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                {{ $item->prasarana->name ?? '-' }}
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                @php
                                    $saranaNames = $item->items
                                        ? $item->items->pluck('sarana.name')->filter()->unique()->implode(', ')
                                        : '';
                                @endphp
                                {{ $saranaNames !== '' ? $saranaNames : '-' }}
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
                                    <span>{{ optional($item->start_date)->format('d/m/Y') }}</span>
                                    <small style="color: var(--text-muted);">s/d {{ optional($item->end_date)->format('d/m/Y') }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                {{ $item->jumlah_peserta ?? '-' }}
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                @php
                                    $status = $item->status;
                                    $labelMap = [
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_PENDING => 'Pending',
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_APPROVED => 'Disetujui',
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_REJECTED => 'Ditolak',
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_PICKED_UP => 'Sedang Dipinjam',
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_RETURNED => 'Dikembalikan',
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_CANCELLED => 'Dibatalkan',
                                    ];
                                    $label = $labelMap[$status] ?? ucfirst($status);

                                    $variant = match ($status) {
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_APPROVED => 'success',
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_REJECTED => 'danger',
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_PICKED_UP => 'primary',
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_RETURNED => 'default',
                                        \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_CANCELLED => 'secondary',
                                        default => 'warning',
                                    };
                                @endphp
                                <x-badge :variant="$variant" size="sm">
                                    {{ $label }}
                                </x-badge>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="7" class="data-table__cell" align="center" style="padding:40px;color:var(--text-muted);">
                                Tidak ada data peminjaman pada periode ini.
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Pagination --}}
    <div class="data-table__pagination">
        @if(isset($paginator) && method_exists($paginator, 'links'))
            {{ $paginator->withQueryString()->links('components.pagination') }}
        @endif
    </div>
</div>
@endsection
