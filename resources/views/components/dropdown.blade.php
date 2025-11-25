@props([
    'align' => 'left',
    'width' => 'default',
    'trigger' => null,
])

@php
    $alignClasses = [
        'left' => 'c-dropdown__menu--left',
        'right' => 'c-dropdown__menu--right',
        'center' => 'c-dropdown__menu--center',
    ];
    
    $widthClasses = [
        'default' => 'c-dropdown__menu--default',
        'sm' => 'c-dropdown__menu--sm',
        'md' => 'c-dropdown__menu--md',
        'lg' => 'c-dropdown__menu--lg',
        'full' => 'c-dropdown__menu--full',
    ];
    
    $menuClass = 'c-dropdown__menu ' . 
        ($alignClasses[$align] ?? $alignClasses['left']) . ' ' .
        ($widthClasses[$width] ?? $widthClasses['default']);
@endphp

<div {{ $attributes->merge(['class' => 'c-dropdown']) }} data-dropdown>
    <div class="c-dropdown__trigger" data-dropdown-trigger>
        {{ $trigger }}
    </div>
    
    <div class="{{ $menuClass }}" data-dropdown-menu role="menu">
        {{ $slot }}
    </div>
</div>
