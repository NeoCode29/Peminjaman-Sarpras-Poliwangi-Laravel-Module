@props([
    'label' => null,
    'name' => null,
    'id' => $name,
    'helper' => null,
    'required' => false,
    'multiple' => false,
    'accept' => null,
    'buttonText' => 'Upload File',
    'buttonIcon' => 'heroicon-o-arrow-up-tray',
    'showRemoveToggle' => false,
    'removeToggleName' => null,
    'removeToggleLabel' => 'Hapus file yang sudah ada',
    'existingUrl' => null,
    'existingRemoveName' => null,
    'existingRemoveLabel' => 'Hapus file ini',
    'existingFiles' => [], // array: [['url' => '...', 'removeName' => '...', 'label' => '...'], ...]
])

<div class="c-input">
    @if($label)
        <label class="c-input__label">{{ $label }}</label>
    @endif

    <div class="c-file">
        <div class="c-file__button">
            @if($buttonIcon)
                <x-dynamic-component :component="$buttonIcon" />
            @endif
            {{ $buttonText }}
        </div>
        
        @if($helper)
            <p class="c-file__text">{{ $helper }}</p>
        @endif

        @if(!empty($existingFiles))
            @foreach($existingFiles as $file)
                @php
                    $fileUrl = $file['url'] ?? null;
                    $fileRemoveName = $file['removeName'] ?? null;
                    $fileLabel = $file['label'] ?? ($label ?? 'File');
                    $fileRemoveLabel = $file['removeLabel'] ?? $existingRemoveLabel;
                @endphp

                @if($fileUrl)
                    <div class="c-file__existing" data-existing-file-wrapper>
                        <img src="{{ $fileUrl }}" alt="{{ $fileLabel }}" class="c-file__existing-image">

                        @if($fileRemoveName)
                            <button
                                type="button"
                                class="c-file__existing-remove"
                                data-existing-file-remove
                                aria-label="{{ $fileRemoveLabel }}"
                            >
                                <span class="c-file__existing-remove-icon" aria-hidden="true">&times;</span>
                                <span class="c-file__existing-remove-label">{{ $fileRemoveLabel }}</span>
                            </button>

                            <input
                                type="hidden"
                                name="{{ $fileRemoveName }}"
                                value="0"
                                data-existing-file-remove-input
                            >
                        @endif
                    </div>
                @endif
            @endforeach
        @elseif($existingUrl)
            <div class="c-file__existing" data-existing-file-wrapper>
                <img src="{{ $existingUrl }}" alt="{{ $label ?? 'File' }}" class="c-file__existing-image">

                @if($existingRemoveName)
                    <button
                        type="button"
                        class="c-file__existing-remove"
                        data-existing-file-remove
                        aria-label="{{ $existingRemoveLabel }}"
                    >
                        <span class="c-file__existing-remove-icon" aria-hidden="true">&times;</span>
                        <span class="c-file__existing-remove-label">{{ $existingRemoveLabel }}</span>
                    </button>

                    <input
                        type="hidden"
                        name="{{ $existingRemoveName }}"
                        value="0"
                        data-existing-file-remove-input
                    >
                @endif
            </div>
        @endif

        <input 
            type="file" 
            id="{{ $id }}" 
            name="{{ $name }}" 
            class="c-file__input" 
            data-file-input
            @if($multiple) multiple @endif
            @if($accept) accept="{{ $accept }}" @endif
            @if($required) required @endif
            {{ $attributes }}
        >

        <div class="c-file__preview" data-file-preview hidden></div>

        <div class="c-file__actions">
            <button
                type="button"
                class="c-file__clear-button"
                data-file-clear
                hidden
            >
                Hapus pilihan
            </button>
        </div>

        @if($showRemoveToggle && $removeToggleName)
            <div class="c-file__existing-toggle">
                <label class="c-input__helper">
                    <input
                        type="checkbox"
                        name="{{ $removeToggleName }}"
                        value="1"
                    >
                    {{ $removeToggleLabel }}
                </label>
            </div>
        @endif
    </div>
</div>
