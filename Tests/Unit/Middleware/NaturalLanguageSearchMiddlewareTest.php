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
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
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
        'persons' => [['name' => 'Hesse', 'relation' => 'An']],
        'topics' => [['term' => 'Exil', 'variants' => []]],
        'mediaTypes' => ['Handschriften'],
    ];

    private VariableFrontend $cache;

    private InMemoryStorage $rateLimiterStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new VariableFrontend('test', new TransientMemoryBackend('Testing'));
        $this->rateLimiterStorage = new InMemoryStorage();
    }

    private function createMiddleware(?array $answer = self::ANSWER, string $baseUrl = 'http://llm.test/v1', int $rateLimit = 10): NaturalLanguageSearchMiddleware
    {
        $configuration = new QueryUnderstandingConfiguration(baseUrl: $baseUrl, model: 'test-model', rateLimitPerMinute: $rateLimit);
        $llmClient = $this->createMock(LlmClient::class);
        $llmClient->method('completeJson')->willReturn($answer);
        $service = new QueryUnderstandingService(
            $configuration,
            $llmClient,
            new QueryPrompt(),
            new SearchArgumentsBuilder(new FakeVocabularyResolver(['Hesse' => ['id' => 'PE1', 'label' => 'Hesse, Hermann (1877-1962)']])),
            new FakeFacetVocabulary(),
            $this->cache,
            new NullLogger(),
        );

        return new NaturalLanguageSearchMiddleware($service, $configuration, $this->rateLimiterStorage, new NullLogger());
    }

    /**
     * Verarbeitet eine Anfrage und liefert die tx_find_find-Argumente der Weiterleitung.
     */
    private function redirect(NaturalLanguageSearchMiddleware $middleware, array $findArguments, string $clientIp = '192.0.2.1'): array
    {
        $queryParams = ['tx_find_find' => $findArguments, 'cHash' => 'abc'];
        $request = (new ServerRequest('https://www.example.org/katalog/find', 'GET', 'php://input', [], ['REMOTE_ADDR' => $clientIp]))
            ->withQueryParams($queryParams);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = $middleware->process($request, $handler);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('noindex, nofollow', $response->getHeaderLine('X-Robots-Tag'));
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
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn(new HtmlResponse('handled'));
        $request = (new ServerRequest('https://www.example.org/katalog/find'))->withQueryParams(['tx_find_find' => ['q' => ['default' => 'Hesse']]]);

        self::assertSame('handled', (string)$this->createMiddleware()->process($request, $handler)->getBody());
    }

    /**
     * @test
     */
    public function redirectsToStructuredSearchKeepingOtherArguments(): void
    {
        $arguments = $this->redirect($this->createMiddleware(), [
            'nl' => '1',
            'q' => ['default' => 'Handschriften an Hesse über Exil'],
            'facet' => ['Sprache' => ['Deutsch' => '1']],
            'count' => '25',
            'page' => '3',
            // Interpretation einer früheren Frage wird ersetzt
            'nlf' => [['f' => 'Ort', 't' => 'Atlantis']],
        ]);

        self::assertSame([
            'q' => ['default' => 'exil'],
            'facet' => [
                'Sprache' => ['Deutsch' => '1'],
                'filterAuthorityRelation_mv' => ['Hesse, Hermann (1877-1962)␝An' => '1'],
                'Medientypen' => ['Handschriften' => '1'],
            ],
            'count' => '25',
            'nlf' => [
                ['f' => 'filterAuthorityRelation_mv', 't' => 'Hesse, Hermann (1877-1962)␝An'],
                ['f' => 'Medientypen', 't' => 'Handschriften'],
            ],
            'nlt' => [['k' => 'topic', 'q' => 'exil', 'l' => 'Exil']],
            'nlq' => 'Handschriften an Hesse über Exil',
            'nlstatus' => 'ok',
        ], $arguments);
    }

    /**
     * @test
     */
    public function fallsBackToNormalSearch(): void
    {
        $expected = ['q' => ['default' => 'Briefe von Hesse'], 'nlq' => 'Briefe von Hesse'];
        $request = ['nl' => '1', 'q' => ['default' => 'Briefe von Hesse']];

        self::assertSame($expected + ['nlstatus' => 'fallback'], $this->redirect($this->createMiddleware(baseUrl: ''), $request));
        self::assertSame($expected + ['nlstatus' => 'fallback'], $this->redirect($this->createMiddleware(answer: null), $request));
        self::assertSame($expected + ['nlstatus' => 'unrecognized'], $this->redirect($this->createMiddleware(answer: ['text' => '']), $request));
    }

    /**
     * @test
     */
    public function ignoresEmptyQuestionAndManipulatedArguments(): void
    {
        self::assertSame([], $this->redirect($this->createMiddleware(), ['nl' => '1', 'q' => ['default' => '  ']]));
        self::assertSame([], $this->redirect($this->createMiddleware(), ['nl' => '1', 'q' => 'Hesse', 'facet' => 'kaputt']));
    }

    /**
     * @test
     */
    public function enforcesRateLimitPerClientIpExceptForCachedQuestions(): void
    {
        $middleware = $this->createMiddleware(rateLimit: 1);
        $status = fn(string $question, string $ip = '192.0.2.1'): string =>
            $this->redirect($middleware, ['nl' => '1', 'q' => ['default' => $question]], $ip)['nlstatus'];

        self::assertSame('ok', $status('Frage eins'));
        self::assertSame('fallback', $status('Frage zwei'));
        self::assertSame('ok', $status('Frage drei', '192.0.2.99'));
        self::assertSame('ok', $status('Frage eins'), 'zwischengespeichert, daher nicht begrenzt');
        self::assertSame('ok', $this->redirect($this->createMiddleware(rateLimit: 0), ['nl' => '1', 'q' => ['default' => 'Frage vier']])['nlstatus']);
    }
}
