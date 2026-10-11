<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\ConcurrencyLimiter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConcurrencyLimiterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function limitsSlotsAndReleasesThem(): void
    {
        $directory = sys_get_temp_dir() . '/dla-limiter-test-' . bin2hex(random_bytes(4));
        $first = new ConcurrencyLimiter($directory, 1);
        $second = new ConcurrencyLimiter($directory, 1);

        self::assertTrue($first->acquire());
        self::assertFalse($second->acquire());
        $first->release();
        self::assertTrue($second->acquire());
        $second->release();

        array_map('unlink', glob($directory . '/*') ?: []);
        rmdir($directory);
    }

    /**
     * @test
     */
    public function refusesWhenLockDirectoryCannotBeCreated(): void
    {
        $file = (string)tempnam(sys_get_temp_dir(), 'dla-limiter-');

        // Unterhalb einer Datei lässt sich kein Verzeichnis anlegen
        self::assertFalse((new ConcurrencyLimiter($file . '/locks', 2))->acquire());

        unlink($file);
    }
}
