<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\FacetVocabulary;
use Dla\DlaOpacNg\Service\SolrConnection;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FacetVocabularyTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private const FACET_RESPONSE = [
        'facet_counts' => ['facet_fields' => [
            'filterType_mv' => ['Gedrucktes' => 1800000, 'Handschriften' => 900000, 'Audio' => 12000],
            'filterFormContent_mv' => ['Brief' => 500000, 'Aufsatz' => 300000, '' => 5],
            'filterLanguage_mv' => ['Deutsch' => 2000000, 'Englisch' => 100000],
        ]],
    ];

    private function createConnection(?array $response, int &$calls, array &$lastParams = []): SolrConnection
    {
        $connection = $this->getMockBuilder(SolrConnection::class)->onlyMethods(['request'])->getMock();
        $connection->method('request')->willReturnCallback(
            function (string $handler, array $params, float $timeout = 1.0) use ($response, &$calls, &$lastParams): ?array {
                $calls++;
                $lastParams = $params + ['__handler' => $handler, '__timeout' => $timeout];
                return $response;
            }
        );

        return $connection;
    }

    private function createCache(): VariableFrontend
    {
        return new VariableFrontend('test', new TransientMemoryBackend('Testing'));
    }

    /**
     * @test
     */
    public function fetchesSortedValuesOfSmallFacets(): void
    {
        $calls = 0;
        $params = [];
        $vocabulary = new FacetVocabulary($this->createConnection(self::FACET_RESPONSE, $calls, $params), $this->createCache());

        self::assertSame([
            'mediaTypes' => ['Audio', 'Gedrucktes', 'Handschriften'],
            'formContent' => ['Aufsatz', 'Brief'],
            'languages' => ['Deutsch', 'Englisch'],
        ], $vocabulary->getAll());
        self::assertSame('select', $params['__handler']);
        self::assertSame(['filterType_mv', 'filterFormContent_mv', 'filterLanguage_mv'], $params['facet.field']);
        self::assertSame('100', $params['f.filterFormContent_mv.facet.limit']);
        self::assertSame('0', $params['rows']);
        self::assertGreaterThan(1.0, $params['__timeout']);
    }

    /**
     * @test
     */
    public function cachesValuesAcrossInstances(): void
    {
        $calls = 0;
        $cache = $this->createCache();
        $connection = $this->createConnection(self::FACET_RESPONSE, $calls);

        (new FacetVocabulary($connection, $cache))->getAll();
        $values = (new FacetVocabulary($connection, $cache))->getValues('formContent');

        self::assertSame(['Aufsatz', 'Brief'], $values);
        self::assertSame(1, $calls);
    }

    /**
     * @test
     */
    public function fallsBackWithoutCachingWhenSolrIsUnavailable(): void
    {
        $calls = 0;
        $cache = $this->createCache();
        $connection = $this->createConnection(null, $calls);

        $values = (new FacetVocabulary($connection, $cache))->getAll();

        self::assertSame(FacetVocabulary::FALLBACK_MEDIA_TYPES, $values['mediaTypes']);
        self::assertSame([], $values['formContent']);
        self::assertSame([], $values['languages']);

        (new FacetVocabulary($connection, $cache))->getAll();
        self::assertSame(2, $calls, 'Fehlschläge werden nicht zwischengespeichert');
    }
}
