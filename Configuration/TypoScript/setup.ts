lib.dynamicContent = COA
lib.dynamicContent {
    10 = LOAD_REGISTER
    10.colPos.cObject = TEXT
    10.colPos.cObject {
        field = colPos
        ifEmpty.cObject = TEXT
        ifEmpty.cObject {
            value.current = 1
            ifEmpty = 0
        }
    }
    20 = CONTENT
    20 {
        table = tt_content
        select {
            orderBy = sorting
            where = colPos={register:colPos}
            where.insertData = 1
        }
    }
    90 = RESTORE_REGISTER
}

page = PAGE
page.typeNum = 0
page.10 = FLUIDTEMPLATE
page.10 {

    #templateName = Default
    templateName = TEXT
    templateName.stdWrap {
        cObject = TEXT
        cObject {
            data = levelfield:-2,backend_layout_next_level,slide
            override.field = backend_layout
            split {
                token = pagets__
                1.current = 1
                1.wrap = |
            }
        }
        ifEmpty = StartPage
    }


    layoutRootPaths {
        10 = EXT:dla_opac_ng/Resources/Private/Page/Layouts
    }
    partialRootPaths {
        10 = EXT:dla_opac_ng/Resources/Private/Page/Partials
    }
    templateRootPaths {
        10 = EXT:dla_opac_ng/Resources/Private/Page/Templates
    }

}





# Für einige Funktionen in der Anzeige benötigt die Extension das Javascript-Framework jQuery
# Einbindung einer lokalen Kopie wegen Beschränkungen im internen Netz des DLA
page.includeJS.jquery = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery-1.11.0.min.js
page.includeJS.jquery-plot = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery.flot.min.js
page.includeJS.jquery-plot-select = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery.flot.selection.js
# Zur Darstellung der Icons in der Trefferliste wird Font Awesome verwendet
page.includeJS.fa = https://use.fontawesome.com/96352f148e.js
page.includeJSFooter.find = EXT:find/Resources/Public/JavaScript/find.js

page.includeCSS.opac-ng =  EXT:dla_opac_ng/Resources/Public/CSS/opac-ng.css


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
                    host = serene.dla-marbach.de
                    port = 8983
                    path = /solr/opac-ng
                    scheme = http
                }
            }
        }

        connection {
            host = serene.dla-marbach.de
            port = 8983
            path = /solr/opac-ng
            timeout = 10
            scheme = http
        }


        # Der Abschnitt "languageRootPath" definiert, wo die Übersetzungsdateien zu finden sind.
        languageRootPath = EXT:dla_opac_ng/Resources/Private/Language/

        # Der Anschnitt "defaultQuery" legt fest, welche Suche initial ausgeführt werden soll, wenn
        # ein Nutzer den Katalog aufruft. Da kein Trefferset erscheinen soll, ist hier eine Suche
        # definiert, die garantiert keine Treffer erzeugt.
        defaultQuery = *:*

        features.eDisMax = 1

        # Der Abschnitt "queryFields" definiert die zur Verfügung stehenden Suchfelder.
        # Der Index "0" beschreibt den Standardsuchschlitz, weitere Indizes können zusätzliche
        # Suchfelder (z.B. für eine erweiterte Suche) beinhalten.
        # Zur Erläuterung der Parameter siehe die typo3-find-Dokumentation auf GitHub.
        queryFields {
            0 {
                # definiert das Solr-Feld für die Standardsuche
                id = default
                type = Text
                # setzt den Standardoperator auf ein logisches UND
                query = {!q.op=AND}*:%s
                # schaltet die Maskierung von Steuerzeichen aus und erlaubt Phrasensuchen und Trunkierungen
                noescape = 1
            }
            1 {
                # definiert exemplarisch ein Suchfeld für die erweiterte Suche
                id = Person
                type = Text
                query = XX_PE_P0800_PE0100:(%s)
                noescape = 1
                # zeigt das Suchfeld nur in der erweiterten Suche an
                extended = 1
            }
            2 {
                # definiert eine Intervall-Suche exemplarisch über Lebensdaten
                id = Lebensspanne
                type = Range
                query = PA8141:[* %2$s] AND PA8142:[%1$s TO *]
                default.0 = *
                default.1 = *
                extended = 1
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
            HSOKEY = H41811,KUTIT
            HSBFKY = BFNAM
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
            BFKEY = BFNAM
            AUKEY = AKKEY
            HSKEY = H41800
            A0001 = SIGNA
            INDKEY = MOTITL
            BUEBKY = BFNAM
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
            picture_mini = picture_mini
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
                    f7 = picture_mini
                }
            }
            detail {
                default {
                    f0 = *
                }
                disallow {
                    f0 = listview_type
                    f1 = listview_type_cardinality
                    f2 = listview_associate
                    f3 = listview_title
                    f4 = listview_additional1
                    f5 = listview_additional2
                    f6 = picture_mini
                }
            }
            data < plugin.tx_find.settings.dataFields.detail
        }

        # Der Abschnitt "sort" definiert die Sortierung der Trefferliste und die Nutzeroptionen.
        sort {
            1 {
                id = default
                sortCriteria = score desc
            }
            2 {
                id = Alphabetisch
                sortCriteria = listview_title asc
            }
            3 {
                id = Geburtstag
                sortCriteria = PA8141 asc
            }
        }

        # Der Abschnitt "facets" definiert die angebotenen Suchfacetten. Hier können auch Tabs konfiguriert werden.
        # Zur Erläuterung der Parameter siehe die typo3-find-Dokumentation auf GitHub.
        facets {
            10 {
                id = Medientyp
                field = icon_facet
                sortOrder = count
                query = icon_facet:("%s")
            }
            20 {
                id = Gattung/Form
                field = type_facet
                sortOrder = count
                query = type_facet:("%s")
            }
            30 {
                id = Bestand
                field = source
                sortOrder = count
            }
            40 {
                id = Zeitraum
                field = A0425
                type = Histogram
                sortOrder = count
                query = A0425("%s")
            }
            50 {
                id = Standort
                field = H02010
                sortOrder = count
                query = H02010("%s")
            }
        }

        # Der Abschnitt "paging" definiert die Anzahl der Treffer pro Seite sowie das Navigieren durch das
        # Trefferset in der Detailanzeige.
        paging {
            perPage = 25
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
                    f23 = picture_mini
                }
            }

        }

    }
}
