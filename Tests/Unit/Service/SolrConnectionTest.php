<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service;

use Dla\DlaOpacNg\Service\SolrConnection;
use Dla\DlaOpacNg\Tests\Support\MockSolrServerProcess;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests SolrConnection (configuration from SOLR_HOST/SOLR_CORE, Solarium client and raw requests)
 * against the local Solr mock server (see Tests/Fixtures/Solr/cassettes).
 */
class SolrConnectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getCoreUrlCombinesHostAndCore(): void
    {
        putenv('SOLR_HOST=http://solr.example.org:8983/solr');
        putenv('SOLR_CORE=/internformat/');

        self::assertSame('http://solr.example.org:8983/solr/internformat/', (new SolrConnection())->getCoreUrl());
    }

    /**
     * @test
     */
    public function getCoreUrlThrowsWithoutConfiguration(): void
    {
        putenv('SOLR_HOST');
        putenv('SOLR_CORE');

        $this->expectException(\RuntimeException::class);
        (new SolrConnection())->getCoreUrl();
    }

    /**
     * @test
     */
    public function requestReturnsNullWithoutConfiguration(): void
    {
        putenv('SOLR_HOST');
        putenv('SOLR_CORE');

        self::assertNull((new SolrConnection())->request('select', ['q' => '*:*']));
    }

    /**
     * @test
     */
    public function clientUsesSameCoreUrlAsRawRequests(): void
    {
        putenv('SOLR_HOST=https://solr.example.org/sub/path/solr/');
        putenv('SOLR_CORE=internformat');

        $endpoint = (new SolrConnection())->getClient()->getEndpoint();

        self::assertSame('https://solr.example.org:443/sub/path/solr/internformat/', $endpoint->getCoreBaseUri());
    }

    /**
     * @test
     */
    public function clientAndRawRequestReachMockSolr(): void
    {
        putenv('SOLR_HOST=' . MockSolrServerProcess::start()->getBaseUrl());
        putenv('SOLR_CORE=internformat');
        $connection = new SolrConnection();

        $query = $connection->getClient()->createSelect();
        $query->setQuery('id:(PE00000863)');
        $query->setRows(1);
        self::assertSame(1, $connection->getClient()->execute($query)->getNumFound());

        $json = $connection->request('select', ['q' => 'id:(PE00000863)', 'rows' => 1]);
        self::assertSame('PE00000863', $json['response']['docs'][0]['id'] ?? null);
    }
}
