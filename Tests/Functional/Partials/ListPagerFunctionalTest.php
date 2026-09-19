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

        preg_match_all('/<a\b[^>]*>/', $html, $anchorTags);
        $nofollowHrefs = [];
        foreach ($anchorTags[0] as $anchorTag) {
            if (!str_contains($anchorTag, 'rel="nofollow"')) {
                continue;
            }
            if (preg_match('/\bhref="([^"]+)"/', $anchorTag, $hrefMatch) === 1) {
                $nofollowHrefs[] = $hrefMatch[1];
            }
        }
        self::assertNotEmpty($nofollowHrefs, 'Expected pager links rendered by f:link.action with href + rel="nofollow".');
        foreach ($nofollowHrefs as $href) {
            self::assertNotSame('', trim($href));
            self::assertNotSame('#test-link', $href);
        }
        self::assertStringContainsString('/katalog', $html);
    }
}
