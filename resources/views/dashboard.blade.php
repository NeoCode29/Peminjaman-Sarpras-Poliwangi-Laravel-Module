@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang di sistem manajemen')

@section('content')
<div class="page-content">
    {{-- Stats Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px;">
        {{-- Total Users --}}
        <div style="background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 16px; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 56px; height: 56px; background: var(--color-blue-100); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    👥
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main);">
                        {{ $stats['total_users'] ?? 0 }}
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">
                        Total Users
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Roles --}}
        <div style="background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 16px; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 56px; height: 56px; background: #f3e5f5; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    🔐
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main);">
                        {{ $stats['total_roles'] ?? 0 }}
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">
                        Total Roles
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Permissions --}}
        <div style="background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 16px; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 56px; height: 56px; background: #e8f5e9; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    🔑
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main);">
                        {{ $stats['total_permissions'] ?? 0 }}
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">
                        Total Permissions
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Users --}}
        <div style="background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 16px; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 56px; height: 56px; background: #e8f5e8; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    ✅
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main);">
                        {{ $stats['active_users'] ?? 0 }}
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">
                        Active Users
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions dinamis --}}
    @if(!empty($quickActions))
    <div class="form-section">
        <div class="form-section__header">
            <h2 style="margin: 0; font-size: 1.4rem; font-weight: 700;">Aksi yang Relevan</h2>
            <div class="form-section__divider"></div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            @foreach($quickActions as $action)
                <a href="{{ $action['url'] }}" class="c-button c-button--primary c-button--with-icon" style="width: 100%; justify-content: center; text-decoration: none;">
                    <span class="c-button__icon" aria-hidden="true">
                        <x-dynamic-component :component="$action['icon']" />
                    </span>
                    {{ $action['title'] }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

    @if(!empty($yearlyLoans))
    {{-- Diagram garis peminjaman dalam setahun --}}
    <div class="form-section">
        <div class="form-section__header">
            <h2 style="margin: 0; font-size: 1.4rem; font-weight: 700;">Tren Peminjaman {{ $yearlyLoans['year'] }}</h2>
            <div class="form-section__divider"></div>
        </div>

        <div style="background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 16px; padding: 16px;">
            <div id="yearly-loan-chart"
                 data-labels='@json($yearlyLoans['labels'])'
                 data-values='@json($yearlyLoans['data'])'
                 style="width: 100%; height: 260px;">
                <svg viewBox="0 0 100 40" preserveAspectRatio="none" style="width:100%;height:100%;">
                    <polyline id="yearly-loan-polyline" fill="none" stroke="var(--color-primary)" stroke-width="0.8" points="" />
                    <line x1="0" y1="39" x2="100" y2="39" stroke="var(--border-subtle)" stroke-width="0.3" />
                </svg>
            </div>
            <div style="margin-top: 8px; font-size: 0.85rem; color: var(--text-muted);">
                Total peminjaman: <strong>{{ $yearlyLoans['total'] }}</strong>
                @if($yearlyLoans['peak_month'])
                    &mdash; Puncak di bulan <strong>{{ $yearlyLoans['peak_month'] }}</strong>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if(!empty($topSarana))
    {{-- Top Sarana Paling Dipinjam (30 hari terakhir) --}}
    <div class="form-section">
        <div class="form-section__header">
            <h2 style="margin: 0; font-size: 1.4rem; font-weight: 700;">Top Sarana Paling Dipinjam (30 hari terakhir)</h2>
            <div class="form-section__divider"></div>
        </div>

        <div class="data-table" aria-label="Top Sarana Paling Dipinjam">
            <div class="data-table__container">
                <table class="data-table__table">
                    <x-table.head class="data-table__head">
                        <tr class="data-table__row">
                            <x-table.th class="data-table__cell">Sarana</x-table.th>
                            <x-table.th class="data-table__cell data-table__cell--meta">Total Dipinjam</x-table.th>
                            <x-table.th class="data-table__cell data-table__cell--meta">Perkiraan Jam Pemakaian</x-table.th>
                        </tr>
                    </x-table.head>
                    <x-table.body class="data-table__body">
                        @foreach($topSarana as $row)
                            <tr class="data-table__row">
                                <x-table.td class="data-table__cell">
                                    <div class="data-table__data">
                                        <strong>{{ $row['name'] }}</strong>
                                        <small style="color: var(--text-muted);">ID: {{ $row['sarana_id'] }}</small>
                                    </div>
                                </x-table.td>
                                <x-table.td class="data-table__cell data-table__cell--meta">
                                    {{ $row['total_qty'] }} kali peminjaman
                                </x-table.td>
                                <x-table.td class="data-table__cell data-table__cell--meta">
                                    {{ $row['used_hours'] }} jam
                                </x-table.td>
                            </tr>
                        @endforeach
                    </x-table.body>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Kalender peminjaman 1 bulan (komponen kalender dashboard) --}}
    <div class="form-section">
        <div class="form-section__header">
            <h2 style="margin: 0; font-size: 1.4rem; font-weight: 700;">Kalender Peminjaman</h2>
            <div class="form-section__divider"></div>
        </div>

        <x-calendar-dashboard
            title="Peminjaman"
            :api-url="route('dashboard.calendar-events')"
        />
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartEl = document.getElementById('yearly-loan-chart');
    if (!chartEl) return;

    const labels = JSON.parse(chartEl.dataset.labels || '[]');
    const values = JSON.parse(chartEl.dataset.values || '[]');
    if (!values.length) return;

    const max = Math.max(...values, 1);
    const stepX = values.length > 1 ? 100 / (values.length - 1) : 0;

    const points = values.map((v, i) => {
        const x = stepX * i;
        const y = 39 - (v / max) * 30; // padding atas 9, bawah 1
        return x.toFixed(2) + ',' + y.toFixed(2);
    }).join(' ');

    const poly = document.getElementById('yearly-loan-polyline');
    if (poly) {
        poly.setAttribute('points', points);
    }
});
</script>
@endpush
