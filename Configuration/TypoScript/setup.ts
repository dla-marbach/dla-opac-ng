# Für einige Funktionen in der Anzeige benötigt die Extension das Javascript-Framework jQuery
# Einbindung einer lokalen Kopie wegen Beschränkungen im internen Netz des DLA


#page.includeJS.init = EXT:dla_opac_ng/Resources/Public/JavaScript/init-live.js
page.includeJSFooter.datetimepicker = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery.datetimepicker.min.js
page.includeCSS.datetimepicker =  EXT:dla_opac_ng/Resources/Public/CSS/jquery.datetimepicker.css

page.includeJS.jquery = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery-3.6.0.js
page.includeJS.jquery-ui = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery-ui.min.js
page.includeCSS.jquery-ui =  EXT:dla_opac_ng/Resources/Public/CSS/jquery-ui/jquery-ui.min.css
page.includeJS.jquery-plot = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery.flot.min.js
page.includeJS.jquery-plot-select = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery.flot.selection.js

# Zur Darstellung der Icons in der Trefferliste wird Font Awesome verwendet
#page.includeJS.fa = https://use.fontawesome.com/96352f148e.js
page.includeJS.find = EXT:find/Resources/Public/JavaScript/find.js

page.includeJSFooter.jscookie = EXT:dla_opac_ng/Resources/Public/JavaScript/js.cookie.min.js

page.includeJS.jspdf = EXT:dla_opac_ng/Resources/Public/JavaScript/jspdf.min.js

page.includeCSS.jplayer = EXT:dla_opac_ng/Resources/Public/JavaScript/jplayer/skin/blue.monday/css/jplayer.blue.monday.min.css
page.includeJS.jplayer = EXT:dla_opac_ng/Resources/Public/JavaScript/jplayer/jquery.jplayer.min.js

page.includeJSFooter.autocomplete = EXT:dla_opac_ng/Resources/Public/JavaScript/autocomplete.js

page.includeJSFooter.dla_opac_ng = EXT:dla_opac_ng/Resources/Public/JavaScript/dla-opac-ng.js
page.includeJSFooter.decisiontree = EXT:dla_opac_ng/Resources/Public/JavaScript/decisiontree.js

page.includeJS.nouislider = EXT:dla_opac_ng/Resources/Public/JavaScript/nouislider.js
page.includeCSS.nouislider =  EXT:dla_opac_ng/Resources/Public/CSS/nouislider.css

page.includeJS.lightbox = EXT:dla_opac_ng/Resources/Public/JavaScript/lightbox.min.js
page.includeCSS.lightbox =  EXT:dla_opac_ng/Resources/Public/CSS/lightbox.css

#page.includeJS.detailtabs = EXT:dla_opac_ng/Resources/Public/JavaScript/detailtabs.js

page.includeCSS.opac-ng =  EXT:dla_opac_ng/Resources/Public/CSS/opac-ng.css


page.includeCSS.site =  https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/css/site.min.css
page.includeCSS.belugino =  EXT:dla_opac_ng/Resources/Public/CSS/belugino.css
page.includeCSS.catalog =  EXT:dla_opac_ng/Resources/Public/CSS/catalog.css

