@extends('layouts.app')

@section('title', 'Manajemen Permission')
@section('page-title', 'Manajemen Permission')
@section('page-subtitle', 'Kelola permission dan hak akses sistem')

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
        <form method="GET" action="{{ route('permission-management.index') }}" class="data-control__search" style="display:flex;align-items:center;width:100%;gap:12px;">
            <div style="flex:1;min-width:220px;">
                <x-input.text
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari permission..."
                    icon="heroicon-o-magnifying-glass"
                    autocomplete="off"
                />
            </div>

            <button class="data-control__filter-toggle" type="button" aria-expanded="false" data-filter-toggle
                    style="background: var(--surface-card); border: 1px solid var(--border-default); cursor: pointer;display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px;">
                <x-heroicon-o-funnel style="width: 18px; height: 18px;" />
            </button>
        </form>
    </section>

    {{-- Filters (Optional) --}}
    <section class="data-filters" id="permission-data-filters" aria-label="Filter Data" data-filter-panel hidden>
        <form method="GET" action="{{ route('permission-management.index') }}" class="data-filters__grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:center;">
            {{-- Pertahankan kata kunci pencarian saat mengubah filter --}}
            <input type="hidden" name="search" value="{{ request('search') }}">

            <div>
                <x-input.select
                    label="Kategori"
                    name="category"
                    id="filter_category"
                    placeholder="Semua Kategori"
                    onchange="this.form.submit()"
                >
                    <option value="user" {{ request('category') === 'user' ? 'selected' : '' }}>User Management</option>
                    <option value="role" {{ request('category') === 'role' ? 'selected' : '' }}>Role Management</option>
                    <option value="permission" {{ request('category') === 'permission' ? 'selected' : '' }}>Permission Management</option>
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
                        <x-table.th class="data-table__cell">Permission Name</x-table.th>
                        <x-table.th class="data-table__cell">Deskripsi</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Kategori</x-table.th>
                        <x-table.th class="data-table__cell data-table__cell--meta">Roles</x-table.th>
                    </tr>
                </x-table.head>
                <x-table.body class="data-table__body">
                    @forelse($permissions ?? [] as $index => $permission)
                        <tr class="data-table__row">
                            <x-table.td align="center" class="data-table__cell data-table__cell--number">{{ $index + 1 }}</x-table.td>
                            <x-table.td class="data-table__cell">
                                <div class="data-table__data">
                                    <strong>{{ $permission->name }}</strong>
                                    <small style="color: var(--text-muted); font-family: monospace;">{{ $permission->guard_name ?? 'web' }}</small>
                                </div>
                            </x-table.td>
                            <x-table.td class="data-table__cell">
                                <small style="color: var(--text-muted);">{{ $permission->description ?? '-' }}</small>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                @php
                                    $category = explode('.', $permission->name)[0] ?? 'other';
                                    $categoryVariant = match($category) {
                                        'user' => 'primary',
                                        'role' => 'secondary',
                                        'permission' => 'success',
                                        default => 'default'
                                    };
                                @endphp
                                <x-badge :variant="$categoryVariant" size="sm" rounded>
                                    {{ ucfirst($category) }}
                                </x-badge>
                            </x-table.td>
                            <x-table.td class="data-table__cell data-table__cell--meta">
                                <div class="data-table__meta-group">
                                    @forelse($permission->roles->take(3) as $role)
                                        <x-badge variant="default" size="sm" rounded style="margin-right: 4px;">
                                            {{ $role->name }}
                                        </x-badge>
                                    @empty
                                        <span style="color: var(--text-muted); font-size: 0.85rem;">No roles</span>
                                    @endforelse
                                    @if($permission->roles->count() > 3)
                                        <x-badge variant="default" size="sm" rounded>
                                            +{{ $permission->roles->count() - 3 }}
                                        </x-badge>
                                    @endif
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr class="data-table__row">
                            <x-table.td colspan="5" class="data-table__cell" align="center" style="padding: 40px; color: var(--text-muted);">
                                Belum ada data permission
                            </x-table.td>
                        </tr>
                    @endforelse
                </x-table.body>
            </table>
        </div>
    </section>

    {{-- Pagination --}}
    @if(isset($permissions) && method_exists($permissions, 'links'))
        {{ $permissions->links('components.pagination') }}
    @endif
</div>

@push('scripts')
<script>
// Filter toggle sudah ada di app.js global
</script>
@endpush
@endsection
