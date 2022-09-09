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
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-7.4.99',
            'typo3' => '10.4.0-10.4.99',
            'find' => '3.0.0-3.1.99',
            'vhs' => '6.0.1-6.9.9'
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
