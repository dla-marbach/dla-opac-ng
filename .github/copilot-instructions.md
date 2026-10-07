# Copilot Instructions für dla-opac-ng

## Projektüberblick

TYPO3-Extension `dla_opac_ng` (Composer: `dla/dla_opac_ng`, Namespace `Dla\DlaOpacNg\`) für den Katalog (OPAC) des Deutschen Literaturarchivs Marbach (https://www.dla-marbach.de/katalog).

- TYPO3 v12, PHP ^8.1 (Entwicklungsumgebung: PHP 8.2, MariaDB 10.11 in DDEV)
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
- `.devfiles/` – Dateien für die lokale Installation (DB-Dump `init.sql`, Site-Konfiguration, TSV-Importdaten, Fonts, CSS)
- `t3example/` – lokal erzeugte TYPO3-Instanz (DDEV), in `.gitignore`; **nicht committen und nicht manuell bearbeiten**

## Entwicklungsumgebung und Befehle

Alle Abläufe laufen über [Task](https://taskfile.dev) (`Taskfile.yml`) und DDEV:

- `task install` – DDEV/TYPO3 in `t3example/` aufsetzen und die Extension einbinden
- `task cache` – Extension-Code neu nach `t3example/.local-ext/` spiegeln und TYPO3-Cache leeren. **Nach Codeänderungen (insbesondere neuen/gelöschten Dateien) vor dem Testen ausführen**, da die Extension per Hardlink-Kopie (`cp -al`) und nicht direkt eingebunden ist.
- `task test:php` – PHPUnit-Tests (inkl. `Tests/Find`) im DDEV-Container, gegen einen Solr-Mock-Server (kein Solr-Tunnel nötig)
- `task test:php:record` – Tests gegen echten Solr ausführen und fehlende Cassetten aufzeichnen (nur mit Solr-Tunnel)
- `task test -- "katalog/"` – Playwright-Tests (benötigen laufende Instanz und Solr-Verbindung)
- `task reinstall` – Umgebung komplett neu aufsetzen

CI (`.github/workflows/`):

- `phpunit.yml` – `task install` + `task test:php`
- `psalm.yml` – Psalm-Sicherheitsanalyse (`psalm.xml`, errorLevel 8) mit dem Docker-Image `ghcr.io/psalm/psalm-github-actions:6.16.1`
- `copilot-setup-steps.yml` – bereitet die Umgebung für den Copilot-Agent vor (DDEV, Task, `task install`, Psalm-Image)

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
