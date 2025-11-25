@props([
    'align' => 'left',
])

@php
    $tdClass = 'c-table__td c-table__td--' . $align;
@endphp

<td {{ $attributes->merge(['class' => $tdClass]) }}>
    {{ $slot }}
</td>
