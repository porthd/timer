<?php

namespace Porthd\Timer\Utilities;

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

use DateTimeZone;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\DefaultTimer;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Services\ListOfTimerService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


class TcaUtility
{
    public static $listOfTimezones = [];

    /** @var ListOfTimerService|null $timerList */
    private static ?ListOfTimerService $timerList = null;
    // General parameter of timerdefinitions
    public static $timerConfig = [];

    protected const DEFAULT_FLATTEN_KEYS_LIST = ['data', 'general', 'timer', 'sDEF', 'lDEF', 'vDEF',];


    /**
     * Beginn the list with the null-element
     *
     * @param string|array $orderList
     * @return array|string[]
     */
    public static function mergeNameFlexformArray($orderList = DefaultTimer::TIMER_NAME)
    {
        if (self::$timerList === null) {
            self::$timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        }
        return self::$timerList->mergeFlexformItems($orderList);
    }

    /**
     * Beginn the list with the null-element
     *
     * @param string|array $orderList
     * @return array|string[]
     */
    public static function mergeSelectorItems($orderList = DefaultTimer::TIMER_NAME)
    {
        if (self::$timerList === null) {
            self::$timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        }
        return self::$timerList->mergeSelectorItems($orderList);
    }

    /**
     * DON`T DELETE!!!!! This function is used in flexform-definitions EXT:Configuration/FlexForms/TimerDef/General/GeneralTimer.flexform
     *
     * @param array $params TCA-Array
     * @param mixed $conf not in use
     * @return array
     */
    public static function listBaseZoneItemsFlexform($params, $conf): array
    {
        $params['items'] = [];
        $count = 0;
        foreach (self::listBaseZoneItems() as $item) {
            $langKey = $item;
            $key = LocalizationUtility::translate('LLL:EXT:timer/Resources/Private/Language/locallang_zone.xlf:timer.flexform.array.key.timezone.' . $langKey,
                TimerConst::EXTENSION_NAME
            );
            $title = LocalizationUtility::translate('LLL:EXT:timer/Resources/Private/Language/locallang_zone.xlf:timer.flexform.array.detail.timezone.' . $langKey,
                TimerConst::EXTENSION_NAME
            );
            if (!empty($title)) {
                if (!empty($key)) {
                    $params['items']['__' . $key] = [$title, $item];
                } else {
                    $params['items']['_0' . $count] = [$title, $item];
                    $count++;
                }
            }
        }
        ksort($params['items']);
        return $params;
    }

    /**
     * @return array
     */
    public static function getListOfTimezones(): array
    {
        if (empty(self::$listOfTimezones)) {
            self::$listOfTimezones = DateTimeZone::listIdentifiers();
        }
        return self::$listOfTimezones;
    }

    /**
     * @param array $listOfTimezones
     */
    public static function setListOfTimezones(array $listOfTimezones): void
    {
        if (!empty($listOfTimezones)) {
            self::$listOfTimezones = $listOfTimezones;
        } else {
            self::$listOfTimezones = [TimerConst::DEFAULT_TIME_ZONE,];
        }
    }

    /**
     * @return array
     */
    public static function resetListOfTimezones(): array
    {
        $listOfTimezones = self::getListOfTimezones();
        // Parts of code, which can by the extension-constants be controlled
        if (empty(self::$timerConfig)) {
            self::$timerConfig = GeneralUtility::makeInstance(
                ExtensionConfiguration::class
            )->get(TimerConst::EXTENSION_NAME);
            $methodName = TimerConst::HOOK_CHANGE_LIST_OF_TIMEZONES;
            foreach (($timerConfig[TimerConst::HOOK_CHANGE_LIST_OF_TIMEZONES] ?? []) as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, $methodName)) {
                    $listOfTimezones = $hookObj->$methodName($listOfTimezones);
                }
            }
            self::setListOfTimezones($listOfTimezones);
        }
        return self::getListOfTimezones();
    }

    /**
     * @return array
     */
    public static function listBaseZoneItems(): array
    {
        if (empty(self::$timerConfig)) {
            self::$timerConfig = GeneralUtility::makeInstance(
                ExtensionConfiguration::class
            )->get(TimerConst::EXTENSION_NAME);
        }
        if ((!empty(self::$timerConfig[TimerConst::HOOK_CHANGE_LIST_OF_TIMEZONES])) &&
            (is_array(self::$timerConfig[TimerConst::HOOK_CHANGE_LIST_OF_TIMEZONES]))
        ) {
            return self::resetListOfTimezones();
        }
        return self::getListOfTimezones();
    }

    /**
     * This method are introduced for easy build of unittests
     * @param string $key
     * @param array $params
     * @return bool
     */
    public static function isTimeZoneInList(string $timeZone = ''): bool
    {
        if (!empty($timeZone)) {
            return in_array(
                $timeZone,
                TcaUtility::listBaseZoneItems()
            );
        }
        return false;
    }

    /**
     * @param $configZone
     * @return string
     */
    protected static function checkTimeZoneName($configZone, $oldZone = TimerConst::DEFAULT_TIME_ZONE): string
    {
        try {
            if (!(new DateTimeZone($configZone))) {
                $default = $oldZone;
            } else {
                $default = $configZone;
            }
        } catch (Exception $e) {
            $default = $oldZone;
        }
        return $default;
    }

    /**
     * remove obsolete layers in array
     * analogous to https://stackoverflow.com/questions/1319903/how-to-flatten-a-multidimensional-array visited 20201109
     *
     * @param $array
     * @param string $removeList
     * @return array|mixed
     */
    public static function flexformArrayFlatten($array, $removeList = self::DEFAULT_FLATTEN_KEYS_LIST)
    {

        if (!is_array($array)) {
            return $array;
        }

        if (count($array) === 1) {
            $key = array_key_first($array);
            $value = self::flexformArrayFlatten($array[$key]);
            if (in_array($key, $removeList)) {
                return $value;
            }
            return [$key => $value];
        }

        $result = [];
        $helpResult = [];
        $flagMerge = false;
        foreach ($array as $key => $value) {
            if (in_array($key, $removeList)) {
                $helpResult[] = self::flexformArrayFlatten($value);
                $flagMerge = true;
            } else {
                $result[$key] = self::flexformArrayFlatten($value);
            }
        }
        if ($flagMerge) {
            $result = array_merge($result, ...$helpResult);
        }
        return $result;
    }

}

