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
     */
    private function renderThumbnail(array $accessLevels, array $hyperlinks, ?array $thumbnails): string
    {
        $fields = [
            'digitalObject_accessLevel_mv' => $accessLevels,
            'digitalObject_hyperlink_mv' => $hyperlinks,
        ];
        if ($thumbnails !== null) {
            $fields['digitalObject_thumbnail_mv'] = $thumbnails;
        }

        return $this->renderPartial('MediaAccess/Thumbnail', [
            'document' => ['fields' => $fields],
        ]);
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
        self::assertStringContainsString('href="https://example.org/media/document.pdf"', $html);
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
}
