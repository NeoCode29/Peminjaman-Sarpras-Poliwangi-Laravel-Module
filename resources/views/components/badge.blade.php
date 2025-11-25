@props([
    'variant' => 'default',
    'size' => 'default',
    'rounded' => false,
    'dot' => false,
])

@php
    $variantClasses = [
        'default' => 'c-badge--default',
        'primary' => 'c-badge--primary',
        'success' => 'c-badge--success',
        'warning' => 'c-badge--warning',
        'danger' => 'c-badge--danger',
        'info' => 'c-badge--info',
    ];
    
    $sizeClasses = [
        'sm' => 'c-badge--sm',
        'default' => 'c-badge--default-size',
        'lg' => 'c-badge--lg',
    ];
    
    $badgeClass = 'c-badge ' . 
        ($variantClasses[$variant] ?? $variantClasses['default']) . ' ' .
        ($sizeClasses[$size] ?? $sizeClasses['default']) .
        ($rounded ? ' c-badge--rounded' : '') .
        ($dot ? ' c-badge--with-dot' : '');
@endphp

<span {{ $attributes->merge(['class' => $badgeClass]) }}>
    @if($dot)
        <span class="c-badge__dot" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
