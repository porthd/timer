<?php
defined('TYPO3_MODE') || die();

use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

//see https://www.sebkln.de/tutorials/typo3-datenbanktabellen-um-neue-felder-erweitern/ visited 2020-oct-05

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
            'default' => true,
        ],
    ],
    'tx_timer_timer' => [
        'exclude' => true,
        'label' => 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer_general.field.tx_timer_timer',
        'l10n_mode' => 'prefixLangTitle',
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
    '--div--;LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer.tca.general.div.timerParams.lable,' .
    'tx_timer_scheduler, tx_timer_selector, tx_timer_timer, ',
    '1'
);
