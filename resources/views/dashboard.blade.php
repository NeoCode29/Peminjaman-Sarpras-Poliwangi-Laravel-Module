@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang di sistem manajemen')

@section('content')
<div class="page-content">
    {{-- Stats Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px;">
        {{-- Total Users --}}
        <div style="background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 16px; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 56px; height: 56px; background: var(--color-blue-100); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    👥
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main);">
                        {{ $stats['total_users'] ?? 0 }}
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">
                        Total Users
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Roles --}}
        <div style="background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 16px; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 56px; height: 56px; background: #f3e5f5; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    🔐
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main);">
                        {{ $stats['total_roles'] ?? 0 }}
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">
                        Total Roles
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Permissions --}}
        <div style="background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 16px; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 56px; height: 56px; background: #e8f5e9; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    🔑
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main);">
                        {{ $stats['total_permissions'] ?? 0 }}
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">
                        Total Permissions
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Users --}}
        <div style="background: var(--surface-card); border: 1px solid var(--border-default); border-radius: 16px; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 56px; height: 56px; background: #e8f5e8; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    ✅
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main);">
                        {{ $stats['active_users'] ?? 0 }}
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500;">
                        Active Users
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="form-section">
        <div class="form-section__header">
            <h2 style="margin: 0; font-size: 1.4rem; font-weight: 700;">Quick Actions</h2>
            <div class="form-section__divider"></div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <a href="{{ route('user-management.create') }}" class="c-button c-button--primary c-button--with-icon" style="width: 100%; justify-content: center; text-decoration: none;">
                <span class="c-button__icon" aria-hidden="true">
                    <x-heroicon-o-plus />
                </span>
                Tambah User
            </a>
            
            <a href="{{ route('role-management.create') }}" class="c-button c-button--primary c-button--with-icon" style="width: 100%; justify-content: center; text-decoration: none;">
                <span class="c-button__icon" aria-hidden="true">
                    <x-heroicon-o-plus />
                </span>
                Tambah Role
            </a>
            
            <a href="{{ route('user-management.index') }}" class="c-button c-button--outline-primary c-button--with-icon" style="text-decoration: none; justify-content: center;">
                <span class="c-button__icon" aria-hidden="true">
                    <x-heroicon-o-clipboard-document-list />
                </span>
                Lihat Users
            </a>
            
            <a href="{{ route('role-management.index') }}" class="c-button c-button--outline-primary c-button--with-icon" style="text-decoration: none; justify-content: center;">
                <span class="c-button__icon" aria-hidden="true">
                    <x-heroicon-o-clipboard-document-list />
                </span>
                Lihat Roles
            </a>
        </div>
    </div>

    {{-- Recent Activity (Optional) --}}
    <div class="form-section">
        <div class="form-section__header">
            <h2 style="margin: 0; font-size: 1.4rem; font-weight: 700;">Informasi Sistem</h2>
            <div class="form-section__divider"></div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; justify-content: space-between; padding: 16px; background: var(--color-soft); border-radius: 12px;">
                <span style="font-weight: 600; color: var(--text-main);">Laravel Version</span>
                <span style="color: var(--text-muted);">{{ app()->version() }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 16px; background: var(--color-soft); border-radius: 12px;">
                <span style="font-weight: 600; color: var(--text-main);">PHP Version</span>
                <span style="color: var(--text-muted);">{{ PHP_VERSION }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 16px; background: var(--color-soft); border-radius: 12px;">
                <span style="font-weight: 600; color: var(--text-main);">Environment</span>
                <span style="color: var(--text-muted);">{{ app()->environment() }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
