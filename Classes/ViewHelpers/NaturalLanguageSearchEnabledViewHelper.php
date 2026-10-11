<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\ViewHelpers;

use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Liefert true, wenn die natürlichsprachige Suche konfiguriert ist (Umgebungsvariable LLM_BASE_URL).
 *
 * Beispiel: <f:if condition="{dla:naturalLanguageSearchEnabled()}">…</f:if>
 */
class NaturalLanguageSearchEnabledViewHelper extends AbstractViewHelper
{
    public function render(): bool
    {
        return QueryUnderstandingConfiguration::fromEnvironment()->isEnabled();
    }
}
