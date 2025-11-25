@extends('layouts.app')

@section('title', 'Notifikasi')
@section('page-title', 'Notifikasi')
@section('page-subtitle', 'Kelola dan lihat semua notifikasi Anda')

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

    {{-- Stats Summary --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <x-stat-card 
            label="Total" 
            :value="$stats['total']"
            icon="heroicon-o-bell"
            variant="primary"
        />
        <x-stat-card 
            label="Belum Dibaca" 
            :value="$stats['unread']"
            icon="heroicon-o-envelope"
            variant="info"
        />
        <x-stat-card 
            label="Hari Ini" 
            :value="$stats['today']"
            icon="heroicon-o-calendar"
            variant="success"
        />
        <x-stat-card 
            label="Minggu Ini" 
            :value="$stats['this_week']"
            icon="heroicon-o-clock"
            variant="purple"
        />
    </div>

    {{-- Data Control --}}
    <section class="data-control" aria-label="Kontrol Data">
        <form method="GET" action="{{ route('notifications.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    :value="$filters['search'] ?? ''"
                    placeholder="Cari notifikasi..."
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
            @if($stats['unread'] > 0)
                <form action="{{ route('notifications.mark-all-read') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="c-button c-button--primary c-button--with-icon data-control__action" style="width:auto;">
                        <span class="c-button__icon" aria-hidden="true">
                            <x-heroicon-o-check-circle />
                        </span>
                        Tandai Semua Dibaca
                    </button>
                </form>
            @endif
        </div>
    </section>

    {{-- Filters (Optional) --}}
    <section class="data-filters" id="notification-data-filters" aria-label="Filter Data" data-filter-panel hidden>
        <form method="GET" action="{{ route('notifications.index') }}" class="data-filters__grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:center;">
            {{-- Pertahankan kata kunci pencarian saat mengubah filter --}}
            <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">

            <div>
                <x-input.select
                    label="Status"
                    name="read"
                    id="filter_read"
                    placeholder="Semua Status"
                    onchange="this.form.submit()"
                >
                    <option value="unread" {{ ($filters['read'] ?? '') === 'unread' ? 'selected' : '' }}>Belum Dibaca</option>
                    <option value="read" {{ ($filters['read'] ?? '') === 'read' ? 'selected' : '' }}>Sudah Dibaca</option>
                </x-input.select>
            </div>

            <div>
                <x-input.select
                    label="Kategori"
                    name="category"
                    id="filter_category"
                    placeholder="Semua Kategori"
                    onchange="this.form.submit()"
                >
                    @foreach($categories as $key => $label)
                        @if($key !== 'all')
                            <option value="{{ $key }}" {{ ($filters['category'] ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endif
                    @endforeach
                </x-input.select>
            </div>

            <div>
                <x-input.select
                    label="Prioritas"
                    name="priority"
                    id="filter_priority"
                    placeholder="Semua Prioritas"
                    onchange="this.form.submit()"
                >
                    <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Rendah</option>
                    <option value="normal" {{ ($filters['priority'] ?? '') === 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>Tinggi</option>
                    <option value="urgent" {{ ($filters['priority'] ?? '') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </x-input.select>
            </div>
        </form>
    </section>

    {{-- List Notifikasi --}}
    <section aria-label="Daftar Notifikasi" class="c-section c-section--flush">
        <div class="c-section__content">
            <div style="background: #ffffff; border: 1px solid var(--border-default); border-radius: 12px; padding: 1rem;">
                @if($notifications->isEmpty())
                    <x-empty-state 
                        icon="heroicon-o-bell-slash"
                        title="Tidak Ada Notifikasi"
                        description="Anda akan menerima notifikasi tentang aktivitas penting di sini"
                    />
                @else
                    <div class="c-list" style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach($notifications as $notification)
                        @php
                            $colorVariant = match($notification->data['color'] ?? 'info') {
                                'success' => 'success',
                                'danger' => 'danger',
                                'warning' => 'warning',
                                default => 'info'
                            };

                            $iconMap = [
                                'check-circle' => 'heroicon-o-check-circle',
                                'x-circle' => 'heroicon-o-x-circle',
                                'exclamation-triangle' => 'heroicon-o-exclamation-triangle',
                                'information-circle' => 'heroicon-o-information-circle',
                                'bell' => 'heroicon-o-bell',
                            ];
                            $iconComponent = $iconMap[$notification->data['icon'] ?? 'bell'] ?? 'heroicon-o-bell';

                            $catVariant = match($notification->data['category'] ?? 'general') {
                                'peminjaman' => 'primary',
                                'approval' => 'warning',
                                'system' => 'info',
                                'conflict' => 'danger',
                                default => 'default'
                            };

                            $priority = $notification->data['priority'] ?? 'normal';
                        @endphp

                        <a href="{{ $notification->data['action_url'] ?? '#' }}"
                           class="js-notification-row"
                           data-notification-id="{{ $notification->id }}"
                           style="text-decoration:none; color:inherit; display:block;"
                           @if(empty($notification->data['action_url']))
                               data-href="#"
                           @else
                               data-href="{{ $notification->data['action_url'] }}"
                           @endif
                        >
                            <x-notification.card
                                :title="$notification->data['title']"
                                :message="Str::limit($notification->data['message'], 160)"
                                :icon="$iconComponent"
                                :category="ucfirst($notification->data['category'] ?? 'General')"
                                :category-variant="$catVariant"
                                :time="$notification->created_at->diffForHumans()"
                                :unread="!$notification->read_at"
                                :priority="$priority"
                            >
                                <x-slot:actions>
                                    @if(!$notification->read_at)
                                        <form action="{{ route('notifications.mark-as-read', $notification->id) }}" method="POST" class="js-row-action">
                                            @csrf
                                            <button type="submit" class="c-button c-button--secondary c-button--size-sm">
                                                Tandai Dibaca
                                            </button>
                                        </form>
                                    @endif
                                </x-slot:actions>
                            </x-notification.card>
                        </a>
                    @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Pagination --}}
    @if(isset($notifications) && method_exists($notifications, 'links'))
        {{ $notifications->links('components.pagination') }}
    @endif
</div>
@endsection



@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Row click to action URL
    var rows = document.querySelectorAll('.js-notification-row');
    rows.forEach(function (row) {
        row.addEventListener('click', function () {
            var href = this.getAttribute('data-href');
            var notifId = this.getAttribute('data-notification-id');
            
            if (href && href !== '#') {
                // Mark as read
                fetch(`/notifications/${notifId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }).then(() => {
                    window.location.href = href;
                }).catch(console.error);
            }
        });
    });

    // Prevent row click on action buttons
    var safeElements = document.querySelectorAll('.js-row-action');
    safeElements.forEach(function (el) {
        el.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });
});
</script>
@endpush
