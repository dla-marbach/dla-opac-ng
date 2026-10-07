<?php

namespace Dla\Find\Utility;

/**
 * Begrenzt, wie tief in eine Trefferliste geblättert und wie viele Facettenfilter gleichzeitig
 * aktiv sein dürfen. Schützt Solr vor teuren Deep-Paging-Abfragen (z.B. durch Crawler, die die
 * leere Suche bis start=4.000.000 durchblättern) und vor endlosen Facettenkombinationen.
 *
 * Konfiguration über TypoScript (plugin.tx_find.settings.limits):
 *   maxResultWindow            start + rows darf diesen Wert nicht überschreiten (Standard 10000)
 *   maxResultWindowEmptyQuery  dasselbe für die leere Suche ohne Suchbegriff (Standard 1000)
 *   maxActiveFilters           maximale Anzahl gleichzeitig aktiver Facettenfilter/-ausschlüsse (Standard 10)
 * Ein Wert von 0 deaktiviert das jeweilige Limit.
 */
class SearchLimitUtility
{
    public const DEFAULT_MAX_RESULT_WINDOW = 10000;

    public const DEFAULT_MAX_RESULT_WINDOW_EMPTY_QUERY = 1000;

    public const DEFAULT_MAX_ACTIVE_FILTERS = 10;

    public const VIOLATION_RESULT_WINDOW = 'resultWindow';

    public const VIOLATION_ACTIVE_FILTERS = 'activeFilters';

    /**
     * Anzahl der Treffer pro Seite: Argument »count« bzw. »paging.perPage«, begrenzt durch »paging.maximumPerPage«.
     */
    public static function getCount(array $arguments, array $settings): int
    {
        $count = (int) ($settings['paging']['perPage'] ?? 0);

        if (array_key_exists('count', $arguments)) {
            $count = (int) $arguments['count'];
        }

        if (isset($settings['paging']['maximumPerPage'])) {
            $count = min($count, (int) $settings['paging']['maximumPerPage']);
        }

        return $count;
    }

    /**
     * Index des ersten Treffers: Argument »start« bzw. aus »page« berechnet.
     */
    public static function getOffset(array $arguments, array $settings): int
    {
        $offset = 0;

        if (array_key_exists('start', $arguments)) {
            $offset = (int) $arguments['start'];
        } elseif (array_key_exists('page', $arguments)) {
            $offset = ((int) $arguments['page'] - 1) * self::getCount($arguments, $settings);
        }

        return $offset;
    }

    /**
     * Leere Suche: kein Suchbegriff bzw. nur Platzhalter wie »*« oder »*:*«.
     * Felder vom Typ »Hidden« (z.B. »extended«) zählen nicht als Suchbegriff.
     */
    public static function isEmptyQuery(array $arguments, array $settings = []): bool
    {
        $query = $arguments['q'] ?? [];
        if (!is_array($query)) {
            return self::isBlank($query);
        }

        $hiddenFields = [];
        foreach ((array) ($settings['queryFields'] ?? []) as $fieldInfo) {
            if (is_array($fieldInfo) && isset($fieldInfo['id']) && 'Hidden' === ($fieldInfo['type'] ?? '')) {
                $hiddenFields[(string) $fieldInfo['id']] = true;
            }
        }

        foreach ($query as $fieldId => $value) {
            if (isset($hiddenFields[(string) $fieldId])) {
                continue;
            }
            if (is_array($value) && array_key_exists('alternate', $value)) {
                $value = $value['term'] ?? '';
            }
            if (!self::isBlank($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Anzahl der per URL gesetzten Facettenfilter und -ausschlüsse (»facet[<id>][<term>]=1|not«).
     */
    public static function countActiveFilters(array $arguments): int
    {
        $facets = $arguments['facet'] ?? [];
        if (!is_array($facets)) {
            return '' === (string) $facets ? 0 : 1;
        }

        $count = 0;
        foreach ($facets as $facetSelection) {
            $count += is_array($facetSelection) ? count($facetSelection) : 1;
        }

        return $count;
    }

    /**
     * Maximal erlaubter Wert für start + rows (0 = unbegrenzt).
     */
    public static function getMaxResultWindow(array $arguments, array $settings): int
    {
        if (self::isEmptyQuery($arguments, $settings)) {
            return self::getLimit($settings, 'maxResultWindowEmptyQuery', self::DEFAULT_MAX_RESULT_WINDOW_EMPTY_QUERY);
        }

        return self::getLimit($settings, 'maxResultWindow', self::DEFAULT_MAX_RESULT_WINDOW);
    }

    /**
     * Maximal erlaubte Anzahl aktiver Facettenfilter (0 = unbegrenzt).
     */
    public static function getMaxActiveFilters(array $settings): int
    {
        return self::getLimit($settings, 'maxActiveFilters', self::DEFAULT_MAX_ACTIVE_FILTERS);
    }

    public static function isResultWindowExceeded(int $offset, int $rows, int $maxResultWindow): bool
    {
        return $maxResultWindow > 0 && $offset + $rows > $maxResultWindow;
    }

    public static function isActiveFilterLimitExceeded(array $arguments, array $settings): bool
    {
        $maxActiveFilters = self::getMaxActiveFilters($settings);

        return $maxActiveFilters > 0 && self::countActiveFilters($arguments) > $maxActiveFilters;
    }

    /**
     * Prüft die Argumente einer Trefferlisten-Anfrage. Liefert null, wenn die Anfrage an Solr gestellt
     * werden darf, sonst den Grund (VIOLATION_*).
     */
    public static function getViolation(array $arguments, array $settings): ?string
    {
        if (self::isActiveFilterLimitExceeded($arguments, $settings)) {
            return self::VIOLATION_ACTIVE_FILTERS;
        }

        if (self::isResultWindowExceeded(
            self::getOffset($arguments, $settings),
            self::getCount($arguments, $settings),
            self::getMaxResultWindow($arguments, $settings)
        )) {
            return self::VIOLATION_RESULT_WINDOW;
        }

        return null;
    }

    /**
     * Prüft die »underlyingQuery« einer Detailseite (Blättern von Treffer zu Treffer).
     * Liefert null, wenn die Position innerhalb der Limits liegt, sonst den Grund (VIOLATION_*).
     */
    public static function getUnderlyingQueryViolation(array $underlyingQuery, array $settings): ?string
    {
        if (self::isActiveFilterLimitExceeded($underlyingQuery, $settings)) {
            return self::VIOLATION_ACTIVE_FILTERS;
        }

        $position = (int) ($underlyingQuery['position'] ?? 0);
        if (self::isResultWindowExceeded($position - 1, 1, self::getMaxResultWindow($underlyingQuery, $settings))) {
            return self::VIOLATION_RESULT_WINDOW;
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private static function isBlank($value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!self::isBlank($item)) {
                    return false;
                }
            }

            return true;
        }

        if (!is_scalar($value)) {
            return true;
        }

        return 1 === preg_match('/^[\s*:()]*$/u', (string) $value);
    }

    private static function getLimit(array $settings, string $key, int $default): int
    {
        $value = $settings['limits'][$key] ?? null;
        if (null === $value || '' === $value || !is_numeric($value)) {
            return $default;
        }

        return max(0, (int) $value);
    }
}
