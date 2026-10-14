<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\VocabularyResolver;
use Dla\DlaOpacNg\Service\SolrConnection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class VocabularyResolverTest extends UnitTestCase
{
    /** @var list<array{handler: string, params: array, timeout: float}> */
    private array $requests = [];

    private function createResolver(?array $response): VocabularyResolver
    {
        $connection = $this->getMockBuilder(SolrConnection::class)->onlyMethods(['request'])->getMock();
        $connection->method('request')->willReturnCallback(
            function (string $handler, array $params, float $timeout = 1.0) use ($response): ?array {
                $this->requests[] = ['handler' => $handler, 'params' => $params, 'timeout' => $timeout];
                return $response;
            }
        );

        return new VocabularyResolver($connection);
    }

    private static function suggestions(string $query, array $suggestions): array
    {
        return ['suggest' => ['mySuggester' => [$query => ['suggestions' => $suggestions]]]];
    }

    /**
     * @test
     */
    public function resolvesPersonWithAllNamePartsAndRemembersResult(): void
    {
        $resolver = $this->createResolver(self::suggestions('Thomas Mann', [
            ['term' => 'Mannheim, Thomas', 'payload' => 'PE1␝Mannheim, Thomas (1900-1980)'],
            ['term' => 'Mann, Thomas', 'payload' => 'PE2␝Mann, Thomas (1875-1955)'],
        ]));

        self::assertSame(['id' => 'PE2', 'label' => 'Mann, Thomas (1875-1955)'], $resolver->resolvePerson('Thomas Mann'));
        $resolver->resolvePerson('Thomas Mann');
        self::assertCount(1, $this->requests);
        self::assertSame(['suggest' => 'true', 'suggest.dictionary' => 'mySuggester', 'suggest.q' => 'Thomas Mann'], $this->requests[0]['params']);
    }

    /**
     * @test
     */
    public function prefersPersonOverMoreFrequentCorporateBody(): void
    {
        $resolver = $this->createResolver(self::suggestions('Anton Kippenberg', [
            ['term' => 'Insel Verlag Anton Kippenberg <Frankfurt am Main> (1960-1991)', 'payload' => 'KS00003264␝Insel Verlag Anton Kippenberg <Frankfurt am Main> (1960-1991)'],
            ['term' => 'Kippenberg, Anton (1874-1950)', 'payload' => 'PE00023126␝Kippenberg, Anton (1874-1950)'],
        ]));

        self::assertSame(['id' => 'PE00023126', 'label' => 'Kippenberg, Anton (1874-1950)'], $resolver->resolvePerson('Anton Kippenberg'));
    }

    /**
     * @test
     */
    public function resolvesCorporateBodyIfNoPersonMatches(): void
    {
        $resolver = $this->createResolver(self::suggestions('Suhrkamp Verlag', [
            ['term' => 'Suhrkamp, Peter', 'payload' => 'PE1␝Suhrkamp, Peter (1891-1959)'],
            ['term' => 'Suhrkamp Verlag <Frankfurt am Main>', 'payload' => 'KS1␝Suhrkamp Verlag <Frankfurt am Main>'],
            ['term' => 'Suhrkamp Verlag <Berlin>', 'payload' => 'KS2␝Suhrkamp Verlag <Berlin>'],
        ]));

        self::assertSame(['id' => 'KS1', 'label' => 'Suhrkamp Verlag <Frankfurt am Main>'], $resolver->resolvePerson('Suhrkamp Verlag'));
    }

    /**
     * @test
     */
    public function returnsNullForUnknownPersonOrUnavailableSolr(): void
    {
        self::assertNull($this->createResolver(self::suggestions('Unbekannt', [['term' => 'X', 'payload' => '']]))->resolvePerson('Unbekannt'));
        self::assertNull($this->createResolver(null)->resolvePerson('Hesse'));
    }

    /**
     * @test
     */
    public function resolvesPlacePreferringExactValue(): void
    {
        $values = ['Berlin-Charlottenburg' => 900, 'Berlin' => 800];
        $resolver = $this->createResolver(['facet_counts' => ['facet_fields' => ['filterLocation_mv' => $values]]]);

        self::assertSame('Berlin', $resolver->resolveFacetValue('filterLocation_mv', 'berlin'));
        self::assertSame('berlin', $this->requests[0]['params']['facet.contains']);
        self::assertSame('true', $this->requests[0]['params']['facet.contains.ignoreCase']);
        self::assertSame(3.0, $this->requests[0]['timeout']);
    }

    /**
     * @test
     */
    public function resolvesPlaceToMostFrequentValueContainingTerm(): void
    {
        $resolver = $this->createResolver(['facet_counts' => ['facet_fields' => ['filterLocation_mv' => ['Marbach am Neckar' => 1234]]]]);

        self::assertSame('Marbach am Neckar', $resolver->resolveFacetValue('filterLocation_mv', 'Marbach'));
        self::assertNull($this->createResolver(null)->resolveFacetValue('filterLocation_mv', 'Atlantis'));
    }

    /**
     * @test
     */
    public function findsExactValueEvenIfLessFrequentThanTheTopTen(): void
    {
        $containing = array_fill_keys(array_map(static fn(int $i): string => 'Berlin-Bezirk ' . $i, range(1, 10)), 100);
        $resolver = $this->createResolver(['facet_counts' => [
            'facet_queries' => ['filterLocation_mv:"berlin"' => 0, 'filterLocation_mv:"Berlin"' => 12],
            'facet_fields' => ['filterLocation_mv' => $containing],
        ]]);

        self::assertSame('Berlin', $resolver->resolveFacetValue('filterLocation_mv', 'berlin'));
        self::assertSame(['filterLocation_mv:"berlin"', 'filterLocation_mv:"Berlin"'], $this->requests[0]['params']['facet.query']);
        self::assertCount(1, $this->requests, 'eine Anfrage für exakte und enthaltende Werte');
    }

    /**
     * @test
     */
    public function prefersMuchMoreFrequentContainingValueOverRareExactValue(): void
    {
        $resolver = $this->createResolver(['facet_counts' => [
            'facet_queries' => ['filterLocation_mv:"Marbach"' => 12],
            'facet_fields' => ['filterLocation_mv' => ['Marbach am Neckar' => 45000, 'Marbach' => 12]],
        ]]);

        self::assertSame('Marbach am Neckar', $resolver->resolveFacetValue('filterLocation_mv', 'Marbach'));
    }
}
