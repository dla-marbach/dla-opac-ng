<?php

namespace Dla\DlaOpacNg\Tests\Functional\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidFunctionalPartialTestCase;
use Dla\DlaOpacNg\Tests\Support\SolrFixture;

/**
 * Prototyp: ListPager-Partial über Functional-Bootstrap (ohne Test-Override für f:link.action).
 */
class ListPagerFunctionalTest extends FluidFunctionalPartialTestCase
{
    /**
     * @test
     */
    public function rendersPageLinksAroundCurrentPageWithRealLinkActionViewHelper(): void
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
        self::assertStringContainsString('href="', $html);
        self::assertStringNotContainsString('href="#test-link"', $html);
        self::assertStringContainsString('rel="nofollow"', $html);
    }
}
