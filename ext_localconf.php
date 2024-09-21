<?php

declare(strict_types=1);

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\CalendarDateRelTimer;
use Porthd\Timer\CustomTimer\HolidayTimer;
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

defined('TYPO3') || die('Access denied in ' . __FILE__ . '.');

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

        // declare namespace in fluid-taemplates
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['timer'] = ['Porthd\\Timer\\ViewHelpers'];

        // the icons for the content-element and for the extension  of the extension icon
        $iconRegistry = GeneralUtility::makeInstance(
            IconRegistry::class
        );
        foreach ([
                     'tx_timer_timericon' => 'EXT:timer/Resources/Public/Icons/icon_timer.svg',
                     'tx_timer_timersimul' => 'EXT:timer/Resources/Public/Icons/Content/timersimul.svg',
                     'tx_timer_holidaycalendar' => 'EXT:timer/Resources/Public/Icons/Content/holidaycalendar.svg',
                 ] as $name => $path
        ) {
            $iconRegistry->registerIcon(
                $name, // Icon-Identifier, e.g. tx-myext-action-preview
                SvgIconProvider::class,
                ['source' => $path]
            );
        }
        // reset starttime and endtime after changes in tx_timer_select and tx_timer_timer
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
            StartEndTimerManipulationHook::class;

        // Parts of code, which can by the extension-constants be controlled
        $timerConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get(TimerConst::EXTENSION_NAME);

        // disallow usage of timer from the extension via the configuration
        $addTimerFlags = (int)((empty($timerConfig['useInternalTimer'])) ? 8191 : $timerConfig['useInternalTimer']);
        if ($addTimerFlags >= 1) {
            // Sum as Defaultvalue => 8191 = 1+ 2 + 4+ 8+ ... +4096;
            $listOfTimerClasses = [
                DailyTimer::class, // => 1
                DatePeriodTimer::class, // => 2
                DefaultTimer::class, // => 4
                EasterRelTimer::class, // => 8
                MoonphaseRelTimer::class, // => 16
                MoonriseRelTimer::class, // => 32
                PeriodListTimer::class, // => 64
                RangeListTimer::class, // => 128
                SunriseRelTimer::class, // => 256
                WeekdayInMonthTimer::class, // => 512
                WeekdaylyTimer::class, // => 1024
                JewishHolidayTimer::class, // => 2048
                HolidayTimer::class, // => 4096
//                CalendarDateRelTimer::class, // 8192 in planing
            ];
            ConfigurationUtility::addExtLocalconfTimerAdding($addTimerFlags, $listOfTimerClasses);


            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][TimerConst::CACHE_IDENT_TIMER_YAMLLIST] ??= [];
            if (!array_key_exists('frontend', $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][TimerConst::CACHE_IDENT_TIMER_YAMLLIST])) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][TimerConst::CACHE_IDENT_TIMER_YAMLLIST]['frontend'] = VariableFrontend::class;
            }
        }

        // definen special field for timegaps in field `durationMinutes` et al.`
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1694842416] = [
            'nodeName' => 'durationMinutesField',
            'priority' => 1,
            'class' => \Porthd\Timer\Form\Element\DurationMinutesFieldElement::class,
        ];


        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
            'timer',
            'setup',
            "@import 'EXT:timer/Configuration/TypoScript/setup.typoscript'"
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
            'timer',
            'constants',
            "@import 'EXT:timer/Configuration/TypoScript/constants.typoscript'"
        );

        // define the caching for timer
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['timer_dataprocessor']
            ??= [];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['timer_dataprocessor'] = array_merge(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['timer_dataprocessor'],
            [
                'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                'options' => [
                    'defaultLifetime' => 864000, // 10 days
                ],
                'groups' => ['pages'],
            ],
        );

    }
);
