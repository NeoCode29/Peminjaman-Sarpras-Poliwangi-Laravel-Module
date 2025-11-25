@props([
    'title' => null,
    'description' => null,
    'variant' => 'default',
    'footer' => null,
])

@php
    $variantClasses = [
        'default' => 'c-section',
        'flush' => 'c-section c-section--flush',
        'muted' => 'c-section c-section--muted',
    ];

    $sectionClasses = $variantClasses[$variant] ?? $variantClasses['default'];
    $hasHeader = $title || $description || isset($headerActions) || isset($meta);
@endphp

<div {{ $attributes->merge(['class' => $sectionClasses]) }}>
    @if($hasHeader)
        <div class="c-section__header">
            <div class="c-section__header-main">
                <div class="c-section__header-text">
                    @if($title)
                        <h2 class="c-section__title">{{ $title }}</h2>
                    @endif
                    @if($description)
                        <p class="c-section__description">{{ $description }}</p>
                    @endif
                </div>

                @isset($meta)
                    <div class="c-section__meta">
                        {{ $meta }}
                    </div>
                @endisset
            </div>

            @isset($headerActions)
                <div class="c-section__actions">
                    {{ $headerActions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="c-section__content">
        <div class="c-section__body">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="c-section__footer">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
