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

function findCassette(string $cassetteDir, string $path, array $query): ?array
{
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

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$query = parseRawQueryString($_SERVER['QUERY_STRING'] ?? '');

$cassette = findCassette($cassetteDir, $path, $query);

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
        'hint' => 'Szenario in Tests/Fixtures/Solr/recorder.php ergänzen und bei aktivem Solr-Tunnel neu aufzeichnen.',
    ]);
    exit;
}

http_response_code((int)($cassette['response']['status'] ?? 200));
echo json_encode($cassette['response']['body'] ?? []);
