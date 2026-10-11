<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Bildet eine geprüfte QueryStructure auf Facetten und Suchbegriffe des Katalogs ab.
 *
 * Die Facetten-IDs entsprechen der TypoScript-Konfiguration plugin.tx_find.settings.facets.
 * Personen und Orte, die sich nicht im Index auflösen lassen, werden nicht verworfen, sondern als weiche
 * Suchbegriffe ins Standardsuchfeld übernommen (beim Lockern werden sie zuerst entfernt).
 */
class SearchArgumentsBuilder
{
    private const FACET_PERSON = 'Personen';

    private const FACET_PERSON_RELATION = 'filterAuthorityRelation_mv';

    private const FACET_MEDIA_TYPE = 'Medientypen';

    private const FACET_FORM_CONTENT = 'FormUndInhalt';

    private const FACET_LANGUAGE = 'Sprache';

    private const FACET_PLACE = 'Ort';

    private const FACET_DIGITAL = 'Digital';

    private const MIN_DATE = 1000;

    /**
     * Felder der Facetten aus plugin.tx_find.settings.facets (Facetten-ID => Solr-Feld), mit denen sich
     * beibehaltene Facetten einer Anfrage für die Trefferzählung in Filter-Queries übersetzen lassen.
     */
    private const FACET_FIELDS = [
        'Personen' => 'filterAuthority_mv',
        'filterAuthorityRelation_mv' => 'filterAuthorityRelation_mv',
        'filterAuthorityRole_mv' => 'filterAuthorityRole_mv',
        'Medientypen' => 'filterType_mv',
        'FormUndInhalt' => 'filterFormContent_mv',
        'Medium' => 'filterMedium_mv',
        'Thema' => 'filterSubject_mv',
        'Sprache' => 'filterLanguage_mv',
        'filterLanguageType_mv' => 'filterLanguageType_mv',
        'Ort' => 'filterLocation_mv',
        'filterLocationType_mv' => 'filterLocationType_mv',
        'Sammlung' => 'filterCollection_mv',
        'Datenbestand' => 'filterSource',
        'Bibliographie' => 'filterBibliography_mv',
    ];

    /**
     * Facetten mit facettype = multi_select_facet: ausgewählte Werte werden ODER-verknüpft.
     */
    private const MULTI_SELECT_FACET_FIELDS = [
        'MedientypenHidden' => 'filterType_mv',
        'DatenbestandHidden' => 'filterSource',
    ];

    private const LABEL_MISSING = 'nicht bestimmt';

    public function __construct(
        private readonly VocabularyResolver $resolver
    ) {
    }

    public function getResolver(): VocabularyResolver
    {
        return $this->resolver;
    }

    public function build(QueryStructure $structure): QueryInterpretation
    {
        $facets = [];
        $textParts = [];

        if ($structure->text !== '') {
            $textParts[] = ['kind' => QueryInterpretation::PART_TEXT, 'query' => $structure->text, 'label' => $structure->text];
        }

        foreach ($structure->persons as $person) {
            $resolved = $this->resolver->resolvePerson($person['name']);
            if ($resolved === null) {
                $textParts[] = self::unresolvedPart($person['name']);
            } elseif ($person['relation'] !== '') {
                $term = $resolved['label'] . '␝' . $person['relation'];
                $facets[] = self::facet(self::FACET_PERSON_RELATION, $term, 'filterAuthorityRelation_mv');
            } else {
                $facets[] = self::facet(self::FACET_PERSON, $resolved['label'], 'filterAuthority_mv');
            }
        }

        // Mehrere Medientypen würden als getrennte Filter UND-verknüpft und so meist zu null Treffern führen;
        // daher wird nur der erste Medientyp übernommen.
        if ($structure->mediaTypes !== []) {
            $facets[] = self::facet(self::FACET_MEDIA_TYPE, $structure->mediaTypes[0], 'filterType_mv');
        }

        // Form/Inhalt und Sprache stammen aus dem Vokabular des Index (enum im JSON-Schema) und werden direkt
        // zu Facetten; ein Abgleich mit Solr ist nicht nötig.
        foreach ($structure->formContent as $value) {
            $facets[] = self::facet(self::FACET_FORM_CONTENT, $value, 'filterFormContent_mv');
        }
        foreach ($structure->languages as $value) {
            $facets[] = self::facet(self::FACET_LANGUAGE, $value, 'filterLanguage_mv');
        }

        // Orte sind zu viele für eine feste Werteliste: Abgleich mit dem Index, sonst weicher Suchbegriff.
        foreach ($structure->places as $value) {
            $resolved = $this->resolver->resolveFacetValue('filterLocation_mv', $value);
            if ($resolved === null) {
                $textParts[] = self::unresolvedPart($value);
            } else {
                $facets[] = self::facet(self::FACET_PLACE, $resolved, 'filterLocation_mv');
            }
        }

        if ($structure->digitalOnly) {
            $facets[] = ['facet' => self::FACET_DIGITAL, 'term' => 'true', 'filterQuery' => 'filterDigital:true'];
        }

        foreach ($structure->topics as $topic) {
            $textParts[] = [
                'kind' => QueryInterpretation::PART_TOPIC,
                'query' => self::topicQuery($topic['term'], $topic['variants']),
                'label' => $topic['term'],
            ];
        }

        $dateRange = null;
        if ($structure->dateFrom !== null || $structure->dateTo !== null) {
            $from = $structure->dateFrom ?? self::MIN_DATE;
            // Bei offenem Ende bis heute, aber nie vor dem Anfangsjahr (z.B. "ab 2030")
            $dateRange = ['from' => $from, 'to' => $structure->dateTo ?? max($from, (int)date('Y'))];
        }

        return new QueryInterpretation($facets, $textParts, $dateRange);
    }

