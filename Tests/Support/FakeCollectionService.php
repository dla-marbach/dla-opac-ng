<?php

namespace Dla\DlaOpacNg\Tests\Support;

use Dla\DlaOpacNg\Service\CollectionService;

/**
 * Ersatz für CollectionService (der sich im Konstruktor per mysqli mit der TYPO3-Datenbank verbindet),
 * damit Partials mit <dla:collection> ohne Datenbank gerendert werden können. Liefert die in
 * $parents hinterlegten Bestandsbäume in dem Format, das CollectionService::getAllParents() erzeugt.
 */
class FakeCollectionService extends CollectionService
{
    /** @var array<string, list<array{record_id: string, uid: string, displayTree: string, child?: array}>> */
    public static array $parents = [];

    public function __construct()
    {
    }

    public function getAllParents(string $nodeId)
    {
        return self::$parents[$nodeId] ?? [];
    }
}
