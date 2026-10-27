<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Statistics;

/**
 * Liest die aggregierten Tages- bzw. Monatsdateien für einen Zeitraum und fasst sie für das Backend-Modul zusammen.
 *
 * Bis MAX_DAYS_FOR_DAILY Tage werden Tagesdateien gelesen (Verlauf pro Tag), darüber Monatsdateien
 * (Verlauf pro Monat; der Zeitraum wird dann auf ganze Monate erweitert). Suchbegriffe unterhalb der
 * Mindesthäufigkeit werden hier entfernt und gelangen nicht in den Browser.
 */
class StatisticsRepository
{
    public const MAX_DAYS_FOR_DAILY = 92;

    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = rtrim($directory ?? StatisticsConfiguration::getStorageDirectory(), '/');
    }

    public function load(\DateTimeImmutable $from, \DateTimeImmutable $to, int $termsMinCount): array
    {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $daily = (int)$from->diff($to)->days + 1 <= self::MAX_DAYS_FOR_DAILY;
        $result = [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'granularity' => $daily ? 'day' : 'month',
            'termsMinCount' => $termsMinCount,
            'series' => [],
            'pow' => ['issued' => 0, 'solved' => 0],
            'audiences' => [],
        ];

        if ($daily) {
            for ($day = $from; $day <= $to; $day = $day->modify('+1 day')) {
                $label = $day->format('Y-m-d');
                $this->addPeriod($result, $label, $this->readJson($this->directory . '/days/' . $label . '.json'));
            }
        } else {
            $month = $from->modify('first day of this month');
            $result['from'] = $month->format('Y-m-d');
            $result['to'] = $to->modify('last day of this month')->format('Y-m-d');
            for (; $month <= $to; $month = $month->modify('+1 month')) {
                $label = $month->format('Y-m');
                $this->addPeriod($result, $label, $this->readJson($this->directory . '/months/' . $label . '.json'));
            }
        }

        StatisticsAggregator::capDimensions($result['audiences']);
        StatisticsAggregator::filterTerms($result['audiences'], $termsMinCount);

        return $result;
    }

    private function addPeriod(array &$result, string $label, ?array $data): void
    {
        $point = ['label' => $label, 'pow' => $data['pow'] ?? ['issued' => 0, 'solved' => 0], 'hits' => []];

        foreach ($data['audiences'] ?? [] as $audience => $areas) {
            foreach ($areas as $area => $stats) {
                $point['hits'][$audience][$area] = (int)($stats['hits'] ?? 0);
                unset($stats['days']);
                $result['audiences'][$audience][$area] ??= [];
                StatisticsAggregator::mergeCounts($result['audiences'][$audience][$area], $stats);
            }
        }

        StatisticsAggregator::mergeCounts($result['pow'], $point['pow']);
        $result['series'][] = $point;
    }

    private function readJson(string $file): ?array
    {
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string)file_get_contents($file), true);

        return is_array($data) ? $data : null;
    }
}
