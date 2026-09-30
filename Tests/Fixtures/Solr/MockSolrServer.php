<?php

/**
 * Router-Skript für `php -S`, das aufgezeichnete Solr-Antworten (Tests/Fixtures/Solr/cassettes/*.json)
 * anhand von Pfad + Query-Parametern ausliefert. Damit lassen sich Tests ohne Solr-Zugriff ausführen.
 */

$cassetteDir = __DIR__ . '/cassettes';

/**
 * Parst einen rohen Query-String, ohne PHPs automatische Umwandlung von "." zu "_" in Schlüsselnamen
 * (die $_GET/parse_str betrifft und Solr-Parameter wie "facet.field" zerstören würde).
 * Mehrfach vorkommende Schlüssel (z.B. suggest.dictionary) werden als Array gesammelt.
 */
function parseRawQueryString(string $queryString): array
{
    $result = [];
    if ($queryString === '') {
        return $result;
    }
    foreach (explode('&', $queryString) as $pair) {
        if ($pair === '') {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
        $key = urldecode($key);
        $value = urldecode($value);
        if (array_key_exists($key, $result)) {
            $result[$key] = array_merge((array)$result[$key], [$value]);
        } else {
            $result[$key] = $value;
        }
    }
    return $result;
}

/** Normalisiert eine Query-Struktur für den Vergleich (sortiert Keys, castet Werte zu Arrays von Strings). */
function normalizeQuery(array $query): array
{
    $normalized = [];
    foreach ($query as $key => $value) {
        $values = array_map('strval', (array)$value);
        sort($values);
        $normalized[$key] = $values;
    }
    ksort($normalized);
    return $normalized;
}

/** Stabiler Kurz-Hash über Pfad + normalisierte Query, dient als Suffix der Dateinamen in cassettes/subqueries/. */
function requestHash(string $path, array $query): string
{
    return substr(sha1($path . '?' . json_encode(normalizeQuery($query))), 0, 12);
}

/** Dateiname-tauglicher Anhang aus dem Query-Parameter "q" (nur zur besseren Auffindbarkeit im Repository). */
function requestSlug(array $query): string
{
    $q = is_array($query['q'] ?? null) ? implode(' ', $query['q']) : (string)($query['q'] ?? '');
    $slug = trim((string)preg_replace('/[^A-Za-z0-9]+/', '-', $q), '-');
    return substr($slug, 0, 50) ?: 'request';
}

function findCassette(string $cassetteDir, string $path, array $query): ?array
{
    // Von Unterabfragen aus Partials (dla:countFromSolr, dla:fromSolr) automatisch aufgezeichnete Cassetten:
    // per Hash im Dateinamen direkt auffindbar, ohne alle Dateien einlesen zu müssen.
    $hash = requestHash($path, $query);
    foreach (glob($cassetteDir . '/subqueries/*-' . $hash . '.json') ?: [] as $file) {
        $cassette = json_decode((string)file_get_contents($file), true);
        if (is_array($cassette)) {
            return $cassette;
        }
    }

    foreach (glob($cassetteDir . '/*.json') ?: [] as $file) {
        $cassette = json_decode((string)file_get_contents($file), true);
        if (!is_array($cassette) || !isset($cassette['request']['path'])) {
            continue;
        }
        if ($cassette['request']['path'] !== $path) {
            continue;
        }
        $expectedQuery = normalizeQuery($cassette['request']['query'] ?? []);
        if ($expectedQuery === normalizeQuery($query)) {
            return $cassette;
        }
    }
    return null;
}

/**
 * Aufnahmemodus (nur wenn SOLR_RECORD_BASE gesetzt ist, z.B. http://host.docker.internal:8983):
 * Unbekannte Requests werden an den echten Solr weitergereicht und als Cassette in
 * cassettes/subqueries/ abgelegt. Dadurch werden alle Unterabfragen der Partials (siehe
 * Tests/Unit/Partials/Detail/) beim Ausführen der Tests automatisch mitgeschrieben.
 */
function recordCassette(string $base, string $cassetteDir, string $path, array $query): ?array
{
    $url = rtrim($base, '/') . $path . '?' . ($_SERVER['QUERY_STRING'] ?? '');
    $body = @file_get_contents($url, false, stream_context_create([
        'http' => ['method' => 'GET', 'timeout' => 15.0, 'ignore_errors' => true],
    ]));
    if ($body === false) {
        return null;
    }

    $status = 200;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches)) {
            $status = (int)$matches[1];
        }
    }

    $decoded = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    $cassette = [
        'request' => ['method' => 'GET', 'path' => $path, 'query' => $query],
        'response' => ['status' => $status, 'body' => $decoded],
    ];

    $directory = $cassetteDir . '/subqueries';
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    file_put_contents(
        $directory . '/' . requestSlug($query) . '-' . requestHash($path, $query) . '.json',
        json_encode($cassette, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
    );

    return $cassette;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$query = parseRawQueryString($_SERVER['QUERY_STRING'] ?? '');

$cassette = findCassette($cassetteDir, $path, $query);

$recordBase = getenv('SOLR_RECORD_BASE');
if ($cassette === null && $recordBase) {
    $cassette = recordCassette($recordBase, $cassetteDir, $path, $query);
}

header('Content-Type: application/json; charset=UTF-8');

if ($cassette === null) {
    http_response_code(404);
    $missingSignature = $path . '?' . http_build_query($query);
    file_put_contents(
        __DIR__ . '/missing-requests.log',
        date('c') . ' ' . $missingSignature . "\n",
        FILE_APPEND
    );
    echo json_encode([
        'error' => 'no fixture found for this request',
        'path' => $path,
        'query' => $query,
        'hint' => 'Szenario in Tests/Fixtures/Solr/recorder.php ergänzen oder Tests mit SOLR_RECORD_BASE=<Solr-URL> ausführen, um Cassetten in cassettes/subqueries/ aufzuzeichnen.',
    ]);
    exit;
}

http_response_code((int)($cassette['response']['status'] ?? 200));
echo json_encode($cassette['response']['body'] ?? []);
