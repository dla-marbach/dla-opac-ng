<?php

namespace Dla\DlaOpacNg\Service;

class EntityService
{
    private const MAX_ENTITIES_PER_REQUEST = 200;

    public function __construct(
        private readonly SolrConnection $solrConnection = new SolrConnection()
    ) {
    }

    private function composeTitle(array $doc): string
    {
        $display = !empty($doc['display']) ? htmlspecialchars((string)$doc['display']) : '';
        $displayName = !empty($doc['displayName']) ? htmlspecialchars((string)$doc['displayName']) : '';
        $displayAddition1 = !empty($doc['displayAddition1']) ? htmlspecialchars((string)$doc['displayAddition1']) : '';
        $displayAddition2 = !empty($doc['displayAddition2']) ? htmlspecialchars((string)$doc['displayAddition2']) : '';

        $titleParts = array_values(array_filter([$displayName, $displayAddition1, $displayAddition2], static fn(string $value): bool => $value !== ''));
        $title = '';

        if (!empty($titleParts)) {
            $title = implode('. - ', $titleParts);
            if ($display !== '') {
                $title = $display . ' / ' . $title;
            }
        } elseif ($display !== '') {
            $title = $display;
        }

        if ($title === '' && !empty($doc['title'])) {
            $title = htmlspecialchars((string)$doc['title']);
        }
        if ($title === '' && !empty($doc['titleMain_text'])) {
            $title = htmlspecialchars((string)$doc['titleMain_text']);
        }
        if ($title === '' && !empty($doc['id'])) {
            $title = htmlspecialchars((string)$doc['id']);
        }

        return $title;
    }

    private function escapeSolrTerm(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], trim($value));
    }

    public function getEntity($id): array
    {
        // Array of entity facts
        $entity = [];

        // Get Solr record
        $json = $this->solrConnection->request('select', ['q' => 'id:(' . $id . ')', 'rows' => 1]);

        if ($json !== null) {
            $responseDocument = $json['response']['docs'][0];
            $entity = [
                'id' => htmlspecialchars($responseDocument['id']),
                'display' => !empty($responseDocument['display']) ? htmlspecialchars($responseDocument['display']) : '',
                'displayName' => !empty($responseDocument['displayName']) ? htmlspecialchars($responseDocument['displayName']) : '',
                'displayAddition1' => !empty($responseDocument['displayAddition1']) ? htmlspecialchars($responseDocument['displayAddition1']) : '',
                'displayAddition2' => !empty($responseDocument['displayAddition2']) ? htmlspecialchars($responseDocument['displayAddition2']) : '',
            ];
        }

        return $entity;
    }

    public function getEntities(String $query): array
    {
        $entities = [];

        $ids = array_filter(array_map('trim', explode(',', $query)), static fn(string $id): bool => $id !== '');
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return [];
        }
        $ids = array_slice($ids, 0, self::MAX_ENTITIES_PER_REQUEST);
        $escapedIds = array_map([$this, 'escapeSolrTerm'], $ids);
        $solrIdQuery = 'id:("' . implode('" OR "', $escapedIds) . '")';

        // Get Solr records
        $json = $this->solrConnection->request('select', ['q' => $solrIdQuery, 'rows' => count($ids)]);

        if ($json !== null) {
            foreach ($json['response']['docs'] as $key => $doc) {
                $title = $this->composeTitle($doc);

                $entities[] = [
                    'id' => htmlspecialchars($doc['id']),
                    'display' => !empty($doc['display']) ? htmlspecialchars($doc['display']) : '',
                    'displayName' => !empty($doc['displayName']) ? htmlspecialchars($doc['displayName']) : '',
                    'displayAddition1' => !empty($doc['displayAddition1']) ? htmlspecialchars($doc['displayAddition1']) : '',
                    'filterType_mv' => !empty($doc['filterType_mv']) ? array_map('htmlspecialchars', (array)$doc['filterType_mv']) : [],
                    'filterFormContent_mv' => !empty($doc['filterFormContent_mv']) ? array_map('htmlspecialchars', $doc['filterFormContent_mv']) : '',
                    'filterMedium_mv' => !empty($doc['filterMedium_mv']) ? array_map('htmlspecialchars', $doc['filterMedium_mv']) : '',
                    'filterDateRange_mv' => !empty($doc['filterDateRange_mv']) ? array_map('htmlspecialchars', $doc['filterDateRange_mv']) : '',
                    'filterAuthority_mv' => !empty($doc['filterAuthority_mv']) ? array_map('htmlspecialchars', $doc['filterAuthority_mv']) : '',
                    'filterSubject_mv' => !empty($doc['filterSubject_mv']) ? array_map('htmlspecialchars', $doc['filterSubject_mv']) : '',
                    'filterLanguage_mv' => !empty($doc['filterLanguage_mv']) ? array_map('htmlspecialchars', $doc['filterLanguage_mv']) : '',
                    'filterLocation_mv' => !empty($doc['filterLocation_mv']) ? array_map('htmlspecialchars', $doc['filterLocation_mv']) : '',
                    'filterSource' => !empty($doc['filterSource']) ? htmlspecialchars($doc['filterSource']) : '',
                    'filterBibliography_mv' => !empty($doc['filterBibliography_mv']) ? array_map('htmlspecialchars', $doc['filterBibliography_mv']) : '',
                    'filterCollection_mv' => !empty($doc['filterCollection_mv']) ? array_map('htmlspecialchars', $doc['filterCollection_mv']) : '',
                    'filterDigital' => !empty($doc['filterDigital']) ? $doc['filterDigital'] : '',
                    'title' => $title,
                ];
            }
            return $entities;
        }
        return [];
    }
}