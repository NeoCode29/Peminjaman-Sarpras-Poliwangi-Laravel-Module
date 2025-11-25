@props([
    'title' => null,
    'description' => null,
    'icon' => null,
    'variant' => 'default',
    'layout' => 'default',
    'footer' => null,
    'headerActions' => null,
])

@php
    $variantClasses = [
        'default' => 'c-card',
        'info' => 'c-card c-card--info',
        'warning' => 'c-card c-card--warning',
        'danger' => 'c-card c-card--danger',
        'success' => 'c-card c-card--success',
    ];

    $layoutClasses = [
        'default' => '',
        'centered' => 'c-card--centered',
    ];

    $cardClasses = trim(($variantClasses[$variant] ?? $variantClasses['default']) . ' ' . ($layoutClasses[$layout] ?? ''));
@endphp

<div {{ $attributes->merge(['class' => $cardClasses]) }}>
    @php($hasHeader = $title || $description || $icon || $headerActions)

    @if($hasHeader)
        <div class="c-card__header">
            <div class="c-card__header-main">
                @if($icon)
                    <div class="c-card__icon" aria-hidden="true">
                        <x-dynamic-component :component="$icon" />
                    </div>
                @endif

                <div class="c-card__header-text">
                    @if($title)
                        <h3 class="c-card__title">{{ $title }}</h3>
                    @endif
                    @if(!empty($description))
                        <p class="c-card__description">{{ $description }}</p>
                    @endif
                </div>
            </div>

            @isset($headerActions)
                <div class="c-card__header-actions">
                    {{ $headerActions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="c-card__body">
        {{ $slot }}
    </div>

    @if($footer)
        <div class="c-card__footer">
            {{ $footer }}
        </div>
    @endif
</div>
