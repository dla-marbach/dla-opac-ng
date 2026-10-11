<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\SolrConnection;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Tatsächliche Werte der kleinen Facetten (Medientyp, Form/Inhalt, Sprache) aus dem Index.
 *
 * Die häufigsten Werte jeder Facette werden per Facettenabfrage geholt, einen Tag zwischengespeichert und dem
 * Modell als erlaubte Werte (enum im JSON-Schema) vorgegeben. So liefert das Modell nur Werte, die es im Index
 * gibt, und es braucht keine Abgleichs- oder Zählabfragen mehr.
 */
class FacetVocabulary
{
    /**
     * Feldname im JSON-Schema => [Solr-Feld, maximale Anzahl Werte]
     */
    public const FIELDS = [
        'mediaTypes' => ['filterType_mv', 20],
        'formContent' => ['filterFormContent_mv', 40],
        'languages' => ['filterLanguage_mv', 25],
    ];

    /**
     * Rückfall für die Medientypen, falls Solr nicht erreichbar ist (Werte laut Datendienst-Dokumentation).
     */
    public const FALLBACK_MEDIA_TYPES = ['Gedrucktes', 'Handschriften', 'Bilder und Objekte', 'Audio', 'Video', 'Normdaten', 'Daten'];

    private const CACHE_IDENTIFIER = 'vocabulary';

    private const CACHE_LIFETIME = 86400;

    /**
     * Facettenabfragen über den gesamten Index dürfen länger dauern als die schnellen Ajax-Anfragen.
     */
    private const REQUEST_TIMEOUT = 5.0;

    /** @var array<string, list<string>>|null */
    private ?array $values = null;

    public function __construct(
        private readonly SolrConnection $solrConnection,
        private readonly FrontendInterface $cache,
    ) {
    }

    /**
     * Erlaubte Werte eines Feldes (alphabetisch sortiert, damit die Prompt-Version stabil bleibt,
     * auch wenn sich die Häufigkeiten verschieben).
     *
     * @return list<string>
     */
    public function getValues(string $field): array
    {
        return $this->getAll()[$field] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function getAll(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $cached = $this->cache->get(self::CACHE_IDENTIFIER);
        if (is_array($cached)) {
            return $this->values = $cached;
        }

        $values = $this->fetch();
        if ($values !== null) {
            // Nur bei erfolgreicher Abfrage zwischenspeichern, damit ein Solr-Ausfall nicht einen Tag lang wirkt.
            $this->cache->set(self::CACHE_IDENTIFIER, $values, [], self::CACHE_LIFETIME);
        } else {
            $values = array_fill_keys(array_keys(self::FIELDS), []);
            $values['mediaTypes'] = self::FALLBACK_MEDIA_TYPES;
        }

        return $this->values = $values;
    }

    /**
     * @return array<string, list<string>>|null null bei Verbindungsfehlern
     */
    private function fetch(): ?array
    {
        $params = [
            'q' => '*:*',
            'rows' => '0',
            'fl' => 'id',
            'facet' => 'true',
            'facet.mincount' => '1',
            'facet.sort' => 'count',
            'json.nl' => 'map',
            'facet.field' => [],
        ];
        foreach (self::FIELDS as [$solrField, $limit]) {
            $params['facet.field'][] = $solrField;
            $params['f.' . $solrField . '.facet.limit'] = (string)$limit;
        }

        $json = $this->solrConnection->request('select', $params, self::REQUEST_TIMEOUT);
        $facetFields = $json['facet_counts']['facet_fields'] ?? null;
        if (!is_array($facetFields)) {
            return null;
        }

        $values = [];
        foreach (self::FIELDS as $field => [$solrField]) {
            $terms = array_map('strval', array_keys(is_array($facetFields[$solrField] ?? null) ? $facetFields[$solrField] : []));
            $terms = array_values(array_filter($terms, static fn(string $term): bool => trim($term) !== ''));
            sort($terms, SORT_STRING);
            $values[$field] = $terms;
        }
        if ($values['mediaTypes'] === []) {
            $values['mediaTypes'] = self::FALLBACK_MEDIA_TYPES;
        }

        return $values;
    }
}
