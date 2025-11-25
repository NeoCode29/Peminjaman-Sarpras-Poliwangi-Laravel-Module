@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'default',
    'block' => false,
    'loading' => false,
    'disabled' => false,
    'icon' => null,
])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 
        'c-button c-button--' . $variant . ' c-button--size-' . $size .
        ($block ? ' c-button--block' : '') .
        ($icon ? ' c-button--with-icon' : '') .
        ($loading ? ' is-loading' : '')
    ]) }}
    @if($disabled || $loading) disabled @endif
>
    @if($icon && !$loading)
        <span class="c-button__icon" aria-hidden="true">
            <x-dynamic-component :component="$icon" />
        </span>
    @endif
    {{ $slot }}
</button>
