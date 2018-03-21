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

    layoutRootPaths {
        10 = EXT:dla_opac_ng/Resources/Private/Page/Layouts
    }
    partialRootPaths {
        10 = EXT:dla_opac_ng/Resources/Private/Page/Partials
    }
    templateRootPaths {
        10 = EXT:dla_opac_ng/Resources/Private/Page/Templates
    }

    file.cObject = CASE
    file.cObject {
        key {
            data = levelfield:-1, backend_layout_next_level, slide
            override.field = backend_layout
        }

        default = TEXT
        default.value = EXT:dla_opac_ng/Resources/Private/Page/Templates/Default.html

        pagets__dlaOpacNgDefault = TEXT
        pagets__dlaOpacNgDefault.value = EXT:dla_opac_ng/Resources/Private/Page/Templates/DlaOpacNgDefault.html

        pagets__startPage = TEXT
        pagets__startPage.value = EXT:dla_opac_ng/Resources/Private/Page/Templates/StartPage.html
    }
}

# Für einige Funktionen in der Anzeige benötigt die Extension das Javascript-Framework jQuery
# Einbindung einer lokalen Kopie wegen Beschränkungen im internen Netz des DLA


page.includeJS.jquery = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery-1.11.0.min.js
page.includeJS.jquery-plot = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery.flot.min.js
page.includeJS.jquery-plot-select = EXT:dla_opac_ng/Resources/Public/JavaScript/jquery.flot.selection.js
# Zur Darstellung der Icons in der Trefferliste wird Font Awesome verwendet
#page.includeJS.fa = https://use.fontawesome.com/96352f148e.js
page.includeJSFooter.find = EXT:find/Resources/Public/JavaScript/find.js

page.includeJSFooter.dla_opac_ng = EXT:dla_opac_ng/Resources/Public/JavaScript/dla-opac-ng.js


page.includeCSS.opac-ng =  EXT:dla_opac_ng/Resources/Public/CSS/opac-ng.css

#page.includeCSS.site =  https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/css/site.min.css
#page.includeCSS.belugino =  EXT:dla_opac_ng/Resources/Public/CSS/belugino.css
#page.includeCSS.catalog =  EXT:dla_opac_ng/Resources/Public/CSS/catalog.css



page {

#bodyTag >
bodyTagCObject = TEXT
bodyTagCObject.field = uid
bodyTagCObject.wrap = <body class="page-|">

    headerData {

    10 = TEXT
    10 {
        value = {page:title}
        wrap = <title>|&#32;- DLA Marbach</title>
        insertData = 1
    }

		## Meta viewport
		##---------------------------------------

        11 = TEXT
    11.value (

        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
)


    50 = TEXT
    50.value (

        <link rel="stylesheet" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/css/site.min.css" media="all">
        <link rel="stylesheet" href="typo3conf/ext/dla_opac_ng/Resources/Public/CSS/belugino.css" media="all">
        <link rel="stylesheet" href="typo3conf/ext/dla_opac_ng/Resources/Public/CSS/catalog.css" media="all">

        <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-title" content="DLA Marbach">
    <meta name="application-name" content="DLA Marbach">

    <!--<script src="https://www-test-ng.dla-marbach.de/typo3conf/ext/lombego_setup/Resources/Public/Build/JavaScript/vendor/modernizr-2.7.1.min.js"></script>-->
    <script src="//use.typekit.net/jvr5rnf.js"></script>
        <script>try{Typekit.load();}catch(e){}</script>
)

    70 = TEXT
    70.value (
    <link rel="apple-touch-icon" sizes="57x57" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-180x180.png">
    <link rel="icon" type="image/png" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/android-chrome-192x192.png" sizes="192x192">
    <link rel="icon" type="image/png" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/manifest.json">
    <link rel="mask-icon" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/safari-pinned-tab.svg" color="#96ba3a">
    <link rel="shortcut icon" href="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/favicon.ico">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/mstile-144x144.png">
    <meta name="msapplication-config" content="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/images/favicons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
)
}
9999 = TEXT
9999 {
    value (
        <script src="https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/js/init-live.min.js"></script>
    )
}
}


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
            #40 {
            #    id = Zeitraum
            #    field = A0425
            #    type = Histogram
            #    sortOrder = count
            #    query = A0425("%s")
            #}
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

        indexPageUid = 3

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
[globalVar = GP:L = 1]
config {
    sys_language_uid = 1
    language = en
    locale_all = en_US.UTF-8
    htmlTag_setParams = lang="en" dir="ltr" class="no-js"
}
[global]