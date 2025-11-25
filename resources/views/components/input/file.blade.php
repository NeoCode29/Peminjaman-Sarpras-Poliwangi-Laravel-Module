@props([
    'label' => null,
    'name' => null,
    'id' => $name,
    'helper' => null,
    'required' => false,
    'multiple' => false,
    'accept' => null,
    'buttonText' => 'Upload File',
    'buttonIcon' => 'heroicon-o-arrow-up-tray',
])

<div class="c-input">
    @if($label)
        <label class="c-input__label">{{ $label }}</label>
    @endif

    <div class="c-file">
        <div class="c-file__button">
            @if($buttonIcon)
                <x-dynamic-component :component="$buttonIcon" />
            @endif
            {{ $buttonText }}
        </div>
        
        @if($helper)
            <p class="c-file__text">{{ $helper }}</p>
        @endif
        
        <input 
            type="file" 
            id="{{ $id }}" 
            name="{{ $name }}" 
            class="c-file__input" 
            data-file-input
            @if($multiple) multiple @endif
            @if($accept) accept="{{ $accept }}" @endif
            @if($required) required @endif
            {{ $attributes }}
        >
        
        <div class="c-file__preview" data-file-preview hidden></div>
    </div>
</div>
