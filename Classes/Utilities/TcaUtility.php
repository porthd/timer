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

    protected const DEFAULT_FLATTEN_KEYS_LIST = ['data', 'general', 'timer', 'sDEF', 'lDEF', 'vDEF',];

    public const HOOK_CHANGE_LIST_OF_TIMEZONES = 'changeListOfTimezones'; // modify the list of uesed timezone-codes with your own hook-Method

    /**
     * Beginn the list with the null-element
     *
     * @param string|array $orderList
     * @return array|string[]
     */
    public static function mergeNameFlexformArray($orderList = DefaultTimer::TIMER_NAME)
    {
        /** @var ListOfTimerService $timerList */
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        return $timerList->mergeFlexformItems($orderList);
    }

    /**
     * Beginn the list with the null-element
     *
     * @param string|array $orderList
     * @return array|string[]
     */
    public static function mergeSelectorItems($orderList = DefaultTimer::TIMER_NAME)
    {
        /** @var ListOfTimerService $timerList */
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        return $timerList->mergeSelectorItems($orderList);
    }

    /**
     * this is an helpful tool to connect the valuepicker oder as fixed list of items in a sellect-box to an selfdefined model in the TYPO3-Backend
     *
     * @param string $tableName
     * @param string $lableFiled
     * @param string $valueField
     * @param bool $sortingField
     * @return array
     */
    public static function getListFromDbForValuepicker(
        $tableName = 'tx_colleaguesearch_domain_model_helpernation',
        $lableField = 'name',
        $valueField = 'item',
        $flagSorting = false
    ) {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select($lableField, $valueField)
            ->from($tableName);
        if ($flagSorting !== false) {
            $queryBuilder->orderBy('sorting');
        } else {
            $queryBuilder->orderBy($lableField);
        }
        $klaus =
            $queryBuilder->execute()
                ->fetchAll();
        $result = [];
        foreach ($klaus as $item) {
            $result[] = array_values($item);
        }
        return $result;
    }

    /**
     * This function is used in flexform-definitions of some timers.
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
    public static function getListOfTimezones()
    {
        if (empty(self::$listOfTimezones)) {
            self::$listOfTimezones = DateTimeZone::listIdentifiers();
        }
        return self::$listOfTimezones;
    }

    /**
     * @param array $listOfTimezones
     */
    public static function setListOfTimezones(array $listOfTimezones)
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
    public static function resetListOfTimezones()
    {
        $listOfTimezones = self::getListOfTimezones();
        foreach (($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][self::HOOK_CHANGE_LIST_OF_TIMEZONES] ?? []) as $classRef) {
            $hookObj = GeneralUtility::makeInstance($classRef);
            if (method_exists($hookObj, self::HOOK_CHANGE_LIST_OF_TIMEZONES)) {
                $listOfTimezones = $hookObj->changeListOfTimezones($listOfTimezones);
            }
        }
        self::setListOfTimezones($listOfTimezones);
        return self::getListOfTimezones();
    }

    /**
     * @return array
     */
    public static function listBaseZoneItems(): array
    {
        if ((!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][self::HOOK_CHANGE_LIST_OF_TIMEZONES])) &&
            (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][self::HOOK_CHANGE_LIST_OF_TIMEZONES]))
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
    public static function isZoneInList(string $key, array $params): bool
    {
        if ((!empty($key)) &&
            (isset($params[$key]))
        ) {
            return in_array(
                $params[$key],
                TcaUtility::listBaseZoneItems()
            );
        }
        return false;
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
     * @param string $default
     * @param $infosFromTca
     * @return string
     * @throws TimerException
     */
    protected static function modifyDefaultTimezoneByHook(string $default, $infosFromTca): string
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][TimerConst::EXTENSION_NAME]['hook-change-default-timezone'])) {
            if ((is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][TimerConst::EXTENSION_NAME]['hook-change-default-timezone'])) &&
                (count($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][TimerConst::EXTENSION_NAME]['hook-change-default-timezone']) > 0)
            ) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][TimerConst::EXTENSION_NAME]['hook-change-default-timezone'] as $className) {
                    $default = self::modifyDefaultTimezoneByHookSingleStep($default,
                        $infosFromTca,
                        $className
                    );
                }
            } else {
                throw new TimerException(
                    'The definition of the hook must be an array. You forgot the `[]` in your definition in the `ext_localconf.php`.
                        Please check it.',
                    1601973776
                );
            }
            return $default;
        }
        return TimerConst::DEFAULT_TIME_ZONE;
    }

    /**
     * @param string $default
     * @param $infosFromTca
     * @param $className
     * @return string
     * @throws TimerException
     */
    protected static function modifyDefaultTimezoneByHookSingleStep(string $default, $infosFromTca, $className): string
    {
        $oldDefault = $default;
        $params = [
            'tcaInfos' => $infosFromTca,
            'default' => $default,
        ];
        try {
            $zoneFromHook = $className::modifyDefaultTimezoneByHook($params);
            $default = self::checkTimeZoneName($zoneFromHook, $oldDefault);
        } catch (TimerException $e) {
            throw new TimerException(
                'The method `getDefaultBaseZoneFromExtensionConfig` seems not to be defined ' .
                'in the class `' . $className . '` for the hook to define the default timezone. Check, if the class exists.  
                            Please check the spelling of the namespace.',
                1601973766
            );
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
            } else {
                return [$key => $value];
            }
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

