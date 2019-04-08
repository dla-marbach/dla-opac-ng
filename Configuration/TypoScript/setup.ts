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
page.includeJS.find = EXT:find/Resources/Public/JavaScript/find.js

page.includeJSFooter.autocomplete = EXT:dla_opac_ng/Resources/Public/JavaScript/autocomplete.js

page.includeJSFooter.dla_opac_ng = EXT:dla_opac_ng/Resources/Public/JavaScript/dla-opac-ng.js

page.includeJS.nouislider = EXT:dla_opac_ng/Resources/Public/JavaScript/nouislider.js
page.includeCSS.nouislider =  EXT:dla_opac_ng/Resources/Public/CSS/nouislider.css

#page.includeJS.detailtabs = EXT:dla_opac_ng/Resources/Public/JavaScript/detailtabs.js

page.includeCSS.opac-ng =  EXT:dla_opac_ng/Resources/Public/CSS/opac-ng.css


#page.includeCSS.site =  https://www-test-ng.dla-marbach.de/fileadmin/lombego/layout/css/site.min.css
#page.includeCSS.belugino =  EXT:dla_opac_ng/Resources/Public/CSS/belugino.css
#page.includeCSS.catalog =  EXT:dla_opac_ng/Resources/Public/CSS/catalog.css

page.includeCSS.tokeninput = EXT:dla_opac_ng/Resources/Public/CSS/token-input.css


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

        <link rel="stylesheet" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/css/site.min.css" media="all">
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
    <link rel="apple-touch-icon" sizes="57x57" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/apple-touch-icon-180x180.png">
    <link rel="icon" type="image/png" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/android-chrome-192x192.png" sizes="192x192">
    <link rel="icon" type="image/png" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/manifest.json">
    <link rel="mask-icon" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/safari-pinned-tab.svg" color="#96ba3a">
    <link rel="shortcut icon" href="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/favicon.ico">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/mstile-144x144.png">
    <meta name="msapplication-config" content="https://www-test.dla-marbach.de/fileadmin/lombego/layout/images/favicons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
)
}
9999 = TEXT
9999 {
    value (
        <script src="https://www-test.dla-marbach.de/fileadmin/lombego/layout/js/init-live.min.js"></script>
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

        mainQueryOperator = AND

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
                class = autocomplete
                type = Text
                # setzt den Standardoperator auf ein logisches UND
                //query = {!q.op=AND}*:%s
                query = %s
                # schaltet die Maskierung von Steuerzeichen auf den Modus 2 um.
                # Gemeinsam mit escapechar werden dann die entsprechenden Zeichen maskiert
                noescape = 2
                escapechar = 
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
            picture_mini = picture_midi
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
                sortCriteria = A0425 asc,H02035 asc,B02040 asc,PA8141 asc,PA8142 asc,KA805 asc
            }
            3 {
                id = Jahr absteigend
                sortCriteria = A0425 desc,H02035 desc,B02040 desc,PA8141 desc,PA8142 desc,KA805 desc
            }

            4 {
                id = Titel (A-Z)
                sortCriteria = listview_title asc
            }

            5 {
                id = Titel (A-Z bibliographisch)
                sortCriteria = sorted_listview_title_s asc
            }

            # is used as a static parameter in "Namen und Werke"
            6 {
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
                text = digital
                query = filter_digital:true
                removeHeadline = true
            }
            11 {
                id = Medientypen
                field = icon_facet
                type = Bar
                sortOrder = count
                query = icon_facet:("%s")
            }

            15 {
                id = FormUndInhalt
                field = facet_form_content
                query = facet_form_content:("%s")
                label = Form und Inhalt
                # Shows a facet value which includes all "not known" items
                showmissing = 1
                labelmissing = nicht bestimmt
            }

            20 {
                id = Medium
                field = facet_medium
                sortOrder = count
                query = facet_medium:("%s")
                # Shows a facet value which includes all "not known" items
                showmissing = 1
                labelmissing = nicht bestimmt
            }

            25 {
                id = Ort
                field = facet_location
                query = facet_location:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showmissing = 1
                labelmissing = nicht bestimmt
            }

            30 {
                id = Thema
                field = facet_subject
                query = facet_subject:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showmissing = 1
                labelmissing = nicht bestimmt
            }

            35 {
                id = Sprache
                field = facet_language
                query = facet_language:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showmissing = 1
                labelmissing = nicht bestimmt
            }

            40 {
                id = NeuImKatalog
                label = Neu im Katalog
                type = Timerange
                field = filter_new
                #query = filter_new:%s
                showmissing = 1
                #start = NOW/YEAR-35YEARS
                #end = NOW
                #gap = +1YEAR
                #displayDateFormat = Y
                collapse = 1
            }

            45 {
                id = Sammlung
                field = filter_collection
                query = filter_collection:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showmissing = 1
                labelmissing = nicht bestimmt
            }

            50 {
                id = PersonenKöperschaften
                label = Personen & Körperschaften
                field = facet_names
                query = facet_names:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showmissing = 1
                labelmissing = nicht bestimmt
            }

            55 {
                id = PersonenKöperschaftenFunktionsbezeichnung
                label = Funktionsbezeichnung
                field = facet_roles
                query = facet_roles:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showmissing = 1
                labelmissing = nicht bestimmt
            }

            60 {
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

            65 {
                id = Datenbestand
                field = facet_source
                query = facet_source:("%s")
                collapse = 1
                # Shows a facet value which includes all "not known" items
                showmissing = 1
                labelmissing = nicht bestimmt
            }

            # Die nächsten konfigurierten Facetten sind für die Sucheinschränkung oberhalb des Suchschlitzes
            # Konkret für Audio & Video und Namen & Werke
            66 {
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
                field = icon_facet
                sortOrder = count
                query = icon_facet:("%s")
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

page.config.contentObjectExceptionHandler = 0
