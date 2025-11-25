@props([
    'label' => null,
    'name' => null,
    'id' => $name,
    'placeholder' => '',
    'helper' => null,
    'required' => false,
    'badge' => null,
    'error' => null,
    'rows' => 4,
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
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            placeholder="{{ $placeholder }}"
            class="c-input__element c-input__element--textarea {{ $error ? 'is-invalid' : '' }}"
            rows="{{ $rows }}"
            @if($required) required @endif
            {{ $attributes }}
        >{{ $slot }}</textarea>
    </div>

    @if($error)
        <p class="c-input__helper is-invalid">{{ $error }}</p>
    @elseif($helper)
        <p class="c-input__helper">{{ $helper }}</p>
    @endif
</div>
