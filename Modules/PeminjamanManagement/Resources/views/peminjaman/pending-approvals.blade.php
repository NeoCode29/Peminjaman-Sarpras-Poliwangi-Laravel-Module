@extends('layouts.app')

@section('title', 'Peminjaman Menunggu Persetujuan')
@section('page-title', 'Peminjaman Menunggu Persetujuan')
@section('page-subtitle', 'Daftar peminjaman yang menunggu tindakan Anda sebagai approver')

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

    {{-- Info --}}
    <section style="margin-bottom:16px;">
        <div style="background: var(--info-subtle); border: 1px solid var(--info); border-radius: 8px; padding: 16px;">
            <div style="display:flex;gap:12px;align-items:flex-start;">
                <x-heroicon-o-information-circle style="width:24px;height:24px;color:var(--info);flex-shrink:0;" />
                <div>
                    <strong style="display:block;margin-bottom:4px;">Tanggung Jawab Anda</strong>
                    <p style="color:var(--text-muted);font-size:0.875rem;margin:0;">
                        Halaman ini menampilkan semua langkah approval (global / sarana / prasarana) yang menunggu keputusan Anda.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Data Table --}}
    <section class="data-table" aria-label="Tabel Peminjaman Menunggu Persetujuan">
        <div class="data-table__container">
            <table class="data-table__table">
                <x-table.head class="data-table__head">
                    <tr class="data-table__row">
                        <x-table.th class="data-table__cell">Nama Acara</x-table.th>
                        <x-table.th class="data-table__cell">Peminjam</x-table.th>
                        <x-table.th class="data-table__cell">Tipe Approval</x-table.th>
                        <x-table.th class="data-table__cell">Referensi</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Jadwal</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Dibuat</x-table.th>
                        <x-table.th align="center" class="data-table__cell data-table__cell--action">Aksi</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($pendingApprovals as $workflow)
                        <tr class="data-table__row">
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $workflow->peminjaman->event_name }}</strong>
                                    <small style="color: var(--text-muted);">
                                        {{ $workflow->peminjaman->user->name ?? '-' }}
                                    </small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                {{ $workflow->peminjaman->user->name ?? '-' }}
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                {{ $workflow->approval_type_label }}
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                @if($workflow->isSpecificSarana())
                                    {{ $workflow->sarana->nama ?? '-' }}
                                @elseif($workflow->isSpecificPrasarana())
                                    {{ $workflow->prasarana->name ?? '-' }}
                                @else
                                    -
                                @endif
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <div class="data-table__data">
                                    <span>{{ $workflow->peminjaman->start_date->format('d/m/Y') }}</span>
                                    <small style="color: var(--text-muted);">s/d {{ $workflow->peminjaman->end_date->format('d/m/Y') }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                {{ optional($workflow->created_at)->format('d/m/Y H:i') }}
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--action">
                                <div style="display:flex;gap:8px;justify-content:flex-end;">
                                    <a href="{{ route('peminjaman.show', $workflow->peminjaman) }}" style="text-decoration:none;" class="js-row-action">
                                        <x-button type="button" variant="secondary" size="sm">
                                            Detail
                                        </x-button>
                                    </a>

                                    <x-button
                                        type="button"
                                        variant="success"
                                        size="sm"
                                        class="js-row-action"
                                        onclick="openApprovalModal('{{ $workflow->id }}', 'approve')"
                                    >
                                        Setujui
                                    </x-button>

                                    <x-button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        class="js-row-action"
                                        onclick="openApprovalModal('{{ $workflow->id }}', 'reject')"
                                    >
                                        Tolak
                                    </x-button>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="7" class="data-table__cell" align="center" style="padding:40px;color:var(--text-muted);">
                                Tidak ada peminjaman yang menunggu persetujuan Anda.
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Modal Approval --}}
    <x-modal id="approvalActionModal" title="Proses Persetujuan" size="sm">
        <form id="approval-action-form" method="POST" style="display:flex;flex-direction:column;gap:1rem;">
            @csrf
            <input type="hidden" name="action" id="approval_action" value="approve">
            <input type="hidden" name="approval_type" id="approval_type" value="global">
            <input type="hidden" name="sarana_id" id="approval_sarana_id" value="">
            <input type="hidden" name="prasarana_id" id="approval_prasarana_id" value="">

            <p id="approval_action_text" style="font-size:0.9rem;color:var(--text-muted);"></p>

            <x-input.text
                label="Catatan / Alasan"
                name="reason"
                id="approval_reason"
                :value="old('reason')"
            />
        </form>

        <x-slot:footer>
            <x-button type="button" variant="secondary" data-modal-close>
                Batal
            </x-button>
            <x-button type="submit" variant="primary" form="approval-action-form">
                Proses
            </x-button>
        </x-slot:footer>
    </x-modal>

    <script>
    function openApprovalModal(workflowId, action) {
        var modal = document.getElementById('approvalActionModal');
        var form = document.getElementById('approval-action-form');
        var actionInput = document.getElementById('approval_action');
        var actionText = document.getElementById('approval_action_text');

        if (!modal || !form || !actionInput || !actionText) {
            return;
        }

        var baseUrl = document.body.getAttribute('data-base-url') || '';
        var actionUrlTemplate = baseUrl + '/peminjaman/__ID__/approval';

        actionInput.value = action === 'reject' ? 'reject' : 'approve';
        actionText.textContent = action === 'reject'
            ? 'Anda akan menolak peminjaman ini. Anda dapat menambahkan alasan penolakan di bawah.'
            : 'Anda akan menyetujui peminjaman ini. Anda dapat menambahkan catatan di bawah (opsional).';

        form.action = actionUrlTemplate.replace('__ID__', workflowId.split(':')[0]);

        modal.open();
    }
    </script>
</div>
@endsection
