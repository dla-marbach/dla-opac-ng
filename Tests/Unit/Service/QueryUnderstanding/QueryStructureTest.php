<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryStructure;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class QueryStructureTest extends UnitTestCase
{
    /**
     * @test
     */
    public function keepsValidValues(): void
    {
        $structure = QueryStructure::fromArray([
            'text' => 'Der Process',
            'persons' => [['name' => 'Kafka', 'relation' => 'Von']],
            'topics' => [['term' => 'Exil', 'variants' => ['Exil*', 'Emigration']]],
            'mediaTypes' => ['Gedrucktes'],
            'formContent' => ['Übersetzung'],
            'languages' => ['Englisch'],
            'places' => ['Prag'],
            'dateFrom' => 1925,
            'dateTo' => 1930,
            'digitalOnly' => true,
        ]);

        self::assertSame('Der Process', $structure->text);
        self::assertSame([['name' => 'Kafka', 'relation' => 'Von']], $structure->persons);
        self::assertSame([['term' => 'Exil', 'variants' => ['Exil*', 'Emigration']]], $structure->topics);
        self::assertSame(['Gedrucktes'], $structure->mediaTypes);
        self::assertSame(['Übersetzung'], $structure->formContent);
        self::assertSame(['Englisch'], $structure->languages);
        self::assertSame(['Prag'], $structure->places);
        self::assertSame(1925, $structure->dateFrom);
        self::assertSame(1930, $structure->dateTo);
        self::assertTrue($structure->digitalOnly);
    }

    /**
     * @test
     */
    public function removesSolrSyntaxFromAllValues(): void
    {
        $structure = QueryStructure::fromArray([
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
    public function dropsUnknownEnumValuesAndFields(): void
    {
        $structure = QueryStructure::fromArray([
            'persons' => [['name' => 'Hesse', 'relation' => 'Mit']],
            'mediaTypes' => ['Handschriften', 'Zauberei', 42],
            'unknownField' => 'ignored',
            'digitalOnly' => 'yes',
        ]);

        self::assertSame('', $structure->persons[0]['relation']);
        self::assertSame(['Handschriften'], $structure->mediaTypes);
        self::assertFalse($structure->digitalOnly);
        self::assertArrayNotHasKey('unknownField', $structure->toArray());
    }

    /**
     * @test
     */
    public function keepsOrderOfMediaTypesFromModelAnswer(): void
    {
        $structure = QueryStructure::fromArray(['mediaTypes' => ['Video', 'Audio', 'Video']]);

        self::assertSame(['Video', 'Audio'], $structure->mediaTypes);
    }

    /**
     * @test
     */
    public function allowsWildcardOnlyAtEndOfSingleWords(): void
    {
        $structure = QueryStructure::fromArray([
            'topics' => [['term' => 'Ex*il', 'variants' => ['Exil*', 'Zweiter Weltkrieg*', 'Ex*', '*']]],
        ]);

        self::assertSame('Ex il', $structure->topics[0]['term']);
        self::assertSame(['Exil*', 'Zweiter Weltkrieg', 'Ex'], $structure->topics[0]['variants']);
    }

    /**
     * @test
     */
    public function limitsListsAndValidatesYears(): void
    {
        $structure = QueryStructure::fromArray([
            'persons' => array_fill(0, 10, ['name' => 'Hesse', 'relation' => '']),
            'places' => ['Berlin', 'Berlin', 'Wien', 'Prag', 'Zürich'],
            'dateFrom' => 1950,
            'dateTo' => '1920',
        ]);

        self::assertCount(3, $structure->persons);
        self::assertSame(['Berlin', 'Wien'], $structure->places);
        self::assertSame(1920, $structure->dateFrom);
        self::assertSame(1950, $structure->dateTo);

        self::assertNull(QueryStructure::fromArray(['dateFrom' => 99999])->dateFrom);
        self::assertNull(QueryStructure::fromArray(['dateFrom' => 19.5])->dateFrom);
    }

    /**
     * @test
     */
    public function emptyAnswerIsEmpty(): void
    {
        self::assertTrue(QueryStructure::fromArray([])->isEmpty());
        self::assertTrue(QueryStructure::fromArray(['text' => '  ', 'persons' => [['name' => '"']]])->isEmpty());
        self::assertFalse(QueryStructure::fromArray(['digitalOnly' => true])->isEmpty());
    }

    /**
     * @test
     */
    public function roundTripsThroughArray(): void
    {
        $structure = QueryStructure::fromArray([
            'text' => 'Steppenwolf',
            'persons' => [['name' => 'Hesse', 'relation' => 'Von']],
            'dateFrom' => 1927,
        ]);

        self::assertEquals($structure, QueryStructure::fromArray($structure->toArray()));
    }
}
