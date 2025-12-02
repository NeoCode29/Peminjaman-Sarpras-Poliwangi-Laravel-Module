@extends('layouts.app')

@section('title', 'Kelola Unit Sarana')
@section('page-title', 'Kelola Unit Sarana')
@section('page-subtitle', 'Manajemen unit serialized untuk sarana ini')

@section('content')
<div class="page-content">
    @if(session('success'))
        <div style="margin-bottom: 1rem;">
            <x-alert type="success">
                {{ session('success') }}
            </x-alert>
        </div>
    @endif

    @if($errors->any())
        <div style="margin-bottom: 1rem;">
            <x-alert type="danger">
                {{ $errors->first() }}
            </x-alert>
        </div>
    @endif

    {{-- Info ringkas sarana --}}
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

            <x-detail-item label="Tipe">
                <x-badge variant="default" size="sm">
                    {{ ucfirst($sarana->type) }}
                </x-badge>
            </x-detail-item>

            <x-detail-item label="Jumlah Total">
                {{ $sarana->jumlah_total }}
            </x-detail-item>

            <x-detail-item label="Unit Tersedia">
                {{ $sarana->jumlah_tersedia }}
            </x-detail-item>

            <x-detail-item label="Remaining Units">
                {{ $sarana->remaining_units }}
            </x-detail-item>
        </x-detail-list>
    </x-detail-section>

    {{-- Modal edit unit --}}
    @foreach($sarana->units as $unit)
        <x-modal id="editUnitModal-{{ $unit->id }}" :title="'Edit Unit: ' . $unit->unit_code" size="sm">
            <form
                id="edit-unit-form-{{ $unit->id }}"
                method="POST"
                action="{{ route('sarana.units.update', [$sarana, $unit]) }}"
                style="display:flex;flex-direction:column;gap:1rem;"
            >
                @csrf
                @method('PUT')

                <x-input.text
                    label="Kode Unit"
                    name="unit_code"
                    id="modal_unit_code_{{ $unit->id }}"
                    :value="$unit->unit_code"
                    required
                />

                <x-input.select
                    label="Status Unit"
                    name="unit_status"
                    id="modal_unit_status_{{ $unit->id }}"
                    required
                >
                    <option value="tersedia" {{ $unit->unit_status === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                    <option value="rusak" {{ $unit->unit_status === 'rusak' ? 'selected' : '' }}>Rusak</option>
                    <option value="maintenance" {{ $unit->unit_status === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="hilang" {{ $unit->unit_status === 'hilang' ? 'selected' : '' }}>Hilang</option>
                </x-input.select>
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button
                    type="submit"
                    variant="primary"
                    form="edit-unit-form-{{ $unit->id }}"
                >
                    Simpan Perubahan
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endforeach

    {{-- Form tambah unit --}}
    <x-detail-section
        title="Tambah Unit"
        description="Tambahkan unit baru untuk sarana ini."
    >
        <form method="POST" action="{{ route('sarana.units.store', $sarana) }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:flex-end;">
            @csrf
            <div>
                <x-input.text
                    label="Kode Unit"
                    name="unit_code"
                    id="unit_code"
                    placeholder="Misal: LPT-001"
                    required
                />
            </div>
            <div>
                <x-input.select
                    label="Status Unit"
                    name="unit_status"
                    id="unit_status"
                >
                    <option value="tersedia">Tersedia</option>
                    <option value="rusak">Rusak</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="hilang">Hilang</option>
                </x-input.select>
            </div>
            <div>
                <x-button type="submit" variant="primary" style="width:100%;">
                    Tambah Unit
                </x-button>
            </div>
        </form>
    </x-detail-section>

    {{-- Tabel unit --}}
    <x-detail-section
        title="Daftar Unit"
        :description="'Total unit tercatat: ' . $sarana->units->count()"
    >
                @if($sarana->units->isEmpty())
                    <x-empty-state
                        title="Belum ada unit"
                        description="Tambahkan unit sarana untuk memanfaatkan mode serialized."
                    />
                @else
                    <div class="data-table__container">
                        <table class="data-table__table">
                            <x-table.head class="data-table__head">
                                <tr class="data-table__row">
                                    <x-table.th class="data-table__cell">Kode Unit</x-table.th>
                                    <x-table.th class="data-table__cell">Status</x-table.th>
                                    <x-table.th class="data-table__cell data-table__cell--action">Aksi</x-table.th>
                                </tr>
                            </x-table.head>
                            <x-table.body class="data-table__body">
                                @foreach($sarana->units as $unit)
                                    <tr class="data-table__row">
                                        <x-table.td class="data-table__cell">
                                            {{ $unit->unit_code }}
                                        </x-table.td>
                                        <x-table.td class="data-table__cell">
                                            @php
                                                $statusVariant = $unit->unit_status === 'tersedia' ? 'success'
                                                    : ($unit->unit_status === 'rusak' ? 'danger'
                                                    : ($unit->unit_status === 'maintenance' ? 'warning' : 'default'));
                                            @endphp
                                            <x-badge :variant="$statusVariant" size="sm">
                                                {{ ucfirst($unit->unit_status) }}
                                            </x-badge>
                                        </x-table.td>
                                        <x-table.td class="data-table__cell data-table__cell--action" align="right">
                                            <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;">
                                                {{-- Edit via modal --}}
                                                <x-button
                                                    type="button"
                                                    size="sm"
                                                    variant="secondary"
                                                    onclick="document.getElementById('editUnitModal-{{ $unit->id }}').open()"
                                                >
                                                    Edit
                                                </x-button>

                                                <x-button
                                                    type="button"
                                                    size="sm"
                                                    variant="danger"
                                                    onclick="document.getElementById('deleteUnitModal-{{ $unit->id }}').open()"
                                                >
                                                    Hapus
                                                </x-button>
                                            </div>
                                        </x-table.td>
                                    </tr>
                                    <x-modal id="deleteUnitModal-{{ $unit->id }}" title="Hapus Unit" size="sm">
                                        <form
                                            id="delete-unit-form-{{ $unit->id }}"
                                            method="POST"
                                            action="{{ route('sarana.units.destroy', [$sarana, $unit]) }}"
                                            style="display:flex;flex-direction:column;gap:1rem;"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <p style="font-size:0.9rem;color:var(--text-muted);">
                                                Yakin ingin menghapus unit <strong>{{ $unit->unit_code }}</strong>? Tindakan ini tidak dapat dibatalkan.
                                            </p>
                                        </form>

                                        <x-slot:footer>
                                            <x-button type="button" variant="secondary" data-modal-close>
                                                Batal
                                            </x-button>
                                            <x-button
                                                type="submit"
                                                variant="danger"
                                                form="delete-unit-form-{{ $unit->id }}"
                                            >
                                                Hapus
                                            </x-button>
                                        </x-slot:footer>
                                    </x-modal>
                                @endforeach
                            </x-table.body>
                        </table>
                    </div>
                @endif
    </x-detail-section>

    {{-- Aksi navigasi --}}
    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
        <a href="{{ route('sarana.show', $sarana) }}" style="text-decoration:none;">
            <x-button type="button" variant="secondary">
                Kembali ke Detail Sarana
            </x-button>
        </a>
        <a href="{{ route('sarana.index') }}" style="text-decoration:none;">
            <x-button type="button" variant="secondary">
                Kembali ke Daftar Sarana
            </x-button>
        </a>
    </div>
</div>
@endsection
