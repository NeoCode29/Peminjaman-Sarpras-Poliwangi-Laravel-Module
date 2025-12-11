@extends('layouts.app')

@section('title', 'Detail Peminjaman')
@section('page-title', 'Detail Peminjaman')
@section('page-subtitle', 'Lihat status dan detail lengkap peminjaman')

@section('content')
<div class="page-content page-content--detail">
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

    @php
        $approvalStatus = $peminjaman->approvalStatus;
        $globalStatus = $globalStatus ?? optional($approvalStatus)->global_approval_status ?? 'pending';

        $displayStatus = $peminjaman->display_status_badge;
        $statusLabel = $displayStatus['label'] ?? $peminjaman->status_label;
        $statusClass = $displayStatus['class'] ?? ('status-' . $peminjaman->status);

        $statusVariant = match (true) {
            str_contains($statusClass, 'overdue') => 'danger',
            str_contains($statusClass, 'partially-approved') => 'primary',
            str_contains($statusClass, 'approved') => 'success',
            str_contains($statusClass, 'rejected') => 'danger',
            str_contains($statusClass, 'picked_up') => 'primary',
            str_contains($statusClass, 'returned') => 'default',
            str_contains($statusClass, 'cancelled') => 'default',
            str_contains($statusClass, 'pending') => 'warning',
            default => 'default',
        };

        $pickupDone = !is_null($peminjaman->pickup_validated_at);
        $pickupVariant = $pickupDone ? 'success' : 'warning';
        $pickupLabel = $pickupDone ? 'Sudah Diambil' : 'Belum Diambil';

        $globalVariant = match($globalStatus) {
            'approved' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'default',
        };
    @endphp

    <div class="detail-layout">
        {{-- Kolom Kiri: Info Peminjaman & Sarana --}}
        <div class="detail-main">
            {{-- Info Kegiatan --}}
            <x-detail-section
                title="Informasi Kegiatan"
                description="Detail dasar dari pengajuan peminjaman."
                icon="heroicon-o-calendar"
            >
                <x-slot:headerActions>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                        <x-badge :variant="$statusVariant" size="sm">
                            Status: {{ $statusLabel }}
                        </x-badge>
                        @if(!in_array($peminjaman->status, [
                            \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_RETURNED,
                            \Modules\PeminjamanManagement\Entities\Peminjaman::STATUS_CANCELLED,
                        ], true))
                            <x-badge :variant="$pickupVariant" size="sm">
                                Pickup: {{ $pickupLabel }}
                            </x-badge>
                        @endif
                        @if($approvalStatus)
                            <x-badge :variant="$globalVariant" size="sm">
                                Global: {{ $approvalStatus->global_status_label }}
                            </x-badge>
                        @endif
                    </div>
                </x-slot:headerActions>

                <x-detail-list :columns="3" variant="bordered">
                    <x-detail-item label="Nama Acara">
                        {{ $peminjaman->event_name }}
                    </x-detail-item>

                    <x-detail-item label="Peminjam">
                        {{ $peminjaman->user->name ?? '-' }}
                        @if($peminjaman->ukm)
                            <br><small style="color:var(--text-muted);">UKM: {{ $peminjaman->ukm->nama }}</small>
                        @endif
                    </x-detail-item>

                    <x-detail-item label="Tanggal">
                        {{ $peminjaman->start_date->format('d/m/Y') }} s/d {{ $peminjaman->end_date->format('d/m/Y') }}
                        <br>
                        <small style="color:var(--text-muted);">Durasi: {{ $peminjaman->getDurationInDays() }} hari</small>
                    </x-detail-item>

                    <x-detail-item label="Waktu (Opsional)">
                        @if($peminjaman->start_time && $peminjaman->end_time)
                            {{ $peminjaman->start_time->format('H:i') }} - {{ $peminjaman->end_time->format('H:i') }}
                        @else
                            <span style="color:var(--text-muted);">Tidak diatur</span>
                        @endif
                    </x-detail-item>

                    <x-detail-item label="Jumlah Peserta">
                        @if($peminjaman->jumlah_peserta)
                            {{ number_format($peminjaman->jumlah_peserta) }} orang
                        @else
                            <span style="color:var(--text-muted);">Tidak diisi</span>
                        @endif
                    </x-detail-item>

                    <x-detail-item label="Dibuat Pada">
                        {{ $peminjaman->created_at->format('d/m/Y H:i') }}
                    </x-detail-item>
                </x-detail-list>

                @php
                    $canSeePhotos = auth()->user() && (auth()->id() === $peminjaman->user_id || auth()->user()->hasRole('Admin Sarpras'));
                @endphp

                @if($canSeePhotos && ($peminjaman->foto_pickup_url || $peminjaman->foto_return_url))
                    <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:12px;">
                        @if($peminjaman->foto_pickup_url)
                            <div style="font-size:0.85rem;">
                                <div style="margin-bottom:4px;color:var(--text-muted);font-weight:600;">Foto Pengambilan</div>
                                <a href="{{ $peminjaman->foto_pickup_url }}" target="_blank" style="display:inline-block;border-radius:8px;overflow:hidden;border:1px solid var(--border-subtle);max-width:220px;">
                                    <img src="{{ $peminjaman->foto_pickup_url }}" alt="Foto Pengambilan" style="display:block;width:100%;height:auto;object-fit:cover;">
                                </a>
                            </div>
                        @endif
                        @if($peminjaman->foto_return_url)
                            <div style="font-size:0.85rem;">
                                <div style="margin-bottom:4px;color:var(--text-muted);font-weight:600;">Foto Pengembalian</div>
                                <a href="{{ $peminjaman->foto_return_url }}" target="_blank" style="display:inline-block;border-radius:8px;overflow:hidden;border:1px solid var(--border-subtle);max-width:220px;">
                                    <img src="{{ $peminjaman->foto_return_url }}" alt="Foto Pengembalian" style="display:block;width:100%;height:auto;object-fit:cover;">
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                @if($peminjaman->surat_url)
                    <x-slot:footer>
                        <a href="{{ $peminjaman->surat_url }}" target="_blank" style="font-size:0.9rem;text-decoration:underline;">
                            Lihat Surat Pengajuan
                        </a>
                    </x-slot:footer>
                @endif
            </x-detail-section>

            {{-- Lokasi & Sarana --}}
            <x-detail-section
                title="Lokasi & Sarana"
                description="Detail lokasi kegiatan dan sarana yang dipinjam."
                icon="heroicon-o-map-pin"
            >
                <x-detail-list :columns="2" variant="bordered">
                    <x-detail-item label="Lokasi Kegiatan">
                        @if($peminjaman->prasarana)
                            {{ $peminjaman->prasarana->name }}
                            @if($peminjaman->prasarana->lokasi)
                                <br><small style="color:var(--text-muted);">{{ $peminjaman->prasarana->lokasi }}</small>
                            @endif
                        @elseif($peminjaman->lokasi_custom)
                            {{ $peminjaman->lokasi_custom }}
                        @else
                            <span style="color:var(--text-muted);">Tidak ditentukan</span>
                        @endif
                    </x-detail-item>

                    <x-detail-item label="Informasi Tambahan">
                        <span style="color:var(--text-muted);font-size:0.85rem;">
                            @if($peminjaman->approvedBy)
                                Disetujui oleh {{ $peminjaman->approvedBy->name }} pada {{ optional($peminjaman->approved_at)->format('d/m/Y H:i') }}<br>
                            @endif
                            @if($peminjaman->pickupValidatedBy)
                                Pengambilan divalidasi oleh {{ $peminjaman->pickupValidatedBy->name }} pada {{ optional($peminjaman->pickup_validated_at)->format('d/m/Y H:i') }}<br>
                            @endif
                            @if($peminjaman->returnValidatedBy)
                                Pengembalian divalidasi oleh {{ $peminjaman->returnValidatedBy->name }} pada {{ optional($peminjaman->return_validated_at)->format('d/m/Y H:i') }}<br>
                            @endif
                            @if($peminjaman->cancelledBy)
                                Dibatalkan oleh {{ $peminjaman->cancelledBy->name }} pada {{ optional($peminjaman->cancelled_at)->format('d/m/Y H:i') }}
                            @endif
                        </span>
                    </x-detail-item>
                </x-detail-list>

                {{-- Tabel Sarana --}}
                <div style="margin-top:16px;">
                    <h3 style="font-size:0.9rem;font-weight:600;margin:0 0 8px;">Sarana yang Dipinjam</h3>
                    <div class="data-table__container" style="border-radius:8px;border:1px solid var(--border-subtle);overflow:hidden;">
                        <table class="data-table__table">
                            <x-table.head class="data-table__head">
                                <tr class="data-table__row">
                                    <x-table.th class="data-table__cell">Sarana</x-table.th>
                                    <x-table.th class="data-table__cell">Tipe</x-table.th>
                                    <x-table.th class="data-table__cell data-table__cell--number">Diminta</x-table.th>
                                    <x-table.th class="data-table__cell data-table__cell--number">Disetujui</x-table.th>
                                    <x-table.th class="data-table__cell data-table__cell--number">Aksi</x-table.th>
                                </tr>
                            </x-table.head>
                            <x-table.body class="data-table__body">
                                @forelse($peminjaman->items as $item)
                                    <tr class="data-table__row">
                                        <x-table.td class="data-table__cell">
                                            <div class="data-table__data">
                                                <strong>{{ $item->sarana->nama ?? '-' }}</strong>
                                                <small style="color:var(--text-muted);">{{ $item->sarana->kode_sarana ?? '' }}</small>
                                            </div>
                                        </x-table.td>
                                        <x-table.td class="data-table__cell">
                                            {{ optional($item->sarana)->type === 'serialized' ? 'Serialized' : 'Pooled' }}
                                        </x-table.td>
                                        <x-table.td class="data-table__cell data-table__cell--number">
                                            {{ $item->qty_requested }}
                                        </x-table.td>
                                        <x-table.td class="data-table__cell data-table__cell--number">
                                            {{ $item->qty_approved ?? '-' }}
                                        </x-table.td>
                                        <x-table.td class="data-table__cell data-table__cell--number">
                                            @php
                                                $isSerialized = optional($item->sarana)->type === 'serialized';
                                                $globalWorkflows = $peminjaman->approvalWorkflow->where('approval_type', 'global');
                                                $globalAllApproved = $globalWorkflows->isNotEmpty() ? $globalWorkflows->every(fn($wf) => $wf->status === 'approved') : true;
                                                $saranaWorkflows = $peminjaman->approvalWorkflow
                                                    ->where('approval_type', 'sarana')
                                                    ->where('sarana_id', $item->sarana_id);
                                                $hasSpecific = $saranaWorkflows->isNotEmpty();
                                                $specificAllApproved = $hasSpecific ? $saranaWorkflows->every(fn($wf) => $wf->status === 'approved') : true;
                                                $canAdjust = $isSerialized && $globalAllApproved && $specificAllApproved && auth()->user()?->can('adjustSarpras', $peminjaman);
                                                $options = ($serializedUnitOptions[$item->id] ?? null);
                                            @endphp
                                            @if($canAdjust && $options)
                                                <x-button
                                                    type="button"
                                                    variant="primary"
                                                    size="xs"
                                                    onclick="document.getElementById('assignUnitsModal-{{ $item->id }}').open()"
                                                >
                                                    Atur Unit
                                                </x-button>
                                            @else
                                                <span style="font-size:0.75rem;color:var(--text-muted);">-</span>
                                            @endif
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <tr class="data-table__row">
                                        <x-table.td colspan="5" class="data-table__cell" align="center" style="padding:16px;color:var(--text-muted);">
                                            Tidak ada sarana yang tercatat.
                                        </x-table.td>
                                    </tr>
                                @endforelse
                            </x-table.body>
                        </table>
                    </div>
                </div>
                @foreach($peminjaman->items as $item)
                    @php
                        $isSerialized = optional($item->sarana)->type === 'serialized';
                        $globalWorkflows = $peminjaman->approvalWorkflow->where('approval_type', 'global');
                        $globalAllApproved = $globalWorkflows->isNotEmpty() ? $globalWorkflows->every(fn($wf) => $wf->status === 'approved') : true;
                        $saranaWorkflows = $peminjaman->approvalWorkflow
                            ->where('approval_type', 'sarana')
                            ->where('sarana_id', $item->sarana_id);
                        $hasSpecific = $saranaWorkflows->isNotEmpty();
                        $specificAllApproved = $hasSpecific ? $saranaWorkflows->every(fn($wf) => $wf->status === 'approved') : true;
                        $canAdjust = $isSerialized && $globalAllApproved && $specificAllApproved && auth()->user()?->can('adjustSarpras', $peminjaman);
                        $options = ($serializedUnitOptions[$item->id] ?? null);
                    @endphp
                    @if($canAdjust && $options)
                        <x-modal id="assignUnitsModal-{{ $item->id }}" title="Atur Unit - {{ $item->sarana->nama ?? '-' }}" size="md">
                            <form
                                id="assign-units-form-{{ $item->id }}"
                                method="POST"
                                action="{{ route('peminjaman.assign-units', $peminjaman) }}"
                                style="display:flex;flex-direction:column;gap:1rem;"
                            >
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <p style="font-size:0.9rem;color:var(--text-muted);">
                                    Pilih unit yang akan dipinjam untuk sarana ini.
                                </p>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                    @foreach($options['units'] as $unit)
                                        @php
                                            $checked = $unit['is_assigned_to_this'];
                                            $disabled = !$checked && $unit['status'] !== 'tersedia';
                                        @endphp
                                        <label style="border:1px solid var(--border-subtle);border-radius:6px;padding:4px 8px;font-size:0.8rem;display:flex;align-items:center;gap:4px;opacity:{{ $disabled ? 0.6 : 1 }};">
                                            <input
                                                type="checkbox"
                                                name="unit_ids[]"
                                                value="{{ $unit['id'] }}"
                                                @if($checked) checked @endif
                                                @if($disabled) disabled @endif
                                            >
                                            <span>
                                                {{ $unit['unit_code'] }}
                                                <small style="color:var(--text-muted);">({{ $unit['status'] }})</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </form>

                            <x-slot:footer>
                                <x-button type="button" variant="secondary" data-modal-close>
                                    Batal
                                </x-button>
                                <x-button type="submit" variant="primary" form="assign-units-form-{{ $item->id }}">
                                    Simpan
                                </x-button>
                            </x-slot:footer>
                        </x-modal>
                    @endif
                @endforeach
            </x-detail-section>
        </div>

        {{-- Kolom Kanan: Workflow & Aksi --}}
        <div class="detail-sidebar">
            {{-- Aksi Cepat --}}
            <x-detail-section
                title="Tindakan"
                description="Pilih tindakan yang tersedia sesuai peran dan status peminjaman."
            >
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @can('update', $peminjaman)
                        <a href="{{ route('peminjaman.edit', $peminjaman) }}" style="text-decoration:none;">
                            <x-button type="button" variant="secondary" icon="heroicon-o-pencil-square" style="width:100%;justify-content:center;">
                                Edit Peminjaman
                            </x-button>
                        </a>
                    @endcan

                    @can('uploadPickupPhoto', $peminjaman)
                        <x-button
                            type="button"
                            variant="secondary"
                            size="sm"
                            icon="heroicon-o-camera"
                            style="width:100%;justify-content:center;"
                            onclick="document.getElementById('uploadPickupPhotoModal').open()"
                        >
                            Upload Foto Pengambilan
                        </x-button>
                    @endcan

                    @can('uploadReturnPhoto', $peminjaman)
                        <x-button
                            type="button"
                            variant="secondary"
                            size="sm"
                            icon="heroicon-o-camera"
                            style="width:100%;justify-content:center;"
                            onclick="document.getElementById('uploadReturnPhotoModal').open()"
                        >
                            Upload Foto Pengembalian
                        </x-button>
                    @endcan

                    @can('cancel', $peminjaman)
                        @if($peminjaman->isPending() || $peminjaman->isApproved())
                            <x-button
                                type="button"
                                variant="danger"
                                icon="heroicon-o-x-circle"
                                style="width:100%;justify-content:center;"
                                onclick="document.getElementById('cancelPeminjamanMainModal').open()"
                            >
                                Batalkan Peminjaman
                            </x-button>
                        @endif
                    @endcan

                    @can('validatePickup', $peminjaman)
                        <x-button
                            type="button"
                            variant="primary"
                            icon="heroicon-o-truck"
                            style="width:100%;justify-content:center;"
                            onclick="document.getElementById('pickupPeminjamanModal').open()"
                        >
                            Validasi Pengambilan
                        </x-button>
                    @endcan

                    @can('validateReturn', $peminjaman)
                        @if($peminjaman->isPickedUp())
                            <x-button
                                type="button"
                                variant="primary"
                                icon="heroicon-o-arrow-uturn-left"
                                style="width:100%;justify-content:center;"
                                onclick="document.getElementById('returnPeminjamanModal').open()"
                            >
                                Validasi Pengembalian
                            </x-button>
                        @endif
                    @endcan
                </div>
            </x-detail-section>

            {{-- Workflow Approval --}}
            <x-detail-section
                title="Workflow Persetujuan"
                description="Rincian langkah persetujuan global dan spesifik."
            >
                {{-- Global --}}
                <div style="margin-bottom:12px;">
                    <h3 style="font-size:0.9rem;font-weight:600;margin:0 0 4px;">Approval Global</h3>
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:4px;font-size:0.9rem;">
                        @foreach(($peminjaman->approvalWorkflow->where('approval_type', 'global') ?? []) as $wf)
                            <li style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
                                <span>
                                    {{ $wf->approver->name ?? '-' }}
                                    <small style="color:var(--text-muted);">(Lv {{ $wf->approval_level }})</small>
                                </span>
                                <div style="display:flex;align-items:center;gap:4px;">
                                    <x-badge :variant="$wf->status_badge_class === 'badge-success' ? 'success' : ($wf->status_badge_class === 'badge-danger' ? 'danger' : ($wf->status_badge_class === 'badge-warning' ? 'warning' : 'default'))" size="sm">
                                        {{ $wf->status_label }}
                                    </x-badge>
                                    @if($wf->status === 'pending' && auth()->id() === $wf->approver_id)
                                        <form
                                            method="POST"
                                            action="{{ route('peminjaman.approval.process', $peminjaman) }}"
                                            style="display:inline-flex;gap:4px;"
                                        >
                                            @csrf
                                            <input type="hidden" name="approval_type" value="global">
                                            <input type="hidden" name="action" value="approve">
                                            <x-button type="submit" variant="success" size="xs">
                                                Setujui
                                            </x-button>
                                        </form>
                                        <x-button
                                            type="button"
                                            variant="danger"
                                            size="xs"
                                            onclick="openRejectApprovalModal('global')"
                                        >
                                            Tolak
                                        </x-button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                        @if(($peminjaman->approvalWorkflow->where('approval_type', 'global') ?? collect())->isEmpty())
                            <li style="color:var(--text-muted);">Tidak ada workflow global.</li>
                        @endif
                    </ul>
                </div>

                {{-- Specific Prasarana --}}
                <div style="margin-bottom:12px;">
                    <h3 style="font-size:0.9rem;font-weight:600;margin:0 0 4px;">Approval Prasarana</h3>
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:4px;font-size:0.9rem;">
                        @foreach(($peminjaman->approvalWorkflow->where('approval_type', 'prasarana') ?? []) as $wf)
                            <li style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
                                <span>
                                    {{ $wf->approver->name ?? '-' }}
                                    <small style="color:var(--text-muted);">(Lv {{ $wf->approval_level }})</small>
                                    @if($wf->prasarana)
                                        <br><small style="color:var(--text-muted);">{{ $wf->prasarana->name }}</small>
                                    @endif
                                </span>
                                <div style="display:flex;align-items:center;gap:4px;">
                                    <x-badge :variant="$wf->status_badge_class === 'badge-success' ? 'success' : ($wf->status_badge_class === 'badge-danger' ? 'danger' : ($wf->status_badge_class === 'badge-warning' ? 'warning' : 'default'))" size="sm">
                                        {{ $wf->status_label }}
                                    </x-badge>
                                    @if($wf->status === 'pending' && auth()->id() === $wf->approver_id)
                                        <form
                                            method="POST"
                                            action="{{ route('peminjaman.approval.process', $peminjaman) }}"
                                            style="display:inline-flex;gap:4px;"
                                        >
                                            @csrf
                                            <input type="hidden" name="approval_type" value="prasarana">
                                            <input type="hidden" name="prasarana_id" value="{{ $wf->prasarana_id }}">
                                            <input type="hidden" name="action" value="approve">
                                            <x-button type="submit" variant="success" size="xs">
                                                Setujui
                                            </x-button>
                                        </form>
                                        <x-button
                                            type="button"
                                            variant="danger"
                                            size="xs"
                                            onclick="openRejectApprovalModal('prasarana', {{ $wf->prasarana_id }})"
                                        >
                                            Tolak
                                        </x-button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                        @if(($peminjaman->approvalWorkflow->where('approval_type', 'prasarana') ?? collect())->isEmpty())
                            <li style="color:var(--text-muted);">Tidak ada workflow prasarana.</li>
                        @endif
                    </ul>
                </div>

                {{-- Specific Sarana --}}
                <div>
                    <h3 style="font-size:0.9rem;font-weight:600;margin:0 0 4px;">Approval Sarana</h3>
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:4px;font-size:0.9rem;">
                        @foreach(($peminjaman->approvalWorkflow->where('approval_type', 'sarana') ?? []) as $wf)
                            <li style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
                                <span>
                                    {{ $wf->approver->name ?? '-' }}
                                    @if($wf->sarana)
                                        <br><small style="color:var(--text-muted);">{{ $wf->sarana->nama }}</small>
                                    @endif
                                </span>
                                <div style="display:flex;align-items:center;gap:4px;">
                                    <x-badge :variant="$wf->status_badge_class === 'badge-success' ? 'success' : ($wf->status_badge_class === 'badge-danger' ? 'danger' : ($wf->status_badge_class === 'badge-warning' ? 'warning' : 'default'))" size="sm">
                                        {{ $wf->status_label }}
                                    </x-badge>
                                    @if($wf->status === 'pending' && auth()->id() === $wf->approver_id)
                                        <form
                                            method="POST"
                                            action="{{ route('peminjaman.approval.process', $peminjaman) }}"
                                            style="display:inline-flex;gap:4px;"
                                        >
                                            @csrf
                                            <input type="hidden" name="approval_type" value="sarana">
                                            <input type="hidden" name="sarana_id" value="{{ $wf->sarana_id }}">
                                            <input type="hidden" name="action" value="approve">
                                            <x-button type="submit" variant="success" size="xs">
                                                Setujui
                                            </x-button>
                                        </form>
                                        <x-button
                                            type="button"
                                            variant="danger"
                                            size="xs"
                                            onclick="openRejectApprovalModal('sarana', {{ $wf->sarana_id }})"
                                        >
                                            Tolak
                                        </x-button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                        @if(($peminjaman->approvalWorkflow->where('approval_type', 'sarana') ?? collect())->isEmpty())
                            <li style="color:var(--text-muted);">Tidak ada workflow sarana.</li>
                        @endif
                    </ul>
                </div>

                {{-- Override Info --}}
                @if(($overrideApprovals ?? collect())->isNotEmpty())
                    <div style="margin-top:12px;padding-top:8px;border-top:1px dashed var(--border-subtle);">
                        <h3 style="font-size:0.9rem;font-weight:600;margin:0 0 4px;">Riwayat Override</h3>
                        <ul style="list-style:none;padding:0;margin:0;font-size:0.85rem;color:var(--text-muted);">
                            @foreach($overrideApprovals as $wf)
                                <li>
                                    {{ $wf->overriddenBy->name ?? 'Approver' }} melakukan override pada {{ optional($wf->overridden_at)->format('d/m/Y H:i') }}.
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-detail-section>

            {{-- Konflik Group --}}
            @if(($konflikMembers ?? collect())->isNotEmpty())
                <x-detail-section
                    title="Konflik Jadwal"
                    description="Peminjaman ini berada dalam grup konflik dengan pengajuan lain."
                >
                    <ul style="list-style:none;padding:0;margin:0;font-size:0.85rem;">
                        @foreach($konflikMembers as $member)
                            <li>
                                <a href="{{ route('peminjaman.show', $member) }}" style="text-decoration:underline;">
                                    #{{ $member->id }} - {{ $member->event_name }} ({{ $member->user->name ?? '-' }})
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-detail-section>
            @endif
        </div>
    </div>

    {{-- Modal Cancel --}}
    @can('cancel', $peminjaman)
        <x-modal id="cancelPeminjamanMainModal" title="Batalkan Peminjaman" size="sm">
            <form
                id="cancel-peminjaman-main-form"
                action="{{ route('peminjaman.cancel', $peminjaman) }}"
                method="POST"
                style="display:flex;flex-direction:column;gap:1rem;"
            >
                @csrf

                <p style="font-size:0.9rem;color:var(--text-muted);">
                    Yakin ingin membatalkan peminjaman <strong>{{ $peminjaman->event_name }}</strong>?
                </p>

                <x-input.text
                    label="Alasan Pembatalan (Opsional)"
                    name="reason"
                    id="cancel_reason_main"
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
                    form="cancel-peminjaman-main-form"
                >
                    Ya, Batalkan
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan

    {{-- Modal Upload Foto Pengambilan (Peminjam) --}}
    @can('uploadPickupPhoto', $peminjaman)
        <x-modal id="uploadPickupPhotoModal" title="Upload Foto Pengambilan" size="sm">
            <form
                id="upload-pickup-photo-form"
                action="{{ route('peminjaman.upload-pickup-photo', $peminjaman) }}"
                method="POST"
                enctype="multipart/form-data"
                style="display:flex;flex-direction:column;gap:1rem;"
            >
                @csrf

                <x-input.file
                    label="Foto Pengambilan"
                    name="foto"
                    id="upload_pickup_foto"
                    accept="image/*"
                    :helper="'Wajib. Maksimal 5MB.'"
                    required
                />
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" form="upload-pickup-photo-form">
                    Simpan Foto
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan

    {{-- Modal Upload Foto Pengembalian (Peminjam) --}}
    @can('uploadReturnPhoto', $peminjaman)
        <x-modal id="uploadReturnPhotoModal" title="Upload Foto Pengembalian" size="sm">
            <form
                id="upload-return-photo-form"
                action="{{ route('peminjaman.upload-return-photo', $peminjaman) }}"
                method="POST"
                enctype="multipart/form-data"
                style="display:flex;flex-direction:column;gap:1rem;"
            >
                @csrf

                <x-input.file
                    label="Foto Pengembalian"
                    name="foto"
                    id="upload_return_foto"
                    accept="image/*"
                    :helper="'Wajib. Maksimal 5MB.'"
                    required
                />
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" form="upload-return-photo-form">
                    Simpan Foto
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan

    {{-- Modal Pickup --}}
    @can('validatePickup', $peminjaman)
        <x-modal id="pickupPeminjamanModal" title="Validasi Pengambilan" size="md">
            <form
                id="pickup-peminjaman-form"
                action="{{ route('peminjaman.validate-pickup', $peminjaman) }}"
                method="POST"
                enctype="multipart/form-data"
                style="display:flex;flex-direction:column;gap:1rem;"
            >
                @csrf

                <p style="font-size:0.9rem;color:var(--text-muted);">
                    Pilih unit yang benar-benar diambil berdasarkan bukti yang telah diunggah oleh peminjam.
                </p>

                {{-- Pilihan unit per item (serialized) untuk pengambilan --}}
                <div style="margin-top:0.5rem;display:flex;flex-direction:column;gap:0.75rem;">
                    @foreach($peminjaman->items as $item)
                        @php
                            $isSerialized = optional($item->sarana)->type === 'serialized';
                            $options = ($serializedUnitOptions[$item->id] ?? null);
                            $activeUnits = $options && !empty($options['units'])
                                ? collect($options['units'])->filter(fn($u) => $u['is_assigned_to_this'])
                                : collect();
                        @endphp
                        @if($isSerialized && $options && $activeUnits->isNotEmpty())
                            <div style="border:1px solid var(--border-subtle);border-radius:8px;padding:8px 10px;">
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;font-size:0.85rem;font-weight:600;">
                                    @php $groupId = 'pickup-item-'.$item->id; @endphp
                                    <input
                                        type="checkbox"
                                        class="unit-group-parent-checkbox"
                                        data-unit-group="{{ $groupId }}"
                                    >
                                    <span>
                                        {{ $item->sarana->nama ?? '-' }}
                                        <span style="font-weight:400;color:var(--text-muted);">
                                            &ndash; Pilih unit yang diambil
                                        </span>
                                    </span>
                                </div>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;padding-left:18px;">
                                    @foreach($activeUnits as $unit)
                                        <label style="border:1px solid var(--border-subtle);border-radius:6px;padding:4px 8px;font-size:0.8rem;display:flex;align-items:center;gap:4px;">
                                            <input
                                                type="checkbox"
                                                class="unit-group-child-checkbox"
                                                data-unit-group="{{ $groupId }}"
                                                name="unit_assignments[{{ $item->id }}][]"
                                                value="{{ $unit['id'] }}"
                                            >
                                            <span>
                                                {{ $unit['unit_code'] }}
                                                <small style="color:var(--text-muted);">({{ $unit['status'] }})</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Checklist sarana pooled (non-serialized) untuk menandai sudah diambil pada saat pickup --}}
                <div style="margin-top:0.5rem;display:flex;flex-direction:column;gap:0.4rem;">
                    @foreach($peminjaman->items as $item)
                        @php
                            $isSerialized = optional($item->sarana)->type === 'serialized';
                        @endphp
                        @if(!$isSerialized)
                            <div style="border:1px solid var(--border-subtle);border-radius:8px;padding:8px 10px;">
                                <div style="display:flex;align-items:center;gap:6px;font-size:0.85rem;font-weight:600;">
                                    <input
                                        type="checkbox"
                                        name="pooled_pickup_items[]"
                                        value="{{ $item->id }}"
                                        @if(($item->qty_approved ?? $item->qty_requested ?? 0) <= 0) disabled @endif
                                    >
                                    <span>
                                        {{ $item->sarana->nama ?? '-' }}
                                        <span style="font-weight:400;color:var(--text-muted);">
                                            &ndash; <span style="font-size:0.8rem;">Tandai sarana ini sudah diambil (qty yang disetujui)</span>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" form="pickup-peminjaman-form">
                    Simpan & Tandai Sudah Diambil
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan

    {{-- Modal Return --}}
    @can('validateReturn', $peminjaman)
        <x-modal id="returnPeminjamanModal" title="Validasi Pengembalian" size="md">
            <form
                id="return-peminjaman-form"
                action="{{ route('peminjaman.validate-return', $peminjaman) }}"
                method="POST"
                enctype="multipart/form-data"
                style="display:flex;flex-direction:column;gap:1rem;"
            >
                @csrf

                <p style="font-size:0.9rem;color:var(--text-muted);">
                    Pilih unit yang dikembalikan pada proses ini berdasarkan bukti yang telah diunggah oleh peminjam. Pengembalian sebagian diperbolehkan.
                </p>

                {{-- Pilihan unit per item (serialized) untuk pengembalian --}}
                <div style="margin-top:0.5rem;display:flex;flex-direction:column;gap:0.4rem;">
                    @foreach($peminjaman->items as $item)
                        @php
                            $isSerialized = optional($item->sarana)->type === 'serialized';
                            $options = ($serializedUnitOptions[$item->id] ?? null);
                            $activeUnits = $options && !empty($options['units'])
                                ? collect($options['units'])->filter(fn($u) => $u['is_assigned_to_this'])
                                : collect();
                        @endphp
                        @if($isSerialized && $options && $activeUnits->isNotEmpty())
                            <div style="border:1px solid var(--border-subtle);border-radius:8px;padding:8px 10px;">
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;font-size:0.85rem;font-weight:600;">
                                    @php $groupId = 'return-item-'.$item->id; @endphp
                                    <input
                                        type="checkbox"
                                        class="unit-group-parent-checkbox"
                                        data-unit-group="{{ $groupId }}"
                                    >
                                    <span>
                                        {{ $item->sarana->nama ?? '-' }}
                                        <span style="font-weight:400;color:var(--text-muted);">
                                            &ndash; Pilih unit yang dikembalikan sekarang
                                        </span>
                                    </span>
                                </div>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;padding-left:18px;">
                                    @foreach($activeUnits as $unit)
                                        <label style="border:1px solid var(--border-subtle);border-radius:6px;padding:4px 8px;font-size:0.8rem;display:flex;align-items:center;gap:4px;">
                                            <input
                                                type="checkbox"
                                                class="unit-group-child-checkbox"
                                                data-unit-group="{{ $groupId }}"
                                                name="unit_assignments[{{ $item->id }}][]"
                                                value="{{ $unit['id'] }}"
                                            >
                                            <span>
                                                {{ $unit['unit_code'] }}
                                                <small style="color:var(--text-muted);">({{ $unit['status'] }})</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Checklist sarana pooled (non-serialized) untuk menandai sudah dikembalikan penuh --}}
                <div style="margin-top:0.5rem;display:flex;flex-direction:column;gap:0.4rem;">
                    @foreach($peminjaman->items as $item)
                        @php
                            $isSerialized = optional($item->sarana)->type === 'serialized';
                        @endphp
                        @if(!$isSerialized)
                            <div style="border:1px solid var(--border-subtle);border-radius:8px;padding:8px 10px;">
                                <div style="display:flex;align-items:center;gap:6px;font-size:0.85rem;font-weight:600;">
                                    <input
                                        type="checkbox"
                                        name="pooled_return_items[]"
                                        value="{{ $item->id }}"
                                        @if(($item->qty_approved ?? $item->qty_requested ?? 0) <= 0) disabled @endif
                                    >
                                    <span>
                                        {{ $item->sarana->nama ?? '-' }}
                                        <span style="font-weight:400;color:var(--text-muted);">
                                            &ndash; <span style="font-size:0.8rem;">Semua qty dianggap sudah dikembalikan jika dicentang</span>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" form="return-peminjaman-form">
                    Simpan & Tandai Sudah Dikembalikan
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan

    {{-- Modal Approval Reject Reason --}}
    <x-modal id="approvalRejectModal" title="Tolak Pengajuan" size="sm">
        <form
            id="approval-reject-form"
            action="{{ route('peminjaman.approval.process', $peminjaman) }}"
            method="POST"
            style="display:flex;flex-direction:column;gap:1rem;"
        >
            @csrf
            <input type="hidden" name="approval_type" id="reject_approval_type" value="">
            <input type="hidden" name="sarana_id" id="reject_sarana_id" value="">
            <input type="hidden" name="prasarana_id" id="reject_prasarana_id" value="">
            <input type="hidden" name="action" value="reject">

            <p style="font-size:0.9rem;color:var(--text-muted);">
                Masukkan alasan penolakan untuk pengajuan ini.
            </p>

            <x-input.textarea
                label="Alasan Penolakan"
                name="reason"
                id="reject_reason"
                rows="3"
                required
            />
        </form>

        <x-slot:footer>
            <x-button type="button" variant="secondary" data-modal-close>
                Batal
            </x-button>
            <x-button type="submit" variant="danger" form="approval-reject-form">
                Ya, Tolak
            </x-button>
        </x-slot:footer>
    </x-modal>

    <script>
    function openRejectApprovalModal(type, referenceId) {
        var typeInput = document.getElementById('reject_approval_type');
        var saranaInput = document.getElementById('reject_sarana_id');
        var prasaranaInput = document.getElementById('reject_prasarana_id');
        var reasonInput = document.getElementById('reject_reason');
        var modal = document.getElementById('approvalRejectModal');

        if (!typeInput || !saranaInput || !prasaranaInput || !reasonInput || !modal) {
            return;
        }

        typeInput.value = type;
        saranaInput.value = '';
        prasaranaInput.value = '';

        if (type === 'sarana' && typeof referenceId !== 'undefined') {
            saranaInput.value = referenceId;
        }

        if (type === 'prasarana' && typeof referenceId !== 'undefined') {
            prasaranaInput.value = referenceId;
        }

        reasonInput.value = '';
        modal.open();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var parentCheckboxes = document.querySelectorAll('.unit-group-parent-checkbox');
        var childCheckboxes = document.querySelectorAll('.unit-group-child-checkbox');

        parentCheckboxes.forEach(function (parent) {
            parent.addEventListener('change', function () {
                var group = parent.getAttribute('data-unit-group');
                var children = document.querySelectorAll('.unit-group-child-checkbox[data-unit-group="' + group + '"]');
                children.forEach(function (child) {
                    if (!child.disabled) {
                        child.checked = parent.checked;
                    }
                });
            });
        });

        childCheckboxes.forEach(function (child) {
            child.addEventListener('change', function () {
                var group = child.getAttribute('data-unit-group');
                var groupChildren = Array.prototype.slice.call(document.querySelectorAll('.unit-group-child-checkbox[data-unit-group="' + group + '"]'));
                var parent = document.querySelector('.unit-group-parent-checkbox[data-unit-group="' + group + '"]');
                if (!parent) {
                    return;
                }

                var allChecked = groupChildren.length > 0 && groupChildren.every(function (c) { return c.checked || c.disabled; });
                var anyChecked = groupChildren.some(function (c) { return c.checked; });

                parent.indeterminate = !allChecked && anyChecked;
                parent.checked = allChecked;
            });
        });
    });
    </script>
</div>
@endsection
