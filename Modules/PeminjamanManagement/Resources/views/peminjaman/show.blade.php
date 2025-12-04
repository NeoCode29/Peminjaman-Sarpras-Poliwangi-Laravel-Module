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
        $overallStatus = $overallStatus ?? optional($approvalStatus)->overall_status ?? 'pending';

        $overallVariant = match($overallStatus) {
            'approved' => 'success',
            'partially_approved' => 'primary',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'default',
        };

        $globalVariant = match($globalStatus) {
            'approved' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'default',
        };
    @endphp

    <div class="detail-layout" style="display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1.4fr);gap:16px;align-items:flex-start;">
        {{-- Kolom Kiri: Info Peminjaman & Sarana --}}
        <div class="detail-main" style="display:flex;flex-direction:column;gap:16px;">
            {{-- Info Kegiatan --}}
            <section class="card" aria-labelledby="section-info-kegiatan" style="background:var(--surface-card);border-radius:12px;border:1px solid var(--border-subtle);padding:16px;">
                <header style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:8px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <x-heroicon-o-calendar style="width:20px;height:20px;color:var(--brand-primary);" />
                        <div>
                            <h2 id="section-info-kegiatan" style="font-size:1rem;font-weight:600;margin:0;">Informasi Kegiatan</h2>
                            <p style="margin:0;color:var(--text-muted);font-size:0.85rem;">Detail dasar dari pengajuan peminjaman.</p>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                        <x-badge :variant="$overallVariant" size="sm">
                            Status: {{ $peminjaman->status_label }}
                        </x-badge>
                        @if($approvalStatus)
                            <x-badge :variant="$globalVariant" size="sm">
                                Global: {{ $approvalStatus->global_status_label }}
                            </x-badge>
                        @endif
                    </div>
                </header>

                <dl style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;font-size:0.9rem;">
                    <div>
                        <dt style="color:var(--text-muted);">Nama Acara</dt>
                        <dd style="margin:0;font-weight:600;">{{ $peminjaman->event_name }}</dd>
                    </div>
                    <div>
                        <dt style="color:var(--text-muted);">Peminjam</dt>
                        <dd style="margin:0;">
                            {{ $peminjaman->user->name ?? '-' }}
                            @if($peminjaman->ukm)
                                <br><small style="color:var(--text-muted);">UKM: {{ $peminjaman->ukm->nama }}</small>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt style="color:var(--text-muted);">Tanggal</dt>
                        <dd style="margin:0;">
                            {{ $peminjaman->start_date->format('d/m/Y') }} s/d {{ $peminjaman->end_date->format('d/m/Y') }}
                            <br>
                            <small style="color:var(--text-muted);">Durasi: {{ $peminjaman->getDurationInDays() }} hari</small>
                        </dd>
                    </div>
                    <div>
                        <dt style="color:var(--text-muted);">Waktu (Opsional)</dt>
                        <dd style="margin:0;">
                            @if($peminjaman->start_time && $peminjaman->end_time)
                                {{ $peminjaman->start_time->format('H:i') }} - {{ $peminjaman->end_time->format('H:i') }}
                            @else
                                <span style="color:var(--text-muted);">Tidak diatur</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt style="color:var(--text-muted);">Jumlah Peserta</dt>
                        <dd style="margin:0;">
                            @if($peminjaman->jumlah_peserta)
                                {{ number_format($peminjaman->jumlah_peserta) }} orang
                            @else
                                <span style="color:var(--text-muted);">Tidak diisi</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt style="color:var(--text-muted);">Dibuat Pada</dt>
                        <dd style="margin:0;">
                            {{ $peminjaman->created_at->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                </dl>

                @if($peminjaman->surat_url)
                    <div style="margin-top:12px;">
                        <a href="{{ $peminjaman->surat_url }}" target="_blank" style="font-size:0.9rem;text-decoration:underline;">
                            Lihat Surat Pengajuan
                        </a>
                    </div>
                @endif
            </section>

            {{-- Lokasi & Sarana --}}
            <section class="card" style="background:var(--surface-card);border-radius:12px;border:1px solid var(--border-subtle);padding:16px;">
                <header style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <x-heroicon-o-map-pin style="width:20px;height:20px;color:var(--brand-primary);" />
                    <div>
                        <h2 style="font-size:1rem;font-weight:600;margin:0;">Lokasi & Sarana</h2>
                        <p style="margin:0;color:var(--text-muted);font-size:0.85rem;">Detail lokasi kegiatan dan sarana yang dipinjam.</p>
                    </div>
                </header>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;font-size:0.9rem;">
                    <div>
                        <h3 style="font-size:0.9rem;font-weight:600;margin:0 0 4px;">Lokasi Kegiatan</h3>
                        <p style="margin:0;">
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
                        </p>
                    </div>

                    <div>
                        <h3 style="font-size:0.9rem;font-weight:600;margin:0 0 4px;">Informasi Tambahan</h3>
                        <p style="margin:0;color:var(--text-muted);font-size:0.85rem;">
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
                        </p>
                    </div>
                </div>

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
                                    </tr>
                                @empty
                                    <tr class="data-table__row">
                                        <x-table.td colspan="4" class="data-table__cell" align="center" style="padding:16px;color:var(--text-muted);">
                                            Tidak ada sarana yang tercatat.
                                        </x-table.td>
                                    </tr>
                                @endforelse
                            </x-table.body>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        {{-- Kolom Kanan: Workflow & Aksi --}}
        <div class="detail-sidebar" style="display:flex;flex-direction:column;gap:16px;">
            {{-- Aksi Cepat --}}
            <section class="card" style="background:var(--surface-card);border-radius:12px;border:1px solid var(--border-subtle);padding:16px;">
                <h2 style="font-size:0.95rem;font-weight:600;margin:0 0 8px;">Tindakan</h2>
                <p style="margin:0 0 12px;color:var(--text-muted);font-size:0.85rem;">Pilih tindakan yang tersedia sesuai peran dan status peminjaman.</p>

                <div style="display:flex;flex-direction:column;gap:8px;">
                    @can('update', $peminjaman)
                        <a href="{{ route('peminjaman.edit', $peminjaman) }}" style="text-decoration:none;">
                            <x-button type="button" variant="secondary" icon="heroicon-o-pencil-square" style="width:100%;justify-content:center;">
                                Edit Peminjaman
                            </x-button>
                        </a>
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
                        @if($peminjaman->isApproved())
                            <x-button
                                type="button"
                                variant="primary"
                                icon="heroicon-o-truck"
                                style="width:100%;justify-content:center;"
                                onclick="document.getElementById('pickupPeminjamanModal').open()"
                            >
                                Validasi Pengambilan
                            </x-button>
                        @endif
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
            </section>

            {{-- Workflow Approval --}}
            <section class="card" style="background:var(--surface-card);border-radius:12px;border:1px solid var(--border-subtle);padding:16px;">
                <h2 style="font-size:0.95rem;font-weight:600;margin:0 0 8px;">Workflow Persetujuan</h2>
                <p style="margin:0 0 12px;color:var(--text-muted);font-size:0.85rem;">Rincian langkah persetujuan global dan spesifik.</p>

                {{-- Global --}}
                <div style="margin-bottom:12px;">
                    <h3 style="font-size:0.9rem;font-weight:600;margin:0 0 4px;">Approval Global</h3>
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:4px;font-size:0.9rem;">
                        @foreach(($peminjaman->approvalWorkflow->where('approval_type', 'global') ?? []) as $wf)
                            <li style="display:flex;justify-content:space-between;gap:8px;">
                                <span>
                                    {{ $wf->approver->name ?? '-' }}
                                    <small style="color:var(--text-muted);">(Lv {{ $wf->approval_level }})</small>
                                </span>
                                <x-badge :variant="$wf->status_badge_class === 'badge-success' ? 'success' : ($wf->status_badge_class === 'badge-danger' ? 'danger' : ($wf->status_badge_class === 'badge-warning' ? 'warning' : 'default'))" size="sm">
                                    {{ $wf->status_label }}
                                </x-badge>
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
                            <li style="display:flex;justify-content:space-between;gap:8px;">
                                <span>
                                    {{ $wf->approver->name ?? '-' }}
                                    <small style="color:var(--text-muted);">(Lv {{ $wf->approval_level }})</small>
                                </span>
                                <x-badge :variant="$wf->status_badge_class === 'badge-success' ? 'success' : ($wf->status_badge_class === 'badge-danger' ? 'danger' : ($wf->status_badge_class === 'badge-warning' ? 'warning' : 'default'))" size="sm">
                                    {{ $wf->status_label }}
                                </x-badge>
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
                            <li style="display:flex;justify-content:space-between;gap:8px;">
                                <span>
                                    {{ $wf->approver->name ?? '-' }}
                                    @if($wf->sarana)
                                        <br><small style="color:var(--text-muted);">{{ $wf->sarana->nama }}</small>
                                    @endif
                                </span>
                                <x-badge :variant="$wf->status_badge_class === 'badge-success' ? 'success' : ($wf->status_badge_class === 'badge-danger' ? 'danger' : ($wf->status_badge_class === 'badge-warning' ? 'warning' : 'default'))" size="sm">
                                    {{ $wf->status_label }}
                                </x-badge>
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
            </section>

            {{-- Konflik Group --}}
            @if(($konflikMembers ?? collect())->isNotEmpty())
                <section class="card" style="background:var(--warning-subtle);border-radius:12px;border:1px solid var(--warning);padding:16px;">
                    <h2 style="font-size:0.95rem;font-weight:600;margin:0 0 4px;">Konflik Jadwal</h2>
                    <p style="margin:0 0 8px;color:var(--text-muted);font-size:0.85rem;">
                        Peminjaman ini berada dalam grup konflik dengan pengajuan lain berikut:
                    </p>
                    <ul style="list-style:none;padding:0;margin:0;font-size:0.85rem;">
                        @foreach($konflikMembers as $member)
                            <li>
                                <a href="{{ route('peminjaman.show', $member) }}" style="text-decoration:underline;">
                                    #{{ $member->id }} - {{ $member->event_name }} ({{ $member->user->name ?? '-' }})
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
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
                    Unggah foto bukti pengambilan (opsional) dan pastikan semua detail peminjaman sudah sesuai.
                </p>

                <x-input.file
                    label="Foto Bukti Pengambilan (Opsional)"
                    name="foto"
                    id="pickup_foto"
                    accept="image/*"
                    :helper="'Opsional. Maksimal 5MB.'"
                />
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
                    Unggah foto bukti pengembalian (opsional) dan pastikan semua sarana sudah dikembalikan.
                </p>

                <x-input.file
                    label="Foto Bukti Pengembalian (Opsional)"
                    name="foto"
                    id="return_foto"
                    accept="image/*"
                    :helper="'Opsional. Maksimal 5MB.'"
                />
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
</div>
@endsection
