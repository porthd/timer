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
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Domain\Model\Listing;
use Porthd\Timer\Domain\Repository\ListingRepository;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Interfaces\ValidateYamlInterface;
use Porthd\Timer\Services\ListOfTimerService;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class RangeListTimer implements TimerInterface, LoggerAwareInterface, ValidateYamlInterface
{
    use GeneralTimerTrait;
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
     * @var ListingRepository
     */
    protected $listingRepository;

    /**
     * @var FrontendInterface|null
     */
    private $cache;

    /**
     * @var YamlFileLoader
     */
    private $yamlFileLoader;

    public function __construct()
    {
        $this->listingRepository = GeneralUtility::makeInstance(ListingRepository::class);
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $this->cache = $cacheManager->getCache(TimerConst::CACHE_IDENT_TIMER_YAMLLIST);
        $this->yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
    }


    public const TIMER_NAME = 'txTimerRangeList';
    protected const ARG_YAML_ACTIVE_FILE_PATH = 'yamlActiveFilePath';
    protected const ARG_YAML_FORBIDDEN_FILE_PATH = 'yamlForbiddenFilePath';

    protected const ARG_DATABASE_ACTIVE_RANGE_LIST = 'databaseActiveRangeList';
    protected const ARG_DATABASE_FORBIDDEN_RANGE_LIST = 'databaseForbiddenRangeList';
    protected const YAML_MAIN_LIST_KEY = 'rangelist';
    protected const YAML_LIST_ITEM_SELECTOR = 'selector';
    protected const YAML_LIST_ITEM_TITLE = 'title';
    protected const YAML_LIST_ITEM_DESCRIPTION = 'description';
    protected const YAML_LIST_ITEM_PARAMS = 'params';

    protected const MAX_TIME_LIMIT_MERGE_COUNT = 4; // count of loops to check for overlapping ranges

    protected const MARK_OF_EXT_FOLDER_IN_FILEPATH = 'EXT:';
    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/RangeListTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,
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
     * tested 20221114
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
        return ($params[self::ARG_ULTIMATE_RANGE_BEGINN] <= $dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME)) &&
            ($dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME) <= $params[self::ARG_ULTIMATE_RANGE_END]);
    }

    /**
     *
     * The method test, if the parameter in the yaml for the periodlist are okay
     * remark: This method must not be tested, if the sub-methods are valid.
     *
     * The method will implicitly called in `readRangeListFromYamlFile(array $params, $key [=self::ARG_YAML_FORBIDDEN_FILE_PATH, self::ARG_YAML_ACTIVE_FILE_PATH]): array`
     *
     * @param array $yamlArray
     * @param string $pathOfYamlFile
     * @throws TimerException
     */
    public function validateYamlOrException(array $yamlConfig, $pathOfYamlFile): void
    {
        if ((!isset($yamlArray[self::YAML_MAIN_LIST_KEY])) ||
            (!is_array($yamlArray[self::YAML_MAIN_LIST_KEY]))
        ) {
            throw new TimerException(
                'The yaml-file has not the correct syntax. It must contain the attribute ' .
                self::YAML_MAIN_LIST_KEY . ' at the starting level. Other attributes will be ignored at the starting-level. ' .
                'Check the structure of your YAML-file `' . $pathOfYamlFile . '` for your `periodListTimer`.',
                1668234195
            );
        }

        $flag = true;
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        foreach ($yamlArray[self::YAML_MAIN_LIST_KEY] as $item) {
            // required fields
            $flag = $flag && isset($item[self::YAML_LIST_ITEM_SELECTOR]) &&
                $timerList->validateSelector($item[self::YAML_LIST_ITEM_SELECTOR]);
            if (!$flag) {
                throw new TimerException(
                    'The selector for the timer`' . $item[self::YAML_LIST_ITEM_SELECTOR] ?? 'NULL' . '` is not defined or instantiated.' .
                    'Check the timer-definitions in your YAML-file `' . $pathOfYamlFile . '`.',
                    1668247251
                );
            };
            $flag = $flag && ((!isset($item[self::YAML_LIST_ITEM_TITLE])) ||
                    (!empty($item[self::YAML_LIST_ITEM_TITLE])));
            $flag = $flag && ((!isset($item[self::YAML_LIST_ITEM_DESCRIPTION])) ||
                    (!empty($item[self::YAML_LIST_ITEM_DESCRIPTION])));
            $flag = $flag && (!is_array($item[self::YAML_LIST_ITEM_PARAMS]));
            if (!$flag) {
                throw new TimerException(
                    'The optional attributes `' . self::YAML_LIST_ITEM_TITLE . '`, `' . self::YAML_LIST_ITEM_DESCRIPTION .
                    '` and `' . self::YAML_LIST_ITEM_PARAMS . '`  should be missing or should not be empty in your yaml-file `' .
                    $pathOfYamlFile . '`. ' . 'Check the items in your YAML-file espacially the following one: ' .
                    print_r($item, true),
                    1668247500
                );
            };
            $flag = $flag && $timerList->validate($item[self::YAML_LIST_ITEM_SELECTOR],
                    $item[self::YAML_LIST_ITEM_PARAMS]
                );
            if (!$flag) {
                throw new TimerException(
                    'The parameter block is not valid for the timer `' . $item[self::YAML_LIST_ITEM_SELECTOR] . '`.' .
                    'Check the items in your YAML-file `' . $pathOfYamlFile . '` espacially the following one: ' .
                    print_r($item, true),
                    1668247500
                );
            };
        }
    }

    /**
     * tested general 20221115
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
        $flag = $flag && $this->validateFlagZone($params);
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
            $result = new TimerStartStopRange();
            $result->failAllActive($dateLikeEventZone);
            $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
            return $result->getResultExist();
        }

        $flag = false;

        $yamlActiveConfig = $this->readRangeListFromYamlFile($this->yamlFileLoader, $params, self::ARG_YAML_ACTIVE_FILE_PATH);
        $databaseActiveConfig = $this->readRangeListFromDatabase($params, self::ARG_DATABASE_ACTIVE_RANGE_LIST);
        $activeTimer = array_merge($yamlActiveConfig, $databaseActiveConfig);
        if (empty($activeTimer)) {
            $this->logger->warning('Your path for the file ' . $yamlActiveConfig[self::YAML_MAIN_LIST_KEY]
                . 'in `' . self::ARG_YAML_ACTIVE_FILE_PATH . '` is missing or empty and '
                . 'the entries for [' . $params[self::ARG_DATABASE_ACTIVE_RANGE_LIST] . '] in `' . self::ARG_DATABASE_ACTIVE_RANGE_LIST . '` are empty too. Please check '
                . 'your configuration. [1600865701].'
            );
            return $flag;
        }
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);

        foreach ($activeTimer as $singleActiveTimer) {
            if (
                (!isset($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR], $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS])) &&
                ($timerList->validate($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]))
            ) {
                // log only the missing of an allowed definition of timer
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
                    $this->expandRangeAroundActive($activeRange, $activeTimer, $timerList);
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
            $yamlForbiddenConfig = $this->readRangeListFromYamlFile($this->yamlFileLoader, $params, self::ARG_YAML_FORBIDDEN_FILE_PATH);
            $databaseForbiddenConfig = $this->readRangeListFromDatabase($params,
                self::ARG_DATABASE_FORBIDDEN_RANGE_LIST);
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
                            $flag = false;
                            $activeRange->failAllActive($dateLikeEventZone);
                            $this->setIsActiveResult($activeRange->getBeginning(),
                                $activeRange->getEnding(),
                                $flag,
                                $dateLikeEventZone,
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
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        $yamlFileActiveTimerList = $this->readRangeListFromYamlFile($this->yamlFileLoader, $params, self::ARG_YAML_ACTIVE_FILE_PATH);

        $databaseActiveTimerList = $this->readRangeListFromDatabase($params, self::ARG_DATABASE_ACTIVE_RANGE_LIST);

        $activeTimerList = array_merge($yamlFileActiveTimerList, $databaseActiveTimerList);
        $refDateTime = clone $dateLikeEventZone;
        $yamlForbiddenTimerList = $this->readRangeListFromYamlFile($this->yamlFileLoader, $params, self::ARG_YAML_FORBIDDEN_FILE_PATH);
        $databaseForbiddenTimerList = $this->readRangeListFromDatabase($params,
            self::ARG_DATABASE_FORBIDDEN_RANGE_LIST);

        $forbiddenTimerList = array_merge($yamlForbiddenTimerList, $databaseForbiddenTimerList);
        // Range-calculate-algorithm
        if (!empty($forbiddenTimerList)) {
            // merge active ranges defined by timers together and reduce the by merged forbidden ranges defined by timers
            $result = $this->getActivePartialRangeWithLowestBeginRefDate(
                $activeTimerList,
                $forbiddenTimerList,
                $refDateTime,
                $timerList
            );
        } else {
            // merge only list of active timer together
            $result = $this->getActiveRangeWithLowestBeginRefDate(
                $activeTimerList,
                $refDateTime
            );
        }
        if ((!$this->isAllowedInRange($result->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($result->getEnding(), $params))
        ) {
            $result->failOnlyNextActive($dateLikeEventZone);
        }

        return $this->validateUltimateRangeForNextRange($result, $params, $dateLikeEventZone);
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
        $yamlFileActiveTimerList = $this->readRangeListFromYamlFile($this->yamlFileLoader, $params, self::ARG_YAML_ACTIVE_FILE_PATH);
        $databaseActiveTimerList = $this->readRangeListFromDatabase($params, self::ARG_DATABASE_ACTIVE_RANGE_LIST);

        $activeTimerList = array_merge($yamlFileActiveTimerList, $databaseActiveTimerList);
        // Generate List of next Ranges Detect Lower-Part
        // Generate find nearest Nextrange with biggest range
        // Generate List of forbidden and detect
        //
        $refDateTime = clone $dateLikeEventZone;
        while ($loopLimiter > 0) {
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
                $activeTimerList[self::YAML_MAIN_LIST_KEY],
                $refDateTime
            );
            $yamlForbiddenTimerList = $this->readRangeListFromYamlFile($this->yamlFileLoader, $params, self::ARG_YAML_FORBIDDEN_FILE_PATH);
            $databaseForbiddenTimerList = $this->readRangeListFromDatabase($params,
                self::ARG_DATABASE_FORBIDDEN_RANGE_LIST);

            $forbiddenTimerList = array_merge($yamlForbiddenTimerList, $databaseForbiddenTimerList);
            if (!empty($forbiddenTimerList[self::YAML_MAIN_LIST_KEY])) {
                [$result, $changed] = $this->reduceActiveRangeByForbiddenRangesWithHighestEnd(
                    $forbiddenTimerList[self::YAML_MAIN_LIST_KEY],
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

        return $this->validateUltimateRangeForPrevRange($result, $params, $dateLikeEventZone);
    }


    /**
     * @param array $activeTimerList
     * @param DateTime $dateLikeEventZone
     * @return array // [TimerStartStopRange, bool]
     */
    protected function reduceActiveRangeByForbiddenRangesWithHighestEnd(
        array $forbiddenTimerList,
        TimerStartStopRange $currentActiveRange
    ) {
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
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
                        $startRange,
                        $stopRange,
                        $timerList
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
     * @param DateTime $dateLikeEventZone
     * @return TimerStartStopRange
     */
    protected function getActiveRangeWithLowestBeginRefDate(
        array $activeTimerList,
        DateTime $dateLikeEventZone
    ): TimerStartStopRange {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        $flagFirstResult = true;
        $flagChange = false;
        $refRange = clone $dateLikeEventZone;
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);

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
     * @param DateTime $dateLikeEventZone
     * @return TimerStartStopRange
     */
    protected function getActiveRangeWithIncludeStartingRange(
        array $activeTimerList,
        TimerStartStopRange $dateRage
    ): TimerStartStopRange {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        $result = null;
        $flagFirstResult = true;
        $flagChange = false;
        $refRange = clone $dateRage;
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
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
     * @param DateTime $dateLikeEventZone
     * @param int $recursionCount
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function getActivePartialRangeWithLowestBeginRefDate(
        array $activeTimerList,
        array $forbiddenTimerList,
        DateTime $dateLikeEventZone,
        ListOfTimerService $timerList,
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
            $refDate,
            $timerList
        );

        if ($flagIsInActive) {
            [$flagIsInForbidden, $firstForbiddenRange] = $this->isRefdateInActiveRange(
                $forbiddenTimerList,
                $refDate,
                $timerList
            );
            if (!$flagIsInForbidden) {
                $firstForbiddenRange = $this->getActiveRangeWithLowestBeginRefDate($forbiddenTimerList,
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
     * @param DateTime $dateLikeEventZone
     * @return TimerStartStopRange
     */
    protected function getActiveRangeWithHighestEndRefDate(
        array $activeTimerList,
        DateTime $dateLikeEventZone
    ): TimerStartStopRange {
        $loopLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        $result = null;
        $flagFirstResult = true;
        $flagChange = false;
        $refRange = clone $dateLikeEventZone;
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);

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
     * @param DateTime $startRange
     * @param DateTime $endRange
     * @param array $params
     * @param bool $highFirst
     * @return array // [TimerStartStopRange, bool]
     */
    protected function getInRangeNearHigherLimit(
        array $singleActiveTimer,
        DateTime $startRange,
        DateTime $endRange,
        ListOfTimerService $timerList
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
     * @param YamlFileLoader $yamlFileLoader
     * @param array $params
     * @param string $key
     * @return array
     * @throws TimerException
     */
    protected function readRangeListFromYamlFile(
        YamlFileLoader $yamlFileLoader,
        array $params,
        string $key
    ): array
    {
        //        $yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);

        if (empty($params[$key])) {
            return [];
        }
        // $this must the method `validateYamlOrException`
        $result = CustomTimerUtility::readListFromYamlFile(
            $params[$key],
            $yamlFileLoader,
            $this,
            $this->cache
        );
        if (!is_array($result[self::YAML_MAIN_LIST_KEY])) {
            throw new TimerException(
                'The key `' . self::YAML_MAIN_LIST_KEY . '` does not exist in your active yaml-file (`' .
                $params[$key] . '`). ' .
                'Please check your configuration.',
                1600865701
            );
        }
        return $result[self::YAML_MAIN_LIST_KEY];
    }

    /**
     * @param array $params
     * @return array
     * @throws TimerException
     */
    protected function readRangeListFromDatabase(array $params, $key = self::ARG_DATABASE_ACTIVE_RANGE_LIST): array
    {

        if (!isset($params[$key])) {
            return [];
        }
        return $this->readListFromDatabase($params[$key]);
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
        $rawResult = $this->listingRepository->findByCommaList($commaListOfUids);
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
                        $rawFlexformString = $item[TimerConst::TIMER_FIELD_FLEX_ACTIVE];
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
            'seem not to end. It need more than ' . self::MAX_TIME_LIMIT_MERGE_COUNT .
            ' cycles to get a full range. A failed result is used instead of the estimated result.',
            [
                'Lower' => json_encode($lowerActive),
                'upper' => json_encode($upperActive),
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
                print_r($yamlOrAllTimerList, true) . print_r($databaseTimerList, true)
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
     * @param DateTime $refRange
     * @return array
     * @throws TimerException
     */
    protected function isRefdateInActiveRange(
        array $activeTimerList,
        DateTime $refRange,
        ListOfTimerService $timerList
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
                $refDate
            );
            $forbiddenStart = clone $result->getBeginning();
            $forbiddenStart->sub(new DateInterval('PT1S'));
            $forbiddenRange = $this->getActiveRangeWithLowestBeginRefDate($forbiddenTimerList,
                $forbiddenStart
            );
            if ($forbiddenRange->getBeginning() <= $result->getBeginning()) {
                // startpart is forbidden => detect the new startpart and then the endpart
                if ($forbiddenRange->getEnding() >= $result->getEnding()) {
                    $newRef = clone $forbiddenRange->getEnding();
                    $result = $this->getActivePartialRangeWithLowestBeginRefDate(
                        $activeTimerList,
                        $forbiddenTimerList,
                        $newRef,
                        $timerList,
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

    /**
     * @param TimerStartStopRange $activeRange
     * @param array $activeTimer
     * @param ListOfTimerService $timerList
     * @throws TimerException
     */
    protected function expandRangeAroundActive(
        TimerStartStopRange $activeRange,
        array $activeTimer,
        ListOfTimerService $timerList
    ): void {
        $limiter = self::MAX_TIME_LIMIT_MERGE_COUNT;
        while ($limiter > 0) {
            $flagChange = false;
            $checkBeginn = $activeRange->getBeginning();
            $checkEnd = $activeRange->getEnding();
            foreach ($activeTimer as $checkActiveTimer) {
                if ($timerList->isActive($checkActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $checkBeginn,
                    $checkActiveTimer[self::YAML_LIST_ITEM_PARAMS])
                ) {
                    $checkRange = $timerList->getLastIsActiveRangeResult(
                        $checkActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $checkBeginn,
                        $checkActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    );
                    $checkBeginn = $checkRange->getBeginning();
                    $activeRange->setBeginning($checkBeginn);
                    $flagChange = true;
                }
                if ($timerList->isActive($checkActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $checkEnd,
                    $checkActiveTimer[self::YAML_LIST_ITEM_PARAMS])
                ) {
                    $checkRange = $timerList->getLastIsActiveRangeResult(
                        $checkActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $checkEnd,
                        $checkActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    );
                    $checkEnd = $checkRange->getBeginning();
                    $activeRange->setBeginning($checkEnd);
                    $flagChange = true;
                }
            }
            if (!$flagChange) {
                break;
            }
            $limiter--;
        }
    }

}