<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Konfiguration der natürlichsprachigen Suche (Zerlegung von Suchanfragen durch ein lokales LLM).
 *
 * Einzige Konfigurationsquelle sind wie bei der Solr-Verbindung Umgebungsvariablen:
 * - LLM_BASE_URL: Basis-URL einer OpenAI-kompatiblen API inkl. "/v1", z.B. "http://ollama:11434/v1"
 *   (Ollama, vLLM, llama.cpp). Ist die Variable leer, ist die natürlichsprachige Suche abgeschaltet.
 * - LLM_MODEL: Name des Modells, z.B. "qwen3:4b-instruct-2507-q4_K_M" (Standard; Qwen3-4B-Instruct-2507 ohne Thinking-Modus)
 * - LLM_API_KEY: optional, wird als Bearer-Token mitgeschickt
 * - LLM_TIMEOUT: optional, Timeout in Sekunden für eine LLM-Anfrage (Standard: 15)
 * - LLM_RATE_LIMIT: optional, maximale Anzahl LLM-Anfragen je Client-IP und Minute (Standard: 10, 0 = aus)
 *
 * Gleichzeitige Anfragen begrenzt der LLM-Server selbst (Ollama: OLLAMA_NUM_PARALLEL, OLLAMA_MAX_QUEUE).
 * Wird als Service über die Factory fromEnvironment() erzeugt (siehe Configuration/Services.yaml).
 */
final class QueryUnderstandingConfiguration
{
    public const DEFAULT_MODEL = 'qwen3:4b-instruct-2507-q4_K_M';

    public const DEFAULT_TIMEOUT = 15.0;

    public const DEFAULT_RATE_LIMIT = 10;

    public function __construct(
        public readonly string $baseUrl = '',
        public readonly string $model = self::DEFAULT_MODEL,
        public readonly string $apiKey = '',
        public readonly float $timeout = self::DEFAULT_TIMEOUT,
        public readonly int $rateLimitPerMinute = self::DEFAULT_RATE_LIMIT,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $timeout = (float)getenv('LLM_TIMEOUT');

        return new self(
            rtrim(trim((string)getenv('LLM_BASE_URL')), '/'),
            trim((string)getenv('LLM_MODEL')) ?: self::DEFAULT_MODEL,
            trim((string)getenv('LLM_API_KEY')),
            $timeout > 0 ? $timeout : self::DEFAULT_TIMEOUT,
            self::integerFromEnvironment('LLM_RATE_LIMIT') ?? self::DEFAULT_RATE_LIMIT,
        );
    }

    /**
     * Nicht-negative Ganzzahl aus einer Umgebungsvariablen; null, wenn nicht gesetzt oder ungültig
     * (z.B. "ten"), damit dann der sichere Standardwert gilt statt einer abgeschalteten Begrenzung.
     */
    private static function integerFromEnvironment(string $name): ?int
    {
        $value = trim((string)getenv($name));

        return ctype_digit($value) ? (int)$value : null;
    }

    public function isEnabled(): bool
    {
        return $this->baseUrl !== '' && $this->model !== '';
    }
}
