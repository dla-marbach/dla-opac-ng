# Ablauf bei neuem Feature oder Fix

Die verbindlichen Grundsätze (Branching, Prüfungen, Secrets, Betrieb) stehen in der [DevOps-Vorlage](https://github.com/dla-marbach/devops). Regeln für KI-Agenten stehen in der `AGENTS.md`.

## Entwicklung

1. **Ticket:** Redmine-Ticket anlegen oder übernehmen. Darin die geplante Änderung, wesentliche Schritte und den Abschluss dokumentieren.
2. **Branch anlegen:** `git switch main && git pull && git switch -c feature/1234-kurzbeschreibung` (bzw. `fix/…`, mit Ticketnummer). Branches, die KI-Agenten selbst anlegen (z. B. `copilot/…`), behalten ihren Namen; die Ticketnummer steht dann im PR-Titel.
3. **Im Devcontainer entwickeln:** Codespace auf dem Branch oder lokal „Reopen in Container“. Tests ergänzen, Linter und Tests lokal ausführen (`task test:php`, Psalm und Playwright siehe README). Der Trivy-Scan läuft in der CI.
4. **Pushen und Pull Request nach `main` öffnen:**
   - Titel mit Ticketnummer ohne „#“, z. B. „CSV-Export für Ergebnisse (Ticket 1234)“. `#1234` würde auf GitHub verlinken statt auf Redmine.
   - Beschreibung: Link zum Ticket, was sich ändert und warum, Breaking Changes und manuelle Upgrade-Schritte.
5. **Copilot-Review anfordern** (unter „Reviewers“). Hinweise umsetzen oder im PR begründen, warum nicht.
6. **CI abwarten:** Alle Checks müssen grün sein, bei Funden nachbessern. Ist `main` weitergegangen, „Update branch“ und erneut abwarten.
7. **Squash-Merge:** Prüfen, dass die Ticketnummer in der Commit-Nachricht steht. Danach lokal aufräumen: `git switch main && git pull --prune && git branch -D feature/1234-kurzbeschreibung`

Die Extension läuft nicht als Container, sondern wird per Composer in die TYPO3-Installationen der Test- und der Produktivumgebung eingebunden. Release, Deployment und Rollback folgen deshalb den Regeln für [Projekte ohne Container-Betrieb](https://github.com/dla-marbach/devops#4-projekte-ohne-container-betrieb).

## Release

Releases und Tags legen nur Menschen an.

1. **CI prüfen:** Der letzte Lauf auf `main` muss grün sein.
2. **Version festlegen:** Patch (`6.0.7`) für Fixes, Minor (`6.1.0`) für Features, Major (`7.0.0`) für Breaking Changes (z. B. neue TYPO3-Hauptversion, entfernte Plugins oder Bezeichner, Änderungen, die angepasstes TypoScript erfordern). Maßgeblich ist die größte Änderung seit dem letzten Release. Tags ohne „v“, siehe [Projektspezifisch](#projektspezifisch).
3. **Release anlegen:** „Releases“ → „Draft a new release“, neuen Tag auf `main`, „Generate release notes“. Breaking Changes und manuelle Upgrade-Schritte ergänzen, z. B. Datenbankänderungen, Upgrade-Wizards, geänderte TypoScript-Einbindung, neue Umgebungsvariablen, erneuter Import von Systematik oder Tektonik (`dla_opac_ng:import`).
4. **Veröffentlichen:** „Publish release“. Ein Release-Workflow läuft nicht; die TYPO3-Installationen beziehen die Version per Composer über den Tag. Tags werden nie verschoben oder wiederverwendet. Ist ein Release fehlerhaft, den Fix als neues Patch-Release ausliefern.

## Deployment

Zuerst in der Testumgebung, danach mit derselben Version in der Produktion. Die TYPO3-Installation verweist per Composer-Constraint auf eine feste Version (z. B. `"dla/dla_opac_ng": "6.0.7"`), nie auf `dev-main`. Auf dem Server im Verzeichnis der TYPO3-Installation (mit deren `composer.json`):

1. **Vorbereiten:** Release Notes aller Versionen seit der installierten lesen.
2. **Sichern:** `composer.json` und `composer.lock` der Installation sowie die TYPO3-Datenbank (logische Sicherung per Dump) in das Sicherungsverzeichnis kopieren:

   ```sh
   BACKUP=<sicherungsverzeichnis>/dla_opac_ng-$(date +%F_%H%M)
   mkdir -p "$BACKUP"
   cp -a composer.json composer.lock "$BACKUP"/
   mariadb-dump --single-transaction <datenbank> | gzip > "$BACKUP"/db.sql.gz
   ```

3. **Version setzen:** `composer require "dla/dla_opac_ng:6.0.7" --no-interaction`
4. **Datenbank und Upgrade-Wizards:** `vendor/bin/typo3 extension:setup` (Tabellen und Felder aus `ext_tables.sql`), anschließend `vendor/bin/typo3 upgrade:list` und ausstehende Wizards mit `vendor/bin/typo3 upgrade:run` ausführen. Manuelle Upgrade-Schritte aus den Release Notes ausführen, falls vorhanden.
5. **Cache leeren und prüfen:** `vendor/bin/typo3 cache:flush`. Katalog, Trefferliste, Detailansicht, Bestandsübersicht und Systematik im Browser testen und das TYPO3-Log (`var/log/`) prüfen. In der Testumgebung zusätzlich die Playwright-Tests gegen die Instanz ausführen: `BASE_URL=<url-der-testumgebung> task test -- "katalog/"`
6. **Produktion:** Läuft das Release in der Testumgebung fehlerfrei, die Schritte 1–5 auf dem Produktionsserver mit derselben Version wiederholen. Sonst die Testumgebung zurückrollen und den Fix als neues Patch-Release ausliefern.

## Rollback

Die alte Version darf nie gegen ein Datenbankschema laufen, das sie nicht kennt.

1. **Alte Version wiederherstellen:** `composer.json` und `composer.lock` aus der Sicherung zurückkopieren, dann `composer install --no-interaction`.
2. **Datenbank zurückspielen**, falls das neue Release das Schema geändert oder Upgrade-Wizards ausgeführt hat. Änderungen seit dem Update (z. B. im Backend) gehen dabei verloren.

   ```sh
   gunzip -c <sicherungsverzeichnis>/dla_opac_ng-<zeitstempel>/db.sql.gz | mariadb <datenbank>
   ```

3. **Cache leeren und prüfen:** `vendor/bin/typo3 cache:flush`, prüfen wie beim Deployment.

## Projektspezifisch

Begründete Abweichungen von den Grundsätzen der DevOps-Vorlage:

- **Versionsschema ohne „v“:** Tags und Releases heißen `X.Y.Z` (z. B. `6.0.7`) statt `vX.Y.Z`. Das ist im TYPO3-Umfeld üblich, und alle bisherigen Releases sind so benannt.
- **Kein Lockfile:** Als Extension legt das Repo keine Versionen der Abhängigkeiten fest, das tut das `composer.lock` der TYPO3-Installation. Für den Trivy-Scan erzeugt die CI ein `composer.lock` mit den Versionen, die eine Neuinstallation aktuell bekäme. Dependabot passt nur die Versionsbereiche in `composer.json` an.
- **Fremd-Actions in `copilot-setup-steps.yml`:** Die Umgebung des Copilot-Agenten wird mit `arduino/setup-task`, `shivammathur/setup-php` und `actions/setup-node` eingerichtet (per Commit-SHA gepinnt). Die CI selbst nutzt PHP und Composer aus dem Runner-Image.
- **Psalm** läuft in der CI als zusätzliche Sicherheitsanalyse (Taint-Analyse) mit festem Image-Tag; die Ergebnisse werden unter „Security“ → „Code scanning“ angezeigt.
