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

    /**
     * @test
     */
    public function doesNotLinkLastPageBeyondPageTen(): void
    {
        $results = SolrFixture::select('goethe', 5);
        $lastPage = (int)ceil($results->getNumFound() / 20);
        self::assertGreaterThan(10, $lastPage);

        $html = $this->renderPartial('Pager/ListPager', [
            'results' => $results,
            'arguments' => ['page' => 1],
            'settings' => ['paging' => ['perPage' => 20], 'jumpToID' => ''],
        ]);

        self::assertStringContainsString('<a class="ctg-button">' . $lastPage . '</a>', $html);
    }

    /**
     * @test
     */
    public function linksLastPageUpToPageTen(): void
    {
        $results = SolrFixture::select('goethe', 5);
        $perPage = (int)ceil($results->getNumFound() / 7);

        $html = $this->renderPartial('Pager/ListPager', [
            'results' => $results,
            'arguments' => ['page' => 1],
            'settings' => ['paging' => ['perPage' => $perPage], 'jumpToID' => ''],
        ]);

        self::assertStringContainsString('7', $html);
        self::assertStringNotContainsString('<a class="ctg-button">7</a>', $html);
    }

    /**
     * @test
     */
    public function doesNotLinkPagesBeyondMaxResultWindowAndShowsHint(): void
    {
        $results = SolrFixture::select('goethe', 5);
        self::assertGreaterThan(100, $results->getNumFound());

        // 100 Treffer erlaubt, 20 pro Seite => höchstens Seite 5
        $html = $this->renderPartial('Pager/ListPager', [
            'results' => $results,
            'arguments' => ['page' => 4],
            'config' => ['count' => 20, 'maxResultWindow' => 100],
            'settings' => ['paging' => ['perPage' => 20], 'jumpToID' => ''],
        ]);

        self::assertStringContainsString('ctg-pager-limit-hint', $html);
        self::assertStringContainsString('Please refine your search', $html);
        // Seite 5 (letzte erlaubte) ist verlinkt, Seite 6 taucht nicht auf
        self::assertMatchesRegularExpression('#<a[^>]*rel="nofollow"[^>]*>\s*5\s*</a>#', $html);
        self::assertDoesNotMatchRegularExpression('#>\s*6\s*</a>#', $html);
    }

    /**
     * @test
     */
    public function lastAllowedPageHasNoNextLink(): void
    {
        $results = SolrFixture::select('goethe', 5);

        $html = $this->renderPartial('Pager/ListPager', [
            'results' => $results,
            'arguments' => ['page' => 5],
            'config' => ['count' => 20, 'maxResultWindow' => 100],
            'settings' => ['paging' => ['perPage' => 20], 'jumpToID' => ''],
        ]);

        self::assertStringContainsString('<a class="ctg-button ctg-bu-disable"><span class="icon bel-pfeil-l01"></span></a>', $html);
    }

    /**
     * @test
     */
    public function usesEffectiveCountForPageCalculation(): void
    {
        $results = SolrFixture::select('goethe', 5);

        // 100 pro Seite bei einem Fenster von 1000 => höchstens 10 Seiten, obwohl perPage 20 ist
        $html = $this->renderPartial('Pager/ListPager', [
            'results' => $results,
            'arguments' => ['page' => 9],
            'config' => ['count' => 100, 'maxResultWindow' => 1000],
            'settings' => ['paging' => ['perPage' => 20], 'jumpToID' => ''],
        ]);

        self::assertMatchesRegularExpression('#>\s*10\s*</a>#', $html);
        self::assertDoesNotMatchRegularExpression('#>\s*11\s*</a>#', $html);
    }

    /**
     * @test
     */
    public function showsNoHintWithoutLimit(): void
    {
        $results = SolrFixture::select('goethe', 5);

        $html = $this->renderPartial('Pager/ListPager', [
            'results' => $results,
            'arguments' => ['page' => 1],
            'settings' => ['paging' => ['perPage' => 20], 'jumpToID' => ''],
        ]);

        self::assertStringNotContainsString('ctg-pager-limit-hint', $html);
    }
}
