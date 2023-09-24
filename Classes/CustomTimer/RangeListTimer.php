<?php

declare(strict_types=1);

namespace Porthd\Timer\CustomTimer;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2022 Dr. Dieter Porth <info@mobger.de>
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
use Porthd\Timer\CustomTimer\GeneralTimerTrait;
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
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
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
     * @var int
     */
    protected $loopRecursiveLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT;

    /**
     * @var array<mixed>
     */
    protected $lastIsActiveParams = [];


    /**
     * @var ListingRepository
     */
    protected $listingRepository;

    /**
     * @var YamlFileLoader
     */
    private $yamlFileLoader;

    public function __construct()
    {
        $this->listingRepository = GeneralUtility::makeInstance(ListingRepository::class);
        $this->yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
    }


    public const TIMER_NAME = 'txTimerRangeList';
    protected const ARG_YAML_RECURSIVE_LOOP_LIMIT = 'recursiveLoopLimit';
    protected const ARG_YAML_ACTIVE_FILE_PATH = 'yamlActiveFilePath';
    protected const ARG_YAML_FORBIDDEN_FILE_PATH = 'yamlForbiddenFilePath';

    protected const ARG_DATABASE_ACTIVE_RANGE_LIST = 'databaseActiveRangeList';
    protected const ARG_DATABASE_FORBIDDEN_RANGE_LIST = 'databaseForbiddenRangeList';
    protected const YAML_MAIN_LIST_KEY = 'rangelist';
    protected const YAML_LIST_ITEM_SELECTOR = 'selector';
    protected const YAML_LIST_ITEM_TITLE = 'title';
    protected const YAML_LIST_ITEM_DESCRIPTION = 'description';
    protected const YAML_LIST_ITEM_PARAMS = 'params';

    protected const MAX_TIME_LIMIT_MERGE_COUNT = 10; // count of loops to check for overlapping ranges

    protected const MARK_OF_EXT_FOLDER_IN_FILEPATH = 'EXT:';
    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    protected const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/RangeListTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_YAML_RECURSIVE_LOOP_LIMIT,
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
     * @return array<mixed>
     */
    public static function getSelectorItem(): array
    {
        return [
            TimerConst::TCA_ITEMS_LABEL => 'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerRangeList.select.name',
            TimerConst::TCA_ITEMS_VALUE => self::TIMER_NAME,
        ];
    }

    /**
     * tested
     *
     * @param string $activeZoneName
     * @param array<mixed> $params
     * @return string
     */
    public function getTimeZoneOfEvent($activeZoneName, array $params = []): string
    {
        return GeneralTimerUtility::getTimeZoneOfEvent($activeZoneName, $params);
    }

    /**
     * tested 20210116
     *
     * @return array<mixed>
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
    }

    /**
     * tested 20201226
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return bool
     */
    public function isAllowedInRange(DateTime $dateLikeEventZone, $params = []): bool
    {
        // use of the trait-function
        return $this->generalIsAllowedInRange($dateLikeEventZone, $params);
    }

    /**
     *
     * The method test, if the parameter in the yaml for the periodlist are okay
     * remark: This method must not be tested, if the sub-methods are valid.
     *
     * The method will implicitly called in `readRangeListFromFileOrUrl(array $params, $key [=self::ARG_YAML_FORBIDDEN_FILE_PATH, self::ARG_YAML_ACTIVE_FILE_PATH]): array`
     *
     * @param array<mixed> $yamlConfig
     * @param string $pathOfYamlFile
     * @throws TimerException
     */
    public function validateYamlOrException(
        array $yamlConfig,
        string $pathOfYamlFile = ''
    ): void {
        if ((!array_key_exists(self::YAML_MAIN_LIST_KEY, $yamlConfig)) ||
            (!is_array($yamlConfig[self::YAML_MAIN_LIST_KEY]))
        ) {
            throw new TimerException(
                'The yaml-file has not the correct syntax. It must contain the attribute `' .
                self::YAML_MAIN_LIST_KEY . '` at the starting level. Other attributes will be ignored at the starting-level. ' .
                'Check the structure of your YAML-file `' . $pathOfYamlFile . '` for your `periodListTimer`.',
                1668234195
            );
        }

        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        foreach ($yamlConfig[self::YAML_MAIN_LIST_KEY] as $item) {
            // required fields
            $flag = (
                array_key_exists(self::YAML_LIST_ITEM_SELECTOR, $item) &&
                $timerList->validateSelector($item[self::YAML_LIST_ITEM_SELECTOR])
            );
            if (!$flag) {
                throw new TimerException(
                    'The selector for the timer`' . $item[self::YAML_LIST_ITEM_SELECTOR] .
                    '` is not defined or instantiated. ' .
                    'Check the timer-definitions in your YAML-file `' . $pathOfYamlFile . '`.',
                    1668247251
                );
            };
            $flag = ((!array_key_exists(self::YAML_LIST_ITEM_TITLE, $item)) ||
                (!empty($item[self::YAML_LIST_ITEM_TITLE])));
            $flag = $flag && ((!array_key_exists(self::YAML_LIST_ITEM_DESCRIPTION, $item)) ||
                    (!empty($item[self::YAML_LIST_ITEM_DESCRIPTION])));
            $flag = $flag && (
                    (!array_key_exists(self::YAML_LIST_ITEM_DESCRIPTION, $item)) ||
                    (
                        (is_array($item[self::YAML_LIST_ITEM_PARAMS])) &&
                        (!empty($item[self::YAML_LIST_ITEM_PARAMS]))
                    )
                );
            if (!$flag) {
                throw new TimerException(
                    'The optional attributes `' . self::YAML_LIST_ITEM_TITLE . '`, `' . self::YAML_LIST_ITEM_DESCRIPTION .
                    '` and `' . self::YAML_LIST_ITEM_PARAMS . '`  should be missing or should not be empty in your yaml-file `' .
                    $pathOfYamlFile . '`. ' . 'Check the items in your YAML-file espacially the following one: ' .
                    print_r($item, true),
                    1668247500
                );
            };
            $flag = $timerList->validate(
                $item[self::YAML_LIST_ITEM_SELECTOR],
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
     * @param array<mixed> $params
     * @return bool
     */
    public function validate(array $params = []): bool
    {
        $flag = $this->validateZone($params);
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
     * @param array<mixed> $params
     * @return int
     */
    public function validateCountArguments(array $params = []): int
    {
        return $this->countParamsInList(self::ARG_REQ_LIST, $params);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return int
     */
    protected function validateOptional(array $params = []): int
    {
        return $this->countParamsInList(self::ARG_OPT_LIST, $params);
    }


    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateYamlFilePath(array $params = []): bool
    {
        // Check for activePath or the definitio is missing
        $flag = $this->validateFilePath(self::ARG_YAML_ACTIVE_FILE_PATH, $params);
        // disallow the missing
        $flag = $flag && (!empty($params[self::ARG_YAML_ACTIVE_FILE_PATH]));
        // !!! allow the FAL-defintion as an substitute
        $flag = $flag || (!empty($params[self::ARG_DATABASE_ACTIVE_RANGE_LIST]));

        // check for optional existing forbiddenpath
        $flag = $flag && $this->validateFilePath(self::ARG_YAML_FORBIDDEN_FILE_PATH, $params);

        return $flag;
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateDatabaseRangeList(array $params = []): bool
    {
        $flag = true;
        foreach ([self::ARG_DATABASE_ACTIVE_RANGE_LIST, self::ARG_DATABASE_FORBIDDEN_RANGE_LIST] as $paramKey) {
            $commaList = (
            array_key_exists($paramKey, $params) ?
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
     * @param array<mixed> $params
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


        $yamlActiveConfig = $this->readRangeListFromFileOrUrl(
            $this->yamlFileLoader,
            $params,
            self::ARG_YAML_ACTIVE_FILE_PATH
        );
        $databaseActiveConfig = $this->readRangeListFromDatabase($params, self::ARG_DATABASE_ACTIVE_RANGE_LIST);
        $activeTimer = array_merge($yamlActiveConfig, $databaseActiveConfig);
        if (empty($activeTimer)) {
            $this->logger->warning(
                'Your path for the file ' . $yamlActiveConfig[self::YAML_MAIN_LIST_KEY]
                . 'in `' . self::ARG_YAML_ACTIVE_FILE_PATH . '` is missing or empty and '
                . 'the entries for [' . $params[self::ARG_DATABASE_ACTIVE_RANGE_LIST] . '] in `' . self::ARG_DATABASE_ACTIVE_RANGE_LIST . '` are empty too. Please check '
                . 'your configuration. [1600865701].'
            );
            return false;
        }
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);

        [$activeRange, $flag] = $this->detectActiveRangeAndShowIncludeFlag(
            $activeTimer,
            $timerList,
            $dateLikeEventZone
        );
        if ($flag) {
            // test the restriction for active-cases
            $yamlForbiddenConfig = $this->readRangeListFromFileOrUrl(
                $this->yamlFileLoader,
                $params,
                self::ARG_YAML_FORBIDDEN_FILE_PATH
            );
            $databaseForbiddenConfig = $this->readRangeListFromDatabase(
                $params,
                self::ARG_DATABASE_FORBIDDEN_RANGE_LIST
            );
            $forbiddenTimer = array_merge($yamlForbiddenConfig, $databaseForbiddenConfig);
            if (!empty($forbiddenTimer)) {
                foreach ($forbiddenTimer as $singleForbiddenTimer) {
                    $flagParamFailure = $this->isParamFailure($singleForbiddenTimer, $timerList);
                    if ($flagParamFailure) {
                        // log only the missing of an allowed-timerdcefinition
                        $this->logger->critical(
                            'The needed parameter `' . print_r(
                                $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS],
                                true
                            ) .
                            '` for the forbidden-timer `' . $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be missdefined or missing. ' .
                            'Please check your definition in your forbidden yaml-file. [1600874901]'
                        );
                    } else {
                        if ($timerList->isActive(
                            $singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR],
                            $dateLikeEventZone,
                            $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]
                        )
                        ) {
                            $flag = false;
                            $activeRange->failAllActive($dateLikeEventZone);
                            $this->setIsActiveResult(
                                $activeRange->getBeginning(),
                                $activeRange->getEnding(),
                                $flag,
                                $dateLikeEventZone,
                                $singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS]
                            );
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
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult(DateTime $dateLikeEventZone, array $params = []): TimerStartStopRange
    {
        return $this->getLastIsActiveResult($dateLikeEventZone, $params);
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested 20221225
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $this->loopRecursiveLimiter = (int)(
        (array_key_exists(self::ARG_YAML_RECURSIVE_LOOP_LIMIT, $params)) ?
            $params[self::ARG_YAML_RECURSIVE_LOOP_LIMIT] :
            self::MAX_TIME_LIMIT_MERGE_COUNT
        );
        return $this->validateUltimateRangeForNextRange(
            $this->nextActiveRecursive(
                $dateLikeEventZone,
                $params,
                $this->loopRecursiveLimiter,
                true
            ),
            $params,
            $dateLikeEventZone
        );
    }

    /**
     * find the next range of a active timegap depending on the defined list
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @param int $recursiveLimiter
     * @return TimerStartStopRange
     */
    protected function nextActiveRecursive(
        DateTime $dateLikeEventZone,
        array $params = [],
        int $recursiveLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT,
        bool $flagInitial = true
    ): TimerStartStopRange {
        if ($recursiveLimiter <= 0) {
            $result = new TimerStartStopRange();
            $result->failAllActive($dateLikeEventZone);
            return $result;
        }
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        [$activeTimerList, $forbiddenTimerList] = $this->detectActiveAndForbiddenList($params);
        if (empty($activeTimerList)) {
            throw new TimerException(
                'The list for the active range must be filled with at least one timer-definition. ' .
                'There is found anything. Please check, if your yaml-file and/or your definitons in the database are available.' .
                'If everything seems fine, then inform the webmaster.',
                1612365701
            );
        }

        if (!empty($forbiddenTimerList)) {
            return $this->nextActiveRecursiveWithForbidden(
                $dateLikeEventZone,
                $params,
                $timerList,
                $activeTimerList,
                $forbiddenTimerList,
                $recursiveLimiter,
                $flagInitial
            );
        }
        return $this->nextActiveRecursiveOnlyActive(
            $dateLikeEventZone,
            $params,
            $timerList,
            $activeTimerList,
            $recursiveLimiter,
            $flagInitial
        );
    }

    /**
     * find the next range of a active timegap depending on the defined list
     *
     *
     * @param DateTime $dateLikeEventZone
     * @param ListOfTimerService $timerList
     * @param array<mixed> $activeTimerList
     * @param array<mixed> $forbiddenTimerList
     * @param int $recursiveLimiter
     * @return TimerStartStopRange
     * @throws TimerException
     */
    /**
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @param ListOfTimerService $timerList
     * @param array<mixed> $activeTimerList
     * @param array<mixed> $forbiddenTimerList
     * @param int $recursiveLimiter
     * @param bool $flagInitial
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function nextActiveRecursiveWithForbidden(
        DateTime $dateLikeEventZone,
        array $params,
        ListOfTimerService $timerList,
        array $activeTimerList,
        array $forbiddenTimerList,
        int $recursiveLimiter,
        bool $flagInitial = true
    ): TimerStartStopRange {
        // is testtime in forbidden range?
        [$result, $flagDateInForbiddenRange] = $this->detectActiveRangeAndShowIncludeFlag(
            $forbiddenTimerList,
            $timerList,
            $dateLikeEventZone
        );
        if ($flagDateInForbiddenRange) {
            $forbiddenRange = $this->expandRangeAtEnding($result, $forbiddenTimerList, $timerList);
            /** @var DateTime $newStart */
            $newStart = clone $forbiddenRange->getEnding();
            $newStart->add(new DateInterval('PT1S'));
            // is testtime in forbidden range?
            [$resultAfterForbidden, $flagDateInActiveRange] = $this->detectActiveRangeAndShowIncludeFlag(
                $activeTimerList,
                $timerList,
                $newStart
            );
            if ($flagDateInActiveRange) {
                // expand and reduce the current range at the end
                // there should at least a rest active range because of the conditions before
                $result = $this->expandRangeAtEnding($resultAfterForbidden, $activeTimerList, $timerList);
                if ($result->getBeginning() > $forbiddenRange->getEnding()) {
                    $result->setBeginning($forbiddenRange->getEnding());
                }
                // fix the ending
                $secondPrevForbidden = $this->detectNearestRange(
                    $forbiddenTimerList,
                    $timerList,
                    $forbiddenRange->getBeginning()
                );
                if (($secondPrevForbidden->getBeginning() < $result->getEnding()) &&
                    ($secondPrevForbidden->getBeginning() > $result->getBeginning())
                ) {
                    $result->setEnding($secondPrevForbidden->getBeginning());
                }

                $this->exceptionIfBeginningGreaterEqualThanEnding(
                    $result,
                    $activeTimerList,
                    $timerList,
                    $forbiddenTimerList,
                    'next'
                );
                return $result;
            }
            return $this->nextActiveRecursive(
                $newStart,
                $params,
                (--$recursiveLimiter),
                false
            );
        } // else => date is not part of forbidden range

        // the  testtime is not in forbidden range?
        /** @var TimerStartStopRange $resultActive */
        [$resultActive, $flagDateInActiveRange] = $this->detectActiveRangeAndShowIncludeFlag(
            $activeTimerList,
            $timerList,
            $dateLikeEventZone
        );
        if ($flagDateInActiveRange) {
            // expand and reduce the current range at the end
            // there should at least an rest active range because of the conditions before
            // add one second to the ending and call the netactive recursively once more
            $rangeActiveWithoutForbidden = $this->expandRangeAtEnding($resultActive, $activeTimerList, $timerList);
            $forbiddenRangeRaw = $this->detectNearestRange($forbiddenTimerList, $timerList, $dateLikeEventZone);
            if (!$flagInitial) {
                if ($forbiddenRangeRaw->getBeginning() < $rangeActiveWithoutForbidden->getEnding()) {
                    $rangeActiveWithoutForbidden->setEnding($forbiddenRangeRaw->getBeginning());
                }
                return $rangeActiveWithoutForbidden;
            }
            $forbiddenRange = $this->expandRangeAtEnding($forbiddenRangeRaw, $forbiddenTimerList, $timerList);
            if ($forbiddenRange->getEnding() < $rangeActiveWithoutForbidden->getEnding()) {
                $rangeActiveWithoutForbidden->setBeginning($forbiddenRange->getEnding());
                // the resulting active range may intercepted by a second forbidden range
                $rawForbiddenSecond = $this->detectNearestRange(
                    $forbiddenTimerList,
                    $timerList,
                    $rangeActiveWithoutForbidden->getBeginning()
                );
                if ($rawForbiddenSecond->getBeginning() < $rangeActiveWithoutForbidden->getEnding()) {
                    $rangeActiveWithoutForbidden->setEnding($rawForbiddenSecond->getBeginning());
                }
                return $rangeActiveWithoutForbidden;
            }
            //get the best starting value for the next calulation of prevActive
            if ($forbiddenRange->getBeginning() < $rangeActiveWithoutForbidden->getEnding()) {
                $newStartRange = $forbiddenRange->getBeginning();
            } else {
                $newStartRange = $rangeActiveWithoutForbidden->getEnding();
            }
            $newStartRange->add(new DateInterval('PT1S'));
            return $this->nextActiveRecursive(
                $newStartRange,
                $params,
                (--$recursiveLimiter),
                false
            );
        }
        //bestimme einen nächsten kleinsten aktiven Bereich
        //erweitere aktiven Bereich, solange Überlappen möglich ist (bis keine weitere Überlappung gefunden wird, Bis Looplimit erreicht wurde oder bis Obergrenze erreicht wird.)
        $resultActiveRaw = $this->detectNearestRange($activeTimerList, $timerList, $dateLikeEventZone);
        $resultActive = $this->expandRangeAtEnding($resultActiveRaw, $activeTimerList, $timerList);
        // Bestimme, ob Beginn im Forbidden bereich ist
        /** @var TimerStartStopRange $resultActive */
        [$resultIgnore, $flagDateInForbiddenRange] = $this->detectActiveRangeAndShowIncludeFlag(
            $forbiddenTimerList,
            $timerList,
            $resultActive->getBeginning()
        );
        if ($flagDateInForbiddenRange) {
            // Wenn Beginn im Forbidden-Range ist, dannn
            $rangeForbidden = $this->expandRangeAtEnding($resultIgnore, $forbiddenTimerList, $timerList);
            // ... prüfe, ob der Forbidden-Bereich den aktiven Bereich vollständig übersteigt
            if (($rangeForbidden->hasResultExist()) &&
                ($rangeForbidden->getEnding() > $resultActive->getEnding())
            ) {
                // wenn ja, dann suche rekursive ab dem Ende vom Forbiddenbereich
                $newStartDate = $rangeForbidden->getEnding();
                $newStartDate->add(new DateInterval('PT1S'));
                return $this->nextActiveRecursive(
                    $newStartDate,
                    $params,
                    (--$recursiveLimiter),
                    false
                );
            }
            /// wenn nein, dann passe den Beginn des aktiven Bereichs durch das Ende des Forbiddenbereichs an ...
            if ($rangeForbidden->hasResultExist()) {
                if (($rangeForbidden->getEnding() > $resultActive->getBeginning()) &&
                    ($rangeForbidden->getEnding() < $resultActive->getEnding())
                ) {
                    $resultActive->setBeginning($rangeForbidden->getEnding());
                }
            }
        }
        // ...und prüfe, ob der aktuelle aktove Bereich an seinem Ende durch einen forbiddenbereich noch zu beschneiden ist
        $forbiddenNextRange = $this->detectNearestRange(
            $forbiddenTimerList,
            $timerList,
            $resultActive->getBeginning()
        );
        if ($forbiddenNextRange->getBeginning() < $resultActive->getEnding()) {
            $resultActive->setEnding($forbiddenNextRange->getBeginning());
        }
        if (($resultActive->getBeginning() < $resultActive->getEnding()) &&
            ($resultActive->hasResultExist())
        ) {
            return $resultActive;
        }
        // Dieser Fall sollte nicht eintreten
        throw new TimerException(
            'This execption should newer happen. Please make a screenshot and inform the webmaster. ' .
            'He needs the following informations to fix this bug:  a list of your timer used for detectikon of ' .
            ' active ranges and a list with all timers for detection of your forbidden ranges. ' .
            'Please check your configuration.',
            16006533911
        );
    }

    /**
     * find the next range of a active timegap depending on the defined list
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @param ListOfTimerService $timerList
     * @param array<mixed> $activeTimerList
     * @param array<mixed> $forbiddenTimerList
     * @param int $recursiveLimiter
     * @param bool $flagIntial
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function prevActiveRecursiveWithForbidden(
        DateTime $dateLikeEventZone,
        array $params,
        ListOfTimerService $timerList,
        array $activeTimerList,
        array $forbiddenTimerList,
        int $recursiveLimiter,
        bool $flagIntial = true
    ): TimerStartStopRange {
        // is testtime in forbidden range?
        [$result, $flagDateInForbiddenRange] = $this->detectActiveRangeAndShowIncludeFlag(
            $forbiddenTimerList,
            $timerList,
            $dateLikeEventZone
        );
        if ($flagDateInForbiddenRange) {
            $forbiddenRange = $this->expandRangeAtBeginning($result, $forbiddenTimerList, $timerList);
            /** @var DateTime $newStart */
            $newStart = clone $forbiddenRange->getBeginning();
            $newStart->sub(new DateInterval('PT1S'));
            // is testtime in forbidden range?
            [$resultBeforeForbidden, $flagDateInActiveRange] = $this->detectActiveRangeAndShowIncludeFlag(
                $activeTimerList,
                $timerList,
                $newStart
            );
            if ($flagDateInActiveRange) {
                // expand and reduce the current range at the end
                // there should at least a rest active range because of the conditions before
                $result = $this->expandRangeAtBeginning($resultBeforeForbidden, $activeTimerList, $timerList);
                // restorate the end
                if ($result->getEnding() > $forbiddenRange->getBeginning()) {
                    $result->setEnding($forbiddenRange->getBeginning());
                }
                // fix the beginninge
                $secondPrevForbidden = $this->detectPrevioustRange(
                    $forbiddenTimerList,
                    $timerList,
                    $forbiddenRange->getBeginning()
                );
                if (($secondPrevForbidden->getEnding() > $result->getBeginning()) &&
                    ($secondPrevForbidden->getEnding() < $result->getEnding())
                ) {
                    $result->setBeginning($secondPrevForbidden->getEnding());
                }
                $this->exceptionIfBeginningGreaterEqualThanEnding(
                    $result,
                    $activeTimerList,
                    $timerList,
                    $forbiddenTimerList,
                    'prev'
                );
                return $result;
            }
            return $this->prevActiveRecursive(
                $newStart,
                $params,
                (--$recursiveLimiter),
                false
            );
        } // else => date is not part of forbidden range

        // the  testtime is not in forbidden range
        // If this method called recursively?
        /** @var TimerStartStopRange $resultActive */
        [$resultActive, $flagDateInActiveRange] = $this->detectActiveRangeAndShowIncludeFlag(
            $activeTimerList,
            $timerList,
            $dateLikeEventZone
        );
        if ($flagDateInActiveRange) {
            // expand and reduce the current range at the end
            // there should at least an rest active range because of the conditions before
            // add one second to the ending and call the netactive recursively once more

            $rangeActiveWithoutForbidden = $this->expandRangeAtBeginning($resultActive, $activeTimerList, $timerList);
            $forbiddenRangeRaw = $this->detectPrevioustRange($forbiddenTimerList, $timerList, $dateLikeEventZone);
            if (!$flagIntial) {
                if ($forbiddenRangeRaw->getEnding() > $rangeActiveWithoutForbidden->getBeginning()) {
                    $rangeActiveWithoutForbidden->setBeginning($forbiddenRangeRaw->getEnding());
                }
                return $rangeActiveWithoutForbidden;
            }
            $forbiddenRange = $this->expandRangeAtBeginning($forbiddenRangeRaw, $forbiddenTimerList, $timerList);
            if ($forbiddenRange->getBeginning() > $rangeActiveWithoutForbidden->getBeginning()) {
                $rangeActiveWithoutForbidden->setEnding($forbiddenRange->getBeginning());
                // the resulting active range may intercepted by a second forbidden range
                $rawForbiddenSecond = $this->detectPrevioustRange(
                    $forbiddenTimerList,
                    $timerList,
                    $rangeActiveWithoutForbidden->getEnding()
                );
                if ($rawForbiddenSecond->getEnding() > $rangeActiveWithoutForbidden->getBeginning()) {
                    $rangeActiveWithoutForbidden->setBeginning($rawForbiddenSecond->getEnding());
                }
                return $rangeActiveWithoutForbidden;
            }
            //get the best starting value for the next calulation of prevActive
            if ($forbiddenRange->getEnding() > $rangeActiveWithoutForbidden->getBeginning()) {
                $newStartRange = $forbiddenRange->getEnding();
            } else {
                $newStartRange = $rangeActiveWithoutForbidden->getBeginning();
            }
            $newStartRange->sub(new DateInterval(('PT1S')));
            return $this->prevActiveRecursive(
                $newStartRange,
                $params,
                (--$recursiveLimiter),
                false
            );
        }
        //bestimme einen nächsten kleinsten aktiven Bereich
        //erweitere aktiven Bereich, solange Überlappen möglich ist (bis keine weitere Überlappung gefunden wird, Bis Looplimit erreicht wurde oder bis Obergrenze erreicht wird.)
        $resultActiveRaw = $this->detectPrevioustRange($activeTimerList, $timerList, $dateLikeEventZone);
        $resultActive = $this->expandRangeAtBeginning($resultActiveRaw, $activeTimerList, $timerList);
        // Bestimme, ob Beginn im Forbidden bereich ist
        /** @var TimerStartStopRange $resultActive */
        [$resultIgnore, $flagDateInForbiddenRange] = $this->detectActiveRangeAndShowIncludeFlag(
            $forbiddenTimerList,
            $timerList,
            $resultActive->getEnding()
        );
        if ($flagDateInForbiddenRange) {
            // Wenn Beginn im Forbidden-Range ist, dannn
            $rangeForbidden = $this->expandRangeAtBeginning($resultIgnore, $forbiddenTimerList, $timerList);
            // ... prüfe, ob der Forbidden-Bereich den aktiven Bereich vollständig übersteigt
            if (($rangeForbidden->hasResultExist()) &&
                ($rangeForbidden->getBeginning() < $resultActive->getBeginning())
            ) {
                // wenn ja, dann suche rekursive ab dem Ende vom Forbiddenbereich
                $newStartDate = $rangeForbidden->getBeginning();
                $newStartDate->sub(new DateInterval('PT1S'));
                return $this->prevActiveRecursive(
                    $newStartDate,
                    $params,
                    (--$recursiveLimiter),
                    false
                );
            }
            /// wenn nein, dann passe den Beginn des aktiven Bereichs durch das Ende des Forbiddenbereichs an ...
            if ($rangeForbidden->hasResultExist()) {
                if (($rangeForbidden->getBeginning() > $resultActive->getBeginning()) &&
                    ($rangeForbidden->getBeginning() < $resultActive->getEnding())
                ) {
                    $resultActive->setBeginning($rangeForbidden->getEnding());
                }
            }
        }
        // ...und prüfe, ob der aktuelle aktove Bereich an seinem Ende durch einen forbiddenbereich noch zu beschneiden ist
        $forbiddenNextRange = $this->detectPrevioustRange(
            $forbiddenTimerList,
            $timerList,
            $resultActive->getEnding()
        );
        if ($forbiddenNextRange->getEnding() > $resultActive->getBeginning()) {
            $resultActive->setBeginning($forbiddenNextRange->getEnding());
        }
        if (($resultActive->getEnding() > $resultActive->getBeginning()) &&
            ($resultActive->hasResultExist())
        ) {
            return $resultActive;
        }
        // Dieser Fall sollte nicht eintreten
        throw new TimerException(
            'This execption should newer happen. Please make a screenshot and inform the webmaster. ' .
            'He needs the following informations to fix this bug:  a list of your timer used for detectikon of ' .
            ' active ranges and a list with all timers for detection of your forbidden ranges. ' .
            'Please check your configuration.',
            16006566923
        );
    }

    /**
     * find the next range of a active timegap depending on the defined list
     *
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @param ListOfTimerService $timerList
     * @param array<mixed> $activeTimerList
     * @param int $recursiveLimiter
     * @param bool $flagIntial
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function nextActiveRecursiveOnlyActive(
        DateTime $dateLikeEventZone,
        array $params,
        ListOfTimerService $timerList,
        array $activeTimerList,
        int $recursiveLimiter,
        bool $flagIntial = true
    ): TimerStartStopRange {
        [$result, $flagDateInActiveRange] = $this->detectActiveRangeAndShowIncludeFlag(
            $activeTimerList,
            $timerList,
            $dateLikeEventZone
        );

        if ($flagDateInActiveRange) {
            if (!$flagIntial) {
                return $result;
            }
            /** @var DateTime $newStart */
            $newStart = clone $result->getEnding();
            $newStart->add(new DateInterval('PT1S'));
            return $this->nextActiveRecursive(
                $newStart,
                $params,
                (--$recursiveLimiter),
                false
            );
        } // else

        //bestimme einen nächsten kleinsten aktiven Bereich
        //erweitere aktiven Bereich, solange Überlappen möglich ist (bis keine weitere Überlappung gefunden wird, Bis Looplimit erreicht wurde oder bis Obergrenze erreicht wird.)
        $resultRaw = $this->detectNearestRange($activeTimerList, $timerList, $dateLikeEventZone);
        return $this->expandRangeAtEnding($resultRaw, $activeTimerList, $timerList);
    }

    /**
     * find the next range of a active timegap depending on the defined list
     *
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @param ListOfTimerService $timerList
     * @param array<mixed> $activeTimerList
     * @param int $recursiveLimiter
     * @param bool $flagInitial
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function prevActiveRecursiveOnlyActive(
        DateTime $dateLikeEventZone,
        array $params,
        ListOfTimerService $timerList,
        array $activeTimerList,
        int $recursiveLimiter,
        bool $flagInitial
    ): TimerStartStopRange {
        [$result, $flagDateInActiveRange] = $this->detectActiveRangeAndShowIncludeFlag(
            $activeTimerList,
            $timerList,
            $dateLikeEventZone
        );

        if ($flagDateInActiveRange) {
            if (!$flagInitial) {
                return $this->expandRangeAtBeginning($result, $activeTimerList, $timerList);
            }
            /** @var DateTime $newStart */
            $newStart = clone $result->getBeginning();
            $newStart->sub(new DateInterval('PT1S'));
            return $this->prevActiveRecursive(
                $newStart,
                $params,
                (--$recursiveLimiter),
                false
            );
        } // else

        //bestimme einen nächsten kleinsten aktiven Bereich
        //erweitere aktiven Bereich, solange Überlappen möglich ist (bis keine weitere Überlappung gefunden wird, Bis Looplimit erreicht wurde oder bis Obergrenze erreicht wird.)
        $resultRaw = $this->detectPrevioustRange($activeTimerList, $timerList, $dateLikeEventZone);
        return $this->expandRangeAtBeginning($resultRaw, $activeTimerList, $timerList);
    }

    /**
     * The method gets an active range and expand this, depending on the timer-List
     *
     * @param TimerStartStopRange $rangeWithMinimalBeginning
     * @param array<mixed> $listOfTimer
     * @param ListOfTimerService $timerList
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function expandRangeAtEnding(
        TimerStartStopRange $rangeWithMinimalBeginning,
        array $listOfTimer,
        ListOfTimerService $timerList
    ): TimerStartStopRange {
        /** @var TimerStartStopRange $result */
        $result = clone $rangeWithMinimalBeginning;
        $expandLimit = $this->loopRecursiveLimiter;
        $flagChange = true;
        while (($expandLimit > 0) && ($flagChange)) {
            $flagChange = false;
            foreach ($listOfTimer as $singleActiveTimer) {
                $flagParamFailure = $this->isParamFailure($singleActiveTimer, $timerList);
                if ($flagParamFailure) {
                    // log only the missing of an allowed definition of timer
                    $this->logger->critical(
                        'The needed values `' . print_r(
                            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                            true
                        ) .
                        '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                        'Please check your definition in your active yaml-file. [1631365701]'
                    );
                } else {
                    $refDateNotInActive = clone $result->getEnding();
                    if ($timerList->isActive(
                        $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $refDateNotInActive,
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    )) {
                        $checkRange = $timerList->getLastIsActiveRangeResult(
                            $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                            $refDateNotInActive,
                            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                        );
                        if (($result->getEnding() < $checkRange->getEnding()) &&
                            ($checkRange->hasResultExist())
                        ) {
                            $result->setEnding($checkRange->getEnding());
                            $flagChange = true;
                        }
                    }
                }
            }
            $expandLimit--;
        }
        return $result;
    }

    /**
     * The method gets an active range and expand this, depending on the timer-List
     *
     * @param TimerStartStopRange $rangeWithMinimalBeginning
     * @param array<mixed> $listOfTimer
     * @param ListOfTimerService $timerList
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function expandRangeAtBeginning(
        TimerStartStopRange $rangeWithMinimalBeginning,
        array $listOfTimer,
        ListOfTimerService $timerList
    ): TimerStartStopRange {
        /** @var TimerStartStopRange $result */
        $result = clone $rangeWithMinimalBeginning;
        $expandLimit = $this->loopRecursiveLimiter;
        $flagChange = true;
        while (($expandLimit > 0) && ($flagChange)) {
            $flagChange = false;
            foreach ($listOfTimer as $singleActiveTimer) {
                $flagParamFailure = $this->isParamFailure($singleActiveTimer, $timerList);
                if ($flagParamFailure) {
                    // log only the missing of an allowed definition of timer
                    $this->logger->critical(
                        'The needed values `' . print_r(
                            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                            true
                        ) .
                        '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                        'Please check your definition in your active yaml-file. [1631365701]'
                    );
                } else {
                    $refDateNotInActive = clone $result->getBeginning();
                    if ($timerList->isActive(
                        $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $refDateNotInActive,
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    )) {
                        $checkRange = $timerList->getLastIsActiveRangeResult(
                            $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                            $refDateNotInActive,
                            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                        );
                        if (($result->getBeginning() > $checkRange->getBeginning()) &&
                            ($checkRange->hasResultExist())
                        ) {
                            $result->setBeginning($checkRange->getBeginning());
                            $flagChange = true;
                        }
                    }
                }
            }
            $expandLimit--;
        }
        return $result;
    }

    /**
     * The method gets an active range and expand this, depending on the timer-List
     *
     * @param TimerStartStopRange $rangeWithMaximalEnding
     * @param array<mixed> $listOfTimer
     * @param ListOfTimerService $timerList
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function reduceRangeFromEnding(
        TimerStartStopRange $rangeWithMaximalEnding,
        array $listOfTimer,
        ListOfTimerService $timerList
    ): TimerStartStopRange {
        /** @var TimerStartStopRange $result */
        $result = clone $rangeWithMaximalEnding;
        $expandLimit = $this->loopRecursiveLimiter;
        $flagChange = true;
        while (($expandLimit > 0) && ($flagChange)) {
            $flagChange = false;
            foreach ($listOfTimer as $singleActiveTimer) {
                $flagParamFailure = $this->isParamFailure($singleActiveTimer, $timerList);
                if ($flagParamFailure) {
                    // log only the missing of an allowed definition of timer
                    $this->logger->critical(
                        'The needed values `' . print_r(
                            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                            true
                        ) .
                        '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                        'Please check your definition in your active yaml-file. [1631476901]'
                    );
                } else {
                    $refDateNotInActive = clone $result->getEnding();
                    if ($timerList->isActive(
                        $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $refDateNotInActive,
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    )) {
                        $checkRange = $timerList->getLastIsActiveRangeResult(
                            $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                            $refDateNotInActive,
                            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                        );
                        if (($checkRange->hasResultExist()) &&
                            ($checkRange->getBeginning() < $result->getEnding())
                        ) {
                            $result->setEnding($checkRange->getBeginning());
                            $flagChange = true;
                        }
                    }
                }
            }
            $expandLimit--;
        }
        return $result;
    }

    /**
     * condition: The $refDateNotInActive is not part of an active range
     *
     * @param array<mixed> $activeTimerList
     * @param ListOfTimerService $timerList
     * @param DateTime $refDateNotInActive
     * @return TimerStartStopRange
     */
    protected function detectNearestRange(
        array $activeTimerList,
        ListOfTimerService $timerList,
        DateTime $refDateNotInActive
    ) {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->failAllActive($refDateNotInActive);
        $flagFirst = true;
        foreach ($activeTimerList as $singleActiveTimer) {
            $flagParamFailure = $this->isParamFailure($singleActiveTimer, $timerList);
            if ($flagParamFailure) {
                // log only the missing of an allowed definition of timer
                $this->logger->critical(
                    'The needed values `' . print_r(
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                        true
                    ) .
                    '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                    'Please check your definition in your active yaml-file. [1631365701]'
                );
            } else {
                $checkRange = $timerList->nextActive(
                    $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $refDateNotInActive,
                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                );
                if ($flagFirst) {
                    $result = clone $checkRange;
                    $flagFirst = false;
                } else {
                    if (($result->getBeginning() > $checkRange->getBeginning()) &&
                        ($checkRange->hasResultExist())
                    ) {
                        $result = clone $checkRange;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * condition: The $refDateNotInActive is not part of an active range
     *
     * @param array<mixed> $activeTimerList
     * @param ListOfTimerService $timerList
     * @param DateTime $refDateNotInActive
     * @return TimerStartStopRange
     */
    protected function detectPrevioustRange(
        array $activeTimerList,
        ListOfTimerService $timerList,
        DateTime $refDateNotInActive
    ) {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->failAllActive($refDateNotInActive);
        $flagFirst = true;
        foreach ($activeTimerList as $singleActiveTimer) {
            $flagParamFailure = $this->isParamFailure($singleActiveTimer, $timerList);

            if ($flagParamFailure) {
                // log only the missing of an allowed definition of timer
                $this->logger->critical(
                    'The needed values `' . print_r(
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                        true
                    ) .
                    '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                    'Please check your definition in your active yaml-file. [1631364891]'
                );
            } else {
                $checkRange = $timerList->prevActive(
                    $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $refDateNotInActive,
                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                );
                if ($flagFirst) {
                    $result = clone $checkRange;
                    $flagFirst = false;
                } else {
                    if (($result->getEnding() < $checkRange->getEnding()) &&
                        ($checkRange->hasResultExist())
                    ) {
                        $result = clone $checkRange;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested 20220925
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $loopRecursiveLimiter = (
        (array_key_exists(self::ARG_YAML_RECURSIVE_LOOP_LIMIT, $params)) ?
            $params[self::ARG_YAML_RECURSIVE_LOOP_LIMIT] :
            self::MAX_TIME_LIMIT_MERGE_COUNT
        );
        return $this->validateUltimateRangeForPrevRange(
            $this->prevActiveRecursive(
                $dateLikeEventZone,
                $params,
                $loopRecursiveLimiter,
                true
            ),
            $params,
            $dateLikeEventZone
        );
    }

    /**
     * find the next range of a active timegap depending on the defined list
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @param int $recursiveLimiter
     * @param bool $flagInitial
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function prevActiveRecursive(
        DateTime $dateLikeEventZone,
        array $params = [],
        int $recursiveLimiter = self::MAX_TIME_LIMIT_MERGE_COUNT,
        bool $flagInitial = true
    ): TimerStartStopRange {
        if ($recursiveLimiter <= 0) {
            $result = new TimerStartStopRange();
            $result->failAllActive($dateLikeEventZone);
            return $result;
        }

        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
        [$activeTimerList, $forbiddenTimerList] = $this->detectActiveAndForbiddenList($params);
        if (empty($activeTimerList)) {
            throw new TimerException(
                'The list for the active range must be filled in `RangeTimerList::prevActive()` with at least one timer-definition. ' .
                'There is found anything. Please check, if your yaml-file and/or your definitons in the database are available.' .
                'If everything seems fine, then inform the webmaster.',
                1612366813
            );
        }
        if (!empty($forbiddenTimerList)) {
            return $this->prevActiveRecursiveWithForbidden(
                $dateLikeEventZone,
                $params,
                $timerList,
                $activeTimerList,
                $forbiddenTimerList,
                $recursiveLimiter,
                $flagInitial
            );
        }
        return $this->prevActiveRecursiveOnlyActive(
            $dateLikeEventZone,
            $params,
            $timerList,
            $activeTimerList,
            $recursiveLimiter,
            $flagInitial
        );
    }

    /**
     * @param array<mixed> $activeTimerList
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
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);

        while ($loopLimiter > 0) {
            foreach ($activeTimerList as $singleActiveTimer) {
                $flagParamFailure = $this->isParamFailure($singleActiveTimer, $timerList);

                if ($flagParamFailure) {
                    // log only the missing of an allowed-timerdcefinition
                    $this->logger->critical(
                        'The needed values `' . print_r(
                            $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                            true
                        ) .
                        '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                        'Please check your definition in your active yaml-file. [1600865701]'
                    );
                } else {
                    /**
                     * Check, if the current date is active and although Part of forbidden.
                     * If Yes: then check, if the resulting part is part of a forbidden-range and
                     * if a part of the current active part is above the current date (next active-part)
                     */
                    $checkResult = $timerList->nextActive(
                        $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
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
     * @param array<mixed> $activeTimerList
     * @param array<mixed> $forbiddenTimerList
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
                $firstForbiddenRange = $this->getActiveRangeWithLowestBeginRefDate(
                    $forbiddenTimerList,
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
     * @param YamlFileLoader $yamlFileLoader
     * @param array<mixed> $params
     * @param string $key
     * @return array<mixed>
     * @throws TimerException
     */
    protected function readRangeListFromFileOrUrl(
        YamlFileLoader $yamlFileLoader,
        array  $params,
        string $key
    ): array
    {
        //        $yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);

        if (empty($params[$key])) {
            return [];
        }
        // $this must the method `validateYamlOrException`
        $result = CustomTimerUtility::readListFromFileOrUrl(
            $params[$key],
            $yamlFileLoader,
            $this,
            $this->logger
        );
        if (array_key_exists(self::YAML_MAIN_LIST_KEY, $result)) {
            $result = $result[self::YAML_MAIN_LIST_KEY];
        } // else $result without yaml-help-layer

        if (!is_array($result)) {
            throw new TimerException(
                'The value of the yaml-file, csv-file or json-file in `' . $params[$key] . '` does not results into an array. ' .
                'Please check your configuration.' . print_r($result, true),
                1600865701
            );
        }
        return $result;
    }

    /**
     * @param array<mixed> $params
     * @param string $key
     * @return array<mixed>
     * @throws TimerException
     */
    protected function readRangeListFromDatabase(
        array $params,
        string $key = self::ARG_DATABASE_ACTIVE_RANGE_LIST
    ): array {
        if (!array_key_exists($key, $params)) {
            return [];
        }
        return $this->readListFromDatabase($params[$key]);
    }

    /**
     * @param string $commaListOfUids
     * @return array<mixed>
     * @throws TimerException
     */
    protected function readListFromDatabase(string $commaListOfUids): array
    {
        if (empty(trim($commaListOfUids))) {
            return [];
        }
        /** @var array<mixed> $rawResult */
        $rawResult = $this->listingRepository->findByCommaList($commaListOfUids);
        if (empty($rawResult)) {
            return [];
        }

        $result = [];
        /** @var Listing $item */
        foreach ($rawResult as $item) {
            if (is_array($item)) {  //@phpstan-ignore-line
                $rawFlexformString = $item[TimerConst::TIMER_FIELD_FLEX_ACTIVE];
            } elseif (is_object($item)) {
                $rawFlexformString = $item->getTxTimerTimer();
            } else { //@phpstan-ignore-line
                throw new TimerException(
                    'The item is wether an object nor an array. Something went seriously wrong.',
                    1654238702
                );
            }

            if (empty($rawFlexformString)) {
                $params = [];
            } else {
                $rawParamsArray = GeneralUtility::xml2array($rawFlexformString);
                $params = TcaUtility::flexformArrayFlatten($rawParamsArray);
            }
            if (is_array($item)) {  //@phpstan-ignore-line
                $result[] = [
                    self::YAML_LIST_ITEM_SELECTOR => $item['tx_timer_selector'],
                    self::YAML_LIST_ITEM_PARAMS => $params,
                    self::YAML_LIST_ITEM_TITLE => $item['title'],
                    self::YAML_LIST_ITEM_DESCRIPTION => $item['description'],
                ];
            } else {
                $result[] = [
                    self::YAML_LIST_ITEM_SELECTOR => $item->getTxTimerSelector(),
                    self::YAML_LIST_ITEM_PARAMS => $params,
                    self::YAML_LIST_ITEM_TITLE => $item->getTitle(),
                    self::YAML_LIST_ITEM_DESCRIPTION => $item->getDescription(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param DateTime $dateStart
     * @param DateTime $dateStop
     * @param bool $flag
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return void
     */
    protected function setIsActiveResult(
        DateTime $dateStart,
        DateTime $dateStop,
        bool $flag,
        DateTime $dateLikeEventZone,
        array $params = []
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
     * @param array<mixed> $params
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
     * @param array<mixed> $activeTimerList
     * @param DateTime $refDateForRange
     * @param ListOfTimerService $timerList
     * @return array<mixed>
     * @throws TimerException
     */
    protected function isRefdateInActiveRange(
        array $activeTimerList,
        DateTime $refDateForRange,
        ListOfTimerService $timerList
    ): array {
        $flagIsInActive = false;
        $firstActiveCurrentRange = new TimerStartStopRange();
        $firstActiveCurrentRange->failAllActive($refDateForRange);
        foreach ($activeTimerList as $singleActiveTimer) {
            $flagParamFailure = $this->isParamFailure($singleActiveTimer, $timerList);
            if ($flagParamFailure) {
                // log only the missing of an allowed-timerdcefinition
                $this->logger->critical(
                    'The needed values `' . print_r(
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                        true
                    ) .
                    '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                    'Please check your definition in your active yaml-file. [1600865701]'
                );
            } else {
                if ($timerList->isActive(
                    $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $refDateForRange,
                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                )) {
                    $flagIsInActive = true;
                    $firstActiveCurrentRange = $timerList->getLastIsActiveRangeResult(
                        $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $refDateForRange,
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
     * @param array<mixed> $activeTimerList
     * @param array<mixed> $forbiddenTimerList
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
            $refDate = clone $startTestDate;
            $refDate->add(new DateInterval('PT1S'));
            $result = $this->getActiveRangeWithLowestBeginRefDate(
                $activeTimerList,
                $refDate
            );
            /** @var TimerStartStopRange $firstForbiddenRange */
            [$flagIsInForbidden, $firstForbiddenRange] = $this->isRefdateInActiveRange(
                $forbiddenTimerList,
                $refDate,
                $timerList
            );
            if ($flagIsInForbidden) {
                if ($firstForbiddenRange->getEnding() < $result->getEnding()) {
                    $result->setBeginning($firstForbiddenRange->getEnding());
                } else {
                    $nextTry = clone $firstForbiddenRange->getEnding();
                    $result = $this->getActivePartialRangeWithLowestBeginRefDate(
                        $activeTimerList,
                        $forbiddenTimerList,
                        $nextTry,
                        $timerList,
                        (--$recursionCount)
                    );
                    break;
                }
            }

            $forbiddenStart = clone $result->getBeginning();
            $forbiddenRange = $this->getActiveRangeWithLowestBeginRefDate(
                $forbiddenTimerList,
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
                        (--$recursionCount)
                    );
                } else {
                    $result->setBeginning($forbiddenRange->getEnding());
                }
                break;
            }
            // startpart is active => detect the endpart
            if ($forbiddenRange->getBeginning() <= $result->getEnding()) {
                $result->setEnding($forbiddenRange->getBeginning());
                break;
            }
            $limiter--;
        }
        return $result;
    }

    /**
     * @param TimerStartStopRange $activeRange
     * @param array<mixed> $activeTimer
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
                if ($timerList->isActive(
                    $checkActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $checkBeginn,
                    $checkActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                )
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
                if ($timerList->isActive(
                    $checkActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $checkEnd,
                    $checkActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                )
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

    /**
     * @param array<mixed> $params
     * @return array<mixed>
     * @throws TimerException
     */
    protected function detectActiveAndForbiddenList(array $params): array
    {
        $yamlFileActiveTimerList = $this->readRangeListFromFileOrUrl(
            $this->yamlFileLoader,
            $params,
            self::ARG_YAML_ACTIVE_FILE_PATH
        );
        $databaseActiveTimerList = $this->readRangeListFromDatabase($params, self::ARG_DATABASE_ACTIVE_RANGE_LIST);
        $activeTimerList = array_merge($yamlFileActiveTimerList, $databaseActiveTimerList);

        $yamlForbiddenTimerList = $this->readRangeListFromFileOrUrl(
            $this->yamlFileLoader,
            $params,
            self::ARG_YAML_FORBIDDEN_FILE_PATH
        );
        $databaseForbiddenTimerList = $this->readRangeListFromDatabase(
            $params,
            self::ARG_DATABASE_FORBIDDEN_RANGE_LIST
        );
        $forbiddenTimerList = array_merge($yamlForbiddenTimerList, $databaseForbiddenTimerList);
        return [$activeTimerList, $forbiddenTimerList];
    }

    /**
     * @param array<mixed> $listOfTimer
     * @param ListOfTimerService $timerList
     * @param DateTime $dateLikeEventZone
     * @return array<mixed>
     * @throws TimerException
     */
    protected function detectActiveRangeAndShowIncludeFlag(
        array $listOfTimer,
        ListOfTimerService $timerList,
        DateTime $dateLikeEventZone
    ): array {
        $flag = false;
        $currentRange = new TimerStartStopRange();
        $currentRange->failAllActive($dateLikeEventZone);
        foreach ($listOfTimer as $singleActiveTimer) {
            $flagParamFailure = $this->isParamFailure($singleActiveTimer, $timerList);
            if ($flagParamFailure) {
                // log only the missing of an allowed definition of timer
                $this->logger->critical(
                    'The needed values `' . print_r(
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS],
                        true
                    ) .
                    '` for the active-timer `' . $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR] . '` seems to be not set or undefined. ' .
                    'Please check your definition in your active yaml-file. [1600865701]'
                );
            } else {
                if ($timerList->isActive(
                    $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                    $dateLikeEventZone,
                    $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                )
                ) {
                    $currentRange = $timerList->getLastIsActiveRangeResult(
                        $singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR],
                        $dateLikeEventZone,
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    );
                    $this->expandRangeAroundActive($currentRange, $listOfTimer, $timerList);
                    $flag = true;
                    $this->setIsActiveResult(
                        $currentRange->getBeginning(),
                        $currentRange->getEnding(),
                        $flag,
                        $dateLikeEventZone,
                        $singleActiveTimer[self::YAML_LIST_ITEM_PARAMS]
                    );
                    break;
                }
            }
        }
        return [$currentRange, $flag];
    }

    /**
     * @param TimerStartStopRange $result
     * @param array<mixed> $activeTimerList
     * @param ListOfTimerService $timerList
     * @param array<mixed> $forbiddenTimerList
     * @return void
     * @throws TimerException
     */
    protected function exceptionIfBeginningGreaterEqualThanEnding(
        TimerStartStopRange $result,
        array $activeTimerList,
        ListOfTimerService $timerList,
        array $forbiddenTimerList,
        string $forMathodAdd = 'next'
    ): void {
        if ($result->getBeginning() >= $result->getEnding()) {
            $listActiveTimer = '';
            foreach ($activeTimerList as $singleActiveTimer) {
                $listActiveTimer .= ' ' . $timerList->selfName($singleActiveTimer[self::YAML_LIST_ITEM_SELECTOR]);
                $listActiveTimer .= '(' . print_r($singleActiveTimer[self::YAML_LIST_ITEM_PARAMS], true) . ',';
            }
            $listActiveTimer = trim($listActiveTimer, ', ');
            $listForbiddenTimer = '';
            foreach ($forbiddenTimerList as $singleForbiddenTimer) {
                $listForbiddenTimer .= ' ' . $timerList->selfName($singleForbiddenTimer[self::YAML_LIST_ITEM_SELECTOR]);
                $listForbiddenTimer .= '(' . print_r($singleForbiddenTimer[self::YAML_LIST_ITEM_PARAMS], true) . ',';
            }
            $listForbiddenTimer = trim($listForbiddenTimer, ', ');
            throw new TimerException(
                'The beginning in the range is greater or equal to the ending of the range. ' .
                'This should not happen in `' . $forMathodAdd . 'ActiveRecursiveWithForbidden`. The range is `' .
                $result->getBeginning()->format('Y-m-d H:i:s') . '` >= `' .
                $result->getEnding()->format('Y-m-d H:i:s') . '`. The used active timers are: ' .
                $listActiveTimer . '. The used forbidden timers are: ' . $listForbiddenTimer . '. ' .
                'There may some missconfigurations in your timerdefinitions. Make a screenshot and inform the webmaster.',
                1601981601
            );
        }
    }

    /**
     * for testing approches
     * easy to mock
     */
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
     * @param array<mixed> $singleTimerParams
     * @param ListOfTimerService $timerList
     * @return bool
     */
    protected function isParamFailure(array $singleTimerParams, ListOfTimerService $timerList): bool
    {
        $flagParamFailure = (!array_key_exists(self::YAML_LIST_ITEM_SELECTOR, $singleTimerParams)) ||
            (!array_key_exists(self::YAML_LIST_ITEM_PARAMS, $singleTimerParams)) ||
            (
            !$timerList->validate(
                $singleTimerParams[self::YAML_LIST_ITEM_SELECTOR],
                $singleTimerParams[self::YAML_LIST_ITEM_PARAMS]
            )
            );
        return $flagParamFailure;
    }
    /**
     * // for testing approches
     * easy to mock
     */
}

/**
 * the solution differs from all this thinkings.
 * This ist only for histroical porposes and for some helps, if there are to fix bugs in the future.
 * Thoughts about the struktur for the nextActive-Algorithm
 * Problem: find the active text range relative to date without any optical informations
 *
 * Requirements:
 * DU has a next process for all affected active and forbidden processes
 *
 * Solution idea 1:
 *     Create a data structure with active areas
 *     Create a data structure with forbidden areas
 *     Determine the differences
 *
 *     allowed:    #### #### ## ####
 *     forbidden: ## ### # # ###   ###
 *     reuslt:      .   . .     ...
 *
 * Solution idea 2: Determine the next area
 *     Check whether the test time is actively in a forbidden range.
 *     •   Yes:
 *         Expand the forbidden area by overlapping at the end (parameter: max. 10)
 *         End time plus one second becomes new start time
 *         Check whether the test time is in the active range
 *         ◦   yes
 *             Determine last active area
 *             Set next Forbidden range as maximum end time for Active range
 *             Check whether the last active area can still be extended by overlapping
 *             => return Result
 *         ◦   no
 *             Determine a next active area with the smallest lower time limit
 *             Check if start time is part of Forbidden range
 *             ▪   yes
 *                 Shift the start time of the active area
 *                 Active area exists?
 *                 •   Yes
 *                     Recursive call to shift start time
 *                 •   no
 *                     Active area no longer exists
 *                     Recursive call
 *                     Determine next forbidden range relative to start time for maximum end time
 *             ▪   no
 *                 Set previous forbidden range, relative
 *         •   no:
 *             If so, then the end time plus one second becomes the new start time.
 *             (recursion call, active okay)
 *             If not, then the start time is not part of a forbidden range.
 *             Check if the test time is part of an active area.
 *             If so, then determine the next forbidden area
 *             Determine the overlap and determine if any
 *             If not
 *             Determine the active next area relative to the start time.
 *
 *
 * solution idea 3:
 * method `nextActive`:
 *     is a forbidden area defined at all
 *     • no
 *         Is test time in active area
 *         ▪   yes:
 *             Specify one of the next Forbidden ranges to determine the upper limit for the current active range.
 *             Expand active area by overlapping as long as overlapping is possible (loop constraint, cap, no more overlapping)
 *             Determine the end time of the active area plus one second as the new start time
 *             return nextRange (activeEndTime+1s) [recursive]
 *         ▪   no:
 *             determine a next smallest active region
 *             return NextArea(startDate = startOfActiveRange) [recursive]
 *     • Yes
 *         If the test time is in a forbidden range
 *         ◦   yes:
 *             Expand the forbidden area as long as the end area still goes (isActive(end) => expand (loop limiter)
 *             Check if end time+1s of Forbidden is in an active time
 *             ▪   yes:
 *                 ActiveStartTime = Forbidden.EndTime+1
 *                 Check next forbidden range to determine upper limit for end time
 *                 Extend end-limit Active area by overlap until no more overlap is found, until loop limit is reached, or until upper limit is reached.
 *                 return RESULT
 *             ▪   no:
 *                 return NextRange (forbiddenRangeEndTime+1s) [recursive]
 *         ◦   no:
 *             Is test time in active area
 *             ▪   yes:
 *                 Extend the active area
 *                 Restrict the IsActive range to the Forbidden range
 *                 Determine the new reference times from the end time of the active area
 *                 return recursive NextRange(isActive-Edtime+1s)
 *                 deleted-start>>>
 *                             Specify one of the next Forbidden ranges to determine the upper limit for the current active range.
 *                             Expand active area by overlapping as long as overlapping is possible (loop constraint, cap, no more overlapping)
 *                             Determine the end time of the active area plus one second as the new start time
 *                             determine
 *                             recursive next range (active end time+1s)
 *                 <<< deleted-end
 *             ▪   no:
 *                 determine a next smallest active region
 *                 check if activerarea.start is in Forbidden area
 *                 •   no:
 *                     determine next smallest forbidden range as upper limit
 *                     extend active range as long as overlap is possible (until no more overlap is found, until loop limit is reached, or until cap is reached.)
 *                     return RESULT
 *                 •   yes:
 *                     expand the forbidden range as long as overlapping is possible
 *                     retunr NextRange (ForbiddenEnd time+1s) [recursive]
 *
 *
 */
