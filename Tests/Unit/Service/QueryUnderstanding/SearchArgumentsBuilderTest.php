<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryInterpretation;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryStructure;
use Dla\DlaOpacNg\Service\QueryUnderstanding\SearchArgumentsBuilder;
use Dla\DlaOpacNg\Tests\Support\FakeVocabularyResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SearchArgumentsBuilderTest extends UnitTestCase
{
    private function createBuilder(): SearchArgumentsBuilder
    {
        return new SearchArgumentsBuilder(new FakeVocabularyResolver(
            persons: [
                'Hesse' => ['id' => 'PE00001234', 'label' => 'Hesse, Hermann (1877-1962)'],
                'Thomas Mann' => ['id' => 'PE00005678', 'label' => 'Mann, Thomas (1875-1955)'],
            ],
            facetValues: [
                'filterLocation_mv' => ['Marbach'],
            ],
        ));
    }

    /**
     * @test
     */
    public function mapsResolvedValuesToFacetsOfTheSearchPlugin(): void
    {
        $interpretation = $this->createBuilder()->build(QueryStructure::fromArray([
            'persons' => [
                ['name' => 'Hesse', 'relation' => 'Von'],
                ['name' => 'Thomas Mann', 'relation' => ''],
            ],
            'mediaTypes' => ['Handschriften'],
            'formContent' => ['Brief'],
            'languages' => ['Englisch'],
            'places' => ['Marbach'],
            'digitalOnly' => true,
            'dateFrom' => 1930,
            'dateTo' => 1939,
        ]));

        self::assertSame([
            'facet' => [
                'filterAuthorityRelation_mv' => ['Hesse, Hermann (1877-1962)␝Von' => '1'],
                'Personen' => ['Mann, Thomas (1875-1955)' => '1'],
                'Medientypen' => ['Handschriften' => '1'],
                'FormUndInhalt' => ['Brief' => '1'],
                'Sprache' => ['Englisch' => '1'],
                'Ort' => ['Marbach' => '1'],
                'Digital' => ['true' => '1'],
                'Zeit' => ['RANGE 1930 TO 1939' => '1'],
            ],
        ], $interpretation->toFindArguments());

        self::assertSame([
            'filterAuthorityRelation_mv:("Hesse, Hermann (1877-1962)␝Von")',
            'filterAuthority_mv:("Mann, Thomas (1875-1955)")',
            'filterType_mv:("Handschriften")',
            'filterFormContent_mv:("Brief")',
            'filterLanguage_mv:("Englisch")',
            'filterLocation_mv:("Marbach")',
            'filterDigital:true',
            'filterDateRange_mv:[1930 TO 1939]',
        ], $interpretation->getFilterQueries());
    }

    /**
     * @test
     */
    public function turnsUnresolvedValuesIntoSearchTerms(): void
    {
        $interpretation = $this->createBuilder()->build(QueryStructure::fromArray([
            'text' => 'Der Process',
            'persons' => [['name' => 'Unbekannte Person', 'relation' => 'Von']],
            'places' => ['Atlantis'],
        ]));

        self::assertSame('Der Process unbekannte person atlantis', $interpretation->getQueryString());
        self::assertTrue($interpretation->hasPart(QueryInterpretation::PART_UNRESOLVED));
        self::assertSame([], $interpretation->getFilterQueries());
    }

    /**
     * @test
     */
    public function buildsTopicsAsOrGroupWithNormalizedWildcards(): void
    {
        $interpretation = $this->createBuilder()->build(QueryStructure::fromArray([
            'topics' => [
                ['term' => 'Exil', 'variants' => ['Exil*', 'Emigration', 'Zweiter Weltkrieg']],
                ['term' => 'Übersetzung', 'variants' => ['Übersetz*']],
                ['term' => 'Film', 'variants' => []],
            ],
        ]));

        self::assertSame(
            '(exil OR exil* OR emigration OR "zweiter weltkrieg") (übersetzung OR uebersetz*) film',
            $interpretation->getQueryString()
        );
        self::assertSame(
            '(philosophie OR filosof*)',
            SearchArgumentsBuilder::topicQuery('Philosophie', ['Philosoph*'])
        );
    }

    /**
     * @test
     */
    public function usesOnlyTheFirstMediaType(): void
    {
        $interpretation = $this->createBuilder()->build(QueryStructure::fromArray([
            'mediaTypes' => ['Audio', 'Video'],
        ]));

        self::assertSame(['facet' => ['Medientypen' => ['Audio' => '1']]], $interpretation->toFindArguments());
    }

    /**
     * @test
     */
    public function completesOpenDateRanges(): void
    {
        $builder = $this->createBuilder();

        self::assertSame(
            ['from' => 1945, 'to' => (int)date('Y')],
            $builder->build(QueryStructure::fromArray(['dateFrom' => 1945]))->dateRange
        );
        self::assertSame(
            ['from' => 1000, 'to' => 1799],
            $builder->build(QueryStructure::fromArray(['dateTo' => 1799]))->dateRange
        );
        self::assertSame(
            ['from' => 2090, 'to' => 2090],
            $builder->build(QueryStructure::fromArray(['dateFrom' => 2090]))->dateRange,
            'Offenes Ende darf nicht vor dem Anfangsjahr liegen'
        );
    }

    /**
     * @test
     */
    public function translatesFacetsOfTheSearchPluginIntoFilterQueries(): void
    {
        self::assertSame([
            'filterType_mv:("Handschriften")',
            'NOT filterLanguage_mv:("Deutsch")',
            'filterSource:("Werke" OR "Personen")',
            'filterDateRange_mv:[1930 TO 1939]',
            'filterDigital:true',
            'filterAuthorityRelation_mv:("Hesse, Hermann (1877-1962)␝Von")',
        ], SearchArgumentsBuilder::filterQueriesForFacets([
            'Medientypen' => ['Handschriften' => '1'],
            'Sprache' => ['Deutsch' => 'not'],
            'DatenbestandHidden' => ['Werke' => '1', 'Personen' => '1'],
            'Zeit' => ['RANGE 1930 TO 1939' => '1'],
            'Digital' => ['true' => '1'],
            'filterAuthorityRelation_mv' => ['Hesse, Hermann (1877-1962)␝Von' => '1'],
        ]));
        self::assertSame([], SearchArgumentsBuilder::filterQueriesForFacets([]));
    }

    /**
     * @test
     */
    public function cannotTranslateUnknownOrSpecialFacets(): void
    {
        self::assertNull(SearchArgumentsBuilder::filterQueriesForFacets(['NeuImKatalog' => ['Woche' => '1']]));
        self::assertNull(SearchArgumentsBuilder::filterQueriesForFacets(['Thema' => ['nicht bestimmt' => '1']]));
        self::assertNull(SearchArgumentsBuilder::filterQueriesForFacets(['Medientypen' => 'kaputt']));
    }

    /**
     * @test
     */
    public function relaxationRemovesPartsAndRemembersThem(): void
    {
        $interpretation = $this->createBuilder()->build(QueryStructure::fromArray([
            'text' => 'Steppenwolf',
            'topics' => [['term' => 'Exil', 'variants' => []]],
            'places' => ['Atlantis'],
            'dateFrom' => 1930,
            'dateTo' => 1939,
        ]));

        $relaxed = $interpretation->without(QueryInterpretation::PART_UNRESOLVED)->without('date');

        self::assertSame('Steppenwolf exil', $relaxed->getQueryString());
        self::assertNull($relaxed->dateRange);
        self::assertSame(['Atlantis', '1930–1939'], $relaxed->dropped);
    }
}
