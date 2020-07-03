<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Dla.' . 'DlaOpacNg',
    'DlaStart',
    [
        'Start' => 'start',
    ],
    [
        'Start' => 'start',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Dla.' . 'DlaOpacNg',
    'DlaCollection',
    [
        'Collection' => 'index',
    ],
    [
        'Collection' => 'index',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Dla.' . 'DlaOpacNg',
    'DlaClassification',
    [
        'Classification' => 'index',
    ],
    [
        'Classification' => 'index',
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['autocomplete'] = 'EXT:dla_opac_ng/Classes/Ajax/Autocomplete.php';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['getEntity'] = 'EXT:dla_opac_ng/Classes/Ajax/GetEntity.php';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['decisiontree'] = 'EXT:dla_opac_ng/Classes/Ajax/Decisiontree.php';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['collection'] = 'EXT:dla_opac_ng/Classes/Ajax/Collection.php';

// Add BackendLayouts
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/PageTS/BackendLayouts.txt">');

// Add RealURL configuration
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('realurl')) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['postVarSets']['_DEFAULT'] = [
        'opac' => [
            [
                'GETvar' => 'tx_find_find[action]',
                'valueMap' => [
                    'id' => 'detail',
                    'suche' => 'index',
                ],
                'noMatch' => 'bypass',
            ],
            [
                'GETvar' => 'tx_find_find[id]',
            ],
            // Ignore 'tx_find_find[controller]' in URLs because it is always "Search" which is the default anyway.
            [
                'GETvar' => 'tx_find_find[controller]',
                'valueMap' => [],
                'noMatch' => 'null',
            ],
        ]
    ];
}
