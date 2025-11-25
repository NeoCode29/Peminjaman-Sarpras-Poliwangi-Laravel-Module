@props([
    'title' => 'Kalender',
    'apiUrl' => null,
])

@php
    $calendarId = 'dashboard-calendar-' . uniqid();
@endphp

<div class="c-calendar-dashboard" data-dashboard-calendar="{{ $calendarId }}" data-api-url="{{ $apiUrl }}">
    <div class="c-calendar-dashboard__panel">
        <div class="c-calendar-dashboard__header">
            <button type="button" class="c-calendar-dashboard__nav" data-calendar-prev aria-label="Bulan sebelumnya">
                <x-heroicon-o-chevron-left />
            </button>
            <div class="c-calendar-dashboard__month" data-calendar-month>-</div>
            <button type="button" class="c-calendar-dashboard__nav" data-calendar-next aria-label="Bulan berikutnya">
                <x-heroicon-o-chevron-right />
            </button>
        </div>
        
        <div class="c-calendar-dashboard__grid" data-calendar-grid>
            <div class="c-calendar-dashboard__loading">
                <x-spinner />
                <p>Memuat kalender...</p>
            </div>
        </div>
    </div>
    
    <div class="c-calendar-dashboard__detail" data-calendar-detail>
        <div class="c-calendar-dashboard__detail-header">
            <h3 class="c-calendar-dashboard__detail-title" data-detail-title>Detail {{ $title }}</h3>
        </div>
        <div class="c-calendar-dashboard__detail-list" data-detail-list>
            <div class="c-calendar-dashboard__placeholder">
                <x-heroicon-o-calendar-days />
                <p>Pilih tanggal untuk melihat detail.</p>
            </div>
        </div>
    </div>
</div>
