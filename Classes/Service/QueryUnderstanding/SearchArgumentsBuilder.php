<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Bildet eine geprüfte QueryStructure auf Facetten und Suchbegriffe des Katalogs ab.
 *
 * Die Facetten-IDs entsprechen der TypoScript-Konfiguration plugin.tx_find.settings.facets. Medientyp, Form/Inhalt
 * und Sprache stammen aus dem Vokabular des Index (FacetVocabulary) und werden direkt zu Facetten. Personen und Orte
 * werden mit dem Index abgeglichen; was sich nicht auflösen lässt, wird zum Suchbegriff.
 */
class SearchArgumentsBuilder
{
    private const MIN_DATE = 1000;

    public function __construct(
        private readonly VocabularyResolver $resolver
    ) {
    }

    public function build(QueryStructure $structure): QueryInterpretation
    {
        $facets = [];
        $textParts = [];

        if ($structure->text !== '') {
            $textParts[] = self::textPart(QueryInterpretation::PART_TEXT, $structure->text, $structure->text);
        }

        foreach ($structure->persons as $person) {
            $resolved = $this->resolver->resolvePerson($person['name']);
            if ($resolved === null) {
                $textParts[] = self::textPart(QueryInterpretation::PART_UNRESOLVED, mb_strtolower($person['name']), $person['name']);
            } elseif ($person['relation'] !== '') {
                $facets[] = self::facet('filterAuthorityRelation_mv', $resolved['label'] . '␝' . $person['relation']);
            } else {
                $facets[] = self::facet('Personen', $resolved['label']);
            }
        }

        // Mehrere Medientypen würden als getrennte Filter UND-verknüpft; daher nur der erste.
        if ($structure->mediaTypes !== []) {
            $facets[] = self::facet('Medientypen', $structure->mediaTypes[0]);
        }
        foreach ($structure->formContent as $value) {
            $facets[] = self::facet('FormUndInhalt', $value);
        }
        foreach ($structure->languages as $value) {
            $facets[] = self::facet('Sprache', $value);
        }
        foreach ($structure->places as $place) {
            $resolved = $this->resolver->resolveFacetValue('filterLocation_mv', $place);
            if ($resolved === null) {
                $textParts[] = self::textPart(QueryInterpretation::PART_UNRESOLVED, mb_strtolower($place), $place);
            } else {
                $facets[] = self::facet('Ort', $resolved);
            }
        }
        if ($structure->dateFrom !== null || $structure->dateTo !== null) {
            $from = $structure->dateFrom ?? self::MIN_DATE;
            // Bei offenem Ende bis heute, aber nie vor dem Anfangsjahr (z.B. "ab 2030")
            $to = $structure->dateTo ?? max($from, (int)date('Y'));
            $facets[] = self::facet('Zeit', sprintf('RANGE %d TO %d', $from, $to));
        }
        if ($structure->digitalOnly) {
            $facets[] = self::facet('Digital', 'true');
        }

        foreach ($structure->topics as $topic) {
            $textParts[] = self::textPart(QueryInterpretation::PART_TOPIC, self::topicQuery($topic['term'], $topic['variants']), $topic['term']);
        }

        return new QueryInterpretation($facets, $textParts);
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
            $stem = str_replace('ph', 'f', strtr(substr($term, 0, -1), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']));
            $ascii = transliterator_transliterate('Any-Latin; Latin-ASCII', $stem);

            return (is_string($ascii) && $ascii !== '' ? $ascii : $stem) . '*';
        }

        return str_contains($term, ' ') ? '"' . $term . '"' : $term;
    }

    private static function textPart(string $kind, string $query, string $label): array
    {
        return ['kind' => $kind, 'query' => $query, 'label' => $label];
    }

    private static function facet(string $facetId, string $term): array
    {
        return ['facet' => $facetId, 'term' => $term];
    }
}
