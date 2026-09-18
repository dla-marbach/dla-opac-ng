<?php

namespace Dla\DlaOpacNg\Tests\Unit\Ajax;

use Dla\DlaOpacNg\Ajax\Autocomplete;
use Dla\DlaOpacNg\Tests\Support\MockSolrServerProcess;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests the Autocomplete middleware against a local Solr mock server replaying
 * recorded real-world suggest responses (see Tests/Fixtures/Solr/cassettes/autocomplete-*.json).
 */
class AutocompleteTest extends UnitTestCase
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

    private function processRequest(string $query): array
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['q' => $query, 'autocomplete' => '1']);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = (new Autocomplete())->process($request, $handler);

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * @test
     */
    public function knownQueryReturnsTextAndNormdataSuggestions(): void
    {
        $suggestions = $this->processRequest('goethe');

        self::assertNotEmpty($suggestions);

        // Text suggestions come first, followed by a separator ('br') and normdata suggestions.
        $separatorIndex = null;
        foreach ($suggestions as $index => $suggestion) {
            if (($suggestion['id'] ?? null) === 'br') {
                $separatorIndex = $index;
                break;
            }
        }
        self::assertNotNull($separatorIndex, 'Expected a separator between text and normdata suggestions');

        $normdataSuggestion = $suggestions[$separatorIndex + 1];
        self::assertSame('searchEntity_id_mv:PE00000863', $normdataSuggestion['id']);
        self::assertSame('0', $normdataSuggestion['autocomplete']);
    }

    /**
     * @test
     */
    public function queryWithoutResultsReturnsEmptyArray(): void
    {
        self::assertSame([], $this->processRequest('zzznoresultzzz'));
    }

    /**
     * @test
     */
    public function blankQueryReturnsEmptyArrayWithoutCallingSolr(): void
    {
        self::assertSame([], $this->processRequest(''));
    }
}
