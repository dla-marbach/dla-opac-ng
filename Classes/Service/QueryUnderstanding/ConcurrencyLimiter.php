<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Service\QueryUnderstanding;

/**
 * Begrenzt die Anzahl gleichzeitiger LLM-Anfragen je Webserver über Dateisperren (flock).
 *
 * Es wird nie gewartet: Ist kein Platz frei, liefert acquire() null und der Aufrufer fällt sofort
 * auf die normale Suche zurück. Sperren werden beim Beenden des PHP-Prozesses automatisch freigegeben.
 */
class ConcurrencyLimiter
{
    /** @var resource|null */
    private $handle = null;

    public function __construct(
        private readonly string $lockDirectory,
        private readonly int $slots
    ) {
    }

    /**
     * Belegt einen freien Platz. Liefert true bei Erfolg.
     */
    public function acquire(): bool
    {
        if ($this->handle !== null) {
            return true;
        }
        if (!is_dir($this->lockDirectory) && !@mkdir($this->lockDirectory, 0775, true) && !is_dir($this->lockDirectory)) {
            // Ohne Sperrverzeichnis nicht blockieren, sondern ohne Begrenzung fortfahren.
            return true;
        }

        for ($slot = 0; $slot < max(1, $this->slots); $slot++) {
            $handle = @fopen($this->lockDirectory . '/slot-' . $slot . '.lock', 'c');
            if ($handle === false) {
                continue;
            }
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                $this->handle = $handle;
                return true;
            }
            fclose($handle);
        }

        return false;
    }

    public function release(): void
    {
        if ($this->handle === null) {
            return;
        }
        flock($this->handle, LOCK_UN);
        fclose($this->handle);
        $this->handle = null;
    }

    public function __destruct()
    {
        $this->release();
    }
}
