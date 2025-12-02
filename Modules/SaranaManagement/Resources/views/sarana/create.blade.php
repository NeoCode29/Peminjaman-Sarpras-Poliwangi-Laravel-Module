@extends('layouts.app')

@section('title', 'Tambah Sarana')
@section('page-title', 'Tambah Sarana Baru')
@section('page-subtitle', 'Lengkapi informasi sarana yang akan dikelola')

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

    @if($errors->any())
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="danger" title="Terjadi Kesalahan" :duration="7000">
                {{ $errors->first() }}
            </x-toast>
        </div>
    @endif

    <form action="{{ route('sarana.store') }}" method="POST" enctype="multipart/form-data" class="form-section">
        @csrf

        <x-form-group
            title="Informasi Sarana"
            description="Lengkapi data dasar untuk sarana baru."
            icon="heroicon-o-cube"
        >
            <x-form-section>
                <div class="form-section__grid">
                    {{-- Kode & Nama --}}
                    <x-input.text
                        label="Kode Sarana (Opsional)"
                        name="kode_sarana"
                        id="kode_sarana"
                        :value="old('kode_sarana')"
                        placeholder="Kosongkan untuk generate otomatis"
                        :error="$errors->first('kode_sarana')"
                    />

                    <x-input.text
                        label="Nama Sarana"
                        name="nama"
                        id="nama"
                        :value="old('nama')"
                        placeholder="Masukkan nama sarana"
                        :required="true"
                        :error="$errors->first('nama')"
                    />

                    {{-- Kategori & Merk --}}
                    <x-input.select
                        label="Kategori"
                        name="kategori_id"
                        id="kategori_id"
                        placeholder="Pilih kategori"
                        :required="true"
                        :error="$errors->first('kategori_id')"
                    >
                        @foreach($kategoris as $kategori)
                            <option value="{{ $kategori->id }}" {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                {{ $kategori->nama }}
                            </option>
                        @endforeach
                    </x-input.select>

                    <x-input.text
                        label="Merk"
                        name="merk"
                        id="merk"
                        :value="old('merk')"
                        placeholder="Merk sarana (opsional)"
                        :error="$errors->first('merk')"
                    />

                    {{-- Spesifikasi (full width) --}}
                    <div class="form-field form-field--full">
                        <div class="c-input">
                            <label for="spesifikasi" class="c-input__label">Spesifikasi</label>
                            <div class="c-input__control">
                                <textarea
                                    id="spesifikasi"
                                    name="spesifikasi"
                                    rows="3"
                                    class="c-input__element"
                                    placeholder="Tulis spesifikasi teknis atau detail penting lain"
                                >{{ old('spesifikasi') }}</textarea>
                            </div>
                            @error('spesifikasi')
                                <p class="c-input__helper is-invalid">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Tipe, Kondisi, Status, Jumlah --}}
                    <x-input.select
                        label="Tipe Sarana"
                        name="type"
                        id="type"
                        placeholder="Pilih tipe sarana"
                        :required="true"
                        :error="$errors->first('type')"
                    >
                        @php($oldType = old('type', 'pooled'))
                        <option value="pooled" {{ $oldType === 'pooled' ? 'selected' : '' }}>Pooled (jumlah per qty)</option>
                        <option value="serialized" {{ $oldType === 'serialized' ? 'selected' : '' }}>Serialized (per unit individual)</option>
                    </x-input.select>

                    <x-input.select
                        label="Kondisi"
                        name="kondisi"
                        id="kondisi"
                        placeholder="Pilih kondisi"
                        :required="true"
                        :error="$errors->first('kondisi')"
                    >
                        @php($oldKondisi = old('kondisi', 'baik'))
                        <option value="baik" {{ $oldKondisi === 'baik' ? 'selected' : '' }}>Baik</option>
                        <option value="rusak_ringan" {{ $oldKondisi === 'rusak_ringan' ? 'selected' : '' }}>Rusak Ringan</option>
                        <option value="rusak_berat" {{ $oldKondisi === 'rusak_berat' ? 'selected' : '' }}>Rusak Berat</option>
                        <option value="dalam_perbaikan" {{ $oldKondisi === 'dalam_perbaikan' ? 'selected' : '' }}>Dalam Perbaikan</option>
                    </x-input.select>

                    <x-input.select
                        label="Status Ketersediaan"
                        name="status_ketersediaan"
                        id="status_ketersediaan"
                        placeholder="Pilih status"
                        :required="true"
                        :error="$errors->first('status_ketersediaan')"
                    >
                        @php($oldStatus = old('status_ketersediaan', 'tersedia'))
                        <option value="tersedia" {{ $oldStatus === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="dipinjam" {{ $oldStatus === 'dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                        <option value="dalam_perbaikan" {{ $oldStatus === 'dalam_perbaikan' ? 'selected' : '' }}>Dalam Perbaikan</option>
                        <option value="tidak_tersedia" {{ $oldStatus === 'tidak_tersedia' ? 'selected' : '' }}>Tidak Tersedia</option>
                    </x-input.select>

                    <x-input.text
                        label="Jumlah Total"
                        name="jumlah_total"
                        id="jumlah_total"
                        type="number"
                        :value="old('jumlah_total', 1)"
                        min="1"
                        :required="true"
                        :error="$errors->first('jumlah_total')"
                    />

                    {{-- Breakdown jumlah untuk tipe pooled (opsional) --}}
                    <div class="form-field form-field--full" id="pooled-breakdown" style="display:none;">
                        <div class="form-section__grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem;">
                            <x-input.text
                                label="Jumlah Tersedia"
                                name="jumlah_tersedia"
                                id="jumlah_tersedia"
                                type="number"
                                :value="old('jumlah_tersedia')"
                                min="0"
                                :error="$errors->first('jumlah_tersedia')"
                            />
                            <x-input.text
                                label="Jumlah Rusak"
                                name="jumlah_rusak"
                                id="jumlah_rusak"
                                type="number"
                                :value="old('jumlah_rusak')"
                                min="0"
                                :error="$errors->first('jumlah_rusak')"
                            />
                            <x-input.text
                                label="Jumlah Maintenance"
                                name="jumlah_maintenance"
                                id="jumlah_maintenance"
                                type="number"
                                :value="old('jumlah_maintenance')"
                                min="0"
                                :error="$errors->first('jumlah_maintenance')"
                            />
                            <x-input.text
                                label="Jumlah Hilang"
                                name="jumlah_hilang"
                                id="jumlah_hilang"
                                type="number"
                                :value="old('jumlah_hilang')"
                                min="0"
                                :error="$errors->first('jumlah_hilang')"
                            />
                        </div>
                        <p class="c-input__helper" style="margin-top: 0.5rem;">
                            Jika dikosongkan, sistem akan menganggap semua unit sebagai <strong>tersedia</strong>.
                        </p>
                    </div>

                    {{-- Tahun, Nilai, Lokasi --}}
                    <x-input.text
                        label="Tahun Pembelian"
                        name="tahun_perolehan"
                        id="tahun_perolehan"
                        type="number"
                        :value="old('tahun_perolehan')"
                        min="1900"
                        max="{{ date('Y') + 1 }}"
                        :error="$errors->first('tahun_perolehan')"
                    />

                    <x-input.text
                        label="Harga Beli (Rp)"
                        name="nilai_perolehan"
                        id="nilai_perolehan"
                        type="number"
                        :value="old('nilai_perolehan')"
                        min="0"
                        step="0.01"
                        :error="$errors->first('nilai_perolehan')"
                    />

                    <x-input.text
                        label="Lokasi Penyimpanan"
                        name="lokasi_penyimpanan"
                        id="lokasi_penyimpanan"
                        :value="old('lokasi_penyimpanan')"
                        placeholder="Contoh: Gudang A, Lab Komputer"
                        :error="$errors->first('lokasi_penyimpanan')"
                    />

                    {{-- Foto & Keterangan --}}
                    <div class="form-field">
                        <x-input.file
                            label="Foto Sarana"
                            name="foto"
                            id="foto"
                            accept="image/*"
                            :helper="'Opsional. Maksimal 2MB. Format gambar.'"
                        />
                        @error('foto')
                            <p class="c-input__helper is-invalid">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field form-field--full">
                        <div class="c-input">
                            <label for="keterangan" class="c-input__label">Keterangan</label>
                            <div class="c-input__control">
                                <textarea
                                    id="keterangan"
                                    name="keterangan"
                                    rows="3"
                                    class="c-input__element"
                                    placeholder="Catatan tambahan tentang sarana (opsional)"
                                >{{ old('keterangan') }}</textarea>
                            </div>
                            @error('keterangan')
                                <p class="c-input__helper is-invalid">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </x-form-section>
        </x-form-group>

        {{-- Actions --}}
        <div class="form-section__actions form-section__actions--footer">
            <a href="{{ route('sarana.index') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Batal
                </x-button>
            </a>
            <x-button type="submit" variant="primary" icon="heroicon-o-check-circle">
                Simpan Sarana
            </x-button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var typeSelect = document.getElementById('type');
        var breakdown = document.getElementById('pooled-breakdown');

        function toggleBreakdown() {
            if (!typeSelect) return;
            var value = typeSelect.value;
            if (value === 'pooled') {
                breakdown.style.display = '';
            } else {
                breakdown.style.display = 'none';
            }
        }

        if (typeSelect && breakdown) {
            typeSelect.addEventListener('change', toggleBreakdown);
            toggleBreakdown();
        }
    });
</script>

@endsection

