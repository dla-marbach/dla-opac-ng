<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Find configuration for DLA Catalog',
    'description' => '',
    'category' => 'plugin',
    'author' => '',
    'author_email' => '',
    'state' => 'beta',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearCacheOnLoad' => false,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-7.3.99',
            'typo3' => '8.7.0-8.7.99',
            'typo3_console' => '5.0.0-5.9.99',
            'find' => '3.0.0-3.1.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Dla\\DlaOpacNg\\' => 'Classes/'
        ]
    ]
];
