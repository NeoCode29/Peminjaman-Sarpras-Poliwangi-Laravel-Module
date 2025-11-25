@props([
    'label' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'c-detail-item']) }}>
    @if($label)
        <dt class="c-detail-item__label">
            @if($icon)
                <span class="c-detail-item__icon">
                    <x-dynamic-component :component="$icon" />
                </span>
            @endif
            {{ $label }}
        </dt>
    @endif
    
    <dd class="c-detail-item__value">
        {{ $slot }}
    </dd>
</div>
