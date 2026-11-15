<?php
defined('TYPO3') or die();

// Die Plugins sind eigene Inhaltselemente (CType = Plugin-Signatur), weil TYPO3 v14 den CType "list" mit
// list_type nicht mehr kennt. Vorhandene Inhaltselemente stellt der Upgrade-Wizard
// \Dla\DlaOpacNg\Updates\PluginListTypeToCTypeUpdate um.

// Such-Plugin aus der ehemaligen Extension dla/find. Extension- und Plugin-Name "Find" bleiben
// erhalten, damit Plugin-Signatur (find_find), URL-Parameter (tx_find_find) und TypoScript
// (plugin.tx_find) unverändert funktionieren.
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Find',
    'Find',
    [
        \Dla\Find\Controller\SearchController::class => 'index, detail, suggest',
    ],
    [
        \Dla\Find\Controller\SearchController::class => 'index, detail, suggest',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'DlaOpacNg',
    'DlaStart',
    [
        \Dla\DlaOpacNg\Controller\StartController::class => 'start',
    ],
    [
        \Dla\DlaOpacNg\Controller\StartController::class => 'start',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'DlaOpacNg',
    'DlaCollection',
    [
        \Dla\DlaOpacNg\Controller\CollectionController::class => 'index',
    ],
    [
        \Dla\DlaOpacNg\Controller\CollectionController::class => 'index',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'DlaOpacNg',
    'DlaClassification',
    [
        \Dla\DlaOpacNg\Controller\ClassificationController::class => 'index',
    ],
    [
        \Dla\DlaOpacNg\Controller\ClassificationController::class => 'index',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Übergang für TYPO3 v12 und v13: Inhaltselemente, die der Upgrade-Wizard noch nicht umgestellt hat
// (CType "list" mit list_type), werden weiterhin ausgegeben.
if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 14) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'dla_opac_ng',
        'setup',
        '
tt_content.list.20.find_find =< tt_content.find_find.20
tt_content.list.20.dlaopacng_dlastart =< tt_content.dlaopacng_dlastart.20
tt_content.list.20.dlaopacng_dlacollection =< tt_content.dlaopacng_dlacollection.20
tt_content.list.20.dlaopacng_dlaclassification =< tt_content.dlaopacng_dlaclassification.20
',
        'defaultContentRendering'
    );
}

// Alle tx_find_find Parameter von der cHash Generierung ausschließen, um unnötige page level locks zu vermeiden.
// Extension ist oben als USER_INT definiert, daher werden die Inhalte der Extension ohnehin nicht gecached.
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = array_merge(
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] ?? [],
    [
        '^tx_find_find', // alle Parameter, die mit tx_find_find beginnen
        '^tx_dlaopacng', // alle Parameter, die mit tx_dlaopacng beginnen
        '^tx_web2pdf', // alle Parameter, die mit tx_web2pdf beginnen (PDF-Link auf Detailseiten)
    ]
);
