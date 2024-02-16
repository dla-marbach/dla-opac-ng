<?php

return [
    'frontend' => [
        'Subugoe/Find/Ajax/Facets' => [
            'target' => \Subugoe\Find\Ajax\Facets::class,
            'after' => [
                'typo3/cms-frontend/site'
            ],
            'before' => [
                'typo3/cms-frontend/backend-user-authentication'
            ],
        ],
    ],
];
