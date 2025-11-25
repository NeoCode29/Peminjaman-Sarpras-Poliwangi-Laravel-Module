@props([])

<tbody {{ $attributes->merge(['class' => 'c-table__body']) }}>
    {{ $slot }}
</tbody>
