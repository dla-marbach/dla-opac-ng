<?php
defined('TYPO3_MODE') || die('Access denied.');


\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Subugoe.' . 'find',
    'FindStart',
    'FindStart'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dla.' . 'DlaOpacNg',
    'DlaTectonic',
    'DlaTectonic'
);

call_user_func(
    function()
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('dla_opac_ng', 'Configuration/TypoScript', 'Find configuration for DLA Catalog');

    }
);
