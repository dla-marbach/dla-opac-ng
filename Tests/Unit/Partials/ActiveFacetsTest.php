<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Rendert Resources/Private/Partials/Display/List/ActiveFacets.html und prüft das Zusammenspiel mit der
 * natürlichsprachigen Suche: Die Box zeigt alle Filter wie bei einer normalen Suche, auch die aus der Frage
 * (die Interpretationszeile zeigt sie zusätzlich).
 */
class ActiveFacetsTest extends FluidPartialTestCase
{
    private const FACETS = [
        ['id' => 'Medientypen', 'field' => 'filterType_mv'],
        ['id' => 'Sprache', 'field' => 'filterLanguage_mv'],
        ['id' => 'filterAuthorityRelation_mv', 'field' => 'filterAuthorityRelation_mv', 'hidden' => 1],
    ];

    private const FACET_VALUES = [
        'Medientypen' => ['values' => ['Handschriften' => 12, 'Gedrucktes' => 3]],
        'Sprache' => ['values' => ['Deutsch' => 15]],
        'filterAuthorityRelation_mv' => ['values' => ['Hesse, Hermann (1877-1962)␝Von' => 15]],
    ];

    private function renderActiveFacets(array $arguments): string
    {
        $activeFacets = [];
        foreach ($arguments['facet'] ?? [] as $facetId => $terms) {
            foreach (array_keys($terms) as $term) {
                $activeFacets[$facetId][$term] = ['id' => $facetId, 'term' => $term];
            }
        }

        return $this->renderPartial('Display/List/ActiveFacets', [
            'arguments' => $arguments,
            'config' => ['facets' => self::FACETS, 'activeFacets' => $activeFacets],
            'results' => ['components' => ['facetset' => ['facets' => self::FACET_VALUES]]],
            'settings' => ['jumpToID' => ''],
        ]);
    }

    /**
     * @test
     */
    public function showsAllActiveFiltersWithoutNaturalLanguageSearch(): void
    {
        $html = $this->renderActiveFacets([
            'facet' => [
                'Medientypen' => ['Handschriften' => '1'],
                'filterAuthorityRelation_mv' => ['Hesse, Hermann (1877-1962)␝Von' => '1'],
            ],
        ]);

        self::assertStringContainsString('active-facets', $html);
        self::assertStringContainsString('Handschriften', $html);
        self::assertStringContainsString('Hesse, Hermann (1877-1962) in Relation Von', $html);
    }

    /**
     * @test
     */
    public function showsFiltersFromTheQuestionLikeAnyOtherFilter(): void
    {
        $html = $this->renderActiveFacets([
            'facet' => [
                'Medientypen' => ['Handschriften' => '1'],
                'Sprache' => ['Deutsch' => '1'],
                'filterAuthorityRelation_mv' => ['Hesse, Hermann (1877-1962)␝Von' => '1'],
            ],
            'nlq' => 'Handschriften von Hesse',
            'nlstatus' => 'ok',
            'nlf' => [
                ['f' => 'Medientypen', 't' => 'Handschriften'],
                ['f' => 'filterAuthorityRelation_mv', 't' => 'Hesse, Hermann (1877-1962)␝Von'],
            ],
        ]);

        self::assertStringContainsString('active-facets', $html);
        self::assertStringContainsString('Deutsch', $html);
        self::assertStringContainsString('Handschriften', $html);
        self::assertStringContainsString('Hesse, Hermann (1877-1962) in Relation Von', $html);
    }

    /**
     * @test
     */
    public function showsBoxWhenAllFiltersComeFromTheQuestion(): void
    {
        $html = $this->renderActiveFacets([
            'q' => ['default' => 'exil'],
            'facet' => ['Medientypen' => ['Handschriften' => '1']],
            'nlq' => 'Handschriften über Exil',
            'nlstatus' => 'ok',
            'nlf' => [['f' => 'Medientypen', 't' => 'Handschriften']],
        ]);

        self::assertStringContainsString('active-facets', $html);
        self::assertStringContainsString('Handschriften', $html);
    }
}
