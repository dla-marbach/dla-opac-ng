<?php

namespace Dla\DlaOpacNg\Tests\Support;

use Dla\DlaOpacNg\Service\QueryUnderstanding\FacetVocabulary;

/**
 * FacetVocabulary mit fest vorgegebenen Werten (ohne Solr und Zwischenspeicher).
 */
class FakeFacetVocabulary extends FacetVocabulary
{
    public const DEFAULT_VALUES = [
        'mediaTypes' => ['Audio', 'Bilder und Objekte', 'Gedrucktes', 'Handschriften', 'Normdaten', 'Video'],
        'formContent' => ['Brief', 'Gedicht', 'Übersetzung'],
        'languages' => ['Deutsch', 'Englisch'],
    ];

    /**
     * @param array<string, list<string>> $values
     */
    public function __construct(private readonly array $values = self::DEFAULT_VALUES)
    {
    }

    public function getAll(): array
    {
        return $this->values;
    }
}
