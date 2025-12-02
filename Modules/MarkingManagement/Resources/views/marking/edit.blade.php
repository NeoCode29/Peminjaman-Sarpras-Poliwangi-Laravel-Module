@extends('layouts.app')

@section('title', 'Edit Marking')
@section('page-title', 'Edit Marking')
@section('page-subtitle', 'Perbarui informasi marking')

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

    <form action="{{ route('marking.update', $marking) }}" method="POST" class="form-section">
        @csrf
        @method('PUT')

        <x-form-group
            title="Informasi Acara"
            description="Perbarui informasi dasar tentang acara."
            icon="heroicon-o-calendar"
        >
            <x-form-section>
                <div class="form-section__grid">
                    {{-- Nama Acara --}}
                    <div class="form-field form-field--full">
                        <x-input.text
                            label="Nama Acara"
                            name="event_name"
                            id="event_name"
                            :value="old('event_name', $marking->event_name)"
                            placeholder="Masukkan nama acara"
                            :required="true"
                            :error="$errors->first('event_name')"
                        />
                    </div>

                    {{-- UKM (hanya untuk mahasiswa) --}}
                    @if(auth()->user()->isStudent())
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
                                    <option value="{{ $ukm->id }}" {{ old('ukm_id', $marking->ukm_id) == $ukm->id ? 'selected' : '' }}>
                                        {{ $ukm->nama }}
                                    </option>
                                @endforeach
                            </x-input.select>
                        </div>
                    @endif

                    {{-- Waktu Mulai & Selesai --}}
                    <x-input.text
                        label="Waktu Mulai"
                        name="start_datetime"
                        id="start_datetime"
                        type="datetime-local"
                        :value="old('start_datetime', $marking->start_datetime->format('Y-m-d\TH:i'))"
                        :required="true"
                        :error="$errors->first('start_datetime')"
                    />

                    <x-input.text
                        label="Waktu Selesai"
                        name="end_datetime"
                        id="end_datetime"
                        type="datetime-local"
                        :value="old('end_datetime', $marking->end_datetime->format('Y-m-d\TH:i'))"
                        :required="true"
                        :error="$errors->first('end_datetime')"
                    />

                    {{-- Jumlah Peserta --}}
                    <x-input.text
                        label="Jumlah Peserta (Opsional)"
                        name="jumlah_peserta"
                        id="jumlah_peserta"
                        type="number"
                        :value="old('jumlah_peserta', $marking->jumlah_peserta)"
                        min="1"
                        placeholder="Perkiraan jumlah peserta"
                        :error="$errors->first('jumlah_peserta')"
                    />

                    {{-- Rencana Submit --}}
                    <x-input.text
                        label="Rencana Submit Pengajuan (Opsional)"
                        name="planned_submit_by"
                        id="planned_submit_by"
                        type="datetime-local"
                        :value="old('planned_submit_by', $marking->planned_submit_by?->format('Y-m-d\TH:i'))"
                        :error="$errors->first('planned_submit_by')"
                    />
                </div>
            </x-form-section>
        </x-form-group>

        @php
            $jenisLokasi = old('jenis_lokasi', $marking->lokasi_custom ? 'lainnya' : 'poliwangi');
        @endphp
        <x-form-group
            title="Lokasi Acara"
            description="Pilih lokasi untuk acara yang akan diadakan."
            icon="heroicon-o-map-pin"
        >
            <x-form-section>
                <div class="form-section__grid">
                    {{-- Jenis Lokasi --}}
                    <div class="form-field form-field--full">
                        <div class="c-input">
                            <label class="c-input__label">Jenis Lokasi <span style="color: var(--danger);">*</span></label>
                            <div style="display: flex; gap: 16px; margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input 
                                        type="radio" 
                                        name="jenis_lokasi" 
                                        value="poliwangi" 
                                        id="lokasi_poliwangi"
                                        {{ $jenisLokasi == 'poliwangi' ? 'checked' : '' }}
                                        onchange="toggleLokasiInput()"
                                        style="width: 18px; height: 18px;"
                                    >
                                    <span>Prasarana Poliwangi</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input 
                                        type="radio" 
                                        name="jenis_lokasi" 
                                        value="lainnya" 
                                        id="lokasi_lainnya"
                                        {{ $jenisLokasi == 'lainnya' ? 'checked' : '' }}
                                        onchange="toggleLokasiInput()"
                                        style="width: 18px; height: 18px;"
                                    >
                                    <span>Lokasi Lainnya</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Prasarana Poliwangi --}}
                    <div class="form-field form-field--full" id="prasarana_container" style="{{ $jenisLokasi == 'lainnya' ? 'display: none;' : '' }}">
                        <x-input.select
                            label="Prasarana Poliwangi"
                            name="prasarana_id"
                            id="prasarana_id"
                            placeholder="Pilih prasarana"
                            :error="$errors->first('prasarana_id')"
                        >
                            @foreach($prasaranas as $prasarana)
                                <option value="{{ $prasarana->id }}" {{ old('prasarana_id', $marking->prasarana_id) == $prasarana->id ? 'selected' : '' }}>
                                    {{ $prasarana->name }} - {{ $prasarana->lokasi ?? 'Lokasi tidak tersedia' }}
                                </option>
                            @endforeach
                        </x-input.select>
                    </div>

                    {{-- Lokasi Lainnya --}}
                    <div class="form-field form-field--full" id="lokasi_lainnya_container" style="{{ $jenisLokasi != 'lainnya' ? 'display: none;' : '' }}">
                        <x-input.text
                            label="Nama Lokasi"
                            name="lokasi_custom"
                            id="lokasi_custom"
                            :value="old('lokasi_custom', $marking->lokasi_custom)"
                            placeholder="Masukkan nama lokasi (contoh: Aula Gedung A, Lapangan Terbuka)"
                            :error="$errors->first('lokasi_custom')"
                        />
                    </div>
                </div>
            </x-form-section>
        </x-form-group>

        <x-form-group
            title="Catatan Tambahan"
            description="Informasi tambahan tentang marking ini."
            icon="heroicon-o-document-text"
        >
            <x-form-section>
                <div class="form-section__grid">
                    <div class="form-field form-field--full">
                        <div class="c-input">
                            <label for="notes" class="c-input__label">Catatan</label>
                            <div class="c-input__control">
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="3"
                                    class="c-input__element"
                                    placeholder="Catatan tambahan tentang marking ini (opsional)"
                                >{{ old('notes', $marking->notes) }}</textarea>
                            </div>
                            @error('notes')
                                <p class="c-input__helper is-invalid">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Info Box --}}
        <div style="background: var(--warning-subtle); border: 1px solid var(--warning); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <x-heroicon-o-clock style="width: 24px; height: 24px; color: var(--warning); flex-shrink: 0;" />
                <div>
                    <strong style="display: block; margin-bottom: 4px;">Masa Berlaku Marking</strong>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                        Marking ini akan kadaluarsa pada <strong>{{ $marking->expires_at->format('d/m/Y H:i') }}</strong>
                        ({{ $marking->getHoursUntilExpiration() }} jam lagi).
                    </p>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="form-section__actions form-section__actions--footer">
            <a href="{{ route('marking.show', $marking) }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Batal
                </x-button>
            </a>
            <x-button type="submit" variant="primary" icon="heroicon-o-check-circle">
                Simpan Perubahan
            </x-button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleLokasiInput() {
    const jenisLokasi = document.querySelector('input[name="jenis_lokasi"]:checked').value;
    const prasaranaContainer = document.getElementById('prasarana_container');
    const lokasiLainnyaContainer = document.getElementById('lokasi_lainnya_container');
    const prasaranaSelect = document.getElementById('prasarana_id');
    const lokasiCustomInput = document.getElementById('lokasi_custom');

    if (jenisLokasi === 'poliwangi') {
        prasaranaContainer.style.display = '';
        lokasiLainnyaContainer.style.display = 'none';
        // Clear lokasi custom when switching to prasarana
        lokasiCustomInput.value = '';
    } else {
        prasaranaContainer.style.display = 'none';
        lokasiLainnyaContainer.style.display = '';
        // Clear prasarana when switching to lokasi lainnya
        prasaranaSelect.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleLokasiInput();
});
</script>
@endpush
