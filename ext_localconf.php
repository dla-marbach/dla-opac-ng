<?php
defined('TYPO3') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'DlaOpacNg',
    'DlaStart',
    [
        \Dla\DlaOpacNg\Controller\StartController::class => 'start',
        \Dla\DlaOpacNg\Controller\ExportController::class => 'csv',
    ],
    [
        \Dla\DlaOpacNg\Controller\StartController::class => 'start',
        \Dla\DlaOpacNg\Controller\ExportController::class => 'csv',
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

// Dev only (needed if no templates existing)
\FluidTYPO3\Flux\Core::registerProviderExtensionKey('DlaOpacNg', 'Content');
\FluidTYPO3\Flux\Core::registerProviderExtensionKey('DlaOpacNg', 'Page');


