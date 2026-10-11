<?php

namespace Dla\DlaOpacNg\Tests\Unit\Middleware;

use Dla\DlaOpacNg\Middleware\NaturalLanguageSearchMiddleware;
use Dla\DlaOpacNg\Service\QueryUnderstanding\ConcurrencyLimiter;
use Dla\DlaOpacNg\Service\QueryUnderstanding\LlmClient;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use Dla\DlaOpacNg\Service\QueryUnderstanding\SearchArgumentsBuilder;
use Dla\DlaOpacNg\Service\QueryUnderstandingService;
use Dla\DlaOpacNg\Tests\Support\FakeVocabularyResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class NaturalLanguageSearchMiddlewareTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private const ANSWER = [
        'text' => '',
        'persons' => [['name' => 'Hesse', 'relation' => 'An']],
        'topics' => [],
        'mediaTypes' => ['Handschriften'],
        'formContent' => [],
        'languages' => [],
        'places' => [],
        'dateFrom' => null,
        'dateTo' => null,
        'digitalOnly' => false,
    ];

    private string $lockDirectory;

    private VariableFrontend $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockDirectory = sys_get_temp_dir() . '/dla-nl-test-' . bin2hex(random_bytes(4));
        $this->cache = new VariableFrontend('test', new TransientMemoryBackend('Testing'));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->lockDirectory . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->lockDirectory);
        parent::tearDown();
    }

    private function createMiddleware(
        string $baseUrl = 'http://llm.invalid/v1',
        int $rateLimit = 10,
        int $slots = 2,
        ?array $answer = self::ANSWER,
    ): NaturalLanguageSearchMiddleware {
        $configuration = new QueryUnderstandingConfiguration(
            baseUrl: $baseUrl,
            model: 'test-model',
            maxConcurrent: $slots,
            rateLimitPerMinute: $rateLimit,
        );
        $llmClient = $this->getMockBuilder(LlmClient::class)
            ->setConstructorArgs([$configuration])
            ->onlyMethods(['completeJson'])
            ->getMock();
        $llmClient->method('completeJson')->willReturn($answer);

        $service = new QueryUnderstandingService(
            configuration: $configuration,
            llmClient: $llmClient,
            builder: new SearchArgumentsBuilder(new FakeVocabularyResolver(
                persons: ['Hesse' => ['id' => 'PE1', 'label' => 'Hesse, Hermann (1877-1962)']],
            )),
            cache: $this->cache,
        );

        return new NaturalLanguageSearchMiddleware(
            $service,
            $this->cache,
            new ConcurrencyLimiter($this->lockDirectory, $slots)
        );
    }

    private function createRequest(array $findArguments, string $clientIp = '192.0.2.1'): ServerRequest
    {
        $queryParams = ['tx_find_find' => $findArguments, 'cHash' => 'abc'];

        return (new ServerRequest(
            'https://www.example.org/katalog/find?' . http_build_query($queryParams),
            'GET',
            'php://input',
            [],
            ['REMOTE_ADDR' => $clientIp]
        ))->withQueryParams($queryParams);
    }

    private function createHandler(bool $expectCall): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($expectCall ? self::once() : self::never())
            ->method('handle')
            ->willReturn(new HtmlResponse('handled'));

        return $handler;
    }

    /**
     * @return array<string,mixed> tx_find_find-Argumente der Weiterleitung
     */
    private function redirectArguments(ResponseInterface $response): array
    {
        self::assertSame(303, $response->getStatusCode());
        $location = parse_url($response->getHeaderLine('Location'));
        self::assertSame('/katalog/find', $location['path']);
        parse_str($location['query'] ?? '', $params);
        self::assertArrayNotHasKey('cHash', $params);

        return $params['tx_find_find'] ?? [];
    }

    /**
     * @test
     */
    public function passesThroughRequestsWithoutFlag(): void
    {
        $response = $this->createMiddleware()->process(
            $this->createRequest(['q' => ['default' => 'Hesse']]),
            $this->createHandler(true)
        );

        self::assertSame('handled', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function redirectsToStructuredSearchKeepingOtherArguments(): void
    {
        $response = $this->createMiddleware()->process(
            $this->createRequest([
                'nl' => '1',
                'q' => ['default' => 'Handschriften an Hesse'],
                'facet' => ['Sprache' => ['Deutsch' => '1']],
                'count' => '25',
                'page' => '3',
            ]),
            $this->createHandler(false)
        );

        $arguments = $this->redirectArguments($response);
        self::assertSame([
            'facet' => [
                'Sprache' => ['Deutsch' => '1'],
                'filterAuthorityRelation_mv' => ['Hesse, Hermann (1877-1962)␝An' => '1'],
                'Medientypen' => ['Handschriften' => '1'],
            ],
            'count' => '25',
            'nlq' => 'Handschriften an Hesse',
            'nlstatus' => 'ok',
        ], $arguments);
        self::assertSame('noindex, nofollow', $response->getHeaderLine('X-Robots-Tag'));
    }

    /**
     * @test
     */
    public function fallsBackToNormalSearchWhenDisabled(): void
    {
        $response = $this->createMiddleware(baseUrl: '')->process(
            $this->createRequest(['nl' => '1', 'q' => ['default' => 'Briefe von Hesse']]),
            $this->createHandler(false)
        );

        self::assertSame([
            'q' => ['default' => 'Briefe von Hesse'],
            'nlq' => 'Briefe von Hesse',
            'nlstatus' => 'fallback',
        ], $this->redirectArguments($response));
    }

    /**
     * @test
     */
    public function fallsBackToNormalSearchWhenLlmFails(): void
    {
        $response = $this->createMiddleware(answer: null)->process(
            $this->createRequest(['nl' => '1', 'q' => ['default' => 'Briefe von Hesse']]),
            $this->createHandler(false)
        );

        self::assertSame('fallback', $this->redirectArguments($response)['nlstatus']);
    }

    /**
     * @test
     */
    public function removesFlagForEmptyQuestion(): void
    {
        $response = $this->createMiddleware()->process(
            $this->createRequest(['nl' => '1', 'q' => ['default' => '  ']]),
            $this->createHandler(false)
        );

        self::assertSame([], $this->redirectArguments($response));
    }

    /**
     * @test
     */
    public function ignoresManipulatedNonArrayArguments(): void
    {
        $response = $this->createMiddleware()->process(
            $this->createRequest(['nl' => '1', 'q' => 'Handschriften an Hesse', 'facet' => 'kaputt']),
            $this->createHandler(false)
        );

        self::assertSame([], $this->redirectArguments($response));
    }

    /**
     * @test
     */
    public function enforcesRateLimitPerClientIp(): void
    {
        $middleware = $this->createMiddleware(rateLimit: 1);
        $handler = $this->createHandler(false);

        $first = $middleware->process($this->createRequest(['nl' => '1', 'q' => ['default' => 'Frage eins']]), $handler);
        $second = $middleware->process($this->createRequest(['nl' => '1', 'q' => ['default' => 'Frage zwei']]), $handler);
        $otherClient = $middleware->process(
            $this->createRequest(['nl' => '1', 'q' => ['default' => 'Frage drei']], '192.0.2.99'),
            $handler
        );
        $cachedQuestion = $middleware->process($this->createRequest(['nl' => '1', 'q' => ['default' => 'Frage eins']]), $handler);

        self::assertSame('ok', $this->redirectArguments($first)['nlstatus']);
        self::assertSame('fallback', $this->redirectArguments($second)['nlstatus']);
        self::assertSame('ok', $this->redirectArguments($otherClient)['nlstatus']);
        self::assertSame('ok', $this->redirectArguments($cachedQuestion)['nlstatus'], 'Zwischengespeicherte Fragen sind nicht begrenzt');
    }

    /**
     * @test
     */
    public function fallsBackWhenAllLlmSlotsAreBusy(): void
    {
        $busy = new ConcurrencyLimiter($this->lockDirectory, 1);
        self::assertTrue($busy->acquire());

        $response = $this->createMiddleware(slots: 1)->process(
            $this->createRequest(['nl' => '1', 'q' => ['default' => 'Briefe von Hesse']]),
            $this->createHandler(false)
        );
        self::assertSame('fallback', $this->redirectArguments($response)['nlstatus']);

        $busy->release();
        $response = $this->createMiddleware(slots: 1)->process(
            $this->createRequest(['nl' => '1', 'q' => ['default' => 'Briefe von Hesse']]),
            $this->createHandler(false)
        );
        self::assertSame('ok', $this->redirectArguments($response)['nlstatus']);
    }
}
