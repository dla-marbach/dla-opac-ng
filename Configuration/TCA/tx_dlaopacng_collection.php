<?php

$tca = [
    'ctrl' => [
        'title'     => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_collection',
        'label'     => 'displayTree',
        'default_sortby' => 'ORDER BY uid',
        'rootLevel' => 0,
    ],
    'columns' => [
        'record_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_collection.record_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 100,
                'eval' => 'nospace',
                'required' => true,
                'default' => '',
            ],
        ],
        'parent_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_collection.parent_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 100,
                'eval' => 'nospace',
                'required' => true,
                'default' => '',
                'searchable' => false,
            ],
        ],
        'displayTree' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_collection.displayTree',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'required' => true,
                'default' => '',
            ],
        ],
        'display' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_collection.display',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'displayName' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_collection.displayName',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'displayAddition1' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_collection.displayAddition1',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'displayAddition2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_collection.displayAddition2',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'facet_value' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_collection.facet_value',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'required' => true,
                'default' => '',
                'searchable' => false,
            ],
        ],
        'hasChild' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlfopacng_collection.hasChild',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'record_id,parent_id,displayTree,display,displayName,displayAddition1,displayAddition2,facet_value,hasChild'],
    ]
];

// Bis TYPO3 v13: durchsuchbare Felder und Zeilenzahl der Listenansicht. TYPO3 v14 wertet beides nicht mehr aus,
// dort sind Eingabefelder durchsuchbar, sofern sie nicht mit 'searchable' => false ausgenommen sind.
if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 14) {
    $tca['ctrl']['searchFields'] = 'record_id,displayTree,display,displayName,displayAddition1,displayAddition2';
    $tca['interface'] = [
        'maxDBListItems' => 25,
        'maxSingleDBListItems' => 50,
    ];
}

return $tca;
