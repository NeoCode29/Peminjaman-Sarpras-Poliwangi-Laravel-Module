@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => true,
    'duration' => 5000,
])

@php
    $typeClasses = [
        'info' => 'c-toast--info',
        'success' => 'c-toast--success',
        'warning' => 'c-toast--warning',
        'danger' => 'c-toast--danger',
    ];
    
    $typeIcons = [
        'info' => 'heroicon-o-information-circle',
        'success' => 'heroicon-o-check-circle',
        'warning' => 'heroicon-o-exclamation-triangle',
        'danger' => 'heroicon-o-x-circle',
    ];
    
    $toastClass = 'c-toast ' . ($typeClasses[$type] ?? $typeClasses['info']);
    $iconComponent = $typeIcons[$type] ?? $typeIcons['info'];
@endphp

<div 
    {{ $attributes->merge(['class' => $toastClass]) }} 
    role="alert" 
    aria-live="assertive"
    data-toast
    @if($duration) data-toast-duration="{{ $duration }}" @endif
>
    <div class="c-toast__icon" aria-hidden="true">
        <x-dynamic-component :component="$iconComponent" />
    </div>
    
    <div class="c-toast__content">
        @if($title)
            <h4 class="c-toast__title">{{ $title }}</h4>
        @endif
        <div class="c-toast__message">
            {{ $slot }}
        </div>
    </div>
    
    @if($dismissible)
        <button type="button" class="c-toast__close" data-toast-close aria-label="Tutup notifikasi">
            <x-heroicon-o-x-mark />
        </button>
    @endif
</div>
