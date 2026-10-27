<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Statistics;

/**
 * Fasst die Rohdateien (raw/YYYYMMDDHH-<host>.ndjson) zu Tagesdateien (days/YYYY-MM-DD.json) und
 * Monatsdateien (months/YYYY-MM.json) zusammen und löscht die verarbeiteten Rohdateien.
 *
 * Aufbau einer Tages- bzw. Monatsdatei:
 *   {
 *     "date": "2026-10-27" | "month": "2026-10",
 *     "sources": ["2026102714-web1", ...],          (nur Tagesdateien: verarbeitete Rohdateien)
 *     "pow": {"issued": n, "solved": n},
 *     "audiences": {
 *       "ext" | "int" | "bot": {
 *         "<Bereich>": {
 *           "hits": n, "hours": [24 Werte] (Tagesdateien) | "days": {"01": n, ...} (Monatsdateien),
 *           "lang": {...}, "ref": {...}, "entry": {"<pid>": n},
 *           ... bereichsspezifische Merkmale, jeweils {Wert: Anzahl} ...
 *         }
 *       }
 *     }
 *   }
 *
 * Alle Zahlen sind additiv: Mehrere Webserver und mehrere Läufe werden einfach aufsummiert. Über
 * »sources« wird jede Rohdatei nur einmal gezählt, auch wenn ein Lauf vor dem Löschen abbricht.
 */
class StatisticsAggregator
{
    /**
     * Maximale Anzahl Einträge pro Merkmal (z.B. Suchbegriffe, Facettenwerte); der Rest wird unter »_other« summiert.
     */
    public const TOP_N = 500;

    public const OTHER_KEY = '_other';

    /**
     * Merkmale mit Suchbegriffen, die nur befristet und ab einer Mindesthäufigkeit aufbewahrt werden.
     */
    public const TERM_DIMENSIONS = ['terms', 'zeroTerms'];

    /**
     * Eine Rohdatei wird erst verarbeitet, wenn ihre Stunde seit dieser Zeit (Sekunden) vorbei ist,
     * damit späte Schreibzugriffe laufender Requests nicht verloren gehen.
     */
    private const GRACE_PERIOD = 120;

    /**
     * Rohdatei »YYYYMMDDHH-<host>.ndjson« bzw. die zur Verarbeitung umbenannte Generation
     * »YYYYMMDDHH-<host>+<generation>.ndjson« (»+« kommt in Hostnamen nicht vor, siehe RawLogWriter).
     */
    private const RAW_FILE_PATTERN = '/^(\d{4})(\d{2})(\d{2})(\d{2})-[A-Za-z0-9._-]+(\+[0-9a-f]+)?\.ndjson$/';

    /**
     * Alle Maps als JSON-Objekte schreiben, auch wenn die Schlüssel zufällig 0, 1, 2 … lauten.
     */
    public const JSON_FLAGS = JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;

    private string $directory;

    private int $termsRetentionDays;

    private int $termsMinCount;

    public function __construct(?string $directory = null, ?int $termsRetentionDays = null, ?int $termsMinCount = null)
    {
        $this->directory = rtrim($directory ?? StatisticsConfiguration::getStorageDirectory(), '/');
        $this->termsRetentionDays = $termsRetentionDays ?? StatisticsConfiguration::getTermsRetentionDays();
        $this->termsMinCount = $termsMinCount ?? StatisticsConfiguration::getTermsMinCount();
    }

