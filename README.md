# dla-opac-ng

TYPO3-Extension für den Katalog des Deutschen Literaturarchivs Marbach https://www.dla-marbach.de/katalog

Das integrierte Such-Plugin basiert auf subugoe/typo3-find (siehe [Herkunft und Credits](#herkunft-und-credits)).

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

## Nutzungsstatistik mit Matomo

Die Extension erfasst eine datensparsame Nutzungsstatistik des Katalogs und sendet sie serverseitig an Matomo
(Bulk-Tracking-API). Im Browser läuft kein Tracking-JavaScript, es werden keine Cookies gesetzt, und Matomo erhält
weder IP-Adressen noch User-Agents der Besucher.

Ablauf:

1. Die Controller der Plugins melden, was ein Request getan hat
   ([Classes/Statistics/StatisticsCollector.php](Classes/Statistics/StatisticsCollector.php)).
2. Die [StatisticsMiddleware](Classes/Middleware/StatisticsMiddleware.php) liegt innerhalb der Proof-of-Work-Middleware:
   Requests, die mit der Challenge-Seite beantwortet werden, erreichen sie nicht und zählen nicht als Zugriffe. Nach
   erfolgreicher Antwort (Status < 400, GET/POST, kein Prefetch) hängt sie eine Zeile an die Rohdatei der aktuellen
   Minute an: `var/opac-stats/raw/YYYYMMDDHHii-<host>.ndjson` (UTC). Im Request gibt es dabei weder Netzwerk- noch
   Datenbankzugriffe; ist Matomo nicht erreichbar, merkt der Katalog davon nichts.
3. Der Befehl `dla_opac_ng:stats:matomo-push` sendet abgeschlossene Minuten in Paketen von 500 Requests an
   `matomo.php` und löscht die Rohdateien danach. Der Zeitpunkt des Zugriffs geht als `cdt` mit. Der Fortschritt
   wird pro Datei vermerkt, sodass ein abgebrochener Lauf ohne Doppelzählungen fortgesetzt wird.

Was an Matomo geht ([Classes/Statistics/MatomoRequestBuilder.php](Classes/Statistics/MatomoRequestBuilder.php)):

| Katalog | Matomo (Website »Katalog«, `opacMatomoSiteId`) |
|---|---|
| Startseite, Trefferliste, Detailseite, Bestandsübersicht, Systematik | Seitenaufruf mit `url` (ohne Query-Parameter) und `action_name` `Katalog/<Bereich>` |
| Suchbegriff (Trefferliste, Suche in Bestandsübersicht/Systematik) | Site Search: `search` (normalisiert), `search_cat` (`Katalog (einfach)`, `Katalog (erweitert)`, `Bestandsübersicht`, `Systematik`), `search_count` (Trefferzahl) |
| Facettenfilter | je Filter ein Event: Kategorie `Facette` bzw. `Facette (ausgeschlossen)`, Aktion = Facette, Name = Wert |
| Herkunft von anderen Websites | `urlref` nur mit dem Hostnamen (`https://<host>/`) |
| Merkmale | Custom Dimensions (Scope »Aktion«), siehe `opacMatomoDimensions` |

| Katalog | Matomo (Website »Katalog – Zähler«, `opacMatomoAuxSiteId`, optional) |
|---|---|
| Proof-of-Work: ausgestellte und gelöste Challenges | Event `Proof-of-Work` / `Challenge ausgestellt` bzw. `Challenge gelöst` |
| Zugriffe von Bots (per User-Agent erkannt) | Event `Bot` / `<Bereich>` |
| AJAX-Endpunkte (nur der Endpunkt, keine Eingaben) | Event `AJAX` / `<Endpunkt>` / `extern` bzw. `intern` |

Die Zähler werden pro Stunde zusammengefasst, die Anzahl steht im **Ereigniswert** (Bericht »Ereignisse«, Spalte
»Summe Ereigniswert«). Ohne `opacMatomoAuxSiteId` werden sie verworfen; Bots fehlen dann in der Statistik.

Datenschutz:

* Jede Katalogseite ist in Matomo ein eigener Besuch mit zufälliger Besucher-ID, fester IP-Adresse (`0.0.0.0`) und
  festem User-Agent. Matomo zählt damit Aktionen, keine Besucher: »Besuche« entspricht den Seitenaufrufen.
* Ob ein Zugriff intern ist (`campusRanges`, `sandboxRanges`, `staffRanges`) oder von einem Bot stammt, wird nur
  während des Requests in TYPO3 ausgewertet. Die Rohdateien enthalten keine IP-Adressen, User-Agents, Cookies oder
  Query-Parameter.
* Matomo kennt keine Mindesthäufigkeit für Suchbegriffe: Auch einmalig gesuchte Begriffe bleiben in den
  archivierten Berichten (siehe Einstellungen zum Löschen alter Berichte in Matomo).
* Ein kurzer Hinweis auf die anonyme, serverseitige Nutzungsstatistik in der Datenschutzerklärung ist sinnvoll.

### Einrichtung im Hauptprojekt (dla-t3-www)

Umgebungsvariablen (z.B. in der `.env` der TYPO3-Instanz, wie `opacPowEnabled`):

| Variable | Standard | |
|---|---|---|
| `opacStatsEnabled` | – | Erfassung einschalten (`1`) |
| `opacStatsPath` | `var/opac-stats` der Instanz | optional, anderes Verzeichnis für die Rohdateien, z.B. ein gemeinsames Verzeichnis mehrerer Webserver; nicht im Webroot |
| `opacMatomoUrl` | – | Basis-URL der Matomo-Installation (ohne `matomo.php`) |
| `opacMatomoToken` | – | `token_auth` eines Matomo-Benutzers mit Schreibrecht auf beide Websites (nötig für `cip` und `cdt`) |
| `opacMatomoSiteId` | – | ID der Website »Katalog« |
| `opacMatomoAuxSiteId` | – | optional, ID der Website »Katalog – Zähler« |
| `opacMatomoDimensions` | – | optional, Zuordnung der Merkmale zu den IDs der Custom Dimensions, z.B. `area:1,audience:2,mode:3,facets:4,hits:5` |

Merkmale für `opacMatomoDimensions` (nicht zugeordnete Merkmale werden nicht gesendet):

| Schlüssel | Werte |
|---|---|
| `area` | Bereich: `Startseite`, `Trefferliste`, `Detailseite`, `Bestandsübersicht`, `Systematik` |
| `audience` | `extern`, `intern` |
| `language` | Sprache der Seite (`de`, `en`) |
| `referrer` | `eigene Website`, `andere Website`, `ohne` |
| `mode` | Trefferliste: `einfach`, `erweitert`, `ohne Suchbegriff`; Detailseite: `aus Trefferliste`, `direkt`, `PDF`, `nicht gefunden`; Bestandsübersicht/Systematik: `Einstieg`, `Datensatz`, `Knoten`, `Suche` |
| `fields` | genutzte Suchfelder (Kombination, sortiert) |
| `facets` | genutzte Facetten (Kombination der IDs, sortiert) |
| `sort` | Sortierung (`Standard`, wenn nicht gewählt) |
| `page` | Ergebnisseite |
| `perPage` | Treffer pro Seite |
| `hits` | Trefferzahl in Größenklassen (`0`, `1`, `2-10`, `11-100`, `101-1000`, `1001-10000`, `>10000`) |

Push regelmäßig ausführen, z.B. jede Minute per Crontab des Webserver-Benutzers:

```
* * * * * cd /pfad/zur/instanz && vendor/bin/typo3 dla_opac_ng:stats:matomo-push --quiet
```

Bei mehreren Webservern läuft der Befehl nur auf einem Knoten, und alle Knoten schreiben in ein gemeinsames
`opacStatsPath`. Parallele Läufe verhindert eine Sperrdatei. Solange der Push nicht läuft (oder Matomo nicht
erreichbar ist), sammeln sich die Rohdateien an; sie werden beim nächsten erfolgreichen Lauf nachgeliefert. Liegen
Zugriffe dabei mehr als einen Tag zurück, müssen die Berichte der betroffenen Tage in Matomo neu archiviert werden
(`./console core:invalidate-report-data`).

### Einrichtung in Matomo

1. Websites anlegen: »Katalog« (Zeitzone Europe/Berlin, Site Search aktiviert) und optional »Katalog – Zähler«.
2. Technischen Benutzer anlegen mit Schreibrecht nur auf diese Websites und dafür einen `token_auth` erzeugen.
3. Custom Dimensions mit Scope »Aktion« für die gewünschten Merkmale anlegen und die IDs in `opacMatomoDimensions`
   eintragen. Matomo stellt pro Scope standardmäßig 5 Plätze bereit, weitere mit
   `./console customdimensions:add-custom-dimension --scope=action --count=6`.
4. In `config/config.ini.php` unter `[General]` die Archivierung per Cron einrichten und die Zeilenlimits der
   Berichte erhöhen, da Facettenwerte, Suchbegriffe und Datensatz-IDs sonst schnell unter »Andere« landen:
   ```ini
   enable_browser_archiving_triggering = 0   ; Cron: ./console core:archive (stündlich)
   datatable_archiving_maximum_rows_events = 5000
   datatable_archiving_maximum_rows_subtable_events = 2000
   datatable_archiving_maximum_rows_site_search = 5000
   datatable_archiving_maximum_rows_actions = 2000
   datatable_archiving_maximum_rows_subtable_actions = 5000
   datatable_archiving_maximum_rows_custom_dimensions = 1000
   ```
5. Datenschutz: Besucher-Log und Besucherprofil deaktivieren, Geolokalisierung abschalten, alte Rohdaten nach
   z.B. 90 Tagen löschen. Ausgeschlossene IP-Bereiche in Matomo greifen nicht (Matomo sieht keine echten IPs);
   interne Zugriffe über das Merkmal `audience` filtern.
6. Segmente anlegen, z.B. »Extern« (`audience` = `extern`).

### Lokal testen

In [.devfiles/.env](.devfiles/.env) ist `opacStatsEnabled=1` gesetzt. Nach einigen Aufrufen im Katalog liegen
Rohdaten in `t3example/var/opac-stats/raw/`. Ohne Matomo zeigt `--dry-run` die Requests, die gesendet würden
(nichts wird gelöscht); mit `--include-current-minute` auch die laufende Minute:

```
t3example/vendor/bin/typo3 dla_opac_ng:stats:matomo-push --dry-run --include-current-minute
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
