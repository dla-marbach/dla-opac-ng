<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Rendert Resources/Private/Partials/Display/Detail/MediatypeIcon.html für die wichtigsten
 * Medientypen. Reine Icon-Logik ohne Solr-Abhängigkeit, daher mit handgeschriebenen Variablen.
 */
class MediatypeIconTest extends FluidPartialTestCase
{
    #[Test]
    public function rendersDigitalImageIcon(): void
    {
        $html = $this->renderPartial('Display/Detail/MediatypeIcon', [
            'filterType_mv' => ['Bilder und Objekte'],
            'filterDigital' => true,
            'filterMultipart' => false,
        ]);

        self::assertStringContainsString('bel-pcbild', $html);
    }

    #[Test]
    public function rendersNonDigitalManuscriptIcon(): void
    {
        $html = $this->renderPartial('Display/Detail/MediatypeIcon', [
            'filterType_mv' => ['Handschriften'],
            'filterDigital' => false,
            'filterMultipart' => false,
        ]);

        self::assertStringContainsString('Manuscripts', $html);
    }

    #[Test]
    public function rendersMultipartMarkerForPrintedMaterial(): void
    {
        $html = $this->renderPartial('Display/Detail/MediatypeIcon', [
            'filterType_mv' => ['Gedrucktes'],
            'filterDigital' => false,
            'filterMultipart' => true,
        ]);

        self::assertStringContainsString('bel-mag', $html);
        self::assertSame(2, substr_count($html, 'bel-mag'));
    }
}
