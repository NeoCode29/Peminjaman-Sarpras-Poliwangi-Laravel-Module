@extends('layouts.app')

@section('title', 'Detail Sarana')
@section('page-title', 'Detail Sarana')
@section('page-subtitle', 'Informasi lengkap sarana dan status ketersediaannya')

@section('content')
<div class="page-content">
    {{-- Toast Notifications --}}
    @if(session('success'))
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="success" title="Berhasil" :duration="2500">
                {{ session('success') }}
            </x-toast>
        </div>
    @endif

    @if(session('sarpras_success'))
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="success" title="Berhasil" :duration="2500">
                {{ session('sarpras_success') }}
            </x-toast>
        </div>
    @endif

    @if($errors->any())
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="danger" title="Terjadi Kesalahan" :duration="4000">
                {{ $errors->first() }}
            </x-toast>
        </div>
    @endif

    <x-detail-section
        :title="$sarana->nama"
        :description="'Kode: ' . $sarana->kode_sarana . ' · Kategori: ' . ($sarana->kategori->nama ?? '-')"
    >
        <x-detail-list :columns="2" variant="bordered">
            <x-detail-item label="Kode Sarana">
                {{ $sarana->kode_sarana }}
            </x-detail-item>

            <x-detail-item label="Kategori">
                {{ $sarana->kategori->nama ?? '-' }}
            </x-detail-item>

            <x-detail-item label="Merk">
                {{ $sarana->merk ?? '-' }}
            </x-detail-item>

            <x-detail-item label="Kondisi">
                <x-badge variant="default" size="sm">
                    {{ ucfirst(str_replace('_', ' ', $sarana->kondisi)) }}
                </x-badge>
            </x-detail-item>

            <x-detail-item label="Status Ketersediaan">
                @php
                    $statusVariant = $sarana->status_ketersediaan === 'tersedia' ? 'success'
                        : ($sarana->status_ketersediaan === 'dipinjam' ? 'warning'
                        : ($sarana->status_ketersediaan === 'dalam_perbaikan' ? 'primary' : 'default'));
                @endphp
                <x-badge :variant="$statusVariant" size="sm">
                    {{ ucfirst(str_replace('_', ' ', $sarana->status_ketersediaan)) }}
                </x-badge>
            </x-detail-item>

            <x-detail-item label="Jumlah Total">
                {{ $sarana->jumlah_total }}
            </x-detail-item>

            <x-detail-item label="Unit Tersedia">
                {{ $sarana->jumlah_tersedia }}
            </x-detail-item>

            <x-detail-item label="Tahun Pembelian">
                {{ $sarana->tahun_perolehan ?? '-' }}
            </x-detail-item>

            <x-detail-item label="Harga Beli">
                {{ $sarana->nilai_perolehan ? 'Rp ' . number_format($sarana->nilai_perolehan, 0, ',', '.') : '-' }}
            </x-detail-item>

            <x-detail-item label="Lokasi Penyimpanan" :full="true">
                {{ $sarana->lokasi_penyimpanan ?? '-' }}
            </x-detail-item>

            <x-detail-item label="Keterangan" :full="true">
                {{ $sarana->keterangan ?? 'Tidak ada keterangan tambahan.' }}
            </x-detail-item>
        </x-detail-list>

        {{-- Panel Foto --}}
        @if($sarana->foto_url)
            <div style="margin-top: 1.5rem; display:flex; flex-wrap:wrap; gap:1rem;">
                <div style="flex:1; min-width:220px;">
                    <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px;background:#ffffff;">
                        <span class="c-input__label" style="font-size:0.75rem;">Foto</span>
                        <div style="margin-top:8px;">
                            <img src="{{ $sarana->foto_url }}" alt="{{ $sarana->nama }}" style="width:100%;max-height:220px;object-fit:contain;border-radius:6px;">
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div style="margin-top: 1.5rem; display:flex; flex-wrap:wrap; gap:0.75rem;">
            <a href="{{ route('sarana.index') }}" style="text-decoration:none;">
                <x-button type="button" variant="secondary">
                    Kembali ke Daftar
                </x-button>
            </a>
            <a href="{{ route('sarana.edit', $sarana) }}" style="text-decoration:none;">
                <x-button type="button" variant="primary">
                    Edit Sarana
                </x-button>
            </a>

            @if($sarana->type === 'serialized')
                @can('manageUnits', $sarana)
                    <a href="{{ route('sarana.units.index', $sarana) }}" style="text-decoration:none;">
                        <x-button type="button" variant="secondary">
                            Kelola Unit
                        </x-button>
                    </a>
                @endcan
            @endif
        </div>
    </x-detail-section>

    @can('viewAny', [\Modules\SaranaManagement\Entities\SaranaApprover::class, $sarana])
        <x-detail-section
            title="Approver Sarana"
            description="Kelola daftar approver khusus untuk sarana ini."
        >
            @can('create', [\Modules\SaranaManagement\Entities\SaranaApprover::class, $sarana])
                <x-slot:headerActions>
                    <x-button
                        type="button"
                        variant="primary"
                        onclick="document.getElementById('addSaranaApproverModal').open()"
                    >
                        Tambah Approver
                    </x-button>
                </x-slot:headerActions>
            @endcan

            <div style="display:flex; flex-wrap:wrap; gap:1.5rem; align-items:flex-start;">
                {{-- Daftar Approver --}}
                <div style="flex:2; min-width:260px;">
                    @if($approvers->isEmpty())
                        <p style="font-size:0.85rem;color:#6b7280;">Belum ada approver yang terdaftar.</p>
                    @else
                        <x-table :striped="true" :hoverable="true">
                            <x-table.head>
                                <x-table.row>
                                    <x-table.th align="left">User</x-table.th>
                                    <x-table.th align="left">Email</x-table.th>
                                    <x-table.th align="center">Level</x-table.th>
                                    <x-table.th align="center">Status</x-table.th>
                                    <x-table.th align="center">Aksi</x-table.th>
                                </x-table.row>
                            </x-table.head>

                            <x-table.body>
                                @foreach($approvers as $approver)
                                    <x-table.row>
                                        <x-table.td>
                                            {{ $approver->approver->name ?? '-' }}
                                        </x-table.td>
                                        <x-table.td>
                                            {{ $approver->approver->email ?? '-' }}
                                        </x-table.td>
                                        <x-table.td align="center">
                                            {{ $approver->approval_level }}
                                        </x-table.td>
                                        <x-table.td align="center">
                                            @if($approver->is_active)
                                                <x-badge variant="success" size="sm">Aktif</x-badge>
                                            @else
                                                <x-badge variant="danger" size="sm">Nonaktif</x-badge>
                                            @endif
                                        </x-table.td>
                                        <x-table.td align="center">
                                            @can('update', $approver)
                                                <x-button
                                                    type="button"
                                                    size="sm"
                                                    variant="secondary"
                                                    onclick="document.getElementById('editSaranaApproverModal-{{ $approver->id }}').open()"
                                                    style="margin-right:0.25rem;"
                                                >
                                                    Edit
                                                </x-button>
                                            @endcan
                                            @can('delete', $approver)
                                                <x-button
                                                    type="button"
                                                    size="sm"
                                                    variant="danger"
                                                    onclick="document.getElementById('deleteSaranaApproverModal-{{ $approver->id }}').open()"
                                                >
                                                    Hapus
                                                </x-button>
                                            @endcan
                                        </x-table.td>
                                    </x-table.row>
                                @endforeach
                            </x-table.body>
                        </x-table>

                        <div class="data-table__pagination" style="margin-top:0.75rem;">
                            @if(method_exists($approvers, 'links'))
                                {{ $approvers->withQueryString()->links('components.pagination') }}
                            @endif
                        </div>
                    @endif
                </div>

            </div>
        </x-detail-section>
    @endcan
