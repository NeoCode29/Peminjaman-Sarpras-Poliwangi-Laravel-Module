@props([
    'name' => null,
    'id' => $name,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'label' => null,
    'description' => null,
])

<label class="c-choice {{ $disabled ? 'is-disabled' : '' }}">
    <input 
        type="checkbox" 
        id="{{ $id }}" 
        name="{{ $name }}" 
        value="{{ $value }}"
        class="c-choice__input"
        @if($checked) checked @endif
        @if($disabled) disabled @endif
        {{ $attributes }}
    >
    <span class="c-choice__box">
        <span class="c-choice__icon" aria-hidden="true">
            <x-heroicon-o-check />
        </span>
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
