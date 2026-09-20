/**
 * Generisches, abhaengigkeitsfreies Carousel fuer die Vorschau mehrerer
 * digitaler Objekte in der Detailansicht (ersetzt das frueher genutzte
 * Lightbox2). Initialisiert jedes Element mit [data-dla-carousel].
 */
(function () {
    'use strict';

    function initCarousel(carousel) {
        var track = carousel.querySelector('.dla-carousel-track');
        var slides = carousel.querySelectorAll('.dla-carousel-slide');
        var prevButton = carousel.querySelector('.dla-carousel-prev');
        var nextButton = carousel.querySelector('.dla-carousel-next');
        var dotsContainer = carousel.querySelector('.dla-carousel-dots');

        if (!track || slides.length <= 1) {
            return;
        }

        var currentIndex = 0;

        function goTo(index) {
            currentIndex = (index + slides.length) % slides.length;
            track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';

            if (dotsContainer) {
                var dots = dotsContainer.querySelectorAll('.dla-carousel-dot');
                for (var i = 0; i < dots.length; i++) {
                    dots[i].classList.toggle('is-active', i === currentIndex);
                }
            }
        }

        if (dotsContainer) {
            for (var d = 0; d < slides.length; d++) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'dla-carousel-dot' + (d === 0 ? ' is-active' : '');
                dot.setAttribute('aria-label', 'Bild ' + (d + 1));
                (function (index) {
                    dot.addEventListener('click', function () {
                        goTo(index);
                    });
                })(d);
                dotsContainer.appendChild(dot);
            }
        }

        if (prevButton) {
            prevButton.addEventListener('click', function () {
                goTo(currentIndex - 1);
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', function () {
                goTo(currentIndex + 1);
            });
        }

        // einfache Touch-/Wisch-Unterstuetzung
        var touchStartX = null;
        carousel.addEventListener('touchstart', function (event) {
            touchStartX = event.touches[0].clientX;
        }, { passive: true });

        carousel.addEventListener('touchend', function (event) {
            if (touchStartX === null) {
                return;
            }
            var deltaX = event.changedTouches[0].clientX - touchStartX;
            if (Math.abs(deltaX) > 40) {
                goTo(deltaX < 0 ? currentIndex + 1 : currentIndex - 1);
            }
            touchStartX = null;
        });

        goTo(0);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var carousels = document.querySelectorAll('[data-dla-carousel]');
        for (var i = 0; i < carousels.length; i++) {
            initCarousel(carousels[i]);
        }
    });
})();
