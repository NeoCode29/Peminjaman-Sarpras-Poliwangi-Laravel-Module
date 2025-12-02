@props([
    'id' => null,
    'autoplay' => false,
    'interval' => 5000,
])

@php
    $carouselId = $id ?? 'carousel-' . uniqid();
@endphp

<div
    {{ $attributes->merge(['class' => 'c-carousel']) }}
    id="{{ $carouselId }}"
    data-carousel
    data-carousel-autoplay="{{ $autoplay ? 'true' : 'false' }}"
    data-carousel-interval="{{ (int) $interval }}"
>
    <div class="c-carousel__viewport" data-carousel-viewport>
        <div class="c-carousel__track" data-carousel-track>
            {{ $slot }}
        </div>
    </div>

    <button
        type="button"
        class="c-carousel__control c-carousel__control--prev"
        data-carousel-prev
        aria-label="Slide sebelumnya"
    >
        <span class="c-carousel__control-icon" aria-hidden="true">&#10094;</span>
    </button>

    <button
        type="button"
        class="c-carousel__control c-carousel__control--next"
        data-carousel-next
        aria-label="Slide berikutnya"
    >
        <span class="c-carousel__control-icon" aria-hidden="true">&#10095;</span>
    </button>

    <div class="c-carousel__indicators" data-carousel-indicators></div>
</div>

<style>
    .c-carousel {
        position: relative;
        width: 100%;
        overflow: hidden;
        border-radius: 12px;
        background: var(--surface-card, #ffffff);
        z-index: 0; /* stacking context agar control tidak menumpuk di luar area carousel */
    }

    .c-carousel__viewport {
        width: 100%;
        overflow: hidden;
    }

    .c-carousel__track {
        display: flex;
        transition: transform 0.4s ease;
        will-change: transform;
    }

    .c-carousel__slide {
        min-width: 100%;
        flex: 0 0 100%;
        position: relative;
    }

    .c-carousel__slide img {
        width: 100%;
        display: block;
        object-fit: cover;
        /* 16:9 aspect ratio */
        aspect-ratio: 16 / 9;
    }

    .c-carousel__slide-content {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 12px 16px;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.55), transparent);
        color: #fff;
        font-size: 0.9rem;
    }

    .c-carousel__control {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 32px;
        height: 32px;
        border-radius: 999px;
        border: none;
        background: rgba(15, 23, 42, 0.65);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        z-index: 10;
    }

    .c-carousel__control--prev { left: 8px; }
    .c-carousel__control--next { right: 8px; }

    .c-carousel__control-icon {
        font-size: 16px;
        line-height: 1;
    }

    .c-carousel__control:focus-visible {
        outline: 2px solid var(--brand-primary, #2563eb);
        outline-offset: 2px;
    }

    .c-carousel__indicators {
        position: absolute;
        left: 50%;
        bottom: 10px;
        transform: translateX(-50%);
        display: flex;
        gap: 6px;
        z-index: 10;
    }

    .c-carousel__indicator {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.8);
        border: none;
        padding: 0;
        cursor: pointer;
    }

    .c-carousel__indicator.is-active {
        width: 16px;
        background: var(--brand-primary, #2563eb);
    }

    @media (max-width: 640px) {
        .c-carousel__slide img {
            aspect-ratio: 16 / 9;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var carousels = document.querySelectorAll('[data-carousel]');

        carousels.forEach(function (root) {
            var track = root.querySelector('[data-carousel-track]');
            var slides = track ? track.children : [];
            if (!track || slides.length === 0) return;

            var prevBtn = root.querySelector('[data-carousel-prev]');
            var nextBtn = root.querySelector('[data-carousel-next]');
            var indicatorsContainer = root.querySelector('[data-carousel-indicators]');
            var autoplay = root.getAttribute('data-carousel-autoplay') === 'true';
            var interval = parseInt(root.getAttribute('data-carousel-interval') || '5000', 10);
            var currentIndex = 0;
            var timer = null;

            // Create indicators
            if (indicatorsContainer) {
                indicatorsContainer.innerHTML = '';

                Array.prototype.forEach.call(slides, function (_slide, index) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'c-carousel__indicator' + (index === 0 ? ' is-active' : '');
                    button.setAttribute('aria-label', 'Slide ' + (index + 1));
                    button.addEventListener('click', function () {
                        goTo(index);
                        restartAutoplay();
                    });
                    indicatorsContainer.appendChild(button);
                });
            }

            function updateIndicators() {
                if (!indicatorsContainer) return;
                var dots = indicatorsContainer.children;
                Array.prototype.forEach.call(dots, function (dot, index) {
                    dot.classList.toggle('is-active', index === currentIndex);
                });
            }

            function goTo(index) {
                var clamped = (index + slides.length) % slides.length;
                currentIndex = clamped;
                var offset = -clamped * 100;
                track.style.transform = 'translateX(' + offset + '%)';
                updateIndicators();
            }

            function next() {
                goTo(currentIndex + 1);
            }

            function prev() {
                goTo(currentIndex - 1);
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    prev();
                    restartAutoplay();
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    next();
                    restartAutoplay();
                });
            }

            function startAutoplay() {
                if (!autoplay || slides.length <= 1) return;
                stopAutoplay();
                timer = window.setInterval(next, interval);
            }

            function stopAutoplay() {
                if (timer) {
                    window.clearInterval(timer);
                    timer = null;
                }
            }

            function restartAutoplay() {
                stopAutoplay();
                startAutoplay();
            }

            // Optional: pause on hover
            root.addEventListener('mouseenter', stopAutoplay);
            root.addEventListener('mouseleave', startAutoplay);

            // Simple swipe support (touch)
            var startX = null;

            root.addEventListener('touchstart', function (e) {
                if (!e.touches || e.touches.length === 0) return;
                startX = e.touches[0].clientX;
            });

            root.addEventListener('touchend', function (e) {
                if (startX === null) return;
                if (!e.changedTouches || e.changedTouches.length === 0) return;

                var endX = e.changedTouches[0].clientX;
                var diff = endX - startX;

                if (Math.abs(diff) > 40) {
                    if (diff < 0) {
                        next();
                    } else {
                        prev();
                    }
                    restartAutoplay();
                }

                startX = null;
            });

            // Init
            goTo(0);
            startAutoplay();
        });
    });
</script>
