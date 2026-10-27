<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Tests\Unit\Statistics;

use Dla\DlaOpacNg\Statistics\MatomoRequestBuilder;
use Dla\DlaOpacNg\Statistics\StatisticsConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MatomoRequestBuilderTest extends UnitTestCase
{
    private const TS = 1761572400;

    private const DIMENSIONS = [
        'area' => 1, 'audience' => 2, 'language' => 3, 'referrer' => 4, 'mode' => 5,
        'fields' => 6, 'facets' => 7, 'sort' => 8, 'page' => 9, 'perPage' => 10, 'hits' => 11,
    ];

    /**
     * @test
     */
    public function searchBecomesSiteSearchWithDimensionsAndOneEventPerFacetFilter(): void
    {
        $requests = (new MatomoRequestBuilder(7, 0, self::DIMENSIONS))->build([[
            'ts' => self::TS,
            'a' => 'search',
            'u' => 'https://www.example.org/find/',
            'l' => 'de',
            'r' => 'ext',
            'rh' => 'www.google.de',
            'in' => 0,
            'bot' => 0,
            'd' => [
                'e' => 0, 'x' => 1, 'qf' => ['title', 'default'], 't' => 'schiller räuber',
                'f' => ['Medientypen' => ['Buch' => 1, 'Handschrift' => 'not'], 'Digital' => ['ja' => 1]],
                'p' => 3, 's' => 'year desc', 'c' => 50, 'n' => 0,
            ],
        ]]);

        self::assertCount(4, $requests);
        [$page, $book, $manuscript, $digital] = $requests;

        self::assertSame(7, $page['idsite']);
        self::assertSame(self::TS, $page['cdt']);
        self::assertSame(MatomoRequestBuilder::CLIENT_IP, $page['cip']);
        self::assertSame(MatomoRequestBuilder::USER_AGENT, $page['ua']);
        self::assertSame(1, $page['new_visit']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $page['cid']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{6}$/', $page['pv_id']);
        self::assertSame('https://www.example.org/find/', $page['url']);
        self::assertSame('Katalog/Trefferliste', $page['action_name']);
        self::assertSame('https://www.google.de/', $page['urlref']);
        self::assertSame('schiller räuber', $page['search']);
        self::assertSame('Katalog (erweitert)', $page['search_cat']);
        self::assertSame(0, $page['search_count']);

        self::assertSame([
            'dimension1' => 'Trefferliste',
            'dimension2' => 'extern',
            'dimension3' => 'de',
            'dimension4' => 'andere Website',
            'dimension5' => 'erweitert',
            'dimension6' => 'default,title',
            'dimension7' => 'Digital,Medientypen',
            'dimension8' => 'year desc',
            'dimension9' => '3',
            'dimension10' => '50',
            'dimension11' => '0',
        ], array_filter($page, static fn(string $key): bool => str_starts_with($key, 'dimension'), ARRAY_FILTER_USE_KEY));

        self::assertSame(['Facette', 'Medientypen', 'Buch'], [$book['e_c'], $book['e_a'], $book['e_n']]);
        self::assertSame(['Facette (ausgeschlossen)', 'Medientypen', 'Handschrift'], [$manuscript['e_c'], $manuscript['e_a'], $manuscript['e_n']]);
        self::assertSame(['Facette', 'Digital', 'ja'], [$digital['e_c'], $digital['e_a'], $digital['e_n']]);
        foreach ([$book, $manuscript, $digital] as $event) {
            self::assertSame($page['cid'], $event['cid']);
            self::assertSame($page['pv_id'], $event['pv_id']);
            self::assertArrayNotHasKey('new_visit', $event);
            self::assertArrayNotHasKey('dimension1', $event);
        }
    }

    /**
     * @test
     */
    public function pagesWithoutSearchTermArePageviewsAndUnmappedDimensionsAreOmitted(): void
    {
        $requests = (new MatomoRequestBuilder(7, 0, ['mode' => 3]))->build([
            ['ts' => self::TS, 'a' => 'search', 'u' => 'https://www.example.org/find/', 'r' => 'int', 'in' => 1, 'bot' => 0,
                'd' => ['e' => 1, 'x' => 0, 'p' => 1, 'c' => 20, 'n' => 12345]],
            ['ts' => self::TS, 'a' => 'detail', 'u' => 'https://www.example.org/find/opac/id/DE-1', 'r' => 'none', 'in' => 0, 'bot' => 0,
                'd' => ['id' => 'DE-1', 'u' => 1, 'pdf' => 0]],
            ['ts' => self::TS, 'a' => 'collection', 'u' => 'https://www.example.org/bestandsuebersicht/', 'r' => 'none', 'in' => 0, 'bot' => 0,
                'd' => ['m' => 'search', 't' => 'kafka']],
        ]);

        self::assertCount(3, $requests);
        self::assertArrayNotHasKey('search', $requests[0]);
        self::assertArrayNotHasKey('urlref', $requests[0]);
        self::assertSame('ohne Suchbegriff', $requests[0]['dimension3']);
        self::assertSame('Katalog/Detailseite', $requests[1]['action_name']);
        self::assertSame('https://www.example.org/find/opac/id/DE-1', $requests[1]['url']);
        self::assertSame('aus Trefferliste', $requests[1]['dimension3']);
        self::assertSame('kafka', $requests[2]['search']);
        self::assertSame('Bestandsübersicht', $requests[2]['search_cat']);
        self::assertSame('Suche', $requests[2]['dimension3']);
        self::assertSame([], array_filter(
            array_merge(...$requests),
            static fn(string $key): bool => str_starts_with($key, 'dimension') && $key !== 'dimension3',
            ARRAY_FILTER_USE_KEY
        ));
    }

    /**
     * @test
     */
    public function powBotsAndAjaxAreCountedPerHourOnAuxiliarySite(): void
    {
        $records = [
            ['ts' => self::TS, 'a' => 'pow:issued'],
            ['ts' => self::TS + 10, 'a' => 'pow:issued'],
            ['ts' => self::TS + 3600, 'a' => 'pow:issued'],
            ['ts' => self::TS, 'a' => 'pow:solved'],
            ['ts' => self::TS, 'a' => 'detail', 'in' => 0, 'bot' => 1, 'd' => ['id' => 'DE-1']],
            ['ts' => self::TS, 'a' => 'ajax:autocomplete', 'in' => 0, 'bot' => 0],
            ['ts' => self::TS, 'a' => 'ajax:autocomplete', 'in' => 1, 'bot' => 0],
            ['ts' => self::TS, 'a' => 'ajax:classification', 'in' => 0, 'bot' => 0, 'd' => ['m' => 'getNodes']],
        ];

        $requests = (new MatomoRequestBuilder(7, 8))->build($records);

        $events = array_map(
            static fn(array $request): string => implode('|', [
                $request['idsite'], $request['e_c'], $request['e_a'], $request['e_n'] ?? '', $request['e_v'], $request['cdt'],
            ]),
            $requests
        );
        sort($events);
        self::assertSame([
            '8|AJAX|autocomplete|extern|1|' . self::TS,
            '8|AJAX|autocomplete|intern|1|' . self::TS,
            '8|AJAX|classification getNodes|extern|1|' . self::TS,
            '8|Bot|Detailseite||1|' . self::TS,
            '8|Proof-of-Work|Challenge ausgestellt||1|' . (self::TS + 3600),
            '8|Proof-of-Work|Challenge ausgestellt||2|' . (self::TS + 10),
            '8|Proof-of-Work|Challenge gelöst||1|' . self::TS,
        ], $events);

        self::assertSame([], (new MatomoRequestBuilder(7))->build($records), 'Without auxiliary site nothing is sent');
    }

    /**
     * @test
     */
    public function hitsAreBucketed(): void
    {
        self::assertSame(
            ['0', '1', '2-10', '11-100', '101-1000', '1001-10000', '>10000'],
            array_map([MatomoRequestBuilder::class, 'bucketHits'], [0, 1, 10, 11, 1000, 10000, 10001])
        );
    }

    /**
     * @test
     */
    public function dimensionMappingIsReadFromEnvironment(): void
    {
        $previous = getenv('opacMatomoDimensions');
        putenv('opacMatomoDimensions=area:1, mode : 3,unknown:4,hits:x,facets:0,sort:12');
        try {
            self::assertSame(['area' => 1, 'mode' => 3, 'sort' => 12], StatisticsConfiguration::getMatomoDimensions());
        } finally {
            putenv($previous === false ? 'opacMatomoDimensions' : 'opacMatomoDimensions=' . $previous);
        }
    }
}
