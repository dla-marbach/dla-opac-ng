<?php

/**
 * Zeichnet reale Solr-Antworten als Fixtures auf, solange der Solr-Tunnel erreichbar ist.
 *
 * Aufruf: php Tests/Fixtures/Solr/recorder.php
 * Optional: SOLR_RECORD_BASE=http://127.0.0.1:8983 php Tests/Fixtures/Solr/recorder.php
 */

$base = getenv('SOLR_RECORD_BASE') ?: 'http://127.0.0.1:8983';
$cassetteDir = __DIR__ . '/cassettes';

if (!is_dir($cassetteDir) && !mkdir($cassetteDir, 0777, true) && !is_dir($cassetteDir)) {
    fwrite(STDERR, "Konnte Verzeichnis {$cassetteDir} nicht anlegen.\n");
    exit(1);
}

// Szenarien orientieren sich an den echten Query-Mustern aus
// Classes/Service/EntityService.php, Classes/Ajax/Autocomplete.php und Classes/Ajax/Decisiontree.php
$scenarios = [
    'entity-single-found' => [
        'path' => '/solr/internformat/select',
        'query' => ['q' => 'id:(PE00000863)', 'rows' => '1'],
    ],
    'entity-batch-found' => [
        'path' => '/solr/internformat/select',
        'query' => ['q' => 'id:("PE00000863" OR "BF00025111")', 'rows' => '2'],
    ],
    'entity-not-found' => [
        'path' => '/solr/internformat/select',
        'query' => ['q' => 'id:(DOES-NOT-EXIST-XYZ)', 'rows' => '1'],
    ],
    'decisiontree-relation1' => [
        'path' => '/solr/internformat/select',
        'query' => [
            'facet.field' => 'filterAuthorityRelation_mv',
            'facet' => 'on',
            'facet.mincount' => '1',
            'facet.prefix' => '',
            'fq' => 'NOT source:(AU OR MM)',
            'q' => 'goethe',
            'rows' => '0',
        ],
    ],
    'decisiontree-relation2' => [
        'path' => '/solr/internformat/select',
        'query' => [
            'facet.field' => 'filterAuthorityRole_mv',
            'facet' => 'on',
            'facet.mincount' => '1',
            'facet.prefix' => '',
            'fq' => 'NOT source:(AU OR MM)',
            'q' => 'goethe',
            'rows' => '0',
        ],
    ],
    // suggest.dictionary wird von der Anwendung zweimal gesendet (mySuggester + text), daher als Array
    'autocomplete-goethe' => [
        'path' => '/solr/internformat/suggest',
        'query' => [
            'suggest' => 'true',
            'suggest.dictionary' => ['mySuggester', 'text'],
            'suggest.q' => 'goethe',
        ],
    ],
    'autocomplete-empty' => [
        'path' => '/solr/internformat/suggest',
        'query' => [
            'suggest' => 'true',
            'suggest.dictionary' => ['mySuggester', 'text'],
            'suggest.q' => 'zzznoresultzzz',
        ],
    ],
];

// Szenarien für Fluid-Partial-Rendering-Tests (Tests/Unit/Partials): geben komplette Solr-Dokumente
// zurück (nicht wie oben nur ausgewählte Felder), damit die Partials mit realistischen Daten
// gerendert werden können. Verwenden das Solarium-Client-Standardparameterschema
// (omitHeader/wt/json.nl/start/fl), weil sie über einen echten Solarium\Client abgefragt werden
// (siehe Tests/Support/SolrFixture.php), genau wie dla-find/Classes/Service/SolrServiceProvider.php
// es in Produktion tut - im Unterschied zu den Szenarien oben, die per file_get_contents() aus den
// Ajax-/Service-Klassen dieser Extension abgefragt werden.
$solariumDefaults = [
    'omitHeader' => 'true',
    'wt' => 'json',
    'json.nl' => 'flat',
    'start' => '0',
    'fl' => '*,score',
];

// Ein Dokument je Quelltyp (siehe Resources/Private/Templates/Search/Detail.html für die
// source/filterSource-Weichen), damit alle Display/Detail/*-Partials mit echten Daten
// abgedeckt werden können.
$detailDocuments = [
    'detail-person' => 'PE00000863',           // Goethe (Normdaten, source=PE, filterSource=Personen)
    'detail-corporation' => 'KS00113059',      // Normdaten, source=KS, filterSource=Körperschaften
    'detail-sachbegriff' => 'TH00000031',      // Normdaten, source=TH, filterSource=Orte und Sachbegriffe
    'detail-fachsystematik' => 'SY00000015',   // Normdaten, source=SY, filterSource=Fachsystematik
    'detail-kette' => 'SE00000121',            // Normdaten, source=SE, filterSource=Systematikketten
    'detail-werk' => 'AK01600972',             // Normdaten, source=AK, filterSource=Werke
    'detail-library' => 'AK01600951',          // Bibliotheksmaterialien, source=AK, filterType_mv=Gedrucktes
    'detail-inventory' => 'BF00025111',        // Nachlässe und Spezialsammlungen, source=BF
    'detail-manuscript' => 'HS00067821',       // Handschriften Einzelnachweise, source=HS
    'detail-imagesandobjects' => 'BI00028331', // Bilder und Objekte, source=BI
];
foreach ($detailDocuments as $name => $id) {
    $scenarios[$name] = [
        'path' => '/solr/internformat/select',
        'query' => $solariumDefaults + ['q' => 'id:(' . $id . ')', 'rows' => '1'],
    ];
}

// Mehrere Dokumente unterschiedlichen Typs für Trefferlisten-Partials (Display/Result, Pager, ...).
$scenarios['resultlist-goethe'] = [
    'path' => '/solr/internformat/select',
    'query' => $solariumDefaults + ['q' => 'goethe', 'rows' => '5'],
];


/**
 * Baut einen rohen Query-String, der auch mehrfach vorkommende Parameter (z.B. suggest.dictionary) unterstützt.
 */
function buildRawQuery(array $query): string
{
    $pairs = [];
    foreach ($query as $key => $value) {
        foreach ((array)$value as $singleValue) {
            $pairs[] = rawurlencode((string)$key) . '=' . rawurlencode((string)$singleValue);
        }
    }
    return implode('&', $pairs);
}

$failures = 0;

foreach ($scenarios as $name => $scenario) {
    $queryString = buildRawQuery($scenario['query']);
    $url = $base . $scenario['path'] . '?' . $queryString;

    echo "Aufnahme: {$name} -> {$url}\n";

    $body = @file_get_contents($url, false, stream_context_create([
        'http' => ['method' => 'GET', 'timeout' => 10.0],
    ]));

    if ($body === false) {
        fwrite(STDERR, "  FEHLER: konnte {$url} nicht abrufen (ist der Solr-Tunnel aktiv?)\n");
        $failures++;
        continue;
    }

    $decoded = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        fwrite(STDERR, "  FEHLER: Antwort war kein gültiges JSON: " . json_last_error_msg() . "\n");
        $failures++;
        continue;
    }

    $cassette = [
        'request' => [
            'method' => 'GET',
            'path' => $scenario['path'],
            'query' => $scenario['query'],
        ],
        'response' => [
            'status' => 200,
            'body' => $decoded,
        ],
    ];

    file_put_contents(
        $cassetteDir . '/' . $name . '.json',
        json_encode($cassette, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
    );
}

if ($failures > 0) {
    fwrite(STDERR, "\n{$failures} Szenario(s) konnten nicht aufgezeichnet werden.\n");
    exit(1);
}

echo "\nAlle Fixtures wurden nach {$cassetteDir} geschrieben.\n";
