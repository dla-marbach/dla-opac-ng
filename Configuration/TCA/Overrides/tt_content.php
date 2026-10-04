<?php
defined('TYPO3') || die();

// Such-Plugin aus der ehemaligen Extension dla/find (Plugin-Signatur find_find).
// Das Icon muss angegeben werden, weil TYPO3 sonst das Extension-Icon von EXT:find sucht.
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Find',
    'Find',
    'find',
    'content-plugin'
);
