@props([
    'target' => null,
    'active' => false,
    'icon' => null,
])

@php
    $tabClass = 'c-tabs__tab' . ($active ? ' c-tabs__tab--active' : '');
@endphp

<button
    type="button"
    {{ $attributes->merge(['class' => $tabClass]) }}
    @if($target) data-tab-target="{{ $target }}" @endif
    @if($active) aria-selected="true" @else aria-selected="false" @endif
    role="tab"
>
    @if($icon)
        <span class="c-tabs__tab-icon" aria-hidden="true">
            <x-dynamic-component :component="$icon" />
        </span>
    @endif
    <span class="c-tabs__tab-text">{{ $slot }}</span>
</button>
