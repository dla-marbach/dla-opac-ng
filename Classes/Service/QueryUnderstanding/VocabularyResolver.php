<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\SolrConnection;

/**
 * Gleicht die vom LLM erkannten Bestandteile mit dem Index ab.
 *
 * Das LLM kennt weder Normdaten noch Facettenwerte. Personen werden deshalb über den Normdaten-Suggester
 * (mySuggester, wie die Autovervollständigung) aufgelöst, Facettenwerte über eine Zählabfrage auf
 * exakte Übereinstimmung geprüft. Nur Werte, die es im Index gibt, werden zu Filtern.
 */
class VocabularyResolver
{
    private const SUGGEST_DICTIONARY = 'mySuggester';

    /** @var array<string,mixed> */
    private array $memo = [];

    public function __construct(
        private readonly SolrConnection $solrConnection = new SolrConnection()
    ) {
    }

    /**
     * Löst einen Namen auf einen Normdatensatz auf.
     *
     * @return array{id: string, label: string}|null label ist der Wert der Facette filterAuthority_mv
     */
    public function resolvePerson(string $name): ?array
    {
        $key = 'person|' . $name;
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        $result = null;
        $json = $this->solrConnection->request('suggest', [
            'suggest' => 'true',
            'suggest.dictionary' => self::SUGGEST_DICTIONARY,
            'suggest.q' => $name,
        ]);
        $suggestions = $json['suggest'][self::SUGGEST_DICTIONARY][$name]['suggestions'] ?? [];
        $nameTokens = self::tokens($name);

        // Vorschläge sind nach Relevanz (searchEntityScore) sortiert. Übernommen wird der erste Vorschlag,
        // der alle Namensbestandteile der Anfrage enthält; so wird aus "Mann" nicht zufällig "Mannheim".
        foreach (is_array($suggestions) ? $suggestions : [] as $suggestion) {
            [$id, $label] = array_pad(explode('␝', (string)($suggestion['payload'] ?? ''), 2), 2, '');
            if ($id === '' || $label === '') {
                continue;
            }
            $termTokens = self::tokens((string)($suggestion['term'] ?? '') . ' ' . $label);
            if (array_diff($nameTokens, $termTokens) === []) {
                $result = ['id' => $id, 'label' => $label];
                break;
            }
        }

        return $this->memo[$key] = $result;
    }

    /**
     * Prüft, ob ein Facettenwert im Index vorkommt, und liefert die exakte Schreibweise
     * (versucht auch die Schreibweise mit großem Anfangsbuchstaben).
     */
    public function resolveFacetValue(string $field, string $value): ?string
    {
        $candidates = array_values(array_unique([
            $value,
            mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1),
        ]));

        foreach ($candidates as $candidate) {
            $count = $this->count('*:*', [$field . ':' . self::quote($candidate)]);
            if ($count !== null && $count > 0) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Anzahl der Treffer für eine Anfrage über den Request-Handler /select (mit dessen Standardwerten:
     * eDisMax, q.op=AND, Ausschluss vertraulicher Datensätze). null bei Verbindungsfehlern.
     *
     * @param list<string> $filterQueries
     */
    public function count(string $query, array $filterQueries = []): ?int
    {
        $key = 'count|' . $query . '|' . implode('|', $filterQueries);
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        $params = ['q' => $query, 'rows' => '0', 'fl' => 'id', 'facet' => 'false'];
        if ($filterQueries !== []) {
            $params['fq'] = $filterQueries;
        }
        $json = $this->solrConnection->request('select', $params);
        $numFound = $json['response']['numFound'] ?? null;

        return $this->memo[$key] = is_int($numFound) ? $numFound : null;
    }

    /**
     * Setzt einen Wert für eine Solr-Phrasensuche in Anführungszeichen.
     */
    public static function quote(string $value): string
    {
        return '"' . addcslashes($value, '"\\') . '"';
    }

    /**
     * @return list<string>
     */
    private static function tokens(string $value): array
    {
        $value = mb_strtolower($value);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($parts));
    }
}
