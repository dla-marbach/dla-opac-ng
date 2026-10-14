<?php

// Nur für die lokale Entwicklungsumgebung (task install), Datenbankzugang steht in settings.php
$GLOBALS['TYPO3_CONF_VARS'] = array_replace_recursive(
    $GLOBALS['TYPO3_CONF_VARS'],
    [
        // cHash deaktivieren
        'FE' => [
            'cacheHash' => [
                'enforceValidation' => false,
            ],
        ],
        // This GFX configuration allows processing by ImageMagick, if installed
        'GFX' => [
            'processor' => 'ImageMagick',
            'processor_path' => '/usr/bin/',
            'processor_path_lzw' => '/usr/bin/',
        ],
        // E-Mails nicht versenden, sondern in var/log/mail.mbox ablegen
        'MAIL' => [
            'transport' => 'mbox',
            'transport_mbox_file' => \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/log/mail.mbox',
        ],
        'SYS' => [
            'trustedHostsPattern' => '.*.*',
            'devIPmask' => '*',
            'displayErrors' => 1,
        ],
    ]
);
