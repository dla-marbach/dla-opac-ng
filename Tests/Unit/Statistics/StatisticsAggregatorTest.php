<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Tests\Unit\Statistics;

use Dla\DlaOpacNg\Statistics\StatisticsAggregator;
use Dla\DlaOpacNg\Statistics\StatisticsConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StatisticsAggregatorTest extends UnitTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/dla-opac-stats-test-' . bin2hex(random_bytes(4));
        mkdir($this->directory . '/raw', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function rawFilesOfSeveralHostsAreSummedUpAndDeleted(): void
    {
        $this->writeRaw('2026102714-web1', [
            ['a' => 'search', 'pid' => 12, 'l' => 'de', 'r' => 'int', 'in' => 0, 'bot' => 0,
                'd' => ['e' => 0, 'x' => 0, 'qf' => ['default'], 't' => 'schiller', 'f' => ['Medientypen' => ['Buch' => 1]], 'p' => 1, 'n' => 0]],
            ['a' => 'search', 'l' => 'de', 'r' => 'int', 'in' => 0, 'bot' => 0, 'd' => ['e' => 1, 'x' => 0, 'p' => 4, 'n' => 2000]],
            ['a' => 'pow:issued'],
            ['a' => 'pow:issued'],
            ['a' => 'pow:solved'],
        ]);
        $this->writeRaw('2026102714-web2', [
            ['a' => 'search', 'l' => 'en', 'r' => 'ext', 'in' => 1, 'bot' => 0,
                'd' => ['e' => 0, 'x' => 1, 'qf' => ['title'], 't' => 'schiller', 'f' => ['Medientypen' => ['Buch' => 'not']], 'p' => 1, 'n' => 5]],
            ['a' => 'detail', 'r' => 'none', 'in' => 0, 'bot' => 1, 'd' => ['id' => 'DE-1', 'u' => 0, 'pdf' => 0]],
            ['a' => 'detail', 'r' => 'none', 'in' => 0, 'bot' => 1, 'd' => ['nf' => 1, 'u' => 0, 'pdf' => 0]],
            'kein JSON',
        ]);

        $result = $this->createAggregator()->aggregate($this->now('2026-10-27 16:00:00'));

        self::assertSame(2, $result['files']);
        self::assertSame(8, $result['records']);
        self::assertSame(['2026-10-27'], $result['days']);
        self::assertSame([], glob($this->directory . '/raw/*'));

        $day = $this->readJson('days/2026-10-27.json');
        self::assertSame(['issued' => 2, 'solved' => 1], $day['pow']);

        $search = $day['audiences']['ext']['search'];
        self::assertSame(2, $search['hits']);
        self::assertSame(2, $search['hours'][14]);
        self::assertSame(['1' => 1, '3-5' => 1], $search['pages']);
        self::assertSame(['text' => 1, 'browse' => 1], $search['mode']);
        self::assertSame(['0' => 1, '>1000' => 1], $search['results']);
        self::assertSame(['schiller' => 1], $search['terms']);
        self::assertSame(['schiller' => 1], $search['zeroTerms']);
        self::assertSame(['Buch' => 1], $search['facetValues']['Medientypen']);
        self::assertSame(['12' => 1], $search['entry']);

        $internal = $day['audiences']['int']['search'];
        self::assertSame(['extended' => 1], $internal['form']);
        self::assertSame(['not:Buch' => 1], $internal['facetValues']['Medientypen']);
        self::assertArrayNotHasKey('zeroTerms', $internal);

        self::assertSame(['DE-1' => 1], $day['audiences']['bot']['detail']['records']);
        self::assertSame(['yes' => 1, 'no' => 1], $day['audiences']['bot']['detail']['found']);

        $month = $this->readJson('months/2026-10.json');
        self::assertSame(2, $month['audiences']['ext']['search']['hits']);
        self::assertSame(['27' => 2], $month['audiences']['ext']['search']['days']);
        self::assertArrayNotHasKey('hours', $month['audiences']['ext']['search']);
    }

    /**
     * @test
     */
    public function currentAndJustFinishedHoursAreLeftForLaterRuns(): void
    {
        $this->writeRaw('2026102715-web1', [['a' => 'start', 'in' => 0, 'bot' => 0]]);
        $this->writeRaw('2026102716-web1', [['a' => 'start', 'in' => 0, 'bot' => 0]]);

        $result = $this->createAggregator()->aggregate($this->now('2026-10-27 16:01:00'));

        self::assertSame(0, $result['files']);
        self::assertCount(2, glob($this->directory . '/raw/*'));

        $result = $this->createAggregator()->aggregate($this->now('2026-10-27 16:03:00'));
        self::assertSame(1, $result['files']);
        self::assertCount(1, glob($this->directory . '/raw/*'));
    }

    /**
     * @test
     */
    public function abortedRunKeepsRawFilesAndIsCompletedWithoutCountingTwice(): void
    {
        $record = ['a' => 'start', 'in' => 0, 'bot' => 0];
        $this->writeRaw('2026102710-web1', [$record]);

        $failing = new class ($this->directory, 90, 5) extends StatisticsAggregator {
            public function rebuildMonth(string $month): void
            {
                throw new \RuntimeException('Abbruch', 1793000099);
            }
        };
        try {
            $failing->aggregate($this->now('2026-10-27 16:00:00'));
            self::fail('Exception expected');
        } catch (\RuntimeException) {
        }
        self::assertCount(1, glob($this->directory . '/raw/*'));
        self::assertFileDoesNotExist($this->directory . '/months/2026-10.json');

        $this->writeRaw('2026102711-web1', [$record]);
        $this->createAggregator()->aggregate($this->now('2026-10-27 16:00:00'));

        self::assertSame(2, $this->readJson('days/2026-10-27.json')['audiences']['ext']['start']['hits']);
        self::assertSame(2, $this->readJson('months/2026-10.json')['audiences']['ext']['start']['hits']);
        self::assertSame([], glob($this->directory . '/raw/*'));
    }

    /**
     * @test
     */
    public function currentHourCanBeAggregatedRepeatedly(): void
    {
        $record = ['a' => 'start', 'in' => 0, 'bot' => 0];
        $this->writeRaw('2026102716-web1', [$record]);
        $this->createAggregator()->aggregate($this->now('2026-10-27 16:30:00'), true);

        // Weitere Requests derselben Stunde schreiben wieder in eine Datei mit demselben Namen.
        $this->writeRaw('2026102716-web1', [$record, $record]);
        $this->createAggregator()->aggregate($this->now('2026-10-27 16:45:00'), true);

        self::assertSame(3, $this->readJson('days/2026-10-27.json')['audiences']['ext']['start']['hits']);
        self::assertSame([], glob($this->directory . '/raw/*'));
    }

    /**
     * @test
     */
    public function runningDaysKeepExactCountsAndAreCappedWhenComplete(): void
    {
        $records = [];
        for ($i = 0; $i < StatisticsAggregator::TOP_N + 20; $i++) {
            $records[] = ['a' => 'detail', 'in' => 0, 'bot' => 0, 'd' => ['id' => 'ID-' . $i]];
        }
        $this->writeRaw('2026102710-web1', $records);
        $this->createAggregator()->aggregate($this->now('2026-10-27 16:00:00'));

        $this->writeRaw('2026102714-web1', [['a' => 'detail', 'in' => 0, 'bot' => 0, 'd' => ['id' => 'ID-' . (StatisticsAggregator::TOP_N + 10)]]]);
        $this->createAggregator()->aggregate($this->now('2026-10-27 16:00:00'));

        $ids = $this->readJson('days/2026-10-27.json')['audiences']['ext']['detail']['records'];
        self::assertCount(StatisticsAggregator::TOP_N + 20, $ids);
        self::assertSame(2, $ids['ID-' . (StatisticsAggregator::TOP_N + 10)]);

        $this->writeRaw('2026102723-web1', [['a' => 'start', 'in' => 0, 'bot' => 0]]);
        $this->createAggregator()->aggregate($this->now('2026-10-28 00:05:00'));

        $ids = $this->readJson('days/2026-10-27.json')['audiences']['ext']['detail']['records'];
        self::assertCount(StatisticsAggregator::TOP_N + 1, $ids);
        self::assertSame(2, $ids['ID-' . (StatisticsAggregator::TOP_N + 10)]);
        self::assertSame(20, $ids[StatisticsAggregator::OTHER_KEY]);
    }

    /**
     * @test
     */
    public function dimensionsAreCappedWithRemainderInOther(): void
    {
        $records = [];
        for ($i = 0; $i < StatisticsAggregator::TOP_N + 20; $i++) {
            $records[] = ['a' => 'detail', 'in' => 0, 'bot' => 0, 'd' => ['id' => 'ID-' . $i]];
        }
        $records[] = ['a' => 'detail', 'in' => 0, 'bot' => 0, 'd' => ['id' => 'ID-7']];
        $this->writeRaw('2026102710-web1', $records);

        $this->createAggregator()->aggregate($this->now('2026-10-28 08:00:00'));

        $ids = $this->readJson('days/2026-10-27.json')['audiences']['ext']['detail']['records'];
        self::assertCount(StatisticsAggregator::TOP_N + 1, $ids);
        self::assertSame(2, $ids['ID-7']);
        self::assertSame(20, $ids[StatisticsAggregator::OTHER_KEY]);
    }

    /**
     * @test
     */
    public function monthFilesOnlyKeepFrequentTerms(): void
    {
        $records = [];
        foreach (['häufig' => 5, 'selten' => 2] as $term => $count) {
            for ($i = 0; $i < $count; $i++) {
                $records[] = ['a' => 'search', 'in' => 0, 'bot' => 0, 'd' => ['e' => 0, 't' => $term, 'p' => 1, 'n' => 3]];
            }
        }
        $this->writeRaw('2026102710-web1', $records);

        $this->createAggregator()->aggregate($this->now('2026-10-27 16:00:00'));

        self::assertSame(['häufig' => 5, 'selten' => 2], $this->readJson('days/2026-10-27.json')['audiences']['ext']['search']['terms']);
        self::assertSame(
            ['häufig' => 5, StatisticsAggregator::OTHER_KEY => 2],
            $this->readJson('months/2026-10.json')['audiences']['ext']['search']['terms']
        );
    }

    /**
     * @test
     */
    public function termsAreRemovedFromOldDayFiles(): void
    {
        $this->writeRaw('2026070110-web1', [['a' => 'search', 'in' => 0, 'bot' => 0, 'd' => ['e' => 0, 't' => 'alt', 'p' => 1, 'n' => 0]]]);
        $this->createAggregator()->aggregate($this->now('2026-07-02 12:00:00'));
        self::assertArrayHasKey('terms', $this->readJson('days/2026-07-01.json')['audiences']['ext']['search']);

        $this->createAggregator()->aggregate($this->now('2026-10-27 12:00:00'));

        $day = $this->readJson('days/2026-07-01.json');
        self::assertArrayNotHasKey('terms', $day['audiences']['ext']['search']);
        self::assertArrayNotHasKey('zeroTerms', $day['audiences']['ext']['search']);
        self::assertSame(1, $day['audiences']['ext']['search']['hits']);
    }

    /**
     * @test
     */
    public function termsOfTheRunningMonthAreKeptDespiteShortRetention(): void
    {
        $records = array_fill(0, 5, ['a' => 'search', 'in' => 0, 'bot' => 0, 'd' => ['e' => 0, 't' => 'goethe', 'p' => 1, 'n' => 3]]);
        $this->writeRaw('2026100210-web1', $records);
        $aggregator = new StatisticsAggregator($this->directory, 7, 5);
        $aggregator->aggregate($this->now('2026-10-03 12:00:00'));

        $this->writeRaw('2026102710-web1', [['a' => 'start', 'in' => 0, 'bot' => 0]]);
        $aggregator->aggregate($this->now('2026-10-27 12:00:00'));

        self::assertSame(['goethe' => 5], $this->readJson('days/2026-10-02.json')['audiences']['ext']['search']['terms']);
        self::assertSame(['goethe' => 5], $this->readJson('months/2026-10.json')['audiences']['ext']['search']['terms']);

        $aggregator->aggregate($this->now('2026-11-01 12:00:00'));
        self::assertArrayNotHasKey('terms', $this->readJson('days/2026-10-02.json')['audiences']['ext']['search']);
        self::assertSame(['goethe' => 5], $this->readJson('months/2026-10.json')['audiences']['ext']['search']['terms']);
    }

    private function createAggregator(): StatisticsAggregator
    {
        return new StatisticsAggregator($this->directory, 90, 5);
    }

    private function now(string $time): \DateTimeImmutable
    {
        return new \DateTimeImmutable($time, StatisticsConfiguration::getTimezone());
    }

    private function writeRaw(string $name, array $records): void
    {
        $lines = array_map(
            static fn($record): string => is_string($record) ? $record : json_encode($record, JSON_UNESCAPED_UNICODE),
            $records
        );
        file_put_contents($this->directory . '/raw/' . $name . '.ndjson', implode("\n", $lines) . "\n");
    }

    private function readJson(string $path): array
    {
        return json_decode((string)file_get_contents($this->directory . '/' . $path), true);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($directory);
    }
}
