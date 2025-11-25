@props([])

<div {{ $attributes->merge(['class' => 'c-tabs__panels']) }}>
    {{ $slot }}
</div>
