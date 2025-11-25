@props([
    'title' => null,
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'c-detail-section']) }}>
    @if($title || $description || isset($headerActions))
        <div class="c-detail-section__header">
            <div class="c-detail-section__header-main">
                @if($icon)
                    <div class="c-detail-section__icon">
                        <x-dynamic-component :component="$icon" />
                    </div>
                @endif
                
                <div class="c-detail-section__header-text">
                    @if($title)
                        <h3 class="c-detail-section__title">{{ $title }}</h3>
                    @endif
                    @if($description)
                        <p class="c-detail-section__description">{{ $description }}</p>
                    @endif
                </div>
            </div>
            
            @isset($headerActions)
                <div class="c-detail-section__actions">
                    {{ $headerActions }}
                </div>
            @endisset
        </div>
    @endif
    
    <div class="c-detail-section__content">
        {{ $slot }}
    </div>
    
    @isset($footer)
        <div class="c-detail-section__footer">
            {{ $footer }}
        </div>
    @endisset
</div>
