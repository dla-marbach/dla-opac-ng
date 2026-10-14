# Anweisungen für KI-Agenten (dla-opac-ng)

Gilt für GitHub Copilot, Claude Code und andere Agenten, die `AGENTS.md` lesen.

## Projektüberblick

TYPO3-Extension `dla_opac_ng` (Composer: `dla/dla_opac_ng`, Namespace `Dla\DlaOpacNg\`) für den Katalog (OPAC) des Deutschen Literaturarchivs Marbach (https://www.dla-marbach.de/katalog).

- TYPO3 v12; `composer.json` erlaubt PHP ^8.1, entwickelt und getestet wird mit PHP 8.3 (Produktivsystem: TYPO3 12.4, PHP 8.3, MariaDB 11.8, Apache; Entwicklungsumgebung: PHP 8.3, MariaDB 11.8 bzw. 10.11, Apache mit PHP-FPM)
- Enthält das Such-Plugin der früheren Extension `dla/find` (Fork von subugoe/typo3-find, mit Historie aus `dla-marbach/typo3-find` übernommen): Code unter `Classes/Find/` (Namespace `Dla\Find\`, ViewHelper-Prefix `s:`), Plugin-Signatur `find_find`, TypoScript `plugin.tx_find`. Suche/Anzeige läuft über Solr (Solarium).
- Solr-Verbindung ausschließlich über die Umgebungsvariablen `SOLR_HOST`/`SOLR_CORE` (optional `SOLR_TIMEOUT`); alle Solr-Zugriffe laufen über `Classes/Service/SolrConnection.php` (Solarium-Client bzw. `request()` für Rohanfragen).
- Projektsprache (README, Commits, Task-Beschreibungen, Übersetzungen) ist überwiegend **Deutsch**.

## Repository-Struktur

- `Classes/` – PHP-Code: `Ajax/` (eID-Endpunkte), `Cli/` (Konsolenbefehle, z.B. `dla_opac_ng:import`), `Controller/` (Plugins DlaStart, DlaCollection, DlaClassification), `Middleware/`, `Service/`, `Updates/` (Upgrade-Wizards), `Utility/`, `ViewHelpers/` (Fluid-ViewHelper, Namespace-Prefix `dla:`)
- `Classes/Find/` – Such-Plugin (ehemals `dla/find`): `SearchController`, `Service/SolrServiceProvider`, ViewHelper (Prefix `s:`)
- `Configuration/` – `TypoScript/setup.ts` + `constants.ts` (u.a. `queryFields`; importieren die Basiskonfiguration aus `TypoScript/Find/`), `Services.yaml`, `RequestMiddlewares.php`, `Commands.php`, `TCA/`
- `Resources/Private/` – Fluid-`Templates/`, `Layouts/`, `Partials/`, Sprachdateien in `Language/` (XLIFF; englisch `locallang*.xlf` und deutsch `de.locallang*.xlf` immer gemeinsam pflegen)
- `Resources/Public/` – CSS, JavaScript, Icons, Bilder, `Resolver/resolver.php`
- `ext_localconf.php`, `ext_tables.php`, `ext_tables.sql` – TYPO3-Extension-Einstiegspunkte
- `Tests/` – PHPUnit-Tests (`Unit/`, Tests des Such-Plugins unter `Find/Unit/`), Test-Hilfen (`Support/`), Solr-Fixtures (`Fixtures/Solr/`)
- `dla-opac-tests/` – Submodul mit Playwright-End-to-End-Tests
- `.devfiles/` – Dateien für die lokale Installation (DB-Dump `init.sql`, Umgebungsvariablen `.env`, Site-Konfiguration, Apache- und PHP-FPM-Konfiguration, TSV-Importdaten, Fonts)
- `t3example/` – lokal erzeugte TYPO3-Instanz, in `.gitignore`; **nicht committen und nicht manuell bearbeiten**

## Entwicklungsumgebung und Befehle

Alle Abläufe laufen über [Task](https://taskfile.dev) (`Taskfile.yml`): PHP 8.3, Composer, Apache und MariaDB werden per apt installiert, TYPO3 läuft unter http://127.0.0.1/ (Backend `/typo3`, admin / `Dla1337!`). Logs: `/tmp/typo3-apache-error.log`, `/tmp/typo3-php-error.log`, `t3example/var/log/`, Solr-Mock `/tmp/solr-mock.log`. PHP-Befehle mit PHP 8.3 ausführen (`php8.3`, falls `php` eine andere Version ist).

- `task install` – PHP/Composer/Apache/MariaDB installieren (falls nötig), TYPO3 in `t3example/` aufsetzen, die Extension einbinden und starten
- `task start` / `task stop` – MariaDB, Solr-Mock-Server, PHP-FPM und Apache starten (idempotent) bzw. Solr-Mock-Server, PHP-FPM und Apache stoppen (MariaDB läuft weiter). Antwortet http://127.0.0.1/ nicht, zuerst `task start` ausführen.
- `task cache` – TYPO3-Cache leeren. Die Extension ist per Composer-Path-Repository eingebunden (`t3example/packages/dla-dla_opac_ng` → Repository), Codeänderungen wirken direkt; nach Änderungen an TypoScript, `Services.yaml`, `ext_localconf.php` o.ä. vor dem Testen `task cache` ausführen.
- `task test:php` – PHPUnit-Tests (inkl. `Tests/Find`) mit lokalem PHP ohne TYPO3-Instanz (`composer install` im Repository + `vendor/bin/phpunit -c Tests/phpunit.xml`, wie in der CI), gegen einen Solr-Mock-Server (kein Solr-Tunnel nötig)
- `task test:php:record` – Tests gegen echten Solr ausführen und fehlende Cassetten aufzeichnen (nur mit Solr-Tunnel)
- `task test -- "katalog/"` – Playwright-Tests (benötigen laufende Instanz)
- Die TYPO3-Instanz fragt Solr über den Solr-Mock-Server (Port 8984) ab: Ohne Tunnel liefert er aufgezeichnete Antworten (`Tests/Fixtures/Solr/cassettes/`, Aufnahmen der Instanz in `typo3/`), mit Tunnel auf Port 8983 reicht er an den echten Solr durch. Seiten, deren Solr-Anfragen nicht aufgezeichnet sind, schlagen ohne Tunnel fehl (fehlende Anfragen in `Tests/Fixtures/Solr/missing-requests.log`); Bestandsübersicht/Systematik (Datenbank) und Backend funktionieren immer.
- `task reinstall` – Umgebung komplett neu aufsetzen

CI (`.github/workflows/`):

- `phpunit.yml` – PHPUnit ohne TYPO3-Instanz: PHP 8.3 per `setup-php`, `composer install` im Repository (mit Composer-Cache), `vendor/bin/phpunit -c Tests/phpunit.xml`
- `psalm.yml` – Psalm-Sicherheitsanalyse (`psalm.xml`, errorLevel 8) mit dem Docker-Image `ghcr.io/psalm/psalm-github-actions:6.16.1`
- `copilot-setup-steps.yml` – bereitet die Umgebung für den Copilot-Agent vor (MariaDB-11.8-Service-Container, Task, PHP 8.3 + `composer install` im Repository, `task install`, Psalm-Image); TYPO3 läuft danach bereits

## Tests

- Neue Funktionalität bzw. Bugfixes möglichst mit PHPUnit-Tests unter `Tests/Unit/` absichern (Namespace `Dla\DlaOpacNg\Tests\`).
- Solr-Zugriffe laufen in Tests gegen `Tests/Fixtures/Solr/MockSolrServer.php`, der aufgezeichnete Antworten (Cassetten) aus `Tests/Fixtures/Solr/cassettes/` liefert. Neue Szenarien in `Tests/Fixtures/Solr/recorder.php` ergänzen; Aufnahme ist nur mit Solr-Tunnel möglich. Cassetten nicht von Hand erfinden.
- Unbekannte Anfragen beantwortet der Mock mit HTTP 404 und protokolliert sie in `Tests/Fixtures/Solr/missing-requests.log` (in `.gitignore`).
- Fluid-Partials werden mit `Tests/Support/FluidPartialTestCase.php` gerendert (minimaler Fluid-Kontext ohne TYPO3-Bootstrap; `f:translate` und `f:link.action` sind durch Test-Varianten in `Tests/Support/FluidViewHelperOverrides/` ersetzt). Echte Solr-Dokumente liefert `Tests/Support/SolrFixture.php`, `dla:collection` wird durch `FakeCollectionService` ersetzt.
- Nach Änderungen an `queryFields` in `Configuration/TypoScript/setup.ts` den Ordner `Tests/Fixtures/Solr/cassettes/subqueries` neu aufzeichnen (siehe README).

## Konventionen

- PHP-Klassen gemäß PSR-4 unter `Classes/`; Services werden über `Configuration/Services.yaml` autowired.
- Fluid-ViewHelper erben von `TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper`, registrieren Argumente in `initializeArguments()` und heißen `<Name>ViewHelper.php`.
- Neue Middlewares in `Configuration/RequestMiddlewares.php`, neue Konsolenbefehle in `Configuration/Services.yaml`/`Commands.php` registrieren.
- Neue UI-Texte als Übersetzungsschlüssel in den XLIFF-Dateien (Englisch und Deutsch) anlegen, nicht hart im Template kodieren.
- Keine Zugangsdaten oder internen Hostnamen in Code/Konfiguration aufnehmen; bestehende Dev-Zugangsdaten in `Taskfile.yml`/README gelten nur für die lokale Umgebung.
- Änderungen minimal halten und den Stil der umgebenden Dateien übernehmen. README (Deutsch) aktualisieren, wenn sich Abläufe oder Befehle ändern.
