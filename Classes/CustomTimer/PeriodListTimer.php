<?php

namespace Porthd\Timer\CustomTimer;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2022 Dr. Dieter Porthd <info@mobger.de>
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


use DateInterval;
use DateTime;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Domain\Model\Listing;
use Porthd\Timer\Domain\Repository\GeneralRepository;
use Porthd\Timer\Domain\Repository\ListingRepository;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Services\ListOfTimerService;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


class PeriodListTimer implements TimerInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    /**
     * @var TimerStartStopRange|null
     */
    protected $lastIsActiveResult;

    /**
     * @var int|null
     */
    protected $lastIsActiveTimestamp;

    /**
     * @var array
     */
    protected $lastIsActiveParams = [];

    /**
     * @var YamlFileLoader
     */
    protected $yamlFileLoader;


    /**
     * @param YamlFileLoader|null $yamlFileLoader
     */
    public function __construct(YamlFileLoader $yamlFileLoader = null)
    {
        $this->yamlFileLoader = $yamlFileLoader ?? GeneralUtility::makeInstance(YamlFileLoader::class);
    }

    public const TIMER_NAME = 'txTimerPeriodList';
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE =TimerConst::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerConst::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerConst::ARG_ULTIMATE_RANGE_END;
protected const ARG_USE_TIMEZONE_FRONTEND =TimerConst::ARG_USE_ACTIVE_TIMEZONE;

    protected const ARG_YAML_PERIOD_FILE_PATH = 'yamlPeriodFilePath';
    protected const ARG_YAML_PERIOD_FIELD = 'yamlTextField';

    protected const YAML_LIST_KEY = 'periodlist';
