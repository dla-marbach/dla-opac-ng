<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Statistics;

/**
 * Hängt Statistikzeilen (NDJSON) an die Rohdatei der aktuellen Stunde an.
 *
 * Eine Datei pro Stunde und Webserver (raw/YYYYMMDDHH-<host>.ndjson). Die Stunde steckt im Dateinamen,
 * die Zeilen selbst enthalten nur Bereich, Merkmale und Flags – keine IP-Adresse, keinen User-Agent,
 * keine Cookie-Werte und keine URL. Der Aggregator (dla_opac_ng:stats:aggregate) verarbeitet nur
 * abgeschlossene Stunden und löscht die Dateien danach.
 */
class RawLogWriter
{
    public const RAW_DIRECTORY = 'raw';

    private ?string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory;
    }

    /**
     * Schreibt einen Datensatz. Fehler werden nicht weitergereicht: Die Statistik darf keinen Request stören.
     */
    public function append(array $record, ?\DateTimeInterface $time = null): bool
    {
        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($line === false) {
            return false;
        }

        $directory = $this->getRawDirectory();
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return false;
        }

        $file = $directory . '/' . self::buildFileName($time ?? new \DateTimeImmutable(), self::getHostName());
        $handle = @fopen($file, 'ab');
        if ($handle === false) {
            return false;
        }

        $written = false;
        if (flock($handle, LOCK_EX)) {
            $written = fwrite($handle, $line . "\n") !== false;
            flock($handle, LOCK_UN);
        }
        fclose($handle);

        return $written;
    }

    public function getRawDirectory(): string
    {
        return ($this->directory ?? StatisticsConfiguration::getStorageDirectory()) . '/' . self::RAW_DIRECTORY;
    }

    public static function buildFileName(\DateTimeInterface $time, string $host): string
    {
        $local = \DateTimeImmutable::createFromInterface($time)->setTimezone(StatisticsConfiguration::getTimezone());

        return $local->format('YmdH') . '-' . $host . '.ndjson';
    }

    /**
     * Hostname für den Dateinamen, damit mehrere Webserver in ein gemeinsames Verzeichnis schreiben können.
     */
    public static function getHostName(): string
    {
        $host = preg_replace('/[^A-Za-z0-9._-]/', '', (string)gethostname());

        return $host !== '' && $host !== null ? $host : 'localhost';
    }
}
