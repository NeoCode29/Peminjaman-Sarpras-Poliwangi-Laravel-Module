@props([
    'columns' => 4,
])

@php
    $gridClasses = [
        2 => 'c-stat-grid c-stat-grid--col-2',
        3 => 'c-stat-grid c-stat-grid--col-3',
        4 => 'c-stat-grid c-stat-grid--col-4',
    ];
    
    $gridClass = $gridClasses[$columns] ?? $gridClasses[4];
@endphp

<div {{ $attributes->merge(['class' => $gridClass]) }}>
    {{ $slot }}
</div>
