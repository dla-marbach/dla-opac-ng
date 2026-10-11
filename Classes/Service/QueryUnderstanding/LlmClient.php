<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Minimaler Client für eine OpenAI-kompatible Chat-Completions-API (Ollama, vLLM, llama.cpp server).
 *
 * Nutzt die RequestFactory von TYPO3 (Guzzle, inkl. Proxy- und TLS-Einstellungen aus $GLOBALS['TYPO3_CONF_VARS']['HTTP']).
 * Die Antwort wird über "response_format" auf ein JSON-Schema festgelegt (structured outputs). Bei Zeitüberschreitung,
 * Verbindungs- oder Formatfehlern liefert der Client null, damit der Aufrufer auf die normale Suche zurückfallen kann.
 */
class LlmClient
{
    private const MAX_TOKENS = 500;

    public function __construct(
        private readonly QueryUnderstandingConfiguration $configuration,
        private readonly RequestFactory $requestFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     * @return array<string,mixed>|null dekodiertes JSON-Objekt der Modellantwort
     */
    public function completeJson(array $messages, array $jsonSchema, string $schemaName = 'result'): ?array
    {
        if (!$this->configuration->isEnabled()) {
            return null;
        }

        $headers = ['Accept' => 'application/json'];
        if ($this->configuration->apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->configuration->apiKey;
        }

        $start = microtime(true);
        try {
            $response = $this->requestFactory->request($this->configuration->baseUrl . '/chat/completions', 'POST', [
                'headers' => $headers,
                'json' => [
                    'model' => $this->configuration->model,
                    'messages' => $messages,
                    'temperature' => 0,
                    'max_tokens' => self::MAX_TOKENS,
                    'stream' => false,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => ['name' => $schemaName, 'strict' => true, 'schema' => $jsonSchema],
                    ],
                ],
                'timeout' => $this->configuration->timeout,
                'connect_timeout' => min(2.0, $this->configuration->timeout),
                'allow_redirects' => false,
            ]);
            $body = (string)$response->getBody();
        } catch (GuzzleException $exception) {
            $this->logger->warning('LLM-Anfrage fehlgeschlagen', [
                'error' => $exception->getMessage(),
                'duration' => round(microtime(true) - $start, 3),
            ]);
            return null;
        }

        $content = json_decode($body, true)['choices'][0]['message']['content'] ?? null;
        $result = is_string($content) ? self::decodeJsonContent($content) : null;
        if ($result === null) {
            $this->logger->warning('LLM-Antwort ist kein JSON-Objekt', ['response' => mb_substr($body, 0, 500)]);
            return null;
        }
        $this->logger->info('LLM-Anfrage erfolgreich', ['duration' => round(microtime(true) - $start, 3), 'model' => $this->configuration->model]);

        return $result;
    }

    /**
     * Dekodiert die Modellantwort. Entfernt vorsorglich "<think>"-Blöcke von Reasoning-Modellen
     * und Markdown-Codeblöcke, falls ein Server das JSON-Schema nicht erzwingt.
     */
    public static function decodeJsonContent(string $content): ?array
    {
        $content = trim((string)preg_replace('#<think>.*?</think>#s', '', $content));
        if (preg_match('#^```(?:json)?\s*(.*?)\s*```$#s', $content, $matches)) {
            $content = $matches[1];
        }
        $decoded = json_decode($content, true);

        return is_array($decoded) && !array_is_list($decoded) ? $decoded : null;
    }
}
