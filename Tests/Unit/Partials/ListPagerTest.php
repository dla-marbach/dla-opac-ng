<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;
use Dla\DlaOpacNg\Tests\Support\SolrFixture;

/**
 * Rendert Resources/Private/Partials/Pager/ListPager.html mit einem echten Solr-Trefferset
 * (viele Treffer für "goethe", siehe Tests/Fixtures/Solr/cassettes/resultlist-goethe.json).
 */
class ListPagerTest extends FluidPartialTestCase
{
    /**
     * @test
     */
    public function rendersPageLinksAroundCurrentPage(): void
    {
        $results = SolrFixture::select('goethe', 5);
        self::assertGreaterThan(100, $results->getNumFound());

        $html = $this->renderPartial('Pager/ListPager', [
            'results' => $results,
            'arguments' => ['page' => 3],
            'settings' => ['paging' => ['perPage' => 20], 'jumpToID' => ''],
        ]);

        self::assertStringContainsString('ctg-pager', $html);
        self::assertStringContainsString('ctg-bu-active', $html);
        self::assertStringNotContainsString('ctg-bu-disable', $html);
    }

    /**
     * @test
     */
    public function disablesPreviousLinkOnFirstPage(): void
    {
        $results = SolrFixture::select('goethe', 5);

        $html = $this->renderPartial('Pager/ListPager', [
            'results' => $results,
            'arguments' => ['page' => 1],
            'settings' => ['paging' => ['perPage' => 20], 'jumpToID' => ''],
        ]);

        self::assertStringContainsString('ctg-bu-disable', $html);
    }
}
