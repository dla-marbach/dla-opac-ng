<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\VocabularyResolver;
use Dla\DlaOpacNg\Service\SolrConnection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class VocabularyResolverTest extends UnitTestCase
{
    /**
     * SolrConnection, die Anfragen protokolliert und Antworten über eine Funktion erzeugt.
     */
    private function createConnection(\Closure $responder, array &$requests): SolrConnection
    {
        $connection = $this->getMockBuilder(SolrConnection::class)->onlyMethods(['request'])->getMock();
        $connection->method('request')->willReturnCallback(
            function (string $handler, array $params) use ($responder, &$requests): ?array {
                $requests[] = ['handler' => $handler, 'params' => $params];
                return $responder($handler, $params);
            }
        );

        return $connection;
    }

    private static function suggestResponse(string $query, array $suggestions): array
    {
        return ['suggest' => ['mySuggester' => [$query => ['numFound' => count($suggestions), 'suggestions' => $suggestions]]]];
    }

    /**
     * @test
     */
    public function resolvePersonUsesFirstSuggestionContainingAllNameParts(): void
    {
        $requests = [];
        $resolver = new VocabularyResolver($this->createConnection(
            static fn(): array => self::suggestResponse('Thomas Mann', [
                ['term' => 'Mannheim, Thomas', 'payload' => 'PE1␝Mannheim, Thomas (1900-1980)'],
                ['term' => 'Mann, Thomas', 'payload' => 'PE00005678␝Mann, Thomas (1875-1955)'],
                ['term' => 'Mann, Heinrich', 'payload' => 'PE00005679␝Mann, Heinrich (1871-1950)'],
            ]),
            $requests
        ));

        self::assertSame(
            ['id' => 'PE00005678', 'label' => 'Mann, Thomas (1875-1955)'],
            $resolver->resolvePerson('Thomas Mann')
        );
        self::assertSame('suggest', $requests[0]['handler']);
        self::assertSame('mySuggester', $requests[0]['params']['suggest.dictionary']);
        self::assertSame('Thomas Mann', $requests[0]['params']['suggest.q']);
    }

    /**
     * @test
     */
    public function resolvePersonMatchesNameVariantsViaLabel(): void
    {
        $requests = [];
        $resolver = new VocabularyResolver($this->createConnection(
            static fn(): array => self::suggestResponse('Goethe', [
                ['term' => 'Goethe, Johann W.', 'payload' => 'PE00000863␝Goethe, Johann Wolfgang von (1749-1832)'],
            ]),
            $requests
        ));

        self::assertSame('PE00000863', $resolver->resolvePerson('Goethe')['id']);
        // Ergebnis wird je Anfrage nur einmal bei Solr erfragt
        $resolver->resolvePerson('Goethe');
        self::assertCount(1, $requests);
    }

    /**
     * @test
     */
    public function resolvePersonReturnsNullWithoutMatchingSuggestion(): void
    {
        $requests = [];
        $resolver = new VocabularyResolver($this->createConnection(
            static fn(): array => self::suggestResponse('Unbekannt', [
                ['term' => 'Unbekannter Künstler', 'payload' => ''],
            ]),
            $requests
        ));

        self::assertNull($resolver->resolvePerson('Unbekannt'));
    }

    /**
     * @test
     */
    public function resolvePersonReturnsNullWhenSolrIsUnavailable(): void
    {
        $requests = [];
        $resolver = new VocabularyResolver($this->createConnection(static fn(): ?array => null, $requests));

        self::assertNull($resolver->resolvePerson('Hesse'));
    }

    /**
     * @test
     */
    public function resolveFacetValueTriesCapitalizedSpelling(): void
    {
        $requests = [];
        $resolver = new VocabularyResolver($this->createConnection(
            static fn(string $handler, array $params): array => [
                'response' => ['numFound' => $params['fq'] === ['filterFormContent_mv:"Brief"'] ? 42 : 0],
            ],
            $requests
        ));

        self::assertSame('Brief', $resolver->resolveFacetValue('filterFormContent_mv', 'brief'));
        self::assertSame(['filterFormContent_mv:"brief"'], $requests[0]['params']['fq']);
        self::assertSame('0', $requests[0]['params']['rows']);
        self::assertNull($resolver->resolveFacetValue('filterFormContent_mv', 'gibtsnicht'));
    }

    /**
     * @test
     */
    public function countReturnsNullOnErrors(): void
    {
        $requests = [];
        $resolver = new VocabularyResolver($this->createConnection(static fn(): array => ['error' => 'x'], $requests));

        self::assertNull($resolver->count('*:*'));
        self::assertArrayNotHasKey('fq', $requests[0]['params']);
    }

    /**
     * @test
     */
    public function quoteEscapesQuotesAndBackslashes(): void
    {
        self::assertSame('"a \\"b\\" \\\\ c"', VocabularyResolver::quote('a "b" \\ c'));
    }
}
