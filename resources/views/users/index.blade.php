@extends('layouts.app')

@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')
@section('page-subtitle', 'Kelola data pengguna sistem')

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
                {{ $errors->first() }}
            </x-toast>
        </div>
    @endif

    {{-- Management Tabs --}}
    <nav aria-label="Manajemen Akses" style="display:flex;gap:16px;margin-bottom:16px;border-bottom:1px solid var(--border-subtle);padding-bottom:4px;">
        <a href="{{ route('user-management.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('user-management.*') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            User
        </a>
        <a href="{{ route('role-management.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('role-management.*') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            Role
        </a>
        <a href="{{ route('permission-management.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('permission-management.*') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            Permissions
        </a>
    </nav>

    {{-- Data Control --}}
    <section class="data-control" aria-label="Kontrol Data">
        <form method="GET" action="{{ route('user-management.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    :value="$filters['search'] ?? ''"
                    placeholder="Cari user..."
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
            <a href="{{ route('user-management.create') }}" class="c-button c-button--primary c-button--with-icon data-control__action"
               style="width:auto;text-decoration:none;">
                <span class="c-button__icon" aria-hidden="true">
                    <x-heroicon-o-plus />
                </span>
                Tambah User
            </a>
        </div>
    </section>

    {{-- Filters (Optional) --}}
    <section class="data-filters" id="user-data-filters" aria-label="Filter Data" data-filter-panel hidden>
        <form method="GET" action="{{ route('user-management.index') }}" class="data-filters__grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:center;">
            {{-- Pertahankan kata kunci pencarian saat mengubah filter --}}
            <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">

            <div>
                <x-input.select
                    label="Role"
                    name="role_id"
                    id="filter_role"
                    placeholder="Semua Role"
                    onchange="this.form.submit()"
                >
                    @foreach($roles ?? [] as $role)
                        <option value="{{ $role->id }}" {{ (string)($filters['role_id'] ?? '') === (string)$role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </x-input.select>
            </div>

            <div>
                <x-input.select
                    label="Status"
                    name="status"
                    id="filter_status"
                    placeholder="Semua Status"
                    onchange="this.form.submit()"
                >
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                    <option value="blocked" {{ ($filters['status'] ?? '') === 'blocked' ? 'selected' : '' }}>Diblokir</option>
                </x-input.select>
            </div>

            {{-- Tidak ada tombol khusus, filter akan diterapkan otomatis saat nilai diubah --}}
        </form>
    </section>

    {{-- Data Table --}}
    <section class="data-table" aria-label="Tabel Data">
        <div class="data-table__container">
            <table class="data-table__table">
                <x-table.head class="data-table__head">
                    <tr class="data-table__row">
                        <x-table.th align="center" class="data-table__cell data-table__cell--number">#</x-table.th>
                        <x-table.th class="data-table__cell">Nama</x-table.th>
                        <x-table.th class="data-table__cell">Email</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Role</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Status</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--action">Aksi</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($users ?? [] as $index => $user)
                        <tr class="data-table__row js-user-row" data-href="{{ route('user-management.show', $user->id) }}" style="cursor:pointer;">
                            <x-table.td align="center" class="data-table__cell data-table__cell--number">{{ $index + 1 }}</x-table.td>
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $user->name }}</strong>
                                    <small style="color: var(--text-muted);">{{ $user->username }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">{{ $user->email }}</x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                @foreach($user->roles as $role)
                                    <x-badge variant="primary" size="sm" style="margin-right: 0.25rem;">
                                        {{ $role->name }}
                                    </x-badge>
                                @endforeach
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                @php
                                    $statusVariant = $user->status === 'active' ? 'success' : ($user->status === 'blocked' ? 'danger' : 'warning');
                                @endphp
                                <x-badge :variant="$statusVariant" size="sm">
                                    {{ ucfirst($user->status) }}
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--action">
                                <div style="display: flex; gap: 8px;">
                                    <a href="{{ route('user-management.edit', $user->id) }}" class="js-row-action" style="text-decoration: none;">
                                        <x-button type="button" variant="secondary" size="sm">
                                            Edit
                                        </x-button>
                                    </a>
                                    <div class="js-row-action">
                                        <x-button
                                            type="button"
                                            variant="danger"
                                            size="sm"
                                            onclick="document.getElementById('deleteUserModal-{{ $user->id }}').open()"
                                        >
                                            Hapus
                                        </x-button>
                                    </div>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="6" class="data-table__cell" align="center" style="padding: 40px; color: var(--text-muted);">
                                Belum ada data user
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Delete Modals --}}
    @foreach($users ?? [] as $user)
        <x-modal id="deleteUserModal-{{ $user->id }}" title="Hapus User" size="sm">
            <form
                id="delete-user-form-{{ $user->id }}"
                method="POST"
                action="{{ route('user-management.destroy', $user->id) }}"
                style="display: flex; flex-direction: column; gap: 1rem;"
            >
                @csrf
                @method('DELETE')

                <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                    Anda akan menghapus akun <strong>{{ $user->name }}</strong> secara permanen dari sistem.
                </p>

                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                    Tindakan ini tidak dapat dibatalkan. Data terkait seperti relasi mungkin juga akan terpengaruh.
                </p>
            </form>

            <x-slot:footer>
                <x-button type="button" variant="secondary" data-modal-close>
                    Batal
                </x-button>
                <x-button type="submit" variant="danger" icon="heroicon-o-trash" form="delete-user-form-{{ $user->id }}">
                    Hapus User
                </x-button>
            </x-slot:footer>
        </x-modal>
    @endforeach

    {{-- Pagination (jika menggunakan Laravel pagination) --}}
    @if(isset($users) && method_exists($users, 'links'))
        {{ $users->links('components.pagination') }}
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var rows = document.querySelectorAll('.js-user-row');
    rows.forEach(function (row) {
        row.addEventListener('click', function () {
            var href = this.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });
    });

    var safeElements = document.querySelectorAll('.js-row-action');
    safeElements.forEach(function (el) {
        el.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });
});
</script>
@endpush
