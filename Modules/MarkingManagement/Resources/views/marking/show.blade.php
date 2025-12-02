@extends('layouts.app')

@section('title', 'Detail Marking')
@section('page-title', 'Detail Marking')
@section('page-subtitle', 'Informasi lengkap marking dan status reservasi')

@section('content')
<div class="page-content">
    {{-- Toast Notifications --}}
    @if(session('success'))
        <div class="u-toast-container">
            <x-toast type="success" title="Berhasil" :duration="3000">
                {{ session('success') }}
            </x-toast>
        </div>
    @endif

    @if(session('error'))
        <div class="u-toast-container">
            <x-toast type="danger" title="Gagal" :duration="5000">
                {{ session('error') }}
            </x-toast>
        </div>
    @endif

    @if(session('info'))
        <div class="u-toast-container">
            <x-toast type="info" title="Info" :duration="5000">
                {{ session('info') }}
            </x-toast>
        </div>
    @endif

    @if($errors->any())
        <div class="u-toast-container">
            <x-toast type="danger" title="Terjadi Kesalahan" :duration="5000">
                {{ $errors->first() }}
            </x-toast>
        </div>
    @endif

    {{-- Status Banner --}}
    @if($marking->isExpired())
        <div style="background: var(--danger-subtle); border: 1px solid var(--danger); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; gap: 12px; align-items: center;">
                <x-heroicon-o-exclamation-triangle style="width: 24px; height: 24px; color: var(--danger); flex-shrink: 0;" />
                <div>
                    <strong style="color: var(--danger);">Marking Sudah Kadaluarsa</strong>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                        Marking ini sudah melewati batas waktu dan tidak dapat digunakan lagi.
                    </p>
                </div>
            </div>
        </div>
    @elseif($marking->isActive() && $marking->getHoursUntilExpiration() <= 24)
        <div style="background: var(--warning-subtle); border: 1px solid var(--warning); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; gap: 12px; align-items: center;">
                <x-heroicon-o-clock style="width: 24px; height: 24px; color: var(--warning); flex-shrink: 0;" />
                <div>
                    <strong style="color: var(--warning);">Marking Akan Segera Kadaluarsa</strong>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                        Marking ini akan kadaluarsa dalam {{ $marking->getHoursUntilExpiration() }} jam. 
                        Segera konversi menjadi pengajuan resmi.
                    </p>
                </div>
            </div>
        </div>
    @elseif($marking->isConverted())
        <div style="background: var(--success-subtle); border: 1px solid var(--success); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; gap: 12px; align-items: center;">
                <x-heroicon-o-check-circle style="width: 24px; height: 24px; color: var(--success); flex-shrink: 0;" />
                <div>
                    <strong style="color: var(--success);">Marking Sudah Dikonversi</strong>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                        Marking ini sudah dikonversi menjadi pengajuan peminjaman resmi.
                    </p>
                </div>
            </div>
        </div>
    @elseif($marking->isCancelled())
        <div style="background: var(--surface-subtle); border: 1px solid var(--border-default); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; gap: 12px; align-items: center;">
                <x-heroicon-o-x-circle style="width: 24px; height: 24px; color: var(--text-muted); flex-shrink: 0;" />
                <div>
                    <strong>Marking Dibatalkan</strong>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                        Marking ini telah dibatalkan oleh pengguna.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <x-detail-section
        :title="$marking->event_name"
        :description="'Dibuat oleh: ' . ($marking->user->name ?? '-') . ' · ' . $marking->created_at->format('d/m/Y H:i')"
    >
        <x-detail-list :columns="2" variant="bordered">
            <x-detail-item label="Nama Acara">
                {{ $marking->event_name }}
            </x-detail-item>

            @if($marking->ukm_id)
                <x-detail-item label="UKM / Organisasi">
                    {{ $marking->ukm->nama ?? '-' }}
                </x-detail-item>
            @endif

            <x-detail-item label="Status">
                @php
                    $statusVariant = match($marking->status) {
                        'active' => $marking->isExpired() ? 'warning' : 'success',
                        'expired' => 'danger',
                        'converted' => 'primary',
                        'cancelled' => 'default',
                        default => 'default'
                    };
                    $statuses = \Modules\MarkingManagement\Entities\Marking::getStatuses();
                @endphp
                <x-badge :variant="$statusVariant" size="sm">
                    {{ $statuses[$marking->status] ?? ucfirst($marking->status) }}
                </x-badge>
            </x-detail-item>

            <x-detail-item label="Lokasi">
                {{ $marking->getLocation() }}
            </x-detail-item>

            <x-detail-item label="Jumlah Peserta">
                {{ $marking->jumlah_peserta ?? '-' }}
            </x-detail-item>

            <x-detail-item label="Waktu Mulai">
                {{ $marking->start_datetime->format('d/m/Y H:i') }}
            </x-detail-item>

            <x-detail-item label="Waktu Selesai">
                {{ $marking->end_datetime->format('d/m/Y H:i') }}
            </x-detail-item>

            <x-detail-item label="Durasi">
                {{ $marking->getDurationInHours() }} jam ({{ $marking->getDurationInDays() }} hari)
            </x-detail-item>

            <x-detail-item label="Kadaluarsa Pada">
                @if($marking->isExpired())
                    <span style="color: var(--danger);">{{ $marking->expires_at->format('d/m/Y H:i') }} (Sudah kadaluarsa)</span>
                @elseif($marking->getHoursUntilExpiration() <= 24)
                    <span style="color: var(--warning);">{{ $marking->expires_at->format('d/m/Y H:i') }} ({{ $marking->getHoursUntilExpiration() }} jam lagi)</span>
                @else
                    {{ $marking->expires_at->format('d/m/Y H:i') }}
                @endif
            </x-detail-item>

            @if($marking->planned_submit_by)
                <x-detail-item label="Rencana Submit">
                    {{ $marking->planned_submit_by->format('d/m/Y H:i') }}
                </x-detail-item>
            @endif

            <x-detail-item label="Catatan" :full="true">
                {{ $marking->notes ?? 'Tidak ada catatan.' }}
            </x-detail-item>
        </x-detail-list>

        {{-- Actions --}}
        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border-default); align-items: center;">
            <a href="{{ route('marking.index') }}">
                <x-button type="button" variant="secondary" size="sm" icon="heroicon-o-arrow-left">
                    Kembali
                </x-button>
            </a>
            
            @can('update', $marking)
                @if($marking->isActive() && !$marking->isExpired())
                    <a href="{{ route('marking.edit', $marking) }}">
                        <x-button type="button" variant="primary" size="sm" icon="heroicon-o-pencil-square">
                            Edit
                        </x-button>
                    </a>
                @endif
            @endcan

            @can('convert', $marking)
                @if($marking->canBeConverted())
                    <form action="{{ route('marking.convert', $marking) }}" method="POST" style="display: contents;">
                        @csrf
                        <x-button type="submit" variant="primary" size="sm" icon="heroicon-o-arrow-path">
                            Konversi ke Peminjaman
                        </x-button>
                    </form>
                @endif
            @endcan

            @can('extend', $marking)
                @if($marking->isActive() && !$marking->isExpired())
                    <x-button 
                        type="button" 
                        variant="secondary"
                        size="sm"
                        icon="heroicon-o-clock"
                        onclick="document.getElementById('extendMarkingModal').open()"
                    >
                        Perpanjang
                    </x-button>
                @endif
            @endcan

            @can('delete', $marking)
                @if($marking->isActive())
                    <x-button
                        type="button"
                        variant="danger"
                        size="sm"
                        icon="heroicon-o-x-circle"
                        onclick="document.getElementById('cancelMarkingModal').open()"
                    >
                        Batalkan
                    </x-button>
                @endif
            @endcan
        </div>
    </x-detail-section>

