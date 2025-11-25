@props([
    'size' => 'default',
    'variant' => 'primary',
])

@php
    $sizeClasses = [
        'sm' => 'c-spinner--sm',
        'default' => 'c-spinner--default',
        'lg' => 'c-spinner--lg',
    ];
    
    $variantClasses = [
        'primary' => 'c-spinner--primary',
        'secondary' => 'c-spinner--secondary',
        'white' => 'c-spinner--white',
    ];
    
    $spinnerClass = 'c-spinner ' . 
        ($sizeClasses[$size] ?? $sizeClasses['default']) . ' ' .
        ($variantClasses[$variant] ?? $variantClasses['primary']);
@endphp

<div {{ $attributes->merge(['class' => $spinnerClass]) }} role="status" aria-live="polite">
    <span class="c-spinner__element"></span>
    <span class="sr-only">Memuat...</span>
</div>