//    protected const YAML_LIST_ITEM_SELECTOR = 'selector';
//    protected const YAML_LIST_ITEM_TITLE = 'title';
//    protected const YAML_LIST_ITEM_DESCRIPTION = 'description';
//    protected const YAML_LIST_ITEM_PARAMS = 'params';
//    protected const YAML_LIST_ITEM_RANGE = 'range';
//    protected const MAX_TIME_LIMIT_ACTIVE_COUNT = 10; // eproduction per timer-level
//    protected const MAX_TIME_LIMIT_FORBIDDEN_COUNT = 10; // eproduction per timer-level

    protected const MAX_TIME_LIMIT_MERGE_COUNT = 4; // count of loops to check for overlapping ranges

    
    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/PeriodListTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        TimerConst::ARG_ULTIMATE_RANGE_BEGINN,
        TimerConst::ARG_ULTIMATE_RANGE_END,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_YAML_PERIOD_FILE_PATH,
        self::ARG_YAML_PERIOD_FIELD,
        self::ARG_USE_TIMEZONE_FRONTEND,

    ];


    /**
     * tested 20221007
     * +
     *
     *
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }


    /**
     * tested
     * @return array
     */
    public static function getSelectorItem(): array
    {
        return [
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerPeriodList.select.name',
            self::TIMER_NAME,
        ];
    }

    /**
     * tested 20221009
     *
     * @param string $activeZoneName
     * @param array $params
     * @return string
     */
    public function getTimeZoneOfEvent($activeZoneName, array $params = []): string
    {
        return GeneralTimerUtility::getTimeZoneOfEvent($activeZoneName, $params);
    }

    /**
     * tested 20221009
     *
     * @return array
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
    }

    /**
     * tested 20221009
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return bool
     */
    public function isAllowedInRange(DateTime $dateLikeEventZone, $params = []): bool
    {
        return ($params[TimerConst::ARG_ULTIMATE_RANGE_BEGINN] <= $dateLikeEventZone->format('Y-m-d H:i:s')) &&
            ($dateLikeEventZone->format('Y-m-d H:i:s') <= $params[TimerConst::ARG_ULTIMATE_RANGE_END]);
    }

    /**
     * tested general 20221009
     * tested special 20221011
     *
     * The method test, if the parameter are valid or not
     * remark: This method must not be tested, if the sub-methods are valid.
     * @param array $params
     * @return bool
     */
    public function validate(array $params = []): bool
    {
        $flag = true;
        $flag = $flag && $this->validateZone($params);
        $flag = $flag && $this->validateUltimate($params);
        $countRequired = $this->validateCountArguments($params);
        $flag = ($flag && ($countRequired === count(self::ARG_REQ_LIST)));
        $flag = $flag && $this->validateYamlFilePath($params);
        $countOptions = $this->validateOptional($params);
        return $flag && ($countOptions >= 0) &&
            ($countOptions <= count(self::ARG_OPT_LIST));

    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateZone(array $params = []): bool
    {
        return !(isset($params[TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT]))||
            TcaUtility::isTimeZoneInList(
                $params[TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT]
            );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateUltimate(array $params = []): bool
    {
        $flag = (!empty($params[TimerConst::ARG_ULTIMATE_RANGE_BEGINN]));
        $flag = $flag && (false !== date_create_from_format(
                    TimerConst::TIMER_FORMAT_DATETIME,
                    $params[TimerConst::ARG_ULTIMATE_RANGE_BEGINN]
                ));
        $flag = $flag && (!empty($params[TimerConst::ARG_ULTIMATE_RANGE_END]));
        return ($flag && (false !== date_create_from_format(
                    TimerConst::TIMER_FORMAT_DATETIME,
                    $params[TimerConst::ARG_ULTIMATE_RANGE_END]
                )));
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateCountArguments(array $params = []): int
    {
        $count = 0;
        foreach (self::ARG_REQ_LIST as $key) {
            if (isset($params[$key])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateOptional(array $params = []): int
    {
        $count = 0;
        foreach (self::ARG_OPT_LIST as $key) {
            if (isset($params[$key])) {
                $count++;
            }
        }
        return $count;
    }


    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateYamlFilePath(array $params = []): bool
    {
        $flag = true;

        $filePath = (isset($params[self::ARG_YAML_PERIOD_FILE_PATH]) ?
            $params[self::ARG_YAML_PERIOD_FILE_PATH] :
            ''
        );
        if (!empty($filePath)) {
            if (strpos($filePath, TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH) === 0) {
                $extPath = $this->getExtentionPathByEnviroment();
                $filePath = substr($filePath, strlen(TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH));
                $flag = $flag && file_exists($extPath . DIRECTORY_SEPARATOR . $filePath);
            } else {
                $rootPath = $this->getPublicPathByEnviroment();
                $flag = $flag && file_exists($rootPath . DIRECTORY_SEPARATOR . $filePath);
            }
        } else {
            $flag = $flag && (!empty($params[self::ARG_YAML_PERIOD_FIELD]));
        }


        return $flag;
    }

    /**
     * tested
     *
     * check, if the timer ist for this time active
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas
     * @param array $params
     * @return bool
     */
    public function isActive(DateTime $dateLikeEventZone, $params = []): bool
    {
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            $result = GeneralUtility::makeInstance(TimerStartStopRange::class);
            $result->failAllActive($dateLikeEventZone);
            $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
            return $result;
        }

        return false;
//        $yamlActiveConfig = $this->readActiveListFromYamlFile($params);
//        $yamlActiveConfig = (empty($yamlActiveConfig[self::YAML_LIST_KEY]) ? [] : $yamlActiveConfig[self::YAML_LIST_KEY]);
//        $databaseActiveConfig = $this->readActiveListFromDatabase($params);
//        $activeTimer = array_merge($yamlActiveConfig, $databaseActiveConfig);
//        if (empty($activeTimer)) {
//            $this->logger->warning('Your path for the file ' . $yamlActiveConfig[self::YAML_LIST_KEY]
//                . 'in `' . self::ARG_YAML_PERIOD_FILE_PATH . '` is missing or empty and '
//                . 'the entries for [' . $params[self::ARG_DATABASE_ACTIVE_RANGE_LIST] . '] in `' . self::ARG_DATABASE_ACTIVE_RANGE_LIST . '` are empty too. Please check '
//                . 'your configuration. [1600865701].'
//            );
//            return $flag;
//        }
//        /** @var ListOfTimerService $timerList */
//        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
//        foreach ($activeTimer as $singleActiveTimer) {
//            if (
//                (!isset($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR], $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])) &&
//                ($timerList->validate($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]))
//            ) {
//                // log only the missing of an allowed-timerdcefinition
//                $this->logger->critical('The needed values `' . print_r($singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
//                        true) .
//                    '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
//                    'Please check your definition in your active yaml-file. [1600865701]'
//                );
//            } else {
//                $this->logWarningIfSelfCalling('active',
//                    $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                    $yamlActiveConfig,
//                    $databaseActiveConfig
//                );
//                if ($timerList->isActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                    $dateLikeEventZone,
//                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])
//                ) {
//                    $activeRange = $timerList->getLastIsActiveRangeResult(
//                        $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                        $dateLikeEventZone,
//                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
//                    );
//                    $flag = true;
//                    $this->setIsActiveResult($activeRange->getBeginning(), $activeRange->getEnding(), $flag,
//                        $dateLikeEventZone,
//                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]);
//                    break;
//                }
//            }
//        }
//        if ($flag) {
//            // test the restriction for active-cases
//            $yamlForbiddenConfig = $this->readForbiddenListFromYamlFile($params);
//            $yamlForbiddenConfig = $yamlForbiddenConfig[self::YAML_LIST_KEY] ?? [];
//            $databaseForbiddenConfig = $this->readForbiddenListFromDatabase($params);
//            $forbiddenTimer = array_merge($yamlForbiddenConfig, $databaseForbiddenConfig);
//            if (!empty($forbiddenTimer)) {
//                foreach ($forbiddenTimer as $singleForbiddenTimer) {
//                    if (
//                        (!isset($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR], $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS])) &&
//                        ($timerList->validate($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
//                            $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]))
//                    ) {
//                        // log only the missing of an allowed-timerdcefinition
//                        $this->logger->critical('The needed parameter `' . print_r($singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS],
//                                true) .
//                            '` for the forbidden-timer `' . $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be missdefined or missing. ' .
//                            'Please check your definition in your forbidden yaml-file. [1600874901]'
//                        );
//                    } else {
//                        $this->logWarningIfSelfCalling(
//                            'forbidden',
//                            $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
//                            $yamlForbiddenConfig,
//                            $databaseForbiddenConfig);
//                        if ($timerList->isActive($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
//                            $dateLikeEventZone,
//                            $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS])
//                        ) {
//                            $forbiddenRange = $timerList->getLastIsActiveRangeResult(
//                                $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
//                                $dateLikeEventZone,
//                                $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]
//                            );
//                            $flag = false;
//                            $this->setIsActiveResult($forbiddenRange->getBeginning(), $forbiddenRange->getEnding(),
//                                $flag, $dateLikeEventZone,
//                                $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]);
//                            break;
//                        }
//                    }
//                }
//            }
//        }
//        return (is_null($this->lastIsActiveResult) ? false : $this->lastIsActiveResult->getResultExist());
    }

    /**
     * tested
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        return $this->getLastIsActiveResult($dateLikeEventZone, $params);
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        $activeTimerList = $this->readPeriodListFromYamlFile($params);
        // Generate List of next Ranges Detect Lower-Part
        // Generate find nearest Nextrange with biggest range
        // Generate List of forbidden and detect
        //
        $refDateTime = clone $dateLikeEventZone;
        while ($loopLimiter > 0) {
            /** @var ListOfTimerService $timerList */
            $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
            /**
             * Range-detect-algorithm
             *
             * 1. detect next active range
             * 1.a detect the range with the lowest lower limit for the active delay
             * 1.b expand the upper-range in a loop repeated for four times (use getInRange, to detect the current range)
             * 3. if forbidden ranges the reduce active by passive range
             * 3.a. reduce the range of the active gap with the forbidden-part try to fix it from the lowest part going on.
             * 3.b. if the lower Limit is part of the forbidden range, than expand the upper-range of the forbidden range in a loop repeated for four times (use getInRange, to detect the current range)
             */
//            $activeResult = $this->getActiveRangeWithLowestBeginRefDate(
            /** @var TimerStartStopRange $activeResult */
            $activeResult = $this->getPeriodRangeWithLowestBeginRefDate(
                $activeTimerList[self::YAML_LIST_KEY],
                $timerList,
                $refDateTime
            );
            if ($activeResult->hasResultExist()) {
                break; // the next active range is detected
            }
            // try to find a next range by using the ending-date of the currently used active range
            $refDateTime = clone $activeResult->getEnding();
            $refDateTime->add(new DateInterval('PT1S'));
            $loopLimiter--;
        }
        if ((!$this->isAllowedInRange($activeResult->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($activeResult->getEnding(), $params))
        ) {
            $activeResult->failOnlyNextActive($dateLikeEventZone);
        }

        return $activeResult;
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
//        $yamlFileActiveTimerList = $this->readActiveListFromYamlFile($params);
        $activeTimerList = $this->readPeriodListFromYamlFile($params);
        // Generate List of next Ranges Detect Lower-Part
        // Generate find nearest Nextrange with biggest range
        // Generate List of forbidden and detect
        //
        $refDateTime = clone $dateLikeEventZone;
        while ($loopLimiter > 0) {
            /** @var ListOfTimerService $timerList */
            $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
            /**
             * Range-detect-algorithm
             *
             * 1. detect next active range
             * 1.a detect the range with the lowest lower limit for the active delay
             * 1.b expand the upper-range in a loop repeated for four times (use getInRange, to detect the current range)
             * 3. if forbidden ranges the reduce active by passive range
             * 3.a. reduce the range of the active gap with the forbidden-part try to fix it from the lowest part going on.
             * 3.b. if the lower Limit is part of the forbidden range, than expand the upper-range of the forbidden range in a loop repeated for four times (use getInRange, to detect the current range)
             */
//            $activeResult = $this->getActiveRangeWithHighestEndRefDate(
            $activeResult = $this->getPeriodRangeWithHighestEndRefDate(
                $activeTimerList[self::YAML_LIST_KEY],
                $timerList,
                $refDateTime
            );

            if ($activeResult->hasResultExist()) {
                break; // the next active range is detected
            }
            // try to find a next range by using the ending-date of the currently used active range
            $refDateTime = clone $activeResult->getBeginning();
            $refDateTime->add(new DateInterval('PT1S'));
            $loopLimiter--;
        }

        if ((!$this->isAllowedInRange($activeResult->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($activeResult->getEnding(), $params))
        ) {
            $activeResult->failOnlyNextActive($dateLikeEventZone);
        }
        return $activeResult;

    }


//    /**
//     * @param array $activeTimerList
//     * @param ListOfTimerService $timerList
//     * @param DateTime $dateLikeEventZone
//     * @return array // [TimerStartStopRange, bool]
//     */
//    protected function reduceActiveRangeByForbiddenRangesWithLowestStart(
//        array $forbiddenTimerList,
//        ListOfTimerService $timerList,
//        TimerStartStopRange $currentActiveRange
//    ) {
//        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
//        $startRange = $currentActiveRange->getBeginning();
//        $stopRange = $currentActiveRange->getEnding();
//        $result = clone $currentActiveRange;
//        $changed = false;
//        while ($loopLimiter > 0) {
//            foreach ($forbiddenTimerList as $singleActiveTimer) {
//                $flagNoRangeChangeByForbidden = true;
//                if (
//                    (!isset($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR], $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])) &&
//                    ($timerList->validate($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]))
//                ) {
//                    // log only the missing of an allowed-timerdcefinition
//                    $this->logger->critical('The needed values `' . print_r($singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
//                            true) .
//                        '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
//                        'Please check your definition in your active yaml-file. [1600865701]'
//                    );
//                } else {
//                    $this->logWarningIfSelfCalling('forbidden-next', $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                        $forbiddenTimerList, []
//                    );
//                    /** TimerStartStopRange $checkResult  */
//                    /** bool $changed  */
//                    [$checkResult, $changed] = $this->getInRangeNearLowerLimit($singleActiveTimer,
//                        $timerList,
//                        $startRange,
//                        $stopRange
//                    );
//
//                    if ($changed) {
//                        $flagNoRangeChangeByForbidden = false;
//                        if ($checkResult->hasResultExist()) {
//                            if ($checkResult->getBeginning() > $startRange) {
//                                $startRange = clone $checkResult->getBeginning();
//                                $result->setBeginning($checkResult->getBeginning());
//                            }
//                            if ($checkResult->getEnding() < $stopRange) {
//                                $stopRange = clone $checkResult->getEnding();
//                                $result->setEnding($checkResult->getEnding());
//                            }
//                        } else {
//                            $result->failOnlyNextActive($startRange);
//                            return [$result, $changed];
//                        }
//                    }
//                }
//            }
//            if ($flagNoRangeChangeByForbidden) {
//                break 1;  // there were no changes by any entry in the $forbiddenTimerList, there is no need for another loop
//            }
//            $loopLimiter--;
//        }
//        return [$result, $changed];
//    }
//
//    /**
//     * @param array $activeTimerList
//     * @param ListOfTimerService $timerList
//     * @param DateTime $dateLikeEventZone
//     * @return array // [TimerStartStopRange, bool]
//     */
//    protected function reduceActiveRangeByForbiddenRangesWithHighestEnd(
//        array $forbiddenTimerList,
//        ListOfTimerService $timerList,
//        TimerStartStopRange $currentActiveRange
//    ) {
//        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
//        $startRange = $currentActiveRange->getBeginning();
//        $stopRange = $currentActiveRange->getEnding();
//        $result = clone $currentActiveRange;
//        $changed = false;
//        while ($loopLimiter > 0) {
//            foreach ($forbiddenTimerList as $singleActiveTimer) {
//                $flagNoRangeChangeByForbidden = true;
//                if (
//                    (!isset($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR], $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])) &&
//                    ($timerList->validate($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]))
//                ) {
//                    // log only the missing of an allowed-timerdcefinition
//                    $this->logger->critical('The needed values `' . print_r($singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
//                            true) .
//                        '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
//                        'Please check your definition in your active yaml-file. [1600865701]'
//                    );
//                } else {
//                    $this->logWarningIfSelfCalling('forbidden-prev', $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                        $forbiddenTimerList, []
//                    );
//                    /** TimerStartStopRange $checkResult  */
//                    /** bool $changed  */
//                    [$checkResult, $changed] = $this->getInRangeNearHigherLimit($singleActiveTimer,
//                        $timerList,
//                        $startRange,
//                        $stopRange
//                    );
//
//                    if ($changed) {
//                        $flagNoRangeChangeByForbidden = false;
//                        if ($checkResult->hasResultExist()) {
//                            if ($checkResult->getEnding() < $stopRange) {
//                                $stopRange = clone $checkResult->getEnding();
//                                $result->setEnding($checkResult->getEnding());
//                            }
//                            if ($checkResult->getBeginning() > $startRange) {
//                                $startRange = clone $checkResult->getBeginning();
//                                $result->setBeginning($checkResult->getBeginning());
//                            }
//                        } else {
//                            $result->failOnlyPrevActive($startRange);
//                            return [$result, $changed];
//                        }
//                    }
//                }
//            }
//            if ($flagNoRangeChangeByForbidden) {
//                break 1;  // there were no changes by any entry in the $forbiddenTimerList, there is no need for another loop
//            }
//            $loopLimiter--;
//        }
//        return [$result, $changed];
//    }

    /**
     * @param array $activeTimerList
     * @param ListOfTimerService $timerList
     * @param DateTime $dateLikeEventZone
     * @return TimerStartStopRange
     */
    protected function getActiveRangeWithLowestBeginRefDate(
        array $activeTimerList,
        ListOfTimerService $timerList,
        DateTime $dateLikeEventZone
    ): TimerStartStopRange {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        $result = null;
        $flagFirstResult = true;
        $flagChange = false;
        $refRange = clone $dateLikeEventZone;
        while ($loopLimiter > 0) {
            foreach ($activeTimerList as $singleActiveTimer) {
                if (
                    (!isset($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR], $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])) &&
                    ($timerList->validate($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]))
                ) {
                    // log only the missing of an allowed-timerdcefinition
                    $this->logger->critical('The needed values `' . print_r($singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                            true) .
                        '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                        'Please check your definition in your active yaml-file. [1600865701]'
                    );
                } else {
                    $this->logWarningIfSelfCalling('active-next', $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $activeTimerList, []
                    );

                    $checkResult = $timerList->nextActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $refRange,
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    );
                    if ($checkResult->hasResultExist()) {
                        if ($flagFirstResult) {
                            $result = clone $checkResult;
                            $flagFirstResult = false;
                            $flagChange = true;
                        } else {
                            if ($checkResult->getBeginning() > $refRange) {
                                // This condition must be first, because the second condition modifies the beginning of result, which is part of the condition
                                if ($checkResult->getBeginning() <= $result->getEnding()) {
                                    if (($checkResult->getEnding() > $result->getEnding()) || // the checkresult extend the result-range and ist overlapping with at
                                        ($checkResult->getEnding() < $result->getBeginning()) // checkResult-range has a gap to the former result
                                    ) {
                                        $flagChange = true;
                                        $result->setEnding($checkResult->getEnding());
                                    }
                                }
                                if ($checkResult->getBeginning() < $result->getBeginning()) { // move startborder of range nearer to test-date
                                    $flagChange = true;
                                    $result->setBeginning($checkResult->getBeginning());
                                }
                            }
                        }
                    }
                }
            }
            if ((!$flagFirstResult) | (!$flagChange)) {
                break;
            }
            $refRange = clone $result->getEnding();
            $flagChange = false;
            $loopLimiter--;
        }
        return $result;
    }

    /**
     * @param array $activeTimerList
     * @param ListOfTimerService $timerList
     * @param DateTime $dateLikeEventZone
     * @return TimerStartStopRange
     */
    protected function getActiveRangeWithHighestEndRefDate(
        array $activeTimerList,
        ListOfTimerService $timerList,
        DateTime $dateLikeEventZone
    ): TimerStartStopRange {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        $result = null;
        $flagFirstResult = true;
        $flagChange = false;
        $refRange = clone $dateLikeEventZone;
        while ($loopLimiter > 0) {
            foreach ($activeTimerList as $singleActiveTimer) {
                if (
                    (!isset($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR], $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])) &&
                    ($timerList->validate($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]))
                ) {
                    // log only the missing of an allowed-timerdcefinition
                    $this->logger->critical('The needed values `' . print_r($singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                            true) .
                        '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                        'Please check your definition in your active yaml-file. [1600865701]'
                    );
                } else {
                    $this->logWarningIfSelfCalling('active-prev', $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $activeTimerList, []
                    );

                    $checkResult = $timerList->prevActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $refRange,
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    );
                    if ($checkResult->hasResultExist()) {
                        if ($flagFirstResult) {
                            $result = clone $checkResult;
                            $flagFirstResult = false;
                            $flagChange = true;
                        } else {
                            if ($checkResult->getEnding() < $refRange) {
                                // This condition must be first, because the second condition modifies the beginning of result, which is part of the condition
                                if ($checkResult->getEnding() >= $result->getBeginning()) {
                                    if (($checkResult->getBeginning() < $result->getBeginning()) || // the checkresult extend the result-range and ist overlapping with at
                                        ($checkResult->getBeginning() > $result->getEnding()) // checkResult-range has a gap to the former result
                                    ) {
                                        $flagChange = true;
                                        $result->setBeginning($checkResult->getBeginning());
                                    }
                                }
                                if ($checkResult->getEnding() > $result->getEnding()) { // move startborder of range nearer to test-date
                                    $flagChange = true;
                                    $result->setEnding($checkResult->getEnding());
                                }
                            }
                        }
                    }
                }
            }
            if ((!$flagFirstResult) | (!$flagChange)) {
                break;
            }
            $refRange = clone $result->getEnding();
            $flagChange = false;
            $loopLimiter--;
        }
        return $result;
    }


    /**
     *
     * Die
     * @param DateTime $startRange
     * @param DateTime $endRange
     * @param array $params
     * @param bool $highFirst
     * @return array // [TimerStartStopRange, bool]
     */
    protected function getInRangeNearLowerLimit(
        array $singleActiveTimer,
        ListOfTimerService $timerList,
        DateTime $startRange,
        DateTime $endRange
    ) {
        $starting = clone $startRange;
        $ending = clone $endRange;
        $result = GeneralUtility::makeInstance(TimerStartStopRange::class);
        $result->setResultExist(true);
        $result->setBeginning($starting);
        $result->setEnding($ending);
        $changed = false;
        if ($timerList->isActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
            $starting,
            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])) {
            $checkResult = $timerList->getLastIsActiveRangeResult($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                $starting,
                $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
            );
            $changed = true;
            // isActive => ($checkResult->getBeginning()<=$starting)
            if ($checkResult->getEnding() < $endRange) {
                $result->setBeginning($checkResult->getEnding());
            } else {
                $result->setResultExist(false);
            }
            return [$result, $changed];
        }

        /** @var TimerStartStopRange $checkResult */
        $checkResult = $timerList->nextActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
            $starting,
            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]);
        if (($checkResult->hasResultExist()) &&
            ($checkResult->getBeginning() < $endRange)
        ) {
            $result->setEnding($checkResult->getBeginning());
            $changed = true;
        }
        return [$result, $changed];
    }

    /**
     *
     * @param DateTime $startRange
     * @param DateTime $endRange
     * @param array $params
     * @param bool $highFirst
     * @return array // [TimerStartStopRange, bool]
     */
    protected function getInRangeNearHigherLimit(
        array $singleActiveTimer,
        ListOfTimerService $timerList,
        DateTime $startRange,
        DateTime $endRange
    ) {
        $starting = clone $startRange;
        $ending = clone $endRange;
        $result = GeneralUtility::makeInstance(TimerStartStopRange::class);
        $result->setResultExist(true);
        $result->setBeginning($starting);
        $result->setEnding($ending);
        $changed = false;
        if ($timerList->isActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
            $ending,
            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])
        ) {
            $checkResult = $timerList->getLastIsActiveRangeResult(
                $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                $ending,
                $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
            );
            $changed = true;
            // isActive => ($checkResult->getEnding()>=$ending)
            if ($checkResult->getBeginning() > $startRange) {
                $result->setEnding($checkResult->getBeginning());
            } else {
                $result->setResultExist(false);
            }
            return [$result, $changed];
        }

        /** @var TimerStartStopRange $checkResult */
        $checkResult = $timerList->prevActive(
            $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
            $endRange,
            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
        );
        if (($checkResult->hasResultExist()) &&
            ($checkResult->getEnding() > $startRange)
        ) {
            $result->setBeginning($checkResult->getEnding());
            $changed = true;
        }
        return [$result, $changed];
    }

    /**
     * @param array $params
     * @return array
     * @throws TimerException
     */
    protected function readPeriodListFromYamlFile(array $params): array
    {
        if (!isset($params[self::ARG_YAML_PERIOD_FILE_PATH])) {
            return [];
        }
        return $this->readListFromYamlFile($params[self::ARG_YAML_PERIOD_FILE_PATH]);
    }

    /**
     * @param string $yamlFilePath
     * @return array
     * @throws TimerException
     */
    protected function readListFromYamlFile(string $yamlFilePath): array
    {
        $yamlFilePathNew = $yamlFilePath;
        if (file_exists($yamlFilePath)) {
            $yamlFilePathNew = realpath($yamlFilePath);
        } else {
            if (GeneralUtility::validPathStr($yamlFilePath)) {
                $yamlFilePathNew = GeneralUtility::getFileAbsFileName($yamlFilePath);
            }
        }
        if (!file_exists($yamlFilePath)) {
            return [];
        }
        $this->yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);

        $yamlConfig = $this->yamlFileLoader->load($yamlFilePathNew);
        if (!is_array($yamlConfig[self::YAML_LIST_KEY])) {
            throw new TimerException(
                'The key `' . self::YAML_LIST_KEY . '` does not exist in your active yaml-file (`' .
                $yamlFilePath . '`). ' .
                'Please check your configuration.',
                1600865701
            );
        }
        return $yamlConfig;
    }
