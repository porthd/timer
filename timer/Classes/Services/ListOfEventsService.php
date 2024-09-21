<?php

declare(strict_types=1);

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
use Exception;
use Porthd\Timer\Services\ListOfTimerService;
use Porthd\Ichschauweg\Utilities\FlexFormUtility;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Domain\Model\InternalFlow\LoopLimiter;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use stdClass;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
    protected const DEFAULT_MAX_GAP = 'P7D';

    /**
     * @param array<mixed> $eventsTimerList
     * @param DateTime $timerEventZone
     * @param LoopLimiter $loopLimiter
     * @return DateTime
     */
    public static function detectNextChangeListFromTimerList(
        array       $eventsTimerList,
        DateTime    $timerEventZone,
        LoopLimiter $loopLimiter
    ): DateTime
    {
        /** @var ListOfTimerService $timerResolver */
        $timerResolver = GeneralUtility::makeInstance(ListOfTimerService::class);
        if ($loopLimiter->isFlagReserve()) {
            return self::nextStartTimeForListOfEventsBelowStartTime(
                $timerEventZone,
                $eventsTimerList,
                $timerResolver,
                $loopLimiter
            );
        }
        return self::nextStartTimeForListOfEventsAboveStartTime(
            $timerEventZone,
            $eventsTimerList,
            $timerResolver,
            $loopLimiter
        );
    }

    /**
     * @param array<mixed> $eventsTimerList
     * @param DateTime $timerEventZone
     * @param LoopLimiter $loopLimiter
     * @return array<mixed>
     * @throws TimerException
     */
    public static function generateEventsListFromTimerList(
        array       $eventsTimerList,
        DateTime    $timerEventZone,
        LoopLimiter $loopLimiter
    ): array
    {
        /** @var ListOfTimerService $timerResolver */
        $timerResolver = GeneralUtility::makeInstance(ListOfTimerService::class);
        if ($loopLimiter->isFlagReserve()) {
            return self::listOfEventsBelowStartTime(
                $timerEventZone,
                $eventsTimerList,
                $timerResolver,
                $loopLimiter
            );
        }
        return self::listOfEventsAboveStartTime(
            $timerEventZone,
            $eventsTimerList,
            $timerResolver,
            $loopLimiter
        );
    }

    /**
     * @param DateTime $timerEventZone
     * @param array<mixed> $eventsTimerList
     * @param ListOfTimerService $timerResolver
     * @return array<mixed>
     */
    protected static function timerListBelowStartDate(
        DateTime           $timerEventZone,
        array              $eventsTimerList,
        ListOfTimerService $timerResolver
    ): array
    {
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
    protected static function timerListNextToBelowStartDate(
        DateTime           $timerEventZone,
        array              $eventsTimerList,
        ListOfTimerService $timerResolver
    ): array
    {
        $listOfTimers = [];
        [$getterSelectName, $getterFlexParameter] = self::generateGetterNamesForTimerFields();
        foreach ($eventsTimerList as $key => $item) {
            [$timerSelectName, $timerFlexParameter] = self::extractSelectorAndTimer(
                $item,
                $getterSelectName,
                $getterFlexParameter
            );

            /** @var TimerStartStopRange $range */
            $rawRange = $timerResolver->prevActive(
                $timerSelectName,
                $timerEventZone,
                $timerFlexParameter
            );
            /** @var TimerStartStopRange $range */
            $range = $timerResolver->nextActive(
                $timerSelectName,
                $rawRange->getEnding(),
                $timerFlexParameter
            );

            $flagAllowed = $timerResolver->isAllowedInRange(
                $timerSelectName,
                $timerEventZone,
                $timerFlexParameter
            );
            if (($range->hasResultExist()) &&
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
        DateTime           $timerEventZone,
        array              $eventsTimerList,
        ListOfTimerService $timerResolver
    ): array
    {
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
     * @return array<mixed>
     */
    protected static function timerListPrevToAboveStartDate(
        DateTime           $timerEventZone,
        array              $eventsTimerList,
        ListOfTimerService $timerResolver
    ): array
    {
        $listOfTimers = [];
        [$getterSelectName, $getterFlexParameter] = self::generateGetterNamesForTimerFields();
        foreach ($eventsTimerList as $key => $item) {
            [$timerSelectName, $timerFlexParameterList] = self::extractSelectorAndTimer(
                $item,
                $getterSelectName,
                $getterFlexParameter
            );
            /** @var TimerStartStopRange $range */
            $rawRange = $timerResolver->nextActive(
                $timerSelectName,
                $timerEventZone,
                $timerFlexParameterList
            );
            /** @var TimerStartStopRange $range */
            $range = $timerResolver->prevActive(
                $timerSelectName,
                $rawRange->getBeginning(),
                $timerFlexParameterList
            );
            $flagAllowed = $timerResolver->isAllowedInRange(
                $timerSelectName,
                $timerEventZone,
                $timerFlexParameterList
            );

            if (($range->hasResultExist()) &&
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
     * @return array<mixed>
     */
    protected static function listOfEventsBelowStartTime(
        DateTime           $timerEventZone,
        array              $eventsTimerList,
        ListOfTimerService $timerResolver,
        LoopLimiter        $loopLimiter
    ): array
    {
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
            if (!is_callable($userCompareString)) {
                throw new TimerException(
                    'The comparefunction `' . $userCompareString . '` is not callable in `listOfEventsBelowStartTime`. ' .
                    'Check your definition of TypoScript at the attribute `' . TimerConst::ARGUMENT_HOOK_CUSTOM_EVENT_COMPARE . '`. ' .
                    'Please make a screenshot and inform the webmaster.',
                    1673162230
                );
            }
            while ((
            $loopLimiter->getFlagMaxCount() ?
                ($count <= $loopLimiter->getMaxCount()) :
                (true)
            )) {
                foreach ($listOfTimers as $key => $timerItem) {
                    /** @var TimerStartStopRange $range */
                    $range = clone $timerItem[self::KEY_EVENT_LIST_RANGE];
                    if ($range->hasResultExist()) {
                        if ($limitInfos->index < 0) {
                            // The first entry is the best result
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
                    (
                    $loopLimiter->getFlagMaxCount() ?
                        (true) :
                        ($limitInfos->base <= $loopLimiter->getMaxLate())
                    ) ||
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
     * @param \Porthd\Timer\Services\ListOfTimerService $timerResolver
     * @param LoopLimiter $loopLimiter
     * @return DateTime
     */
    protected static function nextStartTimeForListOfEventsBelowStartTime(
        DateTime           $timerEventZone,
        array              $eventsTimerList,
        ListOfTimerService $timerResolver,
        LoopLimiter        $loopLimiter
    ): DateTime
    {
        $listOfTimers = self::timerListNextToBelowStartDate(
            $timerEventZone,
            $eventsTimerList,
            $timerResolver
        );
        if (empty($listOfTimers)) {
            return $timerEventZone;
        }
        $result = clone $timerEventZone;
        $flag = true;
        foreach ($listOfTimers as $key => $timerItem) {
            $beginning = $timerItem['range']->getBeginning();
            if ($beginning > $timerEventZone) {
                if ($flag) {
                    $result = $beginning;
                    $flag = false;
                } else {
                    if ($beginning < $result) {
                        $result = $beginning;
                    }
                }
            }
        }
        return clone $result;
    }

    /**
     * @param DateTime $timerEventZone
     * @param array<mixed> $eventsTimerList
     * @param \Porthd\Timer\Services\ListOfTimerService $timerResolver
     * @param LoopLimiter $loopLimiter
     * @return DateTime
     */
    protected static function nextStartTimeForListOfEventsAboveStartTime(
        DateTime           $timerEventZone,
        array              $eventsTimerList,
        ListOfTimerService $timerResolver,
        LoopLimiter        $loopLimiter
    ): DateTime
    {
        $listOfTimers = self::timerListPrevToAboveStartDate(
            $timerEventZone,
            $eventsTimerList,
            $timerResolver
        );
        if (empty($listOfTimers)) {
            return $timerEventZone;
        }
        $result = $timerEventZone;
        $flag = true;
        foreach ($listOfTimers as $key => $timerItem) {
            $ending = $timerItem['range']->getEnding();
            if ($ending < $timerEventZone) {
                if ($flag) {
                    $result = $ending;
                    $flag = false;
                } else {
                    if ($ending > $result) {
                        $result = $ending;
                    }
                }
            }
        }
        return clone $result;
    }

    /**
     * @param DateTime $timerEventZone
     * @param array<mixed> $eventsTimerList
     * @param ListOfTimerService $timerResolver
     * @param LoopLimiter $loopLimiter
     * @return array<mixed>
     */
    protected static function listOfEventsAboveStartTime(
        DateTime           $timerEventZone,
        array              $eventsTimerList,
        ListOfTimerService $timerResolver,
        LoopLimiter        $loopLimiter
    ): array
    {
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
            if (!is_callable($userCompareString)) {
                throw new TimerException(
                    'The comparefunction `' . $userCompareString . '` is not callable in `listOfEventsAboveStartTime`. ' .
                    'Check your definition of TypoScript at the attribute `' . TimerConst::ARGUMENT_HOOK_CUSTOM_EVENT_COMPARE . '`. ' .
                    'Please make a screenshot and inform the webmaster.',
                    1673162538
                );
            }
            while ((
            $loopLimiter->getFlagMaxCount() ?
                ($count <= $loopLimiter->getMaxCount()) :
                (true)
            )) {
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
                if (
                    (
                    $loopLimiter->getFlagMaxCount() ?
                        (false) :
                        ($loopLimiter->getMaxLate() <= $limitInfos->base)
                    ) ||
                    ($limitInfos->index < 0) ||
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
     * Help to rebuild some arguments for SortListQueryProcessor and for RangeListQueryProcessor
     *
     * @param ContentObjectRenderer $cObj
     * @param array<mixed> $arguments
     * @param LoopLimiter $loopLimiter
     * @return void
     */
    public static function getDatetimeRestrictions(
        ContentObjectRenderer $cObj,
        array                 $arguments,
        LoopLimiter           $loopLimiter
    )
    {
        $dateTimeFormat = $cObj->stdWrapValue(
            TimerConst::ARGUMENT_DATETIME_FORMAT,
            $arguments,
            TimerInterface::TIMER_FORMAT_DATETIME
        );
        $loopLimiter->setDatetimeFormat($dateTimeFormat);
        $flagRevers = $cObj->stdWrapValue(TimerConst::ARGUMENT_REVERSE, $arguments, false);
        $loopLimiter->setFlagReserve($flagRevers);
    }

    /**
     * @param ContentObjectRenderer $cObj
     * @param array<mixed> $arguments
     * @param LoopLimiter $loopLimiter
     * @param DateTime $basicDateTime
     * @return LoopLimiter
     * @throws TimerException
     */
    public static function getListRestrictions(
        ContentObjectRenderer $cObj,
        array                 $arguments,
        LoopLimiter           $loopLimiter,
        DateTime              $basicDateTime
    ): LoopLimiter
    {
        /**
         * 1. detect the existence of the three variable
         * 2. define the value or default value for maxCount
         * 3. define the value or default value for maxGap and define based on this and the reverse-information the default maxLate-Value
         * 4. define the value or default value for maxLate
         * 5. define the flag, if the maxCount or the maxLate-Value should be used
         * 6. define a custom compare-funktion, which will be used to generate the sorted informations
         */
        $flagMaxCount = (
            (array_key_exists(TimerConst::ARGUMENT_MAX_COUNT, $arguments)) ||
            (array_key_exists(TimerConst::ARGUMENT_MAX_COUNT . '.', $arguments))
        );
        $flagMaxGap = (
            (array_key_exists(TimerConst::ARGUMENT_MAX_GAP, $arguments)) ||
            (array_key_exists(TimerConst::ARGUMENT_MAX_GAP . '.', $arguments))
        );
        $flagMaxLate = (
            (array_key_exists(TimerConst::ARGUMENT_MAX_LATE, $arguments)) ||
            (array_key_exists(TimerConst::ARGUMENT_MAX_LATE . '.', $arguments))
        );

        $maxCount = $cObj->stdWrapValue(TimerConst::ARGUMENT_MAX_COUNT, $arguments, self::DEFAULT_MAX_COUNT);
        $loopLimiter->setMaxCount($maxCount);

        $flagMaxCountFinal = (
            ($flagMaxCount && ($maxCount > 0)) ||
            ((!$flagMaxCount) && (!$flagMaxGap) && (!$flagMaxLate))
        );
        $loopLimiter->setFlagMaxCount($flagMaxCountFinal);

        $defaultLate = clone $basicDateTime;
        $defaultMaxGapString = $cObj->stdWrapValue(
            TimerConst::ARGUMENT_MAX_GAP,
            $arguments,
            self::DEFAULT_MAX_GAP
        );
        if ($loopLimiter->getFlagReserve()) {
            $defaultLate->sub(new DateInterval($defaultMaxGapString));
        } else {
            $defaultLate->add(new DateInterval($defaultMaxGapString));
        }

        $maxLateString = $cObj->stdWrapValue(
            TimerConst::ARGUMENT_MAX_LATE,
            $arguments,
            $defaultLate->format($loopLimiter->getDatetimeFormat())
        );
        $myDate = DateTime::createFromFormat(
            $loopLimiter->getDatetimeFormat(),
            $maxLateString,
            $basicDateTime->getTimezone()
        );
        if ($myDate === false) {
            throw new TimerException(
                'The date-string `' . $arguments[TimerConst::ARGUMENT_MAX_LATE] . ' or ' .
                print_r($arguments[TimerConst::ARGUMENT_MAX_LATE] . '.', true) .
                '` could not converted to a datetime-Object. ' .
                'Check your format of date-time (should be: `' . $loopLimiter->getDatetimeFormat() . '`). ',
                1648555534
            );
        }
        $loopLimiter->setMaxLate($myDate);

        if (array_key_exists(TimerConst::ARGUMENT_HOOK_CUSTOM_EVENT_COMPARE, $arguments)) {
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
        int         $count,
        DateTime    $baseDate,
        bool        $flagAbove
    ): bool
    {
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
    ): array
    {
        if (is_array($item)) {
            $timerSelectName = $item[TimerConst::TIMER_FIELD_SELECTOR];
            $timerFlexParameterString = $item[TimerConst::TIMER_FIELD_FLEX_ACTIVE];
        } else {
            if ($item instanceof FileReference) {
                $timerSelectName = $item->getReferenceProperty(TimerConst::TIMER_FIELD_SELECTOR);
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
                        str_replace('_', ' ', TimerConst::TIMER_FIELD_SELECTOR)
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
