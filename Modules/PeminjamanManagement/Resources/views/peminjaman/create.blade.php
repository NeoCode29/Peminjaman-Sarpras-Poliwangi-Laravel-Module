@extends('layouts.app')

@section('title', 'Buat Peminjaman')
@section('page-title', 'Buat Peminjaman Baru')
@section('page-subtitle', 'Ajukan peminjaman prasarana dan sarana')

@section('content')
<div class="page-content">
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

    <form action="{{ route('peminjaman.store') }}" method="POST" enctype="multipart/form-data" class="form-section">
        @csrf

        <x-form-group
            title="Informasi Kegiatan"
            description="Lengkapi informasi dasar tentang kegiatan yang akan diadakan."
            icon="heroicon-o-calendar"
        >
            <x-form-section>
                <div class="form-section__grid">
                    {{-- Jenis Peminjaman --}}
                    <div class="form-field form-field--full">
                        <div class="c-input">
                            <label class="c-input__label">Jenis Peminjaman <span style="color: var(--danger);">*</span></label>
                            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:8px;">
                                @php($oldLoanType = old('loan_type', 'prasarana'))
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                                    <input type="radio" name="loan_type" value="prasarana"
                                        {{ $oldLoanType === 'prasarana' ? 'checked' : '' }}
                                        onchange="toggleLoanTypeSections()"
                                        style="width:18px;height:18px;">
                                    <span>Prasarana saja</span>
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                                    <input type="radio" name="loan_type" value="sarana"
                                        {{ $oldLoanType === 'sarana' ? 'checked' : '' }}
                                        onchange="toggleLoanTypeSections()"
                                        style="width:18px;height:18px;">
                                    <span>Sarana saja</span>
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                                    <input type="radio" name="loan_type" value="both"
                                        {{ $oldLoanType === 'both' ? 'checked' : '' }}
                                        onchange="toggleLoanTypeSections()"
                                        style="width:18px;height:18px;">
                                    <span>Sarana &amp; Prasarana</span>
                                </label>
                            </div>
                            @error('loan_type')
                                <p class="c-input__helper is-invalid">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Nama Acara --}}
                    <div class="form-field form-field--full">
                        <x-input.text
                            label="Nama Acara"
                            name="event_name"
                            id="event_name"
                            :value="old('event_name')"
                            placeholder="Masukkan nama acara"
                            :required="true"
                            :error="$errors->first('event_name')"
                        />
                    </div>

                    {{-- UKM (hanya untuk mahasiswa) --}}
                    @if(auth()->user()->user_type === 'mahasiswa')
                        <div class="form-field form-field--full">
                            <x-input.select
                                label="UKM / Organisasi"
                                name="ukm_id"
                                id="ukm_id"
                                placeholder="Pilih UKM atau organisasi"
                                :required="true"
                                :error="$errors->first('ukm_id')"
                            >
                                @foreach($ukms as $ukm)
                                    <option value="{{ $ukm->id }}" {{ old('ukm_id') == $ukm->id ? 'selected' : '' }}>
                                        {{ $ukm->nama }}
                                    </option>
                                @endforeach
                            </x-input.select>
                        </div>
                    @endif

                    {{-- Tanggal Mulai & Selesai --}}
                    <x-input.text
                        label="Tanggal Mulai"
                        name="start_date"
                        id="start_date"
                        type="date"
                        :value="old('start_date')"
                        :required="true"
                        :error="$errors->first('start_date')"
                    />

                    <x-input.text
                        label="Tanggal Selesai"
                        name="end_date"
                        id="end_date"
                        type="date"
                        :value="old('end_date')"
                        :required="true"
                        :error="$errors->first('end_date')"
                    />

                    <x-input.text
                        label="Jam Mulai"
                        name="start_time"
                        id="start_time"
                        type="time"
                        :value="old('start_time')"
                        :required="true"
                        :error="$errors->first('start_time')"
                    />

                    <x-input.text
                        label="Jam Selesai"
                        name="end_time"
                        id="end_time"
                        type="time"
                        :value="old('end_time')"
                        :required="true"
                        :error="$errors->first('end_time')"
                    />

                    {{-- Jumlah Peserta --}}
                    <x-input.text
                        label="Jumlah Peserta"
                        name="jumlah_peserta"
                        id="jumlah_peserta"
                        type="number"
                        :value="old('jumlah_peserta')"
                        min="1"
                        placeholder="Perkiraan jumlah peserta"
                        :error="$errors->first('jumlah_peserta')"
                    />
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Section Prasarana --}}
        <div id="loan_prasarana_section">
            <x-form-group
                title="Lokasi Kegiatan"
                description="Pilih lokasi prasarana atau isi lokasi custom."
                icon="heroicon-o-map-pin"
            >
                <x-form-section>
                    <div class="form-section__grid">
                        <div class="form-field form-field--full">
                            <div class="c-input">
                                <label class="c-input__label">Jenis Lokasi <span style="color: var(--danger);">*</span></label>
                                <div style="display: flex; gap: 16px; margin-top: 8px;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="radio" name="jenis_lokasi" value="prasarana" id="jenis_lokasi_prasarana"
                                            {{ old('jenis_lokasi', 'prasarana') === 'prasarana' ? 'checked' : '' }}
                                            onchange="toggleLokasiInputs()"
                                            style="width: 18px; height: 18px;">
                                        <span>Prasarana Kampus</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="radio" name="jenis_lokasi" value="custom" id="jenis_lokasi_custom"
                                            {{ old('jenis_lokasi') === 'custom' ? 'checked' : '' }}
                                            onchange="toggleLokasiInputs()"
                                            style="width: 18px; height: 18px;">
                                        <span>Lokasi Lainnya</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Prasarana --}}
                        <div class="form-field form-field--full" id="prasarana_container" style="{{ old('jenis_lokasi', 'prasarana') === 'custom' ? 'display:none;' : '' }}">
                            <x-input.select
                                label="Prasarana"
                                name="prasarana_id"
                                id="prasarana_id"
                                placeholder="Pilih prasarana"
                                :error="$errors->first('prasarana_id')"
                            >
                                @foreach($prasarana as $item)
                                    <option value="{{ $item->id }}" {{ old('prasarana_id') == $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}{{ $item->lokasi ? ' - ' . $item->lokasi : '' }}
                                    </option>
                                @endforeach
                            </x-input.select>
                        </div>

                        {{-- Lokasi Custom --}}
                        <div class="form-field form-field--full" id="lokasi_custom_container" style="{{ old('jenis_lokasi') === 'custom' ? '' : 'display:none;' }}">
                            <x-input.text
                                label="Lokasi Custom"
                                name="lokasi_custom"
                                id="lokasi_custom"
                                :value="old('lokasi_custom')"
                                placeholder="Contoh: Lapangan Kota, Gedung Lain"
                                :error="$errors->first('lokasi_custom')"
                            />
                        </div>
                    </div>
                </x-form-section>
            </x-form-group>
        </div>

        {{-- Section Sarana --}}
        <div id="loan_sarana_section">
            <x-form-group
                title="Sarana yang Dipinjam"
                description="Pilih sarana dari dropdown untuk menambahkan ke daftar."
                icon="heroicon-o-cube"
            >
                <x-form-section>
                    <div class="form-section__grid">
                        <div class="form-field form-field--full">
                            {{-- Dropdown untuk memilih sarana --}}
                            <div class="c-input" style="margin-bottom: 16px;">
                                <label class="c-input__label">Pilih Sarana</label>
                                <div class="c-input__control">
                                    <select id="sarana-selector" class="c-input__element c-input__element--select" onchange="addSaranaFromSelect(this)">
                                        <option value="">-- Pilih sarana untuk ditambahkan --</option>
                                        @foreach($sarana as $s)
                                            <option value="{{ $s->id }}" data-nama="{{ $s->nama }}" data-kode="{{ $s->kode_sarana }}" data-stok="{{ $s->jumlah_tersedia ?? 0 }}">
                                                {{ $s->nama }} ({{ $s->kode_sarana }}) - Stok: {{ $s->jumlah_tersedia ?? 0 }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Tabel daftar sarana yang dipilih --}}
                            <div class="c-input">
                                <label class="c-input__label">Daftar Sarana Dipilih</label>
                                <div style="overflow-x: auto;">
                                    <table class="c-table" id="sarana-table" style="width: 100%; margin-top: 8px;">
                                        <thead class="c-table__head">
                                            <tr class="c-table__row">
                                                <th class="c-table__cell c-table__cell--head" style="width: 40%;">Nama Sarana</th>
                                                <th class="c-table__cell c-table__cell--head" style="width: 20%;">Kode</th>
                                                <th class="c-table__cell c-table__cell--head" style="width: 20%;">Jumlah</th>
                                                <th class="c-table__cell c-table__cell--head" style="width: 20%; text-align: center;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="c-table__body" id="sarana-table-body">
                                            {{-- Existing old values --}}
                                            @php($oldItems = old('sarana_items', []))
                                            @foreach($oldItems as $index => $oldItem)
                                                @php($saranaItem = $sarana->firstWhere('id', $oldItem['sarana_id'] ?? null))
                                                @continue(!$saranaItem)
                                                <tr class="c-table__row" data-sarana-id="{{ $oldItem['sarana_id'] }}">
                                                    <td class="c-table__cell">
                                                        {{ $saranaItem->nama }}
                                                        <input type="hidden" name="sarana_items[{{ $index }}][sarana_id]" value="{{ $oldItem['sarana_id'] }}">
                                                    </td>
                                                    <td class="c-table__cell">{{ $saranaItem->kode_sarana }}</td>
                                                    <td class="c-table__cell">
                                                        <input type="number" name="sarana_items[{{ $index }}][qty_requested]" 
                                                            value="{{ $oldItem['qty_requested'] ?? 1 }}" 
                                                            min="1" 
                                                            class="c-input__element" 
                                                            style="width: 80px; padding: 4px 8px;"
                                                            onchange="updateSaranaIndex()">
                                                    </td>
                                                    <td class="c-table__cell" style="text-align: center;">
                                                        <button type="button" class="c-button c-button--ghost c-button--danger" onclick="removeSaranaRow(this)" title="Hapus">
                                                            <x-heroicon-o-trash style="width:16px;height:16px;" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <p id="sarana-empty-message" class="c-input__helper" style="margin-top: 8px; {{ !empty($oldItems) ? 'display:none;' : '' }}">
                                    Belum ada sarana dipilih. Pilih dari dropdown di atas.
                                </p>
                                @error('sarana_items')
                                    <p class="c-input__helper is-invalid">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </x-form-section>
            </x-form-group>
        </div>

        {{-- Lampiran --}}
        <x-form-group
            title="Lampiran & Catatan"
            description="Unggah surat pengajuan dan catatan tambahan."
            icon="heroicon-o-document-text"
        >
            <x-form-section>
                <div class="form-section__grid">
                    <div class="form-field">
                        <x-input.file
                            label="Surat Pengajuan (PDF / Gambar)"
                            name="surat"
                            id="surat"
                            accept=".pdf,image/*"
                            :required="true"
                            :helper="'Wajib diunggah. Maksimal 5MB.'"
                        />
                        @error('surat')
                            <p class="c-input__helper is-invalid">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Info Kuota --}}
        <div style="background: var(--info-subtle); border: 1px solid var(--info); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <x-heroicon-o-information-circle style="width: 24px; height: 24px; color: var(--info); flex-shrink: 0;" />
                <div>
                    <strong style="display: block; margin-bottom: 4px;">Informasi Kuota Peminjaman</strong>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                        Saat ini Anda memiliki <strong>{{ $currentBorrowings ?? 0 }}</strong> peminjaman aktif dari maksimal
                        <strong>{{ $maxActiveBorrowings ?? 3 }}</strong>.
                    </p>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="form-section__actions form-section__actions--footer">
            <a href="{{ route('peminjaman.index') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Batal
                </x-button>
            </a>
            <x-button type="submit" variant="primary" icon="heroicon-o-check-circle">
                Ajukan Peminjaman
            </x-button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleLokasiInputs() {
    var jenis = document.querySelector('input[name="jenis_lokasi"]:checked');
    if (!jenis) return;

    var prasaranaContainer = document.getElementById('prasarana_container');
    var lokasiCustomContainer = document.getElementById('lokasi_custom_container');

    if (jenis.value === 'prasarana') {
        prasaranaContainer.style.display = '';
        lokasiCustomContainer.style.display = 'none';
    } else {
        prasaranaContainer.style.display = 'none';
        lokasiCustomContainer.style.display = '';
    }
}