//
//    /**
//     * @param $activeTimerList
//     * @param ListOfTimerService $timerList
//     * @param DateTime $dateLikeEventZone
//     * @param array $listActiveRanges
//     * @return array
//     */
//    protected function generateListWithTimerAndNextRanges(
//        array $activeTimerList,
//        ListOfTimerService $timerList,
//        DateTime $dateLikeEventZone
//    ): array {
//        $listActiveRanges = [];
//        $flagMinNeedSet = true;
//        $lowerActive = null;
//        $upperActive = null;
//        foreach ($activeTimerList as $singleActiveTimer) {
//            if (
//                (!isset($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR], $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])) &&
//                ($timerList->validate($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]))
//            ) {
//                // log only the missing of an allowed-timerdcefinition
//                $this->logger->critical('The needed values `' . print_r($singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
//                        true) .
//                    '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
//                    'Please check your definition in your active yaml-file. [1600865701]'
//                );
//            } else {
//                $this->logWarningIfSelfCalling('active-next', $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                    $activeTimerList, []
//                );
//
//                /** @var TimerStartStopRange $timerRange */
//                $timerRange = $timerList->nextActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                    $dateLikeEventZone,
//                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]);
//                if ($timerRange->hasResultExist()) {
//                    $listActiveRanges[] = [
//                        self::YAML_LIST_ITEM_SELECTOR => $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                        self::YAML_LIST_ITEM_PARAMS => $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
//                        self::YAML_LIST_ITEM_RANGE => $timerRange,
//                    ];
//                    if ($flagMinNeedSet) {
//                        $lowerActive = $timerRange->getBeginning();
//                        $upperActive = $timerRange->getEnding();
//                        $upperActive->add(new DateInterval('PT59S'));
//                        $flagMinNeedSet = false;
//                    } else {
//                        //  getTimestamp because i do't know, if the compasrion of dateTime take respect to different timezones
//                        if ($lowerActive->getTimestamp() > $timerRange->getBeginning()->getTimestamp()) {
//                            $lowerActive = $timerRange->getBeginning();
//                            if (($upperActive->getTimestamp() < $timerRange->getEnding()->getTimestamp()) ||
//                                ($timerRange->getEnding()->getTimestamp() < $lowerActive->getTimestamp())
//                            ) {
//                                $upperActive = $timerRange->getEnding();
//                                if ($upperActive->format('s') == 0) {
//                                    $upperActive->add(new DateInterval('PT59S'));
//                                }
//                            }
//                        } else {
//                            if (($upperActive->getTimestamp() >= $timerRange->getBeginning()->getTimestamp()) &&
//                                ($timerRange->getEnding()->getTimestamp() > $upperActive->getTimestamp())
//                            ) {
//                                $upperActive = $timerRange->getEnding();
//                                if ((int)$upperActive->format('s') == 0) {
//                                    $upperActive->add(new DateInterval('PT59S'));
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//
//        return [$lowerActive, $upperActive, $listActiveRanges];
//    }
//
//    /**
//     * @param $activeTimerList
//     * @param ListOfTimerService $timerList
//     * @param DateTime $dateLikeEventZone
//     * @param array $listActiveRanges
//     * @return array
//     */
//    protected function generateListWithTimerAndprevRanges(
//        array $activeTimerList,
//        ListOfTimerService $timerList,
//        DateTime $dateLikeEventZone
//    ): array {
//        $listActiveRanges = [];
//        $flagMinNeedSet = true;
//        $lowerActive = null;
//        $upperActive = null;
//        foreach ($activeTimerList as $singleActiveTimer) {
//            if (
//                (!isset($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR], $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])) &&
//                ($timerList->validate($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]))
//            ) {
//                // log only the missing of an allowed-timerdcefinition
//                $this->logger->critical('The needed values `' . print_r($singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
//                        true) .
//                    '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
//                    'Please check your definition in your active yaml-file. [1600865701]'
//                );
//            } else {
//                $this->logWarningIfSelfCalling('active-prev', $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                    $activeTimerList, []
//                );
//                /** @var TimerStartStopRange $timerRange */
//                $timerRange = $timerList->prevActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                    $dateLikeEventZone,
//                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]);
//                if ($timerRange->hasResultExist()) {
//                    $listActiveRanges[] = [
//                        self::YAML_LIST_ITEM_SELECTOR => $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
//                        self::YAML_LIST_ITEM_PARAMS => $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
//                        self::YAML_LIST_ITEM_RANGE => $timerRange,
//                    ];
//                    if ($flagMinNeedSet) {
//                        $lowerActive = $timerRange->getBeginning();
//                        $upperActive = $timerRange->getEnding();
//                    } else {
//                        if ($upperActive < $timerRange->getEnding()) {
//                            $lowerActive = $timerRange->getBeginning();
//                            $upperActive = $timerRange->getEnding();
//                        } else {
//                            if (($upperActive->getTimestamp() === $timerRange->getEnding()->getTimestamp()) &&
//                                ($lowerActive->getTimestamp() > $timerRange->getBeginning()->getTimestamp())) {
//                                $lowerActive = $timerRange->getBeginning();
//                            }
//                        }
//                    }
//                }
//            }
//        }
//        return [$lowerActive, $upperActive, $listActiveRanges];
//    }
//
//    /**
//     * @param array $listActiveRangesAndTimer
//     * @param DateTime|null $lowerActive
//     * @param DateTime|null $upperActive
//     * @param DateTime $dateLikeEventZone
//     * @param ListOfTimerService $timerList
//     * @return array
//     */
//    protected function generateNextMergedActiveRange(
//        array $listActiveRangesAndTimer,
//        $lowerActive,
//        $upperActive,
//        DateTime $dateLikeEventZone,
//        ListOfTimerService $timerList
//    ) {
//        $result = GeneralUtility::makeInstance(TimerStartStopRange::class);
//        if ((empty($listActiveRangesAndTimer)) ||
//            ($lowerActive === null) ||
//            ($upperActive === null)
//        ) {
//            $flagActiveChange = false;
//            $result->failOnlyPrevActive($dateLikeEventZone);
//        } else {
//            // detect for given Range of UpperActive an LowerActive the widest active Array
//            $flagActiveChange = true;
//            $changeLimit = self::MAX_TIME_LIMIT_ACTIVE_COUNT;
//            while (($flagActiveChange) && ($changeLimit > 0)) {
//                $flagActiveChange = false;
//                foreach ($listActiveRangesAndTimer as $key => $itemActiveRange) {
//                    if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->hasResultExist()) {
//                        if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getBeginning() <= $upperActive) {
//                            $flagActiveChange = true;
//                            if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getEnding() > $upperActive) {
//                                $upperActive = clone $itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getEnding();
//                            }
//                            $listActiveRangesAndTimer[$key][self::YAML_LIST_ITEM_RANGE] =
//                                $timerList->nextActive($itemActiveRange[self::YAML_LIST_ITEM_SELECTOR],
//                                    $upperActive,
//                                    $itemActiveRange[self::YAML_LIST_ITEM_PARAMS]
//                                );
//                        }
//                    }
//                }
//                $changeLimit--;
//            }
//            // result with out use of forbiden
//            $result->setBeginning($lowerActive);
//            $result->setEnding($upperActive);
//            $result->setResultExist(true);
//        }
//        return [$flagActiveChange, $result];
//    }
//
//    /**
//     * @param array $listActiveRangesAndTimer
//     * @param DateTime|null $lowerActive
//     * @param DateTime|null $upperActive
//     * @param DateTime $dateLikeEventZone
//     * @param ListOfTimerService $timerList
//     * @return array
//     */
//    protected function generatePrevMergedActiveRange(
//        array $listActiveRangesAndTimer,
//        $lowerActive,
//        $upperActive,
//        DateTime $dateLikeEventZone,
//        ListOfTimerService $timerList
//    ) {
//        $result = GeneralUtility::makeInstance(TimerStartStopRange::class);
//        if ((empty($listActiveRangesAndTimer)) ||
//            ($lowerActive === null) ||
//            ($upperActive === null)
//        ) {
//            $flagActiveChange = false;
//            $result->failOnlyNextActive($dateLikeEventZone);
//        } else {
//            // detect for given Range of UpperActive an LowerActive the widest active Array
//            $flagActiveChange = true;
//            $changeLimit = self::MAX_TIME_LIMIT_ACTIVE_COUNT;
//            while (($flagActiveChange) && ($changeLimit > 0)) {
//                $flagActiveChange = false;
//                foreach ($listActiveRangesAndTimer as $key => $itemActiveRange) {
//                    if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->hasResultExist()) {
//                        if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getEnding() >= $lowerActive) {
//                            $flagActiveChange = true;
//                            if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getBeginning() < $lowerActive) {
//                                $upperActive = clone $itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getEnding();
//                            }
//                            $listActiveRangesAndTimer[$key][self::YAML_LIST_ITEM_RANGE] =
//                                $timerList->prevActive($itemActiveRange[self::YAML_LIST_ITEM_SELECTOR],
//                                    $lowerActive,
//                                    $itemActiveRange[self::YAML_LIST_ITEM_PARAMS]
//                                );
//                        }
//                    }
//                }
//                $changeLimit--;
//            }
//            // result with out use of forbiden
//            $result->setBeginning($lowerActive);
//            $result->setEnding($upperActive);
//            $result->setResultExist(true);
//        }
//        return [$flagActiveChange, $result];
//    }

    /**
     * @param $lowerActive
     * @param $upperActive
     * @param array $params
     */
    protected function logErrorForActiveRange(
        $lowerActive,
        $upperActive,
        array $params
    ): void {
        /** @var $logger Logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $this->logger->log(
            LogLevel::CRITICAL,
            'The fully active nextrange for the timer `' . self::selfName() . '` with the current parameter ' .
            'seem not to end. It need more than ' . self::MAX_TIME_LIMIT_ACTIVE_COUNT .
            ' cycles to get a full range. A failed result is used instead of the estimated result.',
            [
                'Lower' => json_encode($lowerActive),
                'upper' => json_encode($upperActive),
                'params' => json_encode($params),
            ]
        );
    }

// for testing approches

    /**
     * @return string
     */
    protected function getExtentionPathByEnviroment(): string
    {
        return Environment::getExtensionsPath();
    }

    /**
     * @return string
     */
    protected function getPublicPathByEnviroment(): string
    {
        return Environment::getPublicPath();
    }


    /**
     * @param $dateStart
     * @param $dateStop
     * @param bool $flag
     * @param DateTime $dateLikeEventZone
     */
    protected function setIsActiveResult(
        $dateStart,
        $dateStop,
        bool $flag,
        DateTime $dateLikeEventZone,
        $params = []
    ): void {
        if (empty($this->lastIsActiveResult)) {
            $this->lastIsActiveResult = GeneralUtility::makeInstance(TimerStartStopRange::class);
        }
        $this->lastIsActiveResult->setBeginning($dateStart);
        $this->lastIsActiveResult->setEnding($dateStop);
        $this->lastIsActiveResult->setResultExist($flag && (($dateStart <= $dateLikeEventZone) && ($dateLikeEventZone <= $dateStop)));
        $this->lastIsActiveTimestamp = $dateLikeEventZone->getTimestamp();
        $this->lastIsActiveParams = $params;
    }

    /**
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    protected function getLastIsActiveResult(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        if (empty($this->lastIsActiveResult)) {
            $this->lastIsActiveResult = GeneralUtility::makeInstance(TimerStartStopRange::class);
            $this->lastIsActiveTimestamp = $dateLikeEventZone->getTimestamp() + 1; // trigger isActive() in the next step
        }
        if ((is_null($this->lastIsActiveTimestamp)) ||
            ($this->lastIsActiveTimestamp !== $dateLikeEventZone->getTimestamp()) ||
            (md5(json_encode($this->lastIsActiveParams)) !== md5(json_encode($params)))
        ) {
            $this->isActive($dateLikeEventZone, $params);
        }
        return clone $this->lastIsActiveResult;
    }

}