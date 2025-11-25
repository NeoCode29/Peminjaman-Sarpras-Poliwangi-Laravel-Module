@props([
    'separator' => 'chevron',
])

@php
    $separatorIcons = [
        'chevron' => 'heroicon-o-chevron-right',
        'slash' => null,
        'arrow' => 'heroicon-o-arrow-right',
    ];
    
    $separatorIcon = $separatorIcons[$separator] ?? $separatorIcons['chevron'];
    $separatorClass = $separator === 'slash' ? 'c-breadcrumb--slash' : '';
@endphp

<nav {{ $attributes->merge(['class' => 'c-breadcrumb ' . $separatorClass]) }} aria-label="Breadcrumb">
    <ol class="c-breadcrumb__list" data-breadcrumb-separator="{{ $separator }}" data-separator-icon="{{ $separatorIcon }}">
        {{ $slot }}
    </ol>
</nav>
