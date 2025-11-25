@props([
    'variant' => 'default',
])

@php
    $variantClasses = [
        'default' => 'c-tabs',
        'pills' => 'c-tabs c-tabs--pills',
        'underline' => 'c-tabs c-tabs--underline',
    ];
    
    $tabsClass = $variantClasses[$variant] ?? $variantClasses['default'];
@endphp

<div {{ $attributes->merge(['class' => $tabsClass]) }} data-tabs>
    {{ $slot }}
</div>
