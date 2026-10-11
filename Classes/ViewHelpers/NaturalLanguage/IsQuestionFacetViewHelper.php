<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\ViewHelpers\NaturalLanguage;

use Dla\DlaOpacNg\Service\QueryUnderstanding\InterpretationParts;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Liefert true, wenn eine aktive Facette aus der Frage der natürlichsprachigen Suche stammt. Solche Facetten
 * werden in der Interpretationszeile angezeigt und bei den aktiven Filtern ausgelassen.
 *
 * Beispiel: <f:if condition="!{dla:naturalLanguage.isQuestionFacet(arguments: arguments, facetId: 'Ort', term: 'Berlin')}">
 */
class IsQuestionFacetViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('arguments', 'array', 'Such-Argumente (tx_find_find)', false, []);
        $this->registerArgument('facetId', 'string', 'ID der Facette', true);
        $this->registerArgument('term', 'string', 'Wert der Facette', true);
    }

    public function render(): bool
    {
        return InterpretationParts::isQuestionFacet(
            (array)($this->arguments['arguments'] ?? []),
            (string)$this->arguments['facetId'],
            (string)$this->arguments['term']
        );
    }
}
