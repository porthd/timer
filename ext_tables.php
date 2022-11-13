<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') || die('Access denied.');

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 Dr. Dieter Porth <info@mobger.de>
 *
 *  All rights reserved
 *
 *  This script is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


call_user_func(
    function () {

        ExtensionManagementUtility::addLLrefForTCAdescr(
            'tx_timer_domain_model_event',
            'EXT:timer/Resources/Private/Language/locallang_csh_tx_timer_domain_model_event.xlf'
        );
        ExtensionManagementUtility::allowTableOnStandardPages(
            'tx_timer_domain_model_event'
        );

        ExtensionManagementUtility::addLLrefForTCAdescr(
            'tx_timer_domain_model_listing',
            'EXT:timer/Resources/Private/Language/locallang_csh_tx_timer_domain_model_listing.xlf'
        );
        ExtensionManagementUtility::allowTableOnStandardPages(
            'tx_timer_domain_model_listing'
        );

        // Add backend preview hook
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['timer_timersimul'] =
            Porthd\Timer\Hooks\PageLayoutViewDrawItem::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['timer_periodlist'] =
            Porthd\Timer\Hooks\PageLayoutViewDrawItem::class;

        $modelList = ['tx_timer_domain_model_listing','tt_content','pages','sys_file_reference'];
        $timerList = ['daily','dateperiod','default','easterrel','moonphaserel','moonriserel','periodlist','rangelist',
            'sunriserel','weekdayinmonth','weekdayly',];
        foreach($modelList as $model) {
            foreach($timerList as $fieldValue) {
                ExtensionManagementUtility::addLLrefForTCAdescr(
                    $model . '.tx_timer_timer.txTimer'.ucfirst($fieldValue),
                    'EXT:timer/Resources/Private/Language/FlexForms/locallang_csh_generaltimer.xlf');
                $filepath = 'EXT'.':'.'timer/Resources/Private/Language/FlexForms/locallang_csh_'.
                    $fieldValue.'timer.xlf';
                ExtensionManagementUtility::addLLrefForTCAdescr(
                    $model . '.tx_timer_timer.txTimer'.ucfirst($fieldValue),
                    $filepath);
            }
        }

    }
);