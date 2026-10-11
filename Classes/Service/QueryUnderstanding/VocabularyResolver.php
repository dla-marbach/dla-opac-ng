<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\SolrConnection;

/**
 * Gleicht Personen, Orte und Themen, für die es zu viele Werte für eine feste Werteliste gibt, mit dem Index ab.
 *
 * Personen werden über den Normdaten-Suggester (mySuggester, wie die Autovervollständigung) aufgelöst, Orte und
 * Themen über die Facettenwerte. Nur Werte, die es im Index gibt, werden zu Filtern.
 */
class VocabularyResolver
{
    private const SUGGEST_DICTIONARY = 'mySuggester';

    /**
     * Die Facettensuche über alle Werte eines großen Feldes darf länger dauern als die schnellen Ajax-Anfragen.
     */
    private const FACET_REQUEST_TIMEOUT = 3.0;

    /** @var array<string,mixed> */
    private array $memo = [];

    public function __construct(
        private readonly SolrConnection $solrConnection
    ) {
    }

    /**
     * Löst einen Namen auf einen Normdatensatz auf: der erste (relevanteste) Vorschlag, der alle Namensbestandteile
     * enthält, damit aus "Mann" nicht zufällig "Mannheim" wird.
     *
     * @return array{id: string, label: string}|null label ist der Wert der Facette filterAuthority_mv
     */
    public function resolvePerson(string $name): ?array
    {
        $key = 'person|' . $name;
        if (!array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $this->findPerson($name);
        }

        return $this->memo[$key];
    }

    /**
     * Facettenwert, der den Begriff enthält (Groß-/Kleinschreibung egal): die exakte Schreibweise, sonst der
     * häufigste Wert, z.B. "Marbach" → "Marbach am Neckar". Mit $exactOnly nur die exakte Schreibweise.
     */
    public function resolveFacetValue(string $field, string $value, bool $exactOnly = false): ?string
    {
        $values = $this->facetValuesContaining($field, $value);
        $exact = array_values(array_filter($values, static fn(string $candidate): bool => mb_strtolower($candidate) === mb_strtolower($value)));

        return $exact[0] ?? ($exactOnly ? null : $values[0] ?? null);
    }

    /**
     * @return list<string> die häufigsten Werte des Feldes, die den Begriff enthalten
     */
    private function facetValuesContaining(string $field, string $value): array
    {
        $key = 'facet|' . $field . '|' . $value;
        if (!array_key_exists($key, $this->memo)) {
            $json = $this->solrConnection->request('select', [
                'q' => '*:*',
                'rows' => '0',
                'facet' => 'true',
                'facet.field' => $field,
                'facet.contains' => $value,
                'facet.contains.ignoreCase' => 'true',
                'facet.mincount' => '1',
                'facet.limit' => '10',
                'facet.sort' => 'count',
                'json.nl' => 'map',
            ], self::FACET_REQUEST_TIMEOUT);
            $this->memo[$key] = array_map('strval', array_keys((array)($json['facet_counts']['facet_fields'][$field] ?? [])));
        }

        return $this->memo[$key];
    }

    private function findPerson(string $name): ?array
    {
        $json = $this->solrConnection->request('suggest', [
            'suggest' => 'true',
            'suggest.dictionary' => self::SUGGEST_DICTIONARY,
            'suggest.q' => $name,
        ]);
        $nameTokens = self::tokens($name);

        foreach ((array)($json['suggest'][self::SUGGEST_DICTIONARY][$name]['suggestions'] ?? []) as $suggestion) {
            [$id, $label] = array_pad(explode('␝', (string)($suggestion['payload'] ?? ''), 2), 2, '');
            if ($id !== '' && $label !== '' && array_diff($nameTokens, self::tokens(($suggestion['term'] ?? '') . ' ' . $label)) === []) {
                return ['id' => $id, 'label' => $label];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function tokens(string $value): array
    {
        return array_values(array_unique(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($value), -1, PREG_SPLIT_NO_EMPTY) ?: []));
    }
}
