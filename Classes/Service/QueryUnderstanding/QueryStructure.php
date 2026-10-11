<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Geprüfte, bereinigte Zerlegung einer Suchanfrage.
 *
 * Die Modellantwort wird nie direkt an Solr weitergegeben: Alle Werte werden hier gegen das Schema und das
 * Vokabular des Index geprüft, gekürzt und von Solr-Steuerzeichen befreit. Unbekannte Felder und Werte entfallen.
 */
final class QueryStructure
{
    private const MAX_LENGTH = 100;

    private const MAX_YEAR = 2100;

    public const NO_EXCLUSIONS = ['mediaTypes' => [], 'formContent' => [], 'terms' => []];

    /**
     * Ausschlüsse übernehmen wir nur, wenn die Frage eine Verneinung enthält. Kleine Modelle übertragen sonst
     * gelegentlich Ausschlüsse aus den Beispielen, und ein falscher Ausschluss entfernt unbemerkt Treffer.
     */
    private const NEGATION = '/\b(ohne|kein\w*|nicht|au(ß|ss)er|ausgenommen|exklusive|abgesehen)\b/iu';

    /**
     * @param list<array{name: string, fullName: string, relation: string}> $persons
     * @param list<array{term: string, variants: list<string>}> $topics
     * @param list<string> $mediaTypes
     * @param list<string> $formContent
     * @param list<string> $languages
     * @param list<string> $places
     * @param array{mediaTypes: list<string>, formContent: list<string>, terms: list<string>} $exclude
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
        public readonly bool $knownItem = false,
        public readonly array $exclude = self::NO_EXCLUSIONS,
    ) {
    }

    /**
     * @param array<string, list<string>> $vocabulary erlaubte Werte für mediaTypes, formContent und languages
     * @param string|null $question Frage; ohne Verneinung darin werden Ausschlüsse verworfen
     */
    public static function fromArray(array $data, array $vocabulary, ?string $question = null): self
    {
        $persons = [];
        foreach (self::listOf($data['persons'] ?? null) as $person) {
            $name = self::clean($person['name'] ?? null);
            $relation = $person['relation'] ?? '';
            if ($name !== '') {
                $persons[] = [
                    'name' => $name,
                    'fullName' => self::clean($person['fullName'] ?? null),
                    'relation' => in_array($relation, QueryPrompt::RELATIONS, true) ? $relation : '',
                ];
            }
        }

        // Personen sind kein Thema: kleine Modelle wiederholen den Namen sonst gern als Thema ("Literatur über Hesse"),
        // was nur unnötig einschränkt. Verglichen wird mit Name, vollständigem Namen und deren einzelnen Wörtern.
        $personNames = [];
        foreach ($persons as $person) {
            foreach ([$person['name'], $person['fullName']] as $name) {
                $personNames[] = mb_strtolower($name);
                array_push($personNames, ...preg_split('/\s+/u', mb_strtolower($name), -1, PREG_SPLIT_NO_EMPTY) ?: []);
            }
        }

        $topics = [];
        foreach (self::listOf($data['topics'] ?? null) as $topic) {
            $term = self::clean($topic['term'] ?? null, true);
            if ($term === '' || in_array(mb_strtolower(rtrim($term, '*')), $personNames, true)) {
                continue;
            }
            $variants = array_map(static fn($variant): string => self::clean($variant, true), self::listOf($topic['variants'] ?? null, 4));
            $variants = array_filter($variants, static fn(string $variant): bool => $variant !== '' && mb_strtolower($variant) !== mb_strtolower($term));
            $topics[] = ['term' => $term, 'variants' => array_values(array_unique($variants))];
        }

        [$dateFrom, $dateTo] = [self::year($data['dateFrom'] ?? null), self::year($data['dateTo'] ?? null)];
        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $exclude = is_array($data['exclude'] ?? null) && ($question === null || preg_match(self::NEGATION, $question) === 1)
            ? $data['exclude']
            : [];

        return new self(
            self::clean($data['text'] ?? null),
            $persons,
            $topics,
            self::allowed($data['mediaTypes'] ?? null, QueryPrompt::vocabularyValues($vocabulary, 'mediaTypes')),
            self::allowed($data['formContent'] ?? null, QueryPrompt::vocabularyValues($vocabulary, 'formContent')),
            self::allowed($data['languages'] ?? null, QueryPrompt::vocabularyValues($vocabulary, 'languages')),
            self::cleanList($data['places'] ?? null),
            $dateFrom,
            $dateTo,
            ($data['digitalOnly'] ?? false) === true,
            ($data['knownItem'] ?? false) === true,
            [
                'mediaTypes' => self::allowed($exclude['mediaTypes'] ?? null, QueryPrompt::vocabularyValues($vocabulary, 'mediaTypes')),
                'formContent' => self::allowed($exclude['formContent'] ?? null, QueryPrompt::vocabularyValues($vocabulary, 'formContent')),
                'terms' => self::cleanList($exclude['terms'] ?? null, true),
            ],
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Nichts erkannt; die Suchabsicht allein ergibt keine Suche.
     */
    public function isEmpty(): bool
    {
        return $this == new self(knownItem: $this->knownItem);
    }

    /**
     * Entfernt Solr-Steuerzeichen und boolesche Operatoren; erlaubt optional einen Stern am Ende eines einzelnen
     * Wortes mit mindestens drei Zeichen (Trunkierung).
     */
    public static function clean(mixed $value, bool $allowTrailingWildcard = false): string
    {
        if (!is_string($value)) {
            return '';
        }
        $wildcard = $allowTrailingWildcard && str_ends_with(trim($value), '*');
        $value = (string)preg_replace(['/[\\\\+&|!(){}\[\]^"~*?:\/<>=]+/u', '/\b(AND|OR|NOT)\b/'], ' ', $value);
        $value = mb_substr(trim((string)preg_replace('/\s+/u', ' ', $value)), 0, self::MAX_LENGTH);

        return $wildcard && !str_contains($value, ' ') && mb_strlen($value) >= 3 ? $value . '*' : $value;
    }

    /**
     * Nur Werte aus der Liste der erlaubten Werte, in der Reihenfolge der Modellantwort.
     *
     * @param list<string> $allowed
     * @return list<string>
     */
    private static function allowed(mixed $values, array $allowed): array
    {
        return array_values(array_unique(array_filter(
            self::listOf($values),
            static fn($value): bool => in_array($value, $allowed, true)
        )));
    }

    /**
     * @return list<string>
     */
    private static function cleanList(mixed $values, bool $allowTrailingWildcard = false): array
    {
        $values = array_map(static fn($value): string => self::clean($value, $allowTrailingWildcard), self::listOf($values));

        return array_values(array_unique(array_filter($values)));
    }

    private static function listOf(mixed $value, int $max = 3): array
    {
        return is_array($value) ? array_slice(array_values($value), 0, $max) : [];
    }

    private static function year(mixed $value): ?int
    {
        return is_int($value) && $value >= 0 && $value <= self::MAX_YEAR ? $value : null;
    }
}
