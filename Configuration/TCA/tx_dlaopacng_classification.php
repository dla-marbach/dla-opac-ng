<?php

return [
    'ctrl' => [
        'title'     => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_classification',
        'label'     => 'treeview_title_classification',
        'default_sortby' => 'ORDER BY uid',
        'rootLevel' => 0,
        'searchFields' => 'record_id,treeview_title_classification,listview_title,listview_type,listview_associate,listview_additional1,listview_additional2',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => '',
    ],
    'interface' => [
        'showRecordFieldList' => 'treeview_title_classification,record_id',
        'maxDBListItems' => 25,
        'maxSingleDBListItems' => 50,
    ],
    'columns' => [
        'record_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_classification.record_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 100,
                'eval' => 'nospace,required',
                'default' => '',
            ],
        ],
        'parent_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_classification.parent_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 100,
                'eval' => 'nospace,required',
                'default' => '',
            ],
        ],
        'treeview_title_classification' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_classification.treeview_title_classification',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'required,trim',
                'default' => '',
            ],
        ],
        'listview_title' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_classification.listview_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'listview_type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_classification.listview_type',
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
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_classification.listview_associate',
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
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_classification.listview_additional1',
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
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlaopacng_classification.listview_additional2',
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
            'label' => 'LLL:EXT:dla_opac_ng/Resources/Private/Language/TCA.xml:tx_dlfopacng_classification.hasChild',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'record_id,parent_id,treeview_title_classification,listview_title,listview_type,listview_associate,listview_additonal1,listview_additional2,hasChild'],
    ]
];
