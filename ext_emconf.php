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
            'typo3' => '8.7.0-8.7.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Dla\\DlaOpacNg\\' => 'Classes/'
        ],
        'classmap' => [
            'Classes/Controller/StartController.php'
        ]
    ]
];
