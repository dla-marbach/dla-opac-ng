<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials\Detail;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;
use Dla\DlaOpacNg\Tests\Support\SolrFixture;
use Solarium\QueryType\Select\Result\Document;

/**
 * Display/Detail/Manuscripts/Access und Details für eine Handschrift in einem Bibliotheksexemplar
 * (HS00000091: location_mv = Bibliothek, verknüpft mit AK00261435 / AU00651865). Die Gegenfälle
 * ändern Felder desselben Dokuments im Speicher.
 */
class ManuscriptInLibraryItemTest extends FluidPartialTestCase
{
    private const MANUSCRIPT = 'HS00000091';

    /**
     * @test
     */
    public function accessDisablesOrderingAndShowsHint(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Manuscripts/Access', self::MANUSCRIPT));

        self::assertStringContainsString('Not orderable', $text);
        self::assertStringContainsString('This manuscript is located in or on a library copy.', $text);
        self::assertStringNotContainsString('Order now', $text);
    }

    /**
     * @test
     */
    public function accessKeepsOrderingForOtherLocation(): void
    {
        $text = self::visibleText($this->renderModified('Display/Detail/Manuscripts/Access', ['location_mv' => ['Magazin']]));

        self::assertStringContainsString('Order now', $text);
        self::assertStringNotContainsString('library copy', $text);
    }

    /**
     * @test
     */
    public function accessKeepsOrderingWithoutLibraryLink(): void
    {
        $text = self::visibleText($this->renderModified('Display/Detail/Manuscripts/Access', ['item_manifestation_id_mv' => null]));

        self::assertStringContainsString('Order now', $text);
        self::assertStringNotContainsString('library copy', $text);
    }

    /**
     * @test
     */
    public function detailsLinkToLibraryItemPermalink(): void
    {
        $html = $this->renderDetailPartial('Display/Detail/Manuscripts/Details', self::MANUSCRIPT);

        self::assertMatchesRegularExpression(
            '/<a [^>]*data-arguments="\{&quot;id&quot;:&quot;AK00261435&quot;,&quot;au&quot;:&quot;AU00651865&quot;\}"[^>]*data-section="tabaccess"[^>]*>\s*Aus Wut/',
            $html
        );
    }

    private function renderModified(string $partial, array $changedFields): string
    {
        $variables = SolrFixture::detailVariables(self::MANUSCRIPT);
        $variables['document'] = new Document(array_merge($variables['document']->getFields(), $changedFields));

        return $this->renderPartial($partial, $variables);
    }
}
