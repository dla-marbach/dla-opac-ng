<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service;

use Dla\DlaOpacNg\Service\QueryUnderstanding\LlmClient;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryInterpretation;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryStructure;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use Dla\DlaOpacNg\Service\QueryUnderstanding\SearchArgumentsBuilder;
use Dla\DlaOpacNg\Service\QueryUnderstandingService;
use Dla\DlaOpacNg\Tests\Support\FakeVocabularyResolver;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class QueryUnderstandingServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private const ANSWER = [
        'text' => '',
        'persons' => [['name' => 'Hesse', 'relation' => 'Von']],
        'topics' => [['term' => 'Exil', 'variants' => ['Emigration']]],
        'mediaTypes' => ['Handschriften'],
        'formContent' => [],
        'languages' => [],
        'places' => ['Atlantis'],
        'dateFrom' => 1930,
        'dateTo' => 1939,
        'digitalOnly' => false,
    ];

    private function createConfiguration(string $baseUrl = 'http://llm.invalid/v1'): QueryUnderstandingConfiguration
    {
        return new QueryUnderstandingConfiguration(baseUrl: $baseUrl, model: 'test-model');
    }

    /**
     * LLM-Client, der eine feste Antwort liefert und die Aufrufe zählt.
     */
    private function createLlmClient(?array $answer): LlmClient
    {
        $client = $this->getMockBuilder(LlmClient::class)
            ->setConstructorArgs([$this->createConfiguration()])
            ->onlyMethods(['completeJson'])
            ->getMock();
        $client->method('completeJson')->willReturn($answer);

        return $client;
    }

    private function createCache(): VariableFrontend
    {
        return new VariableFrontend('test', new TransientMemoryBackend('Testing'));
    }

    private function createService(
        LlmClient $llmClient,
        ?FakeVocabularyResolver $resolver = null,
        ?VariableFrontend $cache = null,
        ?QueryUnderstandingConfiguration $configuration = null,
    ): QueryUnderstandingService {
        return new QueryUnderstandingService(
            configuration: $configuration ?? $this->createConfiguration(),
            llmClient: $llmClient,
            builder: new SearchArgumentsBuilder($resolver ?? new FakeVocabularyResolver()),
            cache: $cache,
        );
    }

    /**
     * @test
     */
    public function analyzeReturnsValidatedStructure(): void
    {
        $structure = $this->createService($this->createLlmClient(self::ANSWER))->analyze('  Briefe   von Hesse  ');

        self::assertSame('Hesse', $structure->persons[0]['name']);
        self::assertSame(['Handschriften'], $structure->mediaTypes);
    }

    /**
     * @test
     */
    public function analyzeCachesResultsPerQuestion(): void
    {
        $llmClient = $this->createLlmClient(self::ANSWER);
        $llmClient->expects(self::once())->method('completeJson');
        $service = $this->createService($llmClient, cache: $this->createCache());

        self::assertFalse($service->isCached('Briefe von Hesse'));
        $first = $service->analyze('Briefe von Hesse');
        self::assertTrue($service->isCached('briefe  von HESSE'));
        $second = $service->analyze('briefe  von HESSE');

        self::assertEquals($first, $second);
    }

    /**
     * @test
     */
    public function analyzeReturnsNullAndCachesNothingWhenNothingWasRecognized(): void
    {
        $cache = $this->createCache();
        $service = $this->createService($this->createLlmClient(['text' => '']), cache: $cache);

        self::assertNull($service->analyze('???'));
        self::assertFalse($service->isCached('???'));
    }

    /**
     * @test
     */
    public function analyzeReturnsNullWhenLlmFails(): void
    {
        self::assertNull($this->createService($this->createLlmClient(null))->analyze('Briefe von Hesse'));
    }

    /**
     * @test
     */
    public function analyzeDoesNotCallLlmWhenDisabled(): void
    {
        $llmClient = $this->createLlmClient(self::ANSWER);
        $llmClient->expects(self::never())->method('completeJson');
        $service = $this->createService($llmClient, configuration: $this->createConfiguration(''));

        self::assertFalse($service->isEnabled());
        self::assertNull($service->analyze('Briefe von Hesse'));
    }

    /**
     * @test
     */
    public function interpretKeepsEverythingWhenThereAreResults(): void
    {
        $resolver = new FakeVocabularyResolver(
            persons: ['Hesse' => ['id' => 'PE1', 'label' => 'Hesse, Hermann (1877-1962)']],
            counter: static fn(): int => 12,
        );
        $service = $this->createService($this->createLlmClient(self::ANSWER), $resolver);

        $interpretation = $service->interpret(QueryStructure::fromArray(self::ANSWER));

        self::assertSame([], $interpretation->dropped);
        self::assertSame('atlantis (exil OR emigration)', $interpretation->getQueryString());
        self::assertSame(['from' => 1930, 'to' => 1939], $interpretation->dateRange);
    }

    /**
     * @test
     */
    public function interpretRelaxesStepByStepUntilThereAreResults(): void
    {
        // Treffer erst, wenn weder "atlantis" noch das Thema Exil in der Anfrage stehen
        $resolver = new FakeVocabularyResolver(
            persons: ['Hesse' => ['id' => 'PE1', 'label' => 'Hesse, Hermann (1877-1962)']],
            counter: static fn(string $query): int => $query === '*:*' ? 3 : 0,
        );
        $service = $this->createService($this->createLlmClient(self::ANSWER), $resolver);

        $interpretation = $service->interpret(QueryStructure::fromArray(self::ANSWER));

        self::assertSame(['Atlantis', 'Exil'], $interpretation->dropped);
        self::assertSame('', $interpretation->getQueryString());
        self::assertSame(['from' => 1930, 'to' => 1939], $interpretation->dateRange, 'Zeitraum bleibt, da es nun Treffer gibt');
        self::assertContains('filterAuthorityRelation_mv:("Hesse, Hermann (1877-1962)␝Von")', $interpretation->getFilterQueries());
    }

    /**
     * @test
     */
    public function interpretDropsNothingWhenSolrIsUnavailable(): void
    {
        $resolver = new FakeVocabularyResolver(counter: static fn(): ?int => null);
        $service = $this->createService($this->createLlmClient(self::ANSWER), $resolver);

        $interpretation = $service->interpret(QueryStructure::fromArray(self::ANSWER));

        self::assertSame([], $interpretation->dropped);
        self::assertTrue($interpretation->hasPart(QueryInterpretation::PART_TOPIC));
    }

    /**
     * @test
     */
    public function normalizeQuestionTrimsAndLimitsLength(): void
    {
        self::assertSame('Briefe von Hesse', QueryUnderstandingService::normalizeQuestion("  Briefe\n von   Hesse "));
        self::assertSame(300, mb_strlen(QueryUnderstandingService::normalizeQuestion(str_repeat('ä', 500))));
    }
}
