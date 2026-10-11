<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Konfiguration der natürlichsprachigen Suche (Zerlegung von Suchanfragen durch ein lokales LLM).
 *
 * Einzige Konfigurationsquelle sind wie bei der Solr-Verbindung Umgebungsvariablen:
 * - LLM_BASE_URL: Basis-URL einer OpenAI-kompatiblen API inkl. "/v1", z.B. "http://host.docker.internal:11434/v1"
 *   (Ollama, vLLM, llama.cpp). Ist die Variable leer, ist die natürlichsprachige Suche abgeschaltet.
 * - LLM_MODEL: Name des Modells, z.B. "qwen2.5:7b" (Standard)
 * - LLM_API_KEY: optional, wird als Bearer-Token mitgeschickt
 * - LLM_TIMEOUT: optional, Timeout in Sekunden für eine LLM-Anfrage (Standard: 15)
 * - LLM_MAX_CONCURRENT: optional, maximale Anzahl gleichzeitiger LLM-Anfragen je Webserver (Standard: 2)
 * - LLM_RATE_LIMIT: optional, maximale Anzahl LLM-Anfragen je Client-IP und Minute (Standard: 10, 0 = aus)
 */
final class QueryUnderstandingConfiguration
{
    public const DEFAULT_MODEL = 'qwen2.5:7b';

    public const DEFAULT_TIMEOUT = 15.0;

    public const DEFAULT_MAX_CONCURRENT = 2;

    public const DEFAULT_RATE_LIMIT = 10;

    public function __construct(
        public readonly string $baseUrl = '',
        public readonly string $model = self::DEFAULT_MODEL,
        public readonly string $apiKey = '',
        public readonly float $timeout = self::DEFAULT_TIMEOUT,
        public readonly int $maxConcurrent = self::DEFAULT_MAX_CONCURRENT,
        public readonly int $rateLimitPerMinute = self::DEFAULT_RATE_LIMIT,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $timeout = (float)getenv('LLM_TIMEOUT');
        $maxConcurrent = getenv('LLM_MAX_CONCURRENT');
        $rateLimit = getenv('LLM_RATE_LIMIT');

        return new self(
            rtrim(trim((string)getenv('LLM_BASE_URL')), '/'),
            trim((string)getenv('LLM_MODEL')) ?: self::DEFAULT_MODEL,
            trim((string)getenv('LLM_API_KEY')),
            $timeout > 0 ? $timeout : self::DEFAULT_TIMEOUT,
            $maxConcurrent !== false && trim($maxConcurrent) !== '' ? max(1, (int)$maxConcurrent) : self::DEFAULT_MAX_CONCURRENT,
            $rateLimit !== false && trim($rateLimit) !== '' ? max(0, (int)$rateLimit) : self::DEFAULT_RATE_LIMIT,
        );
    }

    public function isEnabled(): bool
    {
        return $this->baseUrl !== '' && $this->model !== '';
    }
}
