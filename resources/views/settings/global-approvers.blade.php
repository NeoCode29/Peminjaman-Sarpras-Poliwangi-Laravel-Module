@extends('layouts.app')

@section('title', 'Global Approvers')
@section('page-title', 'Pengaturan Sistem')
@section('page-subtitle', 'Kelola global approver untuk persetujuan peminjaman')

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

    @if($errors->any())
        <div style="position: fixed; top: 1rem; right: 1rem; z-index: 50;">
            <x-toast type="danger" title="Terjadi Kesalahan" :duration="7000">
                {{ $errors->first('error') ?? $errors->first() }}
            </x-toast>
        </div>
    @endif

    {{-- Settings Tabs --}}
    <nav aria-label="Pengaturan" style="display:flex;gap:16px;margin-bottom:16px;border-bottom:1px solid var(--border-subtle);padding-bottom:4px;">
        <a href="{{ route('settings.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('settings.index') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            Umum
        </a>
        <a href="{{ route('settings.global-approvers.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('settings.global-approvers.*') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            Global Approvers
        </a>
    </nav>

    {{-- Data Control --}}
    <section class="data-control" aria-label="Kontrol Data">
        <form method="GET" action="{{ route('settings.global-approvers.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    :value="request('search', '')"
                    placeholder="Cari approver..."
                    icon="heroicon-o-magnifying-glass"
                    autocomplete="off"
                />
            </div>

            <button class="data-control__filter-toggle" type="button" aria-expanded="false" data-filter-toggle
                    style="background: var(--surface-card); border: 1px solid var(--border-default); cursor: pointer;display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px;">
                <x-heroicon-o-funnel style="width: 18px; height: 18px;" />
            </button>
        </form>

        <div class="data-control__actions">
            <button type="button" 
                    class="c-button c-button--primary c-button--with-icon data-control__action"
                    onclick="document.getElementById('addApproverModal').open()"
                    style="width:auto;">
                <span class="c-button__icon" aria-hidden="true">
                    <x-heroicon-o-plus />
                </span>
                Tambah Approver
            </button>
        </div>
    </section>

    {{-- Filters --}}
    <section class="data-filters" id="approver-data-filters" aria-label="Filter Data" data-filter-panel hidden>
        <form method="GET" action="{{ route('settings.global-approvers.index') }}" class="data-filters__grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:center;">
            <input type="hidden" name="search" value="{{ request('search', '') }}">

            <div>
                <x-input.select
                    label="Level"
                    name="approval_level"
                    id="filter_level"
                    placeholder="Semua Level"
                    onchange="this.form.submit()"
                >
                    @foreach($availableLevels ?? [] as $level)
                        <option value="{{ $level['value'] }}" {{ request('approval_level') == $level['value'] ? 'selected' : '' }}>
                            {{ $level['label'] }}
                        </option>
                    @endforeach
                </x-input.select>
            </div>

            <div>
                <x-input.select
                    label="Status"
                    name="is_active"
                    id="filter_status"
                    placeholder="Semua Status"
                    onchange="this.form.submit()"
                >
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Tidak Aktif</option>
                </x-input.select>
            </div>
        </form>
    </section>

    {{-- Data Table --}}
    <section class="data-table" aria-label="Tabel Data">
        <div class="data-table__container">
            <table class="data-table__table">
                <x-table.head class="data-table__head">
                    <tr class="data-table__row">
                        <x-table.th align="center" class="data-table__cell data-table__cell--number">#</x-table.th>
                        <x-table.th class="data-table__cell">Approver</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Level</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Status</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Dibuat</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--action">Aksi</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($globalApprovers ?? [] as $index => $approver)
                        <tr class="data-table__row">
                            <x-table.td align="center" class="data-table__cell data-table__cell--number">
                                {{ $globalApprovers->firstItem() + $index }}
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $approver->user->name ?? 'N/A' }}</strong>
                                    <small style="color: var(--text-muted);">{{ $approver->user->email ?? '' }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <x-badge variant="primary" size="sm">
                                    {{ $approver->level_label }}
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <x-badge :variant="$approver->is_active ? 'success' : 'danger'" size="sm">
                                    {{ $approver->status_label }}
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <div style="display:flex;flex-direction:column;">
                                    <span>{{ $approver->created_at->format('d/m/Y') }}</span>
                                    <small style="color: var(--text-muted);">{{ $approver->created_at->format('H:i') }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--action">
                                <div style="display: flex; gap: 8px;">
                                    <x-button 
                                        type="button" 
                                        variant="secondary" 
                                        size="sm"
                                        onclick="openEditModal({{ $approver->id }}, {{ $approver->approval_level }}, {{ $approver->is_active ? 'true' : 'false' }})"
                                    >
                                        Edit
                                    </x-button>
                                    <x-button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        onclick="document.getElementById('deleteApproverModal-{{ $approver->id }}').open()"
                                    >
                                        Hapus
                                    </x-button>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="6" class="data-table__cell" align="center" style="padding: 40px; color: var(--text-muted);">
                                <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                                    <x-heroicon-o-users style="width:48px;height:48px;color:var(--text-muted);" />
                                    <span>Belum ada global approver</span>
                                    <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('addApproverModal').open()">
                                        Tambah Approver
                                    </x-button>
                                </div>
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Pagination --}}
    @if(isset($globalApprovers) && method_exists($globalApprovers, 'links'))
        {{ $globalApprovers->links('components.pagination') }}
    @endif

    {{-- Add Modal --}}
    <x-modal id="addApproverModal" title="Tambah Global Approver" size="md">
        <form id="add-approver-form" method="POST" action="{{ route('settings.global-approvers.store') }}" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf

            <x-input.select
                label="Approver"
                name="user_id"
                id="add_user_id"
                placeholder="Pilih User"
                :required="true"
            >
                @foreach($availableUsers ?? [] as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </x-input.select>

            <x-input.select
                label="Level Approval"
                name="approval_level"
                id="add_approval_level"
                placeholder="Pilih Level"
                :required="true"
            >
                @foreach($availableLevels ?? [] as $level)
                    <option value="{{ $level['value'] }}">{{ $level['label'] }}</option>
                @endforeach
            </x-input.select>

            <div style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="is_active" id="add_is_active" value="1" checked style="width:18px;height:18px;">
                <label for="add_is_active" style="font-size:0.9rem;color:var(--text-default);cursor:pointer;">Aktif</label>
            </div>

            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                Global approver dapat menyetujui semua peminjaman. Level yang lebih rendah memiliki otoritas lebih tinggi.
            </p>
        </form>

        <x-slot:footer>
            <x-button type="button" variant="secondary" data-modal-close>
                Batal
            </x-button>
            <x-button type="submit" variant="primary" icon="heroicon-o-check" form="add-approver-form">
                Simpan
            </x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Edit Modal --}}
    <x-modal id="editApproverModal" title="Edit Global Approver" size="md">
        <form id="edit-approver-form" method="POST" action="" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf
            @method('PUT')

            <x-input.select
                label="Level Approval"
                name="approval_level"
                id="edit_approval_level"
                placeholder="Pilih Level"
                :required="true"
            >
                @foreach($availableLevels ?? [] as $level)
                    <option value="{{ $level['value'] }}">{{ $level['label'] }}</option>
                @endforeach
            </x-input.select>

            <div style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="is_active" id="edit_is_active" value="1" style="width:18px;height:18px;">
                <label for="edit_is_active" style="font-size:0.9rem;color:var(--text-default);cursor:pointer;">Aktif</label>
            </div>
        </form>

        <x-slot:footer>
            <x-button type="button" variant="secondary" data-modal-close>
                Batal
            </x-button>
            <x-button type="submit" variant="primary" icon="heroicon-o-check" form="edit-approver-form">
                Update
            </x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Delete Modals --}}
    @foreach($globalApprovers ?? [] as $approver)
        <x-modal id="deleteApproverModal-{{ $approver->id }}" title="Hapus Global Approver" size="sm">
            <form id="delete-approver-form-{{ $approver->id }}" method="POST" action="{{ route('settings.global-approvers.destroy', $approver->id) }}" style="display: flex; flex-direction: column; gap: 1rem;">
                @csrf
                @method('DELETE')

                <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                    Anda akan menghapus <strong>{{ $approver->user->name ?? 'N/A' }}</strong> sebagai global approver.
                </p>

                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                    User ini tidak akan dapat menyetujui peminjaman secara global lagi.
                </p>
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button type="submit" variant="danger" icon="heroicon-o-trash" form="delete-approver-form-{{ $approver->id }}">
                    Hapus
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
function openEditModal(id, level, isActive) {
    var modal = document.getElementById('editApproverModal');
    var form = document.getElementById('edit-approver-form');
    
    if (!modal || !form) return;
    
    form.action = '/settings/global-approvers/' + id;
    document.getElementById('edit_approval_level').value = level;
    document.getElementById('edit_is_active').checked = Boolean(isActive);
    
    modal.open();
}

document.addEventListener('DOMContentLoaded', function() {
    // Filter toggle functionality
    var filterToggle = document.querySelector('[data-filter-toggle]');
    var filterPanel = document.querySelector('[data-filter-panel]');
    
    if (filterToggle && filterPanel) {
        filterToggle.addEventListener('click', function() {
            var isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            filterPanel.hidden = isExpanded;
        });
    }
});
</script>
@endpush
