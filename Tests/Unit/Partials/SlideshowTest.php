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
}
