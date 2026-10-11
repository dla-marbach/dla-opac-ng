<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Ergebnis der Abbildung einer QueryStructure auf Suchparameter des Katalogs.
 *
 * Besteht aus festen Filtern (aufgelöste Facettenwerte), Textbestandteilen für das Standardsuchfeld
 * und einem optionalen Zeitraum. Weiche Bestandteile (nicht aufgelöste Werte, Themen, Zeitraum) können
 * nacheinander entfernt werden, wenn die Suche sonst keine Treffer liefert.
 */
final class QueryInterpretation
{
    public const PART_TEXT = 'text';

    public const PART_TOPIC = 'topic';

    public const PART_UNRESOLVED = 'unresolved';

    /**
     * Reihenfolge, in der weiche Bestandteile bei null Treffern entfernt werden.
     */
    public const RELAXATION_ORDER = [self::PART_UNRESOLVED, self::PART_TOPIC, 'date'];

    /**
     * @param list<array{facet: string, term: string, filterQuery: string}> $facets
     * @param list<array{kind: string, query: string, label: string}> $textParts
     * @param array{from: int, to: int}|null $dateRange
     * @param list<string> $dropped Bezeichnungen der entfernten Bestandteile
     */
    public function __construct(
        public readonly array $facets = [],
        public readonly array $textParts = [],
        public readonly ?array $dateRange = null,
        public readonly array $dropped = [],
    ) {
    }

    public function getQueryString(): string
    {
        return implode(' ', array_column($this->textParts, 'query'));
    }

    /**
     * Filter-Queries für eine Zählabfrage (entsprechen den Facettenfiltern des Such-Plugins).
     *
     * @return list<string>
     */
    public function getFilterQueries(): array
    {
        $filterQueries = array_column($this->facets, 'filterQuery');
        if ($this->dateRange !== null) {
            $filterQueries[] = sprintf('filterDateRange_mv:[%d TO %d]', $this->dateRange['from'], $this->dateRange['to']);
        }

        return $filterQueries;
    }

    public function hasPart(string $kind): bool
    {
        if ($kind === 'date') {
            return $this->dateRange !== null;
        }

        return in_array($kind, array_column($this->textParts, 'kind'), true);
    }

    /**
     * Neue Interpretation ohne die Bestandteile der angegebenen Art.
     */
    public function without(string $kind): self
    {
        if ($kind === 'date') {
            if ($this->dateRange === null) {
                return $this;
            }
            $label = $this->dateRange['from'] === $this->dateRange['to']
                ? (string)$this->dateRange['from']
                : $this->dateRange['from'] . '–' . $this->dateRange['to'];

            return new self($this->facets, $this->textParts, null, [...$this->dropped, $label]);
        }

        $kept = [];
        $dropped = $this->dropped;
        foreach ($this->textParts as $part) {
            if ($part['kind'] === $kind) {
                $dropped[] = $part['label'];
            } else {
                $kept[] = $part;
            }
        }

        return new self($this->facets, $kept, $this->dateRange, $dropped);
    }

    /**
     * Argumente für das Such-Plugin (tx_find_find): Standardsuchfeld und Facetten.
     *
     * @return array{q?: array{default: string}, facet?: array<string, array<string, string>>}
     */
    public function toFindArguments(): array
    {
        $arguments = [];

        $queryString = $this->getQueryString();
        if ($queryString !== '') {
            $arguments['q']['default'] = $queryString;
        }

        foreach ($this->facets as $facet) {
            $arguments['facet'][$facet['facet']][$facet['term']] = '1';
        }

        if ($this->dateRange !== null) {
            $arguments['facet']['Zeit'][sprintf('RANGE %d TO %d', $this->dateRange['from'], $this->dateRange['to'])] = '1';
        }

        return $arguments;
    }
}
