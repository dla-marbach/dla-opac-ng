<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class FacetsJsonViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('facets', 'array', 'Facets configuration from TypoScript settings', true);
        $this->registerArgument('additionalFilters', 'array', 'Additional Solr filters from TypoScript settings', false, []);
    }

    public function render(): string
    {
        $fieldMap = [];   // id → solr field name (standard facets)
        $queryMap = [];   // id → { termId → solr query } (facetQuery facets like NeuImKatalog)
        $fixedMap = [];   // id → fixed solr query (no placeholder, e.g. Digital switch)
        $additionalFilters = [];

        foreach ($this->arguments['facets'] as $facet) {
            $id = $facet['id'] ?? '';
            if ($id === '') {
                continue;
            }

            if (!empty($facet['facetQuery']) && is_array($facet['facetQuery'])) {
                foreach ($facet['facetQuery'] as $entry) {
                    if (!empty($entry['id']) && !empty($entry['query'])) {
                        $queryMap[$id][$entry['id']] = $entry['query'];
                    }
                }
                continue;
            }

            if (!empty($facet['field'])) {
                $fieldMap[$id] = $facet['field'];
                continue;
            }

            if (!empty($facet['query']) && strpos($facet['query'], '%s') === false) {
                $fixedMap[$id] = $facet['query'];
            }
        }

        $this->collectFilters((array)$this->arguments['additionalFilters'], $additionalFilters);

        return (string)json_encode(
            [
                'fieldMap' => $fieldMap,
                'queryMap' => $queryMap,
                'fixedMap' => $fixedMap,
                'additionalFilters' => $additionalFilters,
            ],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
        );
    }

    private function collectFilters(array $items, array &$filters): void
    {
        foreach ($items as $item) {
            if (is_array($item)) {
                $this->collectFilters($item, $filters);
                continue;
            }

            if (is_string($item)) {
                $item = trim($item);
                if ($item !== '') {
                    $filters[] = $item;
                }
            }
        }
    }
}
