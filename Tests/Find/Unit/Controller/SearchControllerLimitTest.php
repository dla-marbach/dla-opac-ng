<?php

namespace Dla\Find\Tests\Unit\Controller;

use Dla\Find\Controller\SearchController;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Prüft, dass SearchController Anfragen jenseits der Such-Limits abweist, bevor eine Verbindung
 * zu Solr aufgebaut wird (HTML-Seite und JSON-Datenpfad).
 */
class SearchControllerLimitTest extends UnitTestCase
{
    private function createController(array $arguments, string $action = 'index', string $format = 'html', bool $expectConnection = false): SearchController
    {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logManager = $this->createMock(LogManagerInterface::class);
        $logManager->method('getLogger')->willReturn($logger);

        $controller = $this->getMockBuilder(SearchController::class)
            ->setConstructorArgs([$logManager])
            ->onlyMethods(['initializeConnection', 'translate'])
            ->getMock();
        $controller->expects($expectConnection ? self::once() : self::never())->method('initializeConnection');
        $controller->method('translate')->willReturnCallback(
            static fn (string $key, ?array $args = null): string => $key . ($args ? ':' . implode(',', $args) : '')
        );

        $searchProvider = new class () {
            public array $requestArguments = [];
            public function setRequestArguments(array $arguments): void
            {
                $this->requestArguments = $arguments;
            }
            public function setAction($action): void
            {
            }
            public function setControllerExtensionKey($key): void
            {
            }
        };

        $parameters = (new ExtbaseRequestParameters())
            ->setControllerExtensionName('Find')
            ->setPluginName('Find')
            ->setControllerName('Search')
            ->setControllerActionName($action)
            ->setFormat($format)
            ->setArguments($arguments);
        $request = new Request((new ServerRequest('https://example.org/find'))->withAttribute('extbase', $parameters));

        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->method('reset')->willReturnSelf();
        $uriBuilder->method('uriFor')->willReturn('/find?x=<y>');

        $this->setProperty($controller, 'request', $request);
        $this->setProperty($controller, 'uriBuilder', $uriBuilder);
        $this->setProperty($controller, 'searchProvider', $searchProvider);
        $this->setProperty($controller, 'settings', [
            'activeConnection' => 'default',
            'paging' => ['perPage' => 25, 'maximumPerPage' => 100],
            'queryFields' => [0 => ['id' => 'default', 'type' => 'Text']],
            'limits' => ['maxResultWindow' => 10000, 'maxResultWindowEmptyQuery' => 1000, 'maxActiveFilters' => 10],
        ]);

        return $controller;
    }

    private function setProperty(object $object, string $name, $value): void
    {
        $property = new \ReflectionProperty(SearchController::class, $name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    private function getProperty(object $object, string $name)
    {
        $property = new \ReflectionProperty(SearchController::class, $name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    private function initialize(SearchController $controller): void
    {
        $method = new \ReflectionMethod(SearchController::class, 'initializeAction');
        $method->setAccessible(true);
        $method->invoke($controller);
    }

    private function initializeExpectingRejection(SearchController $controller): \Psr\Http\Message\ResponseInterface
    {
        try {
            $this->initialize($controller);
        } catch (PropagateResponseException $exception) {
            return $exception->getResponse();
        }
        self::fail('Expected the request to be rejected.');
    }

    /**
     * @test
     */
    public function requestExactlyAtLimitIsPassedToSolr(): void
    {
        $this->initialize($this->createController(['q' => ['default' => 'goethe'], 'page' => '400'], 'index', 'html', true));
        $this->initialize($this->createController(['page' => '40'], 'index', 'html', true));
    }

    /**
     * @test
     */
    public function requestJustAboveLimitIsRejectedWithoutSolrConnection(): void
    {
        $response = $this->initializeExpectingRejection(
            $this->createController(['q' => ['default' => 'goethe'], 'page' => '401'])
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertStringStartsWith('text/html', $response->getHeaderLine('Content-Type'));
        self::assertSame('noindex, nofollow', $response->getHeaderLine('X-Robots-Tag'));
        $body = (string)$response->getBody();
        self::assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $body);
        self::assertStringContainsString('searchLimit.resultWindow:10000', $body);
        self::assertStringNotContainsString('<a ', $body);
    }

    /**
     * @test
     */
    public function emptySearchCrawlerRequestIsRejected(): void
    {
        $response = $this->initializeExpectingRejection(
            $this->createController(['q' => ['default' => '*:*'], 'start' => '4079300', 'count' => '25'])
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('searchLimit.resultWindowEmptyQuery:1000', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function apiPathReturnsJsonError(): void
    {
        $response = $this->initializeExpectingRejection(
            $this->createController(['start' => '976'], 'index', 'data')
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
        $data = json_decode((string)$response->getBody(), true);
        self::assertSame('searchLimitExceeded', $data['error']);
        self::assertSame('resultWindow', $data['reason']);
        self::assertSame(1000, $data['limit']);
    }

    /**
     * @test
     */
    public function apiPathAtLimitIsPassedToSolr(): void
    {
        $this->initialize($this->createController(['start' => '975'], 'index', 'data', true));
    }

    /**
     * @test
     */
    public function tooManyActiveFiltersAreRejected(): void
    {
        $facets = [];
        for ($i = 1; $i <= 11; $i++) {
            $facets['Medium']['Wert ' . $i] = 'not';
        }

        $response = $this->initializeExpectingRejection(
            $this->createController(['q' => ['default' => 'goethe'], 'facet' => $facets])
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('searchLimit.activeFilters:10', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function detailPageDropsUnderlyingQueryBeyondLimitInsteadOfFailing(): void
    {
        $controller = $this->createController(
            ['id' => 'HS00001', 'underlyingQuery' => ['q' => ['default' => '*:*'], 'position' => '4079300']],
            'detail',
            'html',
            true
        );
        $this->initialize($controller);

        self::assertArrayNotHasKey('underlyingQuery', $this->getProperty($controller, 'requestArguments'));
        self::assertArrayNotHasKey('underlyingQuery', $this->getProperty($controller, 'searchProvider')->requestArguments);
    }

    /**
     * @test
     */
    public function detailPageKeepsUnderlyingQueryWithinLimit(): void
    {
        $controller = $this->createController(
            ['id' => 'HS00001', 'underlyingQuery' => ['q' => ['default' => 'goethe'], 'position' => '10000']],
            'detail',
            'html',
            true
        );
        $this->initialize($controller);

        self::assertArrayHasKey('underlyingQuery', $this->getProperty($controller, 'requestArguments'));
    }
}
