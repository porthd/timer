<?php

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3_MODE') || die();


call_user_func(function () {
    // Parts of code, which can by the extension-constants be controlled

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
                    ],
                ],
                'default' => true,
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
    ];

    ExtensionManagementUtility::addTCAcolumns('tt_content', $tmp_timer_columns);

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer.tca.general.div.timerParams.label,' .
        'tx_timer_scheduler, tx_timer_selector, tx_timer_timer,'
    );

    // Parts of code, which can by the extension-constants be controlled
    $timerConfig = GeneralUtility::makeInstance(
        ExtensionConfiguration::class
    )->get(TimerConst::EXTENSION_NAME);

    // define example-content-element
    if (!empty($timerConfig['flagTestContent'])) {
        $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['timer_timersimul'] = 'tx_timer_timersimul';
        $tempTypes = [
            'timer_timersimul' => [
                'columnsOverrides' => [
                    'bodytext' => [
                        'config' => [
                            'richtextConfiguration' => 'timer_timersimul',
                            'enableRichtext' => 1,
                        ],
                    ],
                ],
                'showitem' => '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,header,bodytext,--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,--palette--;;language,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,--palette--;;hidden,--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,pages,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,--div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.tabs.category,categories,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,rowDescription,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,--div--;LLL:EXT:svt/Resources/Private/Language/locallang_db.xlf:tt_content.tab.tx_svt_alternative_partials,tx_svt_alternative_partials,svt_timer,--div--;LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer.tca.general.div.timerParams.label,tx_timer_scheduler,tx_timer_selector,tx_timer_timer,',
            ],
        ];
        $GLOBALS['TCA']['tt_content']['types'] += $tempTypes;
        // Adds the content element to the "Type" dropdown
        ExtensionManagementUtility::addTcaSelectItem(
            'tt_content',
            'CType',
            [
                'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer.tca.element.name.timerTimersimul',
                'timer_timersimul',
                'tx_timer_timersimul',
            ],
            'textmedia',
            'after'
        );


        // will set in the ext_localconf.php
        ExtensionManagementUtility::addStaticFile(
            'timer',
            'Configuration/TypoScript/',
            'Timer'
        );
    }

    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['timer_periodlist'] = 'tx_timer_periodlist';
    $tempTypes = [
        'timer_periodlist' => [
            'columnsOverrides' => [
                'bodytext' => [
                    'config' => [
                        'richtextConfiguration' => 'timer_timersimul',
                        'enableRichtext' => 1,
                    ],
                ],
//                'tx_timer_scheduler' => [
//                    'config' => [
//                        'default' => 0,
//                        'readOnly' => 1,
//                    ],
//                ],
//                // = 'tx_timer_timer'
//                TimerConst::TIMER_FIELD_FLEX_ACTIVE => [
//                    'config' => [
//                        'default' => 'txTimerPeriodList',
//                        'readOnly' => 1,
//                    ],
//                ],
            ],
            'showitem' => $GLOBALS['TCA']['tt_content']['types']['textmedia']['showitem'] ??'--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general, --palette--;;general, --palette--;;headers, bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.media, assets, --palette--;;mediaAdjustments, --palette--;;gallerySettings, --palette--;;imagelinks, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance, --palette--;;frames, --palette--;;appearanceLinks, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, --palette--;;language, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, --palette--;;hidden, --palette--;;access, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories, categories, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes, rowDescription, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended, --div--;LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer.tca.general.div.timerParams.label, tx_timer_scheduler, tx_timer_selector, tx_timer_timer, tx_wysiwyg3_template, tx_wysiwyg3_definition,',
        ],
    ];
    $GLOBALS['TCA']['tt_content']['types'] += $tempTypes;
    // Adds the content element to the "Type" dropdown
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:tx_timer.tca.element.name.timerPeriodlist',
            'timer_periodlist',
            'tx_timer_periodlist',
        ],
        'textmedia',
        'after'
    );
});
