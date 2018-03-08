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


// Add BackendLayouts
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/PageTS/BackendLayouts.txt">');
