@props([
    'sortable' => false,
    'sortColumn' => null,
    'sortDirection' => null,
    'align' => 'left',
])

@php
    $thClass = 'c-table__th c-table__th--' . $align .
        ($sortable ? ' c-table__th--sortable' : '');
    
    $isSorted = $sortable && $sortColumn;
    $sortIcon = null;
    
    if ($isSorted) {
        $thClass .= ' c-table__th--sorted';
        if ($sortDirection === 'asc') {
            $thClass .= ' c-table__th--asc';
            $sortIcon = 'heroicon-o-chevron-up';
        } else {
            $thClass .= ' c-table__th--desc';
            $sortIcon = 'heroicon-o-chevron-down';
        }
    }
@endphp

<th {{ $attributes->merge(['class' => $thClass]) }}>
    @if($sortable)
        <button type="button" class="c-table__sort-button" @if($sortColumn) data-sort-column="{{ $sortColumn }}" @endif>
            <span class="c-table__sort-text">{{ $slot }}</span>
            <span class="c-table__sort-icon" aria-hidden="true">
                @if($sortIcon)
                    <x-dynamic-component :component="$sortIcon" />
                @else
                    <x-heroicon-o-chevron-up-down />
                @endif
            </span>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
