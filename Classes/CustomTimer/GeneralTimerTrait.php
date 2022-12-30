<?php

namespace Porthd\Timer\CustomTimer;

use DateInterval;
use DateTime;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\TcaUtility;

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



/**
 * @package DailyTimer
 */
trait GeneralTimerTrait
{
    /**
     * @param array<mixed> $list
     * @param array<mixed> $params
     * @return int
     */
    protected function countParamsInList(array $list, array $params): int
    {
        $count = 0;
        foreach ($list as $key) {
            if (isset($params[$key])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateUltimate(array $params = []): bool
    {
        $flag = (!empty($params[TimerInterface::ARG_ULTIMATE_RANGE_BEGINN]));
        $flag = (
            $flag && (
                false !== date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $params[TimerInterface::ARG_ULTIMATE_RANGE_BEGINN]
                )
            )
        );
        $flag = $flag && (!empty($params[TimerInterface::ARG_ULTIMATE_RANGE_END]));
        return ($flag && (
            false !== date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $params[TimerInterface::ARG_ULTIMATE_RANGE_END]
            )
        )
        );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateFlagZone(array $params = []): bool
    {
        return((isset($params[TimerInterface::ARG_USE_ACTIVE_TIMEZONE])) &&
            (
                !is_array($params[TimerInterface::ARG_USE_ACTIVE_TIMEZONE]) &&
                !is_object($params[TimerInterface::ARG_USE_ACTIVE_TIMEZONE]) &&
                ($params[TimerInterface::ARG_USE_ACTIVE_TIMEZONE] !== null)
            ) &&(in_array(
                $params[TimerInterface::ARG_USE_ACTIVE_TIMEZONE],
                TimerInterface::ARGVALUE_USE_ACTIVE_TIMEZONE,
                true
            ))
        );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateZone(array $params = []): bool
    {
        return isset($params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT]) &&
            TcaUtility::isTimeZoneInList(
                $params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT]
            );
    }

    /**
     * @param TimerStartStopRange $nextRange
     * @param array<mixed> $params
     * @param DateTime $dateBelowNextActive
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function validateUltimateRangeForNextRange(
        TimerStartStopRange $nextRange,
        array $params,
        DateTime $dateBelowNextActive
    ): TimerStartStopRange {
        if ((!$this->isAllowedInRange($nextRange->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($nextRange->getEnding(), $params))
        ) {
            // fail-cases [n = next, u = ultimate]
            // 0. ub < ue <= nb < ne
            // 1. ub <= nb <= ue < ne => no more next allowed
            // 2. nb < ub < ue < ne => special condition of 2.a or 2.b
            // 2.a nb < ub < ue <= ne
            // 2.b nb <= ub < ue < ne
            // 3. nb < ub =< ne < ue  => start
            // 4. nb < ne <= ub < ue  => next allowed beginning at (ub - 1second)
            $nextEndingFormat = $nextRange->getEnding()->format(self::TIMER_FORMAT_DATETIME);
            $nextBeginningFormat = $nextRange->getBeginning()->format(self::TIMER_FORMAT_DATETIME);
            if (
                ($nextBeginningFormat >= $params[self::ARG_ULTIMATE_RANGE_END]) || // case 0
                (
                    (
                        ($nextBeginningFormat <= $params[self::ARG_ULTIMATE_RANGE_BEGINN]) &&
                        ($nextEndingFormat > $params[self::ARG_ULTIMATE_RANGE_END])
                    ) ||
                    (
                        ($nextBeginningFormat < $params[self::ARG_ULTIMATE_RANGE_BEGINN]) &&
                        ($nextEndingFormat >= $params[self::ARG_ULTIMATE_RANGE_END])
                    )
                ) || // case 2.a, 2.b, 2
                (
                    ($nextBeginningFormat >= $params[self::ARG_ULTIMATE_RANGE_BEGINN]) &&
                    ($nextBeginningFormat <= $params[self::ARG_ULTIMATE_RANGE_END]) &&
                    ($nextEndingFormat > $params[self::ARG_ULTIMATE_RANGE_END])
                ) // case 1
            ) {
                $nextRange->failOnlyPrevActive($dateBelowNextActive);
            } else {
                if (
                    ($nextEndingFormat <= $params[self::ARG_ULTIMATE_RANGE_BEGINN]) // case 1
                ) { // case 4
                    $testBegin = DateTime::createFromFormat(
                        self::TIMER_FORMAT_DATETIME,
                        $params[self::ARG_ULTIMATE_RANGE_BEGINN]
                    );
                    $testBegin->sub(new DateInterval('PT1S'));
                    $nextRange = $this->nextActive($testBegin, $params);
                } else {
                    if (
                        ($nextBeginningFormat < $params[self::ARG_ULTIMATE_RANGE_BEGINN]) &&
                        ($nextEndingFormat < $params[self::ARG_ULTIMATE_RANGE_END]) &&
                        ($nextEndingFormat >= $params[self::ARG_ULTIMATE_RANGE_BEGINN])
                    ) { // case 3
                        $testNextRange = $this->nextActive($nextRange->getEnding(), $params);
                        if (!$testNextRange->hasResultExist()) { // correct the recursive result
                            $nextRange->failOnlyPrevActive($dateBelowNextActive);
                        }
                    } else { // case something forgotten ?
                        $nextRange->failOnlyPrevActive($dateBelowNextActive);
                    }
                }
            }
        }
        return clone $nextRange;
    }

    /**
     * @param TimerStartStopRange $prevRange
     * @param array<mixed> $params
     * @param DateTime $dateAbovePrevActive
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function validateUltimateRangeForPrevRange(
        TimerStartStopRange $prevRange,
        array $params,
        DateTime $dateAbovePrevActive
    ): TimerStartStopRange {
        // `isAllowedInRange` is part of the interface for the timer
        if ((!$this->isAllowedInRange($prevRange->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($prevRange->getEnding(), $params))
        ) {
            // fail-cases [n = prev, u = ultimate]
            // 0. ub < ue <= pb < pe  => prev allowed beginning at (ue + 1second)
            // 1. ub <= pb <= ue < pe => preview allowed at pb
            // 2. pb < ub < ue < pe => special condition of 2.a or 2.b
            // 2.a pb < ub < ue <= pe
            // 2.b pb <= ub < ue < pe
            // 3. pb < ub =< pe <= ue  => no more prev allowed
            // 4. pb < pe <= ub < ue
            $prevEndingFormat = $prevRange->getEnding()->format(self::TIMER_FORMAT_DATETIME);
            $prevBeginningFormat = $prevRange->getBeginning()->format(self::TIMER_FORMAT_DATETIME);
            if (
                ($prevEndingFormat >= $params[self::ARG_ULTIMATE_RANGE_BEGINN]) || // case 4
                (
                    (
                        ($prevBeginningFormat <= $params[self::ARG_ULTIMATE_RANGE_BEGINN]) &&
                        ($prevEndingFormat > $params[self::ARG_ULTIMATE_RANGE_END])
                    ) ||
                    (
                        ($prevBeginningFormat < $params[self::ARG_ULTIMATE_RANGE_BEGINN]) &&
                        ($prevEndingFormat >= $params[self::ARG_ULTIMATE_RANGE_END])
                    )
                ) || // case 2.a, 2.b, 2
                (
                    ($prevBeginningFormat < $params[self::ARG_ULTIMATE_RANGE_BEGINN]) &&
                    ($prevEndingFormat <= $params[self::ARG_ULTIMATE_RANGE_END]) &&
                    ($prevEndingFormat >= $params[self::ARG_ULTIMATE_RANGE_BEGINN])
                ) // case 3
            ) {
                $prevRange->failOnlyNextActive($dateAbovePrevActive);
            } else {
                if (
                    ($prevBeginningFormat >= $params[self::ARG_ULTIMATE_RANGE_END]) // case 1
                ) { // case 0
                    $testBegin = DateTime::createFromFormat(
                        self::TIMER_FORMAT_DATETIME,
                        $params[self::ARG_ULTIMATE_RANGE_END]
                    );
                    $testBegin->add(new DateInterval('PT1S'));
                    // `prevActive` is part of the interface for the timer
                    $prevRange = $this->prevActive($testBegin, $params);
                } else {
                    if (
                        ($prevEndingFormat > $params[self::ARG_ULTIMATE_RANGE_END]) &&
                        ($prevBeginningFormat < $params[self::ARG_ULTIMATE_RANGE_END]) &&
                        ($prevBeginningFormat >= $params[self::ARG_ULTIMATE_RANGE_BEGINN])
                    ) { // case 1
                        // `prevActive` is part of the interface for the timer
                        $testPrevRange = $this->prevActive($prevRange->getBeginning(), $params);
                        if (!$testPrevRange->hasResultExist()) { // correct the recursive result
                            $prevRange->failOnlyPrevActive($dateAbovePrevActive);
                        }
                    } else { // case something forgotten ?
                        $prevRange->failOnlyNextActive($dateAbovePrevActive);
                    }
                }
            }
        }
        return clone $prevRange;
    }
}
