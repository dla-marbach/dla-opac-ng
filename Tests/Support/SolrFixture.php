<?php

namespace Dla\DlaOpacNg\Tests\Support;

use Dla\DlaOpacNg\Service\SolrConnection;
use Solarium\Client;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;
use Symfony\Component\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;

/**
 * Baut echte Solarium-Client-Objekte gegen den Mock-Solr-Server auf (siehe MockSolrServerProcess),
 * um "document"/"results"-Variablen exakt so zu erzeugen, wie es
 * Classes/Find/Service/SolrServiceProvider.php in Produktion tut (echte
 * \Solarium\QueryType\Select\Result\Document- bzw. \Solarium\QueryType\Select\Result\Result-Objekte,
 * keine Plain-Arrays). Damit lassen sich Fluid-Partials, die "document.fields.xxx" oder
 * "results.numfound" verwenden, mit realistischen Daten rendern.
 *
 * Nutzt dieselben Cassetten wie Tests/Fixtures/Solr/recorder.php (Szenarien "detail-*" und
 * "resultlist-*"), erwartet aber Solarium-Standardparameter (siehe dortige Kommentare).
 */
class SolrFixture
{
    /**
     * Richtet die Solr-Verbindung (Classes/Service/SolrConnection.php) auf den Mock-Solr-Server aus.
     */
    public static function useMockSolr(): void
    {
        putenv('SOLR_HOST=' . MockSolrServerProcess::start()->getBaseUrl());
        putenv('SOLR_CORE=internformat');
        putenv('SOLR_TIMEOUT=5');
    }

    public static function client(): Client
    {
        self::useMockSolr();

        return (new SolrConnection())->getClient();
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

    /**
     * Liefert die "settings"-Variable, wie sie der Controller an die Templates übergibt: die
     * echte Konfiguration aus Configuration/TypoScript/setup.ts (plugin.tx_find.settings, u.a.
     * queryFields für dla:solveQuery). Zusätzlich wird die Solr-Verbindung auf den Mock-Solr-Server umgebogen,
     * damit die Unterabfragen der Partials (dla:countFromSolr, dla:fromSolr) gegen die Cassetten laufen.
     */
    public static function settings(): array
    {
        static $typoScriptSettings = null;
        if ($typoScriptSettings === null) {
            $lineStream = (new LossyTokenizer())->tokenize((string)file_get_contents(dirname(__DIR__, 2) . '/Configuration/TypoScript/setup.ts'));
            $ast = (new AstBuilder(new EventDispatcher()))->build($lineStream, new RootNode());
            $plain = (new TypoScriptService())->convertTypoScriptArrayToPlainArray($ast->toArray());
            $typoScriptSettings = $plain['plugin']['tx_find']['settings'];
        }

        self::useMockSolr();

        return $typoScriptSettings;
    }

    /**
     * Variablen, mit denen das Detail-Template (Templates/Search/Detail.html) die Detail-Partials
     * aufruft: "document" (per ID geladen), "results" (Trefferliste mit genau diesem Dokument,
     * wie SolrServiceProvider::getDocumentById() sie liefert), "config" und "settings".
     */
    public static function detailVariables(string $id): array
    {
        return [
            'document' => self::document($id),
            'results' => self::select('id:(' . $id . ')', 1),
            'config' => ['uid' => 1, 'jumpToID' => ''],
            'settings' => self::settings(),
        ];
    }
}
