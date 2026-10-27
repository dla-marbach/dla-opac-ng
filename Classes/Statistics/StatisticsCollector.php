<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Statistics;

use Dla\Find\Utility\SearchLimitUtility;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Sammelt während eines Requests, was die Katalog-Plugins getan haben. Die StatisticsMiddleware
 * schreibt die Einträge nach der Antwort in das Rohlog (siehe RawLogWriter).
 *
 * Erfasst werden nur Merkmale der Nutzung (Bereich, Suchfelder, Facetten, Sortierung, Seite,
 * Trefferzahl, normalisierter Suchbegriff), nie Angaben zur Person.
 */
class StatisticsCollector implements SingletonInterface
{
    public const AREA_START = 'start';
    public const AREA_SEARCH = 'search';
    public const AREA_DETAIL = 'detail';
    public const AREA_COLLECTION = 'collection';
    public const AREA_CLASSIFICATION = 'classification';

    /**
     * Maximale Länge von Suchbegriffen, Facettenwerten u.ä. in der Statistik.
     */
    public const MAX_STRING_LENGTH = 100;

    /**
     * Maximale Anzahl erfasster Facettenfilter pro Anfrage.
     */
    private const MAX_FACET_FILTERS = 20;

    /**
     * @var array<string, array>
     */
    private array $entries = [];

    /**
     * Merkt einen Bereich vor. Pro Request zählt jeder Bereich höchstens einmal (der erste Aufruf gilt).
     */
    public function record(string $area, array $data = []): void
    {
        if (!isset($this->entries[$area])) {
            $this->entries[$area] = $data;
        }
    }

    /**
     * Trefferliste: genutzte Suchfelder, Facetten, Sortierung, Seite und Trefferzahl.
     */
    public function recordSearch(array $arguments, array $settings, ?int $numFound, bool $extendedSearch): void
    {
        $data = [];

        $emptyQuery = SearchLimitUtility::isEmptyQuery($arguments, $settings);
        $data['e'] = $emptyQuery ? 1 : 0;
        $data['x'] = $extendedSearch ? 1 : 0;

        $query = $arguments['q'] ?? [];
        if (is_array($query)) {
            $fields = [];
            foreach ($query as $fieldId => $value) {
                if (!SearchLimitUtility::isEmptyQuery(['q' => [$fieldId => $value]], $settings)) {
                    $fields[] = self::truncate((string)$fieldId);
                }
            }
            if ($fields !== []) {
                $data['qf'] = $fields;
            }
            $term = $query['default'] ?? '';
            if (is_array($term)) {
                $term = $term['term'] ?? '';
            }
        } else {
            $term = $query;
        }
        if (!$emptyQuery && is_scalar($term)) {
            $normalized = self::normalizeTerm((string)$term);
            if ($normalized !== '') {
                $data['t'] = $normalized;
            }
        }

        $facets = $this->describeFacets($arguments['facet'] ?? []);
        if ($facets !== []) {
            $data['f'] = $facets;
        }

        $count = max(1, SearchLimitUtility::getCount($arguments, $settings));
        $data['p'] = intdiv(max(0, SearchLimitUtility::getOffset($arguments, $settings)), $count) + 1;

        if (isset($arguments['sort']) && is_scalar($arguments['sort']) && (string)$arguments['sort'] !== '') {
            $data['s'] = self::truncate((string)$arguments['sort']);
        }
        // Tatsächlich verwendete Anzahl (Standard bzw. durch paging.maximumPerPage begrenzt).
        $data['c'] = $count;
        if ($numFound !== null) {
            $data['n'] = $numFound;
        }

        $this->record(self::AREA_SEARCH, $data);
    }

    /**
     * Detailseite: Datensatz-ID (nur wenn gefunden), Aufruf aus einer Trefferliste (underlyingQuery) und PDF-Ausgabe.
     */
    public function recordDetail(string $id, bool $found, bool $fromResults, bool $pdf): void
    {
        $data = $found ? ['id' => self::truncate($id)] : ['nf' => 1];
        $data['u'] = $fromResults ? 1 : 0;
        $data['pdf'] = $pdf ? 1 : 0;

        $this->record(self::AREA_DETAIL, $data);
    }

    /**
     * Bestandsübersicht bzw. Systematik: Einstieg, Datensatz, Knoten oder Suche.
     */
    public function recordTree(string $area, string $record, string $uid, string $search): void
    {
        $data = [];
        $search = self::normalizeTerm($search);
        if ($search !== '') {
            $data['m'] = 'search';
            $data['t'] = $search;
        } elseif ($record !== '') {
            $data['m'] = 'record';
        } elseif ($uid !== '') {
            $data['m'] = 'node';
        } else {
            $data['m'] = 'entry';
        }

        $this->record($area, $data);
    }

    public function hasEntries(): bool
    {
        return $this->entries !== [];
    }

    /**
     * Liefert die gesammelten Einträge und leert den Collector.
     *
     * @return array<string, array>
     */
    public function pull(): array
    {
        $entries = $this->entries;
        $this->entries = [];

        return $entries;
    }

    /**
     * Kleinschreibung, Leerraum zusammenfassen, auf MAX_STRING_LENGTH Zeichen kürzen.
     */
    public static function normalizeTerm(string $term): string
    {
        $term = mb_strtolower(trim((string)preg_replace('/\s+/u', ' ', $term)));

        return self::truncate($term);
    }

    public static function truncate(string $value): string
    {
        return mb_substr($value, 0, self::MAX_STRING_LENGTH);
    }

    /**
     * Facettenfilter »facet[<id>][<term>]=1|not« als [id => [term => 1|'not']].
     *
     * @return array<string, array<string, int|string>>
     */
    private function describeFacets(mixed $facets): array
    {
        if (!is_array($facets)) {
            return [];
        }

        $result = [];
        $filters = 0;
        foreach ($facets as $facetId => $selection) {
            if (!is_array($selection)) {
                continue;
            }
            foreach ($selection as $term => $mode) {
                if (++$filters > self::MAX_FACET_FILTERS) {
                    break 2;
                }
                $result[self::truncate((string)$facetId)][self::truncate((string)$term)] = $mode === 'not' ? 'not' : 1;
            }
        }

        return $result;
    }
}
