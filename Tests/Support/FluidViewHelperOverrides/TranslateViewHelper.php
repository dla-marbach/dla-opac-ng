<?php

namespace Dla\DlaOpacNg\Tests\Support\FluidViewHelperOverrides;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Test-only Ersatz für TYPO3\CMS\Fluid\ViewHelpers\TranslateViewHelper (final, daher nicht erweiterbar).
 *
 * Verzichtet bewusst auf die request-/BE-User-basierte Locale-Ermittlung des Originals
 * (Locales::createLocaleFromRequest() braucht dafür eine vollständig über den TYPO3-DI-Container
 * gebaute LanguageServiceFactory, die im Unit-Test-Bootstrap nicht existiert) und übersetzt
 * stattdessen immer in die Default-Sprache. Unterstützt nur die tatsächlich in den Partials
 * dieser Extension verwendeten Argumente (key, default, extensionName, arguments).
 */
class TranslateViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeChildren = false;
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('key', 'string', 'Translation Key');
        $this->registerArgument('default', 'string', 'Default value if translation is missing');
        $this->registerArgument('extensionName', 'string', 'UpperCamelCased extension key');
        $this->registerArgument('arguments', 'array', 'Arguments to be replaced in the resulting string');
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $key = (string)($arguments['key'] ?? '');
        $default = (string)($arguments['default'] ?? $renderChildrenClosure() ?? '');
        $extensionName = $arguments['extensionName'] ?? 'DlaOpacNg';
        $translateArguments = $arguments['arguments'];

        $value = LocalizationUtility::translate($key, $extensionName, $translateArguments, 'default');

        if ($value === null) {
            return $translateArguments ? vsprintf($default, $translateArguments) : $default;
        }

        return $value;
    }
}
