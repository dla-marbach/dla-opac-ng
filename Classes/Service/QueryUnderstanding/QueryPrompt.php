<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Prompt und JSON-Schema für die Zerlegung einer Suchanfrage.
 *
 * Systemprompt und Beispiele liegen als bearbeitbare Dateien in Resources/Private/QueryUnderstanding/
 * (system-prompt.md, examples.json). Die Beispiele werden als Frage/Antwort-Paare in den Dialog gelegt
 * (few-shot), das JSON-Schema erzwingt über "response_format" eine gültige Antwortstruktur.
 */
final class QueryPrompt
{
    /**
     * Medientypen (Werte der Facette filterType_mv), die das Modell vergeben darf.
     */
    public const MEDIA_TYPES = [
        'Gedrucktes',
        'Handschriften',
        'Bilder und Objekte',
        'Audio',
        'Video',
        'Normdaten',
        'Daten',
    ];

    public const RELATIONS = ['', 'Von', 'An', 'Über', 'Unter'];

    private string $resourcePath;

    public function __construct(?string $resourcePath = null)
    {
        $this->resourcePath = rtrim($resourcePath ?? dirname(__DIR__, 3) . '/Resources/Private/QueryUnderstanding', '/');
    }

    /**
     * Nachrichten für die Chat-Completion: Systemprompt, Beispielpaare, Anfrage.
     *
     * @return list<array{role: string, content: string}>
     */
    public function buildMessages(string $question): array
    {
        $messages = [['role' => 'system', 'content' => $this->getSystemPrompt()]];

        foreach ($this->getExamples() as $example) {
            $messages[] = ['role' => 'user', 'content' => (string)$example['question']];
            $messages[] = [
                'role' => 'assistant',
                'content' => (string)json_encode($example['answer'], JSON_UNESCAPED_UNICODE),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        return $messages;
    }

    /**
     * Prüfsumme über Prompt und Beispiele, damit sich zwischengespeicherte Ergebnisse
     * bei jeder Änderung an diesen Dateien automatisch erneuern.
     */
    public function getVersion(): string
    {
        return substr(sha1($this->getSystemPrompt() . json_encode($this->getExamples()) . json_encode(self::getJsonSchema())), 0, 12);
    }

    public function getSystemPrompt(): string
    {
        return trim((string)@file_get_contents($this->resourcePath . '/system-prompt.md'));
    }

    /**
     * @return list<array{question: string, answer: array}>
     */
    public function getExamples(): array
    {
        $examples = json_decode((string)@file_get_contents($this->resourcePath . '/examples.json'), true);
        if (!is_array($examples)) {
            return [];
        }

        return array_values(array_filter(
            $examples,
            static fn($example): bool => is_array($example) && isset($example['question'], $example['answer']) && is_array($example['answer'])
        ));
    }

    /**
     * JSON-Schema der Antwort (kompatibel mit "strict" structured outputs: alle Felder required,
     * keine zusätzlichen Felder).
     */
    public static function getJsonSchema(): array
    {
        $stringList = static fn(int $maxItems): array => [
            'type' => 'array',
            'maxItems' => $maxItems,
            'items' => ['type' => 'string'],
        ];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'text',
                'persons',
                'topics',
                'mediaTypes',
                'formContent',
                'languages',
                'places',
                'dateFrom',
                'dateTo',
                'digitalOnly',
            ],
            'properties' => [
                'text' => ['type' => 'string'],
                'persons' => [
                    'type' => 'array',
                    'maxItems' => 3,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'relation'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'relation' => ['type' => 'string', 'enum' => self::RELATIONS],
                        ],
                    ],
                ],
                'topics' => [
                    'type' => 'array',
                    'maxItems' => 3,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['term', 'variants'],
                        'properties' => [
                            'term' => ['type' => 'string'],
                            'variants' => $stringList(4),
                        ],
                    ],
                ],
                'mediaTypes' => [
                    'type' => 'array',
                    'maxItems' => 3,
                    'items' => ['type' => 'string', 'enum' => self::MEDIA_TYPES],
                ],
                'formContent' => $stringList(3),
                'languages' => $stringList(3),
                'places' => $stringList(3),
                'dateFrom' => ['type' => ['integer', 'null']],
                'dateTo' => ['type' => ['integer', 'null']],
                'digitalOnly' => ['type' => 'boolean'],
            ],
        ];
    }
}
