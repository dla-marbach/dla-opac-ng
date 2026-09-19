<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;
use Dla\DlaOpacNg\Tests\Support\SolrFixture;

/**
 * Rendert Resources/Private/Partials/Display/Result.html (Trefferlisten-Eintrag) mit einem
 * echten, über den Solr-Mock-Server geladenen Dokument (siehe Tests/Fixtures/Solr/cassettes/detail-person.json).
 */
class ResultTest extends FluidPartialTestCase
{
    private const STANDARD_FIELDS = [
        'title' => 'display',
        'filterType_mv' => 'filterType_mv',
        'displayName' => 'displayName',
        'display' => 'display',
        'displayAddition1' => 'displayAddition1',
        'displayAddition2' => 'displayAddition2',
    ];

    /**
     * @test
     */
    public function rendersPersonResultWithTitleAndDetailLink(): void
    {
        $document = SolrFixture::document('PE00000863');

        $html = $this->renderPartial('Display/Result', [
            'document' => $document,
            'results' => SolrFixture::select('id:(PE00000863)', 1),
            'config' => ['uid' => 1, 'jumpToID' => ''],
            'settings' => ['standardFields' => self::STANDARD_FIELDS],
        ]);

        self::assertStringContainsString('Goethe, Johann Wolfgang von', $html);
        self::assertStringContainsString('ctg-result-item', $html);
        self::assertStringContainsString('ctg-result-normdata', $html);
    }
}
