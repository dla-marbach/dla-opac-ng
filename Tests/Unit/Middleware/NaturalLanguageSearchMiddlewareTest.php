<?php

namespace Dla\DlaOpacNg\Tests\Unit\Middleware;

use Dla\DlaOpacNg\Middleware\NaturalLanguageSearchMiddleware;
use Dla\DlaOpacNg\Service\QueryUnderstanding\LlmClient;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryPrompt;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use Dla\DlaOpacNg\Service\QueryUnderstanding\SearchArgumentsBuilder;
use Dla\DlaOpacNg\Service\QueryUnderstandingService;
use Dla\DlaOpacNg\Tests\Support\FakeFacetVocabulary;
use Dla\DlaOpacNg\Tests\Support\FakeVocabularyResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
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

    private VariableFrontend $cache;

    private InMemoryStorage $rateLimiterStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new VariableFrontend('test', new TransientMemoryBackend('Testing'));
        $this->rateLimiterStorage = new InMemoryStorage();
    }

    private function createMiddleware(
        string $baseUrl = 'http://llm.invalid/v1',
        int $rateLimit = 10,
        ?array $answer = self::ANSWER,
        ?\Closure $counter = null,
    ): NaturalLanguageSearchMiddleware {
        $configuration = new QueryUnderstandingConfiguration(
            baseUrl: $baseUrl,
            model: 'test-model',
            rateLimitPerMinute: $rateLimit,
        );
        $llmClient = $this->getMockBuilder(LlmClient::class)
            ->setConstructorArgs([$configuration])
            ->onlyMethods(['completeJson'])
            ->getMock();
        $llmClient->method('completeJson')->willReturn($answer);

        $service = new QueryUnderstandingService(
            $configuration,
            $llmClient,
            new QueryPrompt(),
            new SearchArgumentsBuilder(new FakeVocabularyResolver(
                persons: ['Hesse' => ['id' => 'PE1', 'label' => 'Hesse, Hermann (1877-1962)']],
                counter: $counter,
            )),
            new FakeFacetVocabulary(),
            $this->cache,
        );

        return new NaturalLanguageSearchMiddleware($service, $configuration, $this->rateLimiterStorage);
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
            'nlf' => [
                ['f' => 'filterAuthorityRelation_mv', 't' => 'Hesse, Hermann (1877-1962)␝An'],
                ['f' => 'Medientypen', 't' => 'Handschriften'],
            ],
            'nlstatus' => 'ok',
        ], $arguments);
        self::assertSame('noindex, nofollow', $response->getHeaderLine('X-Robots-Tag'));
    }

    /**
     * @test
     */
    public function relaxesWithRetainedFacetsOfTheRequest(): void
    {
        $answer = ['topics' => [['term' => 'Exil', 'variants' => []]]] + self::ANSWER;
        // Treffer nur ohne das Thema, sobald der beibehaltene Sprachfilter mitgezählt wird
        $counter = static fn(string $query, array $filterQueries): int =>
            in_array('filterLanguage_mv:("Deutsch")', $filterQueries, true) && $query === 'exil' ? 0 : 4;

        $response = $this->createMiddleware(answer: $answer, counter: $counter)->process(
            $this->createRequest([
                'nl' => '1',
                'q' => ['default' => 'Handschriften über Exil an Hesse'],
                'facet' => ['Sprache' => ['Deutsch' => '1']],
            ]),
            $this->createHandler(false)
        );

        $arguments = $this->redirectArguments($response);
        self::assertSame('relaxed', $arguments['nlstatus']);
        self::assertSame('Exil', $arguments['nldropped']);
        self::assertArrayNotHasKey('q', $arguments);
    }

    /**
     * @test
     */
    public function replacesInterpretationOfAPreviousQuestion(): void
    {
        $answer = ['text' => 'Steppenwolf', 'topics' => [['term' => 'Exil', 'variants' => ['Emigration']]]] + self::ANSWER;

        $response = $this->createMiddleware(answer: $answer)->process(
            $this->createRequest([
                'nl' => '1',
                'q' => ['default' => 'Steppenwolf-Handschriften an Hesse über Exil'],
                'nlq' => 'alte Frage',
                'nlstatus' => 'relaxed',
                'nldropped' => 'Atlantis',
                'nlf' => [['f' => 'Ort', 't' => 'Atlantis']],
                'nlt' => [['k' => 'text', 'q' => 'alt', 'l' => 'alt']],
            ]),
            $this->createHandler(false)
        );

        $arguments = $this->redirectArguments($response);
        self::assertSame('Steppenwolf (exil OR emigration)', $arguments['q']['default']);
        self::assertSame([
            ['k' => 'text', 'q' => 'Steppenwolf', 'l' => 'Steppenwolf'],
            ['k' => 'topic', 'q' => '(exil OR emigration)', 'l' => 'Exil'],
        ], $arguments['nlt']);
        self::assertNotContains(['f' => 'Ort', 't' => 'Atlantis'], $arguments['nlf']);
        self::assertArrayNotHasKey('nldropped', $arguments);
        self::assertSame('ok', $arguments['nlstatus']);
    }

    /**
     * @test
     */
    public function doesNotRelaxWhenRetainedFacetsCannotBeCounted(): void
    {
        $answer = ['topics' => [['term' => 'Exil', 'variants' => []]]] + self::ANSWER;

        $response = $this->createMiddleware(answer: $answer, counter: static fn(): int => 0)->process(
            $this->createRequest([
                'nl' => '1',
                'q' => ['default' => 'Handschriften über Exil an Hesse'],
                'facet' => ['NeuImKatalog' => ['Woche' => '1']],
            ]),
            $this->createHandler(false)
        );

        $arguments = $this->redirectArguments($response);
        self::assertSame('ok', $arguments['nlstatus']);
        self::assertSame('exil', $arguments['q']['default']);
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
    public function reportsUnrecognizedQuestionSeparatelyFromUnavailableLlm(): void
    {
        $response = $this->createMiddleware(answer: ['text' => ''])->process(
            $this->createRequest(['nl' => '1', 'q' => ['default' => 'Wie spät ist es?']]),
            $this->createHandler(false)
        );

        self::assertSame([
            'q' => ['default' => 'Wie spät ist es?'],
            'nlq' => 'Wie spät ist es?',
            'nlstatus' => 'unrecognized',
        ], $this->redirectArguments($response));
    }

    /**
     * @test
     */
    public function rateLimitCanBeDisabled(): void
    {
        $middleware = $this->createMiddleware(rateLimit: 0);
        $handler = $this->createHandler(false);

        foreach (['eins', 'zwei', 'drei'] as $question) {
            $response = $middleware->process($this->createRequest(['nl' => '1', 'q' => ['default' => 'Frage ' . $question]]), $handler);
            self::assertSame('ok', $this->redirectArguments($response)['nlstatus']);
        }
    }
}
