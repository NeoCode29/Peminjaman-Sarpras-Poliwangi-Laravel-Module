@props([
    'label' => null,
    'name' => null,
    'id' => $name,
    'type' => 'text',
    'placeholder' => '',
    'helper' => null,
    'required' => false,
    'badge' => null,
    'icon' => null,
    'withToggle' => false,
    'error' => null,
    'valid' => false,
])

<div class="c-input {{ $error ? 'c-input--invalid' : ($valid ? 'c-input--valid' : '') }} {{ $icon ? 'c-input--with-icon' : '' }}">
    @if($label)
        <label for="{{ $id }}" class="c-input__label">
            {{ $label }}
            @if($badge)
                <span class="c-input__badge">{{ $badge }}</span>
            @endif
        </label>
    @endif

    <div class="c-input__control {{ $withToggle ? 'c-input__control--with-toggle' : '' }}" 
         @if($type === 'password' && $withToggle) data-password-field @endif>
        
        @if($icon)
            <span class="c-input__icon" aria-hidden="true">
                <x-dynamic-component :component="$icon" />
            </span>
        @endif

        <input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            placeholder="{{ $placeholder }}"
            class="c-input__element {{ $error ? 'is-invalid' : ($valid ? 'is-valid' : '') }} {{ $type === 'number' ? 'c-input__element--number' : '' }} {{ $type === 'date' ? 'c-input__element--date' : '' }}"
            @if($required) required @endif
            {{ $attributes }}
            @if($type === 'password' && $withToggle) data-password-input @endif
        >

        @if($type === 'password' && $withToggle)
            <button type="button" class="c-input__toggle" data-password-toggle aria-label="Tampilkan password">
                <x-heroicon-o-eye-slash />
            </button>
        @endif
    </div>

    @if($error)
        <p class="c-input__helper is-invalid">{{ $error }}</p>
    @elseif($helper)
        <p class="c-input__helper">{{ $helper }}</p>
    @endif
</div>
