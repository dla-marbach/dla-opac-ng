<?php
defined('TYPO3') || die('Access denied.');


\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'DlaOpacNg',
    'DlaStart',
    'DlaStart'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'DlaOpacNg',
    'DlaCollection',
    'DlaCollection'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'DlaOpacNg',
    'DlaClassification',
    'DlaClassification'
);

call_user_func(
    function()
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('dla_opac_ng', 'Configuration/TypoScript', 'Find configuration for DLA Catalog');

    }
);
