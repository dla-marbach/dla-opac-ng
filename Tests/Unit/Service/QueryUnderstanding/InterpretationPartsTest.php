<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\InterpretationParts;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class InterpretationPartsTest extends UnitTestCase
{
    /**
     * Such-Argumente nach der Weiterleitung für "Briefe von Hesse an Thomas Mann aus den 1930ern über Exil",
     * dazu ein selbst gewählter Filter (Sprache) und ein Ausschluss (Ort).
     */
    private const ARGUMENTS = [
        'q' => ['default' => 'steppenwolf (exil OR emigration) atlantis'],
        'facet' => [
            'Sprache' => ['Deutsch' => '1'],
            'Ort' => ['Berlin' => 'not'],
            'filterAuthorityRelation_mv' => [
                'Hesse, Hermann (1877-1962)␝Von' => '1',
                'Mann, Thomas (1875-1955)␝An' => '1',
            ],
            'Medientypen' => ['Handschriften' => '1'],
            'FormUndInhalt' => ['Brief' => '1'],
            'Zeit' => ['RANGE 1930 TO 1939' => '1'],
        ],
        'nlq' => 'Briefe von Hesse an Thomas Mann aus den 1930ern über Exil',
        'nlstatus' => 'ok',
        'nlf' => [
            ['f' => 'filterAuthorityRelation_mv', 't' => 'Hesse, Hermann (1877-1962)␝Von'],
            ['f' => 'filterAuthorityRelation_mv', 't' => 'Mann, Thomas (1875-1955)␝An'],
            ['f' => 'Medientypen', 't' => 'Handschriften'],
            ['f' => 'FormUndInhalt', 't' => 'Brief'],
            ['f' => 'Zeit', 't' => 'RANGE 1930 TO 1939'],
        ],
        'nlt' => [
            ['k' => 'text', 'q' => 'steppenwolf', 'l' => 'Steppenwolf'],
            ['k' => 'unresolved', 'q' => 'atlantis', 'l' => 'Atlantis'],
            ['k' => 'topic', 'q' => '(exil OR emigration)', 'l' => 'Exil'],
        ],
    ];

    /**
     * @test
     */
    public function listsActivePartsOfTheQuestionInReadingOrder(): void
    {
        $parts = InterpretationParts::fromArguments(self::ARGUMENTS);

        self::assertSame(
            ['mediaType', 'formContent', 'relationVon', 'relationAn', 'date', 'text', 'topic', 'unresolved'],
            array_column($parts, 'role')
        );
        self::assertSame(
            ['Handschriften', 'Brief', 'Hesse, Hermann (1877-1962)', 'Mann, Thomas (1875-1955)', '1930–1939', 'Steppenwolf', 'Exil', 'Atlantis'],
            array_column($parts, 'label')
        );
        self::assertSame(
            ['type' => 'facet', 'role' => 'relationVon', 'label' => 'Hesse, Hermann (1877-1962)', 'facetId' => 'filterAuthorityRelation_mv', 'term' => 'Hesse, Hermann (1877-1962)␝Von'],
            $parts[2]
        );
    }

    /**
     * @test
     */
    public function textPartsCarryTheSearchFieldWithoutThemselves(): void
    {
        $parts = array_column(InterpretationParts::fromArguments(self::ARGUMENTS), 'remainingQuery', 'label');

        self::assertSame('(exil OR emigration) atlantis', $parts['Steppenwolf']);
        self::assertSame('steppenwolf atlantis', $parts['Exil']);
        self::assertSame('steppenwolf (exil OR emigration)', $parts['Atlantis']);
    }

    /**
     * @test
     */
    public function removedPartsDisappear(): void
    {
        $arguments = self::ARGUMENTS;
        // Filter bei den aktiven Filtern entfernt, Zeitraum per Schieberegler geändert, Thema aus dem Suchfeld gelöscht
        unset($arguments['facet']['Medientypen']);
        $arguments['facet']['Zeit'] = ['RANGE 1920 TO 1939' => '1'];
        $arguments['q']['default'] = 'steppenwolf atlantis';

        $labels = array_column(InterpretationParts::fromArguments($arguments), 'label');

        self::assertNotContains('Handschriften', $labels);
        self::assertNotContains('1930–1939', $labels);
        self::assertNotContains('Exil', $labels);
        self::assertContains('Brief', $labels);
    }

    /**
     * @test
     */
    public function questionFacetsAreSeparatedFromOtherActiveFacets(): void
    {
        self::assertTrue(InterpretationParts::isQuestionFacet(self::ARGUMENTS, 'Medientypen', 'Handschriften'));
        self::assertTrue(InterpretationParts::isQuestionFacet(self::ARGUMENTS, 'Zeit', 'RANGE 1930 TO 1939'));
        self::assertFalse(InterpretationParts::isQuestionFacet(self::ARGUMENTS, 'Sprache', 'Deutsch'));
        self::assertTrue(InterpretationParts::hasOtherActiveFacets(self::ARGUMENTS));

        $onlyQuestion = self::ARGUMENTS;
        unset($onlyQuestion['facet']['Sprache'], $onlyQuestion['facet']['Ort']);
        self::assertFalse(InterpretationParts::hasOtherActiveFacets($onlyQuestion));
    }

    /**
     * @test
     */
    public function withoutQuestionEverythingIsAnOrdinaryFilter(): void
    {
        $arguments = self::ARGUMENTS;
        unset($arguments['nlq']);

        self::assertSame([], InterpretationParts::fromArguments($arguments));
        self::assertFalse(InterpretationParts::isQuestionFacet($arguments, 'Medientypen', 'Handschriften'));
        self::assertTrue(InterpretationParts::hasOtherActiveFacets($arguments));
        self::assertFalse(InterpretationParts::hasOtherActiveFacets(['nlq' => 'x']));
    }

    /**
     * @test
     */
    public function exclusionsAndUnknownFacetsAreNotPartOfTheInterpretation(): void
    {
        $arguments = self::ARGUMENTS;
        $arguments['facet']['Medientypen']['Handschriften'] = 'not';
        $arguments['nlf'][] = ['f' => 'NeuImKatalog', 't' => 'Woche'];
        $arguments['facet']['NeuImKatalog'] = ['Woche' => '1'];

        $labels = array_column(InterpretationParts::fromArguments($arguments), 'label');

        self::assertNotContains('Handschriften', $labels);
        self::assertNotContains('Woche', $labels);
        self::assertFalse(InterpretationParts::isQuestionFacet($arguments, 'Medientypen', 'Handschriften'));
        self::assertFalse(InterpretationParts::isQuestionFacet($arguments, 'NeuImKatalog', 'Woche'));
    }

    /**
     * @test
     */
    public function ignoresMalformedParameters(): void
    {
        $arguments = ['nlq' => 'Frage', 'nlf' => 'kaputt', 'nlt' => [['k' => 'topic'], 'x'], 'facet' => 'kaputt', 'q' => 'kaputt'];

        self::assertSame([], InterpretationParts::fromArguments($arguments));
        self::assertFalse(InterpretationParts::hasOtherActiveFacets($arguments));
    }

    /**
     * @test
     */
    public function removeFromQueryNormalizesWhitespace(): void
    {
        self::assertSame('a c', InterpretationParts::removeFromQuery('a  b   c', 'b'));
        self::assertSame('', InterpretationParts::removeFromQuery('b', 'b'));
        self::assertSame('a', InterpretationParts::removeFromQuery('a', 'z'));
    }
}
