<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\ViewHelpers\NaturalLanguage;

use Dla\DlaOpacNg\Service\QueryUnderstanding\InterpretationParts;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Noch aktive Bestandteile der Interpretationszeile der natürlichsprachigen Suche.
 *
 * Beispiel: <f:for each="{dla:naturalLanguage.parts(arguments: arguments)}" as="part">…</f:for>
 */
class PartsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('arguments', 'array', 'Such-Argumente (tx_find_find)', false, []);
    }

    public function render(): array
    {
        return InterpretationParts::fromArguments((array)($this->arguments['arguments'] ?? []));
    }
}
