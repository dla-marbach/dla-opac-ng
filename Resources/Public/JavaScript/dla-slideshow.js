/**
 * Generisches, abhaengigkeitsfreies Slideshow-/Carousel-Widget fuer die Bildergalerie
 * eines digitalen Objekts (siehe Resources/Private/Partials/MediaAccess/Slideshow.html).
 * Enthaelt ausschliesslich Bilder, auf die der aktuelle Nutzer bereits Zugriff hat -
 * gesperrte Bilder werden serverseitig nicht in die Slideshow aufgenommen (siehe
 * Classes/ViewHelpers/MediaPlayerViewHelper.php), sondern weiterhin ueber den
 * bestehenden Link-/Rechtehinweis dargestellt.
 */
(function () {
    'use strict';

    function initSlideshow(slideshow) {
        var track = slideshow.querySelector('.dla-slideshow-track');
        var slides = slideshow.querySelectorAll('.dla-slideshow-slide');
        var prevButton = slideshow.querySelector('.dla-slideshow-prev');
        var nextButton = slideshow.querySelector('.dla-slideshow-next');
        var dotsContainer = slideshow.querySelector('.dla-slideshow-dots');

        if (!track || slides.length <= 1) {
            return;
        }

        var currentIndex = 0;

        function goTo(index) {
            currentIndex = (index + slides.length) % slides.length;
            track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';

            for (var s = 0; s < slides.length; s++) {
                var isActive = s === currentIndex;
                slides[s].setAttribute('aria-hidden', isActive ? 'false' : 'true');
                var slideLink = slides[s].querySelector('a');
                if (slideLink) {
                    slideLink.setAttribute('tabindex', isActive ? '0' : '-1');
                }
            }

            if (dotsContainer) {
                var dots = dotsContainer.querySelectorAll('.dla-slideshow-dot');
                for (var i = 0; i < dots.length; i++) {
                    var isCurrent = i === currentIndex;
                    dots[i].classList.toggle('is-active', isCurrent);
                    if (isCurrent) {
                        dots[i].setAttribute('aria-current', 'true');
                    } else {
                        dots[i].removeAttribute('aria-current');
                    }
                }
            }
        }

        if (dotsContainer) {
            for (var d = 0; d < slides.length; d++) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'dla-slideshow-dot' + (d === 0 ? ' is-active' : '');
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
        slideshow.addEventListener('touchstart', function (event) {
            touchStartX = event.touches[0].clientX;
        }, { passive: true });

        slideshow.addEventListener('touchend', function (event) {
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
        var slideshows = document.querySelectorAll('[data-dla-slideshow]');
        for (var i = 0; i < slideshows.length; i++) {
            initSlideshow(slideshows[i]);
        }
    });
})();
