<?php

namespace Dla\DlaOpacNg\Tests\Unit\Updates;

use Dla\DlaOpacNg\Updates\FindStaticTemplateUpdate;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FindStaticTemplateUpdateTest extends UnitTestCase
{
    public static function includeStaticFileProvider(): array
    {
        return [
            'find wird entfernt, wenn dla_opac_ng bereits eingebunden ist' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:find/Configuration/TypoScript,EXT:dla_opac_ng/Configuration/TypoScript',
                'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:dla_opac_ng/Configuration/TypoScript',
            ],
            'find wird an derselben Stelle durch dla_opac_ng ersetzt' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:find/Configuration/TypoScript,EXT:other/Configuration/TypoScript',
                'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:dla_opac_ng/Configuration/TypoScript,EXT:other/Configuration/TypoScript',
            ],
            'Schreibweise mit abschließendem Schrägstrich' => [
                'EXT:find/Configuration/TypoScript/,EXT:dla_opac_ng/Configuration/TypoScript/',
                'EXT:dla_opac_ng/Configuration/TypoScript/',
            ],
            'ohne find bleibt alles unverändert' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:dla_opac_ng/Configuration/TypoScript',
                'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:dla_opac_ng/Configuration/TypoScript',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider includeStaticFileProvider
     */
    public function includeStaticFileIsMigrated(string $input, string $expected): void
    {
        self::assertSame($expected, FindStaticTemplateUpdate::migrateIncludeStaticFile($input));
    }
}
