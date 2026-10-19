<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Rendert Resources/Private/Partials/MediaAccess/Slideshow.html für nicht gesperrte
 * Bilder, wie sie von MediaPlayerViewHelper::buildMediaData() in "images" erzeugt
 * werden. Gesperrte Bilder werden von buildMediaData() bereits herausgefiltert
 * (bleiben in "links"), das Partial selbst bekommt also nie Platzhalter zu sehen.
 */
class SlideshowTest extends FluidPartialTestCase
{
    /**
     * @test
     */
    public function rendersNothingWhenNoImagesArePresent(): void
    {
        $html = $this->renderPartial('MediaAccess/Slideshow', [
            'id' => 'slideshow-empty',
            'images' => [],
        ]);

        self::assertStringNotContainsString('dla-slideshow', $html);
    }

    /**
     * @test
     */
    public function rendersSingleImageWithoutNavigationControls(): void
    {
        $html = $this->renderPartial('MediaAccess/Slideshow', [
            'id' => 'slideshow-1',
            'images' => [
                [
                    'url' => 'https://example.org/media/picture.jpg',
                    'thumbnail' => 'https://example.org/media/picture_thumb.jpg',
                    'display' => 'Ein Bild',
                ],
            ],
        ]);

        self::assertStringContainsString('id="slideshow-1"', $html);
        self::assertStringContainsString('data-dla-slideshow', $html);
        // fokussierbar für die Bedienung mit Cursortasten (dla-slideshow.js)
        self::assertStringContainsString('tabindex="0"', $html);
        self::assertStringContainsString('aria-roledescription="carousel"', $html);
        self::assertStringContainsString('aria-label="Image gallery"', $html);
        self::assertStringContainsString('data-label-image="Image %s of %s"', $html);
        self::assertStringContainsString('src="https://example.org/media/picture_thumb.jpg"', $html);
        self::assertStringContainsString('href="https://example.org/media/picture.jpg"', $html);
        self::assertStringNotContainsString('dla-slideshow-prev', $html);
        self::assertStringNotContainsString('dla-slideshow-next', $html);
        self::assertStringNotContainsString('dla-slideshow-dots', $html);
    }

    /**
     * @test
     */
    public function rendersMultipleImagesWithNavigationControls(): void
    {
        $html = $this->renderPartial('MediaAccess/Slideshow', [
            'id' => 'slideshow-2',
            'images' => [
                ['url' => 'https://example.org/media/a.jpg', 'thumbnail' => 'https://example.org/media/a_thumb.jpg', 'display' => 'Bild A'],
                ['url' => 'https://example.org/media/b.jpg', 'thumbnail' => 'https://example.org/media/b_thumb.jpg', 'display' => 'Bild B'],
            ],
        ]);

        self::assertSame(2, substr_count($html, 'dla-slideshow-slide'));
        self::assertStringContainsString('dla-slideshow-prev', $html);
        self::assertStringContainsString('dla-slideshow-next', $html);
        self::assertStringContainsString('dla-slideshow-dots', $html);
    }

    /**
     * @test
     */
    public function onlyFirstSlideIsInitiallyVisibleToAssistiveTechnologyAndTabOrder(): void
    {
        $html = $this->renderPartial('MediaAccess/Slideshow', [
            'id' => 'slideshow-4',
            'images' => [
                ['url' => 'https://example.org/media/a.jpg', 'thumbnail' => 'https://example.org/media/a_thumb.jpg', 'display' => 'Bild A'],
                ['url' => 'https://example.org/media/b.jpg', 'thumbnail' => 'https://example.org/media/b_thumb.jpg', 'display' => 'Bild B'],
            ],
        ]);

        self::assertStringContainsString('aria-hidden="false"', $html);
        self::assertStringContainsString('aria-hidden="true"', $html);
        self::assertStringContainsString('tabindex="0"', $html);
        self::assertStringContainsString('tabindex="-1"', $html);
    }

