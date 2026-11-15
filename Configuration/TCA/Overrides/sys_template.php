<?php
defined('TYPO3') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'dla_opac_ng',
    'Configuration/TypoScript',
    'Find configuration for DLA Catalog'
);
