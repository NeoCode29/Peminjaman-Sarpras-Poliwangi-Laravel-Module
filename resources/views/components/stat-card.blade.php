@props([
    'label' => null,
    'value' => null,
    'icon' => null,
    'variant' => 'primary',
])

@php
    $variantClasses = [
        'primary' => 'c-stat-card__icon--primary',
        'success' => 'c-stat-card__icon--success',
        'warning' => 'c-stat-card__icon--warning',
        'info' => 'c-stat-card__icon--info',
        'danger' => 'c-stat-card__icon--danger',
        'secondary' => 'c-stat-card__icon--secondary',
        'purple' => 'c-stat-card__icon--purple',
    ];
    
    $iconClass = $variantClasses[$variant] ?? $variantClasses['primary'];
@endphp

<article {{ $attributes->merge(['class' => 'c-stat-card']) }}>
    @if($icon)
        <div class="c-stat-card__icon {{ $iconClass }}">
            <x-dynamic-component :component="$icon" />
        </div>
    @endif
    
    <div class="c-stat-card__body">
        @if($label)
            <span class="c-stat-card__label">{{ $label }}</span>
        @endif
        
        <span class="c-stat-card__value">
            {{ $value ?? $slot }}
        </span>
    </div>
</article>
