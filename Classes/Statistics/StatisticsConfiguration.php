<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Statistics;

use TYPO3\CMS\Core\Core\Environment;

/**
 * Einstellungen der Nutzungsstatistik aus Umgebungsvariablen (siehe README, Abschnitt »Nutzungsstatistik«).
 */
class StatisticsConfiguration
{
    /**
     * Zeitzone für Stunden- und Tageswerte.
     */
    public const TIMEZONE = 'Europe/Berlin';

    /**
     * Standard-Mindesthäufigkeit, ab der Suchbegriffe im Backend-Modul angezeigt werden.
     */
    public const DEFAULT_TERMS_MIN_COUNT = 5;

    /**
     * Standard-Aufbewahrungsdauer (Tage) für Suchbegriffe in den Tagesdateien.
     */
    public const DEFAULT_TERMS_RETENTION_DAYS = 90;

    /**
     * Erfassung nur, wenn opacStatsEnabled exakt "1" ist (wie opacPowEnabled).
     */
    public static function isEnabled(): bool
    {
        return self::getEnvironmentValue('opacStatsEnabled') === '1';
    }

    public static function getTermsMinCount(): int
    {
        return self::getPositiveInt('opacStatsTermsMinCount', self::DEFAULT_TERMS_MIN_COUNT);
    }

    public static function getTermsRetentionDays(): int
    {
        return self::getPositiveInt('opacStatsTermsRetentionDays', self::DEFAULT_TERMS_RETENTION_DAYS);
    }

    /**
     * Ablageort der Statistikdaten: opacStatsPath oder var/opac-stats der TYPO3-Instanz (außerhalb des Webroots).
     */
    public static function getStorageDirectory(): string
    {
        $path = self::getEnvironmentValue('opacStatsPath');
        if ($path !== '') {
            return rtrim($path, '/');
        }

        return Environment::getVarPath() . '/opac-stats';
    }

    public static function getTimezone(): \DateTimeZone
    {
        return new \DateTimeZone(self::TIMEZONE);
    }

    private static function getPositiveInt(string $name, int $default): int
    {
        $value = self::getEnvironmentValue($name);
        if ($value === '' || !ctype_digit($value) || (int)$value < 1) {
            return $default;
        }

        return (int)$value;
    }

    private static function getEnvironmentValue(string $name): string
    {
        $value = getenv($name);
        if ($value === false) {
            return '';
        }

        return trim((string)$value, " \t\n\r\0\x0B\"");
    }
}
