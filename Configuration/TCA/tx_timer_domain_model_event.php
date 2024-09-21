<?php

declare(strict_types=1);

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Utilities\TcaUtility;

defined('TYPO3') || die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_domain_model_event.model.title',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'title, description, ' .
            TimerConst::TIMER_FIELD_FLEX_ACTIVE . ', ' . TimerConst::TIMER_FIELD_SELECTOR . ', ' .
            'teaser_slogan, teaser_infotext,',
        'typeicon_classes' => [
            'default' => 'tx_timer_timericon',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'interface' => [
        'maxDBListItems' => 50,
        'maxSingleDBListItems' => 200
    ],
    'types' => [
        '1' => ['showitem' => '--div--;LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_domain_model_event.tab.single,' .
            'title, description,' .
            '--div--;LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_general.tab.timer,' .
            TimerConst::TIMER_FIELD_SCHEDULER . ', ' . TimerConst::TIMER_FIELD_SELECTOR . ', ' . TimerConst::TIMER_FIELD_FLEX_ACTIVE . ', ' .
            '--div--;LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_domain_model_event.tab.teaser,' .
            'teaser_slogan, teaser_infotext,  ' .
            '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,' .
            'hidden, --palette--;;timeranguage, flagTest,',
        ],
    ],
    'palettes' => [
        'timerlang' => [
            'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_domain_model_event.palette.timerlang',
            'showItem' => 'sys_language_uid, l10n_parent, l10n_diffsource,',
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => ['type' => 'language'],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 0,
                'items' => [
                    [
                        TimerConst::TCA_ITEMS_LABEL => '',
                        TimerConst::TCA_ITEMS_VALUE => 0,
                    ],
                ],
                'foreign_table' => 'tx_timer_domain_model_event',
                'foreign_table_where' => 'AND {#tx_timer_domain_model_event}.{#pid}=###CURRENT_PID### AND {#tx_timer_domain_model_event}.{#sys_language_uid} IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        't3ver_label' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'invertStateDisplay' => true,
                        TimerConst::TCA_ITEMS_LABEL => '',
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],

        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_domain_model_event.field.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_domain_model_event.field.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'enableRichtext' => true,
            ],
        ],

        TimerConst::TIMER_FIELD_SCHEDULER => [
            'exclude' => true,
            'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_general.field.tx_timer_scheduler',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'invertStateDisplay' => false,
                        TimerConst::TCA_ITEMS_LABEL => '',
                    ]
                ],
            ],
        ],
        TimerConst::TIMER_FIELD_FLEX_ACTIVE => [
            'exclude' => true,
            'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_general.field.tx_timer_timer',
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => TimerConst::TIMER_FIELD_SELECTOR,
                'ds' => TcaUtility::mergeNameFlexformArray(),
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'default' => 'default',
            ],
        ],
        TimerConst::TIMER_FIELD_SELECTOR => [
            'exclude' => true,
            'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_general.field.tx_timer_selector',
            'description' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_general.fieldDescription.tx_timer_selector',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'allowNonIdValues' => true,
                'items' => TcaUtility::mergeSelectorItems(),
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'default' => 'default',
            ],
            'onChange' => 'reload',
        ],
    ],
];
