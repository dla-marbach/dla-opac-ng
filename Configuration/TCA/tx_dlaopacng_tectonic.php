<?php

return [
    'ctrl' => [
        'title'     => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_tectonic',
        'label'     => 'listview_title',
        'default_sortby' => 'ORDER BY uid',
        'rootLevel' => 0,
        'searchFields' => 'record_id,listview_title,listview_type,listview_associate,listview_additional1,listview_additional2',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => '',
    ],
    'interface' => [
        'showRecordFieldList' => 'listview_title,record_id',
        'maxDBListItems' => 25,
        'maxSingleDBListItems' => 50,
    ],
    'columns' => [
        'record_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_tectonic.record_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 100,
                'eval' => 'nospace,required,uniqueInPid',
                'default' => '',
            ],
        ],
        'parent_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_tectonic.parent_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 100,
                'eval' => 'nospace,required',
                'default' => '',
            ],
        ],
        'listview_title' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_tectonic.listview_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'required,trim',
                'default' => '',
            ],
        ],
        'listview_type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_tectonic.listview_type',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'listview_associate' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_tectonic.listview_associate',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'listview_additional1' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_tectonic.listview_additional1',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'listview_additional2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_tectonic.listview_additional2',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'hasChild' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlfopacng_tectonic.hasChild',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'record_id,parent_id,listview_title,listview_type,listview_associate,listview_additonal1,listview_additional2,hasChild'],
    ]
];