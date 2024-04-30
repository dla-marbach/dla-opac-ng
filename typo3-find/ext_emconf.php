<?php

$EM_CONF['find'] = [
    'title' => 'Find',
    'description' => 'A frontend for Solr indexes',
    'version' => '5.0.0',
    'state' => 'beta',
    'category' => 'plugin',
    'clearCacheOnLoad' => true,
    'author' => 'Sven-S. Porst, Ingo Pfennigstorf',
    'author_email' => 'pfennigstorf@sub.uni-goettingen.de',
    'author_company' => 'SUB Göttingen',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.1.99',
            'typo3' => '10.4.0-12.4.99',
            'felogin' => '10.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            ['Subugoe\\Find\\' => 'Classes'],
        ],
    ],
];
