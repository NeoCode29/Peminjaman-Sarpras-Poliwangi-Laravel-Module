@props([
    'name' => null,
    'id' => null,
    'value' => null,
    'checked' => false,
    'disabled' => false,
    'label' => null,
    'description' => null,
])

<label class="c-choice c-choice--radio {{ $disabled ? 'is-disabled' : '' }}">
    <input 
        type="radio" 
        id="{{ $id }}" 
        name="{{ $name }}" 
        value="{{ $value }}"
        class="c-choice__input"
        @if($checked) checked @endif
        @if($disabled) disabled @endif
        {{ $attributes }}
    >
    <span class="c-choice__box">
        <span class="c-choice__indicator" aria-hidden="true"></span>
    </span>
    
    @if($label || $description)
        <div>
            @if($label)
                <div class="c-choice__label">{{ $label }}</div>
            @endif
            @if($description)
                <div class="c-choice__description">{{ $description }}</div>
            @endif
        </div>
    @else
        {{ $slot }}
    @endif
</label>
