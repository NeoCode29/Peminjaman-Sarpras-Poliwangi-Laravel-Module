@extends('layouts.app')

@section('title', 'Detail Role')
@section('page-title', 'Detail Role')
@section('page-subtitle', 'Informasi lengkap role dan hak aksesnya')

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

    <x-detail-section
        :title="$role->name"
        description="Informasi dasar dan status role dalam sistem."
    >
        <x-detail-list :columns="2" variant="bordered">
            <x-detail-item label="Nama Role">
                {{ $role->name }}
                @if(\App\Constants\ProtectedRoles::isProtected($role->name))
                    <x-badge variant="warning" size="sm" style="margin-left: 0.5rem;">
                        Protected
                    </x-badge>
                @endif
            </x-detail-item>

            <x-detail-item label="Deskripsi">
                {{ $role->description ?? '-' }}
            </x-detail-item>

            <x-detail-item label="Guard">
                {{ $role->guard_name ?? 'web' }}
            </x-detail-item>

            <x-detail-item label="Status">
                <x-badge :variant="$role->is_active ? 'success' : 'danger'" size="sm">
                    {{ $role->is_active ? 'Aktif' : 'Tidak Aktif' }}
                </x-badge>
            </x-detail-item>
        </x-detail-list>

        {{-- Aksi utama --}}
        <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem;">
            <a href="{{ route('role-management.index') }}" style="text-decoration: none;">
                <x-button type="button" variant="secondary">
                    Kembali ke Daftar Role
                </x-button>
            </a>
            <a href="{{ route('role-management.edit', $role->id) }}" style="text-decoration: none;">
                <x-button type="button" variant="primary">
                    Edit Role
                </x-button>
            </a>
        </div>
    </x-detail-section>

    <x-detail-section
        title="Permissions"
        description="Daftar permission yang dimiliki role ini."
    >
        @if($role->permissions->isEmpty())
            <x-empty-state
                title="Belum ada permission"
                description="Role ini belum memiliki permission apapun."
            />
        @else
            <x-detail-list>
                @foreach($role->permissions as $permission)
                    <x-detail-item :label="$permission->name">
                        {{ $permission->description ?? '-' }}
                    </x-detail-item>
                @endforeach
            </x-detail-list>
        @endif
    </x-detail-section>

    <x-detail-section
        title="Users"
        description="Daftar user yang menggunakan role ini."
    >
        @if($role->users->isEmpty())
            <x-empty-state
                title="Belum ada user"
                description="Belum ada user yang menggunakan role ini."
            />
        @else
            <x-detail-list>
                @foreach($role->users as $user)
                    <x-detail-item :label="$user->name">
                        {{ $user->email }}
                    </x-detail-item>
                @endforeach
            </x-detail-list>
        @endif
    </x-detail-section>

    
</div>
@endsection
