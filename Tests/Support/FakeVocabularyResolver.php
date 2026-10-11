<?php

namespace Dla\DlaOpacNg\Tests\Support;

use Dla\DlaOpacNg\Service\QueryUnderstanding\VocabularyResolver;

/**
 * VocabularyResolver mit fest vorgegebenen Normdaten, Facettenwerten und Trefferzahlen (ohne Solr).
 */
class FakeVocabularyResolver extends VocabularyResolver
{
    /** @var list<array{query: string, filterQueries: list<string>}> */
    public array $countRequests = [];

    /**
     * @param array<string, array{id: string, label: string}> $persons Name => Normdatensatz
     * @param array<string, list<string>> $facetValues Feld => vorhandene Werte
     * @param \Closure(string, list<string>): ?int|null $counter Trefferzahl je Anfrage (Standard: 1)
     */
    public function __construct(
        private readonly array $persons = [],
        private readonly array $facetValues = [],
        private readonly ?\Closure $counter = null,
    ) {
    }

    public function resolvePerson(string $name): ?array
    {
        return $this->persons[$name] ?? null;
    }

    public function resolveFacetValue(string $field, string $value): ?string
    {
        foreach ($this->facetValues[$field] ?? [] as $existing) {
            if (mb_strtolower($existing) === mb_strtolower($value)) {
                return $existing;
            }
        }

        return null;
    }

    public function count(string $query, array $filterQueries = []): ?int
    {
        $this->countRequests[] = ['query' => $query, 'filterQueries' => $filterQueries];

        return $this->counter !== null ? ($this->counter)($query, $filterQueries) : 1;
    }
}
