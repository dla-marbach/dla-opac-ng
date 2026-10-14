<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Rendert Resources/Private/Partials/MediaAccess/TeaserJump.html: Vorschaubild mit
 * Schild, das den Inhalt des Abschnitts "Digitales Objekt" zusammenfasst.
 */
class TeaserJumpTest extends FluidPartialTestCase
{
    private const TEASER = ['url' => 'https://example.org/media/a.jpg', 'thumbnail' => 'https://example.org/media/a_thumb.jpg', 'jump' => true];

    /**
     * @test
     */
    public function singleKindShowsIconAndText(): void
    {
        $html = $this->renderPartial('MediaAccess/TeaserJump', [
            'teaser' => self::TEASER,
            'summary' => [['type' => 'image', 'count' => 4]],
        ]);

        self::assertStringContainsString('href="#digitales-objekt"', $html);
        self::assertStringContainsString('src="https://example.org/media/a_thumb.jpg"', $html);
        self::assertStringContainsString('alt="To the digital object: 4 images"', $html);
        self::assertSame(1, substr_count($html, 'class="dla-teaser-badge-entry"'));
        self::assertStringContainsString('bel-kacheln', $html);
        self::assertMatchesRegularExpression('#</span>\s*4 images\s*</span>#', $html);
    }

    /**
     * @test
     */
    public function mixedKindsShowIconAndCountPerKindAndFullTextAsAlternative(): void
    {
        $html = $this->renderPartial('MediaAccess/TeaserJump', [
            'teaser' => self::TEASER,
            'summary' => [
                ['type' => 'video', 'count' => 1],
                ['type' => 'audio', 'count' => 2],
                ['type' => 'image', 'count' => 4],
                ['type' => 'link', 'count' => 1],
            ],
        ]);

        self::assertStringContainsString('alt="To the digital object: Video, 2 audio files, 4 images, Link"', $html);
        self::assertStringContainsString('title="To the digital object: Video, 2 audio files, 4 images, Link"', $html);
        self::assertSame(4, substr_count($html, 'class="dla-teaser-badge-entry"'));
        foreach (['bel-film', 'bel-ton', 'bel-kacheln', 'bel-link02'] as $icon) {
            self::assertStringContainsString($icon, $html);
        }
        self::assertMatchesRegularExpression('#bel-ton"></span>\s*2\s*</span>#', $html);
        self::assertStringNotContainsString('4 images</span>', $html);
    }
}
