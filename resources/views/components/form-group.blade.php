@props([
    'title' => null,
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'c-form-group']) }}>
    @if($title || $description || isset($headerActions))
        <div class="c-form-group__header">
            <div class="c-form-group__header-main">
                @if($icon)
                    <div class="c-form-group__icon">
                        <x-dynamic-component :component="$icon" />
                    </div>
                @endif
                
                <div class="c-form-group__header-text">
                    @if($title)
                        <h3 class="c-form-group__title">{{ $title }}</h3>
                    @endif
                    @if($description)
                        <p class="c-form-group__description">{{ $description }}</p>
                    @endif
                </div>
            </div>
            
            @isset($headerActions)
                <div class="c-form-group__actions">
                    {{ $headerActions }}
                </div>
            @endisset
        </div>
    @endif
    
    <div class="c-form-group__content">
        {{ $slot }}
    </div>
    
    @isset($footer)
        <div class="c-form-group__footer">
            {{ $footer }}
        </div>
    @endisset
</div>