page.includeCSS.tokeninput = EXT:dla_opac_ng/Resources/Public/CSS/token-input.css
page.includeJSFooter.tokeninput = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery.tokeninput.js
page.includeJS.chart = EXT:dla_opac_ng/Resources/Public/JavaScript/Chart-2.7.2.min.js

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

        # Der Abschnitt "connection" definiert die Verbindung zum Solr-Server.
        # Wichtig ist die Angabe des zu verwendenden Solr-Cores in "path"!
        connections {
            default {
                options {
                    host = instant.dla-marbach.de
                    port = 8983
                    path = /
                    scheme = http
                    core = opac-ng
                }
            }
        }

        connection {
            host = instant.dla-marbach.de
            port = 8983
            path = /
            timeout = 10
            scheme = http
            core = opac-ng
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
                # setzt den Standardoperator auf ein logisches UND
                //query = {!q.op=AND}*:%s
                query = %s
                # schaltet die Maskierung von Steuerzeichen auf den Modus 2 um.
                # Gemeinsam mit escapechar werden dann die entsprechenden Zeichen maskiert
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                replaceAfterEscape {
                    1 {
                        entity_ids\: = entity_ids:
                    }
                    2 {
                        entity_ids_from\: = entity_ids_from:
                        boost = 100
                    }
                    3 {
                        entity_ids_to\: = entity_ids_to:
                        boost = 70
                    }
                }

            }
            # {0:'AKA451:(\'{A0001}\') AND GESTYP:(\'Einzelbestandteil \/ unselbständiges Werk\')', 1:1}
            96 {
                # search for relation article
                id = relation_article
                type = Text
                #query = AKA451:("%1$s") AND GESTYP:("Einzelbestandteil / unselbständiges Werk")
                query = AKA451:("%1$s") AND GESTYP:("Teil einer fortlaufenden Zusammenstellung, Aufsatz (z.B. Zeitschrift)" OR "Teil einer monografischen Zusammenstellung, unselbständiges Stück" OR "Fortlaufende Ressource / Serie, Reihe")
                noescape = 1
                hidden = 1
            }
            #{0:'AKA451:\'{A0001}\' AND (\'Mehrteilige Monografie \/ nicht Teil eines Gesamtwerks\' OR \'Mehrteilige Monografie / Teil eines Gesamtwerks\' OR \'Fortlaufende Ressource / Serie, Reihe\')', 1:1}"
            97 {
            # search for relation bände
                id = relation_volume
                type = Text
                query = AKA451:("%1$s") AND NOT GESTYP:("Teil einer fortlaufenden Zusammenstellung, Aufsatz (z.B. Zeitschrift)" OR "Teil einer monografischen Zusammenstellung, unselbständiges Stück" OR "Begleitmaterial / Beilage")
                noescape = 1
                hidden = 1
            }
            # {0:'AKY526:\'{A0001}\' AND NOT AKYTXT\:\'Bezug: Rezension von\'', 1:1}
            98 {
            # search for relation rezensionen
                id = relation_review
                type = Text
                query = AKY526:("%1$s") AND AKYTXT:("Rezension von")
                noescape = 1
                hidden = 1
            }

            99 {
            # search for relation volume and booklet
                id = relation_volume_and_booklet
                type = Text
                query = AKA451:("%1$s") AND NOT GESTYP:("Teil einer fortlaufenden Zusammenstellung, Aufsatz (z.B. Zeitschrift)" OR "Teil einer monografischen Zusammenstellung, unselbständiges Stück" OR "Begleitmaterial / Beilage")
                noescape = 1
                hidden = 1
            }

            100 {
                id = relation_subinventory
                type = Text
                query = BUEBKY:("%1$s") OR BFKEY:("%1$s")
                noescape = 1
                hidden = 1
            }

            105 {
                id = relation_subinventory_hs
                type = Text
                query = HSBFKY:("%1$s") OR XX_AU_BFKEY_AUKEY:("%1$s")
                noescape = 1
                hidden = 1
            }

            110 {
                id = relation_inside_biokey
                type = Text
                query = BIOKEY:("%1$s")
                noescape = 1
                hidden = 1
            }

            ## Personen ##
            111 {
                id = detail_von_gedrucktes
                type = Text
                # query = PE0100:("%1$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND NOT GFUPE:("Widmungsempfänger" OR "Adressat" OR "Gefeierter") AND
                query = (facet_names_relations:("%1$s") AND listview_type:("Gedrucktes"))
                noescape = 1
                hidden = 1
            }
            112 {
                id = detail_von_handschriften
                type = Text
                # query = PE0100:("%1$s") AND source:("HS")
                query = (facet_names_relations:("%1$s") AND listview_type:("Handschriften"))
                noescape = 1
                hidden = 1
            }
            113 {
                id = detail_von_bundo
                type = Text
                # query = PE0100:("%1$s") AND source:("BI")
                query = (facet_names_relations:("%1$s") AND listview_type:("Bilder und Objekte"))
                noescape = 1
                hidden = 1
            }
            114 {
                id = detail_von_aundv
                type = Text
                # query = PE0100:("%1$s") AND DOKTYP:("Ton- und Bildträger") AND NOT GFUPE:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                query = (facet_names_relations:("%1$s") AND (listview_type:("Audio") OR listview_type:("Video")))

                noescape = 1
                hidden = 1
            }
            1141 {
                id = detail_daten
                type = Text
                query = (facet_names_relations:("%1$s") AND listview_type:("Daten"))
                noescape = 1
                hidden = 1
            }
            115 {
                id = detail_an_gedrucktes
                type = Text
                # query = PE0100:("%1$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND GFUPE:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                query = (facet_names_relations:("%1$s") AND listview_type:("Gedrucktes"))
                noescape = 1
                hidden = 1
            }
            116 {
                id = detail_an_handschriften
                type = Text
                query = (PEA100:("%1$s") AND source:("HS")) OR (facet_names_relations:("%2$s") AND source:("AK"))
                # Geht nicht, findet Widmungen nicht, vgl. #1794:
                # query = (facet_names_relations:("%1$s") AND listview_type:("Handschriften"))
                noescape = 1
                hidden = 1
            }
            117 {
                id = detail_an_bundo
                type = Text
                # query = PEA100:("%1$s") AND source:("BI")
                query = (facet_names_relations:("%1$s") AND listview_type:("Bilder und Objekte"))
                noescape = 1
                hidden = 1
            }
            118 {
                id = detail_an_aundv
                type = Text
                # query = PE0100:("%1$s") AND DOKTYP:("Ton- und Bildträger") AND GFUPE:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                query = (facet_names_relations:("%1$s") AND (listview_type:("Audio") OR listview_type:("Video")))
                noescape = 1 
                hidden = 1
            }
            119 {
                id = detail_ueber_gedrucktes
                type = Text
                # query = PEE100:("%1$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND source:("AK")
                query = (facet_names_relations:("%1$s") AND listview_type:("Gedrucktes"))
                noescape = 1
                hidden = 1
            }
            120 {
                id = detail_ueber_handschriften
                type = Text
                # query = PEE100:("%1$s") AND source:("HS")
                query = (facet_names_relations:("%1$s") AND listview_type:("Handschriften"))
                noescape = 1
                hidden = 1
            }
            121 {
                id = detail_ueber_bundo
                type = Text
                # query = PEE100:("%1$s") AND source:("BI")
                query = (facet_names_relations:("%1$s") AND listview_type:("Bilder und Objekte"))
                noescape = 1
                hidden = 1
            }
            122 {
                id = detail_ueber_aundv
                type = Text
                # query = PEE100:("%1$s") AND DOKTYP:("Ton- und Bildträger")
                query = (facet_names_relations:("%1$s") AND (listview_type:("Audio") OR listview_type:("Video")))
                noescape = 1
                hidden = 1
            }
            123 {
                id = detail_ueber_bestaende
                type = Text
                query = PEE100:("%1$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }
            124 {
                id = detail_unter_bestaende
                type = Text
                query = PEU100:("%1$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }
            125 {
                id = detail_unter_bundo
                type = Text
                # query = PEU100:("%1$s") AND source:("BI")
                query = (facet_names_relations:("%1$s") AND listview_type:("Bilder und Objekte"))
                noescape = 1
                hidden = 1
            }
            126 {
                id = detail_unter_exemplare
                type = Text
                query = facet_names_relations:("%1$s") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            127 {
                id = detail_weitere_gedrucktes
                type = Text
                query = NOT PE0100:("%1$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND facet_names:("%2$s") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            128 {
                id = detail_weitere_handschriften
                type = Text
                query = NOT PE0100:("%1$s") AND NOT PEA100:("%1$s") AND facet_names:("%2$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            129 {
                id = detail_weitere_bundo
                type = Text
                query = NOT PE0100:("%1$s") AND NOT PEA100:("%1$s") AND facet_names:("%2$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            130 {
                id = detail_weitere_aundv
                type = Text
                query = NOT PE0100:("%1$s") AND facet_names:("%2$s") AND DOKTYP:("Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }

            131 {
                id = detail_all
                type = Text
                query = PE0100:("%1$s") OR PEA100:("%1$s") OR PEE100:("%1$s") OR PEU100:("%1$s")
                noescape = 1
                hidden = 1
            }

            132 {
                id = detail_weitere_daten
                type = Text
                query = NOT PE0100:("%1$s") AND NOT PEA100:("%1$s") AND facet_names:("%2$s") AND listview_type:("Daten")
                noescape = 1
                hidden = 1
            }

            133 {
                id = detail_weitere_bestaende
                type = Text
                query = NOT PE0100:("%1$s") AND NOT PEA100:("%1$s") AND facet_names:("%2$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }

            ## Werke ##

            140 {
                id = detail_werke_in_gedrucktes
                type = Text
                query = (GWKEY:("%1$s") OR AKE526:("%1$s")) AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            141 {
                id = detail_werke_in_handschriften
                type = Text
                query = GWKEY:("%1$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            142 {
                id = detail_werke_in_aundv
                type = Text
                query = (GWKEY:("%1$s") OR AKE526:("%1$s")) AND DOKTYP:("Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }

            143 {
                id = detail_werke_translation_gedrucktes
                type = Text
                # query = (GWKEY:("%1$s") OR AKE526:("%1$s")) AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND facet_form_content:("Übersetzung" OR "Übersetzung, deutsch" OR "Übersetzung, fremdsprachig") AND source:("AK")
                query = (%1$s) AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND facet_form_content:("Übersetzung" OR "Übersetzung, deutsch" OR "Übersetzung, fremdsprachig") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            144 {
                id = detail_werke_translation_handschriften
                type = Text
                query = GWKEY:("%1$s") AND source:("HS") AND facet_form_content:("Übersetzung" OR "Übersetzung, deutsch" OR "Übersetzung, fremdsprachig")
                noescape = 1
                hidden = 1
            }
            145 {
                id = detail_werke_translation_aundv
                type = Text
                query = (GWKEY:("%1$s") OR AKE526:("%1$s")) AND DOKTYP:("Ton- und Bildträger") AND facet_form_content:("Übersetzung" OR "Übersetzung, deutsch" OR "Übersetzung, fremdsprachig")
                noescape = 1
                hidden = 1
            }

            146 {
                id = detail_werke_ueber_gedrucktes
                type = Text
                query = AKEKEY:("%1$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }
            147 {
                id = detail_werke_ueber_handschriften
                type = Text
                query = AKY526:("%1$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            149 {
                id = detail_werke_ueber_bundo
                type = Text
                query = AKY526:("%1$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            150 {
                id = detail_werke_ueber_aundv
                type = Text
                query = AKEKEY:("%1$s") AND DOKTYP:("Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }
            151 {
                id = detail_werke_weitere_gedrucktes
                type = Text
                query = (NOT GWKEY:("%1$s") OR NOT AKE526:("%1$s")) AND (A0331:("%2$s") OR ATIT:("%2$s")) AND detail_urheber_ids:("%3$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }
            152 {
                id = detail_werke_weitere_handschriften
                type = Text
                query = NOT GWKEY:("%1$s") AND H41800:("%2$s") AND PE0100:("%3$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            153 {
                id = detail_werke_weitere_aundv
                type = Text
                query = (NOT GWKEY:("%1$s") OR NOT AKE526:("%1$s")) AND DOKTYP:("Ton- und Bildträger") AND (A0331:("%2$s") OR ATIT:("%2$s")) AND detail_urheber_ids:("%3$s")
                noescape = 1
                hidden = 1
            }
            154 {
                id = detail_werke_all
                type = Text
                query = GWKEY:("%1$s") OR AKE526:("%1$s") OR AKY526:("%1$s") OR AKEKEY:("%1$s")
                noescape = 1
                hidden = 1
            }
            155 {
                id = detail_werke_rezension_gedrucktes
                type = Text
                query = (%1$s) AND facet_form_content:("Rezension") AND source:("AK") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }
            156 {
                id = detail_werke_rezension_handschriften
                type = Text
                query = (%1$s) AND facet_form_content:("Rezension") AND source:("HS")
                noescape = 1
                hidden = 1
            }            
            157 {
                id = detail_werke_rezension_aundv
                type = Text
                query = (%1$s) AND facet_form_content:("Rezension") AND DOKTYP:("Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }            

            ### Köperschaften ###

            160 {
                id = detail_ks_von_gedrucktes
                type = Text
                query = (KSC200:("%1$s") OR KS0412:("%1$s")) AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND NOT GFUPE:("Widmungsempfänger" OR "Adressat" OR "Gefeierter") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            161 {
                id = detail_ks_von_handschriften
                type = Text
                query = KSC200:("%1$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            162 {
                id = detail_ks_von_bundo
                type = Text
                query = KSC200:("%1$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            163 {
                id = detail_ks_von_aundv
                type = Text
                query = (KSC200:("%1$s") OR KS0412:("%1$s")) AND DOKTYP:("Ton- und Bildträger") AND NOT GFUPE:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                noescape = 1
                hidden = 1
            }

            164 {
                id = detail_ks_an_gedrucktes
                type = Text
                query = KSC200:("%1$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND GFUPE:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                noescape = 1
                hidden = 1
            }
            165 {
                id = detail_ks_an_handschriften
                type = Text
                query = (KSA200:("%1$s") AND source:("HS")) OR (facet_names_relations:("%2$s") AND source:("AK"))
                noescape = 1
                hidden = 1
            }
            166 {
                id = detail_ks_an_bundo
                type = Text
                query = KSA200:("%1$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            167 {
                id = detail_ks_an_aundv
                type = Text
                query = KSC200:("%1$s") AND DOKTYP:("Ton- und Bildträger") AND GFUPE:("Widmungsempfänger" OR "Adressat" OR "Gefeierter")
                noescape = 1
                hidden = 1
            }
            168 {
                id = detail_ks_ueber_gedrucktes
                type = Text
                query = KSE200:("%1$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            169 {
                id = detail_ks_ueber_handschriften
                type = Text
                query = KSE200:("%1$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            170 {
                id = detail_ks_ueber_bundo
                type = Text
                query = KSE200:("%1$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            171 {
                id = detail_ks_ueber_aundv
                type = Text
                query = KSE200:("%1$s") AND DOKTYP:("Ton- und Bildträger")
                noescape = 1
                hidden = 1
            }
            172 {
                id = detail_ks_ueber_bestaende
                type = Text
                query = KSE200:("%1$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }
            173 {
                id = detail_ks_unter_bestaende
                type = Text
                query = KSU200:("%1$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }
            174 {
                id = detail_ks_unter_bundo
                type = Text
                query = KSU200:("%1$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            175 {
                id = detail_ks_unter_exemplare
                type = Text
                query = facet_names_relations:("%1$s") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            176 {
                id = detail_ks_weitere_gedrucktes
                type = Range
                query = NOT KSC200:("%1$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND facet_names:("%2$s") AND source:("AK")
                noescape = 1
                hidden = 1
            }
            177 {
                id = detail_ks_weitere_handschriften
                type = Range
                query = NOT KSC200:("%1$s") AND NOT KSA200:("%1$s") AND facet_names:("%2$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }
            178 {
                id = detail_ks_weitere_bundo
                type = Range
                query = NOT KSC200:("%1$s") AND NOT KSA200:("%1$s") AND facet_names:("%2$s") AND source:("BI")
                noescape = 1
                hidden = 1
            }
            179 {
                id = detail_ks_weitere_aundv
                type = Range
                query = NOT KSC200:("%1$s") AND DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND facet_names:("%2$s")
                noescape = 1
                hidden = 1
            }

            180 {
                id = detail_ks_all
                type = Text
                query = KSC200:("%1$s") OR KSA200:("%1$s") OR KSE200:("%1$s") OR KSU200:("%1$s")
                noescape = 1
                hidden = 1
            }

            181 {
                id = detail_ks_weitere_bestaende
                type = Range
                query = NOT KSC200:("%1$s") AND NOT KSA200:("%1$s") AND facet_names:("%2$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }

            182 {
                id = detail_ks_weitere_daten
                type = Range
                query = NOT KSC200:("%1$s") AND NOT KSA200:("%1$s") AND facet_names:("%2$s") AND listview_type:("Daten")
                noescape = 1
                hidden = 1
            }


            190 {
                id = detail_sb_schlagwort_gedrucktes
                type = Text
                query = THA710:("%1$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            191 {
                id = detail_sb_schlagwort_bundo
                type = Text
                query = (THA710:("%1$s") OR THA720:("%1$s")) AND source:("BI")
                noescape = 1
                hidden = 1
            }

            192 {
                id = detail_sb_schlagwort_handschriften
                type = Text
                query = THA710:("%1$s") AND source:("HS")
                noescape = 1
                hidden = 1
            }

            193 {
                id = detail_sb_schlagwort_aundv
                type = Text
                query = THA710:("%1$s") AND DOKTYP:("Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            194 {
                id = detail_sb_schlagwort_bestaende
                type = Text
                query = THA710:("%1$s") AND source:("BF")
                noescape = 1
                hidden = 1
            }

            195 {
                id = detail_sb_all
                type = Text
                query = THA710:("%1$s") AND source:("AK" OR "BI" OR "HS" OR "BF")
                noescape = 1
                hidden = 1
            }

            200 {
                id = detail_kette_gedrucktes
                type = Text
                query = SYNKEY:("%1$s") AND NOT DOKTYP:("Werktitel" OR "Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            203 {
                id = detail_kette_aundv
                type = Text
                query = SYNKEY:("%1$s") AND DOKTYP:("Ton- und Bildträger") AND source:("AK")
                noescape = 1
                hidden = 1
            }

            204 {
                id = detail_kette_exemplare
                type = Text
                query = XX_AU_SYNKEY_AUKEY:("%1$s")
                noescape = 1
                hidden = 1
            }

            205 {
                id = detail_kette_all
                type = Text
                query = SYNKEY:("%1$s") OR XX_AU_SYNKEY_AUKEY:("%1$s")
                noescape = 1
                hidden = 1
            }

            220 {
                id = detail_fs_systematikketten
                type = Text
                query = (SYKEY:("%1$s") OR FOKEY:("%1$s")) AND TYP:("Kette Bibliothek") AND source:("SE")
                noescape = 1
                hidden = 1
            }

            221 {
                id = detail_fs_pnormdaten
                type = Text
                query = PS7260:("%1$s") AND source:("PE")
                noescape = 1
                hidden = 1
            }

            222 {
                id = detail_fs_ksnormdaten
                type = Text
                query = PS7260:("%1$s") AND source:("KS")
                noescape = 1
                hidden = 1
            }

            223 {
                id = detail_fs_autorenschemaketten
                type = Text
                query = AUKEY:("%1$s") AND source:("SE")
                noescape = 1
                hidden = 1
            }

            224 {
                id = detail_fs_bibliographieketten
                type = Text
                query = SYKEY:("%1$s") AND TYP:("Bibliographie-Kette") AND source:("SE")
                noescape = 1
                hidden = 1
            }

            225 {
                id = detail_fs_all
                type = Text
                query = (SYKEY:("%1$s") OR FOKEY:("%1$s") OR AUKEY:("%1$s")) OR (PS7260:("%2$s") AND source:("KS" OR "PE"))
                noescape = 1
                hidden = 1
            }




            ### Right column search ###
            300 {
                id = detail_ks_rightcolumn
                type = Text
                query = KSC200:("%1$s") source:("AK") AND DOKTYP:("Werktitel")
                noescape = 1
                hidden = 1
            }

            310 {
                id = detail_pe_rightcolumn
                type = Text
                query = PE0100:("%1$s") source:("AK") AND DOKTYP:("Werktitel")
                noescape = 1
                hidden = 1
            }


            ###### EXTENDED SEARCH #######
            500 {
                id = author
                type = Text
                query = (advanced_names:(%1$s) OR P0800:(%1$s) OR K0800:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            501 {
                id = author_von
                type = Text
                query = (advanced_von:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            502 {
                id = author_an
                type = Text
                query = (advanced_an:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            503 {
                id = author_ueber
                type = Text
                query = (advanced_ueber:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            504 {
                id = author_unter
                type = Text
                query = (advanced_unter:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            505 {
                id = title
                type = Text
                query = ((listview_title:(%1$s) AND (source:("AK") OR source:("BF") OR source:("HS") OR source:("BI") OR source:("dbis") OR source:("ezb"))) OR ATIT:(%1$s) OR H41840:(%1$s) OR H41820:(%1$s) OR H41850:(%1$s) OR B51801:(%1$s) OR XX_AK_TITREG_GWKEY:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            506 {
                id = title_ueber
                type = Text
                query = (XX_AK_ATIT_AKEKEY:(%1$s) OR XX_AK_TITREG_AKY526:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            507 {
                id = date
                type = Text
                query = (facet_time:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechardate
                hidden = 1
            }
            508 {
                id = date_von
                type = Text
                query = (facet_time:[%1$s TO *])
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechardate
                hidden = 1
            }
            509 {
                id = date_bis
                type = Text
                query = (facet_time:[* TO %1$s])
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechardate
                hidden = 1
            }
            510 {
                id = new_von
                type = Text
                query = (filter_new:[%1$s TO *])
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            511 {
                id = new_bis
                type = Text
                query = (filter_new:[* TO %1$s])
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            512 {
                id = place
                type = Text
                query = (H02010:(%1$s) OR A0410:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            513 {
                id = numbers
                type = Text
                query = (id:(%1$s) OR advanced_numbers:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            514 {
                id = signatur
                type = Text
                query = (B41600:(%1$s) OR XX_AU_WSIGNA_AUKEY:(%1$s) OR XX_BF_B41600_HSBFKY:(%1$s) OR XX_BF_B41600_BFKEY:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            515 {
                id = exemplar
                type = Text
                query = (XX_AU_FBTIT_AUKEY:(%1$s) OR XX_AU_BEMEXT_AUKEY:(%1$s) OR XXX_SE_REGTIT_XX_AU_SYNKEY_AUKEY:(%1$s) OR XX_AU_VIRAUF_AUKEY:(%1$s))
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            516 {
                id = searchall
                type = Text
                query = (%1$s)
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }
            517 {
                id = not_date
                type = Text
                query = (-facet_time:*)
                noescape = 2
                escapechar < plugin.tx_find.settings.escapechar
                hidden = 1
            }

            ### Systematikverlinkung ###

            600 {
                id = seisn
                type = Text
                query = SEISN:(%1$s)
                noescape = 1
                hidden = 1
            }
            610 {
                id = sys_suche
                type = Text
                query = SYNKEY:(%1$s) OR XX_AU_SYNKEY_AUKEY:(%1$s)
                noescape = 1
                hidden = 1
            }

            700 {
                id = k10plus
                type = Text
                query = A0025:(%1$s)
                noescape = 1
                hidden = 1
                redirectToDetail = 1
            }

            ### Exemplarsuche ###

            999 {
                id = copy_search
                type = Text
                query = AUKEY:("%1$s")
                noescape = 1
                hidden = 1
            }



        }

        # In den folgenden beiden Abschnitten wird die Behandlung von zu verlinkenden Feldern in der Detailansicht
        # konfiguriert.
        # "queryFieldForDataField" gibt an, in welchem Feld nach dem Inhalt des aktuellen Feldes gesucht werden soll.
        # "displayFieldForDataField" gibt an, welches Feld aus dem gefundenen Datensatz angezeigt werden soll.
        queryFieldForDataField {
            BIKEY = B00001
            XX_AU_AKKEY_AUKEY = A0001
            HSOKEY = H0001
            HSBFKY = B0001
            PE0100 = P0001
            PEA100 = P0001
            PEE100 = P0001
            PEU100 = P0001
            KSA200 = K0001
            KSC200 = K0001
            KSE200 = K0001
            KSU200 = K0001
            AKY526 = A0001
            AKA451 = A0001
            THA720 = THSISN
            THA710 = THSISN
            BFKEY = B0001
            AUKEY = AUISN
            HSKEY = H0001
            A0001 = AKKEY
            INDKEY = MOISN
            BUEBKY = B0001
            GWKEY = A0001
            H0001 = HSOKEY
            P0001 = PE0100,PEA100,PEE100,PEU100
            B00001 = BIOKEY
            BIOKEY = B00001
            SEISN = SYNKEY
            SYNKEY = SEISN
            VORKOP = SYISN
            NACKOP = SYISN
        }
        displayFieldForDataField {
            BIKEY = B51800
            XX_AU_AKKEY_AUKEY = TITREG
            HSOKEY = H41811
            HSBFKY = B41600
            PE0100 = P0800
            PEA100 = P0800
            PEE100 = P0800
            PEU100 = P0800
            KSA200 = K0800
            KSC200 = K0800
            KSE200 = K0800
            KSU200 = K0800
            AKY526 = A0331
            AKA451 = A0331
            THA720 = SWVF
            THA710 = SWVF
            BFKEY = B41600
            AUKEY = AKKEY
            HSKEY = H41800
            A0001 = SIGNA
            INDKEY = MOTITL
            BUEBKY = B41600
            GWKEY = TITREG
            H0001 = H41811,KUTIT
            P0001 = A0331,B51800,KUTIT
            B00001 = B41600,B41650,B51800,B04000
            #BIOKEY = B41600,B41650,B51800,B04000
            BIOKEY = B51800
            SEISN = A0331
            SYNKEY = REGTIT
            VORKOP = VORKOP,NOTAT,TITEL
            NACKOP = NOTAT,TITEL
        }

        # Der Abschnitt "additionalFilters" definiert globale Suchfilter
        additionalFilters {
            1 = NOT source:(AU OR MM)
            2 = NOT filter_hidden:true
        }

        # Der Abschnitt "standardFields" definiert die in der Trefferliste anzuzeigenden Felder in der Form
        # {$Partialsvariable} = {$Solrfeld}
        standardFields {
            #title = listview_title
            listview_type = listview_type
            listview_type_cardinality = listview_type_cardinality
            listview_associate = listview_associate
            listview_title = listview_title
            listview_additional1 = listview_additional1
            listview_additional2 = listview_additional2
            picture_midi = picture_midi
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
                    f0 = id
                    f1 = listview_type
                    f2 = listview_type_cardinality
                    f3 = listview_associate
                    f4 = listview_title
                    f5 = listview_additional1
                    f6 = listview_additional2
                    f7 = picture_midi
                    f8 = facet_medium
                    f9 = detail_restrictions
                    f10 = XX_AU_PRSTAT_AUKEY
                    f11 = XX_MM_MOTITL_INDKEY
                    f12 = XX_MM_MOPFAD_INDKEY
                    f13 = INDKEY
                    f14 = AURL
                    f15 = BEMURL
                    f16 = filter_digital
                }
            }
            detail {
                default {
                    f0 = *
                }
            }
            data < plugin.tx_find.settings.dataFields.detail
        }

        highlight {
            default {
                fields {
                    f0 = id
                    f1 = listview_type
                    f2 = listview_type_cardinality
                    f3 = listview_associate
                    f4 = listview_title
                    f5 = listview_additional1
                    f6 = listview_additional2
                    f7 = picture_midi
                    f8 = B41660
                }
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
                sortCriteria = facet_time_stat asc
            }
            3 {
                id = Jahr absteigend
                sortCriteria = facet_time_stat desc
            }

            5 {
                id = Titel (A-Z)
                sortCriteria = sorted_listview_title_s asc
            }

            6 {
                id = Titel(Z-A)
                sortCriteria = sorted_listview_title_s desc
            }

            # is used as a static parameter in "Namen und Werke"
            7 {
                id = Normdaten absteigend
                sortCriteria = entity_score desc
            }
        }

        # Der Abschnitt "facets" definiert die angebotenen Suchfacetten. Hier können auch Tabs konfiguriert werden.
        # Zur Erläuterung der Parameter siehe die typo3-find-Dokumentation auf GitHub.
        facets {
            10 {
                id = Digital
                field = filter_digital
                type = Switch
                text = nur digitale Medien
                query = filter_digital:true
                removeHeadline = true
            }
            11 {
                id = Medientypen
                field = listview_type
                type = Bar
                sortOrder = count
                query = listview_type:("%s")
                reverseFacet = 1
            }

            15 {
                id = FormUndInhalt
                field = facet_form_content
                query = facet_form_content:("%s")
                label = Form und Inhalt
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelmissing = nicht bestimmt
                reverseFacet = 1
                ajax = 0
                displayDefault = 6
            }

            20 {
                id = Medium
                field = facet_medium
                sortOrder = count
                query = facet_medium:("%s")
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelmissing = nicht bestimmt
                reverseFacet = 1
                ajax = 0
                displayDefault = 6
            }

            25 {
                id = Personen
                label = Personen & Körperschaften
                type = Decisiontree
                field = facet_names
                query = facet_names:("%s")
                reverseFacet = 1
                relationField1 = facet_names_relations
                relationField2 = facet_names_roles
            }

            30 {
                id = Zeit
                label = Zeit
                type = Histogramslider
                field = facet_time
                facettype = date_range
                start = NOW/YEAR-35YEARS
                end = NOW
                gap = +1YEAR
                displayDateFormat = Y
                collapse = 1
            }

            35 {
                id = Thema
                field = facet_subject
                query = facet_subject:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelmissing = nicht bestimmt
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
                        query = filter_new:[NOW/DAY-7DAYS TO NOW/DAY+1DAY]
                    }

                    20 {
                        id = Monat
                        query = filter_new:[NOW/DAY-1MONTH TO NOW/DAY+1DAY]
                    }

                    30 {
                        id = Quartal
                        query = filter_new:[NOW/DAY-3MONTHS TO NOW/DAY+1DAY]
                    }

                    40 {
                        id = Halbjahr
                        query = filter_new:[NOW/DAY-6MONTHS TO NOW/DAY+1DAY]
                    }

                    50 {
                        id = Jahr
                        query = filter_new:[NOW/DAY-1YEAR TO NOW/DAY+1DAY]
                    }
                }
                showMissing = 0

                collapse = 1
                ajax = 0
                displayDefault = 6
            }

            #45 {
            #    id = Sprache
            #    field = facet_language
            #    query = facet_language:("%s")
            #    collapse = 1
            #    # Shows a facet value which includes all "not known" items
            #    showMissing = 1
            #    labelmissing = nicht bestimmt
            #    reverseFacet = 1
            #}

            46 {
                id = Sprache
                label = Sprache
                type = Decisiontree
                field = facet_language
                query = facet_language:("%s")
                reverseFacet = 1
                relationField1 = facet_language_type
                collapse = 1
            }

            47 {
                id = facet_language_type
                type = List
                field = facet_language_type
                query = facet_language_type:("%s")
                hidden = 1
            }

            #50 {
            #    id = Ort
            #    field = facet_location
            #    query = facet_location:("%s")
            #    collapse = 1
            #    # Shows a facet value which includes all "not known" items
            #    showMissing = 1
            #    labelmissing = nicht bestimmt
            #    reverseFacet = 1
            #}

            51 {
                id = Ort
                label = Ort
                type = Decisiontree
                field = facet_location
                query = facet_location:("%s")
                reverseFacet = 1
                relationField1 = facet_location_type
                collapse = 1
            }

            52 {
                id = facet_location_type
                type = List
                field = facet_location_type
                query = facet_location_type:("%s")
                hidden = 1
            }

            53 {
                id = Sammlung
                field = filter_collection
                query = filter_collection:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelmissing = nicht bestimmt
                reverseFacet = 1
                fixed = 1
            }

            65 {
                id = Datenbestand
                field = facet_source
                query = facet_source:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelmissing = nicht bestimmt
                reverseFacet = 1
                ajax = 0
                displayDefault = 6
            }

            66 {
                id = Bibliographie
                field = filter_bibliography
                query = filter_bibliography:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showMissing = 1
                labelmissing = nicht bestimmt
                reverseFacet = 1
                ajax = 0
                displayDefault = 6
            }

            # Die nächsten konfigurierten Facetten sind für die Sucheinschränkung oberhalb des Suchschlitzes
            # Konkret für Audio & Video und Namen & Werke
            70 {
                id = DatenbestandHidden
                facettype = multi_select_facet
                field = facet_source
                query = facet_source:("%s")
                hidden = 1
                fetchMinimum = 0
            }

            77 {
                id = MedientypenHidden
                facettype = multi_select_facet
                field = listview_type
                sortOrder = count
                query = listview_type:("%s")
            }

            89 {
                id = facet_names_roles
                type = List
                field = facet_names_roles
                query = facet_names_roles:("%s")
                hidden = 1
            }

            90 {
                id = facet_names_relations
                type = List
                field = facet_names_relations
                query = facet_names_relations:("%s")
                hidden = 1
            }

            # Facette zur Selektion der beiden relevantesten Normdatentreffern zum aktuellen Trefferset
            99 {
                id = entities
                field = entity_ids
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

        detailViews {
            0 {
                type = Bilder und Objekte
                fields {
                    f0001 = A0410
                    f0002 = A0412
                    f0003 = A0425
                    f002 = XX_PE_listview_title
                    f003 = XX_PE_P0800_PEU100
                    f004 = XX_PE_P0800_PEE100
                    f005 = XX_PE_P0800_PEA100
                    f006 = XX_PE_P0800_PE0100
                    f007 = GFUPUE
                    f0 = B51400
                    f1 = B51800
                    f2 = B51801
                    f3 = PEU100
                    f4 = XX_KS_K0800_KSE200
                    f5 = B52010
                    f6 = B02040
                    f7 = B0204A
                    f8 = B52020
                    f9 = B52010
                    f10 = B02050
                    f11 = B52001
                    f12 = B52002
                    f13 = B52003
                    f14 = B52004
                    f15 = B52011
                    f16 = B01610
                    f17 = B51420
                    f18 = XX_AK_A0331_AKY526
                    f19 = XX_TH_SWAF_THA720
                    f20 = XX_TH_SWAF_THA710
                    f21 = B02040
                    f22 = XX_BI_B51800_BIOKEY
                    f23 = picture_midi
                }
            }

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
                    title = listview_title
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
