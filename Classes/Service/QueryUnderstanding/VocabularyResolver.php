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
     * Facettenwert zum Begriff: die exakte Schreibweise (Groß-/Kleinschreibung egal), sonst der häufigste Wert, der
     * den Begriff enthält, z.B. "Marbach" → "Marbach am Neckar".
     */
    public function resolveFacetValue(string $field, string $value): ?string
    {
        $key = 'facet|' . $field . '|' . $value;
        if (!array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $this->lookupFacetValues($field, $value);
        }
        ['exact' => $exact, 'containing' => $containing] = $this->memo[$key];

        return $exact ?? $containing[0] ?? null;
    }

    /**
     * Eine Anfrage für beides: Die exakte Schreibweise wird unabhängig von der Häufigkeit über facet.query geprüft
     * (in gängigen Schreibweisen, weil die Facettenfelder Groß-/Kleinschreibung unterscheiden), die enthaltenden
     * Werte über facet.contains (die zehn häufigsten).
     *
     * @return array{exact: string|null, containing: list<string>}
     */
    private function lookupFacetValues(string $field, string $value): array
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

        $exact = null;
        foreach ($variants as $variant) {
            if ((int)($json['facet_counts']['facet_queries'][$exactQuery($variant)] ?? 0) > 0) {
                $exact = $variant;
                break;
            }
        }
        $containing = array_map('strval', array_keys((array)($json['facet_counts']['facet_fields'][$field] ?? [])));
        // Exakte Schreibweise in einer anderen Groß-/Kleinschreibung als den geprüften, z.B. "DDR" für "ddr"
        $exact ??= array_values(array_filter($containing, static fn(string $candidate): bool => mb_strtolower($candidate) === $lower))[0] ?? null;

        return ['exact' => $exact, 'containing' => $containing];
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
