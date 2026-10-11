<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryStructure;
use Dla\DlaOpacNg\Tests\Support\FakeFacetVocabulary;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class QueryStructureTest extends UnitTestCase
{
    private static function fromArray(array $data): QueryStructure
    {
        return QueryStructure::fromArray($data, FakeFacetVocabulary::DEFAULT_VALUES);
    }

    /**
     * @test
     */
    public function keepsValidValues(): void
    {
        $data = [
            'text' => 'Der Process',
            'persons' => [['name' => 'Kafka', 'fullName' => 'Franz Kafka', 'relation' => 'Von']],
            'topics' => [['term' => 'Exil', 'variants' => ['Exil*', 'Emigration']]],
            'mediaTypes' => ['Gedrucktes'],
            'formContent' => ['Übersetzung'],
            'languages' => ['Englisch'],
            'places' => ['Prag'],
            'dateFrom' => 1925,
            'dateTo' => 1930,
            'digitalOnly' => true,
            'knownItem' => true,
            'exclude' => ['mediaTypes' => ['Audio'], 'formContent' => ['Brief'], 'terms' => ['Zeitung']],
        ];

        self::assertSame($data, self::fromArray($data)->toArray());
        self::assertEquals(self::fromArray($data), self::fromArray(self::fromArray($data)->toArray()));
    }

    /**
     * @test
     */
    public function removesSolrSyntaxAndOperators(): void
    {
        $structure = self::fromArray([
            'text' => 'id:* OR confidential:true',
            'persons' => [['name' => 'Hesse") OR (*:*', 'relation' => 'Von']],
            'places' => ['{!join from=id to=id}Berlin'],
        ]);

        self::assertSame('id confidential true', $structure->text);
        self::assertSame('Hesse', $structure->persons[0]['name']);
        self::assertSame('join from id to id Berlin', $structure->places[0]);
    }

    /**
     * @test
     */
    public function keepsOnlyAllowedValuesInOrderOfTheAnswer(): void
    {
        $structure = self::fromArray([
            'persons' => [['name' => 'Hesse', 'relation' => 'Mit']],
            'mediaTypes' => ['Video', 'Zauberei', 'Audio', 'Video'],
            'formContent' => ['Briefe', 'Brief'],
            'languages' => ['Klingonisch'],
            'digitalOnly' => 'yes',
            'exclude' => ['mediaTypes' => ['Zauberei'], 'formContent' => 'Brief', 'terms' => ['"Zeitung"', '']],
        ]);

        self::assertSame('', $structure->persons[0]['relation']);
        self::assertSame(['Video', 'Audio'], $structure->mediaTypes);
        self::assertSame(['Brief'], $structure->formContent);
        self::assertSame([], $structure->languages);
        self::assertFalse($structure->digitalOnly);
        self::assertSame(['mediaTypes' => [], 'formContent' => [], 'terms' => ['Zeitung']], $structure->exclude);
    }

    /**
     * @test
     */
    public function allowsWildcardOnlyAtEndOfSingleWords(): void
    {
        $structure = self::fromArray(['topics' => [['term' => 'Ex*il', 'variants' => ['Exil*', 'Zweiter Weltkrieg*', 'Ex*', '*']]]]);

        self::assertSame('Ex il', $structure->topics[0]['term']);
        self::assertSame(['Exil*', 'Zweiter Weltkrieg', 'Ex'], $structure->topics[0]['variants']);
    }

    /**
     * @test
     */
    public function limitsListsAndValidatesYears(): void
    {
        $structure = self::fromArray([
            'persons' => array_fill(0, 10, ['name' => 'Hesse', 'relation' => '']),
            'places' => ['Berlin', 'Berlin', 'Wien', 'Prag'],
            'dateFrom' => 1950,
            'dateTo' => 1920,
        ]);

        self::assertCount(3, $structure->persons);
        self::assertSame(['Berlin', 'Wien'], $structure->places);
        self::assertSame([1920, 1950], [$structure->dateFrom, $structure->dateTo]);
        self::assertNull(self::fromArray(['dateFrom' => 99999])->dateFrom);
        self::assertNull(self::fromArray(['dateFrom' => '1950'])->dateFrom);
    }

    /**
     * @test
     */
    public function keepsExclusionsOnlyIfTheQuestionNegates(): void
    {
        $data = ['text' => 'Grass', 'exclude' => ['formContent' => ['Übersetzung'], 'terms' => ['Zeitung']]];
        $vocabulary = FakeFacetVocabulary::DEFAULT_VALUES;

        self::assertSame(QueryStructure::NO_EXCLUSIONS, QueryStructure::fromArray($data, $vocabulary, 'Briefe von Grass')->exclude);
        foreach (['Grass ohne Übersetzungen', 'Grass, aber keine Zeitungen', 'Grass außer Übersetzungen', 'Grass, nicht übersetzt'] as $question) {
            self::assertSame(['Übersetzung'], QueryStructure::fromArray($data, $vocabulary, $question)->exclude['formContent'], $question);
        }
    }

    /**
     * @test
     */
    public function dropsTopicsThatRepeatAPerson(): void
    {
        $structure = self::fromArray([
            'persons' => [['name' => 'Hesse', 'relation' => 'Über'], ['name' => 'Mann', 'fullName' => 'Thomas Mann', 'relation' => 'An']],
            'topics' => [
                ['term' => 'Hesse', 'variants' => ['Hess*']],
                ['term' => 'Mann*', 'variants' => []],
                ['term' => 'Exil', 'variants' => []],
            ],
        ]);

        self::assertSame(['Exil'], array_column($structure->topics, 'term'));
        self::assertSame([], self::fromArray([
            'persons' => [['name' => 'Mann', 'fullName' => 'Thomas Mann', 'relation' => '']],
            'topics' => [['term' => 'Thomas Mann', 'variants' => []]],
        ])->topics);
    }

    /**
     * @test
     */
    public function detectsEmptyAnswer(): void
    {
        self::assertTrue(self::fromArray([])->isEmpty());
        self::assertTrue(self::fromArray(['text' => '  ', 'persons' => [['name' => '"']]])->isEmpty());
        self::assertTrue(self::fromArray(['knownItem' => true])->isEmpty(), 'Suchabsicht allein ist keine Suche');
        self::assertFalse(self::fromArray(['digitalOnly' => true])->isEmpty());
        self::assertFalse(self::fromArray(['exclude' => ['terms' => ['Zeitung']]])->isEmpty());
    }
}
