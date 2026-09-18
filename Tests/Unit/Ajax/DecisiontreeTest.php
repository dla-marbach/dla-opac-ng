<?php

namespace Dla\DlaOpacNg\Tests\Unit\Ajax;

use Dla\DlaOpacNg\Ajax\Decisiontree;
use Dla\DlaOpacNg\Tests\Support\MockSolrServerProcess;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests the Decisiontree middleware against a local Solr mock server replaying
 * recorded real-world facet responses (see Tests/Fixtures/Solr/cassettes/decisiontree-*.json).
 */
class DecisiontreeTest extends UnitTestCase
{
    private static ?MockSolrServerProcess $mockSolr = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$mockSolr = MockSolrServerProcess::start();
    }

    protected function setUp(): void
    {
        parent::setUp();
        putenv('SOLR_HOST=' . self::$mockSolr->getBaseUrl());
        putenv('SOLR_CORE=internformat');
    }

    private function processRequest(array $queryParams): array
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = (new Decisiontree())->process($request, $handler);

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * @test
     */
    public function singleRelationFieldReturnsItsFacetCounts(): void
    {
        $output = $this->processRequest([
            'q' => 'goethe',
            'p' => '',
            'decisiontree' => '1',
            'relation1' => 'filterAuthorityRelation_mv',
        ]);

        self::assertCount(1, $output);
        self::assertContains('Goethe, Johann Wolfgang von (1749-1832)␝Über', $output[0]);
    }

    /**
     * @test
     */
    public function twoRelationFieldsReturnBothFacetCounts(): void
    {
        $output = $this->processRequest([
            'q' => 'goethe',
            'p' => '',
            'decisiontree' => '1',
            'relation1' => 'filterAuthorityRelation_mv',
            'relation2' => 'filterAuthorityRole_mv',
        ]);

        self::assertCount(2, $output);
        self::assertContains('Goethe, Johann Wolfgang von (1749-1832)␝Über', $output[0]);
    }
}
