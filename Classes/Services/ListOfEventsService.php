<?php

namespace Porthd\Timer\Services;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 Dr. Dieter Porthd <info@mobger.de>
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
use Porthd\Ichschauweg\Utilities\FlexFormUtility;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Domain\Model\InternalFlow\LoopLimiter;
use Porthd\Timer\Exception\TimerException;
use stdClass;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ListOfEventsService
{

    protected const ARGUMENT_START_DATETIME = 'start';
    protected const ARGUMENT_STOP_DATETIME = 'stop';
    protected const SUBKEY_TIMER = 'timer';
    protected const SUBKEY_RANGE = 'range';
    protected const SUBKEY_GAP_MIN = 'gapMin';
    protected const ARGUMENT_KEY = 'key';

    /**
     * @param DateTime $highestEndStopTime
     * @param TimerStartStopRange $range
     * @param int $key
     * @param DateTime $highestEndStartTime
     * @param int $highestLowKey
     * @return array
     */
    protected static function checkIfRangeLowerGreatest(
        &$changed,
        $currentLimitInfos,
        TimerStartStopRange $range,
        int $key
    ): array {
        if (($currentLimitInfos[self::ARGUMENT_STOP_DATETIME] < $range->getEnding()) ||
            ($currentLimitInfos[self::ARGUMENT_KEY] === -1)
        ) {
            $currentLimitInfos[self::ARGUMENT_STOP_DATETIME] = $range->getEnding();
            $currentLimitInfos[self::ARGUMENT_START_DATETIME] = $range->getBeginning();
            $currentLimitInfos[self::ARGUMENT_KEY] = $key;
            $changed = true;
        } else {
            if (($currentLimitInfos[self::ARGUMENT_STOP_DATETIME] == $range->getEnding()) &&
                ($currentLimitInfos[self::ARGUMENT_START_DATETIME] < $range->getBeginning())
            ) {
                // The variable for the ending is already set.
                $currentLimitInfos[self::ARGUMENT_START_DATETIME] = clone $range->getBeginning();
                $currentLimitInfos[self::ARGUMENT_KEY] = $key;
                $changed = true;
            }
        }
        return $currentLimitInfos;
    }


    /**
     * @param $changed
     * @param array $currentLimitInfos
     * @param TimerStartStopRange $range
     * @param int $key
     * @return array
     */
    protected static function checkIfRangeGreaterLowest(
        &$changed,
        $currentLimitInfos,
        TimerStartStopRange $range,
        int $key
    ): array {
        if (($currentLimitInfos[self::ARGUMENT_START_DATETIME] > $range->getBeginning()) ||
            ($currentLimitInfos[self::ARGUMENT_KEY] === -1)
        ) {
            $currentLimitInfos[self::ARGUMENT_START_DATETIME] = clone $range->getBeginning();
            $currentLimitInfos[self::ARGUMENT_STOP_DATETIME] = clone $range->getEnding();
            $currentLimitInfos[self::ARGUMENT_KEY] = $key;
            $changed = true;
        } else {
            if (($currentLimitInfos[self::ARGUMENT_START_DATETIME] == $range->getBeginning()) &&
                ($currentLimitInfos[self::ARGUMENT_STOP_DATETIME] < $range->getEnding())
            ) {
                $currentLimitInfos[self::ARGUMENT_STOP_DATETIME] = clone $range->getEnding();
                $currentLimitInfos[self::ARGUMENT_KEY] = $key;
                $changed = true;
            }
        }
        return $currentLimitInfos;
    }

    /**
     * @param $eventsTimerList
     * @param DateTime $timerEventZone
     * @param LoopLimiter $loopLimiter
     * @param false $flagReverse
     * @param int $maxCount
     * @param DateTime|null $maxLate
     * @return array
     */
    public static function generateEventsListFromTimerList(
        $eventsTimerList,
        DateTime $timerEventZone,
        LoopLimiter $loopLimiter,
        $flagReverse = false,
        int $maxCount = TimerConst::SAVE_LIMIT_MAX_EVENTS
    ): array {
        /** @var ListOfTimerService $timerResolver */
        $timerResolver = GeneralUtility::makeInstance(ListOfTimerService::class);
        if ($flagReverse) {
            return self::listOfEventsBelowStartTime($timerEventZone, $eventsTimerList, $timerResolver, $loopLimiter,
                $maxCount);
        }
        return self::listOfEventsAboveStartTime($timerEventZone, $eventsTimerList, $timerResolver, $loopLimiter,
            $maxCount);
    }

    /**
     * @param DateTime $timerEventZone
     * @param $eventsTimerList
     * @param ListOfTimerService $timerResolver
     * @return array
     */
    protected static function timerListBelowStartDate(
        DateTime $timerEventZone,
        $eventsTimerList,
        ListOfTimerService $timerResolver
    ): array {
        $listOfTimers = [];
        $getterSelectName = TimerConst::GETTER_TIMER_FIELD_SELECT;
        $getterFlexParameter = TimerConst::GETTER_TIMER_FIELD_FLEX_ACTIVE;
        foreach ($eventsTimerList as $key => $item) {
            [$timerSelectName, $timerFlexParameter] = self::extractSelectorAndTimer($item, $getterSelectName,
                $getterFlexParameter);

            /** @var TimerStartStopRange $range */
            $range = $timerResolver->prevActive(
                $timerSelectName,
                $timerEventZone,
                $timerFlexParameter
            );
            $flagAllowed = $timerResolver->isAllowedInRange(
                $timerSelectName,
                $timerEventZone,
                $timerFlexParameter
            );
            if (($range->hasResultExist()) &&
                ($range->getEnding() <= $timerEventZone) &&
                ($range->getBeginning() < $range->getEnding()) &&
                ($flagAllowed)
            ) {

                $timerItem[TimerConst::KEY_EVENT_LIST_TIMER] = $item;
                $timerItem[TimerConst::KEY_EVENT_LIST_RANGE] = clone $range;
                $timerItem[TimerConst::KEY_EVENT_LIST_GAP] = ceil(
                    abs(
                        ($range->getBeginning()->getTimestamp() - $range->getEnding()->getTimestamp()) / 60)
                );
                $listOfTimers[$key] = $timerItem;
            }
        }
        return $listOfTimers;
    }

    /**
     * @param DateTime $timerEventZone
     * @param $eventsTimerList
     * @param ListOfTimerService $timerResolver
     * @return array
     */
    protected static function timerListAboveStartDate(
        DateTime $timerEventZone,
        $eventsTimerList,
        ListOfTimerService $timerResolver
    ): array {
        $listOfTimers = [];
        $getterSelectName = TimerConst::GETTER_TIMER_FIELD_SELECT;
        $getterFlexParameter = TimerConst::GETTER_TIMER_FIELD_FLEX_ACTIVE;
        foreach ($eventsTimerList as $key => $item) {
            [$timerSelectName, $timerFlexParameterList] = self::extractSelectorAndTimer($item, $getterSelectName,
                $getterFlexParameter);
            /** @var TimerStartStopRange $range */
            $range = $timerResolver->nextActive(
                $timerSelectName,
                $timerEventZone,
                $timerFlexParameterList
            );
            $flagAllowed = $timerResolver->isAllowedInRange(
                $timerSelectName,
                $timerEventZone,
                $timerFlexParameterList
            );

            if (($range->hasResultExist()) &&
                ($range->getBeginning() >= $timerEventZone) &&
                ($range->getBeginning() < $range->getEnding()) &&
                ($flagAllowed)
            ) {
                $timerItem[TimerConst::KEY_EVENT_LIST_TIMER] = $item;
                $timerItem[TimerConst::KEY_EVENT_LIST_RANGE] = clone $range;
                $timerItem[TimerConst::KEY_EVENT_LIST_GAP] = ceil(
                    abs(
                        ($range->getBeginning()->getTimestamp() - $range->getEnding()->getTimestamp()) / 60)
                );
                $listOfTimers[$key] = $timerItem;
                unset($range);
            }
        }
        return $listOfTimers;
    }

    /**
     * @param DateTime $timerEventZone
     * @param $eventsTimerList
     * @param ListOfTimerService $timerResolver
     * @param LoopLimiter $loopLimiter
     * @param int $maxCount
     * @param DateTime|null $maxLate
     * @return array
     */
    protected static function listOfEventsBelowStartTime(
        DateTime $timerEventZone,
        $eventsTimerList,
        ListOfTimerService $timerResolver,
        LoopLimiter $loopLimiter,
        int $maxCount = TimerConst::SAVE_LIMIT_MAX_EVENTS
    ): array {
        $listOfTimers = self::timerListBelowStartDate(
            $timerEventZone,
            $eventsTimerList,
            $timerResolver
        );
        $limitInfos = new stdClass();
        self::reinitBelowLimitInfos($limitInfos, $timerEventZone);
        $getterSelectName = TimerConst::GETTER_TIMER_FIELD_SELECT;
        $getterFlexParameter = TimerConst::GETTER_TIMER_FIELD_FLEX_ACTIVE;

        $listOfEvents = [];
        if (!empty($listOfTimers)) {
            $count = 0;
            $userCompareString = ((empty($loopLimiter->getUserCompareFunction())) ?
                'Porthd\Timer\Services\ListOfEventsService::compareForBelowList' :
                $loopLimiter->getUserCompareFunction()
            );
            while ($count <= $maxCount) {
                foreach ($listOfTimers as $key => $timerItem) {
                    /** @var TimerStartStopRange $range */
                    $range = clone $timerItem[TimerConst::KEY_EVENT_LIST_RANGE];
                    if ($range->hasResultExist()) {
                        if ($limitInfos->index < 0) {
                            // The first entrie is the best result
                            $limitInfos->beginning = $range->getBeginning();
                            $limitInfos->ending = $range->getEnding();
                            $limitInfos->base = $limitInfos->ending;
                            $limitInfos->index = $key;
                        } else {
                            // magic string function
                            if ($userCompareString($range, $limitInfos)) {
                                $limitInfos->beginning = $range->getBeginning();
                                $limitInfos->ending = $range->getEnding();
                                $limitInfos->base = $limitInfos->ending;
                                $limitInfos->index = $key;
                            }

                        }
                    }
                }

                if (
                    ($limitInfos->index < 0) ||
                    (self::limitsAllowOneMoreLoop($loopLimiter, $count, $limitInfos->base, false) === false)
                ) {
                    break;
                }

                $listOfEvents[$count] = clone $listOfTimers[$limitInfos->index];
                $count++;
                [$timerSelectName, $timerFlexParameter] = self::extractSelectorAndTimer(
                    $listOfTimers[$limitInfos->index][TimerConst::KEY_EVENT_LIST_TIMER],
                    $getterSelectName,
                    $getterFlexParameter
                );

                $range = $timerResolver->prevActive(
                    $timerSelectName,
                    $limitInfos->beginning,
                    $timerFlexParameter
                );
                if (($range->hasResultExist()) &&
                    ($timerResolver->isAllowedInRange(
                        $timerSelectName,
                        $range->getBeginning(),
                        $timerFlexParameter)
                    )
                ) {


                    $listOfTimers[$limitInfos->index][TimerConst::KEY_EVENT_LIST_RANGE] = clone $range;
                    $listOfTimers[$limitInfos->index][TimerConst::KEY_EVENT_LIST_GAP] = abs(
                        ($range->getBeginning()->getTimestamp() - $range->getEnding()->getTimestamp()) / 60
                    );
                } else {
                    $count--;
                    unset($listOfEvents[$count]);
                    unset($listOfTimers[$limitInfos->index]);
                    if (empty($listOfTimers)) {
                        break;
                    }
                }

                self::reinitBelowLimitInfos($limitInfos, $limitInfos->base);

            }
        }
        return $listOfEvents;
    }

    /**
     * @param DateTime $timerEventZone
     * @param $eventsTimerList
     * @param ListOfTimerService $timerResolver
     * @param LoopLimiter $loopLimiter
     * @param int $maxCount
     * @param DateTime|null $maxLate
     * @return array
     */
    protected static function listOfEventsAboveStartTime(
        DateTime $timerEventZone,
        $eventsTimerList,
        ListOfTimerService $timerResolver,
        LoopLimiter $loopLimiter,
        int $maxCount = TimerConst::SAVE_LIMIT_MAX_EVENTS
    ): array {
        $listOfTimers = self::timerListAboveStartDate(
            $timerEventZone,
            $eventsTimerList,
            $timerResolver
        );
        $limitInfos = new stdClass();
        self::reinitAboveLimitInfos($limitInfos, $timerEventZone);
        $getterSelectName = TimerConst::GETTER_TIMER_FIELD_SELECT;
        $getterFlexParameter = TimerConst::GETTER_TIMER_FIELD_FLEX_ACTIVE;
        $listOfEvents = [];
        if (!empty($listOfTimers)) {
            $count = 0;
            $userCompareString = ((empty($loopLimiter->getUserCompareFunction())) ?
                'Porthd\Timer\Services\ListOfEventsService::compareForAboveList' :
                $loopLimiter->getUserCompareFunction()
            );
            while ($count <= $maxCount) {
                foreach ($listOfTimers as $key => $timerItem) {
                    /** @var TimerStartStopRange $range */
                    $range = clone $timerItem[TimerConst::KEY_EVENT_LIST_RANGE];
                    if ($range->hasResultExist()) {
                        if ($limitInfos->index < 0) {
                            // The first entrie is the best result
                            $limitInfos->beginning = $range->getBeginning();
                            $limitInfos->ending = $range->getEnding();
                            $limitInfos->base = $limitInfos->beginning;
                            $limitInfos->index = $key;
                        } else {
                            if ($userCompareString($range, $limitInfos)) {
                                $limitInfos->beginning = $range->getBeginning();
                                $limitInfos->ending = $range->getEnding();
                                $limitInfos->base = $limitInfos->beginning;
                                $limitInfos->index = $key;
                            }
                        }
                    }
                }
                if (($limitInfos->index < 0) ||
                    (self::limitsAllowOneMoreLoop($loopLimiter, $count, $limitInfos->base, true) === false)
                ) {
                    break;
                }

                $listOfEvents[$count] = $listOfTimers[$limitInfos->index];
                $count++;
                [$timerSelectName, $timerFlexParameter] = self::extractSelectorAndTimer(
                    $listOfTimers[$limitInfos->index][TimerConst::KEY_EVENT_LIST_TIMER],
                    $getterSelectName,
                    $getterFlexParameter
                );

                $range = $timerResolver->nextActive(
                    $timerSelectName,
                    $limitInfos->ending,
                    $timerFlexParameter
                );
                if (($range->hasResultExist()) &&
                    ($timerResolver->isAllowedInRange(
                        $timerSelectName,
                        $range->getEnding(),
                        $timerFlexParameter)
                    )
                ) {

                    $listOfTimers[$limitInfos->index][TimerConst::KEY_EVENT_LIST_RANGE] = clone $range;
                    $listOfTimers[$limitInfos->index][TimerConst::KEY_EVENT_LIST_GAP] = abs(
                        ($range->getBeginning()->getTimestamp() - $range->getEnding()->getTimestamp()) / 60
                    );
                } else {
                    $count--;
                    unset($listOfEvents[$count]);
                    unset($listOfTimers[$limitInfos->index]);
                    if (empty($listOfTimers)) {
                        break;
                    }
                }

                self::reinitAboveLimitInfos($limitInfos, $limitInfos->base);

            }
        }
        return $listOfEvents;
    }

    /**
     * @param array $arguments
     * @param DateTime $basicDateTime
     * @param int $defaultMax
     * @return LoopLimiter
     * @throws TimerException
     */
    public static function getListRestrictions(
        array $arguments,
        DateTime $basicDateTime,
        $defaultMax = TimerConst::DEFAULT_MAX_COUNT
    ): LoopLimiter {
        $loopLimiter = new LoopLimiter();
        if (
            (isset($arguments[TimerConst::ARGUMENT_MAX_COUNT])) &&
            (($maxCount = (int)$arguments[TimerConst::ARGUMENT_MAX_COUNT]) > 0)
        ) {
            $loopLimiter->setMaxCount($maxCount);
        } else {
            $loopLimiter->setMaxCount($defaultMax);
        }
        if (isset($arguments[TimerConst::ARGUMENT_MAX_LATE])) {
            $myDate = DateTime::createFromFormat(
                TimerConst::DEFAULT_DATETIME_FORMAT,
                $arguments[TimerConst::ARGUMENT_MAX_LATE],
                $basicDateTime->getTimezone()
            );
            $loopLimiter->setMaxLate($myDate);
        } else {
            $loopLimiter->setMaxLate(null);
        }
        if ((isset($arguments[TimerConst::ARGUMENT_MAX_LATE])) ||
            ($loopLimiter->getMaxLate() === null)
        ) {
            $loopLimiter->setFlagMaxType(true);
        } else {
            $loopLimiter->setFlagMaxType(false);
        }
        if (isset($arguments[TimerConst::ARGUMENT_HOOK_CUSTOM_EVENT_COMPARE])) {
            $name = $arguments[TimerConst::ARGUMENT_HOOK_CUSTOM_EVENT_COMPARE];
            $method = explode('->', $name);
            if ((count($method) !== 2) ||
                (!method_exists($method[0], $method[1])) ||
                (!is_callable($method[0] . '::' . $method[1]))
            ) {
                throw new TimerException('Your method `$name` is not callable. Check the spelling and the syntax. ' .
                    ' (`namespace\className->staticMethodName`)',
                    1604857630
                );

            }
            $loopLimiter->setUserCompareFunction($method[0] . '::' . $method[1]);
        } else {
            $loopLimiter->setUserCompareFunction('');
        }

        return $loopLimiter;
    }

    /**
     * @param DateTime $timerEventZone
     * @param stdClass $limitInfos
     */
    protected static function reinitAboveLimitInfos(stdClass $limitInfos, DateTime $timerEventZone): void
    {
        $limitInfos->index = -1;
        $limitInfos->base = clone $timerEventZone;
        $limitInfos->beginning = clone $timerEventZone;
        $limitInfos->beginning->add(new DateInterval('P10001Y'));
        $limitInfos->ending = clone $limitInfos->beginning;
        $limitInfos->ending->add(new DateInterval('P10Y'));
    }

    /**
     * @param DateTime $timerEventZone
     * @param stdClass $limitInfos
     */
    protected static function reinitBelowLimitInfos(stdClass $limitInfos, DateTime $timerEventZone): void
    {
        $limitInfos->index = -1;
        $limitInfos->base = clone $timerEventZone;
        $limitInfos->beginning = clone $timerEventZone;
        $limitInfos->beginning->sub(new DateInterval('P10001Y'));
        $limitInfos->ending = clone $limitInfos->beginning;
        $limitInfos->ending->sub(new DateInterval('P10Y'));
    }

    /**
     * @param LoopLimiter $loopLimiter
     * @param int $count
     * @param stdClass $limitInfos
     * @return bool
     */
    protected static function limitsAllowOneMoreLoop(
        LoopLimiter $loopLimiter,
        int $count,
        DateTime $baseDate,
        $flagAbove
    ): bool {
        if ($loopLimiter->isFlagMaxType()) {
            return ($count < $loopLimiter->getMaxCount());
        } else {
            if ($flagAbove === true) {
                return ($baseDate <= $loopLimiter->getMaxLate());
            } else {
                return ($loopLimiter->getMaxLate() <= $baseDate);
            }
        }
    }

    // Call by magic String
    public static function compareForBelowList(TimerStartStopRange $range, $limitInfos)
    {
        return (($range->getEnding() > $limitInfos->ending) ||
            (
                ($range->getEnding() == $limitInfos->ending) &&
                ($range->getBeginning() < $limitInfos->beginning)
            )
        );
    }

    // Call by magic String
    public static function compareForAboveList(TimerStartStopRange $range, $limitInfos)
    {
        return (($range->getBeginning() < $limitInfos->beginning) ||
            (
                ($range->getBeginning() == $limitInfos->beginning) &&
                ($range->getEnding() > $limitInfos->ending)
            )
        );
    }

    /**
     * @param $item
     * @param string $getterSelectName
     * @param string $getterFlexParameter
     * @return array
     */
    protected static function extractSelectorAndTimer(
        $item,
        string $getterSelectName,
        string $getterFlexParameter
    ): array {
        if (is_array($item)) {
            $timerSelectName = $item[TimerConst::TIMER_FIELD_SELECT];
            $timerFlexParameterString = $item[TimerConst::TIMER_FIELD_FLEX_ACTIVE];
        } else {
            if ($item instanceof FileReference) {
                $timerSelectName = $item->getReferenceProperty(TimerConst::TIMER_FIELD_SELECT);
                $timerFlexParameterString = $item->getReferenceProperty(TimerConst::TIMER_FIELD_FLEX_ACTIVE);
            } else {
                $timerSelectName = $item->$getterSelectName();
                $timerFlexParameterString = $item->$getterFlexParameter();
            }
        }
        $timerFlexParameter = FlexFormUtility::flexformArrayFlatten(
            GeneralUtility::xml2array($timerFlexParameterString)
        );
//        $timerFlexParameter = array_merge(...$timerFlexParameter);
        return [$timerSelectName, $timerFlexParameter];
    }

}
