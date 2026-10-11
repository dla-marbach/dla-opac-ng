<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Prompt und JSON-Schema für die Zerlegung einer Suchanfrage.
 *
 * Systemprompt und Beispiele liegen als bearbeitbare Dateien in Resources/Private/QueryUnderstanding/
 * (system-prompt.md, examples.json). Die Beispiele werden als Frage/Antwort-Paare in den Dialog gelegt
 * (few-shot), das JSON-Schema erzwingt über "response_format" eine gültige Antwortstruktur.
 *
 * Für Medientyp, Form/Inhalt und Sprache werden die tatsächlichen Werte des Index (FacetVocabulary) als
 * enum ins Schema und als Liste in den Systemprompt gesetzt. Das Vokabular wird als Array übergeben
 * (Feldname => Werte); fehlt ein Feld (null), sind für Form/Inhalt und Sprache freie Texte erlaubt.
 */
final class QueryPrompt
{
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
    public function buildMessages(string $question, ?array $vocabulary = null): array
    {
        $messages = [['role' => 'system', 'content' => $this->getSystemPrompt($vocabulary)]];

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
     * Prüfsumme über Prompt, Beispiele und Vokabular, damit sich zwischengespeicherte Ergebnisse
     * bei jeder Änderung automatisch erneuern.
     */
    public function getVersion(?array $vocabulary = null): string
    {
        return substr(sha1(
            $this->getSystemPrompt($vocabulary) . json_encode($this->getExamples()) . json_encode(self::getJsonSchema($vocabulary))
        ), 0, 12);
    }

    /**
     * Systemprompt aus system-prompt.md, ergänzt um die erlaubten Werte aus dem Index.
     */
    public function getSystemPrompt(?array $vocabulary = null): string
    {
        $prompt = trim((string)@file_get_contents($this->resourcePath . '/system-prompt.md'));
        if ($vocabulary === null) {
            return $prompt;
        }

        $lines = ['', 'Erlaubte Werte (aus dem Katalog; andere Werte sind nicht möglich):'];
        foreach (array_keys(FacetVocabulary::FIELDS) as $field) {
            $values = self::vocabularyValues($vocabulary, $field);
            $lines[] = '- "' . $field . '": ' . ($values === [] ? '(keine, Feld leer lassen)' : implode(', ', array_map(
                static fn(string $value): string => '"' . $value . '"',
                $values
            )));
        }

        return $prompt . "\n" . implode("\n", $lines);
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
    public static function getJsonSchema(?array $vocabulary = null): array
    {
        $stringList = static fn(int $maxItems): array => [
            'type' => 'array',
            'maxItems' => $maxItems,
            'items' => ['type' => 'string'],
        ];
        // Liste mit erlaubten Werten; ohne Werte (Solr nicht erreichbar) bleibt das Feld leer.
        $enumList = static fn(int $maxItems, array $values): array => $values === []
            ? ['type' => 'array', 'maxItems' => 0, 'items' => ['type' => 'string']]
            : ['type' => 'array', 'maxItems' => $maxItems, 'items' => ['type' => 'string', 'enum' => $values]];
        $vocabularyList = static fn(string $field, int $maxItems): array => $vocabulary === null && $field !== 'mediaTypes'
            ? $stringList($maxItems)
            : $enumList($maxItems, self::vocabularyValues($vocabulary, $field));

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
                'mediaTypes' => $vocabularyList('mediaTypes', 3),
                'formContent' => $vocabularyList('formContent', 3),
                'languages' => $vocabularyList('languages', 3),
                'places' => $stringList(3),
                'dateFrom' => ['type' => ['integer', 'null']],
                'dateTo' => ['type' => ['integer', 'null']],
                'digitalOnly' => ['type' => 'boolean'],
            ],
        ];
    }

    /**
     * Erlaubte Werte eines Feldes; für die Medientypen mit Rückfall auf die bekannten Werte.
     *
     * @return list<string>
     */
    public static function vocabularyValues(?array $vocabulary, string $field): array
    {
        $values = $vocabulary[$field] ?? null;
        if (!is_array($values) || ($field === 'mediaTypes' && $values === [])) {
            return $field === 'mediaTypes' ? FacetVocabulary::FALLBACK_MEDIA_TYPES : [];
        }

        return array_values(array_map('strval', $values));
    }
}
