<?php

namespace Porthd\Timer\Services;

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
use Porthd\Ichschauweg\Utilities\FlexFormUtility;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Domain\Model\InternalFlow\LoopLimiter;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use stdClass;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ListOfEventsService
{
    protected const ARGUMENT_START_DATETIME = 'start';
    protected const ARGUMENT_STOP_DATETIME = 'stop';
    protected const SUBKEY_RANGE = 'range';
    protected const SUBKEY_GAP_MIN = 'gapMin';
    protected const ARGUMENT_KEY = 'key';

    protected const KEY_EVENT_LIST_GAP = 'gap';
    protected const KEY_EVENT_LIST_RANGE = 'range';
    protected const KEY_EVENT_LIST_TIMER = 'timer';

    protected const DEFAULT_MAX_COUNT = 25;

    /**
     * @param array<mixed> $eventsTimerList
     * @param DateTime $timerEventZone
     * @param LoopLimiter $loopLimiter
     * @param bool $flagReverse
     * @param int $maxCount
     * @return array<mixed>
     */
    public static function generateEventsListFromTimerList(
        array $eventsTimerList,
        DateTime $timerEventZone,
        LoopLimiter $loopLimiter,
        bool $flagReverse = false,
        int $maxCount = TimerConst::SAVE_LIMIT_MAX_EVENTS
    ): array {
        /** @var ListOfTimerService $timerResolver */
        $timerResolver = GeneralUtility::makeInstance(ListOfTimerService::class);
        if ($flagReverse) {
            return self::listOfEventsBelowStartTime(
                $timerEventZone,
                $eventsTimerList,
                $timerResolver,
                $loopLimiter,
                $maxCount
            );
        }
        return self::listOfEventsAboveStartTime(
            $timerEventZone,
            $eventsTimerList,
            $timerResolver,
            $loopLimiter,
            $maxCount
        );
    }

    /**
     * @param DateTime $timerEventZone
     * @param array<mixed> $eventsTimerList
     * @param ListOfTimerService $timerResolver
     * @return array<mixed>
     */
    protected static function timerListBelowStartDate(
        DateTime $timerEventZone,
        array $eventsTimerList,
        ListOfTimerService $timerResolver
    ): array {
        $listOfTimers = [];
        [$getterSelectName, $getterFlexParameter] = self::generateGetterNamesForTimerFields();
        foreach ($eventsTimerList as $key => $item) {
            [$timerSelectName, $timerFlexParameter] = self::extractSelectorAndTimer(
                $item,
                $getterSelectName,
                $getterFlexParameter
            );

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
                $timerItem[self::KEY_EVENT_LIST_TIMER] = $item;
                $timerItem[self::KEY_EVENT_LIST_RANGE] = clone $range;
                $timerItem[self::KEY_EVENT_LIST_GAP] = ceil(
                    abs(
                        ($range->getBeginning()->getTimestamp() - $range->getEnding()->getTimestamp()) / 60
                    )
                );
                $listOfTimers[$key] = $timerItem;
            }
        }
        return $listOfTimers;
    }

    /**
     * @param DateTime $timerEventZone
     * @param array<mixed> $eventsTimerList
     * @param ListOfTimerService $timerResolver
     * @return array<mixed>
     */
    protected static function timerListAboveStartDate(
        DateTime $timerEventZone,
        array $eventsTimerList,
        ListOfTimerService $timerResolver
    ): array {
        $listOfTimers = [];
        [$getterSelectName, $getterFlexParameter] = self::generateGetterNamesForTimerFields();
        foreach ($eventsTimerList as $key => $item) {
            [$timerSelectName, $timerFlexParameterList] = self::extractSelectorAndTimer(
                $item,
                $getterSelectName,
                $getterFlexParameter
            );
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
                $timerItem[self::KEY_EVENT_LIST_TIMER] = $item;
                $timerItem[self::KEY_EVENT_LIST_RANGE] = clone $range;
                $timerItem[self::KEY_EVENT_LIST_GAP] = ceil(
                    abs(
                        ($range->getBeginning()->getTimestamp() - $range->getEnding()->getTimestamp()) / 60
                    )
                );
                $listOfTimers[$key] = $timerItem;
                unset($range);
            }
        }
        return $listOfTimers;
    }

    /**
     * @param DateTime $timerEventZone
     * @param array<mixed> $eventsTimerList
     * @param ListOfTimerService $timerResolver
     * @param LoopLimiter $loopLimiter
     * @param int $maxCount
     * @return array<mixed>
     */
    protected static function listOfEventsBelowStartTime(
        DateTime $timerEventZone,
        array $eventsTimerList,
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
        [$getterSelectName, $getterFlexParameter] = self::generateGetterNamesForTimerFields();

        $listOfEvents = [];
        if (!empty($listOfTimers)) {
            $count = 0;
            $userCompareString = (
                (empty($loopLimiter->getUserCompareFunction())) ?
                    'Porthd\Timer\Services\ListOfEventsService::compareForBelowList' :
                    $loopLimiter->getUserCompareFunction()
            );
            while ($count <= $maxCount) {
                foreach ($listOfTimers as $key => $timerItem) {
                    /** @var TimerStartStopRange $range */
                    $range = clone $timerItem[self::KEY_EVENT_LIST_RANGE];
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
                    $listOfTimers[$limitInfos->index][self::KEY_EVENT_LIST_TIMER],
                    $getterSelectName,
                    $getterFlexParameter
                );

                $range = $timerResolver->prevActive(
                    $timerSelectName,
                    $limitInfos->beginning,
                    $timerFlexParameter
                );
                if (($range->hasResultExist()) &&
                    (
                        $timerResolver->isAllowedInRange(
                            $timerSelectName,
                            $range->getBeginning(),
                            $timerFlexParameter
                        )
                    )
                ) {
                    $listOfTimers[$limitInfos->index][self::KEY_EVENT_LIST_RANGE] = clone $range;
                    $listOfTimers[$limitInfos->index][self::KEY_EVENT_LIST_GAP] = abs(
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
     * @param array<mixed> $eventsTimerList
     * @param ListOfTimerService $timerResolver
     * @param LoopLimiter $loopLimiter
     * @param int $maxCount
     * @return array<mixed>
     */
    protected static function listOfEventsAboveStartTime(
        DateTime $timerEventZone,
        array $eventsTimerList,
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
        [$getterSelectName, $getterFlexParameter] = self::generateGetterNamesForTimerFields();
        $listOfEvents = [];
        if (!empty($listOfTimers)) {
            $count = 0;
            $userCompareString = (
                (empty($loopLimiter->getUserCompareFunction())) ?
                    'Porthd\Timer\Services\ListOfEventsService::compareForAboveList' :
                    $loopLimiter->getUserCompareFunction()
            );
            while ($count <= $maxCount) {
                foreach ($listOfTimers as $key => $timerItem) {
                    /** @var TimerStartStopRange $range */
                    $range = clone $timerItem[self::KEY_EVENT_LIST_RANGE];
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
                    $listOfTimers[$limitInfos->index][self::KEY_EVENT_LIST_TIMER],
                    $getterSelectName,
                    $getterFlexParameter
                );

                $range = $timerResolver->nextActive(
                    $timerSelectName,
                    $limitInfos->ending,
                    $timerFlexParameter
                );
                if (($range->hasResultExist()) &&
                    (
                        $timerResolver->isAllowedInRange(
                            $timerSelectName,
                            $range->getEnding(),
                            $timerFlexParameter
                        )
                    )
                ) {
                    $listOfTimers[$limitInfos->index][self::KEY_EVENT_LIST_RANGE] = clone $range;
                    $listOfTimers[$limitInfos->index][self::KEY_EVENT_LIST_GAP] = abs(
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
     * @param array<mixed> $arguments
     * @param DateTime $basicDateTime
     * @param int $defaultMax
     * @return LoopLimiter
     * @throws TimerException
     */
    public static function getListRestrictions(
        array $arguments,
        DateTime $basicDateTime,
        $defaultMax = self::DEFAULT_MAX_COUNT
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
                TimerInterface::TIMER_FORMAT_DATETIME,
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
                throw new TimerException(
                    'Your method `$name` is not callable. Check the spelling and the syntax. ' .
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
     * @param DateTime $baseDate
     * @param bool $flagAbove
     * @return bool
     */
    protected static function limitsAllowOneMoreLoop(
        LoopLimiter $loopLimiter,
        int $count,
        DateTime $baseDate,
        bool $flagAbove
    ): bool {
        if ($loopLimiter->isFlagMaxType()) {
            return ($count < $loopLimiter->getMaxCount());
        }
        if ($flagAbove === true) {
            return ($baseDate <= $loopLimiter->getMaxLate());
        }
        return ($loopLimiter->getMaxLate() <= $baseDate);
    }

    // Call by magic String

    /**
     * only for internal use, because the stdClass for $limitInfos has some special definitions, which are needed
     *
     * @param TimerStartStopRange $range
     * @param object $limitInfos
     * @return bool
     */
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

    /**
     * only for internal use, because the stdClass for $limitInfos has some special definitions, which are needed
     *
     * @param TimerStartStopRange $range
     * @param object $limitInfos
     * @return bool
     */
    public static function compareForAboveList(TimerStartStopRange $range, $limitInfos): bool
    {
        return (($range->getBeginning() < $limitInfos->beginning) ||
            (
                ($range->getBeginning() == $limitInfos->beginning) &&
                ($range->getEnding() > $limitInfos->ending)
            )
        );
    }

    /**
     * @param mixed $item
     * @param string $getterSelectName
     * @param string $getterFlexParameter
     * @return array<mixed>
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
        return [$timerSelectName, $timerFlexParameter];
    }

    /**
     * @return string[]
     */
    protected static function generateGetterNamesForTimerFields(): array
    {
        $getterSelectName = 'get' . ucfirst(
            str_replace(
                ' ',
                '',
                ucwords(
                    str_replace('_', ' ', TimerConst::TIMER_FIELD_SELECT)
                )
            )
        );
        $getterFlexParameter = 'get' . ucfirst(
            str_replace(
                ' ',
                '',
                ucwords(
                    str_replace('_', ' ', TimerConst::TIMER_FIELD_FLEX_ACTIVE)
                )
            )
        );
        return [$getterSelectName, $getterFlexParameter];
    }
}
