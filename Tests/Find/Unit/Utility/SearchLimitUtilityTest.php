<?php

namespace Dla\Find\Tests\Unit\Utility;

use Dla\Find\Utility\SearchLimitUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Grenzfälle der Such-Limits (Deep Paging, leere Suche, Anzahl aktiver Filter).
 */
class SearchLimitUtilityTest extends UnitTestCase
{
    private function settings(array $limits = []): array
    {
        $settings = [
            'paging' => ['perPage' => 25, 'maximumPerPage' => 100],
            'queryFields' => [
                0 => ['id' => 'default', 'type' => 'Text'],
                10001 => ['id' => 'extended', 'type' => 'Hidden'],
            ],
        ];
        if ([] !== $limits) {
            $settings['limits'] = $limits;
        }

        return $settings;
    }

    private static function withQuery(array $arguments, string $term = 'goethe'): array
    {
        return array_merge(['q' => ['default' => $term]], $arguments);
    }

    /**
     * @test
     */
    public function firstPagesOfNormalSearchAreAllowed(): void
    {
        self::assertNull(SearchLimitUtility::getViolation(self::withQuery([]), $this->settings()));
        self::assertNull(SearchLimitUtility::getViolation(self::withQuery(['page' => 2]), $this->settings()));
        self::assertNull(SearchLimitUtility::getViolation(['q' => ['default' => 'goethe'], 'facet' => ['Medium' => ['Buch' => 1]]], $this->settings()));
    }

    /**
     * @test
     */
    public function searchWithTermIsAllowedExactlyAtTheLimit(): void
    {
        // Seite 400 à 25 Treffer: start 9975 + rows 25 = 10000
        self::assertNull(SearchLimitUtility::getViolation(self::withQuery(['page' => 400]), $this->settings()));
        self::assertNull(SearchLimitUtility::getViolation(self::withQuery(['start' => 9975]), $this->settings()));
        self::assertNull(SearchLimitUtility::getViolation(self::withQuery(['start' => 9900, 'count' => 100]), $this->settings()));
    }

    /**
     * @test
     */
    public function searchWithTermIsRejectedJustAboveTheLimit(): void
    {
        self::assertSame(
            SearchLimitUtility::VIOLATION_RESULT_WINDOW,
            SearchLimitUtility::getViolation(self::withQuery(['page' => 401]), $this->settings())
        );
        self::assertSame(
            SearchLimitUtility::VIOLATION_RESULT_WINDOW,
            SearchLimitUtility::getViolation(self::withQuery(['start' => 9976]), $this->settings())
        );
        self::assertSame(
            SearchLimitUtility::VIOLATION_RESULT_WINDOW,
            SearchLimitUtility::getViolation(self::withQuery(['page' => 101, 'count' => 100]), $this->settings())
        );
    }

    /**
     * @test
     */
    public function emptySearchHasStricterLimit(): void
    {
        // Seite 40 à 25 Treffer: start 975 + rows 25 = 1000
        self::assertNull(SearchLimitUtility::getViolation(['page' => 40], $this->settings()));
        self::assertNull(SearchLimitUtility::getViolation(['start' => 975], $this->settings()));

        self::assertSame(SearchLimitUtility::VIOLATION_RESULT_WINDOW, SearchLimitUtility::getViolation(['page' => 41], $this->settings()));
        self::assertSame(SearchLimitUtility::VIOLATION_RESULT_WINDOW, SearchLimitUtility::getViolation(['start' => 976], $this->settings()));
        // Crawler-Muster aus dem Slow-Request-Log
        self::assertSame(SearchLimitUtility::VIOLATION_RESULT_WINDOW, SearchLimitUtility::getViolation(['start' => 4079300], $this->settings()));
    }

