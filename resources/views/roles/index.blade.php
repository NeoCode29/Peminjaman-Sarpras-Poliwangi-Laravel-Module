@extends('layouts.app')

@section('title', 'Manajemen Role')
@section('page-title', 'Manajemen Role')
@section('page-subtitle', 'Kelola role dan hak akses pengguna')

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
        <form method="GET" action="{{ route('role-management.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari role..."
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
            <a href="{{ route('role-management.create') }}" class="c-button c-button--primary c-button--with-icon data-control__action"
               style="width: auto; text-decoration: none;">
                <span class="c-button__icon" aria-hidden="true">
                    <x-heroicon-o-plus />
                </span>
                Tambah Role
            </a>
        </div>
    </section>

    {{-- Filters (Optional) --}}
    <section class="data-filters" id="role-data-filters" aria-label="Filter Data" data-filter-panel hidden>
        <form method="GET" action="{{ route('role-management.index') }}" class="data-filters__grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;">
            {{-- Pertahankan kata kunci pencarian saat mengubah filter --}}
            <input type="hidden" name="search" value="{{ request('search') }}">

            <div>
                <x-input.select
                    label="Guard"
                    name="guard_name"
                    id="filter_guard_name"
                    placeholder="Semua Guard"
                    onchange="this.form.submit()"
                >
                    <option value="web" {{ request('guard_name') === 'web' ? 'selected' : '' }}>Web</option>
                    <option value="api" {{ request('guard_name') === 'api' ? 'selected' : '' }}>API</option>
                </x-input.select>
            </div>

            <div>
                <x-input.select
                    label="Tipe Role"
                    name="protected"
                    id="filter_protected"
                    placeholder="Semua Role"
                    onchange="this.form.submit()"
                >
                    <option value="1" {{ request('protected') === '1' ? 'selected' : '' }}>Protected Roles</option>
                    <option value="0" {{ request('protected') === '0' ? 'selected' : '' }}>Custom Roles</option>
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
                        <x-table.th class="data-table__cell">Nama Role</x-table.th>
                        <x-table.th class="data-table__cell">Deskripsi</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Permissions</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Users</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--action">Aksi</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($roles ?? [] as $index => $role)
                        <tr
                            class="data-table__row js-role-row"
                            data-href="{{ route('role-management.show', $role->id) }}"
                            style="cursor: pointer;"
                        >
                            <x-table.td align="center" class="data-table__cell data-table__cell--number">{{ $index + 1 }}</x-table.td>
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $role->name }}</strong>
                                    @if(\App\Constants\ProtectedRoles::isProtected($role->name))
                                        <x-badge variant="warning" size="sm" style="margin-left: 8px;">
                                            Protected
                                        </x-badge>
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                <small style="color: var(--text-muted);">{{ $role->description ?? '-' }}</small>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <x-badge variant="primary" size="sm" rounded>
                                    {{ $role->permissions_count ?? $role->permissions->count() }} permissions
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <x-badge variant="default" size="sm" rounded>
                                    {{ $role->users_count ?? $role->users->count() }} users
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--action">
                                <div style="display: flex; gap: 8px;" class="js-row-action">
                                    <a href="{{ route('role-management.edit', $role->id) }}" style="text-decoration: none;" class="js-row-action">
                                        <x-button type="button" variant="secondary" size="sm">
                                            Edit
                                        </x-button>
                                    </a>
                                    @if(!\App\Constants\ProtectedRoles::isProtected($role->name))
                                        <x-button
                                            type="button"
                                            variant="danger"
                                            size="sm"
                                            onclick="document.getElementById('deleteRoleModal-{{ $role->id }}').open()"
                                            class="js-row-action"
                                        >
                                            Hapus
                                        </x-button>
                                    @endif
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="6" class="data-table__cell" align="center" style="padding: 40px; color: var(--text-muted);">
                                Belum ada data role
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Delete Modals --}}
    @foreach($roles ?? [] as $role)
        @if(!\App\Constants\ProtectedRoles::isProtected($role->name))
            <x-modal id="deleteRoleModal-{{ $role->id }}" title="Hapus Role" size="sm">
                <form
                    id="delete-role-form-{{ $role->id }}"
                    method="POST"
                    action="{{ route('role-management.destroy', $role->id) }}"
                    style="display: flex; flex-direction: column; gap: 1rem;"
                >
                    @csrf
                    @method('DELETE')

                    <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                        Anda akan menghapus role <strong>{{ $role->name }}</strong> dari sistem.
                    </p>

                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                        Tindakan ini tidak dapat dibatalkan. Pastikan role ini tidak lagi digunakan.
                    </p>
                </form>

                <x-slot:footer>
                    <x-button type="button" variant="secondary" data-modal-close>
                        Batal
                    </x-button>
                    <x-button type="submit" variant="danger" icon="heroicon-o-trash" form="delete-role-form-{{ $role->id }}">
                        Hapus Role
                    </x-button>
                </x-slot:footer>
            </x-modal>
        @endif
    @endforeach

    {{-- Pagination --}}
    @if(isset($roles) && method_exists($roles, 'links'))
        {{ $roles->links('components.pagination') }}
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var rows = document.querySelectorAll('.js-role-row');
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
