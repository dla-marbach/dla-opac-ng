<?php

return [
    'frontend' => [
        'Dla/dla_opac_ng/proof-of-work' => [
            'target' => \Dla\DlaOpacNg\Middleware\ProofOfWorkMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'typo3/cms-frontend/site-resolver',
            ],
        ],
        // Nutzungsstatistik: innerhalb der PoW (abgewiesene Requests zählen nicht), vor den AJAX-Endpunkten,
        // damit auch deren Aufrufe gezählt werden.
        'Dla/dla_opac_ng/statistics' => [
            'target' => \Dla\DlaOpacNg\Middleware\StatisticsMiddleware::class,
            'after' => [
                'typo3/cms-frontend/page-resolver',
                'Dla/dla_opac_ng/proof-of-work',
            ],
            'before' => [
                'Dla/dla_opac_ng/ajax/getentity',
                'Dla/dla_opac_ng/ajax/getentities',
                'Dla/dla_opac_ng/ajax/watchlistexportcsv',
                'Dla/dla_opac_ng/ajax/autocomplete',
                'Dla/dla_opac_ng/ajax/decisiontree',
                'Dla/dla_opac_ng/ajax/collection',
            ],
        ],
        'Dla/dla_opac_ng/ajax/getentity' => [
            'target' => \Dla\DlaOpacNg\Ajax\GetEntity::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ],
        ],
        'Dla/dla_opac_ng/ajax/getentities' => [
            'target' => \Dla\DlaOpacNg\Ajax\GetEntities::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ],
        ],
        'Dla/dla_opac_ng/ajax/watchlistexportcsv' => [
            'target' => \Dla\DlaOpacNg\Ajax\ExportController::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ],
        ],
        'Dla/dla_opac_ng/ajax/autocomplete' => [
            'target' => \Dla\DlaOpacNg\Ajax\Autocomplete::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ],
        ],
        'Dla/dla_opac_ng/ajax/decisiontree' => [
            'target' => \Dla\DlaOpacNg\Ajax\Decisiontree::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ],
        ],
        'Dla/dla_opac_ng/ajax/collection' => [
            'target' => \Dla\DlaOpacNg\Ajax\Collection::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ],
        ]
    ],
];