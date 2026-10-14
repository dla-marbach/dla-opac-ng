# Anweisungen für KI-Agenten (dla-opac-ng)

Gilt für GitHub Copilot, Claude Code und andere Agenten, die `AGENTS.md` lesen.
Ausführliche Beschreibung der Abläufe: [README.md](README.md).

## Projekt

- TYPO3-12-Extension `dla_opac_ng` (Composer `dla/dla_opac_ng`, Namespace `Dla\DlaOpacNg\`) für den Katalog (OPAC) des Deutschen Literaturarchivs Marbach. Produktivsystem: TYPO3 12.4, PHP 8.3, MariaDB 11.8, Apache.
- Enthält das Such-Plugin der früheren Extension `dla/find` (Fork von subugoe/typo3-find): Code unter `Classes/Find/` mit eigenem Namespace `Dla\Find\`, ViewHelper-Prefix `s:`, Plugin-Signatur `find_find`, TypoScript `plugin.tx_find`. Diese Bezeichner bleiben aus Kompatibilitätsgründen unverändert.
- Solr wird über Solarium angesprochen, ausschließlich über `Classes/Service/SolrConnection.php` und die Umgebungsvariablen `SOLR_HOST`, `SOLR_CORE` (optional `SOLR_TIMEOUT`).
- Projektsprache (README, Commits, Kommentare, PR-Beschreibungen) ist **Deutsch**.

## Wo liegt was

- `Classes/` – `Ajax/` (eID-Endpunkte), `Cli/` (Konsolenbefehl `dla_opac_ng:import`), `Controller/` (Plugins DlaStart, DlaCollection, DlaClassification), `Middleware/`, `Service/`, `Updates/` (Upgrade-Wizards), `Utility/`, `ViewHelpers/` (Prefix `dla:`), `Find/` (Such-Plugin)
- `Configuration/` – `TypoScript/setup.ts` und `constants.ts` (DLA-spezifisch, u.a. `queryFields`; importieren die Basiskonfiguration aus `TypoScript/Find/`), `Services.yaml`, `RequestMiddlewares.php`, `Commands.php`, `TCA/`
- `Resources/Private/` – Fluid-Templates, -Layouts, -Partials; Sprachdateien in `Language/` (XLIFF)
- `Tests/` – PHPUnit-Tests in `Unit/` und `Find/Unit/`, Test-Hilfen in `Support/`, Solr-Mock-Server und Cassetten in `Fixtures/Solr/`
- `dla-opac-tests/` – Submodul mit Playwright-End-to-End-Tests
- `.devfiles/` – Dateien der Entwicklungsumgebung (DB-Dump `init.sql`, `.env`, Site-, Apache- und PHP-FPM-Konfiguration, Importdaten, Fonts)
- `t3example/` – lokal erzeugte TYPO3-Instanz (in `.gitignore`); nicht bearbeiten, nicht committen

## Umgebung und Befehle

Die Umgebung ist beim Start bereits eingerichtet (`.github/workflows/copilot-setup-steps.yml`): TYPO3 läuft unter http://127.0.0.1/ (Backend `/typo3`, admin / `Dla1337!`). PHP-Befehle mit PHP 8.3 ausführen (`php8.3`, falls `php` eine andere Version ist).

- `task start` – MariaDB, Solr-Mock-Server, PHP-FPM und Apache starten (idempotent), falls http://127.0.0.1/ nicht antwortet
- `task cache` – TYPO3-Cache leeren; Code wirkt direkt (Symlink `t3example/packages/dla-dla_opac_ng`), aber nach Änderungen an TypoScript, `Services.yaml`, `ext_localconf.php` u.ä. nötig
- `task test:php` – PHPUnit-Tests ohne TYPO3-Instanz, wie in der CI (`.github/workflows/phpunit.yml`); einzelne Tests mit `task test:php -- --filter Name`
- `task test -- "katalog/"` – Playwright-Tests gegen die laufende Instanz
- Psalm-Sicherheitsanalyse wie in der CI (`.github/workflows/psalm.yml`, Konfiguration `psalm.xml`), Image ist vorab geladen: `docker run --rm -v "$PWD":/app -w /app --entrypoint /composer/vendor/bin/psalm ghcr.io/psalm/psalm-github-actions:6.16.1 --taint-analysis --no-cache`
- Logs: `/tmp/typo3-apache-error.log`, `/tmp/typo3-php-error.log`, `t3example/var/log/`, `/tmp/solr-mock.log`

## Solr ohne Netzwerkzugriff

Ein echter Solr ist für Agenten nicht erreichbar. Instanz (Port 8984) und PHPUnit-Tests fragen stattdessen den Mock-Server `Tests/Fixtures/Solr/MockSolrServer.php` ab, der aufgezeichnete Antworten aus `Tests/Fixtures/Solr/cassettes/` liefert. Unbekannte Anfragen beantwortet er mit HTTP 404 und protokolliert sie in `Tests/Fixtures/Solr/missing-requests.log`. Seiten mit nicht aufgezeichneten Solr-Anfragen schlagen daher fehl; Bestandsübersicht, Systematik (Datenbank) und Backend funktionieren immer.

Cassetten können nur mit Solr-Tunnel aufgenommen werden (`task solr:record`, `task test:php:record`, `Tests/Fixtures/Solr/recorder.php`, siehe README). **Cassetten nie von Hand erstellen oder ändern.** Fehlen Cassetten für neue Tests, die benötigten Szenarien in `recorder.php` ergänzen und im PR darauf hinweisen, dass sie aufgenommen werden müssen.

## Konventionen

- Änderungen minimal halten und den Stil der umgebenden Dateien übernehmen.
- Neue Funktionalität und Bugfixes mit PHPUnit-Tests absichern (`Tests/Unit/`, Namespace `Dla\DlaOpacNg\Tests\`). Fluid-Partials mit `Tests/Support/FluidPartialTestCase.php` testen, Solr-Dokumente liefert `Tests/Support/SolrFixture.php`.
- PHP-Klassen gemäß PSR-4; Services werden über `Configuration/Services.yaml` autowired. Middlewares in `Configuration/RequestMiddlewares.php`, Konsolenbefehle in `Configuration/Services.yaml`/`Commands.php` registrieren.
- Fluid-ViewHelper erben von `TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper`, registrieren Argumente in `initializeArguments()` und heißen `<Name>ViewHelper.php`.
- UI-Texte nicht im Template kodieren, sondern als Übersetzungsschlüssel anlegen, immer in englischer (`locallang*.xlf`) und deutscher (`de.locallang*.xlf`) Sprachdatei.
- Keine Zugangsdaten oder internen Hostnamen in Code oder Konfiguration aufnehmen; die Dev-Zugangsdaten in `Taskfile.yml`/`.devfiles/`/README gelten nur lokal.
- README aktualisieren, wenn sich Abläufe oder Befehle ändern.
