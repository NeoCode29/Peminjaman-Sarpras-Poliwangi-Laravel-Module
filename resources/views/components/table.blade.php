@props([
    'striped' => false,
    'hoverable' => true,
    'bordered' => false,
    'responsive' => true,
])

@php
    $tableClass = 'c-table' .
        ($striped ? ' c-table--striped' : '') .
        ($hoverable ? ' c-table--hoverable' : '') .
        ($bordered ? ' c-table--bordered' : '');
    
    $wrapperClass = $responsive ? 'c-table-wrapper' : '';
@endphp

@if($responsive)
    <div class="{{ $wrapperClass }}">
@endif

<table {{ $attributes->merge(['class' => $tableClass]) }}>
    {{ $slot }}
</table>

@if($responsive)
    </div>
@endif
