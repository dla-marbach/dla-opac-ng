# dla-opac-ng

TYPO3-Extension für den Katalog des Deutschen Literaturarchivs Marbach https://www.dla-marbach.de/katalog

Die Extension basiert auf subugoe/typo3-find (siehe [Herkunft und Credits](#herkunft-und-credits)).

## Installation

Die Entwicklungsumgebung (Ubuntu 24.04) wird mit [Task](https://taskfile.dev) eingerichtet ([Taskfile.yml](Taskfile.yml)):
PHP 8.3, Composer, Apache (mit PHP-FPM) und MariaDB (wie im Produktivsystem 11.8, sonst die Version der
Distribution) werden per `apt` installiert, eine bereits auf Port 3306 laufende Datenbank wird verwendet.
In einem Codespace geschieht das automatisch ([.devcontainer/startup.sh](.devcontainer/startup.sh)), sonst mit:

```
task install
```

| Task | |
|---|---|
| `task install` | PHP/Composer/Apache/MariaDB installieren (falls nötig), TYPO3 in `t3example/` aufsetzen, Extension einbinden und starten |
| `task start` / `task stop` | MariaDB, Solr-Mock-Server, PHP-FPM und Apache starten bzw. Solr-Mock-Server, PHP-FPM und Apache stoppen (MariaDB läuft weiter); im Codespace startet `task start` automatisch |
| `task cache` | TYPO3-Cache leeren, nötig nach Änderungen z.B. an TypoScript, `Configuration/Services.yaml` oder `ext_localconf.php` |
| `task reinstall` | TYPO3-Instanz und Datenbank löschen und neu aufsetzen |

Die Extension ist per Composer-Path-Repository eingebunden (`t3example/packages/dla-dla_opac_ng` ist ein Symlink auf
dieses Repository), Änderungen am Code wirken also direkt. Konfiguration: [.devfiles/apache.conf](.devfiles/apache.conf),
[.devfiles/php-fpm.conf](.devfiles/php-fpm.conf) und Umgebungsvariablen in [.devfiles/.env](.devfiles/.env)
(werden mit `task start` übernommen). Logs: `/tmp/typo3-apache-error.log`, `/tmp/typo3-php-error.log`,
`t3example/var/log/` und `/tmp/solr-mock.log`.

Für den Copilot-Agent richtet [.github/workflows/copilot-setup-steps.yml](.github/workflows/copilot-setup-steps.yml)
dieselbe Umgebung ein.

Anweisungen für KI-Agenten stehen in [AGENTS.md](AGENTS.md).

## GUIs

Katalog: [http://127.0.0.1/katalog](http://127.0.0.1/katalog)
* Trefferliste: http://127.0.0.1/find
* Bestandsübersicht: http://127.0.0.1/bestandsuebersicht
* Thematischer Sucheinstieg: http://127.0.0.1/systematik

TYPO3 Backend: [http://127.0.0.1/typo3](http://127.0.0.1/typo3)
* admin
* Dla1337!

## Solr

Alle Solr-Zugriffe (Such-Plugin, ViewHelper, Ajax-Endpunkte) laufen über
[Classes/Service/SolrConnection.php](Classes/Service/SolrConnection.php) und werden ausschließlich über
Umgebungsvariablen konfiguriert:

| Variable | Beispiel | |
|---|---|---|
| `SOLR_HOST` | `http://127.0.0.1:8984/solr/` | Basis-URL inkl. Solr-Kontext |
| `SOLR_CORE` | `internformat` | Name des Cores |
| `SOLR_TIMEOUT` | `10` | optional, Timeout in Sekunden (Standard: 10) |

### Solr-Mock-Server

In der Entwicklungsumgebung und in den PHPUnit-Tests wird statt eines echten Solr der Solr-Mock-Server
[Tests/Fixtures/Solr/MockSolrServer.php](Tests/Fixtures/Solr/MockSolrServer.php) abgefragt. Er beantwortet Anfragen
aus aufgezeichneten, echten Solr-Antworten (Cassetten) in [Tests/Fixtures/Solr/cassettes](Tests/Fixtures/Solr/cassettes),
die dauerhaft im Repository liegen. So funktionieren Instanz und Tests auch ohne Solr-Zugriff (z.B. für Copilot
und Claude), allerdings nur für bereits aufgezeichnete Anfragen. Unbekannte Anfragen beantwortet er mit HTTP 404
und protokolliert sie in `Tests/Fixtures/Solr/missing-requests.log`.

Die TYPO3-Instanz nutzt den Mock-Server auf Port 8984 (`task start`). Besteht ein [Tunnel](#tunnel-zum-solr) zum
echten Solr auf `127.0.0.1:8983`, reicht er alle Anfragen unverändert dorthin durch (Antwort-Header
`X-Solr-Mock: proxy`), ohne dass umgeschaltet werden muss. Die PHPUnit-Tests starten einen eigenen Mock-Server.

### Cassetten aufnehmen

Neue Cassetten lassen sich nur bei bestehendem Tunnel aufnehmen; anschließend die neuen Dateien committen.

| Befehl | Ablage in `cassettes/` | Inhalt |
|---|---|---|
| `php Tests/Fixtures/Solr/recorder.php [szenario ...]` | `*.json` | Szenarien (Pfad + Query-Parameter) aus [recorder.php](Tests/Fixtures/Solr/recorder.php), ohne Argumente alle |
| `task test:php:record` | `subqueries/` | fehlende Anfragen beim Ausführen der PHPUnit-Tests, v.a. Unterabfragen der Partials (`dla:countFromSolr`, `dla:fromSolr`) |
| `task solr:record` | `typo3/` | fehlende Anfragen der TYPO3-Instanz beim Aufruf im Browser bzw. mit Playwright; danach mit `task solr:stop solr:start` zurückwechseln |

Hinweise:

* Der Anfang der Datumsfacette in den Trefferlisten hängt vom aktuellen Jahr ab (`NOW/YEAR-…YEARS`): Nach dem
  Jahreswechsel die Cassetten in `typo3/` neu aufnehmen.
* Nach Änderungen an den `queryFields` in [Configuration/TypoScript/setup.ts](Configuration/TypoScript/setup.ts) den
  Ordner `subqueries/` vorher löschen, damit keine veralteten Cassetten zurückbleiben.

### Tunnel zum Solr

Der Tunnel wird vom lokalen PC aus (mit aktiver VPN-Verbindung zum DLA-Testsystem) per GitHub CLI
[gh](https://cli.github.com) in den Codespace aufgebaut und endet dort auf `127.0.0.1:8983`:

```
gh codespace ssh -- -N -o ExitOnForwardFailure=yes -o ServerAliveInterval=30 -o ServerAliveCountMax=3 -R 127.0.0.1:8983:serene.dla-marbach.de:8983
```

## Tests

### Playwright

End-to-End-Tests aus dem Submodul [dla-opac-tests](dla-opac-tests) gegen die laufende Instanz:

```
task test -- "katalog/"                                      # alle Tests für den Katalog
task test -- -g "robots"                                     # einzelner Test
BASE_URL=https://www.dla-marbach.de task test -- -g "robots" # Vergleich mit dem Produktivsystem
```

### PHPUnit

Die PHPUnit-Tests (`Tests/Unit`, Tests des Such-Plugins in `Tests/Find/Unit`) brauchen weder TYPO3-Instanz noch
Datenbank oder Solr-Tunnel. Sie laufen mit lokalem PHP gegen den [Solr-Mock-Server](#solr-mock-server), wie in der
CI ([.github/workflows/phpunit.yml](.github/workflows/phpunit.yml)):

```
task test:php
task test:php -- --filter MediaPlayer   # einzelne Tests
```

[Tests/Unit/Partials](Tests/Unit/Partials) rendert einzelne Fluid-Partials aus
[Resources/Private/Partials](Resources/Private/Partials) und prüft das erzeugte HTML:

* [Tests/Support/FluidPartialTestCase.php](Tests/Support/FluidPartialTestCase.php) baut einen minimalen
  Fluid-`RenderingContext` ohne TYPO3-Bootstrap. `f:translate` (immer Default-Sprache) und `f:link.action`
  (Platzhalter-Link `#test-link`) sind dafür durch Test-Varianten in
  [Tests/Support/FluidViewHelperOverrides](Tests/Support/FluidViewHelperOverrides) ersetzt. Tests schlagen fehl,
  wenn eine Solr-Anfrage ohne Cassette ankommt (sonst würde z.B. `dla:countFromSolr` still `0` anzeigen).
* [Tests/Support/SolrFixture.php](Tests/Support/SolrFixture.php) liefert echte Solarium-Dokumente bzw.
  Trefferlisten aus den Cassetten (`detail-*.json`, `resultlist-*.json`), die echte TypoScript-Konfiguration
  (`SolrFixture::settings()`, u.a. `queryFields`) und setzt `SOLR_HOST`/`SOLR_CORE` auf den Mock-Server.
* `dla:collection` (Bestandsbaum aus der Datenbank) wird durch `FakeCollectionService` ersetzt, Testdaten über
  `FakeCollectionService::$parents`.

## Datenbankdump für TYPO3-Grundkonfiguration

Bei der Installation (task install) wird ein Datenbank-Dump [.devfiles/init.sql](.devfiles/init.sql) eingespielt. Bei einer neuen TYPO3-Version muss dieser Dump ggf. manuell neu erstellt werden.

### TYPO3-Grundkonfiguration ohne Datenbankdump

1. Menü Maintenance / Analyze Database Structure: Alle Vorschläge übernehmen (ggf. mehrmals)

2. Menü Page: Neue Seite auf DLA OPAC ziehen und `Start` nennen

  * Start anklicken und "Create new content" auswählen
  * Menu > Sitemap auswählen
  * Speichern
  * Kontextmenü im Seitenbaum für diese Seite aufrufen und Enable auswählen

3. Menü Page: Neue Seite auf Start ziehen und `Katalog` nennen

  * Katalog anklicken und "Create new content" auswählen
  * Plugin TYPO3 Find auswählen
  * Reiter Plugin im Pulldown "DlaStart" auswählen
  * Speichern
  * Kontextmenü im Seitenbaum für diese Seite aufrufen und Enable auswählen

4. Menü Page: Neue Unterseite von Katalog und `Find` nennen

  * find anklicken und "Create new content" auswählen
  * Plugin TYPO3 Find auswählen
  * Speichern
  * Edit page properties > URL Segment /katalog entfernen
  * Kontextmenü im Seitenbaum für diese Seite aufrufen und Enable auswählen

5. Menü Page: Neue Unterseite von Katalog und `Bestandsübersicht` nennen

  * Bestandsuebersicht anklicken und "Create new content" auswählen
  * Plugin TYPO3 Find auswählen
  * Reiter Plugin im Pulldown "DlaCollection" auswählen
  * Speichern
  * Edit page properties > URL Segment /katalog entfernen
  * Kontextmenü im Seitenbaum für diese Seite aufrufen und Enable auswählen

6. Menü Page: Neue Unterseite von Katalog und `Systematik` nennen

  * Systematik anklicken und "Create new content" auswählen
  * Plugin TYPO3 Find auswählen
  * Reiter Plugin im Pulldown "DlaClassification" auswählen
  * Speichern
  * Edit page properties > URL Segment /katalog entfernen
  * Kontextmenü im Seitenbaum für diese Seite aufrufen und Enable auswählen

7. Menü Page: Neuen Ordner unterhalb von Katalog und `Daten` nennen

  * Kontextmenü im Seitenbaum für diese Seite aufrufen und Enable auswählen

8. Menü Typoscript für Seite `Start`

  * Oben im Pulldown "Edit Typoscript Record" aufrufen
  * Create a root TypoScript record
  * Edit the whole Typoscript record
  * Reiter Advanced Options / Include TypoScript sets auswählen: Alle Fluid Items und "Find configuration for DLA Catalog (dla_opac_ng)"
  * Reiter General im Bereich Setup einfügen:

    ```
    page.5 > // Flux-Renderer überschreiben, sonst Ausgabe doppelt
    page = PAGE
    page {
      typeNum = 0
      includeCSS.site = EXT:dla_opac_ng/Resources/Public/CSS/site.min.css
      10 = FLUIDTEMPLATE
      10 {
        file = EXT:dla_opac_ng/Resources/Private/Templates/Page/Start.html
        section = Main
        layoutRootPaths.10 = EXT:dla_opac_ng/Resources/Private/Layouts/
        partialRootPaths.10 = EXT:dla_opac_ng/Resources/Private/Partials/
      }
    }
    ```

9. Menü Typoscript für Seite `Katalog`

  * Create an additional TypoScript record
  * Edit the whole Typoscript record
  * Reiter Advanced Options / Include TypoScript sets auswählen: Alle Fluid Items und "Find configuration for DLA Catalog (dla_opac_ng)"
  * Reiter General im Bereich Setup einfügen:

    ```
    plugin.tx_find.settings.mainPageUid = 2
    plugin.tx_find.settings.indexPageUid = 3
    plugin.tx_find.settings.collectionUid = 4
    plugin.tx_find.settings.helpUid = 1
    plugin.tx_find.settings.orderlink = https://www.dla-marbach.de/cgi-bin/aDISCGI/kallias_prod/lib/ng-ausleihe.html?test=test

    # Browser-Zurück ermöglichen
    config.additionalHeaders {
        20.header = Cache-control: private, must-revalidate
    }
    ```

10. Menü Typoscript für Seite `find`

  * Create an additional TypoScript record
  * Setup auswählen und einfügen:

    ```
    # Trefferliste noindex,nofollow; Detailseite nur nofollow
    page.meta.robots = noindex, nofollow
    page.meta.robots.replace = 1
    [traverse(request.getQueryParams(), 'tx_find_find/action') == 'detail']
      page.meta.robots = nofollow
      page.meta.robots.replace = 1
    [END]

    # Links rel="canonical" und rel="alternate" deaktivieren
    config.disableCanonical = 1
    config.disableHrefLang = 1
    ```

### Datenbankdump erstellen

1. TYPO3 Backoffice > Maintenance > Clear Persistent Database Tables (dabei "be_sessions" zuletzt)

2. init.sql ersetzen:

```
task db:export
```

## Herkunft und Credits

Das Such-Plugin (Code unter [Classes/Find](Classes/Find), [Tests/Find](Tests/Find),
[Configuration/TypoScript/Find](Configuration/TypoScript/Find) sowie Teile von `Resources/`) basiert auf der
TYPO3-Extension [subugoe/typo3-find](https://github.com/subugoe/typo3-find) von Sven-S. Porst, Ingo Pfennigstorf
und weiteren Beitragenden der Niedersächsischen Staats- und Universitätsbibliothek Göttingen (SUB Göttingen).
Sie wurde als Fork [dla-marbach/typo3-find](https://github.com/dla-marbach/typo3-find) von effective WEBWORK
und Open Culture Consulting für den Katalog des DLA Marbach weiterentwickelt und mit vollständiger
Git-Historie in dieses Repository übernommen (`git log -- Classes/Find`).

Der übernommene Code steht unter der GNU General Public License, Version 2 oder später
([LICENSES/find-GPL-2.0-or-later.txt](LICENSES/find-GPL-2.0-or-later.txt)); die Extension insgesamt steht unter
der GNU General Public License, Version 3 oder später ([LICENSE](LICENSE)).
