<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials\Detail;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Display/Detail/Normdata/Werk und WerkRightColumn mit einem Werk-Normdatensatz (Habilitationsschrift).
 */
class NormdataWerkTest extends FluidPartialTestCase
{
    private const WERK = 'AK01600972';

    /**
     * @test
     */
    public function rendersMasterDataFromDocument(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Normdata/Werk', self::WERK));

        self::assertStringContainsString('Title of the Work Die frühen Komödien Pierre Corneilles und das französische Theater um 1630', $text);
        self::assertStringContainsString('Creator Bürger, Peter (1936-2017) [Verfasser/Urheber]', $text);
        self::assertStringContainsString('Form of the Work Habilitationsschrift Time 1971 Language Deutsch', $text);
    }

    /**
     * @test
     */
    public function showsCountsOfEveryRelationBlock(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Normdata/Werk', self::WERK));

        self::assertStringContainsString('Primary Sources from', $text);
        self::assertMatchesRegularExpression('/\(Habilitationsschrift : 1971\) \.\.\. Printed Works \(0\) Manuscripts \(1\) Audio and Video \(0\) Translations from/', $text);
        self::assertMatchesRegularExpression('/\(Werk als Thema\) Printed Works \(0\) Manuscripts \(1\) Pictures and Objects \(0\) Audio and Video \(0\) Reviews of/', $text);
        self::assertStringContainsString('Possible further hits Printed Works (0) Manuscripts (0) Audio and Video (0)', $text);
        self::assertStringContainsString('Not the right category? (1)', $text);
    }

    /**
     * @test
     */
    public function rightColumnLinksGnd(): void
    {
        $html = $this->renderDetailPartial('Display/Detail/Normdata/WerkRightColumn', self::WERK);

        self::assertStringContainsString('href="http://d-nb.info/gnd/', $html);
    }
}
