<?php
declare(strict_types=1);

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();
call_user_func(function () {
    $tmp_timer_columns = [
        TimerConst::TIMER_FIELD_SCHEDULER => [
            'exclude' => true,
            'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_general.field.tx_timer_scheduler',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'invertStateDisplay' => true,
                        TimerConst::TCA_ITEMS_LABEL => '',
                    ]
                ],
                'default' => false,
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
            ],
            'onChange' => 'reload',
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'datetime',
                'readOnly' => 0,
                'default' => 0,
                'nullable' => true,
                'dbType' => 'datetime',
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'l10n_display' => 'defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'datetime',
                'readOnly' => 0,
                'default' => 0,
                'nullable' => true,
                'dbType' => 'datetime',
            ],
        ],
    ];

    ExtensionManagementUtility::addTCAcolumns('sys_file_reference', $tmp_timer_columns);

    ExtensionManagementUtility::addToAllTCAtypes(
        'sys_file_reference',
        '--div--;LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer.tca.general.div.timerParams.label,' .
        TimerConst::TIMER_FIELD_SCHEDULER . ', ' . TimerConst::TIMER_FIELD_SELECTOR . ', ' . TimerConst::TIMER_FIELD_FLEX_ACTIVE . ',starttime,endtime,'
    );


    $GLOBALS['TCA']['sys_file_reference']['palettes']['imageoverlayPalette']['showitem'] .= ',--linebreak--,' .
        TimerConst::TIMER_FIELD_FLEX_ACTIVE . ',' . TimerConst::TIMER_FIELD_SELECTOR . ',--linebreak--,starttime,endtime,';
});
