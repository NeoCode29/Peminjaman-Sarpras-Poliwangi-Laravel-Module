@props([
    'icon' => 'heroicon-o-inbox',
    'title' => 'Tidak ada data',
    'description' => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'c-empty-state']) }}>
    <div class="c-empty-state__icon" aria-hidden="true">
        <x-dynamic-component :component="$icon" />
    </div>
    
    <h3 class="c-empty-state__title">{{ $title }}</h3>
    
    @if($description)
        <p class="c-empty-state__description">{{ $description }}</p>
    @endif
    
    @if($action)
        <div class="c-empty-state__action">
            {{ $action }}
        </div>
    @endif
    
    @if($slot->isNotEmpty())
        <div class="c-empty-state__content">
            {{ $slot }}
        </div>
    @endif
</div>
