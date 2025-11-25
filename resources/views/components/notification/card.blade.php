@props([
    'title',
    'message',
    'icon' => 'heroicon-o-bell',
    'category' => null,
    'categoryVariant' => 'default',
    'time' => null,
    'unread' => false,
    'priority' => 'normal', // low, normal, high, urgent
])

@php
    $isImportant = in_array($priority, ['high', 'urgent'], true);

    $wrapperClasses = 'c-card c-card--notification';
    if ($unread) {
        $wrapperClasses .= ' c-card--notification-unread';
    }
@endphp

<div {{ $attributes->merge(['class' => $wrapperClasses]) }}>
    <div class="c-card__body c-card__body--notification">
        <div class="c-card__notification-main">
            <div class="c-card__notification-icon" aria-hidden="true">
                <x-dynamic-component :component="$icon" />
            </div>

            <div class="c-card__notification-content">
                <div class="c-card__notification-header">
                    <div>
                        <h3 class="c-card__title c-card__title--notification">
                            {{ $title }}
                            @if($isImportant)
                                <span class="c-card__notification-pill">
                                    {{ $priority === 'urgent' ? 'URGENT' : 'Prioritas Tinggi' }}
                                </span>
                            @endif
                        </h3>
                    </div>

                    @if($category)
                        <x-badge variant="{{ $categoryVariant }}" size="sm">
                            {{ $category }}
                        </x-badge>
                    @endif
                </div>

                <p class="c-card__description c-card__description--notification">
                    {{ $message }}
                </p>

                @if($time)
                    <div class="c-card__notification-meta">
                        <span class="c-card__notification-time">{{ $time }}</span>
                        @if($unread)
                            <span class="c-card__notification-dot" aria-label="Belum dibaca"></span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if(isset($actions))
            <div class="c-card__notification-actions">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
