# CSS
page.includeCSS.datetimepicker = EXT:dla_opac_ng/Resources/Public/CSS/jquery.datetimepicker.css
page.includeCSS.jquery-ui = EXT:dla_opac_ng/Resources/Public/CSS/jquery-ui/jquery-ui.min.css
page.includeCSS.nouislider = EXT:dla_opac_ng/Resources/Public/CSS/nouislider.css
page.includeCSS.lightbox = EXT:dla_opac_ng/Resources/Public/CSS/lightbox.css
page.includeCSS.opac-ng = EXT:dla_opac_ng/Resources/Public/CSS/opac-ng.css
page.includeCSS.belugino = EXT:dla_opac_ng/Resources/Public/CSS/belugino.css
page.includeCSS.catalog = EXT:dla_opac_ng/Resources/Public/CSS/catalog.css
page.includeCSS.tokeninput = EXT:dla_opac_ng/Resources/Public/CSS/token-input.css

# JavaScript-Basisbliotheken im Header laden wegen inline-<script>-Blöcken in Templates
page.includeJS.jquery = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery-3.6.0.js
page.includeJS.jquery-ui = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery-ui.min.js
page.includeJS.nouislider = EXT:dla_opac_ng/Resources/Public/JavaScript/nouislider.js
page.includeJS.chart = EXT:dla_opac_ng/Resources/Public/JavaScript/Chart-2.7.2.min.js
page.includeJS.find = EXT:find/Resources/Public/JavaScript/find.js

# Weitere JavaScript-Bibliotheken im Footer laden
page.includeJSFooter.datetimepicker = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery.datetimepicker.min.js
page.includeJSFooter.tokeninput = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery.tokeninput.js
page.includeJSFooter.jscookie = EXT:dla_opac_ng/Resources/Public/JavaScript/js.cookie.min.js
page.includeJSFooter.jspdf = EXT:dla_opac_ng/Resources/Public/JavaScript/jspdf.min.js
page.includeJSFooter.lightbox = EXT:dla_opac_ng/Resources/Public/JavaScript/lightbox.min.js

# Projektspezifisches JavaScript ebenfalls im Footer
page.includeJSFooter.autocomplete = EXT:dla_opac_ng/Resources/Public/JavaScript/autocomplete.js
page.includeJSFooter.dla_opac_ng = EXT:dla_opac_ng/Resources/Public/JavaScript/dla-opac-ng.js
page.includeJSFooter.decisiontree = EXT:dla_opac_ng/Resources/Public/JavaScript/decisiontree.js