</div>
@endsection

{{-- Extend Marking Modal --}}
@can('extend', $marking)
    @if($marking->isActive() && !$marking->isExpired())
        <x-modal id="extendMarkingModal" title="Perpanjang Marking" size="sm">
            <form
                id="extend-marking-form"
                action="{{ route('marking.extend', $marking) }}"
                method="POST"
                style="display:flex;flex-direction:column;gap:1rem;"
            >
                @csrf

                <p style="font-size:0.9rem;color:var(--text-muted);">
                    Perpanjang masa berlaku marking ini. Maksimal perpanjangan adalah {{ config('markingmanagement.max_extension_days', 7) }} hari.
                </p>

                <x-input.text
                    label="Jumlah Hari Perpanjangan"
                    name="extension_days"
                    id="extension_days"
                    type="number"
                    value="1"
                    min="1"
                    max="{{ config('markingmanagement.max_extension_days', 7) }}"
                    :required="true"
                />
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" data-modal-close>
                    Batal
                </x-button>
                <x-button
                    type="submit"
                    variant="primary"
                    size="sm"
                    form="extend-marking-form"
                >
                    Perpanjang
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endif
@endcan

{{-- Cancel Marking Modal --}}
@can('delete', $marking)
    @if($marking->isActive())
        <x-modal id="cancelMarkingModal" title="Batalkan Marking" size="sm">
            <form
                id="cancel-marking-form"
                action="{{ route('marking.destroy', $marking) }}"
                method="POST"
                style="display:flex;flex-direction:column;gap:1rem;"
            >
                @csrf
                @method('DELETE')

                <p style="font-size:0.9rem;color:var(--text-muted);">
                    Yakin ingin membatalkan marking <strong>{{ $marking->event_name }}</strong>? 
                    Tindakan ini tidak dapat dibatalkan dan reservasi akan dilepas.
                </p>
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" data-modal-close>
                    Tidak
                </x-button>
                <x-button
                    type="submit"
                    variant="danger"
                    size="sm"
                    form="cancel-marking-form"
                >
                    Ya, Batalkan
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endif
@endcan
