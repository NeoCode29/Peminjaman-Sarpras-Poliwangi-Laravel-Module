@props([])

<div {{ $attributes->merge(['class' => 'c-tabs__list']) }} role="tablist">
    {{ $slot }}
</div>
