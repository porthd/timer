<?php

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\DailyTimer;
use Porthd\Timer\CustomTimer\DatePeriodTimer;
use Porthd\Timer\CustomTimer\DefaultTimer;
use Porthd\Timer\CustomTimer\EasterRelTimer;
use Porthd\Timer\CustomTimer\MoonphaseRelTimer;
use Porthd\Timer\CustomTimer\MoonriseRelTimer;
use Porthd\Timer\CustomTimer\PeriodListTimer;
use Porthd\Timer\CustomTimer\RangeListTimer;
use Porthd\Timer\CustomTimer\SunriseRelTimer;
use Porthd\Timer\CustomTimer\WeekdayInMonthTimer;
use Porthd\Timer\CustomTimer\WeekdaylyTimer;
use Porthd\Timer\CustomTimer\JewishHolidayTimer;
use Porthd\Timer\CustomTimer\CalendarDateRelTimer;
use Porthd\Timer\Hooks\Backend\FlexformManipulationHook;
use Porthd\Timer\Hooks\Backend\StartEndTimerManipulationHook;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

use Porthd\Timer\Hooks\FlexFormParsingHook;
use Porthd\Timer\Utilities\ConfigurationUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    static function () {
        // Hook for dynamically generated flexformsfiles
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'][] =
            FlexFormParsingHook::class;

        // declare namespace in fluid-taemplates
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['timer'] = ['Porthd\\Timer\\ViewHelpers'];

        // the icons for the content-element and for the extension  of the extension icon
        $iconRegistry = GeneralUtility::makeInstance(
            IconRegistry::class
        );
        foreach ([
                     'tx_timer-timer' => 'EXT:timer/Resources/Public/Icons/icon_timer.svg',
                     'tx_timer_timersimul' => 'EXT:timer/Resources/Public/Icons/Content/timersimul.svg',
                     'tx_timer_periodlist' => 'EXT:timer/Resources/Public/Icons/Content/periodlist.svg',
                     'tx_timer_holidaycalendar' => 'EXT:timer/Resources/Public/Icons/Content/holidaycalendar.svg',
                 ] as $name => $path
        ) {
            $iconRegistry->registerIcon(
                $name, // Icon-Identifier, e.g. tx-myext-action-preview
                SvgIconProvider::class,
                ['source' => $path]
            );
        }
        // allow default-values like `now` `+1 day` or similiar as default-Value in Flexforms
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'][] =
            FlexformManipulationHook::class;
        // reset starttime and endtime after changes in tx_timer_select and tx_timer_timer
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
            StartEndTimerManipulationHook::class;

        // Parts of code, which can by the extension-constants be controlled
        $timerConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get(TimerConst::EXTENSION_NAME);


        if (!empty($timerConfig['flagTestContent'])) {
            //automatically integrated typoScript for content-element `timersimul`
            ExtensionManagementUtility::addTypoScriptConstants(
                "@import 'EXT:timer/Configuration/TypoScript/constants.typoscript' "
            );
            ExtensionManagementUtility::addTypoScriptSetup(
                "@import 'EXT:timer/Configuration/TypoScript/setup.typoscript' "
            );
        }


        // disallow usage of timer from the extension via the configuration
        if (!empty($timerConfig['useInternalTimer'])) {
            $addTimerFlags = (int)$timerConfig['useInternalTimer'];
            $listOfTimerClasses = [
                DailyTimer::class, // => 1
                DatePeriodTimer::class, // => 2
                DefaultTimer::class, // => 4
                EasterRelTimer::class,
                MoonphaseRelTimer::class,
                MoonriseRelTimer::class,
                PeriodListTimer::class,
                RangeListTimer::class,
                SunriseRelTimer::class,
                WeekdayInMonthTimer::class,
                WeekdaylyTimer::class,
                JewishHolidayTimer::class,
                CalendarDateRelTimer::class,
            ];
            ConfigurationUtility::addExtLocalconfTimerAdding($addTimerFlags, $listOfTimerClasses);


            $tsConfig =
                "@import 'EXT:timer/Configuration/TsConfig/Page/BackendPreview.tsconfig'" .
                "\n" .
                "@import 'EXT:timer/Configuration/TsConfig/Page/RteTimerSimul.tsconfig'";

            if (!empty($timerConfig['flagTimersimul'])) {
                $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['timer_timersimul'] = 'EXT:timer/Configuration/RTE/TimerTimersimul.yaml';
                $tsConfig .= "\n" . "@import 'EXT:timer/Configuration/TsConfig/Page/NewContentTimersimul.tsconfig'";

                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
                    TimerConst::EXTENSION_NAME,
                    'setup',
                    "@import 'EXT:timer/Configuration/TypoScript/Timersimul/setup.typoscript'" . "\n"
                );
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
                    TimerConst::EXTENSION_NAME,
                    'constants',
                    "@import 'EXT:timer/Configuration/TypoScript/Timersimul/constants.typoscript'" . "\n"
                );
            }
            if (!empty($timerConfig['flagPeriodlist'])) {
                $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['timer_periodlist'] = 'EXT:timer/Configuration/RTE/TimerPeriodlist.yaml';
                $tsConfig .= "\n" . "@import 'EXT:timer/Configuration/TsConfig/Page/NewContentPeriodlist.tsconfig'";
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
                    TimerConst::EXTENSION_NAME,
                    'setup',
                    "@import 'EXT:timer/Configuration/TypoScript/Periodlist/setup.typoscript'" . "\n"
                );
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
                    TimerConst::EXTENSION_NAME,
                    'constants',
                    "@import 'EXT:timer/Configuration/TypoScript/Periodlist/constants.typoscript'" . "\n"
                );
            }
            if (!empty($timerConfig['flagHolidaycalendar'])) {
                $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['timer_holidaycalendar'] = 'EXT:timer/Configuration/RTE/TimerHolidaycalendar.yaml';
                $tsConfig .= "\n" . "@import 'EXT:timer/Configuration/TsConfig/Page/NewContentHolidaycalendar.tsconfig'";
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
                    TimerConst::EXTENSION_NAME,
                    'setup',
                    "@import 'EXT:timer/Configuration/TypoScript/Holidaycalendar/setup.typoscript'" . "\n"
                );
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
                    TimerConst::EXTENSION_NAME,
                    'constants',
                    "@import 'EXT:timer/Configuration/TypoScript/Holidaycalendar/constants.typoscript'" . "\n"
                );
            }
            ExtensionManagementUtility::addPageTSConfig($tsConfig);

            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][TimerConst::CACHE_IDENT_TIMER_YAMLLIST] ??= [];
            if (!array_key_exists('frontend',
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][TimerConst::CACHE_IDENT_TIMER_YAMLLIST])) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][TimerConst::CACHE_IDENT_TIMER_YAMLLIST]['frontend'] = VariableFrontend::class;
            }
        }
    }
);
