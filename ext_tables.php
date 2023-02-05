<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') || die('Access denied.');

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020,2023 Dr. Dieter Porth <info@mobger.de>
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
    static function () {
        ExtensionManagementUtility::allowTableOnStandardPages(
            'tx_timer_domain_model_event'
        );

        ExtensionManagementUtility::allowTableOnStandardPages(
            'tx_timer_domain_model_listing'
        );


        // Add backend preview hook
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['timer_timersimul'] =
            Porthd\Timer\Hooks\PageLayoutViewDrawItem::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['timer_periodlist'] =
            Porthd\Timer\Hooks\PageLayoutViewDrawItem::class;

        // add cshfiles for main table/model
        $modelList = [
            'tx_timer_domain_model_event',
            'tx_timer_domain_model_listing',
            'tt_content',
            'pages',
            'sys_file_reference',
        ];
        foreach ($modelList as $model) {
            $filepath = 'EXT' . ':' . 'timer/Resources/Private/Language/locallang_csh_' . $model . '.xlf';
            ExtensionManagementUtility::addLLrefForTCAdescr(
                $model,
                $filepath
            );
        }
    }
);
