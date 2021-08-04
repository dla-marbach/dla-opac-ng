<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Dla.' . 'DlaOpacNg',
    'DlaStart',
    [
        'Start' => 'start',
        'Export' => 'csv',
    ],
    [
        'Start' => 'start',
        'Export' => 'csv',
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

// Add BackendLayouts
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/PageTS/BackendLayouts.txt">');

