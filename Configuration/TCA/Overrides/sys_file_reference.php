<?php

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') || die();
call_user_func(function (){

$tmp_timer_columns = [
    'tx_timer_scheduler' => [
        'exclude' => true,
        'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_general.field.tx_timer_scheduler',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'items' => [
                [
                    0 => '',
                    1 => '',
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
            'ds_pointerField' => 'tx_timer_selector',
            'ds' => TcaUtility::mergeNameFlexformArray(),
            'behaviour' => [
                'allowLanguageSynchronization' => true,
            ],
        ],
    ],
    'tx_timer_selector' => [
        'exclude' => true,
        'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_general.field.tx_timer_selector',
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
            'default' => '0',
            'eval' => 'datetime,int',
            'renderType' => 'inputDateTime',
            'type' => 'input',
            'readOnly' => true,
        ],
    ],
    'endtime' => [
        'exclude' => true,
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
        'l10n_display' => 'defaultAsReadonly',
        'l10n_mode' => 'exclude',
        'config' => [
            'default' => '0',
            'eval' => 'datetime,int',
            'renderType' => 'inputDateTime',
            'type' => 'input',
            'readOnly' => true,
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('sys_file_reference', $tmp_timer_columns);

ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_reference',
    '--div--;LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer.tca.general.div.timerParams.label,' .
    'tx_timer_scheduler, tx_timer_selector, tx_timer_timer,starttime,endtime,'
);


$GLOBALS['TCA']['sys_file_reference']['palettes']['imageoverlayPalette']['showitem'] .= ',--linebreak--,tx_timer_timer,tx_timer_selector,--linebreak--,starttime,endtime,';
});
