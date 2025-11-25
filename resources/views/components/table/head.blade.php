@props([])

<thead {{ $attributes->merge(['class' => 'c-table__head']) }}>
    {{ $slot }}
</thead>
