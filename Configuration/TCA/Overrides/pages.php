<?php

declare(strict_types=1);

defined('TYPO3') || die();


use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

//see https://www.sebkln.de/tutorials/typo3-datenbanktabellen-um-neue-felder-erweitern/ visited 2020-oct-05

$tmp_timer_columns = [
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
            'default' => true,
        ],
    ],
    TimerConst::TIMER_FIELD_FLEX_ACTIVE => [
        'exclude' => true,
        'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_general.field.tx_timer_timer',
        'l10n_mode' => 'prefixLangTitle',
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
        'l10n_mode' => 'prefixLangTitle',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'allowNonIdValues' => true,
            'items' => TcaUtility::mergeSelectorItems(),
            'behaviour' => [
                'allowLanguageSynchronization' => true,
            ],
        ],
        'onChange' => 'reload',
    ],
];

ExtensionManagementUtility::addTCAcolumns('pages', $tmp_timer_columns);

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer.tca.general.div.timerParams.label,' .
    TimerConst::TIMER_FIELD_SCHEDULER . ', ' . TimerConst::TIMER_FIELD_SELECTOR . ', ' . TimerConst::TIMER_FIELD_FLEX_ACTIVE . ', ',
    '1'
);
