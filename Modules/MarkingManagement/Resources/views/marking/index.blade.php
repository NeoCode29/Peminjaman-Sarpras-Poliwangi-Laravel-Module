@extends('layouts.app')

@section('title', 'Marking')
@section('page-title', 'Daftar Marking')
@section('page-subtitle', 'Kelola reservasi sementara prasarana dan sarana')

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

    @if(session('info'))
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="info" title="Info" :duration="5000">
                {{ session('info') }}
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
    <section class="data-control" aria-label="Kontrol Data Marking">
        <form method="GET" action="{{ route('marking.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    :value="$filters['search'] ?? ''"
                    placeholder="Cari marking (nama acara / lokasi)"
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
            @can('create', \Modules\MarkingManagement\Entities\Marking::class)
                <a href="{{ route('marking.create') }}" class="c-button c-button--primary c-button--with-icon data-control__action" style="width:auto;text-decoration:none;">
                    <span class="c-button__icon" aria-hidden="true">
                        <x-heroicon-o-plus />
                    </span>
                    Buat Marking
                </a>
            @endcan
        </div>
    </section>

    {{-- Filters Panel --}}
    <section class="data-filters" id="marking-data-filters" aria-label="Filter Data Marking" data-filter-panel hidden>
        <form method="GET" action="{{ route('marking.index') }}" class="data-filters__grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;">
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
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
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
    <section class="data-table" aria-label="Tabel Data Marking">
        <div class="data-table__container">
            <table class="data-table__table">
                <x-table.head class="data-table__head">
                    <tr class="data-table__row">
                        <x-table.th class="data-table__cell">Nama Acara</x-table.th>
                        <x-table.th class="data-table__cell">Lokasi</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Waktu</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Kadaluarsa</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Status</x-table.th>
                        <x-table.th align="center" class="data-table__cell data-table__cell--action">Aksi</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($markings as $marking)
                        <tr class="data-table__row" data-href="{{ route('marking.show', $marking) }}" style="cursor:pointer;">
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $marking->event_name }}</strong>
                                    <small style="color: var(--text-muted);">{{ $marking->user->name ?? '-' }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                {{ $marking->getLocation() }}
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <div class="data-table__data">
                                    <span>{{ $marking->start_datetime->format('d/m/Y H:i') }}</span>
                                    <small style="color: var(--text-muted);">s/d {{ $marking->end_datetime->format('d/m/Y H:i') }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                @if($marking->isExpired())
                                    <span style="color: var(--danger);">Sudah kadaluarsa</span>
                                @elseif($marking->getHoursUntilExpiration() <= 24)
                                    <span style="color: var(--warning);">{{ $marking->expires_at->format('d/m/Y H:i') }}</span>
                                @else
                                    {{ $marking->expires_at->format('d/m/Y H:i') }}
                                @endif
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                @php
                                    $statusVariant = match($marking->status) {
                                        'active' => $marking->isExpired() ? 'warning' : 'success',
                                        'expired' => 'danger',
                                        'converted' => 'primary',
                                        'cancelled' => 'default',
                                        default => 'default'
                                    };
                                @endphp
                                <x-badge :variant="$statusVariant" size="sm">
                                    {{ $statuses[$marking->status] ?? ucfirst($marking->status) }}
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--action">
                                <div style="display:flex;gap:8px;justify-content:flex-end;">
                                    @can('update', $marking)
                                        @if($marking->isActive() && !$marking->isExpired())
                                            <a href="{{ route('marking.edit', $marking) }}" style="text-decoration:none;" class="js-row-action">
                                                <x-button type="button" variant="secondary" size="sm">
                                                    Edit
                                                </x-button>
                                            </a>
                                        @endif
                                    @endcan

                                    @can('delete', $marking)
                                        @if($marking->isActive())
                                            <x-button
                                                type="button"
                                                variant="danger"
                                                size="sm"
                                                class="js-row-action"
                                                onclick="document.getElementById('cancelMarkingModal-{{ $marking->id }}').open()"
                                            >
                                                Batalkan
                                            </x-button>
                                        @endif
                                    @endcan
                                </div>
                            </x-table.td>
                        </tr>
                        @can('delete', $marking)
                            <x-modal id="cancelMarkingModal-{{ $marking->id }}" title="Batalkan Marking" size="sm">
                                <form
                                    id="cancel-marking-form-{{ $marking->id }}"
                                    action="{{ route('marking.destroy', $marking) }}"
                                    method="POST"
                                    style="display:flex;flex-direction:column;gap:1rem;"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <p style="font-size:0.9rem;color:var(--text-muted);">
                                        Yakin ingin membatalkan marking <strong>{{ $marking->event_name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                                    </p>
                                </form>

                                <x-slot:footer>
                                    <x-button type="button" variant="secondary" data-modal-close>
                                        Tidak
                                    </x-button>
                                    <x-button
                                        type="submit"
                                        variant="danger"
                                        form="cancel-marking-form-{{ $marking->id }}"
                                    >
                                        Ya, Batalkan
                                    </x-button>
                                </x-slot:footer>
                            </x-modal>
                        @endcan
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="6" class="data-table__cell" align="center" style="padding:40px;color:var(--text-muted);">
                                Belum ada data marking
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Pagination --}}
    <div class="data-table__pagination">
        {{ $markings->withQueryString()->links() }}
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
