<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Statistics;

/**
 * Hängt Statistikzeilen (NDJSON) an die Rohdatei der aktuellen Minute an.
 *
 * Eine Datei pro Minute (UTC) und Webserver (raw/YYYYMMDDHHii-<host>.ndjson). Jede Zeile enthält den Zeitpunkt
 * (»ts«, Unix-Zeit), Bereich, Merkmale und Flags – keine IP-Adresse, keinen User-Agent, keine Cookie-Werte und
 * keine Query-Parameter. Der Befehl dla_opac_ng:stats:matomo-push sendet nur abgeschlossene Minuten an Matomo
 * und löscht die Dateien danach.
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
        $time ??= new \DateTimeImmutable();
        $line = json_encode(
            ['ts' => $time->getTimestamp()] + $record,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($line === false) {
            return false;
        }

        $directory = $this->getRawDirectory();
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return false;
        }

        $file = $directory . '/' . self::buildFileName($time, self::getHostName());
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

    /**
     * Minute in UTC, damit die Dateinamen auch bei der Zeitumstellung eindeutig sind.
     */
    public static function buildFileName(\DateTimeInterface $time, string $host): string
    {
        return gmdate('YmdHi', $time->getTimestamp()) . '-' . $host . '.ndjson';
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
