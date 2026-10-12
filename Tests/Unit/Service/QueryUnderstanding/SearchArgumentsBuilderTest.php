<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryStructure;
use Dla\DlaOpacNg\Service\QueryUnderstanding\SearchArgumentsBuilder;
use Dla\DlaOpacNg\Tests\Support\FakeFacetVocabulary;
use Dla\DlaOpacNg\Tests\Support\FakeVocabularyResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SearchArgumentsBuilderTest extends UnitTestCase
{
    private static function build(array $data): array
    {
        $builder = new SearchArgumentsBuilder(new FakeVocabularyResolver(
            persons: [
                'Hesse' => ['id' => 'PE1', 'label' => 'Hesse, Hermann (1877-1962)'],
                'Thomas Mann' => ['id' => 'PE2', 'label' => 'Mann, Thomas (1875-1955)'],
                'Rainer Maria Rilke' => ['id' => 'PE3', 'label' => 'Rilke, Rainer Maria (1875-1926)'],
            ],
            places: ['Marbach' => 'Marbach am Neckar'],
        ));
        $interpretation = $builder->build(QueryStructure::fromArray($data, FakeFacetVocabulary::DEFAULT_VALUES));

        return $interpretation->toFindArguments() + $interpretation->toUrlParts();
    }

    /**
     * @test
     */
    public function mapsTheQuestionToFacetsAndSearchTerms(): void
    {
        $arguments = self::build([
            'text' => 'Steppenwolf',
            'persons' => [['name' => 'Hesse', 'relation' => 'Von'], ['name' => 'Thomas Mann', 'relation' => ''], ['name' => 'Unbekannt', 'relation' => 'An']],
            'topics' => [['term' => 'Exil', 'variants' => ['Exil*', 'Emigration']]],
            'mediaTypes' => ['Handschriften', 'Audio'],
            'formContent' => ['Brief'],
            'languages' => ['Englisch'],
            'places' => ['Marbach', 'Atlantis'],
            'dateFrom' => 1930,
            'dateTo' => 1939,
            'digitalOnly' => true,
        ]);

        self::assertSame('Steppenwolf unbekannt atlantis (exil OR exil* OR emigration)', $arguments['q']['default']);
        self::assertSame([
            'filterAuthorityRelation_mv' => ['Hesse, Hermann (1877-1962)␝Von' => '1'],
            'Personen' => ['Mann, Thomas (1875-1955)' => '1'],
            'Medientypen' => ['Handschriften' => '1'],
            'FormUndInhalt' => ['Brief' => '1'],
            'Sprache' => ['Englisch' => '1'],
            'Ort' => ['Marbach am Neckar' => '1'],
            'Zeit' => ['RANGE 1930 TO 1939' => '1'],
            'Digital' => ['true' => '1'],
        ], $arguments['facet']);
        self::assertContains(['f' => 'Zeit', 't' => 'RANGE 1930 TO 1939'], $arguments['nlf']);
        self::assertSame([
            ['k' => 'text', 'q' => 'Steppenwolf', 'l' => 'Steppenwolf'],
            ['k' => 'unresolved', 'q' => 'unbekannt', 'l' => 'Unbekannt'],
            ['k' => 'unresolved', 'q' => 'atlantis', 'l' => 'Atlantis'],
            ['k' => 'topic', 'q' => '(exil OR exil* OR emigration)', 'l' => 'Exil'],
        ], $arguments['nlt']);
    }

    /**
     * @test
     */
    public function completesOpenDateRanges(): void
    {
        self::assertSame(['RANGE 1945 TO ' . date('Y') => '1'], self::build(['dateFrom' => 1945])['facet']['Zeit']);
        self::assertSame(['RANGE 1000 TO 1799' => '1'], self::build(['dateTo' => 1799])['facet']['Zeit']);
        self::assertSame(['RANGE 2090 TO 2090' => '1'], self::build(['dateFrom' => 2090])['facet']['Zeit']);
        self::assertSame(['RANGE 0 TO 499' => '1'], self::build(['dateTo' => 499])['facet']['Zeit']);
    }

    /**
     * @test
     */
    public function normalizesTopicWildcardsLikeTheSearchField(): void
    {
        self::assertSame('(übersetzung OR uebersetz*)', SearchArgumentsBuilder::topicQuery('Übersetzung', ['Übersetz*']));
        self::assertSame('(philosophie OR filosof*)', SearchArgumentsBuilder::topicQuery('Philosophie', ['Philosoph*']));
        self::assertSame('(krieg OR "zweiter weltkrieg")', SearchArgumentsBuilder::topicQuery('Krieg', ['Zweiter Weltkrieg']));
        self::assertSame('film', SearchArgumentsBuilder::topicQuery('Film', []));
    }

    /**
     * @test
     */
    public function emptyStructureGivesNoArguments(): void
    {
        self::assertSame(['nlf' => [], 'nlt' => []], self::build([]));
    }

    /**
     * @test
     */
    public function resolvesPersonsByFullNameFirst(): void
    {
        $arguments = self::build(['persons' => [
            ['name' => 'Rilke', 'fullName' => 'Rainer Maria Rilke', 'relation' => 'Von'],
            ['name' => 'Hesse', 'fullName' => 'Hermann Karl Hesse', 'relation' => 'Von'],
            ['name' => 'Mann', 'fullName' => '', 'relation' => 'Über'],
        ]]);

        self::assertSame([
            'Rilke, Rainer Maria (1875-1926)␝Von' => '1',
            'Hesse, Hermann (1877-1962)␝Von' => '1',
        ], $arguments['facet']['filterAuthorityRelation_mv']);
        self::assertSame('mann', $arguments['q']['default'], 'nicht aufgelöst: Name aus der Frage');
    }

    /**
     * @test
     */
    public function searchesKnownItemsAsPhraseWithoutTopicVariants(): void
    {
        $data = [
            'text' => 'Der Process',
            'persons' => [['name' => 'Max Unbekannt', 'fullName' => '', 'relation' => 'Von']],
            'topics' => [['term' => 'Prozess', 'variants' => ['Gericht*']]],
        ];

        self::assertSame('Der Process max unbekannt (prozess OR gericht*)', self::build($data)['q']['default']);
        self::assertSame('"Der Process" "max unbekannt" prozess', self::build(['knownItem' => true] + $data)['q']['default']);
    }

    /**
     * @test
     */
    public function excludesMediaTypesFormsAndTerms(): void
    {
        $arguments = self::build([
            'persons' => [['name' => 'Hesse', 'fullName' => '', 'relation' => 'Über']],
            'exclude' => ['mediaTypes' => ['Audio'], 'formContent' => ['Übersetzung'], 'terms' => ['Zeitung', 'Kritik*']],
        ]);

        self::assertSame(['Audio' => 'not'], $arguments['facet']['Medientypen']);
        self::assertSame(['Übersetzung' => 'not'], $arguments['facet']['FormUndInhalt']);
        self::assertContains(['f' => 'FormUndInhalt', 't' => 'Übersetzung', 'n' => '1'], $arguments['nlf']);
        self::assertSame('NOT zeitung NOT kritik*', $arguments['q']['default']);
    }
}
