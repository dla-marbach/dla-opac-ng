<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Subugoe.' . 'find',
    'FindStart',
    [
        'Start' => 'start',
    ],
    [
        'Start' => 'start',
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['autocomplete'] = 'EXT:dla_opac_ng/Classes/Ajax/Autocomplete.php';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['getEntity'] = 'EXT:dla_opac_ng/Classes/Ajax/GetEntity.php';

// Add BackendLayouts
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/PageTS/BackendLayouts.txt">');
