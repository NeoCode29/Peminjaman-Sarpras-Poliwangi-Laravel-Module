@extends('layouts.app')

@section('title', 'System Settings')
@section('page-title', 'Pengaturan Sistem')
@section('page-subtitle', 'Kelola pengaturan autentikasi dan akses aplikasi')

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

    {{-- Settings Tabs --}}
    <nav aria-label="Pengaturan" style="display:flex;gap:16px;margin-bottom:16px;border-bottom:1px solid var(--border-subtle);padding-bottom:4px;">
        <a href="{{ route('settings.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('settings.index') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            Umum
        </a>
        @can('global_approver.manage')
        <a href="{{ route('settings.global-approvers.index') }}"
           style="padding:6px 0;font-size:0.95rem;font-weight:600;text-decoration:none;{{ request()->routeIs('settings.global-approvers.*') ? 'color:var(--brand-primary);border-bottom:2px solid var(--brand-primary);' : 'color:var(--text-muted);' }}">
            Global Approvers
        </a>
        @endcan
    </nav>

    <form action="{{ route('settings.update') }}" method="POST" class="form-section">
        @csrf

        {{-- Authentication Settings --}}
        @if(isset($settingsByGroup['authentication']))
            <x-form-group
                title="Autentikasi & Akses"
                description="Pengaturan registrasi manual dan login SSO."
                icon="heroicon-o-shield-check"
            >
                <x-form-section>
                    <div class="form-section__grid">
                        @foreach($settingsByGroup['authentication'] as $setting)
                            @php
                                $value = old('settings.' . $setting->key, $values[$setting->key] ?? $setting->value);
                                $labelMap = [
                                    'enable_manual_registration' => 'Registrasi Manual',
                                    'enable_sso_login' => 'Login dengan SSO',
                                ];
                                $label = $labelMap[$setting->key] ?? ucwords(str_replace('_', ' ', $setting->key));
                                
                                // Convert boolean to string for comparison
                                if (is_bool($value)) {
                                    $value = $value ? '1' : '0';
                                } else {
                                    $value = (string)$value;
                                }
                            @endphp

                            @if($setting->type === 'boolean')
                                <x-input.select
                                    label="{{ $label }}"
                                    name="settings[{{ $setting->key }}]"
                                    id="settings_{{ $setting->key }}"
                                    helper="{{ $setting->description }}"
                                    :error="$errors->first('settings.' . $setting->key)"
                                    placeholder=""
                                    :required="true"
                                >
                                    <option value="1" {{ $value === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ $value === '0' ? 'selected' : '' }}>Nonaktif</option>
                                </x-input.select>
                            @else
                                <x-input.text
                                    label="{{ $label }}"
                                    name="settings[{{ $setting->key }}]"
                                    id="settings_{{ $setting->key }}"
                                    :value="$value"
                                    placeholder="{{ $setting->description }}"
                                    :required="false"
                                />
                            @endif
                        @endforeach
                    </div>
                </x-form-section>
            </x-form-group>
        @endif

        {{-- Actions --}}
        <div class="form-section__actions form-section__actions--footer">
            <x-button type="submit" variant="primary" icon="heroicon-o-check-circle">
                Simpan Pengaturan
            </x-button>
        </div>
    </form>
</div>
@endsection
