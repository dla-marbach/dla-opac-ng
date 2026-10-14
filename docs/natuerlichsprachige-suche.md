# Natürlichsprachige Suche (Proof of Concept)

Nutzerinnen und Nutzer können im Suchschlitz eine Suchanfrage in natürlicher Sprache eingeben und „Filter automatisch erkennen (KI, Beta)“ ankreuzen,
z. B. *„Briefe von Hesse an Thomas Mann aus den 1930er Jahren über das Exil“*. Ein lokal betriebenes Sprachmodell (LLM)
zerlegt die Eingabe in Suchbegriffe und Filter; der Katalog zeigt danach eine ganz normale Trefferliste mit gesetzten Facetten:

| Erkannt | wird im Katalog zu |
|---|---|
| „von Hesse“ | Facette `filterAuthorityRelation_mv` = `Hesse, Hermann (1877-1962)␝Von` |
| „an Thomas Mann“ | Facette `filterAuthorityRelation_mv` = `Mann, Thomas (1875-1955)␝An` |
| „Briefe“ | Facette Medientypen = `Handschriften`, Form und Inhalt = `Brief` |
| „1930er Jahre“ | Facette Zeit = `RANGE 1930 TO 1939` |
| „über das Exil“ | Suchbegriffe `(exil OR exil* OR emigration)` |

Inhalt:

