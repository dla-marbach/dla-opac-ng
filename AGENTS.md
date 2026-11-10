# AGENTS.md

Regeln für KI-Agenten in diesem Repository. Zweck, Aufbau und Befehle des Projekts stehen im README.

## Umgebung
- Lokal und in Codespaces läuft die Entwicklung im Devcontainer (`.devcontainer/`).
- Fehlt ein Werkzeug, benenne es im PR; die Ergänzung gehört in `.devcontainer/`.

## Checks vor Abschluss
- Maßgeblich sind die Schritte in `.github/workflows/`. Führe Linter und Tests lokal genauso aus wie die CI. Sicherheitsscans laufen nur in der CI; behebe ihre Funde.
- Linter und alle Unit-Tests müssen grün sein. Neue Funktionen und Fixes bekommen eigene Tests.

## Git und Pull Requests
- Jede Änderung gehört zu einem Redmine-Ticket. Arbeite in einem Branch `feature/<ticket>-<kurzbeschreibung>` oder `fix/<ticket>-<kurzbeschreibung>`, nie direkt auf `main`. Vergibt dein Werkzeug den Branch-Namen selbst (z. B. `copilot/…`), behalte ihn und nenne die Ticketnummer im PR-Titel. Fehlt die Ticketnummer, frage danach.
- Halte Änderungen klein und auf eine Aufgabe beschränkt. Gemergt wird per Squash, und zwar nur durch Menschen.
- Nenne die Ticketnummer im PR-Titel ohne „#“ (z. B. „… (Ticket 1234)“), verlinke das Ticket in der Beschreibung und beschreibe, was sich ändert und warum. Breaking Changes und manuelle Upgrade-Schritte (z. B. Datenbank, Upgrade-Wizards, neue Variablen) werden zusätzlich in die Release Notes übernommen.
- Setze Hinweise aus dem Copilot-Review um oder begründe im PR, warum nicht.

## Abhängigkeiten
- Updates von Abhängigkeiten, GitHub Actions und Base Images übernimmt Dependabot. Ändere Versionen nur, wenn die Aufgabe es erfordert.
- Füge neue Abhängigkeiten nur hinzu, wenn die Aufgabe es erfordert. Begründe sie im PR. Die Extension hat kein Lockfile; Versionsbereiche stehen in `composer.json`.
- Base Image in `.devcontainer/Dockerfile` immer mit festem Versionstag, nie `latest`.
- Fremdbibliotheken, auch JavaScript und CSS, nur über den Paketmanager einbinden, nie als kopierte Datei ins Repo. Die bereits kopierten Bibliotheken unter `Resources/Public/` werden per Ticket abgelöst; füge keine weiteren hinzu und aktualisiere sie nicht durch erneutes Kopieren.

## Betrieb
- Die Anwendung ist eine TYPO3-Extension und läuft nicht als Container. Sie wird per Composer in einer TYPO3-Installation eingebunden; Ablauf siehe `CONTRIBUTING.md`.
- Konfiguration der Umgebung ausschließlich über Umgebungsvariablen (z. B. `SOLR_HOST`, `SOLR_CORE`). Neue Variablen im README und in `.devfiles/.env` eintragen.
- Datenbankänderungen nur über `ext_tables.sql` und Upgrade-Wizards (`Classes/Updates/`), die im Deployment ausgeführt werden.
- Code und Abhängigkeiten kommen nur per Composer in die TYPO3-Installation, nie über das Dateisystem der Installation (z. B. `fileadmin/`).
- IP-Adressen nur anonymisiert loggen (IPv4 ohne letztes Oktett, IPv6 nur die ersten 48 Bit).
- Versionen folgen SemVer (`X.Y.Z`, ohne „v“). Tags und Releases legen nur Menschen an.

## No-Gos
- Keine Secrets, Tokens oder Passwörter in Code, Tests, Logs oder Commits.
- Keine personenbezogenen Daten oder Produktivdaten in Code, Tests, Beispieldaten, Logs oder Commits. Test- und Beispieldaten sind synthetisch.
- Upgrade-Wizards, die bereits in einem Release enthalten sind, nicht ändern.
- Tests nicht abschwächen oder löschen und Checks oder Scans nicht deaktivieren oder unterdrücken, um die CI grün zu bekommen.
- Keine Einträge in `.trivyignore`. Ausnahmen für Sicherheitsfunde legen nur Menschen an.
- `.github/` und `.devcontainer/` nur auf ausdrückliche Anweisung ändern.

