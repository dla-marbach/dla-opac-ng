<?php
defined('TYPO3') or die();

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