1. [Funktionsweise](#1-funktionsweise)
2. [Schnellstart in der Entwicklungsumgebung](#2-schnellstart-in-der-entwicklungsumgebung)
3. [LLM-Dienst auf einem Server installieren (Ollama)](#3-llm-dienst-auf-einem-server-installieren-ollama)
4. [TYPO3 konfigurieren](#4-typo3-konfigurieren)
5. [Prompt und Beispiele anpassen](#5-prompt-und-beispiele-anpassen)
6. [Last, Sicherheit und Datenschutz](#6-last-sicherheit-und-datenschutz)
7. [Tests](#7-tests)
8. [Fehlerbehebung](#8-fehlerbehebung)
9. [Grenzen des Proof of Concept und nächste Schritte](#9-grenzen-des-proof-of-concept-und-nächste-schritte)

## 1. Funktionsweise

```
Browser                         TYPO3 (dla-opac-ng)                                   LLM-Dienst (Ollama)
  │ GET …/find?tx_find_find[q][default]=Eingabe&tx_find_find[nl]=1
  ├──────────────────────────► ProofOfWorkMiddleware (Bot-Schutz, unverändert)
  │                             NaturalLanguageSearchMiddleware
  │                               ├─ Zwischenspeicher? ─ nein ─► Rate Limit
  │                               │                              └─ QueryUnderstandingService ─► POST /v1/chat/completions
  │                               │                                                               (JSON nach festem Schema)
  │                               ├─ QueryStructure: Antwort prüfen und bereinigen
  │                               └─ SearchArgumentsBuilder: Abgleich mit Solr (Normdaten-Suggester, Orte)
  │ ◄──── 303 Redirect auf …/find?tx_find_find[facet][…]=1&tx_find_find[q][default]=…&tx_find_find[nlq]=Eingabe
  │ GET (normale Suche, wie bisher)
```

* **Das LLM wird nur beim Absenden der Suche aufgerufen.** Danach folgt eine Weiterleitung auf eine normale Such-URL
  (Post/Redirect/Get). Blättern, Facettenklicks und Links lösen keinen weiteren LLM-Aufruf aus.
* **Das LLM erzeugt keine Solr-Syntax.** Es liefert nur ein JSON-Objekt mit festen Feldern (`persons`, `mediaTypes`,
  `formContent`, `topics`, `places`, `languages`, `dateFrom`, `dateTo`, `digitalOnly`, `knownItem`, `exclude`, `text`).
  Die Abfrage wird aus geprüften Werten zusammengesetzt.
* **Suchabsicht:** Bei einem bestimmten Werk (`knownItem`, z. B. „Kafkas Der Process“) kommen Titel und nicht
  aufgelöste Namen als Phrase in die Suche, und Themen werden nicht um Varianten erweitert.
* **Zeitraum als Filter oder als Thema:** `dateFrom`/`dateTo` meinen die Entstehungs- bzw. Erscheinungszeit der
  Objekte („Briefe aus den 1930er Jahren“ → Facette Zeit). Ist der Zeitraum das Thema („Literatur über die Weimarer
  Republik“, „Exilliteratur 1933–1945“), wird er zum Thema statt zum Zeitfilter. Relative Angaben („aus den letzten
  zehn Jahren“) rechnet das Modell vom aktuellen Jahr aus, das zur Laufzeit in den Prompt kommt.
* **Ausschlüsse:** „ohne“, „keine“, „außer“ werden zu ausgeschlossenen Facettenwerten (Medientyp, Form/Inhalt, Status
  `not` wie beim Ausschließen in der Facettenliste) bzw. zu `NOT begriff` im Suchfeld.
* **Kleine Wertelisten gibt der Index vor.** Für Medientyp, Form/Inhalt und Sprache werden die häufigsten Werte der
  jeweiligen Facette aus Solr geholt (einen Tag zwischengespeichert) und als erlaubte Werte (`enum`) ins JSON-Schema
  und in den Prompt gesetzt (`FacetVocabulary`). Das Modell kann nur Werte liefern, die es im Index gibt.
* **Große Wertelisten löst Solr auf, nicht das LLM.** Personen werden über den Normdaten-Suggester `mySuggester`
  aufgelöst (wie bei der Autovervollständigung), zuerst mit dem vollständigen Namen, den das Modell ergänzt
  („Hesse“ → „Hermann Hesse“), sonst mit dem Namen aus der Eingabe (im Nominativ, „Rilkes“ → „Rilke“). Orte werden über
  die exakte Schreibweise oder den häufigsten Facettenwert aufgelöst, der den Begriff enthält (z. B. „Marbach“ →
  „Marbach am Neckar“); die exakte Schreibweise gewinnt nur, wenn sie nicht mehr als zehnmal seltener ist als der häufigste enthaltende Wert (ein seltenes „Marbach“ unterliegt „Marbach am Neckar“). Was sich
  nicht auflösen lässt, wird als weicher Suchbegriff übernommen. Themen werden immer zu Suchbegriffen mit Varianten
  („Stichwort: …“), nicht zur Facette Thema: Viele Datensätze, z. B. Handschriften, haben keine Schlagwörter und würden
  sonst ausgeschlossen.
* **Keine Treffer:** Liefert die Suche 0 Treffer, weist die Interpretationszeile darauf hin, dass sich einzelne
  Bestandteile entfernen lassen. Es wird nichts automatisch weggelassen.
* **Interpretationszeile:** Über der Trefferliste steht „In Ihrer Suche »…« erkannt:“ mit den einzelnen
  Bestandteilen, z. B. `Handschriften ✕ · von Hesse, Hermann (1877-1962) ✕ · 1930–1939 ✕ · Stichwort: Exil ✕`. Jeder Teil
  lässt sich entfernen: Filter über denselben Mechanismus wie bei den aktiven Filtern (`s:find.facetLinkArguments`),
  Suchbegriffe durch Entfernen aus dem Suchfeld. Maßgeblich ist immer der aktuelle Zustand der Suche; die Middleware
  vermerkt in der URL nur, welche Filter (`nlf`) und Suchbegriffe (`nlt`) aus der Eingabe stammen
  (`InterpretationParts`). Die Box „Aktive Filter“ bleibt unverändert und zeigt alle Filter wie bei einer normalen
  Suche, auch die erkannten; die Interpretationszeile ergänzt sie um die Rollen (von/an/über …) und die
  Suchbegriffe. Wird ein Filter an einer Stelle entfernt oder geändert (z. B. der Zeitraum per Schieberegler),
  verschwindet er an beiden Stellen. Eine neue Suche über das Suchfeld beendet die Interpretation.
* **Rückfall:** Ist das LLM nicht erreichbar, zu langsam oder ausgelastet, wird normal nach dem eingegebenen Text gesucht
  („automatische Erkennung nicht verfügbar“). Erkennt es in der Eingabe nichts, wird ebenfalls normal gesucht, mit eigenem Hinweis;
  auch dieses Ergebnis wird zwischengespeichert.

Beteiligte Dateien:

| Datei | Aufgabe |
|---|---|
| `Classes/Middleware/NaturalLanguageSearchMiddleware.php` | Einstieg, Begrenzungen, Weiterleitung |
| `Classes/Service/QueryUnderstandingService.php` | Ablaufsteuerung, Zwischenspeicher |
| `Classes/Service/QueryUnderstanding/LlmClient.php` | Aufruf der OpenAI-kompatiblen API (über die `RequestFactory` von TYPO3) |
| `Classes/Service/QueryUnderstanding/QueryPrompt.php` | Nachrichten und JSON-Schema |
| `Classes/Service/QueryUnderstanding/QueryStructure.php` | Prüfung und Bereinigung der Modellantwort |
| `Classes/Service/QueryUnderstanding/VocabularyResolver.php` | Abgleich mit Solr |
| `Classes/Service/QueryUnderstanding/SearchArgumentsBuilder.php` | Abbildung auf Facetten und Suchbegriffe |
| `Classes/Service/QueryUnderstanding/QueryInterpretation.php` | Ergebnis: Facetten, Suchbegriffe, Such-Argumente |
| `Classes/Service/QueryUnderstanding/FacetVocabulary.php` | Werte von Medientyp, Form/Inhalt und Sprache aus dem Index |
| `Classes/Service/QueryUnderstanding/QueryUnderstandingConfiguration.php` | Konfiguration aus Umgebungsvariablen |
| `Resources/Private/QueryUnderstanding/system-prompt.md` | Systemprompt (fachliche Regeln) |
| `Resources/Private/QueryUnderstanding/examples.json` | Beispielpaare Eingabe → Antwort (few-shot) |
| `Classes/Service/QueryUnderstanding/InterpretationParts.php` | Bestandteile der Interpretationszeile, Abgleich mit den aktiven Filtern |
| `Classes/ViewHelpers/NaturalLanguage/PartsViewHelper.php` | ViewHelper für die Interpretationszeile |
| `Resources/Private/Partials/Form/NaturalLanguageSearch.html` | Checkbox, Hinweis und Interpretationszeile in der Suchmaske |

## 2. Schnellstart in der Entwicklungsumgebung

Voraussetzung: die Entwicklungsumgebung aus der [README](../README.md) (Codespace bzw. Devcontainer mit Docker und
Solr-Tunnel). Das LLM läuft als Docker-Container `ollama`, dessen Port nur auf `127.0.0.1:11434` freigegeben ist.
PHP-FPM läuft direkt im Codespace und erreicht ihn dort als `http://127.0.0.1:11434`.

1. LLM-Dienst starten und Modell laden (beim ersten Mal ca. 2 GB Download):

   ```sh
   task llm
   ```

   Standardmodell ist `qwen3:4b-instruct-2507-q4_K_M` (ca. 2,5 GB, läuft auch ohne GPU). Ein anderes Modell: `task llm MODEL=qwen3:4b-instruct-2507-q8_0`.

2. Funktionstest des LLM-Dienstes (benötigt `jq`):

   ```sh
   task llm:test
   task llm:test QUESTION="Gedichte von Rilke aus Paris"
   ```

   Die Anfrage enthält wie im Katalog Systemprompt (mit aktuellem Jahr), Beispiele und JSON-Schema, nur ohne die
   Wertelisten aus Solr. Erwartet wird ein JSON-Objekt mit den Feldern `persons`, `mediaTypes` usw., ohne `<think>`-Block.

3. Umgebungsvariablen für TYPO3 sind in `.devfiles/.env` bereits gesetzt (`LLM_BASE_URL="http://127.0.0.1:11434/v1"`)
   und werden mit `task start` in die PHP-FPM-Konfiguration übernommen. Nach Änderungen an `.devfiles/.env`:

   ```sh
   task start
   task cache
   ```

4. Prüfen, ob der LLM-Dienst erreichbar ist:

   ```sh
   curl -s http://127.0.0.1:11434/api/version
   ```

5. Katalog öffnen (http://127.0.0.1/katalog), unter dem Suchschlitz „Filter automatisch erkennen (KI, Beta)“ ankreuzen
   und eine Suchanfrage in natürlicher Sprache eingeben.

Den Dienst stoppen: `task llm:stop`.

## 3. LLM-Dienst auf einem Server installieren (Ollama)

Für den Proof of Concept empfehle ich [Ollama](https://ollama.com): eine Installation per Skript, ein systemd-Dienst,
Modelle mit einem Befehl, eine OpenAI-kompatible API mit erzwungener JSON-Ausgabe. Für den späteren Produktivbetrieb
mit vielen gleichzeitigen Anfragen siehe [3.8 vLLM](#38-alternative-für-den-produktivbetrieb-vllm). TYPO3 spricht beide
über dieselbe Schnittstelle an; es ändern sich nur `LLM_BASE_URL` und `LLM_MODEL`.

### 3.1 Hardware und Modellwahl

| Einsatz | Hardware | Modell (`LLM_MODEL`) | Größe | Antwortzeit (Richtwert) |
|---|---|---|---|---|
| Entwicklung, Codespace | 4 CPU-Kerne, 8 GB RAM | `qwen3:4b-instruct-2507-q4_K_M` | ca. 2,5 GB | erste Anfrage ca. 1 min, danach ca. 15–20 s (gemessen) |
| Testserver ohne GPU | 8+ CPU-Kerne, 16 GB RAM | `qwen3:4b-instruct-2507-q4_K_M` | ca. 2,5 GB | ca. 10 s (geschätzt), nur für Tests |
| Testserver mit GPU (empfohlen) | NVIDIA-GPU mit ≥ 8 GB VRAM, z. B. L4, A10, RTX 4000 | `qwen3:4b-instruct-2507-q4_K_M` oder `qwen3:4b-instruct-2507-q8_0` | 2,5–4,3 GB | ca. 1–2 s (geschätzt) |
| Produktion | NVIDIA-GPU mit 24 GB VRAM | `Qwen/Qwen3-4B-Instruct-2507` mit vLLM | | < 1 s (geschätzt) |

Ohne GPU ist die KI-Suche nur zum Ausprobieren geeignet: Im Codespace (4 CPU-Kerne) dauerte die erste Anfrage mit
Laden des Modells und Verarbeitung des Prompts etwa eine Minute, jede weitere 15–20 Sekunden (gemessen, bevor
Antworten und Prompt verkürzt wurden). Die meiste Zeit kostet die Ausgabe des JSON mit wenigen Tokens pro Sekunde;
der gleichbleibende Anfang des Prompts (Systemprompt, Wertelisten und Beispiele) wird nach der ersten Anfrage aus dem
Zwischenspeicher von Ollama gelesen, sofern die Anfrage im selben Slot landet (`OLLAMA_NUM_PARALLEL`). Deshalb gibt
das Modell nur gefüllte Felder aus (im JSON-Schema sind fast alle Felder optional, die Beispiele sind kompakt), und
die Wertelisten sind auf die häufigsten Werte begrenzt (`FacetVocabulary::FIELDS`: 40 Werte für Form/Inhalt, 25
Sprachen).
In der Entwicklungsumgebung ist deshalb `LLM_TIMEOUT="90"` gesetzt (`.devfiles/.env`); mit dem Standard von
15 Sekunden würde dort fast immer normal gesucht. Für den Betrieb ist eine GPU nötig, damit Antworten in 1–2
Sekunden kommen. `task llm:test` zeigt unter `usage` die Zahl der Tokens; die Dauer misst `time task llm:test`.
Die erste Anfrage nach dem Laden des Modells dauert immer länger.

Andere geeignete Modelle (mehrsprachig, mit Deutsch, gute JSON-Ausgabe) finden sich in der
[Ollama-Bibliothek](https://ollama.com/library), z. B. aus den Familien Qwen, Mistral, Gemma oder Llama. Die Auswahl
sollte mit eigenen Testanfragen verglichen werden (siehe [Abschnitt 9](#9-grenzen-des-proof-of-concept-und-nächste-schritte)).
Reasoning-Modelle („thinking“) sind für diese Aufgabe unnötig langsam.

**Wichtig: genau die Instruct-Variante verwenden.** Qwen3-4B-Instruct-2507 (Apache 2.0, August 2025) arbeitet laut
[Modellkarte](https://huggingface.co/Qwen/Qwen3-4B-Instruct-2507) ausschließlich ohne Thinking-Modus und erzeugt keine
`<think>`-Blöcke. In Ollama heißt es `qwen3:4b-instruct-2507-q4_K_M` (bzw. `-q8_0`, `-fp16`). Nicht einfach `qwen3`, `qwen3:4b` oder ein anderes
`qwen3`-Tag verwenden: Diese können auf das ursprüngliche Qwen3 mit Thinking-Modus oder auf die Thinking-Variante
zeigen. Beim ursprünglichen Qwen3 müsste Thinking über Laufzeit bzw. Chat-Template abgeschaltet werden; eine
Anweisung im Prompt („antworte kurz“) ist dafür kein zuverlässiger Ersatz. Die Modellnamen in Ollama weichen von den
Namen bei Qwen ab, deshalb den Tag vor dem Einsatz auf [ollama.com/library/qwen3/tags](https://ollama.com/library/qwen3/tags)
prüfen. Sollte doch ein `<think>`-Block in der Antwort stehen, entfernt ihn der `LlmClient`, die Antwort dauert dann
aber deutlich länger.

### 3.2 Installation (Ubuntu/Debian)

Bei einem Server mit NVIDIA-GPU zuerst den NVIDIA-Treiber installieren und mit `nvidia-smi` prüfen. Dann:

```sh
curl -fsSL https://ollama.com/install.sh | sh
```

Das Skript installiert Ollama nach `/usr/local`, legt den Benutzer `ollama` und den systemd-Dienst `ollama` an und
erkennt eine vorhandene GPU. Der Dienst lauscht danach auf `127.0.0.1:11434`.

```sh
systemctl status ollama
curl http://127.0.0.1:11434/api/version
```

### 3.3 Konfiguration

Einstellungen werden als Umgebungsvariablen des systemd-Dienstes gesetzt:

```sh
sudo systemctl edit ollama
```

Im Editor zwischen den Kommentarzeilen einfügen:

```ini
[Service]
# auf allen Schnittstellen lauschen (Zugriff per Firewall einschränken, siehe 3.5);
# alternativ nur die interne IP des Servers, z. B. 10.0.0.5:11434
Environment="OLLAMA_HOST=0.0.0.0:11434"
# Modell 24 Stunden im Speicher halten (sonst wird es nach 5 Minuten entladen und die nächste Anfrage ist langsam)
Environment="OLLAMA_KEEP_ALIVE=24h"
# Anzahl parallel bearbeiteter Anfragen je Modell
Environment="OLLAMA_NUM_PARALLEL=2"
# Höchstens 4 wartende Anfragen; weitere beantwortet Ollama sofort mit HTTP 503, TYPO3 sucht dann normal.
# Begrenzt so auch, wie viele PHP-Prozesse gleichzeitig auf das LLM warten (Standard von Ollama: 512).
Environment="OLLAMA_MAX_QUEUE=4"
# nur ein Modell gleichzeitig laden
Environment="OLLAMA_MAX_LOADED_MODELS=1"
# Kontextlänge: Prompt, Beispiele und Wertelisten aus dem Index umfassen bis zu ca. 5.000 Tokens
Environment="OLLAMA_CONTEXT_LENGTH=8192"
```

Speichern und den Dienst neu starten:

```sh
sudo systemctl daemon-reload
sudo systemctl restart ollama
```

### 3.4 Modell laden

```sh
ollama pull qwen3:4b-instruct-2507-q4_K_M
ollama list
```

Optional das Modell gleich in den Speicher laden, damit die erste Anfrage schnell ist:

```sh
ollama run qwen3:4b-instruct-2507-q4_K_M "Hallo" >/dev/null
```

### 3.5 Absichern

Ollama hat **keine eigene Authentifizierung**. Der Dienst darf nur vom TYPO3-Server (bzw. den TYPO3-Servern)
erreichbar sein, nie aus dem Internet.

Beispiel mit `ufw` (IP des TYPO3-Servers anpassen):

```sh
sudo ufw allow from 10.0.0.10 to any port 11434 proto tcp
sudo ufw deny 11434/tcp
sudo ufw status
```

Läuft TYPO3 auf demselben Server, genügt `OLLAMA_HOST=127.0.0.1:11434` (Standard). Bei einem
Docker-basierten TYPO3 auf demselben Server ist der Dienst aus dem Container über `host.docker.internal` bzw. die
Docker-Bridge-IP erreichbar; dann `OLLAMA_HOST` auf diese IP setzen.

Soll der Zugriff zusätzlich per Token geschützt werden, kann ein Reverse Proxy vorgeschaltet werden. Der Token wird in
TYPO3 als `LLM_API_KEY` gesetzt und als `Authorization: Bearer …` mitgeschickt. Beispiel für nginx:

```nginx
server {
    listen 10.0.0.5:8443 ssl;
    # ssl_certificate …; ssl_certificate_key …;

    location /v1/ {
        if ($http_authorization != "Bearer GEHEIMER-TOKEN") {
            return 401;
        }
        proxy_pass http://127.0.0.1:11434;
        proxy_read_timeout 60s;
    }
}
```

### 3.6 Funktionstest

Vom TYPO3-Server aus (IP/Host des LLM-Servers und Modell anpassen):

```sh
curl -s http://10.0.0.5:11434/v1/chat/completions \
  -H 'Content-Type: application/json' \
  -d '{
    "model": "qwen3:4b-instruct-2507-q4_K_M",
    "temperature": 0,
    "messages": [
      {"role": "system", "content": "Zerlege die Suchanfrage. Antworte nur mit JSON."},
      {"role": "user", "content": "Briefe von Hesse an Thomas Mann aus den 1930er Jahren"}
    ],
    "response_format": {
      "type": "json_schema",
      "json_schema": {
        "name": "test",
        "strict": true,
        "schema": {
          "type": "object",
          "additionalProperties": false,
          "required": ["persons", "dateFrom", "dateTo"],
          "properties": {
            "persons": {"type": "array", "items": {"type": "string"}},
            "dateFrom": {"type": ["integer", "null"]},
            "dateTo": {"type": ["integer", "null"]}
          }
        }
      }
    }
  }' | jq -r '.choices[0].message.content'
```

Erwartet wird etwa `{"persons": ["Hesse", "Thomas Mann"], "dateFrom": 1930, "dateTo": 1939}`. Mit dem vollständigen
Prompt der Extension testet `task llm:test` (im Repository, gegen `127.0.0.1:11434`).

### 3.7 Alternative: Ollama mit Docker

```sh
# ohne GPU
docker run -d --name ollama --restart unless-stopped \
  -p 127.0.0.1:11434:11434 -v ollama:/root/.ollama \
  -e OLLAMA_KEEP_ALIVE=24h -e OLLAMA_NUM_PARALLEL=2 -e OLLAMA_MAX_QUEUE=4 -e OLLAMA_CONTEXT_LENGTH=8192 \
  ollama/ollama

# mit NVIDIA-GPU (vorher NVIDIA Container Toolkit installieren)
docker run -d --name ollama --restart unless-stopped --gpus=all \
  -p 127.0.0.1:11434:11434 -v ollama:/root/.ollama \
  -e OLLAMA_KEEP_ALIVE=24h -e OLLAMA_NUM_PARALLEL=2 -e OLLAMA_MAX_QUEUE=4 -e OLLAMA_CONTEXT_LENGTH=8192 \
  ollama/ollama

docker exec ollama ollama pull qwen3:4b-instruct-2507-q4_K_M
```

### 3.8 Alternative für den Produktivbetrieb: vLLM

[vLLM](https://docs.vllm.ai) verarbeitet viele gleichzeitige Anfragen deutlich effizienter (continuous batching,
prefix caching) und erzwingt das JSON-Schema ebenfalls über `response_format`. Es benötigt eine NVIDIA-GPU.

```sh
docker run -d --name vllm --restart unless-stopped --gpus=all --ipc=host \
  -p 127.0.0.1:8000:8000 -v ~/.cache/huggingface:/root/.cache/huggingface \
  vllm/vllm-openai \
  --model Qwen/Qwen3-4B-Instruct-2507 --max-model-len 8192 \
  --enable-prefix-caching --api-key GEHEIMER-TOKEN
```

TYPO3-Konfiguration dazu: `LLM_BASE_URL=http://<server>:8000/v1`, `LLM_MODEL=Qwen/Qwen3-4B-Instruct-2507`,
`LLM_API_KEY=GEHEIMER-TOKEN`. vLLM lehnt bei Überlast nicht ab, sondern stellt Anfragen in eine Warteschlange;
`LLM_TIMEOUT` begrenzt dann, wie lange TYPO3 wartet.

## 4. TYPO3 konfigurieren

Die natürlichsprachige Suche wird wie die Solr-Verbindung ausschließlich über Umgebungsvariablen konfiguriert
(dort setzen, wo auch `SOLR_HOST` und `SOLR_CORE` gesetzt werden, in der Entwicklungsumgebung in `.devfiles/.env`):

| Variable | Beispiel | Bedeutung |
|---|---|---|
| `LLM_BASE_URL` | `http://10.0.0.5:11434/v1` | Basis-URL der OpenAI-kompatiblen API inkl. `/v1`. **Leer = Funktion aus** (keine Checkbox). |
| `LLM_MODEL` | `qwen3:4b-instruct-2507-q4_K_M` | Modellname (Standard: `qwen3:4b-instruct-2507-q4_K_M`) |
| `LLM_API_KEY` | | optional, wird als Bearer-Token mitgeschickt |
| `LLM_TIMEOUT` | `15` | optional, Sekunden bis zum Rückfall auf die normale Suche (Standard: 15) |
| `LLM_RATE_LIMIT` | `10` | optional, LLM-Anfragen je Client-IP und Minute (Standard: 10, `0` = keine Begrenzung) |

Nach Änderungen: PHP-Prozess bzw. Container neu starten (Umgebungsvariablen) und den TYPO3-Cache leeren
(`vendor/bin/typo3 cache:flush`, in der Entwicklungsumgebung `task cache`).

Weitere Voraussetzungen:

* Der Zwischenspeicher liegt im Cache-Verzeichnis (`var/cache/data/dla_opac_ng_query_understanding/`, eigene
  Cache-Gruppe `dla_opac_ng`, gezielt leerbar mit `vendor/bin/typo3 cache:flush -g dla_opac_ng`). „Alle Caches leeren“
  bzw. `cache:flush` leeren ihn mit; Einträge werden ohnehin ungültig, sobald sich Prompt, Beispiele, Vokabular oder
  Modell ändern. Das Rate Limit nutzt den Core-Cache `ratelimiter`.
* Die Proof-of-Work-Prüfung (`opacPowEnabled=1`) sollte aktiv sein; sie schützt auch den LLM-Aufruf.
* Gestaltung: Checkbox und Hinweis haben die CSS-Klassen `dla-nl-toggle` und `dla-nl-notice`
  (plus `dla-nl-notice-ok`, `dla-nl-notice-unrecognized`, `dla-nl-notice-fallback`), der Hinweis bei 0 Treffern
  die Klasse `dla-nl-no-results`.
  Die Interpretationszeile ist eine Liste `ul.dla-nl-parts` mit `li.dla-nl-part` und je einer Klasse für die Rolle
  (`dla-nl-part-mediaType`, `-formContent`, `-person`, `-relationVon`, `-relationAn`, `-relationUeber`,
  `-relationUnter`, `-place`, `-language`, `-date`, `-digital`, `-text`, `-topic`, `-unresolved`, `-excluded`); die Links nutzen
  wie die aktiven Filter das Symbol `bel-ende01`. Styles gehören in das Site-CSS.

## 5. Prompt und Beispiele anpassen

Die fachlichen Regeln stehen nicht im PHP-Code, sondern in zwei Dateien, die ohne Programmierkenntnisse gepflegt werden können:

* `Resources/Private/QueryUnderstanding/system-prompt.md`: Beschreibung der Felder und Regeln, z. B. wann eine Person
  „Von“, „An“, „Über“ oder „Unter“ ist und welche Medientypen es gibt. **Die Beschreibungen der Relationen bitte
  fachlich prüfen.**
* `Resources/Private/QueryUnderstanding/examples.json`: Beispielpaare aus Eingabe und erwarteter Antwort. Sie sind der
  wirksamste Hebel für gute Ergebnisse. Am besten echte Suchanfragen aus den Suchlogs verwenden und typische
  DLA-Formulierungen abdecken (Nachlass, Autorenbibliothek, Sekundärliteratur, Übersetzungen …).

Hinweise:

* Die Felder werden im Prompt automatisch alphabetisch sortiert, im Schema wie in den Beispielen. Ollama gibt sie
  ohnehin in alphabetischer Reihenfolge aus; abweichende Beispiele führten dazu, dass das Modell Felder ausließ.
* Personen, Orte und Themen werden nur übernommen, wenn sie (bzw. eine Themen-Variante) in der Eingabe vorkommen;
  kleine Modelle übernehmen sonst gelegentlich Werte aus den Beispielen. Ausschlüsse nur bei einer Verneinung.
* Antworten in den Beispielen enthalten nur gefüllte Felder, ohne leere Werte (`""`, `[]`, `null`, `false`). Das
  Modell ahmt das nach und gibt weniger Tokens aus, was die Antwortzeit deutlich verkürzt. Ein Test prüft das.
* Mehr Beispiele bedeuten einen längeren Prompt. Mit Ollama bleibt die Antwortzeit trotzdem gering, weil der
  gleichbleibende Anfang des Prompts zwischengespeichert wird.
* Zwischengespeicherte Zerlegungen werden automatisch ungültig, sobald sich Prompt, Beispiele, Vokabular oder Modell ändern.
* Die erlaubten Werte für Medientyp, Form/Inhalt und Sprache werden automatisch aus dem Index an den Prompt angehängt
  (`FacetVocabulary::FIELDS` legt fest, wie viele der häufigsten Werte je Facette). Beispiele sollten nur Werte
  verwenden, die es im Index gibt.
* Die Relationen stehen zusätzlich in `QueryPrompt::RELATIONS` (JSON-Schema); neue Relationen müssen auch dort ergänzt werden.

## 6. Last, Sicherheit und Datenschutz

* **Bot-Schutz:** Die Anfrage enthält `tx_find_find` und läuft deshalb zuerst durch die `ProofOfWorkMiddleware`.
  Ausgenommene IP-Bereiche (Campus, Sandbox, Personal) erreichen das LLM ohne Rechenaufgabe, sind aber weiterhin durch
  das Rate Limit begrenzt.
* **Begrenzungen:** je Client-IP und Minute (`LLM_RATE_LIMIT`, mit `symfony/rate-limiter`). Gleichzeitige Anfragen
  begrenzt der LLM-Server (`OLLAMA_NUM_PARALLEL`, `OLLAMA_MAX_QUEUE`); bei Überlast antwortet er mit HTTP 503 und es
  wird sofort normal gesucht.
* **Zwischenspeicher:** Zerlegungen werden 30 Tage je Eingabe gespeichert (unabhängig von Groß-/Kleinschreibung und
  Leerzeichen). Wiederholte Eingaben kosten keinen LLM-Aufruf und unterliegen keiner Begrenzung.
* **Keine Abfrage-Injection:** Das LLM liefert nur Werte nach festem Schema. Solr-Steuerzeichen und boolesche
  Operatoren werden entfernt, Facettenwerte stammen aus dem Index bzw. werden nur übernommen, wenn sie dort existieren. Vertrauliche Datensätze
  bleiben ausgeschlossen, weil `NOT confidential:true` in den `appends` der `solrconfig.xml` steht.
* **Indexierung:** Die Weiterleitung sendet `X-Robots-Tag: noindex, nofollow`.
* **Datenschutz:** Die Suchanfragen verlassen das Haus nicht, wenn der LLM-Dienst selbst betrieben wird. Der Dienst
  protokolliert Suchanfragen und Zerlegungen auf Log-Level `info`; die Standardkonfiguration von TYPO3 schreibt erst ab
  `warning` in die Logdatei. Für eine Auswertung kann das Level für den Namespace `Dla\DlaOpacNg\Service` gesenkt
  werden. Dabei die Datenschutzregeln für Suchanfragen beachten.

## 7. Tests

```sh
task test:php
```

Die Tests laufen ohne LLM und ohne Solr:

* Die LLM-API wird mit dem `MockHandler` von Guzzle nachgebildet (inkl. Fehler, Zeitüberschreitung, ungültigem JSON
  und `<think>`-Blöcken).
* `Tests/Support/FakeVocabularyResolver.php` und `Tests/Support/FakeFacetVocabulary.php` ersetzen den Abgleich mit Solr.
* Getestet werden Client, Bereinigung, Abbildung auf Facetten, Zwischenspeicher, Middleware (Weiterleitung, Rückfall,
  Rate Limit), Interpretationszeile, die Partials und die Dependency-Injection-Konfiguration.

## 8. Fehlerbehebung

| Problem | Ursache und Lösung |
|---|---|
| Checkbox fehlt | `LLM_BASE_URL` ist im PHP-Prozess nicht gesetzt. In der Entwicklungsumgebung `.devfiles/.env` prüfen und `task start`. |
| Es wird immer „normal gesucht“ | LLM nicht erreichbar oder zu langsam. Erreichbarkeit vom Webserver bzw. Web-Container prüfen: `curl http://<llm-host>:11434/api/version` (in der Entwicklungsumgebung: `curl -s http://127.0.0.1:11434/api/version`; schlägt das fehl, `task llm` erneut ausführen). Kommt der Hinweis nach etwa 2 Sekunden, ist der Dienst nicht erreichbar (Verbindungs-Timeout); nach `LLM_TIMEOUT` Sekunden ist er zu langsam. In `var/log/typo3_*.log` nach „LLM-Anfrage fehlgeschlagen“ suchen (die Meldung nennt Verbindungsfehler, Timeout oder HTTP-Status). |
| Erste Anfrage sehr langsam | Modell wird geladen. `OLLAMA_KEEP_ALIVE=24h` setzen, Modell vorab laden (3.4). |
| Generell zu langsam | Modell zu groß für die Hardware: kleineres Modell oder GPU; `LLM_TIMEOUT` erhöhen. |
| Status 404 mit „model … not found“ im Log | `LLM_MODEL` stimmt nicht mit `ollama list` überein. |
| Antwort ist kein JSON | Ältere Ollama-Version ohne Unterstützung für `response_format` mit `json_schema`: Ollama aktualisieren (Installationsskript erneut ausführen). |
| Falsche Person erkannt | Der Normdaten-Suggester liefert einen anderen Datensatz zuerst. Die Facette lässt sich in der Trefferliste entfernen. Vollständigere Namen in der Eingabe helfen. |
| Unpassende Zerlegung | Prompt bzw. Beispiele ergänzen (Abschnitt 5). |

## 9. Grenzen des Proof of Concept und nächste Schritte

* **Fachlich prüfen:** Relationen (insbesondere „Unter“), Zuordnung der Medientypen, Beispiele.
* **Schlagwörter und Systematik:** Themen werden nicht gegen die Facette Thema (`filterSubject_mv`) abgeglichen, weil
  ein Filter alle Datensätze ohne Schlagwörter ausschließt (im Test hatten alle Handschriften „nicht bestimmt“). Die
  Systematik ist nicht als Facette konfiguriert und wird nur über Identnummern gesucht.
* **Mehrere Medientypen:** Es wird nur der erste übernommen. Die ODER-Facette `MedientypenHidden` eignet sich dafür
  noch nicht: Das Such-Plugin verbindet ihre Werte mit Leerzeichen und ohne Anführungszeichen
  (`filterType_mv:(Audio Video)`); mit `q.op=AND` aus der `solrconfig.xml` wird daraus vermutlich ein UND, und
  Werte mit Leerzeichen („Bilder und Objekte“) funktionieren nicht. Das sollte zuerst im Such-Plugin behoben werden.
* **Mehrere Webserver:** Rate Limit und Zwischenspeicher liegen im jeweiligen `var/`-Verzeichnis; für mehrere Webserver
  ein gemeinsames Cache-Backend (z. B. Redis) für `ratelimiter` und `dla_opac_ng_query_understanding` verwenden.
* **Testmenge:** 100–200 echte Suchanfragen mit erwarteter Zerlegung sammeln und damit Modelle und Prompt-Änderungen
  vergleichen (Trefferquote je Feld).
* **Messen:** Anteil der Anfragen mit Rückfall bzw. ohne Treffer, Antwortzeiten, Nutzung der Checkbox, entfernte Bestandteile.
