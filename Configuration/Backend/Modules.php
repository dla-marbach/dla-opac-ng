<?php

use Dla\DlaOpacNg\Controller\Backend\StatisticsController;

/**
 * Backend-Modul »Katalog-Statistik« (Web > Katalog-Statistik). Freigabe über die Modulrechte der Backend-Gruppen.
 */
return [
    'dlaopacng_statistics' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/opac-statistics',
        'iconIdentifier' => 'dlaopacng-module-statistics',
        'labels' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/locallang_statistics.xlf',
        'inheritNavigationComponentFromMainModule' => false,
        'routes' => [
            '_default' => [
                'target' => StatisticsController::class . '::indexAction',
            ],
            'data' => [
                'path' => '/data',
                'target' => StatisticsController::class . '::dataAction',
            ],
        ],
    ],
];
