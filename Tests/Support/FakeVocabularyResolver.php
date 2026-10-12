<?php

namespace Dla\DlaOpacNg\Tests\Support;

use Dla\DlaOpacNg\Service\QueryUnderstanding\VocabularyResolver;

/**
 * VocabularyResolver mit fest vorgegebenen Normdaten und Orten (ohne Solr).
 */
class FakeVocabularyResolver extends VocabularyResolver
{
    /**
     * @param array<string, array{id: string, label: string}> $persons Name => Normdatensatz
     * @param array<string, string> $places Begriff => Facettenwert (filterLocation_mv)
     */
    public function __construct(
        private readonly array $persons = [],
        private readonly array $places = [],
    ) {
    }

    public function resolvePerson(string $name): ?array
    {
        return $this->persons[$name] ?? null;
    }

    public function resolveFacetValue(string $field, string $value): ?string
    {
        return $this->places[$value] ?? null;
    }
}
