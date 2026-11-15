<?php

namespace Dla\DlaOpacNg\Tests\Unit\Language;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * TYPO3 v14 erkennt eine Übersetzungsdatei am Attribut »target-language« des file-Elements; fehlt es,
 * liest v14 die englischen Quelltexte statt der Übersetzungen (bis v13 entscheidet allein der Dateiname).
 */
class LanguageFilesTest extends UnitTestCase
{
    public static function languageFileProvider(): array
    {
        $files = [];
        foreach (glob(dirname(__DIR__, 3) . '/Resources/Private/Language/*.xlf') ?: [] as $file) {
            $files[basename($file)] = [$file];
        }

        return $files;
    }

    #[Test]
    #[DataProvider('languageFileProvider')]
    public function targetLanguageMatchesFileName(string $file): void
    {
        $xliff = simplexml_load_file($file);
        self::assertNotFalse($xliff);

        $expected = preg_match('/^([a-z]{2})\./', basename($file), $matches) ? $matches[1] : null;
        $targetLanguage = isset($xliff->file['target-language']) ? (string)$xliff->file['target-language'] : null;

        self::assertSame($expected, $targetLanguage);
    }
}
