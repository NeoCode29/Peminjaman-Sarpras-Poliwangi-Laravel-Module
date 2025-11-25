@props([
    'active' => false,
])

@php
    $rowClass = 'c-table__row' . ($active ? ' c-table__row--active' : '');
@endphp

<tr {{ $attributes->merge(['class' => $rowClass]) }}>
    {{ $slot }}
</tr>
