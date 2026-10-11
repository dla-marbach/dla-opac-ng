<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Bestandteile der Interpretationszeile („Verstanden als: …“) der natürlichsprachigen Suche.
 *
 * Maßgeblich ist allein der aktuelle Zustand der Suche (tx_find_find[facet] und tx_find_find[q][default]).
 * Die Middleware vermerkt in der URL nur, welche Facetten (nlf) und welche Teile des Suchfelds (nlt) aus der
 * Frage stammen. Angezeigt wird davon nur, was in der Suche noch aktiv ist: Entfernt man einen Filter in der
 * Interpretationszeile oder bei den aktiven Filtern, verschwindet er an beiden Stellen.
 *
 * Die Box „Aktive Filter“ bleibt unverändert und zeigt alle Filter wie bei einer normalen Suche, also auch die
 * aus der Frage; die Interpretationszeile ergänzt sie um die Rollen (von/an/über …) und die Suchbegriffe.
 */
final class InterpretationParts
{
    /**
     * Reihenfolge der Anzeige: Was wird gesucht, von/an/über wem, wo, wann, worüber.
     */
    private const ROLE_ORDER = [
        'mediaType',
        'formContent',
        'person',
        'relationVon',
        'relationAn',
        'relationUeber',
        'relationUnter',
        'place',
        'language',
        'date',
        'digital',
        'text',
        'topic',
        'unresolved',
        'excluded',
    ];

    private const FACET_ROLES = [
        'Medientypen' => 'mediaType',
        'FormUndInhalt' => 'formContent',
        'Personen' => 'person',
        'Ort' => 'place',
        'Sprache' => 'language',
        'Zeit' => 'date',
        'Digital' => 'digital',
        'Thema' => 'topic',
    ];

    private const RELATION_ROLES = [
        'Von' => 'relationVon',
        'An' => 'relationAn',
        'Über' => 'relationUeber',
        'Unter' => 'relationUnter',
    ];

    private const TEXT_ROLES = [
        QueryInterpretation::PART_TEXT => 'text',
        QueryInterpretation::PART_TOPIC => 'topic',
        QueryInterpretation::PART_UNRESOLVED => 'unresolved',
        QueryInterpretation::PART_EXCLUDED => 'excluded',
    ];

    /**
     * Noch aktive Bestandteile aus der Frage.
     *
     * Facetten: ['type' => 'facet', 'role', 'label', 'facetId', 'term']
     * Suchfeld: ['type' => 'text', 'role', 'label', 'remainingQuery'] (Suchfeld ohne diesen Teil)
     *
     * @param array $arguments Such-Argumente (tx_find_find)
     * @return list<array<string, string>>
     */
    public static function fromArguments(array $arguments): array
    {
        if (!is_string($arguments['nlq'] ?? null) || $arguments['nlq'] === '') {
            return [];
        }

        $parts = [];
        foreach (self::questionFacets($arguments) as [$facetId, $term, $not]) {
            if (!self::isActiveFacet($arguments, $facetId, $term, $not)) {
                continue;
            }
            [$role, $label] = self::facetRoleAndLabel($facetId, $term);
            $role = $not && $role !== null ? 'excluded' : $role;
            if ($role === null) {
                continue;
            }
            $parts[] = ['type' => 'facet', 'role' => $role, 'label' => $label, 'facetId' => $facetId, 'term' => $term];
        }

        $query = is_string($arguments['q']['default'] ?? null) ? $arguments['q']['default'] : '';
        foreach (is_array($arguments['nlt'] ?? null) ? $arguments['nlt'] : [] as $textPart) {
            $partQuery = is_array($textPart) && is_string($textPart['q'] ?? null) ? $textPart['q'] : '';
            $role = self::TEXT_ROLES[$textPart['k'] ?? ''] ?? null;
            if ($partQuery === '' || $role === null || !str_contains($query, $partQuery)) {
                continue;
            }
            $parts[] = [
                'type' => 'text',
                'role' => $role,
                'label' => is_string($textPart['l'] ?? null) && $textPart['l'] !== '' ? $textPart['l'] : $partQuery,
                'remainingQuery' => self::removeFromQuery($query, $partQuery),
            ];
        }

        usort($parts, static fn(array $a, array $b): int =>
            array_search($a['role'], self::ROLE_ORDER, true) <=> array_search($b['role'], self::ROLE_ORDER, true));

        return $parts;
    }

    /**
     * Suchfeld ohne den angegebenen Teil (erstes Vorkommen), Leerzeichen bereinigt.
     */
    public static function removeFromQuery(string $query, string $part): string
    {
        $position = strpos($query, $part);
        if ($position === false) {
            return $query;
        }
        $query = substr($query, 0, $position) . substr($query, $position + strlen($part));

        return trim((string)preg_replace('/\s+/u', ' ', $query));
    }

    /**
     * @return list<array{0: string, 1: string, 2: bool}> Facette, Wert, Ausschluss
     */
    private static function questionFacets(array $arguments): array
    {
        $facets = [];
        foreach (is_array($arguments['nlf'] ?? null) ? $arguments['nlf'] : [] as $facet) {
            if (is_array($facet) && is_string($facet['f'] ?? null) && is_string($facet['t'] ?? null)) {
                $facets[] = [$facet['f'], $facet['t'], ($facet['n'] ?? '') === '1'];
            }
        }

        return $facets;
    }

    /**
     * Aktiv mit dem Status aus der Frage: Ausschluss ("not") oder Filter. Wurde ein Filter aus der Frage später
     * umgekehrt (oder umgekehrt), gehört er nicht mehr zur Interpretation.
     */
    private static function isActiveFacet(array $arguments, string $facetId, string $term, bool $not): bool
    {
        $status = $arguments['facet'][$facetId][$term] ?? null;
        if ($status === null || (string)$status === '') {
            return false;
        }

        return ((string)$status === 'not') === $not;
    }

    /**
     * @return array{0: string|null, 1: string} Rolle (Schlüssel der Übersetzung nl.part.<Rolle>) und Bezeichnung
     */
    private static function facetRoleAndLabel(string $facetId, string $term): array
    {
        if ($facetId === 'filterAuthorityRelation_mv') {
            [$name, $relation] = array_pad(explode('␝', $term, 2), 2, '');
            return [self::RELATION_ROLES[$relation] ?? null, $name];
        }

        $role = self::FACET_ROLES[$facetId] ?? null;
        if ($role === 'date' && preg_match('/^RANGE (-?\d+) TO (-?\d+)$/', $term, $matches)) {
            return [$role, $matches[1] === $matches[2] ? $matches[1] : $matches[1] . '–' . $matches[2]];
        }

        return [$role, $term];
    }
}
