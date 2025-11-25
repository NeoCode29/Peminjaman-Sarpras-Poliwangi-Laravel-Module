@props([
    'href' => null,
    'icon' => null,
    'active' => false,
    'danger' => false,
])

@php
    $itemClass = 'c-dropdown__item' .
        ($active ? ' c-dropdown__item--active' : '') .
        ($danger ? ' c-dropdown__item--danger' : '');
    
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @else type="button" @endif
    {{ $attributes->merge(['class' => $itemClass]) }}
    role="menuitem"
>
    @if($icon)
        <span class="c-dropdown__item-icon" aria-hidden="true">
            <x-dynamic-component :component="$icon" />
        </span>
    @endif
    <span class="c-dropdown__item-text">{{ $slot }}</span>
</{{ $tag }}>
