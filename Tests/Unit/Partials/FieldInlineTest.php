<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;
use Dla\DlaOpacNg\Tests\Support\SolrFixture;

/**
 * Rendert Resources/Private/Partials/Display/Field/Inline.html (und darüber transitiv
 * Display/Field/General.html und Display/Field/Content.html) mit einem echten, über den
 * Solr-Mock-Server geladenen Dokument.
 */
class FieldInlineTest extends FluidPartialTestCase
{
    /**
     * @test
     */
    public function rendersSingleValuedFieldWithoutLink(): void
    {
        $document = SolrFixture::document('PE00000863');
        $results = SolrFixture::select('id:(PE00000863)', 1);

        $html = $this->renderPartial('Display/Field/Inline', [
            'document' => $document,
            'results' => $results,
            'field' => 'display',
            'prefixString' => '',
            'separator' => '',
            'linkFieldContent' => false,
            'linkFacets' => false,
            'config' => [],
        ]);

        self::assertStringContainsString('Goethe, Johann Wolfgang von', $html);
        self::assertStringContainsString('field-display', $html);
    }

    /**
     * @test
     */
    public function rendersMultiValuedFieldAsGroupedSpans(): void
    {
        $document = SolrFixture::document('PE00000863');
        $results = SolrFixture::select('id:(PE00000863)', 1);

        $html = $this->renderPartial('Display/Field/Inline', [
            'document' => $document,
            'results' => $results,
            'field' => 'filterType_mv',
            'prefixString' => '',
            'separator' => '; ',
            'linkFieldContent' => false,
            'linkFacets' => false,
            'config' => [],
        ]);

        self::assertStringContainsString('field-filterType_mv-group', $html);
        self::assertStringContainsString('Normdaten', $html);
    }
}
