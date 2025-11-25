@props([
    'text' => null,
    'orientation' => 'horizontal',
])

@php
    $dividerClass = 'c-divider c-divider--' . $orientation .
        ($text ? ' c-divider--with-text' : '');
@endphp

@if($text)
    <div {{ $attributes->merge(['class' => $dividerClass]) }} role="separator">
        <span class="c-divider__text">{{ $text }}</span>
    </div>
@else
    <hr {{ $attributes->merge(['class' => $dividerClass]) }} role="separator" />
@endif