# Generell wird die gesamte Konfiguration in "plugin.tx_find" gebündelt
plugin.tx_find {

    # Der Abschnitt "view" verweist auf die zu verwendenden Designtemplates und ViewHelper-Partials
    # (siehe [[Definition eigener Designtemplates für die Ausgabe in Trefferliste und Detailanzeige]])
    view {
        templateRootPaths.10 = EXT:dla_opac_ng/Resources/Private/Templates/
        partialRootPaths.10 =  EXT:dla_opac_ng/Resources/Private/Partials/
    }

    # Der Abschnitt "settings" enthält die Konfiguration der Plugin-Instanz
    settings {

        dataserviceUrl = https://dataservice.dla-marbach.de/

        # Optionale URL zum Datendienst. Wird als Icon-Link unterhalb der Format-Buttons angezeigt.
        dataserviceInfoUrl = https://www.dla-marbach.de/katalog/datendienst/

        # Der Abschnitt "connection" definiert die Verbindung zum Solr-Server.
        # Wichtig ist die Angabe des zu verwendenden Solr-Cores in "path"!
        connections {
            default {
                options {
                    host = host.docker.internal
                    port = 8983
                    path = /
                    scheme = http
                    core = internformat
                }
            }
        }
        
        connection {
            host = host.docker.internal
            port = 8983
            path = /
            timeout = 10
            scheme = http
            core = internformat
        }

        mainQueryOperator = AND

        # Der Abschnitt "languageRootPath" definiert, wo die Übersetzungsdateien zu finden sind.
        languageRootPath = EXT:dla_opac_ng/Resources/Private/Language/

        # Der Anschnitt "defaultQuery" legt fest, welche Suche initial ausgeführt werden soll, wenn
        # ein Nutzer den Katalog aufruft. Da kein Trefferset erscheinen soll, ist hier eine Suche
        # definiert, die garantiert keine Treffer erzeugt.
        defaultQuery = *:*

        orderlink = https://cww-test.dla-marbach.de/cgi-bin/aDISCGI/kallias_intra_test/lib/ng-ausleihe.html

        luceneMatchVersionNumber = 8
        features.eDisMax = 0

        escapechar = \,+,-,&,|,!,{,},[,],?,:
        escapechardate = \,+,&,|,!,{,},[,],?,:

        redirectAllOneHitToDetail = 1

        # Der Abschnitt "queryFields" definiert die zur Verfügung stehenden Suchfelder.
        # Der Index "0" beschreibt den Standardsuchschlitz, weitere Indizes können zusätzliche
        # Suchfelder (z.B. für eine erweiterte Suche) beinhalten.
        # Zur Erläuterung der Parameter siehe die typo3-find-Dokumentation auf GitHub.
        queryFields {
            0 {
                # definiert das Solr-Feld für die Standardsuche
                id = default
                class = autocomplete
                type = Text
                query = %s
                # schaltet die Maskierung von Steuerzeichen auf den Modus 2 um.
                # Gemeinsam mit escapechar werden dann die entsprechenden Zeichen maskiert
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                replaceAfterEscape {
                    1 {
                        searchEntity_id_mv\: = searchEntity_id_mv:
                    }
                }
            }
            # {0:'parent_id_mv:(\'{id}\') AND parent_type_mv:(\'Einzelbestandteil \/ unselbständiges Werk\')', 1:1}
            96 {
                # search for relation article
                id = relation_article
                type = Text
                #query = parent_id_mv:("%1$s") AND parent_type_mv:("Einzelbestandteil / unselbständiges Werk")
                query = parent_id_mv:("%1$s") AND parent_type_mv:("Teil einer fortlaufenden Zusammenstellung, Aufsatz (z.B. Zeitschrift)" OR "Teil einer monografischen Zusammenstellung, unselbständiges Stück" OR "Fortlaufende Ressource / Serie, Reihe")
                noescape = 1
                hidden = 1
            }
            #{0:'parent_id_mv:\'{id}\' AND (\'Mehrteilige Monografie \/ nicht Teil eines Gesamtwerks\' OR \'Mehrteilige Monografie / Teil eines Gesamtwerks\' OR \'Fortlaufende Ressource / Serie, Reihe\')', 1:1}
            97 {
            # search for relation bände
                id = relation_volume
                type = Text
                query = parent_id_mv:("%1$s") AND NOT parent_type_mv:("Teil einer fortlaufenden Zusammenstellung, Aufsatz (z.B. Zeitschrift)" OR "Teil einer monografischen Zusammenstellung, unselbständiges Stück" OR "Begleitmaterial / Beilage")
                noescape = 1
                hidden = 1
            }

            98 {
            # search for relation rezensionen
                id = relation_review
                type = Text
                query = relation_id_mv:("%1$s") AND relation_type_mv:("Rezension von")
                noescape = 1
                hidden = 1
            }

            99 {
            # search for relation volume and booklet
                id = relation_volume_and_booklet
                type = Text
                query = parent_id_mv:("%1$s") AND NOT parent_type_mv:("Teil einer fortlaufenden Zusammenstellung, Aufsatz (z.B. Zeitschrift)" OR "Teil einer monografischen Zusammenstellung, unselbständiges Stück" OR "Begleitmaterial / Beilage")
                noescape = 1
                hidden = 1
            }

            100 {
                id = relation_subinventory
                type = Text
                query = (parent_id_mv:"%1$s" AND source:BF) OR (collection_id_mv:"%1$s" AND source:BI)
                noescape = 1
                hidden = 1
            }

            105 {
                id = relation_subinventory_hs
                type = Text
                query = collection_id_mv:("%1$s") OR item_collection_id_mv:("%1$s")
                noescape = 1
                hidden = 1
            }

            110 {
                id = relation_inside_biokey
                type = Text
                query = parent_id_mv:("%1$s")
                noescape = 1
                hidden = 1
            }

            ## Personen ##
            111 {
                id = detail_von_gedrucktes
                type = Text
                # query = personBy_id_mv:("%1$s") AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND NOT personBy_role_mv:("Widmungsempfänger" OR "Adressat" OR "Gefeierter") AND
                query = (filterAuthorityRelation_mv:("%1$s") AND filterType_mv:("Gedrucktes"))
                noescape = 1
                hidden = 1
            }
            112 {
                id = detail_von_handschriften
                type = Text
                # query = personBy_id_mv:("%1$s") AND source:("HS")
                query = (filterAuthorityRelation_mv:("%1$s") AND filterType_mv:("Handschriften"))
                noescape = 1
                hidden = 1
            }
            113 {
                id = detail_von_bundo
                type = Text
                # query = personBy_id_mv:("%1$s") AND source:("BI")
                query = (filterAuthorityRelation_mv:("%1$s") AND filterType_mv:("Bilder und Objekte"))
                noescape = 1
                hidden = 1
            }
            114 {
                id = detail_von_aundv
                type = Text
                # query = personBy_id_mv:("%1$s") AND category:("Ton- und Bildträger") AND NOT personBy_role_mv:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                query = (filterAuthorityRelation_mv:("%1$s") AND (filterType_mv:("Audio") OR filterType_mv:("Video")))

                noescape = 1
                hidden = 1
            }
            1141 {
                id = detail_daten
                type = Text
                query = (filterAuthorityRelation_mv:("%1$s") AND filterType_mv:("Daten"))
                noescape = 1
                hidden = 1
            }
            115 {
                id = detail_an_gedrucktes
                type = Text
                # query = personBy_id_mv:("%1$s") AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND personBy_role_mv:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                query = (filterAuthorityRelation_mv:("%1$s") AND filterType_mv:("Gedrucktes"))
                noescape = 1
                hidden = 1
            }
            116 {
                id = detail_an_handschriften
                type = Text
                query = (personTo_id_mv:("%1$s") AND source:("HS")) OR (filterAuthorityRelation_mv:("%2$s") AND source:("AK"))
                # Geht nicht, findet Widmungen nicht, vgl. #1794:
                # query = (filterAuthorityRelation_mv:("%1$s") AND filterType_mv:("Handschriften"))
                noescape = 1
                hidden = 1
            }
            117 {
                id = detail_an_bundo
                type = Text
                # query = personTo_id_mv:("%1$s") AND source:("BI")
                query = (filterAuthorityRelation_mv:("%1$s") AND filterType_mv:("Bilder und Objekte"))
                noescape = 1
                hidden = 1
            }
            118 {
                id = detail_an_aundv
                type = Text
                # query = personBy_id_mv:("%1$s") AND category:("Ton- und Bildträger") AND personBy_role_mv:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                query = (filterAuthorityRelation_mv:("%1$s") AND (filterType_mv:("Audio") OR filterType_mv:("Video")))
                noescape = 1 
                hidden = 1
            }
            119 {
                id = detail_ueber_gedrucktes
                type = Text
                query = (filterAuthorityRelation_mv:("%1$s") AND filterType_mv:("Gedrucktes") AND source:("AK"))
                noescape = 1
                hidden = 1
            }
            120 {
                id = detail_ueber_handschriften
                type = Text
                query = (filterAuthorityRelation_mv:("%1$s") AND source:("HS"))
                noescape = 1
                hidden = 1
            }
            121 {
                id = detail_ueber_bundo
                type = Text
                query = (filterAuthorityRelation_mv:("%1$s") AND source:("BI"))
                noescape = 1
                hidden = 1
            }
            122 {
                id = detail_ueber_aundv
                type = Text
                query = (filterAuthorityRelation_mv:("%1$s") AND (filterType_mv:("Audio") OR filterType_mv:("Video")))
                noescape = 1
                hidden = 1
            }
            123 {
                id = detail_ueber_bestaende
                type = Text
                query = personAbout_id_mv:("%1$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }
            124 {
                id = detail_unter_bestaende
                type = Text
                query = personAt_id_mv:("%1$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }
            125 {
                id = detail_unter_bundo
                type = Text
                # query = personAt_id_mv:("%1$s") AND source:("BI")
                query = (filterAuthorityRelation_mv:("%1$s") AND filterType_mv:("Bilder und Objekte"))
                noescape = 1
                hidden = 1
            }
            126 {
                id = detail_unter_exemplare
                type = Text
                query = filterAuthorityRelation_mv:("%1$s") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            127 {
                id = detail_weitere_gedrucktes
                type = Text
                query = NOT personBy_id_mv:("%1$s") AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND filterAuthority_mv:("%2$s") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            128 {
                id = detail_weitere_handschriften
                type = Text
                query = NOT personBy_id_mv:("%1$s") AND NOT personTo_id_mv:("%1$s") AND filterAuthority_mv:("%2$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            129 {
                id = detail_weitere_bundo
                type = Text
                query = NOT personBy_id_mv:("%1$s") AND NOT personTo_id_mv:("%1$s") AND filterAuthority_mv:("%2$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            130 {
                id = detail_weitere_aundv
                type = Text
                query = NOT personBy_id_mv:("%1$s") AND filterAuthority_mv:("%2$s") AND category:("Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }

            131 {
                id = detail_all
                type = Text
                query = personBy_id_mv:("%1$s") OR personTo_id_mv:("%1$s") OR personAbout_id_mv:("%1$s") OR personAt_id_mv:("%1$s")
                noescape = 1
                hidden = 1
            }

            132 {
                id = detail_weitere_daten
                type = Text
                query = NOT personBy_id_mv:("%1$s") AND NOT personTo_id_mv:("%1$s") AND filterAuthority_mv:("%2$s") AND filterType_mv:("Daten")
                noescape = 1
                hidden = 1
            }

            133 {
                id = detail_weitere_bestaende
                type = Text
                query = NOT personBy_id_mv:("%1$s") AND NOT personTo_id_mv:("%1$s") AND filterAuthority_mv:("%2$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }

            ## Werke ##

            140 {
                id = detail_werke_in_gedrucktes
                type = Text
                query = (work_id_mv:("%1$s") OR workCompilation_id_mv:("%1$s")) AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            141 {
                id = detail_werke_in_handschriften
                type = Text
                query = work_id_mv:("%1$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            142 {
                id = detail_werke_in_aundv
                type = Text
                query = (work_id_mv:("%1$s") OR workCompilation_id_mv:("%1$s")) AND category:("Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }

            143 {
                id = detail_werke_translation_gedrucktes
                type = Text
                # query = (work_id_mv:("%1$s") OR workCompilation_id_mv:("%1$s")) AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND filterFormContent_mv:("Übersetzung" OR "Übersetzung, deutsch" OR "Übersetzung, fremdsprachig") AND source:("AK")
                query = (%1$s) AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND filterFormContent_mv:("Übersetzung" OR "Übersetzung, deutsch" OR "Übersetzung, fremdsprachig") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            144 {
                id = detail_werke_translation_handschriften
                type = Text
                query = work_id_mv:("%1$s") AND source:("HS") AND filterFormContent_mv:("Übersetzung" OR "Übersetzung, deutsch" OR "Übersetzung, fremdsprachig")
                noescape = 1
                hidden = 1
            }
            145 {
                id = detail_werke_translation_aundv
                type = Text
                query = (work_id_mv:("%1$s") OR workCompilation_id_mv:("%1$s")) AND category:("Ton- und Bildträger") AND filterFormContent_mv:("Übersetzung" OR "Übersetzung, deutsch" OR "Übersetzung, fremdsprachig")
                noescape = 1
                hidden = 1
            }

            146 {
                id = detail_werke_ueber_gedrucktes
                type = Text
                query = workAbout_id_mv:("%1$s") AND NOT category:("Werktitel" OR "Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }
            147 {
                id = detail_werke_ueber_handschriften
                type = Text
                query = work_id_mv:("%1$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            149 {
                id = detail_werke_ueber_bundo
                type = Text
                query = work_id_mv:("%1$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            150 {
                id = detail_werke_ueber_aundv
                type = Text
                query = workAbout_id_mv:("%1$s") AND category:("Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }
            151 {
                id = detail_werke_weitere_gedrucktes
                type = Text
                query = (NOT work_id_mv:("%1$s") OR NOT workCompilation_id_mv:("%1$s")) AND (titleMain_text:("%2$s") OR titleOther_text_mv:("%2$s")) AND creator_id_mv:("%3$s") AND NOT category:("Werktitel" OR "Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }
            152 {
                id = detail_werke_weitere_handschriften
                type = Text
                query = NOT work_id_mv:("%1$s") AND titleMain_text:("%2$s") AND personBy_id_mv:("%3$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            153 {
                id = detail_werke_weitere_aundv
                type = Text
                query = (NOT work_id_mv:("%1$s") OR NOT workCompilation_id_mv:("%1$s")) AND category:("Ton- und Bildträger") AND (titleMain_text:("%2$s") OR titleOther_text_mv:("%2$s")) AND creator_id_mv:("%3$s")
                noescape = 1
                hidden = 1
            }
            154 {
                id = detail_werke_all
                type = Text
                query = work_id_mv:("%1$s") OR workCompilation_id_mv:("%1$s") OR work_id_mv:("%1$s") OR workAbout_id_mv:("%1$s")
                noescape = 1
                hidden = 1
            }
            155 {
                id = detail_werke_rezension_gedrucktes
                type = Text
                query = (%1$s) AND filterFormContent_mv:("Rezension") AND source:("AK") AND NOT category:("Werktitel" OR "Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }
            156 {
                id = detail_werke_rezension_handschriften
                type = Text
                query = (%1$s) AND filterFormContent_mv:("Rezension") AND source:("HS")
                noescape = 1
                hidden = 1
            }            
            157 {
                id = detail_werke_rezension_aundv
                type = Text
                query = (%1$s) AND filterFormContent_mv:("Rezension") AND category:("Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }            

            ### Köperschaften ###

            160 {
                id = detail_ks_von_gedrucktes
                type = Text
                query = (corporationBy_id_mv:("%1$s") OR publisher_id_mv:("%1$s")) AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND NOT personBy_role_mv:("Widmungsempfänger" OR "Adressat" OR "Gefeierter") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            161 {
                id = detail_ks_von_handschriften
                type = Text
                query = corporationBy_id_mv:("%1$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            162 {
                id = detail_ks_von_bundo
                type = Text
                query = corporationBy_id_mv:("%1$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            163 {
                id = detail_ks_von_aundv
                type = Text
                query = (corporationBy_id_mv:("%1$s") OR publisher_id_mv:("%1$s")) AND category:("Ton- und Bildträger") AND NOT personBy_role_mv:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                noescape = 1
                hidden = 1
            }

            164 {
                id = detail_ks_an_gedrucktes
                type = Text
                query = corporationBy_id_mv:("%1$s") AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND personBy_role_mv:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                noescape = 1
                hidden = 1
            }
            165 {
                id = detail_ks_an_handschriften
                type = Text
                query = (corporationTo_id_mv:("%1$s") AND source:("HS")) OR (filterAuthorityRelation_mv:("%2$s") AND source:("AK"))
                noescape = 1
                hidden = 1
            }
            166 {
                id = detail_ks_an_bundo
                type = Text
                query = corporationTo_id_mv:("%1$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            167 {
                id = detail_ks_an_aundv
                type = Text
                query = corporationBy_id_mv:("%1$s") AND category:("Ton- und Bildträger") AND personBy_role_mv:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                noescape = 1
                hidden = 1
            }
            168 {
                id = detail_ks_ueber_gedrucktes
                type = Text
                query = corporationAbout_id_mv:("%1$s") AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            169 {
                id = detail_ks_ueber_handschriften
                type = Text
                query = corporationAbout_id_mv:("%1$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            170 {
                id = detail_ks_ueber_bundo
                type = Text
                query = corporationAbout_id_mv:("%1$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            171 {
                id = detail_ks_ueber_aundv
                type = Text
                query = corporationAbout_id_mv:("%1$s") AND category:("Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }
            172 {
                id = detail_ks_ueber_bestaende
                type = Text
                query = corporationAbout_id_mv:("%1$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }
            173 {
                id = detail_ks_unter_bestaende
                type = Text
                query = corporationAt_id_mv:("%1$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }
            174 {
                id = detail_ks_unter_bundo
                type = Text
                query = corporationAt_id_mv:("%1$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            175 {
                id = detail_ks_unter_exemplare
                type = Text
                query = filterAuthorityRelation_mv:("%1$s") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            176 {
                id = detail_ks_weitere_gedrucktes
                type = Range
                query = NOT corporationBy_id_mv:("%1$s") AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND filterAuthority_mv:("%2$s") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            177 {
                id = detail_ks_weitere_handschriften
                type = Range
                query = NOT corporationBy_id_mv:("%1$s") AND NOT corporationTo_id_mv:("%1$s") AND filterAuthority_mv:("%2$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            178 {
                id = detail_ks_weitere_bundo
                type = Range
                query = NOT corporationBy_id_mv:("%1$s") AND NOT corporationTo_id_mv:("%1$s") AND filterAuthority_mv:("%2$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            179 {
                id = detail_ks_weitere_aundv
                type = Range
                query = NOT corporationBy_id_mv:("%1$s") AND category:("Werktitel" OR "Ton- und Bildträger") AND filterAuthority_mv:("%2$s")
                noescape = 1
                hidden = 1
            }

            180 {
                id = detail_ks_all
                type = Text
                query = corporationBy_id_mv:("%1$s") OR corporationTo_id_mv:("%1$s") OR corporationAbout_id_mv:("%1$s") OR corporationAt_id_mv:("%1$s")
                noescape = 1
                hidden = 1
            }

            181 {
                id = detail_ks_weitere_bestaende
                type = Range
                query = NOT corporationBy_id_mv:("%1$s") AND NOT corporationTo_id_mv:("%1$s") AND filterAuthority_mv:("%2$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }

            182 {
                id = detail_ks_weitere_daten
                type = Range
                query = NOT corporationBy_id_mv:("%1$s") AND NOT corporationTo_id_mv:("%1$s") AND filterAuthority_mv:("%2$s") AND filterType_mv:("Daten")
                noescape = 1
                hidden = 1
            }


            190 {
                id = detail_sb_schlagwort_gedrucktes
                type = Text
                query = subject_id_mv:("%1$s") AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            191 {
                id = detail_sb_schlagwort_bundo
                type = Text
                query = (subject_id_mv:("%1$s") OR subjectLocation_id_mv:("%1$s")) AND source:("BI")
                noescape = 1
                hidden = 1
            }

            192 {
                id = detail_sb_schlagwort_handschriften
                type = Text
                query = subject_id_mv:("%1$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }

            193 {
                id = detail_sb_schlagwort_aundv
                type = Text
                query = subject_id_mv:("%1$s") AND category:("Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            194 {
                id = detail_sb_schlagwort_bestaende
                type = Text
                query = subject_id_mv:("%1$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }

            195 {
                id = detail_sb_all
                type = Text
                query = subject_id_mv:("%1$s") AND source:("AK" OR "BI" OR "HS" OR "BF")
                noescape = 1
                hidden = 1
            }

            200 {
                id = detail_kette_gedrucktes
                type = Text
                query = classification_id_mv:("%1$s") AND NOT category:("Werktitel" OR "Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            203 {
                id = detail_kette_aundv
                type = Text
                query = classification_id_mv:("%1$s") AND category:("Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            204 {
                id = detail_kette_exemplare
                type = Text
                query = item_classification_id_mv:("%1$s")
                noescape = 1
                hidden = 1
            }

            205 {
                id = detail_kette_all
                type = Text
                query = classification_id_mv:("%1$s") OR item_classification_id_mv:("%1$s")
                noescape = 1
                hidden = 1
            }

            220 {
                id = detail_fs_systematikketten
                type = Text
                query = (classificationSubject_id_mv:("%1$s") OR classificationForm_id_mv:("%1$s")) AND category:("Kette Bibliothek") AND source:("SE")
                noescape = 1
                hidden = 1
            }

            221 {
                id = detail_fs_pnormdaten
                type = Text
                query = classificationOther_text_mv:("%1$s") AND source:("PE")
                noescape = 1
                hidden = 1
            }

            222 {
                id = detail_fs_ksnormdaten
                type = Text
                query = classificationOther_text_mv:("%1$s") AND source:("KS")
                noescape = 1
                hidden = 1
            }

            223 {
                id = detail_fs_autorenschemaketten
                type = Text
                query = item_id_mv:("%1$s") AND source:("SE")
                noescape = 1
                hidden = 1
            }

            224 {
                id = detail_fs_bibliographieketten
                type = Text
                query = classificationSubject_id_mv:("%1$s") AND category:("Bibliographie-Kette") AND source:("SE")
                noescape = 1
                hidden = 1
            }

            225 {
                id = detail_fs_all
                type = Text
                query = (classificationSubject_id_mv:("%1$s") OR classificationForm_id_mv:("%1$s") OR item_id_mv:("%1$s")) OR (classificationOther_text_mv:("%2$s") AND source:("KS" OR "PE"))
                noescape = 1
                hidden = 1
            }




            ### Right column search ###
            300 {
                id = detail_ks_rightcolumn
                type = Text
                query = corporationBy_id_mv:("%1$s") source:("AK") AND category:("Werktitel")
                noescape = 1
                hidden = 1
            }

            310 {
                id = detail_pe_rightcolumn
                type = Text
                query = personBy_id_mv:("%1$s") source:("AK") AND category:("Werktitel")
                noescape = 1
                hidden = 1
            }


            ###### EXTENDED SEARCH #######
            # OR-Queries in diesem Block immer mit äußerer Klammer schreiben,
            # da alle queryFields global per AND zusammengebaut werden.
            # Person/Körperschaft
            500 {
                id = author
                type = Text
                query = searchName_mv:(%1$s)
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Person/Körperschaft VON
            501 {
                id = author_von
                type = Text
                query = searchNameBy_mv:(%1$s)
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Person/Körperschaft AN
            502 {
                id = author_an
                type = Text
                query = searchNameTo_mv:(%1$s)
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Person/Körperschaft ÜBER
            503 {
                id = author_ueber
                type = Text
                query = searchNameAbout_mv:(%1$s)
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Person/Körperschaft UNTER
            504 {
                id = author_unter
                type = Text
                query = searchNameAt_mv:(%1$s)
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Titel
            505 {
                id = title
                type = Text
                query = ((display:(%1$s) AND source:(AK OR BI OR BF OR HS OR dbis OR ezb)) OR (work_display_mv:(%1$s) AND source:(AK OR BI OR HS)) OR titleOther_text_mv:(%1$s) OR titleMain_comment:(%1$s) OR titleOther_comment_mv:(%1$s) OR titleOriginal:(%1$s))                        
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Titel ÜBER
            506 {
                id = title_ueber
                type = Text
                query = (workAbout_titleOther_text_mv:(%1$s) OR (relation_text_mv:(%1$s) AND source:(AK)) OR (work_display_mv:(%1$s) AND source:(BI OR HS)))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Jahr/Datum
            507 {
                id = date
                type = Text
                query = (filterDateRange_mv:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechardate
                hidden = 1
            }
            # Zeit/Datum Von
            508 {
                id = date_von
                type = Text
                query = (filterDateRange_mv:[%1$s TO *])
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechardate
                hidden = 1
            }
            # Zeit/Datum Bis
            509 {
                id = date_bis
                type = Text
                query = (filterDateRange_mv:[* TO %1$s])
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechardate
                hidden = 1
            }
            # Neu erfasst von
            510 {
                id = new_von
                type = Text
                query = (filterRecent:[%1$s TO *])
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Neu erfasst bis
            511 {
                id = new_bis
                type = Text
                query = (filterRecent:[* TO %1$s])
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Entstehungsort/Erscheinungsort
            512 {
                id = place
                type = Text
                query = (place_mv:(%1$s) OR publisherOriginalLocation_mv:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Nummern
            513 {
                id = numbers
                type = Text
                query = (accessionNumber:(%1$s) OR acronym_mv:(%1$s) OR id:(%1$s) OR identifier_id_mv:(%1$s) OR inventoryNumber:(%1$s) OR isbn_mv:(%1$s) OR ismn_mv:(%1$s) OR issn_mv:(%1$s) OR item_accessionNumber_mv:(%1$s) OR item_callNumberReadingRoom_mv:(%1$s) OR mediaNumber:(%1$s) OR microform_mv:(%1$s) OR photoNegativeNumber_mv:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Bestandssignatur
            514 {
                id = signatur
                type = Text
                query = ((title:(%1$s) AND source:("BF")) OR item_callNumberCollection_mv:(%1$s) OR collection_display_mv:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Provenienzmerkmale (Exemplar)
            515 {
                id = exemplar
                type = Text
                query = (item_note_mv:(%1$s) OR item_noteOther_mv:(%1$s) OR item_classification_display_mv:(%1$s) OR item_virtualRecording_mv:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Freie Suche
            516 {
                id = searchall
                type = Text
                query = (%1$s)
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Nur ohne Datum
            517 {
                id = not_date
                type = Text
                query = (*:* AND -filterDateRange_mv:*)
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            # Identnummer
            518 {
                id = id
                type = Text
                query = (id:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }

            ### Systematikverlinkung ###

            # Systematik (Identnummer)
            610 {
                id = classification
                type = Text
                query = (searchClassification_id_mv:(%1$s))
                noescape = 1
                hidden = 1
            }

            700 {
                id = k10plus
                type = Text
                query = vendor_id_mv:(%1$s)
                noescape = 1
                hidden = 1
                redirectToDetail = 1
            }

            ### Exemplarsuche ###

            999 {
                id = copy_search
                type = Text
                query = item_id_mv:("%1$s")
                noescape = 1
                hidden = 1
            }



        }
        # Der Abschnitt "additionalFilters" definiert globale Suchfilter
        additionalFilters {
            1 = NOT source:(AU OR BE OR MM)
        }

        # Der Abschnitt "standardFields" definiert die in der Trefferliste anzuzeigenden Felder in der Form
        # {$Partialsvariable} = {$Solrfeld}
        standardFields {
            title = display
            filterType_mv = filterType_mv
            displayName = displayName
            display = display
            displayAddition1 = displayAddition1
            displayAddition2 = displayAddition2
        }

        # Der Abschnitt "dataFields" bestimmt, welche Felder in den jeweiligen Ansichten geladen werden sollen.
        dataFields {
            default {
                default {
                    f0 = id
                }
            }
            index {
                default {
                    # Kernanzeige
                    f0 = id
                    f1 = display
                    f2 = displayAddition1
                    f3 = displayAddition2
                    f4 = displayName

                    # Medientyp / Icon-Logik
                    f5 = filterType_mv
                    f6 = filterMultipart
                    f7 = filterDigital

                    # Vorschaubilder
                    f8 = digitalObject_thumbnail_mv

                    # Aufklappbereich: Medium & Nutzung
                    f9 = filterMedium_mv
                    f10 = usageRestrictionNote

                    # Aufklappbereich: Webseiten
                    f11 = website_url_mv
                    f12 = website_description_mv

                    # Aufklappbereich: Digitale Objekte
                    f13 = digitalObject_id_mv
                    f14 = digitalObject_display_mv
                    f15 = digitalObject_hyperlink_mv
                    f16 = digitalObject_accessLevel_mv
                    f17 = digitalObject_fileExtension_mv

                    # Aufklappbereich: Provenienz
                    f18 = item_provenance_mv
                }
            }
            detail {
                default {
                    f0 = *
                }
            }
        }

        highlight {
            default {
                fields {
                    f0 = display
                    f1 = displayAddition1
                    f2 = displayAddition2
                    f3 = displayName
                }
                query = (display:(%1$s) OR displayAddition1:(%1$s) OR displayAddition2:(%1$s) OR displayName:(%1$s))
                useQueryTerms = 1
                useFacetTerms = 0
            }
        }

        # Der Abschnitt "sort" definiert die Sortierung der Trefferliste und die Nutzeroptionen.
        sort {
            1 {
                id = default
                sortCriteria = score desc
            }
            2 {
                id = Jahr aufsteigend
                sortCriteria = filterDatePoint_mv asc
            }
            3 {
                id = Jahr absteigend
                sortCriteria = filterDatePoint_mv desc
            }

            5 {
                id = Titel (A-Z)
                sortCriteria = display asc
            }

            6 {
                id = Titel(Z-A)
                sortCriteria = display desc
            }

            # is used as a static parameter in "Namen und Werke"
            7 {
                id = Normdaten absteigend
                sortCriteria = searchEntityScore desc
            }
        }

        # Der Abschnitt "facets" definiert die angebotenen Suchfacetten. Hier können auch Tabs konfiguriert werden.
        # Zur Erläuterung der Parameter siehe die typo3-find-Dokumentation auf GitHub.
        facets {
            10 {
                id = Digital
                field = filterDigital
                type = Switch
                text = nur digitale Medien
                query = filterDigital:true
                removeHeadline = true
            }
            11 {
                id = Medientypen
                field = filterType_mv
                type = Bar
                sortOrder = count
                query = filterType_mv:("%s")
                reverseFacet = 1
            }

            15 {
                id = FormUndInhalt
                field = filterFormContent_mv
                query = filterFormContent_mv:("%s")
                label = Form und Inhalt
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelMissing = nicht bestimmt
                reverseFacet = 1
                ajax = 0
                displayDefault = 6
            }

            20 {
                id = Medium
                field = filterMedium_mv
                sortOrder = count
                query = filterMedium_mv:("%s")
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelMissing = nicht bestimmt
                reverseFacet = 1
                ajax = 0
                displayDefault = 6
            }

            25 {
                id = Personen
                label = Personen & Körperschaften
                type = Decisiontree
                field = filterAuthority_mv
                query = filterAuthority_mv:("%s")
                reverseFacet = 1
                relationField1 = filterAuthorityRelation_mv
                relationField2 = filterAuthorityRole_mv
            }

            30 {
                id = Zeit
                label = Zeit
                type = Histogramslider
                field = filterDateRange_mv
                statsfield = filterDatePoint_mv
                facettype = date_range
                start = NOW/YEAR-35YEARS
                end = NOW
                gap = +1YEAR
                displayDateFormat = Y
                collapse = 1
            }

            35 {
                id = Thema
                field = filterSubject_mv
                query = filterSubject_mv:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelMissing = nicht bestimmt
                reverseFacet = 1
                ajax = 0
                displayDefault = 6
            }

            40 {
                id = NeuImKatalog
                label = Neu im Katalog

                excludeOwnFilter = 1

                facetQuery {

                    10 {
                        id = Woche
                        query = filterRecent:[NOW/DAY-7DAYS TO NOW/DAY+1DAY]
                    }

                    20 {
                        id = Monat
                        query = filterRecent:[NOW/DAY-1MONTH TO NOW/DAY+1DAY]
                    }

                    30 {
                        id = Quartal
                        query = filterRecent:[NOW/DAY-3MONTHS TO NOW/DAY+1DAY]
                    }

                    40 {
                        id = Halbjahr
                        query = filterRecent:[NOW/DAY-6MONTHS TO NOW/DAY+1DAY]
                    }

                    50 {
                        id = Jahr
                        query = filterRecent:[NOW/DAY-1YEAR TO NOW/DAY+1DAY]
                    }
                }
                showmissing = 0

                collapse = 1
                ajax = 0
                displayDefault = 6
            }

            #45 {
            #    id = Sprache
            #    field = filterLanguage_mv
            #    query = filterLanguage_mv:("%s")
            #    collapse = 1
            #    # Shows a facet value which includes all "not known" items
            #    showMissing = 1
            #    labelMissing = nicht bestimmt
            #    reverseFacet = 1
            #}

            46 {
                id = Sprache
                label = Sprache
                type = Decisiontree
                field = filterLanguage_mv
                query = filterLanguage_mv:("%s")
                reverseFacet = 1
                relationField1 = filterLanguageType_mv
                collapse = 1
            }

            47 {
                id = filterLanguageType_mv
                type = List
                field = filterLanguageType_mv
                query = filterLanguageType_mv:("%s")
                hidden = 1
            }

            #50 {
            #    id = Ort
            #    field = filterLocation_mv
            #    query = filterLocation_mv:("%s")
            #    collapse = 1
            #    # Shows a facet value which includes all "not known" items
            #    showMissing = 1
            #    labelMissing = nicht bestimmt
            #    reverseFacet = 1
            #}

            51 {
                id = Ort
                label = Ort
                type = Decisiontree
                field = filterLocation_mv
                query = filterLocation_mv:("%s")
                reverseFacet = 1
                relationField1 = filterLocationType_mv
                collapse = 1
            }

            52 {
                id = filterLocationType_mv
                type = List
                field = filterLocationType_mv
                query = filterLocationType_mv:("%s")
                hidden = 1
            }

            53 {
                id = Sammlung
                field = filterCollection_mv
                query = filterCollection_mv:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelMissing = nicht bestimmt
                reverseFacet = 1
                fixed = 1
            }

            65 {
                id = Datenbestand
                field = filterSource
                query = filterSource:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelMissing = nicht bestimmt
                reverseFacet = 1
                ajax = 0
                displayDefault = 6
            }

            66 {
                id = Bibliographie
                field = filterBibliography_mv
                query = filterBibliography_mv:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelMissing = nicht bestimmt
                reverseFacet = 1
                fixed = 1
                ajax = 0
                displayDefault = 6
            }

            # Die nächsten konfigurierten Facetten sind für die Sucheinschränkung oberhalb des Suchschlitzes
            # Konkret für Audio & Video und Namen & Werke
            70 {
                id = DatenbestandHidden
                facettype = multi_select_facet
                field = filterSource
                query = filterSource:("%s")
                hidden = 1
                fetchMinimum = 0
            }

            77 {
                id = MedientypenHidden
                facettype = multi_select_facet
                field = filterType_mv
                sortOrder = count
                query = filterType_mv:("%s")
            }

            89 {
                id = filterAuthorityRole_mv
                type = List
                field = filterAuthorityRole_mv
                query = filterAuthorityRole_mv:("%s")
                hidden = 1
            }

            90 {
                id = filterAuthorityRelation_mv
                type = List
                field = filterAuthorityRelation_mv
                query = filterAuthorityRelation_mv:("%s")
                hidden = 1
            }

            # Facette zur Selektion der beiden relevantesten Normdatentreffern zum aktuellen Trefferset
            99 {
                id = entities
                field = searchEntity_id_mv
                # Wir benötigen nur die ersten zwei Treffer
                fetchMaximum = 2
                # nicht als Facette anzeigen
                hidden = 1
            }

        }

        # Der Abschnitt "paging" definiert die Anzahl der Treffer pro Seite sowie das Navigieren durch das
        # Trefferset in der Detailanzeige.
        paging {
            perPage = 25
            menu {
                25 = 25
                50 = 50
                100 = 100
            }
            maximumPerPage = 100
            detailPagePaging = 1
        }

        indexPageUid = 3

        citation {
            Bibtex {
                formatMapping {
                    field = source
                    id = id
                    fileext = bib
                    mapping {
                        AK = @article
                        BI = @proceedings
                        default = @misc
                    }
                }
                fieldMapping {
                    author = author
                    title = display
                    edition = id
                    publisher = id
                    isbn = id
                    issn = id
                    keywords = id
                    year = id
                    abstract = id
                    booktitle = id
                    address = id
                }
            }
            Ris {
                formatMapping {
                    field = source
                    id = id
                    fileext = ris
                    mapping {
                        AK = MGZN
                        BI = BOOK
                        default = GEN
                    }
                }
                fieldMapping {
                    AU = id
                    TI = id
                    ET = id
                    PB = id
                    SN = id
                    SN = id
                    KW = id
                    PY = id
                    N2 = id
                    N2 = id
                    BT = id
                    CY = id
                    UR = id
                    ER = id

                }
            }
            Endnote {
                formatMapping {
                    field = source
                    id = id
                    fileext = enw
                    mapping {
                        AK = Journal Article
                        BI = Audiovisual Material
                        default = Generic
                    }
                }
                fieldMapping {
                    T = id
                    A = id
                    7 = id
                    I = id
                    K = id
                    D = id
                    X = id
                    X = id
                    C = id
                    U = id

                }
            }
        }
    }
}


# Localization:
    config {
    linkVars = L
    sys_language_uid = 0
    sys_language_overlay = 1
    sys_language_mode = content_fallback
    language = de
    locale_all = de_DE.UTF-8
    htmlTag_setParams = lang="de" dir="ltr" class="no-js"
}
[siteLanguage("locale") == "en_US.UTF-8"]
config {
    sys_language_uid = 1
    language = en
    locale_all = en_US.UTF-8
    htmlTag_setParams = lang="en" dir="ltr" class="no-js"
}
[END]

page.config.contentObjectExceptionHandler = 0