## Projektspezifisch

### Projekt

- TYPO3-12-Extension `dla_opac_ng` (Composer `dla/dla_opac_ng`, Namespace `Dla\DlaOpacNg\`) für den Katalog (OPAC) des Deutschen Literaturarchivs Marbach. Produktivsystem: TYPO3 12.4, PHP 8.3, MariaDB 11.8, Apache.
- Enthält das Such-Plugin der früheren Extension `dla/find` (Fork von subugoe/typo3-find): Code unter `Classes/Find/` mit eigenem Namespace `Dla\Find\`, ViewHelper-Prefix `s:`, Plugin-Signatur `find_find`, TypoScript `plugin.tx_find`. Diese Bezeichner bleiben aus Kompatibilitätsgründen unverändert.
- Solr wird über Solarium angesprochen, ausschließlich über `Classes/Service/SolrConnection.php` und die Umgebungsvariablen `SOLR_HOST`, `SOLR_CORE` (optional `SOLR_TIMEOUT`).
- Projektsprache (README, Commits, Kommentare, PR-Beschreibungen) ist **Deutsch**.

### Wo liegt was

- `Classes/` – `Ajax/` (eID-Endpunkte), `Cli/` (Konsolenbefehl `dla_opac_ng:import`), `Controller/` (Plugins DlaStart, DlaCollection, DlaClassification), `Middleware/`, `Service/`, `Updates/` (Upgrade-Wizards), `Utility/`, `ViewHelpers/` (Prefix `dla:`), `Find/` (Such-Plugin)
- `Configuration/` – `TypoScript/setup.ts` und `constants.ts` (DLA-spezifisch, u.a. `queryFields`; importieren die Basiskonfiguration aus `TypoScript/Find/`), `Services.yaml`, `RequestMiddlewares.php`, `Commands.php`, `TCA/`
- `Resources/Private/` – Fluid-Templates, -Layouts, -Partials; Sprachdateien in `Language/` (XLIFF)
- `Tests/` – PHPUnit-Tests in `Unit/` und `Find/Unit/`, Test-Hilfen in `Support/`, Solr-Mock-Server und Cassetten in `Fixtures/Solr/`
- `dla-opac-tests/` – Submodul mit Playwright-End-to-End-Tests
- `.devfiles/` – Dateien der Entwicklungsumgebung (DB-Dump `init.sql`, `.env`, Site-, Apache- und PHP-FPM-Konfiguration, Importdaten, Fonts)
- `t3example/` – lokal erzeugte TYPO3-Instanz (in `.gitignore`); nicht bearbeiten, nicht committen

### Umgebung und Befehle

Im Devcontainer (Codespaces oder lokal) richtet `.devcontainer/startup.sh` die TYPO3-Instanz beim Erstellen ein. In der GitHub-Copilot-Umgebung geschieht das beim Start durch `.github/workflows/copilot-setup-steps.yml`. TYPO3 läuft dann unter http://127.0.0.1/ (Backend `/typo3`, admin / `Dla1337!`). PHP-Befehle mit PHP 8.3 ausführen (`php8.3`, falls `php` eine andere Version ist).

In anderen Umgebungen (z.B. Claude Code in der Cloud) ist nichts vorbereitet. `task test:php` funktioniert sofort; für die übrigen Tests vorher einmalig einrichten:

- `task install` – TYPO3-Instanz (einige Minuten; ist `r.mariadb.com` nicht erreichbar, wird MariaDB der Distribution verwendet)
- `git submodule update --init` und `npm ci` in `dla-opac-tests/`; ist Chromium vorinstalliert (z.B. `PLAYWRIGHT_BROWSERS_PATH`), entfällt `npx playwright install`
- Psalm: Docker-Daemon ggf. selbst starten (`dockerd`), dann `docker pull ghcr.io/psalm/psalm-github-actions:6.16.1`; scheitert der Download an der Netzwerkrichtlinie, Psalm nicht ausführen und im PR darauf hinweisen

- `task start` – MariaDB, Solr-Mock-Server, PHP-FPM und Apache starten (idempotent), falls http://127.0.0.1/ nicht antwortet
- `task cache` – TYPO3-Cache leeren; Code wirkt direkt (Symlink `t3example/packages/dla-dla_opac_ng`), aber nach Änderungen an TypoScript, `Services.yaml`, `ext_localconf.php` u.ä. nötig
- `task test:php` – PHPUnit-Tests ohne TYPO3-Instanz, wie in der CI (Job „PHPUnit“ in `.github/workflows/ci.yml`); einzelne Tests mit `task test:php -- --filter Name`
- `task test -- "katalog/"` – Playwright-Tests gegen die laufende Instanz; Tests, die externe Dienste aufrufen (Downloads von `digital.dla-marbach.de`, Datendienst `dataservice.dla-marbach.de`, z.B. „Merkliste“, „Multimedia“, `datendienst/`), schlagen ohne Netzwerkzugriff fehl
- Psalm-Sicherheitsanalyse wie in der CI (Job „Psalm“ in `.github/workflows/ci.yml`, Konfiguration `psalm.xml`), Image ist in der Copilot-Umgebung vorab geladen: `docker run --rm -v "$PWD":/app -w /app --entrypoint /composer/vendor/bin/psalm ghcr.io/psalm/psalm-github-actions:6.16.1 --taint-analysis --no-cache`
- Logs: `/tmp/typo3-apache-error.log`, `/tmp/typo3-php-error.log`, `t3example/var/log/`, `/tmp/solr-mock.log`

### Solr ohne Netzwerkzugriff

Ein echter Solr ist für Agenten nicht erreichbar. Instanz (Port 8984) und PHPUnit-Tests fragen stattdessen den Mock-Server `Tests/Fixtures/Solr/MockSolrServer.php` ab, der aufgezeichnete Antworten aus `Tests/Fixtures/Solr/cassettes/` liefert. Unbekannte Anfragen beantwortet er mit HTTP 404 und protokolliert sie in `Tests/Fixtures/Solr/missing-requests.log`. Seiten mit nicht aufgezeichneten Solr-Anfragen schlagen daher fehl; Bestandsübersicht, Systematik (Datenbank) und Backend funktionieren immer.

Cassetten können nur mit Solr-Tunnel aufgenommen werden (`task solr:record`, `task test:php:record`, `Tests/Fixtures/Solr/recorder.php`, siehe README). **Cassetten nie von Hand erstellen oder ändern.** Fehlen Cassetten für neue Tests, die benötigten Szenarien in `recorder.php` ergänzen und im PR darauf hinweisen, dass sie aufgenommen werden müssen.

### Konventionen

- Änderungen minimal halten und den Stil der umgebenden Dateien übernehmen.
- Neue Funktionalität und Bugfixes mit PHPUnit-Tests absichern (`Tests/Unit/`, Namespace `Dla\DlaOpacNg\Tests\`). Fluid-Partials mit `Tests/Support/FluidPartialTestCase.php` testen, Solr-Dokumente liefert `Tests/Support/SolrFixture.php`.
- PHP-Klassen gemäß PSR-4; Services werden über `Configuration/Services.yaml` autowired. Middlewares in `Configuration/RequestMiddlewares.php`, Konsolenbefehle in `Configuration/Services.yaml`/`Commands.php` registrieren.
- Fluid-ViewHelper erben von `TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper`, registrieren Argumente in `initializeArguments()` und heißen `<Name>ViewHelper.php`.
- UI-Texte nicht im Template kodieren, sondern als Übersetzungsschlüssel anlegen, immer in englischer (`locallang*.xlf`) und deutscher (`de.locallang*.xlf`) Sprachdatei.
- Keine Zugangsdaten oder internen Hostnamen in Code oder Konfiguration aufnehmen; die Dev-Zugangsdaten in `Taskfile.yml`/`.devfiles/`/README gelten nur lokal.
- README aktualisieren, wenn sich Abläufe oder Befehle ändern.