    /**
     * Verarbeitet alle abgeschlossenen Rohdateien.
     *
     * @return array{files: int, records: int, days: string[], months: string[]}
     */
    public function aggregate(?\DateTimeImmutable $now = null, bool $includeCurrentHour = false): array
    {
        $now ??= new \DateTimeImmutable();
        $result = ['files' => 0, 'records' => 0, 'days' => [], 'months' => []];

        $today = $now->setTimezone(StatisticsConfiguration::getTimezone())->format('Y-m-d');

        $filesByDay = [];
        foreach ($this->findRawFiles($now, $includeCurrentHour) as $file => $hourInfo) {
            $file = $this->claimRawFile($file);
            if ($file !== null) {
                $filesByDay[$hourInfo['day']][$file] = $hourInfo['hour'];
            }
        }
        ksort($filesByDay);

        $processed = [];
        foreach ($filesByDay as $day => $files) {
            $dayData = $this->readJson($this->getDayFile($day)) ?? $this->createDay($day);
            foreach ($files as $file => $hour) {
                $source = basename($file, '.ndjson');
                if (!in_array($source, $dayData['sources'], true)) {
                    $result['records'] += $this->aggregateFile($dayData, $file, $hour);
                    $dayData['sources'][] = $source;
                }
                $processed[] = $file;
            }

            // Erst abgeschlossene Tage kappen: Bis dahin bleiben die Zähler exakt, sonst hinge die Rangliste
            // davon ab, wann aggregiert wurde.
            if ($day < $today) {
                self::capDimensions($dayData['audiences']);
            }
            $this->writeJson($this->getDayFile($day), $dayData);

            $result['days'][] = $day;
            $result['months'][substr($day, 0, 7)] = substr($day, 0, 7);
        }

        foreach ($result['months'] as $month) {
            $this->rebuildMonth($month);
        }
        $result['months'] = array_values($result['months']);

        // Rohdateien erst löschen, wenn Tages- und Monatsdateien geschrieben sind. Bricht ein Lauf vorher ab,
        // zählt der nächste die Dateien nicht erneut (»sources«), baut aber die Monate neu auf.
        foreach ($processed as $file) {
            @unlink($file);
        }
        $result['files'] = count($processed);

        $this->pruneTerms($now);

        return $result;
    }

    /**
     * Berechnet eine Monatsdatei aus den Tagesdateien neu. Suchbegriffe bleiben dort nur ab der Mindesthäufigkeit.
     */
    public function rebuildMonth(string $month): void
    {
        $monthData = ['month' => $month, 'pow' => ['issued' => 0, 'solved' => 0], 'audiences' => []];

        foreach (glob($this->directory . '/days/' . $month . '-*.json') ?: [] as $dayFile) {
            $dayData = $this->readJson($dayFile);
            if ($dayData === null) {
                continue;
            }
            $dayOfMonth = substr((string)($dayData['date'] ?? basename($dayFile, '.json')), 8, 2);
            self::mergeCounts($monthData['pow'], $dayData['pow'] ?? []);

            foreach ($dayData['audiences'] ?? [] as $audience => $areas) {
                foreach ($areas as $area => $stats) {
                    $hits = (int)($stats['hits'] ?? 0);
                    unset($stats['hours']);
                    $target = &$monthData['audiences'][$audience][$area];
                    $target ??= [];
                    self::mergeCounts($target, $stats);
                    $target['days'][$dayOfMonth] = ($target['days'][$dayOfMonth] ?? 0) + $hits;
                    unset($target);
                }
            }
        }

        self::capDimensions($monthData['audiences']);
        self::filterTerms($monthData['audiences'], $this->termsMinCount);
        $this->writeJson($this->directory . '/months/' . $month . '.json', $monthData);
    }

    /**
     * Addiert Zählerstände rekursiv (Schlüssel für Schlüssel).
     */
    public static function mergeCounts(array &$target, array $source): void
    {
        foreach ($source as $key => $value) {
            if (is_array($value)) {
                if (!isset($target[$key]) || !is_array($target[$key])) {
                    $target[$key] = [];
                }
                self::mergeCounts($target[$key], $value);
            } elseif (is_int($value) || is_float($value)) {
                $target[$key] = (int)(($target[$key] ?? 0) + $value);
            } elseif (!isset($target[$key])) {
                $target[$key] = $value;
            }
        }
    }

