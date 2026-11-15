<?php
defined('TYPO3') || die();

// TYPO3 v12 kennt die Gruppe "plugins" der Inhaltstypen noch nicht.
$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups']['plugins']
    ??= 'LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:plugins';

// Such-Plugin aus der ehemaligen Extension dla/find (Plugin-Signatur find_find).
// Das Icon muss angegeben werden, weil TYPO3 sonst das Extension-Icon von EXT:find sucht.
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Find',
    'Find',
    'LLL:EXT:dla_opac_ng/Resources/Private/Language/locallang_be.xlf:ce.title',
    'content-plugin',
    'plugins',
    'LLL:EXT:dla_opac_ng/Resources/Private/Language/locallang_be.xlf:ce.description'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'DlaOpacNg',
    'DlaStart',
    'DlaStart',
    'content-plugin',
    'plugins'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'DlaOpacNg',
    'DlaCollection',
    'DlaCollection',
    'content-plugin',
    'plugins'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'DlaOpacNg',
    'DlaClassification',
    'DlaClassification',
    'content-plugin',
    'plugins'
);
