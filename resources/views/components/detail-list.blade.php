@props([
    'columns' => 2, // 1, 2, atau 3
    'variant' => 'default', // default, bordered, striped
])

@php
    $columnClass = match($columns) {
        1 => 'c-detail-list--col-1',
        3 => 'c-detail-list--col-3',
        default => 'c-detail-list--col-2',
    };

    $variantClass = match($variant) {
        'bordered' => 'c-detail-list--bordered',
        'striped' => 'c-detail-list--striped',
        default => '',
    };

    $classes = trim("c-detail-list {$columnClass} {$variantClass}");
@endphp

<dl {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</dl>
