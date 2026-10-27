<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Tests\Unit\Statistics;

use Dla\DlaOpacNg\Statistics\MatomoPusher;
use Dla\DlaOpacNg\Statistics\MatomoRequestBuilder;
use Dla\DlaOpacNg\Statistics\RawLogWriter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MatomoPusherTest extends UnitTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/dla-opac-stats-push-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/raw/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->directory . '/raw');
        @rmdir($this->directory);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function onlyCompletedMinutesAreSentAndDeleted(): void
    {
        $now = new \DateTimeImmutable('2026-10-27 12:00:20 UTC');
        $writer = new RawLogWriter($this->directory);
        $writer->append(['a' => 'start'], new \DateTimeImmutable('2026-10-27 11:58:10 UTC'));
        $writer->append(['a' => 'start'], new \DateTimeImmutable('2026-10-27 11:58:50 UTC'));
        $writer->append(['a' => 'start'], new \DateTimeImmutable('2026-10-27 11:59:40 UTC'));
        $writer->append(['a' => 'start'], new \DateTimeImmutable('2026-10-27 12:00:10 UTC'));

        $sent = [];
        $result = $this->createPusher(static function (array $requests) use (&$sent): int {
            $sent[] = $requests;
            return 0;
        })->push($now);

        self::assertSame(['files' => 1, 'records' => 2, 'requests' => 2, 'invalid' => 0], $result);
        self::assertCount(1, $sent);
        self::assertSame([1793102290, 1793102330], array_column($sent[0], 'cdt'));
        self::assertSame(['202610271159', '202610271200'], $this->listRawMinutes());
    }

    /**
     * @test
     */
    public function failedBatchIsResumedWithoutSendingEarlierBatchesAgain(): void
    {
        $writer = new RawLogWriter($this->directory);
        $time = new \DateTimeImmutable('2026-10-27 11:00:00 UTC');
        for ($i = 0; $i < MatomoPusher::BATCH_SIZE + 10; $i++) {
            $writer->append(['a' => 'start', 'n' => $i], $time);
        }
        $now = $time->modify('+5 minutes');

        $calls = 0;
        $failing = $this->createPusher(static function (array $requests) use (&$calls): int {
            if (++$calls === 2) {
                throw new \RuntimeException('Matomo not reachable');
            }
            return 0;
        });
        try {
            $failing->push($now);
            self::fail('Exception expected');
        } catch (\RuntimeException) {
        }
        self::assertSame(['202610271100'], $this->listRawMinutes());

        $sizes = [];
        $result = $this->createPusher(static function (array $requests) use (&$sizes): int {
            $sizes[] = count($requests);
            return 1;
        })->push($now);

        self::assertSame([10], $sizes);
        self::assertSame(['files' => 1, 'records' => MatomoPusher::BATCH_SIZE + 10, 'requests' => 10, 'invalid' => 1], $result);
        self::assertSame([], glob($this->directory . '/raw/*'));
    }

    private function createPusher(\Closure $send): MatomoPusher
    {
        return new MatomoPusher($this->directory, new MatomoRequestBuilder(7), $send);
    }

    /**
     * @return list<string>
     */
    private function listRawMinutes(): array
    {
        return array_map(
            static fn(string $file): string => substr(basename($file), 0, 12),
            glob($this->directory . '/raw/*.ndjson') ?: []
        );
    }
}
