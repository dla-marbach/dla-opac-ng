<?php
defined('TYPO3') or die();

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
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'DlaOpacNg',
    'DlaStart',
    [
        \Dla\DlaOpacNg\Controller\StartController::class => 'start',
    ],
    [
        \Dla\DlaOpacNg\Controller\StartController::class => 'start',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'DlaOpacNg',
    'DlaCollection',
    [
        \Dla\DlaOpacNg\Controller\CollectionController::class => 'index',
    ],
    [
        \Dla\DlaOpacNg\Controller\CollectionController::class => 'index',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'DlaOpacNg',
    'DlaClassification',
    [
        \Dla\DlaOpacNg\Controller\ClassificationController::class => 'index',
    ],
    [
        \Dla\DlaOpacNg\Controller\ClassificationController::class => 'index',
    ]
);

// Alle tx_find_find Parameter von der cHash Generierung ausschließen, um unnötige page level locks zu vermeiden.
// Extension ist oben als USER_INT definiert, daher werden die Inhalte der Extension ohnehin nicht gecached.
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = array_merge(
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] ?? [],
    [
        '^tx_find_find', // alle Parameter, die mit tx_find_find beginnen
        '^tx_dlaopacng', // alle Parameter, die mit tx_dlaopacng beginnen
    ]
);
