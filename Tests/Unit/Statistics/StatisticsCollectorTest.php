<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Tests\Unit\Statistics;

use Dla\DlaOpacNg\Statistics\StatisticsCollector;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StatisticsCollectorTest extends UnitTestCase
{
    private const SETTINGS = [
        'paging' => ['perPage' => 20, 'maximumPerPage' => 100],
        'queryFields' => [
            ['id' => 'default', 'type' => 'Text'],
            ['id' => 'title', 'type' => 'Text'],
            ['id' => 'extended', 'type' => 'Hidden'],
        ],
    ];

    /**
     * @test
     */
    public function searchRecordsFieldsFacetsPageSortAndHits(): void
    {
        $collector = new StatisticsCollector();
        $collector->recordSearch([
            'q' => ['default' => "  Schiller \t Räuber ", 'title' => 'Die Räuber', 'extended' => '1', 'author' => ''],
            'facet' => ['Medientypen' => ['Buch' => '1', 'Handschrift' => 'not']],
            'page' => '3',
            'sort' => 'year desc',
            'count' => '50',
        ], self::SETTINGS, 0, true);

        self::assertSame(['search' => [
            'e' => 0,
            'x' => 1,
            'qf' => ['default', 'title'],
            't' => 'schiller räuber',
            'f' => ['Medientypen' => ['Buch' => 1, 'Handschrift' => 'not']],
            'p' => 3,
            's' => 'year desc',
            'c' => 50,
            'n' => 0,
        ]], $collector->pull());
    }

    /**
     * @test
     */
    public function emptySearchHasNoTermAndStartsOnFirstPage(): void
    {
        $collector = new StatisticsCollector();
        $collector->recordSearch(['q' => ['default' => '*'], 'facet' => ['Digital' => ['ja' => '1']]], self::SETTINGS, 1234, false);

        $search = $collector->pull()['search'];
        self::assertSame(1, $search['e']);
        self::assertArrayNotHasKey('t', $search);
        self::assertArrayNotHasKey('qf', $search);
        self::assertSame(1, $search['p']);
        self::assertSame(1234, $search['n']);
    }

    /**
     * @test
     */
    public function longValuesAreTruncated(): void
    {
        $collector = new StatisticsCollector();
        $collector->recordSearch(['q' => ['default' => str_repeat('ä', 300)]], self::SETTINGS, null, false);

        self::assertSame(StatisticsCollector::MAX_STRING_LENGTH, mb_strlen($collector->pull()['search']['t']));
    }

    /**
     * @test
     */
    public function firstRecordOfAnAreaWinsAndPullEmptiesTheCollector(): void
    {
        $collector = new StatisticsCollector();
        $collector->recordDetail('DE-123', true, true, false);
        $collector->recordDetail('DE-456', true, false, true);
        $collector->record(StatisticsCollector::AREA_START);

        self::assertTrue($collector->hasEntries());
        self::assertSame([
            'detail' => ['id' => 'DE-123', 'u' => 1, 'pdf' => 0],
            'start' => [],
        ], $collector->pull());
        self::assertFalse($collector->hasEntries());
        self::assertSame([], $collector->pull());
    }

    /**
     * @test
     */
    public function unknownDetailIdsAreNotRecorded(): void
    {
        $collector = new StatisticsCollector();
        $collector->recordDetail('../../etc/passwd', false, false, false);

        self::assertSame(['detail' => ['nf' => 1, 'u' => 0, 'pdf' => 0]], $collector->pull());
    }

    /**
     * @test
     */
    public function treeModesAreDetected(): void
    {
        $collector = new StatisticsCollector();
        $collector->recordTree('collection', '', '', '');
        $collector->recordTree('classification', '', '', '  Lyrik ');
        self::assertSame([
            'collection' => ['m' => 'entry'],
            'classification' => ['m' => 'search', 't' => 'lyrik'],
        ], $collector->pull());

        $collector->recordTree('collection', 'HS00001', '', '');
        $collector->recordTree('classification', '', '42', '');
        self::assertSame([
            'collection' => ['m' => 'record'],
            'classification' => ['m' => 'node'],
        ], $collector->pull());
    }
}
