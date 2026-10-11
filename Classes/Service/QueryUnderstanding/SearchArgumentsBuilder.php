<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Bildet eine geprüfte QueryStructure auf Facetten und Suchbegriffe des Katalogs ab.
 *
 * Die Facetten-IDs entsprechen der TypoScript-Konfiguration plugin.tx_find.settings.facets.
 * Werte, die sich nicht im Index auflösen lassen, werden nicht verworfen, sondern als weiche
 * Suchbegriffe ins Standardsuchfeld übernommen.
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

    public function __construct(
        private readonly VocabularyResolver $resolver = new VocabularyResolver()
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

        foreach ([
            [self::FACET_FORM_CONTENT, 'filterFormContent_mv', $structure->formContent],
            [self::FACET_LANGUAGE, 'filterLanguage_mv', $structure->languages],
            [self::FACET_PLACE, 'filterLocation_mv', $structure->places],
        ] as [$facetId, $field, $values]) {
            foreach ($values as $value) {
                $resolved = $this->resolver->resolveFacetValue($field, $value);
                if ($resolved === null) {
                    $textParts[] = self::unresolvedPart($value);
                } else {
                    $facets[] = self::facet($facetId, $resolved, $field);
                }
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
            $dateRange = [
                'from' => $structure->dateFrom ?? self::MIN_DATE,
                'to' => $structure->dateTo ?? (int)date('Y'),
            ];
        }

        return new QueryInterpretation($facets, $textParts, $dateRange);
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