function getLoanType() {
    var checked = document.querySelector('input[name="loan_type"]:checked');
    return checked ? checked.value : null;
}

function toggleLoanTypeSections() {
    var type = getLoanType();
    var prasaranaSection = document.getElementById('loan_prasarana_section');
    var saranaSection = document.getElementById('loan_sarana_section');

    if (!prasaranaSection || !saranaSection) return;

    if (type === 'prasarana' || type === 'both') {
        prasaranaSection.style.display = '';
    } else {
        prasaranaSection.style.display = 'none';
    }

    if (type === 'sarana' || type === 'both') {
        saranaSection.style.display = '';
    } else {
        saranaSection.style.display = 'none';
    }
}

function addSaranaFromSelect(selectEl) {
    var selectedOption = selectEl.options[selectEl.selectedIndex];
    if (!selectedOption || !selectedOption.value) return;

    var saranaId = selectedOption.value;
    var nama = selectedOption.getAttribute('data-nama');
    var kode = selectedOption.getAttribute('data-kode');
    var stok = parseInt(selectedOption.getAttribute('data-stok') || '0', 10);

    if (isNaN(stok) || stok <= 0) {
        alert('Stok sarana ini kosong, tidak dapat dipinjam.');
        selectEl.value = '';
        return;
    }

    // Cek apakah sudah ada di tabel
    var tableBody = document.getElementById('sarana-table-body');
    var existingRow = tableBody.querySelector('tr[data-sarana-id="' + saranaId + '"]');
    if (existingRow) {
        alert('Sarana ini sudah ada di daftar!');
        selectEl.value = '';
        return;
    }

    // Hitung index baru
    var index = tableBody.querySelectorAll('tr').length;

    // Buat row baru
    var tr = document.createElement('tr');
    tr.className = 'c-table__row';
    tr.setAttribute('data-sarana-id', saranaId);
    tr.innerHTML = 
        '<td class="c-table__cell">' +
            nama +
            '<input type="hidden" name="sarana_items[' + index + '][sarana_id]" value="' + saranaId + '">' +
        '</td>' +
        '<td class="c-table__cell">' + kode + '</td>' +
        '<td class="c-table__cell">' +
            '<input type="number" name="sarana_items[' + index + '][qty_requested]" value="1" min="1" class="c-input__element" style="width: 80px; padding: 4px 8px;" onchange="updateSaranaIndex()">' +
        '</td>' +
        '<td class="c-table__cell" style="text-align: center;">' +
            '<button type="button" class="c-button c-button--ghost c-button--danger" onclick="removeSaranaRow(this)" title="Hapus">' +
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>' +
            '</button>' +
        '</td>';

    tableBody.appendChild(tr);

    // Reset select
    selectEl.value = '';

    // Hide empty message
    document.getElementById('sarana-empty-message').style.display = 'none';

    // Update index
    updateSaranaIndex();
}

function removeSaranaRow(button) {
    var row = button.closest('tr');
    if (!row) return;

    row.remove();

    // Update index
    updateSaranaIndex();

    // Show empty message if no rows
    var tableBody = document.getElementById('sarana-table-body');
    if (tableBody.querySelectorAll('tr').length === 0) {
        document.getElementById('sarana-empty-message').style.display = '';
    }
}

function updateSaranaIndex() {
    var tableBody = document.getElementById('sarana-table-body');
    var rows = tableBody.querySelectorAll('tr');
    rows.forEach(function(row, index) {
        var hiddenInput = row.querySelector('input[type="hidden"]');
        var qtyInput = row.querySelector('input[type="number"]');
        if (hiddenInput) {
            hiddenInput.name = 'sarana_items[' + index + '][sarana_id]';
        }
        if (qtyInput) {
            qtyInput.name = 'sarana_items[' + index + '][qty_requested]';
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    toggleLokasiInputs();
    toggleLoanTypeSections();
    updateSaranaIndex();
});
</script>
@endpush
