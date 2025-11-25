@props([
    'title' => null,
    'description' => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'c-form-section']) }}>
    @if($title || $description)
        <div class="c-form-section__header">
            @if($title)
                <h4 class="c-form-section__title">
                    {{ $title }}
                    @if($required)
                        <span class="c-form-section__required">*</span>
                    @endif
                </h4>
            @endif
            @if($description)
                <p class="c-form-section__description">{{ $description }}</p>
            @endif
        </div>
    @endif
    
    <div class="c-form-section__body">
        {{ $slot }}
    </div>
</div>
