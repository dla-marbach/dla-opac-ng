<?php

namespace Dla\DlaOpacNg\Tests\Support;

use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Baut echte Solarium-Client-Objekte gegen den Mock-Solr-Server auf (siehe MockSolrServerProcess),
 * um "document"/"results"-Variablen exakt so zu erzeugen, wie es
 * dla-find/Classes/Service/SolrServiceProvider.php in Produktion tut (echte
 * \Solarium\QueryType\Select\Result\Document- bzw. \Solarium\QueryType\Select\Result\Result-Objekte,
 * keine Plain-Arrays). Damit lassen sich Fluid-Partials, die "document.fields.xxx" oder
 * "results.numfound" verwenden, mit realistischen Daten rendern.
 *
 * Nutzt dieselben Cassetten wie Tests/Fixtures/Solr/recorder.php (Szenarien "detail-*" und
 * "resultlist-*"), erwartet aber Solarium-Standardparameter (siehe dortige Kommentare).
 */
class SolrFixture
{
    private static ?Client $client = null;

    public static function client(): Client
    {
        if (self::$client === null) {
            $mockSolr = MockSolrServerProcess::start();
            $url = parse_url($mockSolr->getBaseUrl());

            $adapter = new Curl();
            $adapter->setTimeout(5);
            self::$client = new Client($adapter, new EventDispatcher(), [
                'endpoint' => [
                    'default' => [
                        'host' => $url['host'],
                        'port' => $url['port'],
                        'path' => '/',
                        'scheme' => $url['scheme'],
                        'core' => 'internformat',
                    ],
                ],
            ]);
        }

        return self::$client;
    }

    /**
     * Führt eine Solr-Select-Query gegen den Mock-Server aus.
     */
    public static function select(string $query, int $rows): Result
    {
        $client = self::client();
        $selectQuery = $client->createSelect();
        $selectQuery->setQuery($query);
        $selectQuery->setRows($rows);

        return $client->execute($selectQuery);
    }

    /**
     * Lädt ein einzelnes Dokument per ID (passend zu den "detail-*"-Szenarien in recorder.php).
     */
    public static function document(string $id): Document
    {
        $documents = self::select('id:(' . $id . ')', 1)->getDocuments();
        if (!isset($documents[0])) {
            throw new \RuntimeException('Keine Solr-Fixture für ID "' . $id . '" gefunden. Cassette in Tests/Fixtures/Solr/cassettes/ vorhanden?');
        }

        return $documents[0];
    }
}
