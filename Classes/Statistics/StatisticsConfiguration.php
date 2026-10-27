<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Statistics;

use TYPO3\CMS\Core\Core\Environment;

/**
 * Einstellungen der Nutzungsstatistik aus Umgebungsvariablen (siehe README, Abschnitt »Nutzungsstatistik mit Matomo«).
 */
class StatisticsConfiguration
{
    /**
     * Schlüssel, die in opacMatomoDimensions einer Custom Dimension (Scope »Aktion«) zugeordnet werden können.
     */
    public const DIMENSION_KEYS = [
        'area', 'audience', 'language', 'referrer', 'mode', 'fields', 'facets', 'sort', 'page', 'perPage', 'hits',
    ];

    /**
     * Erfassung nur, wenn opacStatsEnabled exakt "1" ist (wie opacPowEnabled).
     */
    public static function isEnabled(): bool
    {
        return self::getEnvironmentValue('opacStatsEnabled') === '1';
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

    /**
     * Basis-URL der Matomo-Installation, z.B. https://matomo.example.org/ (ohne matomo.php).
     */
    public static function getMatomoUrl(): string
    {
        return rtrim(self::getEnvironmentValue('opacMatomoUrl'), '/');
    }

    public static function getMatomoToken(): string
    {
        return self::getEnvironmentValue('opacMatomoToken');
    }

    /**
     * Website-ID für die Zugriffe auf den Katalog.
     */
    public static function getMatomoSiteId(): int
    {
        return self::getPositiveInt('opacMatomoSiteId', 0);
    }

    /**
     * Optionale Website-ID für zusammengefasste Zähler (Proof-of-Work, Bots, AJAX); 0 = nicht senden.
     */
    public static function getMatomoAuxSiteId(): int
    {
        return self::getPositiveInt('opacMatomoAuxSiteId', 0);
    }

    public static function isMatomoConfigured(): bool
    {
        return self::getMatomoUrl() !== '' && self::getMatomoToken() !== '' && self::getMatomoSiteId() > 0;
    }

    /**
     * Zuordnung der Merkmale zu den IDs der Custom Dimensions in Matomo,
     * z.B. opacMatomoDimensions="area:1,audience:2,mode:3" → ['area' => 1, 'audience' => 2, 'mode' => 3].
     * Unbekannte Schlüssel und ungültige IDs werden ignoriert.
     *
     * @return array<string, int>
     */
    public static function getMatomoDimensions(): array
    {
        $dimensions = [];
        foreach (explode(',', self::getEnvironmentValue('opacMatomoDimensions')) as $pair) {
            [$key, $id] = array_map('trim', explode(':', $pair, 2)) + [1 => ''];
            if (in_array($key, self::DIMENSION_KEYS, true) && ctype_digit($id) && (int)$id > 0) {
                $dimensions[$key] = (int)$id;
            }
        }

        return $dimensions;
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
