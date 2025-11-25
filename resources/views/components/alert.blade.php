@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
    'icon' => null,
])

@php
    $typeClasses = [
        'info' => 'c-alert--info',
        'success' => 'c-alert--success',
        'warning' => 'c-alert--warning',
        'danger' => 'c-alert--danger',
    ];
    
    $defaultIcons = [
        'info' => 'heroicon-o-information-circle',
        'success' => 'heroicon-o-check-circle',
        'warning' => 'heroicon-o-exclamation-triangle',
        'danger' => 'heroicon-o-x-circle',
    ];
    
    $alertClass = 'c-alert ' . ($typeClasses[$type] ?? $typeClasses['info']);
    $iconComponent = $icon ?? ($defaultIcons[$type] ?? $defaultIcons['info']);
@endphp

<div {{ $attributes->merge(['class' => $alertClass]) }} role="alert" @if($dismissible) data-alert @endif>
    <div class="c-alert__content">
        @if($iconComponent)
            <div class="c-alert__icon" aria-hidden="true">
                <x-dynamic-component :component="$iconComponent" />
            </div>
        @endif
        
        <div class="c-alert__body">
            @if($title)
                <h4 class="c-alert__title">{{ $title }}</h4>
            @endif
            <div class="c-alert__message">
                {{ $slot }}
            </div>
        </div>
    </div>
    
    @if($dismissible)
        <button type="button" class="c-alert__close" data-alert-close aria-label="Tutup alert">
            <x-heroicon-o-x-mark />
        </button>
    @endif
</div>