    /**
     * Übersetzt Facetten aus den Such-Argumenten (tx_find_find[facet][ID][Wert] = 1 bzw. "not") in Filter-Queries,
     * wie sie das Such-Plugin erzeugt. Liefert null, sobald eine Facette nicht sicher übersetzt werden kann
     * (unbekannte ID, "nicht bestimmt", vordefinierte Facetten-Queries); dann ist keine Trefferzählung möglich.
     *
     * @return list<string>|null
     */
    public static function filterQueriesForFacets(array $facets): ?array
    {
        $filterQueries = [];
        foreach ($facets as $facetId => $terms) {
            if (!is_array($terms)) {
                return null;
            }
            $facetId = (string)$facetId;
            if (isset(self::MULTI_SELECT_FACET_FIELDS[$facetId])) {
                $values = array_map(static fn($term): string => VocabularyResolver::quote((string)$term), array_keys($terms));
                if ($values !== []) {
                    $filterQueries[] = self::MULTI_SELECT_FACET_FIELDS[$facetId] . ':(' . implode(' OR ', $values) . ')';
                }
                continue;
            }

            foreach ($terms as $term => $status) {
                $term = (string)$term;
                if ($facetId === self::FACET_DIGITAL) {
                    $filterQuery = 'filterDigital:true';
                } elseif ($facetId === 'Zeit' && preg_match('/^RANGE (-?\d+|\*) TO (-?\d+|\*)$/', $term, $matches)) {
                    $filterQuery = 'filterDateRange_mv:[' . $matches[1] . ' TO ' . $matches[2] . ']';
                } elseif (isset(self::FACET_FIELDS[$facetId]) && $term !== self::LABEL_MISSING) {
                    $filterQuery = self::FACET_FIELDS[$facetId] . ':(' . VocabularyResolver::quote($term) . ')';
                } else {
                    return null;
                }
                $filterQueries[] = ((string)$status === 'not' ? 'NOT ' : '') . $filterQuery;
            }
        }

        return $filterQueries;
    }

    /**
     * Thema mit Varianten als ODER-Gruppe, z.B. "(exil OR exil* OR emigration)".
     *
     * @param list<string> $variants
     */
    public static function topicQuery(string $term, array $variants): string
    {
        $terms = array_values(array_unique(array_map(self::termQuery(...), [$term, ...$variants])));

        return count($terms) === 1 ? $terms[0] : '(' . implode(' OR ', $terms) . ')';
    }

    private static function termQuery(string $term): string
    {
        $term = mb_strtolower($term);
        if (str_ends_with($term, '*')) {
            // Platzhaltersuchen durchlaufen nicht alle Filter der Feldanalyse; die Normalisierung des
            // Standardsuchfelds (texts_normalized) wird deshalb hier vorweggenommen.
            return self::normalizeLikeIndex(substr($term, 0, -1)) . '*';
        }

        return str_contains($term, ' ') ? '"' . $term . '"' : $term;
    }

    private static function normalizeLikeIndex(string $term): string
    {
        $term = strtr($term, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $term = str_replace('ph', 'f', $term);
        $ascii = function_exists('transliterator_transliterate')
            ? transliterator_transliterate('Any-Latin; Latin-ASCII', $term)
            : iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $term);

        return is_string($ascii) && $ascii !== '' ? $ascii : $term;
    }

    private static function unresolvedPart(string $value): array
    {
        return [
            'kind' => QueryInterpretation::PART_UNRESOLVED,
            'query' => mb_strtolower($value),
            'label' => $value,
        ];
    }

    private static function facet(string $facetId, string $term, string $field): array
    {
        return [
            'facet' => $facetId,
            'term' => $term,
            'filterQuery' => $field . ':(' . VocabularyResolver::quote($term) . ')',
        ];
    }
}