    /**
     * @test
     */
    public function rendersTitleFromDisplayAsCaptionBelowEachImage(): void
    {
        $html = $this->renderPartial('MediaAccess/Slideshow', [
            'id' => 'slideshow-5',
            'images' => [
                ['url' => 'https://example.org/media/a.jpg', 'thumbnail' => 'https://example.org/media/a_thumb.jpg', 'display' => 'Bild A'],
                ['url' => 'https://example.org/media/b.jpg', 'thumbnail' => 'https://example.org/media/b_thumb.jpg', 'display' => 'Bild B'],
            ],
        ]);

        self::assertStringContainsString('<div class="dla-slideshow-caption">Bild A</div>', $html);
        self::assertStringContainsString('<div class="dla-slideshow-caption">Bild B</div>', $html);
        // Bildunterschrift steht innerhalb der Slide, nach dem Bild-Link
        self::assertMatchesRegularExpression('#a\.jpg"[^>]*>.*?</a>\s*<div class="dla-slideshow-caption">Bild A</div>#s', $html);
    }

    /**
     * @test
     */
    public function rendersNoCaptionWhenDisplayIsMissingOrPlaceholder(): void
    {
        $html = $this->renderPartial('MediaAccess/Slideshow', [
            'id' => 'slideshow-6',
            'images' => [
                ['url' => 'https://example.org/media/a.jpg', 'thumbnail' => null, 'display' => '␣'],
                ['url' => 'https://example.org/media/b.jpg', 'thumbnail' => null, 'display' => null],
            ],
        ]);

        self::assertStringNotContainsString('dla-slideshow-caption', $html);
    }

    /**
     * @test
     */
    public function fallsBackToFullImageWhenNoThumbnailIsAvailable(): void
    {
        $html = $this->renderPartial('MediaAccess/Slideshow', [
            'id' => 'slideshow-3',
            'images' => [
                ['url' => 'https://example.org/media/picture.jpg', 'thumbnail' => null, 'display' => 'Ein Bild'],
            ],
        ]);

        self::assertStringContainsString('src="https://example.org/media/picture.jpg"', $html);
    }

    /**
     * @test
     */
    public function viewerDigitisationShowsSignatureAsCaptionAndOpenInViewerAction(): void
    {
        $html = $this->renderPartial('MediaAccess/Slideshow', [
            'id' => 'slideshow-7',
            'images' => [
                [
                    'url' => 'https://digital.example.org/viewer/fullscreen/HS00242238/',
                    'thumbnail' => 'https://digital.example.org/viewer/api/HS00242238.jpg',
                    'display' => 'HS00242238',
                    'ext' => null,
                    'kind' => 'viewer',
                ],
            ],
        ]);

        self::assertStringContainsString('<div class="dla-slideshow-caption">HS00242238</div>', $html);
        self::assertStringContainsString('class="dla-slideshow-action"', $html);
        self::assertMatchesRegularExpression('#<div class="dla-slideshow-action">\s*<a href="https://digital\.example\.org/viewer/fullscreen/HS00242238/" target="_blank" tabindex="0">\s*Open in viewer#', $html);
        self::assertStringContainsString('(opens in a new window)', $html);
        // Bild-Link mit gleichem Ziel ist für Tastatur und Screenreader ausgeblendet
        self::assertMatchesRegularExpression('#<a href="https://digital\.example\.org/viewer/fullscreen/HS00242238/" target="_blank" tabindex="-1" aria-hidden="true" data-dla-slideshow-skip>\s*<img#', $html);
    }

    /**
     * @test
     */
    public function fileWithThumbnailShowsOpenFileActionWithExtension(): void
    {
        $html = $this->renderPartial('MediaAccess/Slideshow', [
            'id' => 'slideshow-8',
            'images' => [
                [
                    'url' => 'https://example.org/media/book.epub',
                    'thumbnail' => 'https://example.org/media/book-thumbnail.png',
                    'display' => 'Elektronische Ressource: Zugang',
                    'ext' => 'epub',
                    'kind' => 'file',
                ],
            ],
        ]);

        self::assertStringContainsString('Open file (EPUB)', $html);
    }

    /**
     * @test
     */
    public function imageFileHasNoActionLine(): void
    {
        $html = $this->renderPartial('MediaAccess/Slideshow', [
            'id' => 'slideshow-9',
            'images' => [
                ['url' => 'https://example.org/media/a.jpg', 'thumbnail' => 'https://example.org/media/a_thumb.jpg', 'display' => 'Bild A', 'ext' => 'jpg', 'kind' => 'image'],
            ],
        ]);

        self::assertStringNotContainsString('dla-slideshow-action', $html);
        self::assertStringNotContainsString('data-dla-slideshow-skip', $html);
    }
}
