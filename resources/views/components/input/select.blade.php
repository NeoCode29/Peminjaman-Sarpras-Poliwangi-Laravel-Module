@props([
    'label' => null,
    'name' => null,
    'id' => $name,
    'placeholder' => 'Pilih...',
    'helper' => null,
    'required' => false,
    'badge' => null,
    'error' => null,
])

<div class="c-input {{ $error ? 'c-input--invalid' : '' }}">
    @if($label)
        <label for="{{ $id }}" class="c-input__label">
            {{ $label }}
            @if($badge)
                <span class="c-input__badge">{{ $badge }}</span>
            @endif
        </label>
    @endif

    <div class="c-input__control">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            class="c-input__element c-input__element--select {{ $error ? 'is-invalid' : '' }}"
            @if($required) required @endif
            {{ $attributes }}
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            {{ $slot }}
        </select>
    </div>

    @if($error)
        <p class="c-input__helper is-invalid">{{ $error }}</p>
    @elseif($helper)
        <p class="c-input__helper">{{ $helper }}</p>
    @endif
</div>
