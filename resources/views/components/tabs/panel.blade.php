@props([
    'id' => null,
    'active' => false,
])

@php
    $panelClass = 'c-tabs__panel' . ($active ? ' c-tabs__panel--active' : '');
@endphp

<div
    @if($id) id="{{ $id }}" @endif
    {{ $attributes->merge(['class' => $panelClass]) }}
    role="tabpanel"
    @if(!$active) hidden @endif
>
    {{ $slot }}
</div>