    /**
     * @test
     */
    public function wildcardAndHiddenFieldsCountAsEmptySearch(): void
    {
        $settings = $this->settings();

        self::assertTrue(SearchLimitUtility::isEmptyQuery([], $settings));
        self::assertTrue(SearchLimitUtility::isEmptyQuery(['q' => ['default' => '*:*']], $settings));
        self::assertTrue(SearchLimitUtility::isEmptyQuery(['q' => ['default' => ' * ']], $settings));
        self::assertTrue(SearchLimitUtility::isEmptyQuery(['q' => ['raw' => '*:*']], $settings));
        self::assertTrue(SearchLimitUtility::isEmptyQuery(['q' => ['default' => '', 'extended' => '1']], $settings));
        self::assertTrue(SearchLimitUtility::isEmptyQuery(['q' => ['default' => ['alternate' => '1', 'term' => '']]], $settings));
        self::assertTrue(SearchLimitUtility::isEmptyQuery(['q' => ['range' => ['*', '*']]], $settings));

        self::assertFalse(SearchLimitUtility::isEmptyQuery(['q' => ['default' => 'goethe']], $settings));
        self::assertFalse(SearchLimitUtility::isEmptyQuery(['q' => ['default' => 'goe*']], $settings));
        self::assertFalse(SearchLimitUtility::isEmptyQuery(['q' => ['default' => ['alternate' => '1', 'term' => 'goethe']]], $settings));

        self::assertSame(1000, SearchLimitUtility::getMaxResultWindow(['q' => ['default' => '*:*']], $settings));
        self::assertSame(10000, SearchLimitUtility::getMaxResultWindow(['q' => ['default' => 'goethe']], $settings));
    }

    /**
     * @test
     */
    public function activeFilterLimitAllowsTenAndRejectsEleven(): void
    {
        $facets = [];
        for ($i = 1; $i <= 10; $i++) {
            $facets[$i % 2 ? 'Medium' : 'Sammlung']['Wert ' . $i] = $i % 3 ? 'not' : '1';
        }
        $arguments = self::withQuery(['facet' => $facets]);

        self::assertSame(10, SearchLimitUtility::countActiveFilters($arguments));
        self::assertNull(SearchLimitUtility::getViolation($arguments, $this->settings()));

        $arguments['facet']['Sprache']['Deutsch'] = 'not';
        self::assertSame(11, SearchLimitUtility::countActiveFilters($arguments));
        self::assertSame(SearchLimitUtility::VIOLATION_ACTIVE_FILTERS, SearchLimitUtility::getViolation($arguments, $this->settings()));
    }

    /**
     * @test
     */
    public function limitsAreConfigurableAndZeroDisablesThem(): void
    {
        $settings = $this->settings(['maxResultWindow' => 500, 'maxResultWindowEmptyQuery' => 0, 'maxActiveFilters' => '2']);

        self::assertNull(SearchLimitUtility::getViolation(self::withQuery(['page' => 20]), $settings));
        self::assertSame(SearchLimitUtility::VIOLATION_RESULT_WINDOW, SearchLimitUtility::getViolation(self::withQuery(['page' => 21]), $settings));
        self::assertNull(SearchLimitUtility::getViolation(['start' => 4079300], $settings));
        self::assertSame(
            SearchLimitUtility::VIOLATION_ACTIVE_FILTERS,
            SearchLimitUtility::getViolation(self::withQuery(['facet' => ['Medium' => ['a' => 1, 'b' => 1, 'c' => 'not']]]), $settings)
        );
    }

    /**
     * @test
     */
    public function countIsCappedByMaximumPerPage(): void
    {
        self::assertSame(25, SearchLimitUtility::getCount([], $this->settings()));
        self::assertSame(100, SearchLimitUtility::getCount(['count' => 5000], $this->settings()));
        // count=5000 wird auf 100 begrenzt: Seite 100 liegt genau am Limit
        self::assertNull(SearchLimitUtility::getViolation(self::withQuery(['page' => 100, 'count' => 5000]), $this->settings()));
    }

    /**
     * @test
     */
    public function underlyingQueryOfDetailPageIsCheckedAgainstTheLimits(): void
    {
        $settings = $this->settings();

        self::assertNull(SearchLimitUtility::getUnderlyingQueryViolation(['q' => ['default' => 'goethe'], 'position' => 10000], $settings));
        self::assertSame(
            SearchLimitUtility::VIOLATION_RESULT_WINDOW,
            SearchLimitUtility::getUnderlyingQueryViolation(['q' => ['default' => 'goethe'], 'position' => 10001], $settings)
        );
        self::assertNull(SearchLimitUtility::getUnderlyingQueryViolation(['position' => 1000], $settings));
        self::assertSame(SearchLimitUtility::VIOLATION_RESULT_WINDOW, SearchLimitUtility::getUnderlyingQueryViolation(['position' => 1001], $settings));
    }
}
