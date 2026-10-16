<?php

namespace Dla\DlaOpacNg\Service;

/**
 * Entscheidet, ob für einen Bibliotheksdatensatz ohne Exemplare (insbesondere Beiträge) der Bestellbutton angezeigt wird.
 * Die Felder sind Solr-Dokumentfelder (Mehrfachfelder als Array).
 */
class OrderabilityResolver
{
    private const ARTICLE = 'Einzelbestandteil / unselbständiges Werk';

    private const NOT_IN_HOLDINGS = ['nicht im bestand', 'kein bestand'];

    private const NON_PHYSICAL_SOURCES = ['Digitale Nachschlagewerke', 'Elektronische Zeitschriftenbibliothek'];

    private const ELECTRONIC_MEDIA = ['Online-Ressource', 'Elektronisches Dokument', 'E-Book', 'Datenbank'];

    private const CONTINUING_MEDIA = ['Zeitschrift', 'Zeitung', 'Reihe'];

    private const VIEWER_URL = 'https://digital.dla-marbach.de/viewer';

    /**
     * @param array<string,mixed> $fields
     * @param list<array<string,mixed>> $parents Felder der Elternsätze (parent_id_mv)
     */
    public function isOrderable(array $fields, array $parents): bool
    {
        if ($this->isNotOrderableItself($fields)) {
            return false;
        }

        if ($parents === []) {
            return true;
        }

        foreach ($parents as $parent) {
            if ($this->isElectronic($parent)) {
                return false;
            }
        }

        // Retro-Beiträge: Heftbestellung läuft über die Zeitschrift/Zeitung/Reihe, die selbst nicht ausleihbar ist.
        foreach ($parents as $parent) {
            if (array_intersect(self::CONTINUING_MEDIA, self::values($parent, 'filterMedium_mv')) !== []) {
                return true;
            }
        }

        foreach ($parents as $parent) {
            if ($this->hasOrderableItem($parent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,mixed> $fields
     */
    private function isNotOrderableItself(array $fields): bool
    {
        // Die Signatur kommt auch als "Kein Bestand", "[kein Bestand]" oder "HNicht im Bestand des DLA" vor.
        $notInHoldings = false;
        foreach (self::values($fields, 'callNumberBibliographic_mv') as $callNumber) {
            foreach (self::NOT_IN_HOLDINGS as $marker) {
                $notInHoldings = $notInHoldings || str_contains(mb_strtolower($callNumber), $marker);
            }
        }

        // Der Hinweistext im Tab muss zum Button passen.
        $note = mb_strtolower(self::values($fields, 'usageRestrictionNote')[0] ?? '');

        return self::values($fields, 'categorySub') !== [self::ARTICLE]
            || self::values($fields, 'category') === ['Zeitschriftenheft']
            || $notInHoldings
            || str_starts_with($note, 'nicht benutzbar')
            || $this->isElectronic($fields);
    }

    /**
     * @param array<string,mixed> $fields
     */
    private function isElectronic(array $fields): bool
    {
        // Wie bei Exemplaren sperrt nur ein Link auf den Viewer, nicht jeder Link zum Digitalen Objekt.
        $viewerLink = false;
        foreach (self::values($fields, 'digitalObject_hyperlink_mv') as $hyperlink) {
            $viewerLink = $viewerLink || str_contains($hyperlink, self::VIEWER_URL);
        }

        return $viewerLink
            || array_intersect(self::NON_PHYSICAL_SOURCES, self::values($fields, 'filterSource')) !== []
            || (self::values($fields, 'filterType_mv')[0] ?? null) === 'Daten'
            || array_intersect(self::ELECTRONIC_MEDIA, self::values($fields, 'filterMedium_mv')) !== [];
    }

    /**
     * Wie Library/Item.html: ein Exemplar ist nicht bestellbar bei "nicht benutzbar", "nicht im Bestand des DLA" oder Viewer-Link.
     *
     * @param array<string,mixed> $fields
     */
    private function hasOrderableItem(array $fields): bool
    {
        $restrictions = (array)($fields['item_usageRestriction_mv'] ?? []);
        $locations = (array)($fields['item_location_mv'] ?? []);
        $hyperlinks = (array)($fields['item_digitalObject_hyperlink_mv'] ?? []);

        foreach (array_keys((array)($fields['item_id_mv'] ?? [])) as $index) {
            if (($restrictions[$index] ?? null) === 'nicht benutzbar'
                || mb_strtolower((string)($locations[$index] ?? '')) === 'nicht im bestand des dla'
                || str_contains((string)($hyperlinks[$index] ?? ''), self::VIEWER_URL)
            ) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Feldwerte als Liste, ohne leere Werte und den Solr-Platzhalter "␣".
     *
     * @param array<string,mixed> $fields
     * @return list<string>
     */
    private static function values(array $fields, string $key): array
    {
        $values = [];
        foreach ((array)($fields[$key] ?? []) as $value) {
            $value = trim((string)$value);
            if ($value !== '' && $value !== '␣') {
                $values[] = $value;
            }
        }

        return $values;
    }
}
