<?php

namespace Dla\DlaOpacNg\Tests\Support;

use Dla\DlaOpacNg\Service\QueryUnderstanding\VocabularyResolver;

/**
 * VocabularyResolver mit fest vorgegebenen Normdaten, Orten und Themen (ohne Solr).
 */
class FakeVocabularyResolver extends VocabularyResolver
{
    /**
     * @param array<string, array{id: string, label: string}> $persons Name => Normdatensatz
     * @param array<string, string> $places Begriff => Facettenwert (filterLocation_mv)
     * @param array<string, string> $subjects Begriff => exakter Facettenwert (filterSubject_mv)
     */
    public function __construct(
        private readonly array $persons = [],
        private readonly array $places = [],
        private readonly array $subjects = [],
    ) {
    }

    public function resolvePerson(string $name): ?array
    {
        return $this->persons[$name] ?? null;
    }

    public function resolveFacetValue(string $field, string $value, bool $exactOnly = false): ?string
    {
        return ($field === 'filterSubject_mv' ? $this->subjects : $this->places)[$value] ?? null;
    }
}
