<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\LlmClient;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryPrompt;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use Dla\DlaOpacNg\Tests\Support\MockLlmServerProcess;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testet den LLM-Client gegen einen lokalen Mock einer OpenAI-kompatiblen API (Tests/Fixtures/Llm/).
 */
class LlmClientTest extends UnitTestCase
{
    private static ?MockLlmServerProcess $mockLlm = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$mockLlm = MockLlmServerProcess::start();
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$mockLlm->clearLastRequest();
    }

    private function createClient(float $timeout = 5.0, string $apiKey = ''): LlmClient
    {
        return new LlmClient(new QueryUnderstandingConfiguration(
            baseUrl: self::$mockLlm->getBaseUrl(),
            model: 'test-model',
            apiKey: $apiKey,
            timeout: $timeout,
        ));
    }

    private function complete(LlmClient $client, string $question): ?array
    {
        return $client->completeJson(
            (new QueryPrompt())->buildMessages($question),
            QueryPrompt::getJsonSchema(),
            'catalog_query'
        );
    }

    /**
     * @test
     */
    public function returnsDecodedJsonFromModelAnswer(): void
    {
        $result = $this->complete($this->createClient(), 'Briefe von Hesse an Thomas Mann aus den 1930er Jahren über das Exil');

        self::assertSame('Hesse', $result['persons'][0]['name']);
        self::assertSame(1930, $result['dateFrom']);
    }

    /**
     * @test
     */
    public function sendsModelSchemaAndFewShotExamples(): void
    {
        $this->complete($this->createClient(), 'Gedichte von Rilke');

        $body = self::$mockLlm->getLastRequest()['body'];
        self::assertSame('test-model', $body['model']);
        self::assertSame(0, $body['temperature']);
        self::assertSame('json_schema', $body['response_format']['type']);
        self::assertTrue($body['response_format']['json_schema']['strict']);
        self::assertSame(QueryPrompt::getJsonSchema(), $body['response_format']['json_schema']['schema']);

        self::assertSame('system', $body['messages'][0]['role']);
        self::assertStringContainsString('Deutschen Literaturarchivs Marbach', $body['messages'][0]['content']);
        // Beispiele als Frage/Antwort-Paare, danach die eigentliche Frage
        self::assertSame('user', $body['messages'][1]['role']);
        self::assertSame('assistant', $body['messages'][2]['role']);
        self::assertIsArray(json_decode($body['messages'][2]['content'], true));
        self::assertSame(['role' => 'user', 'content' => 'Gedichte von Rilke'], end($body['messages']));
    }

    /**
     * @test
     */
    public function sendsApiKeyAsBearerToken(): void
    {
        $this->complete($this->createClient(apiKey: 'secret'), 'Gedichte von Rilke');

        self::assertSame('Bearer secret', self::$mockLlm->getLastRequest()['authorization']);
    }

    /**
     * @test
     */
    public function returnsNullOnServerError(): void
    {
        self::assertNull($this->complete($this->createClient(), '__error__'));
    }

    /**
     * @test
     */
    public function returnsNullOnInvalidJson(): void
    {
        self::assertNull($this->complete($this->createClient(), '__invalid__'));
    }

    /**
     * @test
     */
    public function returnsNullOnTimeout(): void
    {
        $start = microtime(true);
        self::assertNull($this->complete($this->createClient(timeout: 0.5), '__slow__'));
        self::assertLessThan(1.5, microtime(true) - $start);
    }

    /**
     * @test
     */
    public function stripsThinkBlocksOfReasoningModels(): void
    {
        self::assertSame('gedacht', $this->complete($this->createClient(), '__think__')['text']);
    }

    /**
     * @test
     */
    public function returnsNullWithoutRequestWhenDisabled(): void
    {
        $client = new LlmClient(new QueryUnderstandingConfiguration(baseUrl: ''));

        self::assertNull($this->complete($client, 'Gedichte von Rilke'));
        self::assertSame([], self::$mockLlm->getLastRequest());
    }

    /**
     * @test
     */
    public function returnsNullWhenServerIsUnreachable(): void
    {
        $client = new LlmClient(new QueryUnderstandingConfiguration(baseUrl: 'http://127.0.0.1:1/v1', timeout: 1.0));

        self::assertNull($this->complete($client, 'Gedichte von Rilke'));
    }

    /**
     * @test
     */
    public function decodeJsonContentAcceptsMarkdownCodeBlocks(): void
    {
        self::assertSame(['text' => 'a'], LlmClient::decodeJsonContent("```json\n{\"text\": \"a\"}\n```"));
        self::assertNull(LlmClient::decodeJsonContent('[1, 2]'));
    }
}
