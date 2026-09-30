<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials\Detail;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Display/Detail/Normdata/Person und PersonRightColumn mit dem Goethe-Normdatensatz. Die Zähler der
 * Teaser-Blöcke stammen aus den Unterabfragen (dla:solveQuery + dla:countFromSolr) und werden aus
 * den Cassetten in Tests/Fixtures/Solr/cassettes/subqueries/ beantwortet.
 */
class NormdataPersonTest extends FluidPartialTestCase
{
    private const GOETHE = 'PE00000863';

    /**
     * @test
     */
    public function rendersHeadAndMasterDataFromDocument(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Normdata/Person', self::GOETHE));

        self::assertStringContainsString('Goethe, Johann Wolfgang von (1749-1832) Schriftsteller, Politiker, Naturwissenschaftler', $text);
        self::assertStringContainsString('Birth and Death Dates 28.08.1749 – 22.03.1832', $text);
        self::assertStringContainsString('Place of birth Frankfurt <Main>', $text);
        self::assertStringContainsString('Place of death Weimar', $text);
    }

    /**
     * @test
     */
    public function showsCountsOfEveryRelationBlock(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Normdata/Person', self::GOETHE));
        $name = 'Goethe, Johann Wolfgang von ...';

        self::assertStringContainsString('by ' . $name . ' Printed Works (5435) Manuscripts (556) Pictures and Objects (0) Audio and Video (2342) Information (2)', $text);
        self::assertStringContainsString('to ' . $name . ' Printed Works (254) Manuscripts (299) Pictures and Objects (0) Audio and Video (19) Information (0)', $text);
        self::assertStringContainsString('about ' . $name . ' Printed Works (21217) Manuscripts (709) Pictures and Objects (103) Audio and Video (2544) Information (3) Holdings (3)', $text);
        self::assertStringContainsString('under ' . $name . ' Holdings (11) Pictures and Objects (0) Provenance Copies (0)', $text);
        self::assertStringContainsString('Possible further hits Printed Works (0) Manuscripts (23) Pictures and Objects (2) Audio and Video (0) Holdings (0) Information (0)', $text);
        self::assertStringContainsString('Everything about this person (31172)', $text);
    }

    /**
     * @test
     */
    public function rightColumnShowsWorkCountAndExternalLinks(): void
    {
        $html = $this->renderDetailPartial('Display/Detail/Normdata/PersonRightColumn', self::GOETHE);
        $text = self::visibleText($html);

        self::assertStringContainsString('Document links Werke (923)', $text);
        self::assertStringContainsString('Wikimedia Commons', $text);
        self::assertStringContainsString('href="https://www.wikidata.org/wiki/', $html);
        self::assertStringContainsString('href="http://d-nb.info/gnd/', $html);
    }
}
