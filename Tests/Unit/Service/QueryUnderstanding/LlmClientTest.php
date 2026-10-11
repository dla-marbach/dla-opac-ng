<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\LlmClient;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryPrompt;
use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testet den LLM-Client mit Guzzles MockHandler statt einer echten OpenAI-kompatiblen API.
 */
class LlmClientTest extends UnitTestCase
{
    /** @var list<array{request: RequestInterface, options: array}> */
    private array $history = [];

    private function createClient(array $responses, string $apiKey = '', string $baseUrl = 'http://llm.test/v1'): LlmClient
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        $guzzleFactory = $this->createMock(GuzzleClientFactory::class);
        $guzzleFactory->method('getClient')->willReturn(new Client(['handler' => $stack]));

        return new LlmClient(
            new QueryUnderstandingConfiguration(baseUrl: $baseUrl, model: 'test-model', apiKey: $apiKey, timeout: 5.0),
            new RequestFactory($guzzleFactory),
            new NullLogger()
        );
    }

    private static function answer(string $content): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], (string)json_encode([
            'choices' => [['message' => ['role' => 'assistant', 'content' => $content]]],
        ]));
    }

    private static function complete(LlmClient $client): ?array
    {
        return $client->completeJson((new QueryPrompt())->buildMessages('Gedichte von Rilke'), QueryPrompt::getJsonSchema(), 'catalog_query');
    }

    /**
     * @test
     */
    public function sendsSchemaAndReturnsDecodedAnswer(): void
    {
        $result = self::complete($this->createClient([self::answer('{"text": "Rilke"}')], 'secret'));

        self::assertSame(['text' => 'Rilke'], $result);
        $request = $this->history[0]['request'];
        self::assertSame('http://llm.test/v1/chat/completions', (string)$request->getUri());
        self::assertSame('Bearer secret', $request->getHeaderLine('Authorization'));
        self::assertSame(5.0, $this->history[0]['options']['timeout']);
        $body = json_decode((string)$request->getBody(), true);
        self::assertSame('test-model', $body['model']);
        self::assertSame(0, $body['temperature']);
        self::assertSame(QueryPrompt::getJsonSchema(), $body['response_format']['json_schema']['schema']);
        self::assertFalse($body['response_format']['json_schema']['strict']);
        self::assertSame(['role' => 'user', 'content' => 'Gedichte von Rilke'], end($body['messages']));
    }

    /**
     * @test
     */
    public function returnsNullOnErrors(): void
    {
        self::assertNull(self::complete($this->createClient([new Response(500, [], 'model not found')])));
        self::assertNull(self::complete($this->createClient([new ConnectException('timeout', new \GuzzleHttp\Psr7\Request('POST', 'x'))])));
        self::assertNull(self::complete($this->createClient([self::answer('Das ist kein JSON')])));
        self::assertNull(self::complete($this->createClient([new Response(200, [], 'kaputt')])));
    }

    /**
     * @test
     */
    public function sendsNothingWhenDisabled(): void
    {
        self::assertNull(self::complete($this->createClient([], baseUrl: '')));
        self::assertSame([], $this->history);
    }

    /**
     * @test
     */
    public function decodeJsonContentToleratesThinkBlocksAndCodeFences(): void
    {
        self::assertSame(['text' => 'a'], LlmClient::decodeJsonContent("<think>…</think>\n{\"text\": \"a\"}"));
        self::assertSame(['text' => 'a'], LlmClient::decodeJsonContent("```json\n{\"text\": \"a\"}\n```"));
        self::assertNull(LlmClient::decodeJsonContent('[1, 2]'));
    }
}
