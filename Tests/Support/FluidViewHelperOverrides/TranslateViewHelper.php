<?php

namespace Dla\DlaOpacNg\Tests\Support\FluidViewHelperOverrides;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Test-only Ersatz für TYPO3\CMS\Fluid\ViewHelpers\TranslateViewHelper (final, daher nicht erweiterbar).
 *
 * Verzichtet bewusst auf die Lokalisierung des Core (LocalizationUtility/LanguageServiceFactory brauchen
 * den TYPO3-DI-Container, der im Unit-Test-Bootstrap nicht existiert, und sind in jeder TYPO3-Version
 * anders aufgebaut) und liest stattdessen die Texte der Default-Sprache direkt aus den XLIFF-Dateien
 * dieser Extension. Unterstützt nur die tatsächlich in den Partials dieser Extension verwendeten
 * Argumente (key, default, extensionName, arguments).
 */
class TranslateViewHelper extends AbstractViewHelper
{
    protected $escapeChildren = false;
    protected $escapeOutput = false;

    /** @var array<string, array<string, string>> Texte der Default-Sprache je XLIFF-Datei */
    private static array $labels = [];

    public function initializeArguments(): void
    {
        $this->registerArgument('key', 'string', 'Translation Key');
        $this->registerArgument('default', 'string', 'Default value if translation is missing');
        $this->registerArgument('extensionName', 'string', 'UpperCamelCased extension key');
        $this->registerArgument('arguments', 'array', 'Arguments to be replaced in the resulting string');
    }

    public function render(): string
    {
        $key = (string)($this->arguments['key'] ?? '');
        $default = (string)($this->arguments['default'] ?? $this->renderChildren() ?? '');
        $translateArguments = $this->arguments['arguments'];
        if ($translateArguments && !array_is_list($translateArguments)) {
            // TYPO3 v14 ersetzt %s-Platzhalter nur, wenn die Argumente eine bei 0 beginnende Liste sind.
            throw new \InvalidArgumentException('f:translate (' . $key . '): "arguments" muss eine bei 0 beginnende Liste sein.', 1794700000);
        }

        $value = self::translate($key) ?? $default;

        return $translateArguments ? vsprintf($value, $translateArguments) : $value;
    }

    /**
     * Schlüssel ohne Dateiangabe stehen in locallang.xlf, sonst gilt "LLL:EXT:dla_opac_ng/<Datei>:<Schlüssel>".
     * Wie im Core spielt die Dateiendung der Angabe keine Rolle (die Templates verweisen auf "locallang.xml").
     */
    private static function translate(string $key): ?string
    {
        $file = 'EXT:dla_opac_ng/Resources/Private/Language/locallang.xlf';
        if (str_starts_with($key, 'LLL:')) {
            $keyParts = explode(':', substr($key, 4));
            $key = (string)array_pop($keyParts);
            $file = implode(':', $keyParts);
        }
        if (!str_starts_with($file, 'EXT:dla_opac_ng/')) {
            return null;
        }
        $file = dirname(__DIR__, 3) . '/' . preg_replace('/\.[a-z0-9]+$/i', '', substr($file, 16)) . '.xlf';

        self::$labels[$file] ??= self::readLabels($file);

        return self::$labels[$file][$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private static function readLabels(string $file): array
    {
        $labels = [];
        $xliff = is_file($file) ? simplexml_load_file($file) : false;
        if ($xliff === false) {
            return $labels;
        }
        foreach ($xliff->file->body->children() as $element) {
            if ($element->getName() === 'trans-unit') {
                $labels[(string)$element['id']] = (string)$element->source;
            }
        }

        return $labels;
    }
}
