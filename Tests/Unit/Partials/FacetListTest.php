<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Rendert Resources/Private/Partials/Facets/Facet/List.html (und transitiv .../List/Item.html)
 * mit einer handgeschriebenen Facetten-Auszählung (term => count), wie sie von
 * dla-find/Classes/Service/SolrServiceProvider.php aus den Solr-facet_counts gebaut wird.
 */
class FacetListTest extends FluidPartialTestCase
{
    private const SETTINGS = [
        'languageRootPath' => 'EXT:dla_opac_ng/Resources/Private/Language/',
        'jumpToID' => '',
        'facetDefaults' => ['displayDefault' => 10],
    ];

    /**
     * @test
     */
    public function rendersInactiveFacetTermsAsSelectableLinks(): void
    {
        $html = $this->renderPartial('Facets/Facet/List', [
            'facetInfo' => ['id' => 'filterType_mv', 'displayDefault' => 10, 'autocomplete' => false, 'collapse' => false],
            'facetData' => ['values' => ['Handschriften' => 42, 'Gedrucktes' => 17]],
            'config' => ['activeFacets' => []],
            'settings' => self::SETTINGS,
        ]);

        self::assertStringContainsString('facetList', $html);
        self::assertStringContainsString('count-42', $html);
        self::assertStringNotContainsString('facetActive', $html);
    }

    /**
     * @test
     */
    public function marksSelectedFacetTermAsActive(): void
    {
        $html = $this->renderPartial('Facets/Facet/List', [
            'facetInfo' => ['id' => 'filterType_mv', 'displayDefault' => 10, 'autocomplete' => false, 'collapse' => false],
            'facetData' => ['values' => ['Handschriften' => 42, 'Gedrucktes' => 17]],
            'config' => ['activeFacets' => [[['id' => 'filterType_mv', 'term' => 'Handschriften']]]],
            'settings' => self::SETTINGS,
        ]);

        self::assertStringContainsString('facetActive', $html);
    }
}
