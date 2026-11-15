<?php

namespace Dla\DlaOpacNg\Tests\Unit\Updates;

use Dla\DlaOpacNg\Updates\PluginListTypeToCTypeUpdate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PluginListTypeToCTypeUpdateTest extends UnitTestCase
{
    public static function explicitAllowDenyProvider(): array
    {
        return [
            'Plugins der Extension werden umgestellt' => [
                'tt_content:CType:text,tt_content:list_type:find_find,tt_content:list_type:dlaopacng_dlastart',
                'tt_content:CType:text,tt_content:CType:find_find,tt_content:CType:dlaopacng_dlastart',
            ],
            'fremde Plugins bleiben unverändert' => [
                'tt_content:list_type:news_pi1,tt_content:list_type:dlaopacng_dlacollection',
                'tt_content:list_type:news_pi1,tt_content:CType:dlaopacng_dlacollection',
            ],
            'bereits vorhandene CType-Berechtigung wird nicht verdoppelt' => [
                'tt_content:CType:find_find,tt_content:list_type:find_find',
                'tt_content:CType:find_find',
            ],
            'ohne Plugins der Extension bleibt alles unverändert' => [
                'tt_content:CType:text,tt_content:list_type:news_pi1',
                'tt_content:CType:text,tt_content:list_type:news_pi1',
            ],
            'leere Berechtigungen' => ['', ''],
        ];
    }

    #[Test]
    #[DataProvider('explicitAllowDenyProvider')]
    public function explicitAllowDenyIsMigrated(string $input, string $expected): void
    {
        self::assertSame($expected, PluginListTypeToCTypeUpdate::migrateExplicitAllowDeny($input));
    }

    /**
     * Die Plugin-Signaturen müssen zu den in ext_localconf.php konfigurierten Plugins passen,
     * sonst bleiben Inhaltselemente beim Upgrade auf TYPO3 v14 zurück.
     */
    #[Test]
    public function allConfiguredPluginsAreMigrated(): void
    {
        $extLocalconf = (string)file_get_contents(dirname(__DIR__, 3) . '/ext_localconf.php');
        preg_match_all('/configurePlugin\(\s*\'(\w+)\',\s*\'(\w+)\'/', $extLocalconf, $matches, PREG_SET_ORDER);
        $configured = array_map(
            static fn(array $match): string => strtolower($match[1] . '_' . $match[2]),
            $matches
        );

        self::assertNotSame([], $configured);
        self::assertEqualsCanonicalizing($configured, PluginListTypeToCTypeUpdate::PLUGIN_SIGNATURES);
    }
}
