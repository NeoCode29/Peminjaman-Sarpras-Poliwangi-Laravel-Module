@props([
    'href' => null,
    'active' => false,
    'icon' => null,
])

@php
    $itemClass = 'c-breadcrumb__item' . ($active ? ' c-breadcrumb__item--active' : '');
@endphp

<li {{ $attributes->merge(['class' => $itemClass]) }}>
    @if($href && !$active)
        <a href="{{ $href }}" class="c-breadcrumb__link">
            @if($icon)
                <span class="c-breadcrumb__icon" aria-hidden="true">
                    <x-dynamic-component :component="$icon" />
                </span>
            @endif
            {{ $slot }}
        </a>
    @else
        <span class="c-breadcrumb__current" @if($active) aria-current="page" @endif>
            @if($icon)
                <span class="c-breadcrumb__icon" aria-hidden="true">
                    <x-dynamic-component :component="$icon" />
                </span>
            @endif
            {{ $slot }}
        </span>
    @endif
</li>