    /**
     * Entfernt Suchbegriffe unterhalb der Mindesthäufigkeit (nur Anzeige- und Monatsdaten).
     */
    public static function filterTerms(array &$audiences, int $minCount): void
    {
        foreach ($audiences as &$areas) {
            foreach ($areas as &$stats) {
                foreach (self::TERM_DIMENSIONS as $dimension) {
                    if (!isset($stats[$dimension]) || !is_array($stats[$dimension])) {
                        continue;
                    }
                    $other = 0;
                    foreach ($stats[$dimension] as $term => $count) {
                        if ($term === self::OTHER_KEY || $count < $minCount) {
                            $other += $count;
                            unset($stats[$dimension][$term]);
                        }
                    }
                    if ($other > 0) {
                        $stats[$dimension][self::OTHER_KEY] = $other;
                    }
                }
            }
            unset($stats);
        }
        unset($areas);
    }

    /**
     * Begrenzt jedes Merkmal auf die TOP_N häufigsten Werte; der Rest wird unter »_other« summiert.
     */
    public static function capDimensions(array &$audiences): void
    {
        foreach ($audiences as &$areas) {
            foreach ($areas as &$stats) {
                foreach ($stats as $dimension => &$values) {
                    if (!is_array($values) || $dimension === 'hours' || $dimension === 'days') {
                        continue;
                    }
                    if ($dimension === 'facetValues') {
                        foreach ($values as &$facetValues) {
                            $facetValues = self::capMap($facetValues);
                        }
                        unset($facetValues);
                    } else {
                        $values = self::capMap($values);
                    }
                }
                unset($values);
            }
            unset($stats);
        }
        unset($areas);
    }

    public function getDayFile(string $day): string
    {
        return $this->directory . '/days/' . $day . '.json';
    }

    /**
     * Zählt eine Rohdatei in die Tagesdaten ein.
     */
    private function aggregateFile(array &$dayData, string $file, int $hour): int
    {
        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            return 0;
        }

        $records = 0;
        while (($line = fgets($handle)) !== false) {
            $record = json_decode($line, true);
            if (!is_array($record) || !isset($record['a']) || !is_string($record['a'])) {
                continue;
            }
            $this->countRecord($dayData, $record, $hour);
            $records++;
        }
        fclose($handle);

