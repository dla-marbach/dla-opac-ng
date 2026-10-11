<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Ergebnis der Abbildung einer QueryStructure auf Suchparameter des Katalogs: Facetten (IDs aus
 * plugin.tx_find.settings.facets) und Bestandteile des Standardsuchfelds.
 */
final class QueryInterpretation
{
    public const PART_TEXT = 'text';

    public const PART_TOPIC = 'topic';

    public const PART_UNRESOLVED = 'unresolved';

    public const PART_EXCLUDED = 'excluded';

    /**
     * @param list<array{facet: string, term: string, not?: bool}> $facets not: Ausschluss (Status "not" des Such-Plugins)
     * @param list<array{kind: string, query: string, label: string}> $textParts
     */
    public function __construct(
        public readonly array $facets = [],
        public readonly array $textParts = [],
    ) {
    }

    public function getQueryString(): string
    {
        return implode(' ', array_column($this->textParts, 'query'));
    }

    /**
     * Argumente für das Such-Plugin (tx_find_find): Standardsuchfeld und Facetten.
     *
     * @return array{q?: array{default: string}, facet?: array<string, array<string, string>>}
     */
    public function toFindArguments(): array
    {
        $arguments = [];
        if ($this->getQueryString() !== '') {
            $arguments['q']['default'] = $this->getQueryString();
        }
        foreach ($this->facets as $facet) {
            $arguments['facet'][$facet['facet']][$facet['term']] = ($facet['not'] ?? false) ? 'not' : '1';
        }

        return $arguments;
    }

    /**
     * Für die Interpretationszeile (siehe InterpretationParts): welche Facetten und welche Teile des Suchfelds aus
     * der Frage stammen. Wird als tx_find_find[nlf] bzw. tx_find_find[nlt] in die URL geschrieben.
     *
     * @return array{nlf: list<array{f: string, t: string, n?: string}>, nlt: list<array{k: string, q: string, l: string}>}
     */
    public function toUrlParts(): array
    {
        return [
            'nlf' => array_map(
                static fn(array $facet): array => ['f' => $facet['facet'], 't' => $facet['term']] + (($facet['not'] ?? false) ? ['n' => '1'] : []),
                $this->facets
            ),
            'nlt' => array_map(static fn(array $part): array => ['k' => $part['kind'], 'q' => $part['query'], 'l' => $part['label']], $this->textParts),
        ];
    }
}
