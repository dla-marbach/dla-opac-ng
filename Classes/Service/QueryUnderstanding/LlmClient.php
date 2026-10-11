<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Minimaler Client für eine OpenAI-kompatible Chat-Completions-API (Ollama, vLLM, llama.cpp server).
 *
 * Die Antwort wird über "response_format" auf ein JSON-Schema festgelegt (structured outputs).
 * Bei Zeitüberschreitung, Verbindungs- oder Formatfehlern liefert der Client null, damit der
 * Aufrufer auf die normale Suche zurückfallen kann.
 */
class LlmClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const MAX_TOKENS = 500;

    public function __construct(
        private readonly QueryUnderstandingConfiguration $configuration
    ) {
        $this->logger = new NullLogger();
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

        $payload = [
            'model' => $this->configuration->model,
            'messages' => $messages,
            'temperature' => 0,
            'max_tokens' => self::MAX_TOKENS,
            'stream' => false,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => $schemaName,
                    'strict' => true,
                    'schema' => $jsonSchema,
                ],
            ],
        ];

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($this->configuration->apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->configuration->apiKey;
        }

        $curl = curl_init($this->configuration->baseUrl . '/chat/completions');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT_MS => (int)min(2000, $this->configuration->timeout * 1000),
            CURLOPT_TIMEOUT_MS => (int)($this->configuration->timeout * 1000),
        ]);
        $start = microtime(true);
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        $duration = round(microtime(true) - $start, 3);

        if (!is_string($body) || $status !== 200) {
            $this->logger->warning('LLM-Anfrage fehlgeschlagen', [
                'status' => $status,
                'error' => $error,
                'response' => is_string($body) ? mb_substr($body, 0, 300) : null,
                'duration' => $duration,
            ]);
            return null;
        }

        $response = json_decode($body, true);
        $content = $response['choices'][0]['message']['content'] ?? null;
        if (!is_string($content)) {
            $this->logger->warning('LLM-Antwort ohne Inhalt', ['duration' => $duration]);
            return null;
        }

        $result = self::decodeJsonContent($content);
        if ($result === null) {
            $this->logger->warning('LLM-Antwort ist kein JSON-Objekt', ['content' => mb_substr($content, 0, 500), 'duration' => $duration]);
            return null;
        }

        $this->logger->info('LLM-Anfrage erfolgreich', [
            'duration' => $duration,
            'model' => $this->configuration->model,
            'usage' => $response['usage'] ?? null,
        ]);

        return $result;
    }

    /**
     * Dekodiert die Modellantwort. Entfernt vorsorglich "<think>"-Blöcke von Reasoning-Modellen
     * und Markdown-Codeblöcke, falls ein Server das JSON-Schema nicht erzwingt.
     */
    public static function decodeJsonContent(string $content): ?array
    {
        $content = (string)preg_replace('#<think>.*?</think>#s', '', $content);
        $content = trim($content);
        if (preg_match('#^```(?:json)?\s*(.*?)\s*```$#s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) && !array_is_list($decoded) ? $decoded : null;
    }
}
