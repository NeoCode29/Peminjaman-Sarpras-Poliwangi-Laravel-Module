@props([
    'id' => 'modal-' . uniqid(),
    'title' => null,
    'size' => 'default',
    'footer' => null,
    'closable' => true,
    'staticBackdrop' => false,
])

@php
    $sizeClasses = [
        'sm' => 'c-modal__dialog--sm',
        'default' => 'c-modal__dialog--default',
        'lg' => 'c-modal__dialog--lg',
        'xl' => 'c-modal__dialog--xl',
        'fullscreen' => 'c-modal__dialog--fullscreen',
    ];
    
    $dialogClass = 'c-modal__dialog ' . ($sizeClasses[$size] ?? $sizeClasses['default']);
@endphp

<div 
    {{ $attributes->merge(['class' => 'c-modal']) }} 
    id="{{ $id }}" 
    data-modal 
    @if($staticBackdrop) data-modal-static @endif
    role="dialog" 
    aria-labelledby="{{ $id }}-title" 
    aria-hidden="true"
>
    <div class="c-modal__backdrop" data-modal-backdrop></div>
    
    <div class="{{ $dialogClass }}" role="document">
        <div class="c-modal__content">
            @if($title || $closable)
                <div class="c-modal__header">
                    @if($title)
                        <h3 class="c-modal__title" id="{{ $id }}-title">{{ $title }}</h3>
                    @endif
                    
                    @if($closable)
                        <button type="button" class="c-modal__close" data-modal-close aria-label="Tutup modal">
                            <x-heroicon-o-x-mark />
                        </button>
                    @endif
                </div>
            @endif
            
            <div class="c-modal__body">
                {{ $slot }}
            </div>
            
            @if($footer)
                <div class="c-modal__footer">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
