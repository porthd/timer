<?php

namespace Porthd\Timer\CustomTimer;

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


class RangeListTimer implements TimerInterface, LoggerAwareInterface
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

//    /**
//     * @param YamlFileLoader $yamlFileLoader
//     */
//    public function injectYamlFileLoader(YamlFileLoader $yamlFileLoader)
//    {
//        $this->yamlFileLoader = $yamlFileLoader;
//    }

    /**
     * @var ListingRepository
     */
    protected $listingRepository;
//    public function injectListingRepository(ListingRepository $listingRepository)
//    {
//        $this->listingRepository = $listingRepository;
//    }

//    public function __construct()
//    {
////        $this->listingRepository =  GeneralUtility::makeInstance(ListingRepository::class);
//        $this->yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
//    }

    public const TIMER_NAME = 'txTimerRangeList';
    protected const ARG_YAML_ACTIVE_FILE_PATH = 'yamlActiveFilePath';
    protected const ARG_YAML_FORBIDDEN_FILE_PATH = 'yamlForbiddenFilePath';

    protected const ARG_DATABASE_ACTIVE_RANGE_LIST = 'databaseActiveRangeList';
    protected const ARG_DATABASE_FORBIDDEN_RANGE_LIST = 'databaseForbiddenRangeList';
    protected const YAML_LIST_KEY = 'rangelist';
    protected const YAML_LIST_ITEM_SELECTOR = 'selector';
    protected const YAML_LIST_ITEM_TITLE = 'title';
    protected const YAML_LIST_ITEM_DESCRIPTION = 'description';
    protected const YAML_LIST_ITEM_PARAMS = 'params';
    protected const YAML_LIST_ITEM_RANGE = 'range';

    protected const MAX_TIME_LIMIT_MERGE_COUNT = 4; // count of loops to check for overlapping ranges
    protected const MAX_TIME_LIMIT_ACTIVE_COUNT = 10; // count of loops to check for overlapping active ranges
    protected const MAX_TIME_LIMIT_FORBIDDEN_COUNT = 10; // // count of loops to check for overlapping forbidden ranges

    protected const MARK_OF_EXT_FOLDER_IN_FILEPATH = 'EXT:';
    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/RangeListTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_YAML_ACTIVE_FILE_PATH,
        self::ARG_YAML_FORBIDDEN_FILE_PATH,
        self::ARG_DATABASE_ACTIVE_RANGE_LIST,
        self::ARG_DATABASE_FORBIDDEN_RANGE_LIST,
    ];


    /**
     * tested 20210116
     *
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }


    /**
     * tested 20210116
     * @return array
     */
    public static function getSelectorItem(): array
    {
        return [
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerRangeList.select.name',
            self::TIMER_NAME,
        ];
    }

    /**
     * tested
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
     * tested 20210116
     *
     * @return array
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
    }

    /**
     * tested 20201226
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return bool
     */
    public function isAllowedInRange(DateTime $dateLikeEventZone, $params = []): bool
    {
        return ($params[self::ARG_ULTIMATE_RANGE_BEGINN] <= $dateLikeEventZone->format('Y-m-d H:i:s')) &&
            ($dateLikeEventZone->format('Y-m-d H:i:s') <= $params[self::ARG_ULTIMATE_RANGE_END]);
    }

    /**
     * tested general 20210116
     * tested special 20220803
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
        $flag = $flag && $this->validateDatabaseRangeList($params);
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
        return !(isset($params[self::ARG_EVER_TIME_ZONE_OF_EVENT])) ||
            TcaUtility::isTimeZoneInList(
                $params[self::ARG_EVER_TIME_ZONE_OF_EVENT]
            );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateUltimate(array $params = []): bool
    {
        $flag = (!empty($params[self::ARG_ULTIMATE_RANGE_BEGINN]));
        $flag = $flag && (false !== date_create_from_format(
                    self::TIMER_FORMAT_DATETIME,
                    $params[self::ARG_ULTIMATE_RANGE_BEGINN]
                ));
        $flag = $flag && (!empty($params[self::ARG_ULTIMATE_RANGE_END]));
        return ($flag && (false !== date_create_from_format(
                    self::TIMER_FORMAT_DATETIME,
                    $params[self::ARG_ULTIMATE_RANGE_END]
                )));
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    public function validateCountArguments(array $params = []): int
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
        foreach ([self::ARG_YAML_ACTIVE_FILE_PATH, self::ARG_YAML_FORBIDDEN_FILE_PATH] as $paramKey) {
            $filePath = (isset($params[$paramKey]) ?
                $params[$paramKey] :
                ''
            );
            if (!empty($filePath)) {
                if (strpos($filePath, self::MARK_OF_EXT_FOLDER_IN_FILEPATH) === 0) {
                    $extPath = $this->getExtentionPathByEnviroment();
                    $filePath = substr($filePath, strlen(self::MARK_OF_EXT_FOLDER_IN_FILEPATH));
                    $flag = $flag && file_exists($extPath . DIRECTORY_SEPARATOR . $filePath);
                } else {
                    $rootPath = $this->getPublicPathByEnviroment();
                    $flag = $flag && file_exists($rootPath . DIRECTORY_SEPARATOR . $filePath);
                }
            } else {
                $flag = $flag &&
                    (
                        ($paramKey === self::ARG_YAML_FORBIDDEN_FILE_PATH) ||
                        (!empty($params[self::ARG_DATABASE_ACTIVE_RANGE_LIST])) ||
                        (!empty($params[self::ARG_YAML_ACTIVE_FILE_PATH]))
                    );
            }
        }

        return $flag;
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateDatabaseRangeList(array $params = []): bool
    {
        $flag = true;
        foreach ([self::ARG_DATABASE_ACTIVE_RANGE_LIST, self::ARG_DATABASE_FORBIDDEN_RANGE_LIST] as $paramKey) {
            $commaList = (isset($params[$paramKey]) ?
                $params[$paramKey] :
                ''
            );
            if (!empty($commaList)) {
                $flag = $flag && (preg_match('/[^0-9, ]/', $commaList) === 0);
            } else {
                $flag = $flag &&
                    (
                        ($paramKey === self::ARG_DATABASE_FORBIDDEN_RANGE_LIST) ||
                        (!empty($params[self::ARG_DATABASE_ACTIVE_RANGE_LIST])) ||
                        (!empty($params[self::ARG_YAML_ACTIVE_FILE_PATH]))
                    );
            }
        }

        return $flag;
    }


    /**
     * tested: 20220910
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

        $flag = false;

        $yamlActiveConfig = $this->readActiveListFromYamlFile($params);
        $yamlActiveConfig = (empty($yamlActiveConfig[self::YAML_LIST_KEY]) ? [] : $yamlActiveConfig[self::YAML_LIST_KEY]);
        $databaseActiveConfig = $this->readActiveListFromDatabase($params);
        $activeTimer = array_merge($yamlActiveConfig, $databaseActiveConfig);
        if (empty($activeTimer)) {
            $this->logger->warning('Your path for the file ' . $yamlActiveConfig[self::YAML_LIST_KEY]
                . 'in `' . self::ARG_YAML_ACTIVE_FILE_PATH . '` is missing or empty and '
                . 'the entries for [' . $params[self::ARG_DATABASE_ACTIVE_RANGE_LIST] . '] in `' . self::ARG_DATABASE_ACTIVE_RANGE_LIST . '` are empty too. Please check '
                . 'your configuration. [1600865701].'
            );
            return $flag;
        }
        /** @var ListOfTimerService $timerList */
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        foreach ($activeTimer as $singleActiveTimer) {
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
                $this->logWarningIfSelfCalling('active',
                    $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $yamlActiveConfig,
                    $databaseActiveConfig
                );
                if ($timerList->isActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $dateLikeEventZone,
                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])
                ) {
                    $activeRange = $timerList->getLastIsActiveRangeResult(
                        $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $dateLikeEventZone,
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    );
                    $flag = true;
                    $this->setIsActiveResult($activeRange->getBeginning(), $activeRange->getEnding(), $flag,
                        $dateLikeEventZone,
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]);
                    break;
                }
            }
        }
        if ($flag) {
            // test the restriction for active-cases
            $yamlForbiddenConfig = $this->readForbiddenListFromYamlFile($params);
            $yamlForbiddenConfig = $yamlForbiddenConfig[self::YAML_LIST_KEY] ?? [];
            $databaseForbiddenConfig = $this->readForbiddenListFromDatabase($params);
            $forbiddenTimer = array_merge($yamlForbiddenConfig, $databaseForbiddenConfig);
            if (!empty($forbiddenTimer)) {
                foreach ($forbiddenTimer as $singleForbiddenTimer) {
                    if (
                        (!isset($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR], $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS])) &&
                        ($timerList->validate($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                            $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]))
                    ) {
                        // log only the missing of an allowed-timerdcefinition
                        $this->logger->critical('The needed parameter `' . print_r($singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS],
                                true) .
                            '` for the forbidden-timer `' . $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be missdefined or missing. ' .
                            'Please check your definition in your forbidden yaml-file. [1600874901]'
                        );
                    } else {
                        $this->logWarningIfSelfCalling(
                            'forbidden',
                            $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                            $yamlForbiddenConfig,
                            $databaseForbiddenConfig);
                        if ($timerList->isActive($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                            $dateLikeEventZone,
                            $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS])
                        ) {
                            $forbiddenRange = $timerList->getLastIsActiveRangeResult(
                                $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                                $dateLikeEventZone,
                                $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]
                            );
                            $flag = false;
                            $this->setIsActiveResult($forbiddenRange->getBeginning(), $forbiddenRange->getEnding(),
                                $flag, $dateLikeEventZone,
                                $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]);
                            break;
                        }
                    }
                }
            }
        }
        return (is_null($this->lastIsActiveResult) ? false : $this->lastIsActiveResult->getResultExist());
    }

    /**
     * tested:
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
     * tested 20220918
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        $yamlFileActiveTimerList = $this->readActiveListFromYamlFile($params);
        $databaseActiveTimerList = $this->readActiveListFromDatabase($params);
        $activeTimerList = array_merge(($yamlFileActiveTimerList[self::YAML_LIST_KEY] ?? []), $databaseActiveTimerList);
        $refDateTime = clone $dateLikeEventZone;
        /** @var ListOfTimerService $timerList */
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        $yamlForbiddenTimerList = $this->readForbiddenListFromYamlFile($params);
        $databaseForbiddenTimerList = $this->readForbiddenListFromDatabase($params);
        $forbiddenTimerList = array_merge(($yamlForbiddenTimerList[self::YAML_LIST_KEY] ?? []),
            $databaseForbiddenTimerList);
        // Range-calculate-algorithm
        if (!empty($forbiddenTimerList)) {
            // merge active ranges defined by timers together and reduce the by merged forbidden ranges defined by timers
            $result = $this->getActivePartialRangeWithLowestBeginRefDate(
                $activeTimerList,
                $forbiddenTimerList,
                $timerList,
                $refDateTime
            );
        } else {
            // merge only list of active timer together
            $result = $this->getActiveRangeWithLowestBeginRefDate(
                $activeTimerList,
                $timerList,
                $refDateTime
            );
        }
        if ((!$this->isAllowedInRange($result->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($result->getEnding(), $params))
        ) {
            $result->failOnlyNextActive($dateLikeEventZone);
        }

        return $result;
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested 20220925
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        $yamlFileActiveTimerList = $this->readActiveListFromYamlFile($params);
        $databaseActiveTimerList = $this->readActiveListFromDatabase($params);
        $activeTimerList = array_merge($yamlFileActiveTimerList, $databaseActiveTimerList);
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
            $activeResult = $this->getActiveRangeWithHighestEndRefDate(
                $activeTimerList[self::YAML_LIST_KEY],
                $timerList,
                $refDateTime
            );
            $yamlForbiddenTimerList = $this->readForbiddenListFromYamlFile($params);
            $databaseForbiddenTimerList = $this->readForbiddenListFromDatabase($params);
            $forbiddenTimerList = array_merge($yamlForbiddenTimerList, $databaseForbiddenTimerList);
            if (!empty($forbiddenTimerList[self::YAML_LIST_KEY])) {
                [$result, $changed] = $this->reduceActiveRangeByForbiddenRangesWithHighestEnd(
                    $forbiddenTimerList[self::YAML_LIST_KEY],
                    $timerList,
                    $activeResult
                );
            } else {
                $result = $activeResult;
            }

            if ($result->hasResultExist()) {
                break; // the next active range is detected
            }
            // try to find a next range by using the ending-date of the currently used active range
            $refDateTime = clone $activeResult->getBeginning();
            $refDateTime->add(new DateInterval('PT1S'));
            $loopLimiter--;
        }

        if ((!$this->isAllowedInRange($result->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($result->getEnding(), $params))
        ) {
            $result->failOnlyNextActive($dateLikeEventZone);
        }
        return $result;

    }


    /**
     * @param array $activeTimerList
     * @param ListOfTimerService $timerList
     * @param DateTime $dateLikeEventZone
     * @return array // [TimerStartStopRange, bool]
     */
    protected function reduceActiveRangeByForbiddenRangesWithLowestStart(
        array $forbiddenTimerList,
        ListOfTimerService $timerList,
        TimerStartStopRange $currentActiveRange
    ) {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        $startRange = $currentActiveRange->getBeginning();
        $stopRange = $currentActiveRange->getEnding();
        $result = clone $currentActiveRange;
        $changed = false;
        while ($loopLimiter > 0) {
            foreach ($forbiddenTimerList as $singleActiveTimer) {
                $flagNoRangeChangeByForbidden = true;
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
                    $this->logWarningIfSelfCalling('forbidden-next', $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $forbiddenTimerList, []
                    );
                    /** TimerStartStopRange $checkResult  */
                    /** bool $changed  */
                    [$checkResult, $changed] = $this->getInRangeNearLowerLimit($singleActiveTimer,
                        $timerList,
                        $startRange,
                        $stopRange
                    );

                    if ($changed) {
                        $flagNoRangeChangeByForbidden = false;
                        if ($checkResult->hasResultExist()) {
                            if ($checkResult->getBeginning() > $startRange) {
                                $startRange = clone $checkResult->getBeginning();
                                $result->setBeginning($checkResult->getBeginning());
                            }
                            if ($checkResult->getEnding() < $stopRange) {
                                $stopRange = clone $checkResult->getEnding();
                                $result->setEnding($checkResult->getEnding());
                            }
                        } else {
                            $result->failOnlyNextActive($startRange);
                            return [$result, $changed];
                        }
                    }
                }
            }
            if ($flagNoRangeChangeByForbidden) {
                break 1;  // there were no changes by any entry in the $forbiddenTimerList, there is no need for another loop
            }
            $loopLimiter--;
        }
        return [$result, $changed];
    }

    /**
     * @param array $activeTimerList
     * @param ListOfTimerService $timerList
     * @param DateTime $dateLikeEventZone
     * @return array // [TimerStartStopRange, bool]
     */
    protected function reduceActiveRangeByForbiddenRangesWithHighestEnd(
        array $forbiddenTimerList,
        ListOfTimerService $timerList,
        TimerStartStopRange $currentActiveRange
    ) {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        $startRange = $currentActiveRange->getBeginning();
        $stopRange = $currentActiveRange->getEnding();
        $result = clone $currentActiveRange;
        $changed = false;
        while ($loopLimiter > 0) {
            foreach ($forbiddenTimerList as $singleActiveTimer) {
                $flagNoRangeChangeByForbidden = true;
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
                    $this->logWarningIfSelfCalling('forbidden-prev', $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $forbiddenTimerList, []
                    );
                    /** TimerStartStopRange $checkResult  */
                    /** bool $changed  */
                    [$checkResult, $changed] = $this->getInRangeNearHigherLimit($singleActiveTimer,
                        $timerList,
                        $startRange,
                        $stopRange
                    );

                    if ($changed) {
                        $flagNoRangeChangeByForbidden = false;
                        if ($checkResult->hasResultExist()) {
                            if ($checkResult->getEnding() < $stopRange) {
                                $stopRange = clone $checkResult->getEnding();
                                $result->setEnding($checkResult->getEnding());
                            }
                            if ($checkResult->getBeginning() > $startRange) {
                                $startRange = clone $checkResult->getBeginning();
                                $result->setBeginning($checkResult->getBeginning());
                            }
                        } else {
                            $result->failOnlyPrevActive($startRange);
                            return [$result, $changed];
                        }
                    }
                }
            }
            if ($flagNoRangeChangeByForbidden) {
                break 1;  // there were no changes by any entry in the $forbiddenTimerList, there is no need for another loop
            }
            $loopLimiter--;
        }
        return [$result, $changed];
    }

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

                    /**
                     * Check, if the current date is active and although Part of forbidden.
                     * If Yes: then check, if the resulting part is part of a forbidden-range and
                     * if a part of the current active part is above the current date (next active-part)
                     */
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
    protected function getActiveRangeWithIncludeStartingRange(
        array $activeTimerList,
        ListOfTimerService $timerList,
        TimerStartStopRange $dateRage
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

                    /**
                     * Check, if the current date is active and although Part of forbidden.
                     * If Yes: then check, if the resulting part is part of a forbidden-range and
                     * if a part of the current active part is above the current date (next active-part)
                     */
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
     * @param array $forbiddenTimerList
     * @param ListOfTimerService $timerList
     * @param DateTime $dateLikeEventZone
     * @param int $recursionCount
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function getActivePartialRangeWithLowestBeginRefDate(
        array $activeTimerList,
        array $forbiddenTimerList,
        ListOfTimerService $timerList,
        DateTime $dateLikeEventZone,
        $recursionCount = self::MAX_TIME_LIMIT_MERGE_COUNT
    ): TimerStartStopRange {
        if ($recursionCount < 0) {
            $failRange = new TimerStartStopRange();
            $failRange->failOnlyNextActive($dateLikeEventZone);
            return $failRange;
        }
        $refDate = clone $dateLikeEventZone;

        /**
         * 1. Detect, if current-dat in range of active-Parts
         */
        /* @var TimerStartStopRange $firstActiveCurrentRange */
        /* @var bool $flagIsInActive */
        [$flagIsInActive, $firstActiveCurrentRange] = $this->isRefdateInActiveRange(
            $activeTimerList,
            $timerList,
            $refDate
        );

        if ($flagIsInActive) {
            [$flagIsInForbidden, $firstForbiddenRange] = $this->isRefdateInActiveRange(
                $forbiddenTimerList,
                $timerList,
                $refDate
            );
            if (!$flagIsInForbidden) {
                $firstForbiddenRange = $this->getActiveRangeWithLowestBeginRefDate($forbiddenTimerList,
                    $timerList,
                    $dateLikeEventZone
                );
            }
            if ($firstForbiddenRange->getEnding() < $firstActiveCurrentRange->getEnding()) {
                $result = clone $firstActiveCurrentRange;
                $result->setBeginning($firstForbiddenRange->getEnding());
            } else { // The current active ranges are not allowed => search for the next
                $startTestDate = clone $firstForbiddenRange->getEnding();
                $result = $this->reduceActiveRangeByNearestForbiddenRange(
                    $startTestDate,
                    $activeTimerList,
                    $forbiddenTimerList,
                    $timerList,
                    $recursionCount
                );
            }
        } else {
            $result = $this->reduceActiveRangeByNearestForbiddenRange(
                $dateLikeEventZone,
                $activeTimerList,
                $forbiddenTimerList,
                $timerList,
                $recursionCount
            );
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
        $result = new TimerStartStopRange();
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
    protected function readActiveListFromYamlFile(array $params): array
    {
        if (!isset($params[self::ARG_YAML_ACTIVE_FILE_PATH])) {
            return [];
        }
        return $this->readListFromYamlFile($params[self::ARG_YAML_ACTIVE_FILE_PATH]);
    }

    /**
     * @param array $params
     * @return array
     * @throws TimerException
     */
    protected function readForbiddenListFromYamlFile(array $params): array
    {
        if (!isset($params[self::ARG_YAML_FORBIDDEN_FILE_PATH])) {
            return [];
        }
        return $this->readListFromYamlFile($params[self::ARG_YAML_FORBIDDEN_FILE_PATH]);
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
        /** @var YamlFileLoader $yamlFileLoader */
        $yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);

        $yamlConfig = $yamlFileLoader->load($yamlFilePathNew);
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


    /**
     * @param array $params
     * @return array
     * @throws TimerException
     */
    protected function readActiveListFromDatabase(array $params): array
    {

        if (!isset($params[self::ARG_DATABASE_ACTIVE_RANGE_LIST])) {
            return [];
        }
        return $this->readListFromDatabase($params[self::ARG_DATABASE_ACTIVE_RANGE_LIST]);
    }

    /**
     * @param array $params
     * @return array
     * @throws TimerException
     */
    protected function readForbiddenListFromDatabase(array $params): array
    {
        if (empty($params[self::ARG_DATABASE_FORBIDDEN_RANGE_LIST])) {
            return [];
        }
        return $this->readListFromDatabase($params[self::ARG_DATABASE_FORBIDDEN_RANGE_LIST]);
    }

    /**
     * @param string $commaListOfUids
     * @return array
     * @throws TimerException
     */
    protected function readListFromDatabase(string $commaListOfUids): array
    {
        if (empty(trim($commaListOfUids))) {
            return [];
        }
        $listingRepository = GeneralUtility::makeInstance(ListingRepository::class);
        $rawResult = $listingRepository->findByCommaList($commaListOfUids);
        if (empty($rawResult)) {
            return [];
        }

        $result = [];
        /** @var Listing $item */
        foreach ($rawResult as $item) {
            if ($item !== null) {
                if (is_object($item)) {
                    $rawFlexformString = $item->getTxTimerTimer();
                } else {
                    if (is_array($item)) {
                        $rawFlexformString = $item['tx_timer_timer'];
                    } else {
                        throw new TimerException(
                            'The item is wether an object nor an array. Something went seriously wrong.',
                            1654238702
                        );
                    }
                }
                if (empty($rawFlexformString)) {
                    $params = [];
                } else {
                    $rawParamsArray = GeneralUtility::xml2array($rawFlexformString);
                    $params = TcaUtility::flexformArrayFlatten($rawParamsArray);
                }
                if (is_object($item)) {
                    $result[] = [
                        self::YAML_LIST_ITEM_SELECTOR => $item->getTxTimerSelector(),
                        self::YAML_LIST_ITEM_PARAMS => $params,
                        self::YAML_LIST_ITEM_TITLE => $item->getTitle(),
                        self::YAML_LIST_ITEM_DESCRIPTION => $item->getDescription(),
                    ];
                } else {
                    $result[] = [
                        self::YAML_LIST_ITEM_SELECTOR => $item['tx_timer_selector'],
                        self::YAML_LIST_ITEM_PARAMS => $params,
                        self::YAML_LIST_ITEM_TITLE => $item['title'],
                        self::YAML_LIST_ITEM_DESCRIPTION => $item['description'],
                    ];
                }
            }
        }
        return $result;
    }


    /**
     * @param $activeTimerList
     * @param ListOfTimerService $timerList
     * @param DateTime $dateLikeEventZone
     * @param array $listActiveRanges
     * @return array
     */
    protected function generateListWithTimerAndNextRanges(
        array $activeTimerList,
        ListOfTimerService $timerList,
        DateTime $dateLikeEventZone
    ): array {
        $listActiveRanges = [];
        $flagMinNeedSet = true;
        $lowerActive = null;
        $upperActive = null;
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

                /** @var TimerStartStopRange $timerRange */
                $timerRange = $timerList->nextActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $dateLikeEventZone,
                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]);
                if ($timerRange->hasResultExist()) {
                    $listActiveRanges[] = [
                        self::YAML_LIST_ITEM_SELECTOR => $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        self::YAML_LIST_ITEM_PARAMS => $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                        self::YAML_LIST_ITEM_RANGE => $timerRange,
                    ];
                    if ($flagMinNeedSet) {
                        $lowerActive = $timerRange->getBeginning();
                        $upperActive = $timerRange->getEnding();
                        $upperActive->add(new DateInterval('PT59S'));
                        $flagMinNeedSet = false;
                    } else {
                        //  getTimestamp because i do't know, if the compasrion of dateTime take respect to different timezones
                        if ($lowerActive->getTimestamp() > $timerRange->getBeginning()->getTimestamp()) {
                            $lowerActive = $timerRange->getBeginning();
                            if (($upperActive->getTimestamp() < $timerRange->getEnding()->getTimestamp()) ||
                                ($timerRange->getEnding()->getTimestamp() < $lowerActive->getTimestamp())
                            ) {
                                $upperActive = $timerRange->getEnding();
                                if ($upperActive->format('s') == 0) {
                                    $upperActive->add(new DateInterval('PT59S'));
                                }
                            }
                        } else {
                            if (($upperActive->getTimestamp() >= $timerRange->getBeginning()->getTimestamp()) &&
                                ($timerRange->getEnding()->getTimestamp() > $upperActive->getTimestamp())
                            ) {
                                $upperActive = $timerRange->getEnding();
                                if ((int)$upperActive->format('s') == 0) {
                                    $upperActive->add(new DateInterval('PT59S'));
                                }
                            }
                        }
                    }
                }
            }
        }

        return [$lowerActive, $upperActive, $listActiveRanges];
    }

    /**
     * @param $activeTimerList
     * @param ListOfTimerService $timerList
     * @param DateTime $dateLikeEventZone
     * @param array $listActiveRanges
     * @return array
     */
    protected function generateListWithTimerAndprevRanges(
        array $activeTimerList,
        ListOfTimerService $timerList,
        DateTime $dateLikeEventZone
    ): array {
        $listActiveRanges = [];
        $flagMinNeedSet = true;
        $lowerActive = null;
        $upperActive = null;
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
                /** @var TimerStartStopRange $timerRange */
                $timerRange = $timerList->prevActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $dateLikeEventZone,
                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]);
                if ($timerRange->hasResultExist()) {
                    $listActiveRanges[] = [
                        self::YAML_LIST_ITEM_SELECTOR => $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        self::YAML_LIST_ITEM_PARAMS => $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                        self::YAML_LIST_ITEM_RANGE => $timerRange,
                    ];
                    if ($flagMinNeedSet) {
                        $lowerActive = $timerRange->getBeginning();
                        $upperActive = $timerRange->getEnding();
                    } else {
                        if ($upperActive < $timerRange->getEnding()) {
                            $lowerActive = $timerRange->getBeginning();
                            $upperActive = $timerRange->getEnding();
                        } else {
                            if (($upperActive->getTimestamp() === $timerRange->getEnding()->getTimestamp()) &&
                                ($lowerActive->getTimestamp() > $timerRange->getBeginning()->getTimestamp())) {
                                $lowerActive = $timerRange->getBeginning();
                            }
                        }
                    }
                }
            }
        }
        return [$lowerActive, $upperActive, $listActiveRanges];
    }

    /**
     * @param array $listActiveRangesAndTimer
     * @param DateTime|null $lowerActive
     * @param DateTime|null $upperActive
     * @param DateTime $dateLikeEventZone
     * @param ListOfTimerService $timerList
     * @return array
     */
    protected function generateNextMergedActiveRange(
        array $listActiveRangesAndTimer,
        $lowerActive,
        $upperActive,
        DateTime $dateLikeEventZone,
        ListOfTimerService $timerList
    ) {
        $result = new TimerStartStopRange();
        if ((empty($listActiveRangesAndTimer)) ||
            ($lowerActive === null) ||
            ($upperActive === null)
        ) {
            $flagActiveChange = false;
            $result->failOnlyPrevActive($dateLikeEventZone);
        } else {
            // detect for given Range of UpperActive an LowerActive the widest active Array
            $flagActiveChange = true;
            $changeLimit = self::MAX_TIME_LIMIT_ACTIVE_COUNT;
            while (($flagActiveChange) && ($changeLimit > 0)) {
                $flagActiveChange = false;
                foreach ($listActiveRangesAndTimer as $key => $itemActiveRange) {
                    if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->hasResultExist()) {
                        if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getBeginning() <= $upperActive) {
                            $flagActiveChange = true;
                            if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getEnding() > $upperActive) {
                                $upperActive = clone $itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getEnding();
                            }
                            $listActiveRangesAndTimer[$key][self::YAML_LIST_ITEM_RANGE] =
                                $timerList->nextActive($itemActiveRange[self::YAML_LIST_ITEM_SELECTOR],
                                    $upperActive,
                                    $itemActiveRange[self::YAML_LIST_ITEM_PARAMS]
                                );
                        }
                    }
                }
                $changeLimit--;
            }
            // result with out use of forbiden
            $result->setBeginning($lowerActive);
            $result->setEnding($upperActive);
            $result->setResultExist(true);
        }
        return [$flagActiveChange, $result];
    }

    /**
     * @param array $listActiveRangesAndTimer
     * @param DateTime|null $lowerActive
     * @param DateTime|null $upperActive
     * @param DateTime $dateLikeEventZone
     * @param ListOfTimerService $timerList
     * @return array
     */
    protected function generatePrevMergedActiveRange(
        array $listActiveRangesAndTimer,
        $lowerActive,
        $upperActive,
        DateTime $dateLikeEventZone,
        ListOfTimerService $timerList
    ) {
        $result = new TimerStartStopRange();
        if ((empty($listActiveRangesAndTimer)) ||
            ($lowerActive === null) ||
            ($upperActive === null)
        ) {
            $flagActiveChange = false;
            $result->failOnlyNextActive($dateLikeEventZone);
        } else {
            // detect for given Range of UpperActive an LowerActive the widest active Array
            $flagActiveChange = true;
            $changeLimit = self::MAX_TIME_LIMIT_ACTIVE_COUNT;
            while (($flagActiveChange) && ($changeLimit > 0)) {
                $flagActiveChange = false;
                foreach ($listActiveRangesAndTimer as $key => $itemActiveRange) {
                    if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->hasResultExist()) {
                        if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getEnding() >= $lowerActive) {
                            $flagActiveChange = true;
                            if ($itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getBeginning() < $lowerActive) {
                                $upperActive = clone $itemActiveRange[self::YAML_LIST_ITEM_RANGE]->getEnding();
                            }
                            $listActiveRangesAndTimer[$key][self::YAML_LIST_ITEM_RANGE] =
                                $timerList->prevActive($itemActiveRange[self::YAML_LIST_ITEM_SELECTOR],
                                    $lowerActive,
                                    $itemActiveRange[self::YAML_LIST_ITEM_PARAMS]
                                );
                        }
                    }
                }
                $changeLimit--;
            }
            // result with out use of forbiden
            $result->setBeginning($lowerActive);
            $result->setEnding($upperActive);
            $result->setResultExist(true);
        }
        return [$flagActiveChange, $result];
    }

    /**
     * @param $yamlForbiddenConfig
     * @param ListOfTimerService $timerList
     * @param $relativeToLowerActive
     * @return array
     * @throws TimerException
     */
    protected function generateForbiddenListWithNextRangesAndTimer(
        $forbiddenTimerList,
        ListOfTimerService $timerList,
        $relativeToLowerActive
    ): array {
        $listForbiddenRanges = [];
        $flagMinNeedSet = true;
        $lowerForbidden = null;
        $upperForbidden = null;
        foreach ($forbiddenTimerList as $singleForbiddenTimer) {
            if (
                (!isset($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR], $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS])) &&
                ($timerList->validate($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]))
            ) {
                // log only the missing of an allowed-timerdcefinition
                $this->logger->critical('The needed values `' . print_r($singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS],
                        true) .
                    '` for the active-timer `' . $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                    'Please check your definition in your active yaml-file. [1600865701]'
                );
            } else {
                $this->logWarningIfSelfCalling('forbidden-next', $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $forbiddenTimerList, []
                );

                if ($timerList->isActive($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $relativeToLowerActive,
                    $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS])
                ) {
                    $nextHelp = $timerList->nextActive($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $relativeToLowerActive,
                        $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]);
                    $timerRange = $timerList->prevActive($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $nextHelp->getBeginning(),
                        $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]);
                } else {
                    /** @var TimerStartStopRange $timerRange */
                    $timerRange = $timerList->nextActive($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $relativeToLowerActive,
                        $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]);
                }
                if ($timerRange->hasResultExist()) {
                    $listForbiddenRanges[] = [
                        self::YAML_LIST_ITEM_SELECTOR => $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                        self::YAML_LIST_ITEM_PARAMS => $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS],
                        self::YAML_LIST_ITEM_RANGE => $timerRange,
                    ];
                    if ($flagMinNeedSet) {
                        $lowerForbidden = $timerRange->getBeginning();
                        $upperForbidden = $timerRange->getEnding();
                        $flagMinNeedSet = false;
                    } else {
                        if (($upperForbidden < $timerRange->getEnding()) &&
                            ($timerRange->getBeginning() <= $upperForbidden)) {
                            $upperForbidden = $timerRange->getEnding();
                        }
                        if (($timerRange->getBeginning() < $lowerForbidden) &&
                            ($lowerForbidden <= $timerRange->getEnding())
                        ) {
                            $lowerForbidden = $timerRange->getBeginning();
                        }
                        if (($timerRange->getEnding() < $lowerForbidden) &&
                            ($relativeToLowerActive < $timerRange->getEnding())
                        ) {
                            // perhaps an is-active-Part
                            $lowerForbidden = $timerRange->getBeginning();
                            $upperForbidden = $timerRange->getEnding();
                        }

                    }
                }
            }
        }
        return [$listForbiddenRanges, $lowerForbidden, $upperForbidden];
    }

    /**
     * @param $yamlForbiddenConfig
     * @param ListOfTimerService $timerList
     * @param $relativeToLowerActive
     * @return array
     * @throws TimerException
     */
    protected function generateForbiddenListWithprevRangesAndTimer(
        $forbiddenTimerList,
        ListOfTimerService $timerList,
        $relativeToUpperActive
    ): array {
        $listForbiddenRanges = [];
        $flagMinNeedSet = true;
        $lowerForbidden = null;
        $upperForbidden = null;
        foreach ($forbiddenTimerList as $singleForbiddenTimer) {
            if (
                (!isset($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR], $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS])) &&
                ($timerList->validate($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]))
            ) {
                // log only the missing of an allowed-timerdcefinition
                $this->logger->critical('The needed values `' . print_r($singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS],
                        true) .
                    '` for the active-timer `' . $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                    'Please check your definition in your active yaml-file. [1600865701]'
                );
            } else {
                $this->logWarningIfSelfCalling('forbidden-prev', $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $forbiddenTimerList, []
                );

                if ($timerList->isActive($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $relativeToUpperActive,
                    $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS])
                ) {
                    $nextHelp = $timerList->prevActive($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $relativeToUpperActive,
                        $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]);
                    $timerRange = $timerList->nextActive($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $nextHelp->getEnding(),
                        $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]);
                } else {
                    /** @var TimerStartStopRange $timerRange */
                    $timerRange = $timerList->prevActive($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $relativeToUpperActive,
                        $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]);
                }
                if ($timerRange->hasResultExist()) {
                    $listForbiddenRanges[] = [
                        self::YAML_LIST_ITEM_SELECTOR => $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                        self::YAML_LIST_ITEM_PARAMS => $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS],
                        self::YAML_LIST_ITEM_RANGE => $timerRange,
                    ];
                    if ($flagMinNeedSet) {
                        $lowerForbidden = $timerRange->getBeginning();
                        $upperForbidden = $timerRange->getEnding();
                        $flagMinNeedSet = false;
                    } else {
                        if (($upperForbidden < $timerRange->getEnding()) &&
                            ($timerRange->getBeginning() <= $lowerForbidden)) {
                            $upperForbidden = $timerRange->getEnding();
                        }
                        if (($timerRange->getEnding() < $upperForbidden) &&
                            ($upperForbidden <= $timerRange->getBeginning())
                        ) {
                            $lowerForbidden = $timerRange->getBeginning();
                        }
                        if (($timerRange->getBeginning() > $upperForbidden) &&
                            ($relativeToUpperActive < $timerRange->getBeginning())
                        ) {
                            // perhaps an is-active-Part
                            $lowerForbidden = $timerRange->getBeginning();
                            $upperForbidden = $timerRange->getEnding();
                        }

                    }
                }
            }
        }
        return [$listForbiddenRanges, $lowerForbidden, $upperForbidden];
    }

    /**
     * @param $listForbiddenRanges
     * @param $upperForbidden
     * @param $lowerForbidden
     * @param ListOfTimerService $timerList
     * @param array $listActiveRangesAndTimer
     * @return array
     */
    protected function generateNextMergedForbiddenRange(
        $listForbiddenRangesAndTimer,
        $upperForbidden,
        $lowerForbidden,
        ListOfTimerService $timerList
    ): array {
// detect for given Range of UpperActive an LowerActive the widest active Array
        $flagForbiddenChange = true;
        $changeLimit = self::MAX_TIME_LIMIT_FORBIDDEN_COUNT;
        while (($flagForbiddenChange) && ($changeLimit > 0)) {
            foreach ($listForbiddenRangesAndTimer as $key => $itemForbiddenRange) {
                if ($itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->hasResultExist()) {
                    if (($itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->getBeginning() <= $upperForbidden)) {
                        $flagForbiddenChange = true;
                        if ($itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->getEnding() > $upperForbidden) {
                            $upperForbidden = clone $itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->getEnding();
                        }
                        if ($itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->getBeginning() < $lowerForbidden) {
                            $lowerForbidden = clone $itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->getBeginning();
                        }
                        $listForbiddenRangesAndTimer[$key][self::YAML_LIST_ITEM_RANGE] =
                            $timerList->nextActive($itemForbiddenRange[self::YAML_LIST_ITEM_SELECTOR],
                                $upperForbidden,
                                $itemForbiddenRange[self::YAML_LIST_ITEM_PARAMS]
                            );
                    }
                }
            }
            $changeLimit--;
        }
        return [$flagForbiddenChange, $upperForbidden, $lowerForbidden];
    }


    /**
     * @param $listForbiddenRanges
     * @param $upperForbidden
     * @param $lowerForbidden
     * @param ListOfTimerService $timerList
     * @param array $listActiveRangesAndTimer
     * @return array
     */
    protected function generatePrevMergedForbiddenRange(
        $listForbiddenRangesAndTimer,
        $upperForbidden,
        $lowerForbidden,
        ListOfTimerService $timerList
    ): array {
// detect for given Range of UpperActive an LowerActive the widest active Array
        $flagForbiddenChange = true;
        $changeLimit = self::MAX_TIME_LIMIT_FORBIDDEN_COUNT;
        while (($flagForbiddenChange) && ($changeLimit > 0)) {
            $flagForbiddenChange = false;
            foreach ($listForbiddenRangesAndTimer as $key => $itemForbiddenRange) {
                if ($itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->hasResultExist()) {
                    if (($itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->getEnding() >= $lowerForbidden)) {
                        $flagForbiddenChange = true;
                        if ($itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->getEnding() > $upperForbidden) {
                            $upperForbidden = clone $itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->getEnding();
                        }
                        if ($itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->getBeginning() < $lowerForbidden) {
                            $lowerForbidden = clone $itemForbiddenRange[self::YAML_LIST_ITEM_RANGE]->getBeginning();
                        }
                        $listForbiddenRangesAndTimer[$key][self::YAML_LIST_ITEM_RANGE] =
                            $timerList->prevActive($itemForbiddenRange[self::YAML_LIST_ITEM_SELECTOR],
                                $lowerForbidden,
                                $itemForbiddenRange[self::YAML_LIST_ITEM_PARAMS]
                            );
                    }
                }
            }
            $changeLimit--;
        }
        return [$flagForbiddenChange, $upperForbidden, $lowerForbidden];
    }

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

    /**
     * @param $lowerForbidden
     * @param $upperForbidden
     * @param array $params
     */
    protected function logErrorForForbiddenRange(
        $lowerForbidden,
        $upperForbidden,
        array $params
    ): void {
        /** @var $logger Logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $this->logger->log(
            LogLevel::CRITICAL,
            'The fully forbidden nextrange for the timer `' . self::selfName() . '` with the current parameter ' .
            'seem not to end. It need more than ' . self::MAX_TIME_LIMIT_FORBIDDEN_COUNT .
            ' cycles to get a full range. A failed result is used instead of the estimated result.',
            [
                'Lower' => json_encode($lowerForbidden),
                'upper' => json_encode($upperForbidden),
                'params' => json_encode($params),
            ]
        );
    }

    /**
     * @param string $typeOfWarning
     * @param string $singleTimer
     * @param array $yamlOrAllTimerList
     * @param array $databaseTimerList
     */
    protected function logWarningIfSelfCalling(
        string $typeOfWarning,
        string $singleTimer,
        array $yamlOrAllTimerList,
        array $databaseTimerList
    ): void {
        if ($singleTimer === self::selfName()) {
            $this->logger->warning('Your current ' . $typeOfWarning . ' timer may cause an infinite loop. [1627276901]' .
                print_r($yamlOrAllTimerList, true), print_r($databaseTimerList, true)
            );

        }
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
            $this->lastIsActiveResult = new TimerStartStopRange();
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
            $this->lastIsActiveResult = new TimerStartStopRange();
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

    /**
     * @param array $activeTimerList
     * @param ListOfTimerService $timerList
     * @param DateTime $refRange
     * @return array
     * @throws TimerException
     */
    protected function isRefdateInActiveRange(
        array $activeTimerList,
        ListOfTimerService $timerList,
        DateTime $refRange
    ): array {
        $flagIsInActive = false;
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
                if ($timerList->isActive($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $refRange,
                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                )) {
                    $flagIsInActive = true;
                    $firstActiveCurrentRange = $timerList->getLastIsActiveRangeResult($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $refRange,
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    );

                    break;
                }
            }
        }
        return [$flagIsInActive, $firstActiveCurrentRange];
    }

    /**
     * @param DateTime $startTestDate
     * @param array $activeTimerList
     * @param array $forbiddenTimerList
     * @param ListOfTimerService $timerList
     * @param int $recursionCount
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function reduceActiveRangeByNearestForbiddenRange(
        DateTime $startTestDate,
        array $activeTimerList,
        array $forbiddenTimerList,
        ListOfTimerService $timerList,
        int $recursionCount
    ): TimerStartStopRange {
        $limiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        while ($limiter > 0) {
            $refDate = $startTestDate;
            $refDate->add(new DateInterval('PT1S'));
            $result = $this->getActiveRangeWithLowestBeginRefDate($activeTimerList,
                $timerList,
                $refDate
            );
            $forbiddenStart = clone $result->getBeginning();
            $forbiddenStart->sub(new DateInterval('PT1S'));
            $forbiddenRange = $this->getActiveRangeWithLowestBeginRefDate($forbiddenTimerList,
                $timerList,
                $forbiddenStart
            );
            if ($forbiddenRange->getBeginning() <= $result->getBeginning()) {
                // startpart is forbidden => detect the new startpart and then the endpart
                if ($forbiddenRange->getEnding() >= $result->getEnding()) {
                    $newRef = clone $forbiddenRange->getEnding();
                    $result = $this->getActivePartialRangeWithLowestBeginRefDate(
                        $activeTimerList,
                        $forbiddenTimerList,
                        $timerList,
                        $newRef,
                        ($recursionCount--)
                    );
                } else {
                    $result->setBeginning($forbiddenRange->getEnding());
                }
                break;
            } else {
                // startpart is active => detect the endpart
                if ($forbiddenRange->getBeginning() <= $result->getEnding()) {
                    $result->setEnding($forbiddenRange->getBeginning());
                }
                break;
            }
            $limiter--;
        }
        return $result;
    }

}