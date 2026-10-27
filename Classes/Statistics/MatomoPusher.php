<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Statistics;

/**
 * Sendet abgeschlossene Rohdateien (raw/YYYYMMDDHHii-<host>.ndjson) an Matomo und löscht sie danach.
 *
 * Die Requests einer Datei gehen in Paketen von BATCH_SIZE an Matomo. Nach jedem bestätigten Paket wird der
 * Fortschritt in »<Datei>.progress« vermerkt: Bricht ein Lauf ab oder ist Matomo nicht erreichbar, setzt der
 * nächste Lauf dort fort, ohne bereits gesendete Requests doppelt zu zählen.
 */
class MatomoPusher
{
    public const BATCH_SIZE = 500;

    /**
     * Eine Rohdatei wird erst gesendet, wenn ihre Minute seit dieser Zeit (Sekunden) vorbei ist,
     * damit späte Schreibzugriffe laufender Requests nicht verloren gehen.
     */
    private const GRACE_PERIOD = 30;

    private const RAW_FILE_PATTERN = '/^(\d{12})-[A-Za-z0-9._-]+\.ndjson$/';

    private string $rawDirectory;

    /**
     * @param \Closure(list<array<string, string|int>>): int $send sendet ein Paket und liefert die Anzahl der
     *        von Matomo verworfenen Requests; wirft eine Exception, wenn das Paket nicht angenommen wurde
     */
    public function __construct(
        string $directory,
        private readonly MatomoRequestBuilder $builder,
        private readonly \Closure $send,
    ) {
        $this->rawDirectory = rtrim($directory, '/') . '/' . RawLogWriter::RAW_DIRECTORY;
    }

    /**
     * @return array{files: int, records: int, requests: int, invalid: int}
     */
    public function push(?\DateTimeImmutable $now = null, bool $includeCurrentMinute = false): array
    {
        $result = ['files' => 0, 'records' => 0, 'requests' => 0, 'invalid' => 0];

        foreach ($this->findRawFiles($now ?? new \DateTimeImmutable(), $includeCurrentMinute) as $file) {
            $records = $this->readRecords($file);
            $requests = $this->builder->build($records);

            $progressFile = $file . '.progress';
            $offset = is_file($progressFile) ? (int)file_get_contents($progressFile) : 0;
            while ($offset < count($requests)) {
                $batch = array_slice($requests, $offset, self::BATCH_SIZE);
                $result['invalid'] += ($this->send)($batch);
                $offset += count($batch);
                $result['requests'] += count($batch);
                file_put_contents($progressFile, (string)$offset);
            }

            @unlink($file);
            @unlink($progressFile);
            $result['files']++;
            $result['records'] += count($records);
        }

        return $result;
    }

    /**
     * Requests aller abgeschlossenen Rohdateien, ohne zu senden oder zu löschen (für --dry-run).
     *
     * @return list<array<string, string|int>>
     */
    public function preview(?\DateTimeImmutable $now = null, bool $includeCurrentMinute = false): array
    {
        $requests = [];
        foreach ($this->findRawFiles($now ?? new \DateTimeImmutable(), $includeCurrentMinute) as $file) {
            array_push($requests, ...$this->builder->build($this->readRecords($file)));
        }

        return $requests;
    }

    /**
     * @return list<string> Rohdateien abgeschlossener Minuten, älteste zuerst
     */
    private function findRawFiles(\DateTimeImmutable $now, bool $includeCurrentMinute): array
    {
        $files = [];
        foreach (glob($this->rawDirectory . '/*.ndjson') ?: [] as $file) {
            if (preg_match(self::RAW_FILE_PATTERN, basename($file), $matches) !== 1) {
                continue;
            }
            $minute = \DateTimeImmutable::createFromFormat('!YmdHi', $matches[1], new \DateTimeZone('UTC'));
            if ($minute === false) {
                continue;
            }
            if ($includeCurrentMinute || $minute->getTimestamp() + 60 + self::GRACE_PERIOD <= $now->getTimestamp()) {
                $files[] = $file;
            }
        }
        sort($files);

        return $files;
    }

    /**
     * @return list<array>
     */
    private function readRecords(string $file): array
    {
        $records = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $record = json_decode($line, true);
            if (is_array($record)) {
                $records[] = $record;
            }
        }

        return $records;
    }
}
