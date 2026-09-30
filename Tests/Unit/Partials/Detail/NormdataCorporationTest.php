<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials\Detail;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Display/Detail/Normdata/Corporation und KSRightColumn: ein Datensatz mit vielen Treffern (Cotta)
 * und einer mit fast keinen (Kunsthalle Verlag).
 */
class NormdataCorporationTest extends FluidPartialTestCase
{
    private const COTTA = 'KS00011400';
    private const KUNSTHALLE = 'KS00113059';

    /**
     * @test
     */
    public function rendersMasterDataAndCountsForCorporationWithManyHits(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Normdata/Corporation', self::COTTA));
        $name = "J.-G.-Cotta'sche Buchhandlung <Stuttgart> ...";

        self::assertStringContainsString("J.-G.-Cotta'sche Buchhandlung <Stuttgart> (1810-1889) Körperschaft", $text);
        self::assertStringContainsString("Predecessor J. G. Cotta'sche Buchhandlung <Tübingen> (1659-1810)", $text);
        self::assertStringContainsString('Time Gründung: 1810 Auflösung: 1889', $text);
        self::assertStringContainsString('by ' . $name . ' Printed Works (3) Manuscripts (69774) Pictures and Objects (0) Audio and Video (0)', $text);
        self::assertStringContainsString('to ' . $name . ' Printed Works (1) Manuscripts (15532)', $text);
        self::assertStringContainsString('about ' . $name . ' Printed Works (45) Manuscripts (1353) Pictures and Objects (0) Audio and Video (1) Holdings (0)', $text);
        self::assertStringContainsString('under ' . $name . ' Holdings (88) Pictures and Objects (0) Provenance Copies (6)', $text);
        self::assertStringContainsString('Everything about this corporation (85857)', $text);
    }

    /**
     * @test
     */
    public function rendersZeroCountsForCorporationWithoutHits(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Normdata/Corporation', self::KUNSTHALLE));

        self::assertStringContainsString('Kunsthalle Verlag', $text);
        self::assertStringContainsString('by Kunsthalle ... Printed Works (2) Manuscripts (0) Pictures and Objects (0) Audio and Video (0)', $text);
        self::assertStringContainsString('Everything about this corporation (0)', $text);
    }

    /**
     * @test
     */
    public function rightColumnOmitsWorkLinkWithoutWorksAndLinksGnd(): void
    {
        $html = $this->renderDetailPartial('Display/Detail/Normdata/KSRightColumn', self::COTTA);

        self::assertStringNotContainsString('Werke (', $html);
        self::assertStringContainsString('href="http://d-nb.info/gnd/5062045-9"', $html);
    }
}
