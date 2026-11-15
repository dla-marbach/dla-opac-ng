<?php

return [
    'frontend' => [
        // Die Ajax-Endpunkte (ajax/*) brauchen kein TypoScript und laufen deshalb vor dessen Aufbereitung:
        // ab TYPO3 v13 bricht diese mit "No page configured for type=..." ab, weil die Bestandsübersicht
        // den Parameter "type" (collection/classification) verwendet, den TYPO3 als Seitentyp auswertet.
        'Dla/dla_opac_ng/proof-of-work' => [
            'target' => \Dla\DlaOpacNg\Middleware\ProofOfWorkMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'typo3/cms-frontend/site-resolver',
            ],
        ],
        'Dla/dla_opac_ng/ajax/getentity' => [
            'target' => \Dla\DlaOpacNg\Ajax\GetEntity::class,
            'after' => [
                'typo3/cms-frontend/page-argument-validator',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'Dla/dla_opac_ng/ajax/getentities' => [
            'target' => \Dla\DlaOpacNg\Ajax\GetEntities::class,
            'after' => [
                'typo3/cms-frontend/page-argument-validator',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'Dla/dla_opac_ng/ajax/watchlistexportcsv' => [
            'target' => \Dla\DlaOpacNg\Ajax\ExportController::class,
            'after' => [
                'typo3/cms-frontend/page-argument-validator',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'Dla/dla_opac_ng/ajax/autocomplete' => [
            'target' => \Dla\DlaOpacNg\Ajax\Autocomplete::class,
            'after' => [
                'typo3/cms-frontend/page-argument-validator',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'Dla/dla_opac_ng/ajax/decisiontree' => [
            'target' => \Dla\DlaOpacNg\Ajax\Decisiontree::class,
            'after' => [
                'typo3/cms-frontend/page-argument-validator',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'Dla/dla_opac_ng/ajax/collection' => [
            'target' => \Dla\DlaOpacNg\Ajax\Collection::class,
            'after' => [
                'typo3/cms-frontend/page-argument-validator',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ]
    ],
];