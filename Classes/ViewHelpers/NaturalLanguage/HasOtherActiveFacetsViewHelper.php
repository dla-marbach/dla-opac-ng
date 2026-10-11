<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\ViewHelpers\NaturalLanguage;

use Dla\DlaOpacNg\Service\QueryUnderstanding\InterpretationParts;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Liefert true, wenn es aktive Facetten gibt, die nicht aus der Frage der natürlichsprachigen Suche stammen.
 * Ohne natürlichsprachige Suche entspricht das einer Prüfung auf {arguments.facet}.
 */
class HasOtherActiveFacetsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('arguments', 'array', 'Such-Argumente (tx_find_find)', false, []);
    }

    public function render(): bool
    {
        return InterpretationParts::hasOtherActiveFacets((array)($this->arguments['arguments'] ?? []));
    }
}
