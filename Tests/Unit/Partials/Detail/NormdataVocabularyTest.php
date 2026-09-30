<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials\Detail;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Display/Detail/Normdata/Sachbegriffe, SachbegriffeRightColumn, Fachsystematik und Ketten: je ein
 * Normdatensatz (Schlagwort "Nachruf", Autorenschema-Stelle "A1. Forschung", Kette "Hesse / Siddhartha").
 */
class NormdataVocabularyTest extends FluidPartialTestCase
{
    /**
     * @test
     */
    public function sachbegriffShowsHeadingAndCounts(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Normdata/Sachbegriffe', 'TH00000031'));

        self::assertStringContainsString('Nachruf Deskriptor. - Sachschlagwort', $text);
        self::assertStringContainsString('Quelle gegen M, RSWK Anl. 6', $text);
        self::assertStringContainsString('Schlagwort Nachruf ... Printed Works (72) Pictures and Objects (0) Manuscripts (21) Audio and Video (0) Holdings (0)', $text);
        self::assertStringContainsString('Everything about this keyword (93)', $text);
    }

    /**
     * @test
     */
    public function sachbegriffRightColumnLinksGnd(): void
    {
        $html = $this->renderDetailPartial('Display/Detail/Normdata/SachbegriffeRightColumn', 'TH00000031');

        self::assertStringContainsString('href="http://d-nb.info/gnd/', $html);
    }

    /**
     * @test
     */
    public function fachsystematikShowsHierarchyAndCounts(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Normdata/Fachsystematik', 'SY00000015'));

        self::assertStringContainsString('A1. Forschung Autorenschema', $text);
        self::assertStringContainsString('Übergeordnete Systemstelle A. Literatur ÜBER Person', $text);
        self::assertStringContainsString('Untergeordnete Systemstelle A1.1. Bibliographien, Hilfsmittel', $text);
        self::assertStringContainsString('Autorenschemaketten?? (117)', $text);
        self::assertStringContainsString('Everything about this classification (0)', $text);
    }

    /**
     * @test
     */
    public function kettenShowsComponentsAndCounts(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Normdata/Ketten', 'SE00000121'));

        self::assertStringContainsString('Typ Kette Bibliothek', $text);
        self::assertStringContainsString('Person Hesse, Hermann (1877-1962) Autorenschema A5.5.3. Erzählende Prosa / Einzelne Werke', $text);
        self::assertStringContainsString('Printed Works (60) Audio and Video (5) Provenance Copies (0)', $text);
        self::assertStringContainsString('Everything about this chain (65)', $text);
    }
}
