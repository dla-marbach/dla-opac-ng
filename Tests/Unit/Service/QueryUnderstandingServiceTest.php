<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service;

use Dla\DlaOpacNg\Service\QueryUnderstanding\LlmClient;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryPrompt;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use Dla\DlaOpacNg\Service\QueryUnderstanding\SearchArgumentsBuilder;
use Dla\DlaOpacNg\Service\QueryUnderstandingService;
use Dla\DlaOpacNg\Tests\Support\FakeFacetVocabulary;
use Dla\DlaOpacNg\Tests\Support\FakeVocabularyResolver;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class QueryUnderstandingServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private const ANSWER = [
        'persons' => [['name' => 'Hesse', 'relation' => 'Von']],
        'mediaTypes' => ['Handschriften'],
        'formContent' => ['Brief', 'Briefe'],
    ];

    private LlmClient&MockObject $llmClient;

    private function createService(?array $answer, string $baseUrl = 'http://llm.test/v1'): QueryUnderstandingService
    {
        $configuration = new QueryUnderstandingConfiguration(baseUrl: $baseUrl, model: 'test-model');
        $this->llmClient = $this->createMock(LlmClient::class);
        $this->llmClient->method('completeJson')->willReturn($answer);

        return new QueryUnderstandingService(
            $configuration,
            $this->llmClient,
            new QueryPrompt(),
            new SearchArgumentsBuilder(new FakeVocabularyResolver(['Hesse' => ['id' => 'PE1', 'label' => 'Hesse, Hermann (1877-1962)']])),
            new FakeFacetVocabulary(),
            new VariableFrontend('test', new TransientMemoryBackend('Testing')),
            new NullLogger(),
        );
    }

    /**
     * @test
     */
    public function analyzesWithIndexVocabularyAndCachesPerQuestion(): void
    {
        $service = $this->createService(self::ANSWER);
        $this->llmClient->expects(self::once())->method('completeJson')->with(
            self::callback(static fn(array $messages): bool => str_contains($messages[0]['content'], '"formContent": "Brief", "Gedicht", "Übersetzung"')),
            self::callback(static fn(array $schema): bool => $schema['properties']['formContent']['items']['enum'] === ['Brief', 'Gedicht', 'Übersetzung']),
        );

        self::assertFalse($service->isCached('Briefe von Hesse'));
        $structure = $service->analyze('  Briefe   von Hesse ');
        self::assertTrue($service->isCached('briefe von HESSE'));
        self::assertEquals($structure, $service->analyze('briefe von HESSE'));

        self::assertSame(['Brief'], $structure->formContent, 'nur Werte aus dem Index');
        self::assertSame(
            ['filterAuthorityRelation_mv' => ['Hesse, Hermann (1877-1962)␝Von' => '1'], 'Medientypen' => ['Handschriften' => '1'], 'FormUndInhalt' => ['Brief' => '1']],
            $service->interpret($structure)->toFindArguments()['facet']
        );
    }

    /**
     * @test
     */
    public function cachesAlsoWhenNothingWasRecognized(): void
    {
        $service = $this->createService(['text' => '']);
        $this->llmClient->expects(self::once())->method('completeJson');

        self::assertTrue($service->analyze('???')->isEmpty());
        self::assertTrue($service->analyze('???')->isEmpty());
    }

    /**
     * @test
     */
    public function returnsNullWhenLlmFailsOrIsDisabled(): void
    {
        self::assertNull($this->createService(null)->analyze('Briefe von Hesse'));

        $disabled = $this->createService(self::ANSWER, '');
        $this->llmClient->expects(self::never())->method('completeJson');
        self::assertFalse($disabled->isEnabled());
        self::assertNull($disabled->analyze('Briefe von Hesse'));
    }

    /**
     * @test
     */
    public function normalizesQuestions(): void
    {
        self::assertSame('Briefe von Hesse', QueryUnderstandingService::normalizeQuestion("  Briefe\n von   Hesse "));
        self::assertSame(300, mb_strlen(QueryUnderstandingService::normalizeQuestion(str_repeat('ä', 500))));
    }
}
