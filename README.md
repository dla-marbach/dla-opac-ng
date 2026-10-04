# dla-opac-ng

TYPO3-Extension für den Katalog des Deutschen Literaturarchivs Marbach https://www.dla-marbach.de/katalog

Die Extension enthält seit Version 6 auch das Such-Plugin der früheren Extension `dla/find`
(siehe [Herkunft und Credits](#herkunft-und-credits) und [Upgrade auf Version 6](#upgrade-auf-version-6)).

## Installation

Einmalig nach Erstellen eines Codespaces aufrufen:

```
task install
```

## Verbindung zum Solr

Lokal muss GitHub CLI [gh](https://cli.github.com) installiert sein.

### DLA Testsystem

Auf dem lokalen PC mit aktiver VPN-Verbindung:

```
gh codespace ssh -- -N -o ExitOnForwardFailure=yes -o ServerAliveInterval=30 -o ServerAliveCountMax=3 -R 127.0.0.1:18983:serene.dla-marbach.de:8983
```

### Entwicklungssystem

1. Codespace mit Repo https://github.com/dla-marbach/dla-opac-transform starten und im Codespace `task solr` ausführen

2. Auf dem lokalen PC:

```
SOLR="127.0.0.1"
gh codespace ssh -- -N -o ExitOnForwardFailure=yes -o ServerAliveInterval=30 -o ServerAliveCountMax=3 -R 127.0.0.1:18983:$SOLR:8983
```

## GUIs

Katalog: [http://127.0.0.1/katalog](http://127.0.0.1/katalog)
* Trefferliste: http://127.0.0.1/find
* Bestandsübersicht: http://127.0.0.1/bestandsuebersicht
* Thematischer Sucheinstieg: http://127.0.0.1/systematik

TYPO3 Backend: [http://127.0.0.1/typo3](http://127.0.0.1/typo3)
* admin
* Dla1337!

## Tests

Alle Playwright-Tests für den Katalog ausführen:

```
task test -- "katalog/"
```

Einzelnen Test ausführen:

```
task test -- -g "robots"
```

Test zum Vergleich auf Produktivsystem ausführen:

```
BASE_URL=https://www.dla-marbach.de task test -- -g "robots"
```

## PHPUnit-Tests mit Solr-Mockdaten

Die PHP-Unit-Tests (`Tests/`, inkl. der Tests des Such-Plugins in [Tests/Find](Tests/Find)) laufen gegen
einen lokalen Solr-Mock-Server und benötigen dafür **keinen** Solr-Tunnel:

```
task test:php
```

Der Mock-Server ([Tests/Fixtures/Solr/MockSolrServer.php](Tests/Fixtures/Solr/MockSolrServer.php)) beantwortet
Anfragen anhand aufgezeichneter, echter Solr-Antworten ([Tests/Fixtures/Solr/cassettes](Tests/Fixtures/Solr/cassettes)),
die dauerhaft im Repository liegen. Dadurch bleiben die Tests auch dann nutzbar, wenn kein Solr-Zugriff
mehr besteht.

Neue Fixtures aufnehmen (nur möglich, solange der Solr-Tunnel aktiv ist, siehe oben):

```
php Tests/Fixtures/Solr/recorder.php
```

Dazu vorher das gewünschte Szenario (Pfad + Query-Parameter) in
[Tests/Fixtures/Solr/recorder.php](Tests/Fixtures/Solr/recorder.php) ergänzen. Bei unbekannten Anfragen
antwortet der Mock-Server mit HTTP 404 und protokolliert die fehlende Anfrage in
`Tests/Fixtures/Solr/missing-requests.log`. Mit Szenarionamen als Argumente
(`php Tests/Fixtures/Solr/recorder.php detail-person`) werden nur diese Szenarien neu aufgezeichnet.

Unterabfragen, die Partials selbst per `dla:countFromSolr`/`dla:fromSolr` absetzen (z.B. die Zähler in
`Display/Detail/Normdata/*`), müssen nicht einzeln als Szenario gepflegt werden: Der Task

```
task test:php:record
```

führt die Tests mit `SOLR_RECORD_BASE` (Solr-Tunnel nötig) aus. Der Mock-Server leitet dann jede noch
unbekannte Anfrage an den echten Solr weiter und legt die Antwort als Cassette in
[Tests/Fixtures/Solr/cassettes/subqueries](Tests/Fixtures/Solr/cassettes/subqueries) ab (Dateiname: Query-Auszug +
Hash), von wo aus sie der Task in den Workspace kopiert. Nach Änderungen an den `queryFields` in
[Configuration/TypoScript/setup.ts](Configuration/TypoScript/setup.ts) den Ordner `subqueries` vorher löschen
(auch unter `t3example/.local-ext/dla-dla_opac_ng/Tests/Fixtures/Solr/cassettes/`), damit keine
veralteten Cassetten zurückbleiben.

## Fluid-Partial-Rendering-Tests

Zusätzlich zu den Service-/Ajax-Tests gibt es [Tests/Unit/Partials](Tests/Unit/Partials), die einzelne
Fluid-Partials dieser Extension (aus [Resources/Private/Partials](Resources/Private/Partials)) direkt
rendern und den erzeugten HTML-Ausschnitt prüfen. Laufen ebenfalls über `task test:php`, ohne DB/Solr-Tunnel.

Basis ist [Tests/Support/FluidPartialTestCase.php](Tests/Support/FluidPartialTestCase.php): baut eine
minimale Fluid-`RenderingContext` (ohne vollen TYPO3-Functional-Bootstrap) und rendert
`<f:render partial="..." arguments="{_all}"/>` mit den im Test übergebenen Variablen. Zwei
Fluid-Kern-ViewHelper sind darin durch schlanke Test-Ersatzimplementierungen ersetzt (siehe
[Tests/Support/FluidViewHelperOverrides](Tests/Support/FluidViewHelperOverrides)), weil die echten in
diesem minimalen Bootstrap ohne Weiteres nicht funktionieren:

* `f:translate` – übersetzt immer in die Default-Sprache (keine Sprachauswahl über Request/Backend-User nötig)
* `f:link.action` – rendert einen Platzhalter-Link (`href="#test-link"`), da echtes TYPO3-Routing einen
  vollständigen Frontend-Request (Site-Konfiguration, TSFE) voraussetzt

Für Partials, die reale Solr-Dokumente/Trefferlisten benötigen (z.B. `document.fields.*`), stellt
[Tests/Support/SolrFixture.php](Tests/Support/SolrFixture.php) echte `Solarium\QueryType\Select\Result\Document`-
bzw. `\Result`-Objekte bereit – erzeugt über einen echten Solarium-Client gegen den Solr-Mock-Server, genauso
wie `Classes/Find/Service/SolrServiceProvider.php` es in Produktion tut. Die zugehörigen Cassetten
(`detail-*.json`, `resultlist-*.json`) liegen ebenfalls in
[Tests/Fixtures/Solr/cassettes](Tests/Fixtures/Solr/cassettes) und werden über dieselbe
`recorder.php` aufgenommen (siehe die entsprechenden Szenarien am Ende der Datei).

[Tests/Unit/Partials/Detail](Tests/Unit/Partials/Detail) rendert die Detail-Partials (`Display/Detail/Normdata/*`,
`Library/*`, `Inventory/*`, `Manuscripts/*`, `ImagesAndObjects/*`) mit je einem Dokument pro Quelltyp. Die
`settings` liefert `SolrFixture::settings()`: die echte TypoScript-Konfiguration (u.a. `queryFields` für
`dla:solveQuery`) mit `connection` auf den Mock-Server, sodass auch die Unterabfragen der Partials gegen
Cassetten laufen. `FluidPartialTestCase` lässt Tests fehlschlagen, wenn dabei eine Anfrage ohne Cassette
ankommt (sonst würde `dla:countFromSolr` still `0` anzeigen). `dla:collection` (Bestandsbaum aus der Datenbank)
wird durch `FakeCollectionService` ersetzt; Testdaten dafür über `FakeCollectionService::$parents`.

## Weitere Hinweise

Der Code der Extension ist über Symlinks eingebunden. Nach Änderungen am Code wie beispielsweise in [Configuration/TypoScript/setup.ts](Configuration/TypoScript/setup.ts) ist also lediglich ein Löschen des TYPO3-Caches erforderlich:

```
task cache
```

Nach einem Neustart des Codespaces ausführen:

```
task reinstall
```

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

  * Start anklicken und "Create new content" auswählen
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
ddev export-db t3example --gzip=false > .devfiles/init.sql
```

## Upgrade auf Version 6

Mit Version 6 ist die bisher separat installierte Extension `dla/find` (Repo
[dla-marbach/typo3-find](https://github.com/dla-marbach/typo3-find)) in `dla/dla_opac_ng` aufgegangen.
`dla/dla_opac_ng` ersetzt das Paket `dla/find` (Composer `replace`), es wird beim Update automatisch entfernt.

Unverändert bleiben bewusst Plugin-Signatur (`find_find`), URL-Parameter (`tx_find_find[...]`),
TypoScript-Pfad (`plugin.tx_find`) und der PHP-Namespace `Dla\Find\` (jetzt unter [Classes/Find](Classes/Find)).
Bestehende Inhaltselemente, Links und TypoScript-Anpassungen funktionieren daher weiter.

Schritte in einer bestehenden Installation:

1. `composer require dla/dla_opac_ng:^6` (bzw. `composer update dla/dla_opac_ng dla/find -W`)
2. Upgrade-Wizard "DLA OPAC: Statisches TypoScript von EXT:find entfernen" ausführen
   (`vendor/bin/typo3 upgrade:run dlaOpacNg_findStaticTemplate` oder im Install Tool). Er entfernt
   `EXT:find/Configuration/TypoScript` aus den TypoScript-Datensätzen, die Basiskonfiguration ist jetzt
   Bestandteil des statischen TypoScripts von `dla_opac_ng`.
3. Falls in eigenem TypoScript auf Dateien unter `EXT:find/...` verwiesen wird, auf
   `EXT:dla_opac_ng/...` umstellen.
4. `vendor/bin/typo3 cache:flush`

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
der GNU General Public License, Version 3 ([LICENSE](LICENSE)).
