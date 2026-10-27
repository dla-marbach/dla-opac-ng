<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Statistics;

/**
 * Übersetzt die Datensätze des Rohlogs (siehe RawLogWriter, StatisticsMiddleware) in Requests für die
 * Bulk-Tracking-API von Matomo.
 *
 * Website »Katalog« (opacMatomoSiteId), je Katalogseite:
 *   - ein Seitenaufruf (url ohne Query-Parameter, action_name »Katalog/<Bereich>«) bzw. bei Suchbegriffen
 *     eine Site-Search-Aktion (search, search_cat, search_count)
 *   - je Facettenfilter ein Event (e_c »Facette« bzw. »Facette (ausgeschlossen)«, e_a Facette, e_n Wert)
 *   - Merkmale als Custom Dimensions (Scope »Aktion«, Zuordnung über opacMatomoDimensions)
 *
 * Website »Katalog – Zähler« (opacMatomoAuxSiteId, optional): Proof-of-Work, Bots und AJAX-Endpunkte als
 * Events, pro Stunde zusammengefasst; die Anzahl steht im Ereigniswert (e_v).
 *
 * Matomo erhält keine IP-Adresse und keinen User-Agent der Besucher: cip und ua sind fest, jede Katalogseite
 * ist ein eigener Besuch mit zufälliger Besucher-ID (cid). Matomo zählt damit Aktionen, keine Besucher.
 */
class MatomoRequestBuilder
{
    /**
     * Feste IP-Adresse für alle Requests; ohne cip würde Matomo die IP des TYPO3-Servers speichern.
     */
    public const CLIENT_IP = '0.0.0.0';

    public const USER_AGENT = 'DLA-OPAC-Statistik';

    private const AREA_LABELS = [
        StatisticsCollector::AREA_START => 'Startseite',
        StatisticsCollector::AREA_SEARCH => 'Trefferliste',
        StatisticsCollector::AREA_DETAIL => 'Detailseite',
        StatisticsCollector::AREA_COLLECTION => 'Bestandsübersicht',
        StatisticsCollector::AREA_CLASSIFICATION => 'Systematik',
    ];

    private const TREE_MODES = [
        'entry' => 'Einstieg',
        'record' => 'Datensatz',
        'node' => 'Knoten',
        'search' => 'Suche',
    ];

    private const REFERRERS = [
        'int' => 'eigene Website',
        'ext' => 'andere Website',
        'none' => 'ohne',
    ];

    private const POW_EVENTS = [
        'pow:issued' => 'Challenge ausgestellt',
        'pow:solved' => 'Challenge gelöst',
    ];

    /**
     * @param array<string, int> $dimensions Merkmal => ID der Custom Dimension
     */
    public function __construct(
        private readonly int $siteId,
        private readonly int $auxSiteId = 0,
        private readonly array $dimensions = [],
    ) {
    }

    public static function fromConfiguration(): self
    {
        return new self(
            StatisticsConfiguration::getMatomoSiteId(),
            StatisticsConfiguration::getMatomoAuxSiteId(),
            StatisticsConfiguration::getMatomoDimensions()
        );
    }

    /**
     * @param iterable<array> $records Datensätze einer Rohdatei
     * @return list<array<string, string|int>> Requests in der Reihenfolge der Datensätze, danach die Zähler
     */
    public function build(iterable $records): array
    {
        $requests = [];
        $counters = [];

        foreach ($records as $record) {
            $area = (string)($record['a'] ?? '');
            $time = (int)($record['ts'] ?? 0);
            if ($area === '' || $time <= 0) {
                continue;
            }

            if (isset(self::POW_EVENTS[$area])) {
                $this->count($counters, $time, 'Proof-of-Work', self::POW_EVENTS[$area], '');
            } elseif (!empty($record['bot'])) {
                $this->count($counters, $time, 'Bot', $this->getAreaLabel($area), '');
            } elseif (str_starts_with($area, 'ajax:')) {
                $action = substr($area, 5) . (isset($record['d']['m']) ? ' ' . $record['d']['m'] : '');
                $this->count($counters, $time, 'AJAX', $action, $this->getAudience($record));
            } elseif (isset(self::AREA_LABELS[$area])) {
                array_push($requests, ...$this->buildPage($area, $time, $record));
            }
        }

        if ($this->auxSiteId > 0) {
            ksort($counters);
            foreach ($counters as $counter) {
                $request = $this->createBase($this->auxSiteId, $counter['ts']) + [
                    'new_visit' => 1,
                    'e_c' => $counter['c'],
                    'e_a' => $counter['a'],
                ];
                if ($counter['n'] !== '') {
                    $request['e_n'] = $counter['n'];
                }
                $requests[] = $request + ['e_v' => $counter['v']];
            }
        }

        return $requests;
    }