</div>
@endsection

@foreach($approvers as $approver)
    @can('delete', $approver)
        <x-modal id="deleteSaranaApproverModal-{{ $approver->id }}" title="Hapus Approver" size="sm">
            <form
                id="delete-sarana-approver-form-{{ $approver->id }}"
                action="{{ route('sarana.approvers.destroy', [$sarana, $approver]) }}"
                method="POST"
                style="display:flex;flex-direction:column;gap:1rem;"
            >
                @csrf
                @method('DELETE')

                <p style="font-size:0.9rem;color:var(--text-muted);">
                    Yakin ingin menghapus approver <strong>{{ $approver->approver->name ?? '-' }}</strong> untuk sarana ini?
                </p>
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button
                    type="submit"
                    variant="danger"
                    form="delete-sarana-approver-form-{{ $approver->id }}"
                >
                    Hapus
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan
@endforeach

@can('create', [\Modules\SaranaManagement\Entities\SaranaApprover::class, $sarana])
    <x-modal id="addSaranaApproverModal" title="Tambah Approver Sarana" size="sm">
        <form
            id="add-sarana-approver-form"
            method="POST"
            action="{{ route('sarana.approvers.store', $sarana) }}"
            style="display:flex; flex-direction:column; gap:0.75rem;"
        >
            @csrf

            <x-input.select
                name="approver_id"
                label="User"
                :required="true"
                placeholder="Pilih user approver"
            >
                @foreach($availableApprovers as $user)
                    <option value="{{ $user->id }}">
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </x-input.select>

            <x-input.text
                type="number"
                name="approval_level"
                label="Level Approval"
                value="1"
                min="1"
                max="10"
                :required="true"
            />

            <x-input.checkbox
                name="is_active"
                label="Aktif"
                :checked="true"
            />
        </form>

        <x-slot:footer>
            <x-button type="button" variant="secondary" data-modal-close>
                Batal
            </x-button>
            <x-button
                type="submit"
                variant="primary"
                form="add-sarana-approver-form"
            >
                Simpan Approver
            </x-button>
        </x-slot:footer>
    </x-modal>
@endcan

@foreach($sarana->approvers as $approver)
    @can('update', $approver)
        <x-modal id="editSaranaApproverModal-{{ $approver->id }}" title="Edit Approver Sarana" size="sm">
            <form
                id="edit-sarana-approver-form-{{ $approver->id }}"
                method="POST"
                action="{{ route('sarana.approvers.update', [$sarana, $approver]) }}"
                style="display:flex; flex-direction:column; gap:0.75rem;"
            >
                @csrf
                @method('PUT')

                <x-input.text
                    type="number"
                    name="approval_level"
                    label="Level Approval"
                    :value="$approver->approval_level"
                    min="1"
                    max="10"
                    :required="true"
                />

                <x-input.checkbox
                    name="is_active"
                    label="Aktif"
                    :checked="$approver->is_active"
                />
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button
                    type="submit"
                    variant="primary"
                    form="edit-sarana-approver-form-{{ $approver->id }}"
                >
                    Simpan Perubahan
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan
@endforeach

