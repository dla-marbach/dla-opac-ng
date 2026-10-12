<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\SolrConnection;

/**
 * Gleicht Personen und Orte, für die es zu viele Werte für eine feste Werteliste gibt, mit dem Index ab.
 *
 * Personen werden über den Normdaten-Suggester (mySuggester, wie die Autovervollständigung) aufgelöst, Orte über
 * die Facettenwerte. Nur Werte, die es im Index gibt, werden zu Filtern.
 */
class VocabularyResolver
{
    private const SUGGEST_DICTIONARY = 'mySuggester';

    /**
     * Die Facettensuche über alle Werte eines großen Feldes darf länger dauern als die schnellen Ajax-Anfragen.
     */
    private const FACET_REQUEST_TIMEOUT = 3.0;

    /**
     * Die exakte Schreibweise wird genommen, solange der häufigste enthaltende Wert höchstens zehnmal so häufig ist.
     */
    private const EXACT_MATCH_RATIO = 10;

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
     * Facettenwert zum Begriff: die exakte Schreibweise (Groß-/Kleinschreibung egal) oder der häufigste Wert, der den
     * Begriff enthält, z.B. "Marbach" → "Marbach am Neckar". Die exakte Schreibweise gewinnt nur, wenn sie nicht viel
     * seltener ist (EXACT_MATCH_RATIO): "Berlin" bleibt "Berlin" statt "Berlin-Charlottenburg", aber ein seltenes
     * "Marbach" unterliegt dem häufigen "Marbach am Neckar".
     */
    public function resolveFacetValue(string $field, string $value): ?string
    {
        $key = 'facet|' . $field . '|' . $value;
        if (!array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $this->lookupFacetValue($field, $value);
        }

        return $this->memo[$key];
    }

    /**
     * Eine Anfrage für beides: Die exakte Schreibweise wird unabhängig von der Häufigkeit über facet.query gezählt
     * (in gängigen Schreibweisen, weil die Facettenfelder Groß-/Kleinschreibung unterscheiden), die enthaltenden
     * Werte über facet.contains (die zehn häufigsten).
     */
    private function lookupFacetValue(string $field, string $value): ?string
    {
        $lower = mb_strtolower($value);
        $variants = array_values(array_unique([
            $value,
            $lower,
            mb_strtoupper(mb_substr($lower, 0, 1)) . mb_substr($lower, 1),
            mb_convert_case($lower, MB_CASE_TITLE),
        ]));
        $exactQuery = static fn(string $variant): string => $field . ':"' . addcslashes($variant, '"\\') . '"';

        $json = $this->solrConnection->request('select', [
            'q' => '*:*',
            'rows' => '0',
            'facet' => 'true',
            'facet.query' => array_map($exactQuery, $variants),
            'facet.field' => $field,
            'facet.contains' => $value,
            'facet.contains.ignoreCase' => 'true',
            'facet.mincount' => '1',
            'facet.limit' => '10',
            'facet.sort' => 'count',
            'json.nl' => 'map',
        ], self::FACET_REQUEST_TIMEOUT);

        /** @var array<string, int> $counts Wert => Anzahl, exakte Schreibweisen und enthaltende Werte */
        $counts = [];
        foreach ($variants as $variant) {
            $count = (int)($json['facet_counts']['facet_queries'][$exactQuery($variant)] ?? 0);
            if ($count > 0) {
                $counts[$variant] = $count;
            }
        }
        foreach ((array)($json['facet_counts']['facet_fields'][$field] ?? []) as $candidate => $count) {
            $counts[(string)$candidate] = max((int)$count, $counts[(string)$candidate] ?? 0);
        }
        if ($counts === []) {
            return null;
        }

        arsort($counts);
        $exact = array_filter($counts, static fn(int $count, int|string $candidate): bool => mb_strtolower((string)$candidate) === $lower, ARRAY_FILTER_USE_BOTH);
        $mostFrequent = (string)array_key_first($counts);
        if ($exact !== [] && reset($exact) * self::EXACT_MATCH_RATIO >= $counts[$mostFrequent]) {
            return (string)array_key_first($exact);
        }

        return $mostFrequent;
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
