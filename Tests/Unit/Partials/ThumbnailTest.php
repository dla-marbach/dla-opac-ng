<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Rendert Resources/Private/Partials/MediaAccess/Thumbnail.html, das auf Detailseiten
 * (u.a. für AK-, HS- und BI-Sätze, siehe Display/Detail/Library/Details.html,
 * Manuscripts/Details.html und ImagesAndObjects/Details.html) je nach Zugriffsrecht
 * entweder den echten Vorschaubild-Thumbnail-Link oder den "Zugriff gesperrt"-Hinweis
 * (grüner geschlossener Kasten, CSS-Klasse "access-denied") anzeigt.
 *
 * Der Rechtehinweis darf nicht davon abhängen, ob das Solr-Feld
 * digitalObject_thumbnail_mv befüllt ist (Regressionstest für den Fall, dass bei
 * AK-Sätzen dieses Feld anders als bei HS/BI oft leer ist).
 */
class ThumbnailTest extends FluidPartialTestCase
{
    /** @var array<string, string|false> */
    private array $previousEnv = [];

    /** @var array<string, mixed> */
    private array $previousServer = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['campusRanges', 'sandboxRanges', 'staffRanges'] as $envName) {
            $this->previousEnv[$envName] = getenv($envName);
        }
        foreach (['REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR'] as $serverKey) {
            $this->previousServer[$serverKey] = $_SERVER[$serverKey] ?? null;
        }
        // Client außerhalb aller bekannten IP-Bereiche (weder Campus, Sandbox noch Staff).
        $this->setEnvironmentVariable('campusRanges', '10.0.0.0/8');
        $this->setEnvironmentVariable('sandboxRanges', '192.168.0.0/16');
        $this->setEnvironmentVariable('staffRanges', '172.16.0.0/12');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.1';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnv as $envName => $value) {
            $this->restoreEnvironmentVariable($envName, $value);
        }
        foreach ($this->previousServer as $serverKey => $value) {
            if ($value === null) {
                unset($_SERVER[$serverKey]);
                continue;
            }
            $_SERVER[$serverKey] = $value;
        }

        parent::tearDown();
    }

    private function restoreEnvironmentVariable(string $envName, string|false $value): void
    {
        if ($value === false) {
            putenv($envName);
            unset($_ENV[$envName], $_SERVER[$envName]);
            return;
        }

        putenv($envName . '=' . $value);
        $_ENV[$envName] = $value;
        $_SERVER[$envName] = $value;
    }

    private function setEnvironmentVariable(string $envName, string $value): void
    {
        putenv($envName . '=' . $value);
        $_ENV[$envName] = $value;
        $_SERVER[$envName] = $value;
    }

    /**
     * @param array<int, string> $accessLevels
     * @param array<int, string> $hyperlinks
     * @param array<int, string>|null $thumbnails
     * @param array<int, string> $extensions
     * @param array<string, mixed> $additionalArguments
     */
    private function renderThumbnail(array $accessLevels, array $hyperlinks, ?array $thumbnails, array $extensions = [], array $additionalArguments = []): string
    {
        $fields = [
            'digitalObject_accessLevel_mv' => $accessLevels,
            'digitalObject_hyperlink_mv' => $hyperlinks,
            'digitalObject_fileExtension_mv' => $extensions,
        ];
        if ($thumbnails !== null) {
            $fields['digitalObject_thumbnail_mv'] = $thumbnails;
        }

        return $this->renderPartial('MediaAccess/Thumbnail', [
            'document' => ['fields' => $fields],
        ] + $additionalArguments);
    }

    /**
     * @test
     */
    public function showsGreenboxForForbiddenCampusAccessEvenWithoutThumbnailField(): void
    {
        $html = $this->renderThumbnail(
            ['campus'],
            ['https://example.org/media/document.pdf'],
            null
        );

        self::assertStringContainsString('access-denied', $html);
        self::assertStringNotContainsString('<img', $html);
    }

    /**
     * @test
     */
    public function showsGreenboxForForbiddenSandboxAccessEvenWithoutThumbnailField(): void
    {
        $html = $this->renderThumbnail(
            ['sandbox'],
            ['https://example.org/media/document.pdf'],
            null
        );

        self::assertStringContainsString('access-denied', $html);
        self::assertStringNotContainsString('<img', $html);
    }

    /**
     * @test
     */
    public function showsGreenboxForForbiddenAccessInsteadOfThumbnailWhenThumbnailFieldIsSet(): void
    {
        $html = $this->renderThumbnail(
            ['campus'],
            ['https://example.org/media/document.pdf'],
            ['https://example.org/media/document_thumb.jpg']
        );

        self::assertStringContainsString('access-denied', $html);
        self::assertStringNotContainsString('src="https://example.org/media/document_thumb.jpg"', $html);
    }

    /**
     * @test
     */
    public function showsThumbnailImageForPublicAccessWhenThumbnailFieldIsSet(): void
    {
        $html = $this->renderThumbnail(
            ['public'],
            ['https://example.org/media/document.pdf'],
            ['https://example.org/media/document_thumb.jpg']
        );

        self::assertStringNotContainsString('access-denied', $html);
        self::assertStringContainsString('src="https://example.org/media/document_thumb.jpg"', $html);
        // Objekte mit Vorschaubild stehen im Abschnitt "Digitales Objekt", das Vorschaubild springt dorthin
        self::assertStringContainsString('href="#digitales-objekt"', $html);
    }

    /**
     * @test
     */
    public function viewerDigitisationWithThumbnailJumpsToDigitalObjectSection(): void
    {
        // z.B. Handschriften: Link in den Viewer ohne Dateiendung
        $html = $this->renderThumbnail(
            ['public'],
            ['https://digital.example.org/viewer/fullscreen/HS00242238/?media_access=public'],
            ['https://digital.example.org/viewer/api/v1/records/HS00242238/files/images/HS_00242238_0001.tif/full/!480,480/0/default.jpg']
        );

        self::assertStringContainsString('href="#digitales-objekt"', $html);
        self::assertStringContainsString('alt="To the digital object: Digitised copy"', $html);
        self::assertStringContainsString('bel-papiere', $html);
    }

    /**
     * @test
     */
    public function rendersNothingForPublicAccessWithoutThumbnailField(): void
    {
        $html = $this->renderThumbnail(
            ['public'],
            ['https://example.org/media/document.pdf'],
            null
        );

        self::assertStringNotContainsString('access-denied', $html);
        self::assertStringNotContainsString('<img', $html);
    }

    /**
     * @test
     */
    public function showsFirstAccessibleThumbnailWhenFirstObjectIsForbidden(): void
    {
        $html = $this->renderThumbnail(
            ['campus', 'public'],
            ['https://example.org/media/front.pdf', 'https://example.org/media/back.pdf'],
            ['https://example.org/media/front_thumb.jpg', 'https://example.org/media/back_thumb.jpg'],
            ['pdf', 'pdf']
        );

        self::assertStringNotContainsString('access-denied', $html);
        self::assertStringNotContainsString('front_thumb.jpg', $html);
        self::assertStringContainsString('src="https://example.org/media/back_thumb.jpg"', $html);
    }

    /**
     * @test
     */
    public function thumbnailJumpsToDigitalObjectSectionWhenMediaFileIsPresent(): void
    {
        $html = $this->renderThumbnail(
            ['campus', 'public', '␣'],
            ['https://example.org/media/a.mp3', 'https://example.org/media/b.mp3', '␣'],
            ['https://example.org/media/a_thumb.png', 'https://example.org/media/b_thumb.png', '␣'],
            ['mp3', 'mp3', '␣']
        );

        self::assertStringNotContainsString('access-denied', $html);
        self::assertStringContainsString('href="#digitales-objekt"', $html);
        self::assertStringContainsString('src="https://example.org/media/b_thumb.png"', $html);
        self::assertStringNotContainsString('target="_blank"', $html);
    }

    /**
     * @test
     */
    public function resultListShowsFirstAccessibleThumbnailWhenFirstObjectIsForbidden(): void
    {
        $html = $this->renderPartial('MediaAccess/ThumbnailResult', [
            'document' => [
                'id' => 'AK00317796',
                'fields' => [
                    'digitalObject_accessLevel_mv' => ['campus', 'public', '␣'],
                    'digitalObject_hyperlink_mv' => ['https://example.org/media/a.mp3', 'https://example.org/media/b.mp3', '␣'],
                    'digitalObject_thumbnail_mv' => ['https://example.org/media/a_thumb.png', 'https://example.org/media/b_thumb.png', '␣'],
                ],
            ],
        ]);

        self::assertStringNotContainsString('a_thumb.png', $html);
        self::assertStringContainsString('src="https://example.org/media/b_thumb.png"', $html);
    }

    /**
     * @test
     */
    public function resultListShowsNoThumbnailWithoutAccessibleObject(): void
    {
        $html = $this->renderPartial('MediaAccess/ThumbnailResult', [
            'document' => [
                'id' => 'BI00019570',
                'fields' => [
                    'digitalObject_accessLevel_mv' => ['campus'],
                    'digitalObject_hyperlink_mv' => ['https://example.org/media/a.jpg'],
                    'digitalObject_thumbnail_mv' => ['https://example.org/media/a_thumb.jpg'],
                ],
            ],
        ]);

        self::assertStringNotContainsString('<img', $html);
        self::assertStringNotContainsString('ctg-ri-image', $html);
    }

    /**
     * @test
     */
    public function resultListShowsLaterThumbnailWhenFirstThumbnailIsPlaceholder(): void
    {
        $html = $this->renderPartial('MediaAccess/ThumbnailResult', [
            'document' => [
                'id' => 'AK01661671',
                'fields' => [
                    'digitalObject_accessLevel_mv' => ['␣', 'public', '␣'],
                    'digitalObject_hyperlink_mv' => ['␣', 'https://example.org/media/b.pdf', '␣'],
                    'digitalObject_thumbnail_mv' => ['␣', 'https://example.org/media/b_thumb.jpg', '␣'],
                ],
            ],
        ]);

        self::assertStringContainsString('<div class="ctg-ri-image">', $html);
        self::assertStringContainsString('src="https://example.org/media/b_thumb.jpg"', $html);
    }

    /**
     * @test
     */
    public function thumbnailOpensFileWhenCallerHasNoDigitalObjectSection(): void
    {
        // z.B. Display/Detail/Normdata/Werk.html
        $html = $this->renderThumbnail(
            ['public'],
            ['https://example.org/media/a.jpg'],
            ['https://example.org/media/a_thumb.jpg'],
            ['jpg'],
            ['withoutJump' => 1]
        );

        self::assertStringNotContainsString('#digitales-objekt', $html);
        self::assertStringContainsString('href="https://example.org/media/a.jpg"', $html);
        self::assertStringContainsString('src="https://example.org/media/a_thumb.jpg"', $html);
    }
}
