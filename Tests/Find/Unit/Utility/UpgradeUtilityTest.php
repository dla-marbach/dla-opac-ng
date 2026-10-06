<?php

declare(strict_types=1);

namespace Dla\Find\Tests\Unit\Utility;

use PHPUnit\Framework\TestCase;
use Dla\Find\Utility\UpgradeUtility;

class UpgradeUtilityTest extends TestCase
{
    public static function settingsProvider(): array
    {
        return [
            [
                [
                    'host' => 'solr',
                    'port' => 8983,
                    'path' => '/solr/core_en',
                    'scheme' => 'http',
                ],
                [
                    'host' => 'solr',
                    'port' => 8983,
                    'path' => '/',
                    'scheme' => 'http',
                    'core' => 'core_en',
                ],
            ],
            [
                [
                    'host' => 'solr',
                    'port' => 8983,
                    'path' => '/solr/core_en/',
                    'scheme' => 'http',
                ],
                [
                    'host' => 'solr',
                    'port' => 8983,
                    'path' => '/',
                    'scheme' => 'http',
                    'core' => 'core_en',
                ],
            ],
            [
                [
                    'host' => 'solr.local',
                    'port' => 8983,
                    'path' => '/',
                    'scheme' => 'http',
                    'core' => 'core_en',
                ],
                [
                    'host' => 'solr.local',
                    'port' => 8983,
                    'path' => '/',
                    'scheme' => 'http',
                    'core' => 'core_en',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider settingsProvider
     */
    public function configurationIsAutomaticallyUpgraded(array $settings, $expected): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $result = UpgradeUtility::handleSolariumUpgrade($settings);
        } finally {
            restore_error_handler();
        }

        self::assertSame($expected, $result);
        self::assertCount(1, $deprecations);
    }
}
