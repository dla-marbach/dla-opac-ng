<?php

return [
    'frontend' => [
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