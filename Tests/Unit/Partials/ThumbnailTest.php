<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Rendert Resources/Private/Partials/MediaAccess/Thumbnail.html (generisches Carousel für
 * mehrere digitale Objekte in der Detailansicht, siehe auch Classes/ViewHelpers/MediaPlayerViewHelper.php
 * für die Berechtigungsprüfung anhand von digitalObject_accessLevel_mv).
 */
class ThumbnailTest extends FluidPartialTestCase
{
    /**
     * @test
     */
    public function rendersSingleThumbnailWithoutCarouselControls(): void
    {
        $document = [
            'fields' => [
                'digitalObject_accessLevel_mv' => ['public'],
                'digitalObject_hyperlink_mv' => ['https://example.org/object.jpg'],
                'digitalObject_thumbnail_mv' => ['https://example.org/thumb.jpg'],
            ],
        ];

        $html = $this->renderPartial('MediaAccess/Thumbnail', [
            'document' => $document,
        ]);

        self::assertStringContainsString('dla-carousel', $html);
        self::assertStringContainsString('src="https://example.org/thumb.jpg"', $html);
        self::assertStringNotContainsString('dla-carousel-prev', $html);
        self::assertStringNotContainsString('dla-carousel-dots', $html);
    }

    /**
     * @test
     */
    public function rendersCarouselWithPlaceholderForForbiddenImage(): void
    {
        $document = [
            'fields' => [
                'digitalObject_accessLevel_mv' => ['public', 'campus'],
                'digitalObject_hyperlink_mv' => [
                    'https://example.org/public.jpg',
                    'https://example.org/campus.jpg',
                ],
                'digitalObject_thumbnail_mv' => [
                    'https://example.org/public-thumb.jpg',
                    'https://example.org/campus-thumb.jpg',
                ],
            ],
        ];

        $html = $this->renderPartial('MediaAccess/Thumbnail', [
            'document' => $document,
        ]);

        // öffentliches Bild wird als anklickbares Thumbnail angezeigt
        self::assertStringContainsString('src="https://example.org/public-thumb.jpg"', $html);
        self::assertStringContainsString('href="https://example.org/public.jpg"', $html);

        // Campus-Bild ist von hier aus nicht freigegeben (keine passende IP-Range konfiguriert)
        // und wird daher als Platzhalter statt als echtes Thumbnail angezeigt.
        self::assertStringNotContainsString('src="https://example.org/campus-thumb.jpg"', $html);
        self::assertStringContainsString('access-denied', $html);

        // bei mehreren Bildern werden die Carousel-Steuerelemente gerendert
        self::assertStringContainsString('dla-carousel-prev', $html);
        self::assertStringContainsString('dla-carousel-next', $html);
        self::assertStringContainsString('dla-carousel-dots', $html);
    }
}
