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

// Dev only (needed if no templates existing)
//\FluidTYPO3\Flux\Core::registerProviderExtensionKey('Dla.DlaOpacNg', 'Content');
//\FluidTYPO3\Flux\Core::registerProviderExtensionKey('Dla.DlaOpacNg', 'Page');


