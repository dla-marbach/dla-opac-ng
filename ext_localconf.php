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
    'DlaTectonic',
    [
        'Tectonic' => 'index',
    ],
    [
        'Tectonic' => 'index',
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['autocomplete'] = 'EXT:dla_opac_ng/Classes/Ajax/Autocomplete.php';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['getEntity'] = 'EXT:dla_opac_ng/Classes/Ajax/GetEntity.php';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['decisiontree'] = 'EXT:dla_opac_ng/Classes/Ajax/Decisiontree.php';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tectonic'] = 'EXT:dla_opac_ng/Classes/Ajax/Tectonic.php';

// Add BackendLayouts
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/PageTS/BackendLayouts.txt">');
