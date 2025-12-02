@extends('layouts.app')

@section('title', 'Detail Prasarana')
@section('page-title', 'Detail Prasarana')
@section('page-subtitle', 'Informasi lengkap prasarana dan gambar terkait')

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

    @if(session('sarpras_success'))
        <div class="u-toast-container">
            <x-toast type="success" title="Berhasil" :duration="3000">
                {{ session('sarpras_success') }}
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

    <x-detail-section
        :title="$prasarana->name"
        :description="($prasarana->kategori?->name ? $prasarana->kategori->name . ' · ' : '') . ($prasarana->lokasi ?? 'Lokasi belum diisi')"
    >
        <x-detail-list :columns="2" variant="bordered">
            <x-detail-item label="Nama Prasarana">
                {{ $prasarana->name }}
            </x-detail-item>

            <x-detail-item label="Kategori">
                {{ $prasarana->kategori->name ?? '-' }}
            </x-detail-item>

            <x-detail-item label="Lokasi">
                {{ $prasarana->lokasi ?? '-' }}
            </x-detail-item>

            <x-detail-item label="Kapasitas">
                {{ $prasarana->kapasitas ?? '-' }}
            </x-detail-item>

            <x-detail-item label="Status">
                @php
                    $statusVariant = match($prasarana->status) {
                        'tersedia' => 'success',
                        'rusak' => 'danger',
                        'maintenance' => 'warning',
                        default => 'default',
                    };
                @endphp
                <x-badge :variant="$statusVariant" size="sm" rounded>
                    {{ ucfirst($prasarana->status) }}
                </x-badge>
            </x-detail-item>

            <x-detail-item label="Deskripsi" :full="true">
                {{ $prasarana->description ?: 'Belum ada deskripsi.' }}
            </x-detail-item>
        </x-detail-list>

        {{-- Gallery --}}
        <div class="u-gallery-section">
            <h3 class="u-gallery-title">Gambar Prasarana</h3>

            @if($prasarana->images->count() > 0)
                <x-carousel :autoplay="true" :interval="5000" class="u-carousel-mt">
                    @foreach($prasarana->images as $image)
                        <div class="c-carousel__slide">
                            <img
                                src="{{ asset('storage/' . $image->image_url) }}"
                                alt="Gambar Prasarana"
                                loading="lazy"
                            >
                            <div class="c-carousel__slide-content">
                                <strong>{{ $prasarana->name }}</strong>
                                <div class="u-slide-meta">
                                    {{ basename($image->image_url) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </x-carousel>
            @else
                <p class="u-text-empty">
                    Belum ada gambar yang diunggah untuk prasarana ini.
                </p>
            @endif
        </div>

        <div class="u-action-group">
            <a href="{{ route('prasarana.index') }}" class="u-link-plain">
                <x-button type="button" variant="secondary">
                    Kembali ke Daftar
                </x-button>
            </a>
            @can('update', $prasarana)
                <a href="{{ route('prasarana.edit', $prasarana) }}" class="u-link-plain">
                    <x-button type="button" variant="primary">
                        Edit Prasarana
                    </x-button>
                </a>
            @endcan
        </div>
    </x-detail-section>

    @can('viewAny', [\Modules\PrasaranaManagement\Entities\PrasaranaApprover::class, $prasarana])
        <x-detail-section
            title="Approver Prasarana"
            description="Kelola daftar approver khusus untuk prasarana ini."
        >
            @can('create', [\Modules\PrasaranaManagement\Entities\PrasaranaApprover::class, $prasarana])
                <x-slot:headerActions>
                    <x-button
                        type="button"
                        variant="primary"
                        onclick="document.getElementById('addPrasaranaApproverModal').open()"
                    >
                        Tambah Approver
                    </x-button>
                </x-slot:headerActions>
            @endcan

            <div class="u-flex-wrap">
                {{-- Daftar Approver --}}
                <div class="u-flex-grow">
                    @if($approvers->isEmpty())
                        <p class="u-text-muted-sm">Belum ada approver yang terdaftar.</p>
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
                                                    onclick="document.getElementById('editPrasaranaApproverModal-{{ $approver->id }}').open()"
                                                    class="u-btn-mr-xs"
                                                >
                                                    Edit
                                                </x-button>
                                            @endcan
                                            @can('delete', $approver)
                                                <x-button
                                                    type="button"
                                                    size="sm"
                                                    variant="danger"
                                                    onclick="document.getElementById('deletePrasaranaApproverModal-{{ $approver->id }}').open()"
                                                >
                                                    Hapus
                                                </x-button>
                                            @endcan
                                        </x-table.td>
                                    </x-table.row>
                                @endforeach
                            </x-table.body>
                        </x-table>

                        <div class="data-table__pagination u-pagination-wrapper">
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

{{-- Modal Tambah Approver (Global) --}}
@can('create', [\Modules\PrasaranaManagement\Entities\PrasaranaApprover::class, $prasarana])
    <x-modal id="addPrasaranaApproverModal" title="Tambah Approver Prasarana" size="sm">
        <form
            id="add-prasarana-approver-form"
            method="POST"
            action="{{ route('prasarana.approvers.store', $prasarana) }}"
            class="u-modal-form"
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
                form="add-prasarana-approver-form"
            >
                Simpan Approver
            </x-button>
        </x-slot:footer>
    </x-modal>
@endcan

{{-- Modal Edit & Delete Approver --}}
@foreach($approvers as $approver)
    @can('update', $approver)
        <x-modal id="editPrasaranaApproverModal-{{ $approver->id }}" title="Edit Approver Prasarana" size="sm">
            <form
                id="edit-prasarana-approver-form-{{ $approver->id }}"
                method="POST"
                action="{{ route('prasarana.approvers.update', [$prasarana, $approver]) }}"
                class="u-modal-form"
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
                    form="edit-prasarana-approver-form-{{ $approver->id }}"
                >
                    Simpan Perubahan
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan

    @can('delete', $approver)
        <x-modal id="deletePrasaranaApproverModal-{{ $approver->id }}" title="Hapus Approver" size="sm">
            <form
                id="delete-prasarana-approver-form-{{ $approver->id }}"
                action="{{ route('prasarana.approvers.destroy', [$prasarana, $approver]) }}"
                method="POST"
                class="u-modal-form--delete"
            >
                @csrf
                @method('DELETE')

                <p class="u-confirm-text">
                    Yakin ingin menghapus approver <strong>{{ $approver->approver->name ?? '-' }}</strong> untuk prasarana ini?
                </p>
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button
                    type="submit"
                    variant="danger"
                    form="delete-prasarana-approver-form-{{ $approver->id }}"
                >
                    Hapus
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endcan
@endforeach