    /**
     * Seitenaufruf bzw. Site-Search-Aktion plus ein Event je Facettenfilter (gleicher Besuch und Seitenaufruf).
     *
     * @return list<array<string, string|int>>
     */
    private function buildPage(string $area, int $time, array $record): array
    {
        $data = is_array($record['d'] ?? null) ? $record['d'] : [];
        $url = (string)($record['u'] ?? '');

        $base = $this->createBase($this->siteId, $time) + ['pv_id' => self::randomHex(6)];
        if ($url !== '') {
            $base['url'] = $url;
        }

        $page = $base + [
            'new_visit' => 1,
            'action_name' => 'Katalog/' . $this->getAreaLabel($area),
        ];
        if (($record['r'] ?? '') === 'ext' && !empty($record['rh'])) {
            $page['urlref'] = 'https://' . $record['rh'] . '/';
        }

        $term = (string)($data['t'] ?? '');
        if ($term !== '') {
            $page['search'] = $term;
            $page['search_cat'] = $area === StatisticsCollector::AREA_SEARCH
                ? (empty($data['x']) ? 'Katalog (einfach)' : 'Katalog (erweitert)')
                : $this->getAreaLabel($area);
            if (isset($data['n'])) {
                $page['search_count'] = (int)$data['n'];
            }
        }

        foreach ($this->describe($area, $record, $data) as $key => $value) {
            if (isset($this->dimensions[$key]) && $value !== '') {
                $page['dimension' . $this->dimensions[$key]] = $value;
            }
        }

        $requests = [$page];
        foreach (is_array($data['f'] ?? null) ? $data['f'] : [] as $facet => $values) {
            foreach (is_array($values) ? $values : [] as $value => $mode) {
                $requests[] = $base + [
                    'e_c' => $mode === 'not' ? 'Facette (ausgeschlossen)' : 'Facette',
                    'e_a' => (string)$facet,
                    'e_n' => (string)$value,
                ];
            }
        }

        return $requests;
    }

    /**
     * Werte für die Custom Dimensions (siehe StatisticsConfiguration::DIMENSION_KEYS).
     *
     * @return array<string, string>
     */
    private function describe(string $area, array $record, array $data): array
    {
        $values = [
            'area' => $this->getAreaLabel($area),
            'audience' => $this->getAudience($record),
            'language' => (string)($record['l'] ?? ''),
            'referrer' => self::REFERRERS[$record['r'] ?? 'none'] ?? '',
        ];

        if ($area === StatisticsCollector::AREA_SEARCH) {
            $fields = is_array($data['qf'] ?? null) ? $data['qf'] : [];
            sort($fields);
            $facets = is_array($data['f'] ?? null) ? array_map('strval', array_keys($data['f'])) : [];
            sort($facets);

            $values['mode'] = !empty($data['e']) ? 'ohne Suchbegriff' : (empty($data['x']) ? 'einfach' : 'erweitert');
            $values['fields'] = implode(',', $fields);
            $values['facets'] = implode(',', $facets);
            $values['sort'] = (string)($data['s'] ?? 'Standard');
            $values['page'] = (string)($data['p'] ?? '');
            $values['perPage'] = (string)($data['c'] ?? '');
            $values['hits'] = isset($data['n']) ? self::bucketHits((int)$data['n']) : '';
        } elseif ($area === StatisticsCollector::AREA_DETAIL) {
            $values['mode'] = match (true) {
                !empty($data['nf']) => 'nicht gefunden',
                !empty($data['pdf']) => 'PDF',
                !empty($data['u']) => 'aus Trefferliste',
                default => 'direkt',
            };
        } elseif (isset($data['m'])) {
            $values['mode'] = self::TREE_MODES[$data['m']] ?? (string)$data['m'];
        }

        return $values;
    }

    /**
     * Trefferzahl in Größenklassen, damit der Dimensionsbericht übersichtlich bleibt.
     */
    public static function bucketHits(int $hits): string
    {
        return match (true) {
            $hits <= 1 => (string)max(0, $hits),
            $hits <= 10 => '2-10',
            $hits <= 100 => '11-100',
            $hits <= 1000 => '101-1000',
            $hits <= 10000 => '1001-10000',
            default => '>10000',
        };
    }

    /**
     * @return array<string, string|int>
     */
    private function createBase(int $siteId, int $time): array
    {
        return [
            'idsite' => $siteId,
            'rec' => 1,
            'apiv' => 1,
            'send_image' => 0,
            'cdt' => $time,
            'cip' => self::CLIENT_IP,
            'ua' => self::USER_AGENT,
            // Bot-Erkennung erfolgt in TYPO3; Matomo soll die festen Werte für cip/ua nicht als Bot verwerfen.
            'bots' => 1,
            'cid' => self::randomHex(16),
        ];
    }

    /**
     * Zähler pro Stunde, Kategorie, Aktion und Name; Zeitpunkt ist der letzte Zugriff der Stunde.
     */
    private function count(array &$counters, int $time, string $category, string $action, string $name): void
    {
        $key = intdiv($time, 3600) . "\0" . $category . "\0" . $action . "\0" . $name;
        if (!isset($counters[$key])) {
            $counters[$key] = ['ts' => $time, 'c' => $category, 'a' => $action, 'n' => $name, 'v' => 0];
        }
        $counters[$key]['ts'] = max($counters[$key]['ts'], $time);
        $counters[$key]['v']++;
    }

    private function getAreaLabel(string $area): string
    {
        return self::AREA_LABELS[$area] ?? $area;
    }

    private function getAudience(array $record): string
    {
        return !empty($record['in']) ? 'intern' : 'extern';
    }

    private static function randomHex(int $length): string
    {
        return bin2hex(random_bytes(intdiv($length, 2)));
    }
}
