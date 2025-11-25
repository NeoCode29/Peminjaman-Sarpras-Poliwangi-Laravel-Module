@props([
    'title',
    'description' => null,
    'meta' => null,
    'status' => null,
    'statusVariant' => 'default',
    'unread' => false,
    'icon' => null,
    'iconColor' => 'info',
])

@php
    $itemClasses = 'c-list__item';
    if ($unread) {
        $itemClasses .= ' c-list__item--unread';
    }
@endphp

<div {{ $attributes->class($itemClasses) }}>
    @if($icon)
        <div class="c-list__leading">
            <div class="c-list__icon c-list__icon--{{ $iconColor }}">
                <x-dynamic-component :component="$icon" />
            </div>
        </div>
    @endif

    <div class="c-list__body">
        <div class="c-list__header">
            <div class="c-list__title">{{ $title }}</div>
            @if($meta)
                <div class="c-list__meta">{!! $meta !!}</div>
            @endif
        </div>

        @if($description)
            <div class="c-list__description">
                {!! $description !!}
            </div>
        @endif

        @if(trim($slot))
            <div class="c-list__extra">
                {{ $slot }}
            </div>
        @endif
    </div>

    @if($status)
        <div class="c-list__status">
            <x-badge :variant="$statusVariant" size="sm">
                {{ $status }}
            </x-badge>
        </div>
    @endif

    @if($actions ?? false)
        <div class="c-list__actions">
            {{ $actions }}
        </div>
    @endif
</div>
