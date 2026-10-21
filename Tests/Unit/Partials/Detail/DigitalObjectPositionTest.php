<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials\Detail;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;
use Dla\DlaOpacNg\Tests\Support\SolrFixture;
use Solarium\QueryType\Select\Result\Document;

/**
 * Display/Detail/*\/Details: der Abschnitt "Digitales Objekt" (id="digitales-objekt") ist in allen
 * Datenbeständen der letzte Abschnitt im Reiter "Details", damit Player und Bildergalerie die
 * Metadaten nicht unterbrechen.
 */
class DigitalObjectPositionTest extends FluidPartialTestCase
{
    /**
     * @test
     * @dataProvider detailsWithDigitalObject
     */
    public function digitalObjectIsLastSectionOfDetailsTab(string $partial, string $id): void
    {
        $this->assertDigitalObjectIsLastSection($this->renderDetailPartial($partial, $id));
    }

    public static function detailsWithDigitalObject(): array
    {
        return [
            'Bibliothek' => ['Display/Detail/Library/Details', 'AK00983699'],
            'Bilder und Objekte' => ['Display/Detail/ImagesAndObjects/Details', 'BI00005210'],
            'Handschriften' => ['Display/Detail/Manuscripts/Details', 'HS00242238'],
        ];
    }

    /**
     * @test
     */
    public function digitalObjectIsLastSectionOfInventoryDetailsTab(): void
    {
        // Keine Bestands-Fixture mit Digitalisat vorhanden: Bestand BF00025111 um ein PDF ergänzen
        $variables = SolrFixture::detailVariables('BF00025111');
        $variables['document'] = new Document(array_merge($variables['document']->getFields(), [
            'digitalObject_hyperlink_mv' => ['https://digital.dla-marbach.de/files/test/public/test.pdf'],
            'digitalObject_display_mv' => ['Findbuch'],
            'digitalObject_fileExtension_mv' => ['pdf'],
            'digitalObject_accessLevel_mv' => ['public'],
        ]));

        $this->assertDigitalObjectIsLastSection($this->renderPartial('Display/Detail/Inventory/Details', $variables));
    }

    private function assertDigitalObjectIsLastSection(string $html): void
    {
        $detailStart = strpos($html, '<div id="detail"');
        self::assertNotFalse($detailStart, 'Reiter "Details" fehlt');
        $detail = substr($html, $detailStart);

        $parts = explode('id="digitales-objekt"', $detail);
        self::assertCount(2, $parts, 'Abschnitt "Digitales Objekt" fehlt im Reiter "Details"');
        self::assertStringContainsString('class="ctg-dtvt-label"', $parts[0]);

        // Nach dem Abschnitt folgen keine weiteren Felder (Zeilen mit Label bzw. dt/dd-Paare)
        self::assertSame(1, substr_count($parts[1], 'class="ctg-dtvt-label"'));
        self::assertStringNotContainsString('<dt', $parts[1]);
    }
}