        return $records;
    }

    private function countRecord(array &$dayData, array $record, int $hour): void
    {
        $area = $record['a'];
        if ($area === 'pow:issued' || $area === 'pow:solved') {
            $key = substr($area, 4);
            $dayData['pow'][$key] = ($dayData['pow'][$key] ?? 0) + 1;
            return;
        }

        $audience = !empty($record['bot']) ? 'bot' : (!empty($record['in']) ? 'int' : 'ext');
        $stats = &$dayData['audiences'][$audience][$area];
        $stats ??= ['hits' => 0, 'hours' => array_fill(0, 24, 0)];

        $stats['hits']++;
        $stats['hours'][$hour]++;
        self::increment($stats, 'lang', $record['l'] ?? '');
        self::increment($stats, 'ref', $record['r'] ?? '');
        self::increment($stats, 'entry', $record['pid'] ?? '');

        $data = is_array($record['d'] ?? null) ? $record['d'] : [];
        match ($area) {
            StatisticsCollector::AREA_SEARCH => $this->countSearch($stats, $data),
            StatisticsCollector::AREA_DETAIL => $this->countDetail($stats, $data),
            default => $this->countGeneric($stats, $data),
        };
        unset($stats);
    }

    private function countSearch(array &$stats, array $data): void
    {
        $page = max(1, (int)($data['p'] ?? 1));
        self::increment($stats, 'pages', self::bucket($page, [1 => '1', 2 => '2', 5 => '3-5', 10 => '6-10'], '11+'));
        self::increment($stats, 'mode', empty($data['e']) ? 'text' : 'browse');
        self::increment($stats, 'form', empty($data['x']) ? 'simple' : 'extended');

        foreach ((array)($data['qf'] ?? []) as $field) {
            self::increment($stats, 'fields', $field);
        }

        $facets = is_array($data['f'] ?? null) ? $data['f'] : [];
        $filters = 0;
        foreach ($facets as $facetId => $values) {
            self::increment($stats, 'facets', $facetId);
            foreach ((array)$values as $value => $mode) {
                $key = ($mode === 'not' ? 'not:' : '') . $value;
                $stats['facetValues'][(string)$facetId][$key] = ($stats['facetValues'][(string)$facetId][$key] ?? 0) + 1;
                $filters++;
            }
        }
        self::increment($stats, 'filters', self::bucket($filters, [0 => '0', 1 => '1', 2 => '2', 3 => '3'], '4+'));

        self::increment($stats, 'sort', $data['s'] ?? 'default');
        if (isset($data['c'])) {
            self::increment($stats, 'count', $data['c']);
        }

        if (isset($data['n'])) {
            $numFound = (int)$data['n'];
            self::increment(
                $stats,
                'results',
                self::bucket($numFound, [0 => '0', 1 => '1', 10 => '2-10', 100 => '11-100', 1000 => '101-1000'], '>1000')
            );
            // Suchbegriffe nur bei neuen Suchen (erste Seite) zählen, nicht beim Blättern.
            if (isset($data['t']) && $page === 1) {
                self::increment($stats, 'terms', $data['t']);
                if ($numFound === 0) {
                    self::increment($stats, 'zeroTerms', $data['t']);
                }
            }
        } elseif (isset($data['t']) && $page === 1) {
            self::increment($stats, 'terms', $data['t']);
        }
    }

    private function countDetail(array &$stats, array $data): void
    {
        self::increment($stats, 'found', empty($data['nf']) ? 'yes' : 'no');
        self::increment($stats, 'source', empty($data['u']) ? 'direct' : 'results');
        self::increment($stats, 'format', empty($data['pdf']) ? 'html' : 'pdf');
        if (isset($data['id'])) {
            self::increment($stats, 'records', $data['id']);
        }
    }

    private function countGeneric(array &$stats, array $data): void
    {
        if (isset($data['m'])) {
            self::increment($stats, 'mode', $data['m']);
        }
        if (isset($data['t'])) {
            self::increment($stats, 'terms', $data['t']);
        }
    }

    private static function increment(array &$stats, string $dimension, mixed $value): void
    {
        if (!is_scalar($value) || (string)$value === '') {
            return;
        }
        $key = StatisticsCollector::truncate((string)$value);
        $stats[$dimension][$key] = ($stats[$dimension][$key] ?? 0) + 1;
    }

    /**
     * @param array<int, string> $limits Obergrenze => Bezeichnung, aufsteigend
     */
    private static function bucket(int $value, array $limits, string $above): string
    {
        foreach ($limits as $limit => $label) {
            if ($value <= $limit) {
                return $label;
            }
        }

        return $above;
    }

    private static function capMap(mixed $values): mixed
    {
        if (!is_array($values) || count($values) <= self::TOP_N) {
            return $values;
        }

        $other = (int)($values[self::OTHER_KEY] ?? 0);
        unset($values[self::OTHER_KEY]);
        arsort($values);
        foreach (array_slice($values, self::TOP_N, null, true) as $count) {
            $other += (int)$count;
        }
        $values = array_slice($values, 0, self::TOP_N, true);
        $values[self::OTHER_KEY] = $other;

        return $values;
    }

    /**
     * Entfernt Suchbegriffe aus Tagesdateien, die älter als die Aufbewahrungsdauer sind und zu einem
     * abgeschlossenen Monat gehören. Die Monatsdateien behalten die Begriffe ab der Mindesthäufigkeit.
     */
    private function pruneTerms(\DateTimeImmutable $now): void
    {
        $limit = $now->setTimezone(StatisticsConfiguration::getTimezone())
            ->modify('-' . $this->termsRetentionDays . ' days')
            ->format('Y-m-d');

        $currentMonth = $now->setTimezone(StatisticsConfiguration::getTimezone())->format('Y-m');

        foreach (glob($this->directory . '/days/*.json') ?: [] as $dayFile) {
            $day = basename($dayFile, '.json');
            // Nur Tage abgeschlossener Monate bereinigen, sonst fehlten die Begriffe beim nächsten Monatsaufbau.
            if ($day >= $limit || substr($day, 0, 7) >= $currentMonth) {
                continue;
            }
            $dayData = $this->readJson($dayFile);
            if ($dayData === null || !empty($dayData['termsPruned'])) {
                continue;
            }
            foreach ($dayData['audiences'] as &$areas) {
                foreach ($areas as &$stats) {
                    foreach (self::TERM_DIMENSIONS as $dimension) {
                        unset($stats[$dimension]);
                    }
                }
                unset($stats);
            }
            unset($areas);
            $dayData['termsPruned'] = true;
            $this->writeJson($dayFile, $dayData);
        }
    }

    /**
     * Benennt eine Rohdatei vor der Verarbeitung in eine eindeutige Generation um. Requests, die danach in
     * dieselbe Stunde schreiben, legen wieder eine neue Datei an, die ein späterer Lauf verarbeitet.
     * Bereits umbenannte Dateien (aus abgebrochenen Läufen) werden unverändert übernommen.
     */
    private function claimRawFile(string $file): ?string
    {
        if (str_contains(basename($file), '+')) {
            return $file;
        }

        $claimed = substr($file, 0, -strlen('.ndjson')) . '+' . dechex(time()) . bin2hex(random_bytes(4)) . '.ndjson';
        if (!@rename($file, $claimed)) {
            return null;
        }

        // Auf einen gerade laufenden Schreibzugriff warten (RawLogWriter schreibt unter LOCK_EX).
        $handle = @fopen($claimed, 'rb');
        if ($handle !== false) {
            flock($handle, LOCK_SH);
            fclose($handle);
        }

        return $claimed;
    }

    /**
     * @return array<string, array{day: string, hour: int}>
     */
    private function findRawFiles(\DateTimeImmutable $now, bool $includeCurrentHour): array
    {
        $timezone = StatisticsConfiguration::getTimezone();
        $files = [];

        foreach (glob($this->directory . '/' . RawLogWriter::RAW_DIRECTORY . '/*.ndjson') ?: [] as $file) {
            if (preg_match(self::RAW_FILE_PATTERN, basename($file), $matches) !== 1) {
                continue;
            }
            [, $year, $month, $day, $hour] = $matches;
            $hourStart = new \DateTimeImmutable(sprintf('%s-%s-%s %s:00:00', $year, $month, $day, $hour), $timezone);
            if (!$includeCurrentHour && $hourStart->getTimestamp() + 3600 + self::GRACE_PERIOD > $now->getTimestamp()) {
                continue;
            }
            $files[$file] = ['day' => sprintf('%s-%s-%s', $year, $month, $day), 'hour' => (int)$hour];
        }
        ksort($files);

        return $files;
    }

    private function createDay(string $day): array
    {
        return ['date' => $day, 'sources' => [], 'pow' => ['issued' => 0, 'solved' => 0], 'audiences' => []];
    }

    private function readJson(string $file): ?array
    {
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data)) {
            return null;
        }
        $data['sources'] ??= [];
        $data['audiences'] ??= [];

        return $data;
    }

    /**
     * Schreibt atomar (temporäre Datei, dann rename), damit das Backend-Modul nie halbe Dateien liest.
     */
    private function writeJson(string $file, array $data): void
    {
        $directory = dirname($file);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Cannot create statistics directory ' . $directory, 1793000001);
        }

        $temporaryFile = $file . '.' . getmypid() . '.tmp';
        $json = json_encode($data, self::JSON_FLAGS);
        if ($json === false || file_put_contents($temporaryFile, $json) === false || !rename($temporaryFile, $file)) {
            @unlink($temporaryFile);
            throw new \RuntimeException('Cannot write statistics file ' . $file, 1793000002);
        }
    }
}
