<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Geprüfte, bereinigte Zerlegung einer Suchanfrage.
 *
 * Die Modellantwort wird nie direkt an Solr weitergegeben: Alle Werte werden hier gegen das Schema
 * geprüft, gekürzt und von Solr-Steuerzeichen befreit. Unbekannte Felder und Werte entfallen.
 */
final class QueryStructure
{
    private const MAX_STRING_LENGTH = 100;

    private const MAX_TEXT_LENGTH = 200;

    private const MIN_YEAR = 0;

    private const MAX_YEAR = 2100;

    /**
     * @param list<array{name: string, relation: string}> $persons
     * @param list<array{term: string, variants: list<string>}> $topics
     * @param list<string> $mediaTypes
     * @param list<string> $formContent
     * @param list<string> $languages
     * @param list<string> $places
     */
    public function __construct(
        public readonly string $text = '',
        public readonly array $persons = [],
        public readonly array $topics = [],
        public readonly array $mediaTypes = [],
        public readonly array $formContent = [],
        public readonly array $languages = [],
        public readonly array $places = [],
        public readonly ?int $dateFrom = null,
        public readonly ?int $dateTo = null,
        public readonly bool $digitalOnly = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $persons = [];
        foreach (self::listOf($data['persons'] ?? null, 3) as $person) {
            if (!is_array($person)) {
                continue;
            }
            $name = self::cleanTerm($person['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $relation = (string)($person['relation'] ?? '');
            $persons[] = [
                'name' => $name,
                'relation' => in_array($relation, QueryPrompt::RELATIONS, true) ? $relation : '',
            ];
        }

        $topics = [];
        foreach (self::listOf($data['topics'] ?? null, 3) as $topic) {
            if (!is_array($topic)) {
                continue;
            }
            $term = self::cleanTerm($topic['term'] ?? '', true);
            if ($term === '') {
                continue;
            }
            $variants = [];
            foreach (self::listOf($topic['variants'] ?? null, 4) as $variant) {
                $variant = self::cleanTerm($variant, true);
                if ($variant !== '' && mb_strtolower($variant) !== mb_strtolower($term)) {
                    $variants[] = $variant;
                }
            }
            $topics[] = ['term' => $term, 'variants' => array_values(array_unique($variants))];
        }

        $mediaTypes = array_values(array_intersect(
            QueryPrompt::MEDIA_TYPES,
            array_map('strval', array_filter(self::listOf($data['mediaTypes'] ?? null, 7), 'is_string'))
        ));

        $dateFrom = self::year($data['dateFrom'] ?? null);
        $dateTo = self::year($data['dateTo'] ?? null);
        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return new self(
            self::cleanText($data['text'] ?? ''),
            $persons,
            $topics,
            $mediaTypes,
            self::stringList($data['formContent'] ?? null),
            self::stringList($data['languages'] ?? null),
            self::stringList($data['places'] ?? null),
            $dateFrom,
            $dateTo,
            ($data['digitalOnly'] ?? false) === true,
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function isEmpty(): bool
    {
        return $this->text === '' && $this->persons === [] && $this->topics === [] && $this->mediaTypes === []
            && $this->formContent === [] && $this->languages === [] && $this->places === []
            && $this->dateFrom === null && $this->dateTo === null && !$this->digitalOnly;
    }

    /**
     * Entfernt Solr-Steuerzeichen; erlaubt optional einen Stern am Wortende (Trunkierung).
     */
    public static function cleanTerm(mixed $value, bool $allowTrailingWildcard = false): string
    {
        if (!is_string($value)) {
            return '';
        }

        $wildcard = $allowTrailingWildcard && str_ends_with(trim($value), '*');
        $value = (string)preg_replace('/[\\\\+\-&|!(){}\[\]^"~*?:\/<>=]+/u', ' ', $value);
        $value = (string)preg_replace('/\b(AND|OR|NOT)\b/', ' ', $value);
        $value = trim((string)preg_replace('/\s+/u', ' ', $value));
        $value = mb_substr($value, 0, self::MAX_STRING_LENGTH);

        // Trunkierung nur bei einzelnen Wörtern mit mindestens drei Zeichen zulassen.
        if ($wildcard && $value !== '' && !str_contains($value, ' ') && mb_strlen($value) >= 3) {
            $value .= '*';
        }

        return $value;
    }

    private static function cleanText(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $value = (string)preg_replace('/[\\\\+&|!(){}\[\]^"~*?:\/<>=]+/u', ' ', $value);
        // Boolesche Operatoren aus dem Modell nicht als Syntax durchreichen.
        $value = (string)preg_replace('/\b(AND|OR|NOT)\b/', ' ', $value);

        return trim(mb_substr(trim((string)preg_replace('/\s+/u', ' ', $value)), 0, self::MAX_TEXT_LENGTH));
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        $result = [];
        foreach (self::listOf($value, 3) as $item) {
            $item = self::cleanTerm($item);
            if ($item !== '' && !in_array($item, $result, true)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    private static function listOf(mixed $value, int $max): array
    {
        return is_array($value) ? array_slice(array_values($value), 0, $max) : [];
    }

    private static function year(mixed $value): ?int
    {
        if (!is_int($value) && !(is_string($value) && preg_match('/^-?\d{1,4}$/', $value))) {
            return null;
        }
        $year = (int)$value;

        return $year >= self::MIN_YEAR && $year <= self::MAX_YEAR ? $year : null;
    }
}
