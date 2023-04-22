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
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\GeneralTimerUtility;

class WeekdayInMonthTimer implements TimerInterface
{
    use GeneralTimerTrait;

    public const TIMER_NAME = 'txTimerWeekdayInMonth';
    protected const ARG_REQ_START_TIME = 'startTimeSeconds';
    protected const ARG_REQ_START_TIME_MIN = 0;
    protected const ARG_REQ_START_TIME_MAX = 86400;
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_REQ_DURMIN_MIN = -1439;
    protected const ARG_REQ_DURMIN_FORBIDDEN = 0;
    protected const ARG_REQ_DURMIN_MAX = 1439;
    protected const ARG_NTH_WEEKDAY_IN_MONTH = 'nthWeekdayInMonth';
    protected const ARG_NTH_WEEKDAY_IN_MONTH_MIN = 1;
    protected const ARG_NTH_WEEKDAY_IN_MONTH_MAX = 31;
    protected const ARG_ACTIVE_WEEKDAY = 'activeWeekday';
    protected const ARG_ACTIVE_WEEKDAY_ALL = 127;
    protected const ARG_ACTIVE_MONTH = 'activeMonth';
    protected const ARG_ACTIVE_MONTH_ALL = 2047;

    protected const MAX_COUNT_NEXT_PREV_CALCS = 200;

    protected const ARG_START_COUNT_AT_END = 'startCountAtEnd';
    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/WeekdayInMonthTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,

        self::ARG_REQ_START_TIME,
        self::ARG_REQ_DURATION_MINUTES,
        self::ARG_NTH_WEEKDAY_IN_MONTH,
        self::ARG_ACTIVE_WEEKDAY,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_START_COUNT_AT_END,
        self::ARG_ACTIVE_MONTH,
    ];

    /**
     * @var TimerStartStopRange|null
     */
    protected $lastIsActiveResult;

    /**
     * @var int|null
     */
    protected $lastIsActiveTimestamp;

    /**
     * @var array<mixed>
     */
    protected $lastIsActiveParams = [];

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
     *
     * @return array<mixed>
     */
    public static function getSelectorItem(): array
    {
        return [
            TimerConst::TCA_ITEMS_LABEL => 'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerWeekdayInMonth.select.name',
            TimerConst::TCA_ITEMS_VALUE => self::TIMER_NAME,
        ];
    }

    /**
     * tested 20221009
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
     * tested general 20221115
     * tested special 20221115
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
        $countRequired = $this->validateArguments($params);
        $flag = $flag && ($countRequired === count(self::ARG_REQ_LIST));
        $flag = $flag && $this->validateNthWeekdayInMonth($params);
        $flag = $flag && $this->validateStartTime($params);
        $flag = $flag && $this->validateDurationMinutes($params);
        $flag = $flag && $this->validateActiveWeekday($params);
        $flag = $flag && $this->validateActiveMonth($params);

        $countOptions = $this->validateOptional($params);
        return $flag && ($countOptions >= 0) &&
            ($countOptions <= count(self::ARG_OPT_LIST));
    }


    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateStartTime(array $params = []): bool
    {
        $number = (int)$params[self::ARG_REQ_START_TIME];
        return (($number - (int)$params[self::ARG_REQ_START_TIME]) === 0) &&
            ($number >= self::ARG_REQ_START_TIME_MIN) && ($number < self::ARG_REQ_START_TIME_MAX);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateNthWeekdayInMonth(array $params = []): bool
    {
        $number = (int)$params[self::ARG_NTH_WEEKDAY_IN_MONTH];
        return (($number - $params[self::ARG_NTH_WEEKDAY_IN_MONTH]) === 0) &&
            (($number & self::ARG_NTH_WEEKDAY_IN_MONTH_MAX) > 0);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateActiveWeekday(array $params = []): bool
    {
        $number = (int)$params[self::ARG_ACTIVE_WEEKDAY];
        return (($number - $params[self::ARG_ACTIVE_WEEKDAY]) === 0) &&
            (($number & self::ARG_ACTIVE_WEEKDAY_ALL) > 0);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateActiveMonth(array $params = []): bool
    {
        $value = (
        (array_key_exists(self::ARG_ACTIVE_MONTH, $params)) ?
            $params[self::ARG_ACTIVE_MONTH] :
            self::ARG_ACTIVE_MONTH_ALL
        );
        $number = (int)$value;
        return (($number - $value) === 0) &&
            (($number & self::ARG_ACTIVE_MONTH_ALL) > 0);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return int
     */
    public function validateArguments(array $params = []): int
    {
        return $this->countParamsInList(self::ARG_REQ_LIST, $params);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateDurationMinutes(array $params = []): bool
    {
        if (!array_key_exists(self::ARG_REQ_DURATION_MINUTES, $params)) {
            return false;
        }
        $number = (int)($params[self::ARG_REQ_DURATION_MINUTES] ?: 0); // what will happen with float
        $floatNumber = (float)($params[self::ARG_REQ_DURATION_MINUTES] ?: 0);
        $flagCheck = ($number - $floatNumber == 0);
        if (is_string($params[self::ARG_REQ_DURATION_MINUTES])) {
            $flagCheck = (bool)preg_match('/^\d+$/', $params[self::ARG_REQ_DURATION_MINUTES]);
        }
        return (
            ($flagCheck) &&
            ($number >= self::ARG_REQ_DURMIN_MIN) &&
            ($number !== self::ARG_REQ_DURMIN_FORBIDDEN) &&
            ($number <= self::ARG_REQ_DURMIN_MAX)
        );
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
     * tested: 20221006
     *
     * check, if a date is part of an daily range, which has das defined start-position with the defined attributes (weekday, position in month, allowed month).
     * example. "every first tuesday in may beginning ad 22:00 for 4 hours." The date wendesday 4.5.2022 01:00 is part of an allowed range,
     * because the start of the range is the tuesday 3.5.2022 at 22:00 and the by the startpoint allowed range will end at 4.5.2022 02:00.
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

        $durationMinutes = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        $allowedWeekdays = (int)($params[self::ARG_ACTIVE_WEEKDAY] ?? 127);
        $allowedMonths = (int)(
        (array_key_exists(self::ARG_ACTIVE_MONTH, $params)) ?
            $params[self::ARG_ACTIVE_MONTH] :
            self::ARG_ACTIVE_MONTH_ALL
        );
        $allowedNthDay = (int)($params[self::ARG_NTH_WEEKDAY_IN_MONTH] ?? 31);
        $reverseNthDay = (array_key_exists(self::ARG_START_COUNT_AT_END, $params) &&
            ($params[self::ARG_START_COUNT_AT_END] !== false));
        $startTime = (int)$params[self::ARG_REQ_START_TIME];
        $rangeStartRelativeToDate = clone $dateLikeEventZone;
        $rangeStartRelativeToDate->setTime(
            (floor($startTime / 3600) % 24),
            (floor($startTime / 60) % 60),
            (floor($startTime) % 60)
        );
        $rangeStopRelativeToDate = clone $rangeStartRelativeToDate;

        $flagRangeOtherDay = false;
        $flagCheckMonthOtherDay = false;
        $flagCheckAllowedOtherDay = false;
        $flagNumerberOfDayInMonthOtherDay = false;
        if ($durationMinutes > 0) {
            $flagCheckMonth = (
                (2 ** ($rangeStartRelativeToDate->format('n') - 1)) & $allowedMonths
            );
            $flagAllowedWeekday = (
                (2 ** $rangeStartRelativeToDate->format('w')) & $allowedWeekdays
            );
            if ($reverseNthDay) {
                $numberOfDay = ((int)$rangeStartRelativeToDate->format('t') - (int)$rangeStartRelativeToDate->format('j') + 1);
            } else {
                $numberOfDay = ($rangeStartRelativeToDate->format('j'));
            }

            $rangeStopRelativeToDate->add(new DateInterval('PT' . $durationMinutes . 'M'));
            if ($rangeStopRelativeToDate->format('j') !== $dateLikeEventZone->format('j')) {
                $rangeStartBefore = clone $rangeStartRelativeToDate;
                $rangeStartBefore->sub(new DateInterval('P1D'));
                $rangeStopBefore = clone $rangeStopRelativeToDate;
                $rangeStopBefore->sub(new DateInterval('P1D'));
                $flagRangeOtherDay = (
                    ($rangeStartBefore <= $dateLikeEventZone) &&
                    ($dateLikeEventZone <= $rangeStopBefore)
                );
                if ($flagRangeOtherDay) {
                    $flagCheckMonthOtherDay = (
                        (2 ** ($rangeStartBefore->format('n') - 1)) & $allowedMonths
                    );
                    $flagCheckAllowedOtherDay = (
                        (2 ** $rangeStartBefore->format('w')) & $allowedWeekdays
                    );
                    if ($reverseNthDay) {
                        $otherNumberOfDay = ((int)$rangeStartBefore->format('t') - (int)$rangeStartBefore->format('j') + 1);
                    } else {
                        $otherNumberOfDay = ($rangeStartBefore->format('j'));
                    }
                    $flagNumerberOfDayInMonthOtherDay = (
                        (2 ** (ceil($otherNumberOfDay / 7) - 1)) & $allowedNthDay
                    );
                }
            }
        } else {
            $flagCheckMonth = (
                (2 ** ($rangeStopRelativeToDate->format('n') - 1)) & $allowedMonths
            );
            $flagAllowedWeekday = (
                (2 ** $rangeStopRelativeToDate->format('w')) & $allowedWeekdays
            );
            if ($reverseNthDay) {
                $numberOfDay = (int)$rangeStopRelativeToDate->format('t')
                    - (int)$rangeStopRelativeToDate->format('j')
                    + 1;
            } else {
                $numberOfDay = (int)$rangeStopRelativeToDate->format('j');
            }
            $rangeStartRelativeToDate->sub(new DateInterval('PT' . $durationMinutes . 'M'));
            if ($rangeStartRelativeToDate->format('j') !== $dateLikeEventZone->format('j')) {
                $rangeStartBefore = clone $rangeStartRelativeToDate;
                $rangeStartBefore->add(new DateInterval('P1D'));
                $rangeStopBefore = clone $rangeStopRelativeToDate;
                $rangeStopBefore->add(new DateInterval('P1D'));
                $flagRangeOtherDay = (
                    ($rangeStartBefore <= $dateLikeEventZone) &&
                    ($dateLikeEventZone <= $rangeStopBefore)
                );
                if ($flagRangeOtherDay) {
                    $flagCheckMonthOtherDay = (
                        (2 ** ($rangeStopBefore->format('n') - 1)) & $allowedMonths
                    );
                    $flagCheckAllowedOtherDay = (
                        (2 ** $rangeStopBefore->format('w')) & $allowedWeekdays
                    );
                    if ($reverseNthDay) {
                        $otherNumberOfDay = (int)$rangeStopBefore->format('t')
                            - (int)$rangeStopBefore->format('j')
                            + 1;
                    } else {
                        $otherNumberOfDay = ($rangeStopBefore->format('j'));
                    }
                    $flagNumerberOfDayInMonthOtherDay = (
                        (2 ** (ceil($otherNumberOfDay / 7) - 1)) & $allowedNthDay
                    );
                }
            }
        }
        $flagNumerberOfDayInMonth = (
            (2 ** (ceil($numberOfDay / 7) - 1)) & $allowedNthDay
        );
        $flagRangeCurrentDay = (
            ($rangeStartRelativeToDate <= $dateLikeEventZone) &&
            ($dateLikeEventZone <= $rangeStopRelativeToDate)
        );

        $flagActive = ($flagRangeCurrentDay || $flagRangeOtherDay);
        // check Month
        $flagActive = $flagActive && ($flagCheckMonth || $flagCheckMonthOtherDay); // check bitwise
        // Check Weekday
        $flagActive = $flagActive && ($flagAllowedWeekday || $flagCheckAllowedOtherDay); // check bitwise
        // check Day in Month
        $flagActive = $flagActive && ($flagNumerberOfDayInMonth || $flagNumerberOfDayInMonthOtherDay);
        // build last range used. if no valid range exist, then use the next range relative to the current date.
        if (!$flagActive) {
            $helpResult = $this->nextActive($dateLikeEventZone, $params);
            $rangeStartRelativeToDate = clone $helpResult->getBeginning();
            $rangeStopRelativeToDate = clone $helpResult->getEnding();
        }
        $this->setIsActiveResult(
            $rangeStartRelativeToDate,
            $rangeStopRelativeToDate,
            $flagActive,
            $dateLikeEventZone,
            $params
        );
        return $flagActive;
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
     * tested 20221012
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);

        $durationMinutes = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        $allowedWeekdays = (int)($params[self::ARG_ACTIVE_WEEKDAY] ?? 127);
        $allowedMonths = (int)(
        (array_key_exists(self::ARG_ACTIVE_MONTH, $params)) ?
            $params[self::ARG_ACTIVE_MONTH] :
            self::ARG_ACTIVE_MONTH_ALL
        );
        $allowedNthDay = (int)($params[self::ARG_NTH_WEEKDAY_IN_MONTH] ?? 31);
        $reverseNthDay = (array_key_exists(self::ARG_START_COUNT_AT_END, $params) &&
            ($params[self::ARG_START_COUNT_AT_END] !== false));
        $startTime = (int)$params[self::ARG_REQ_START_TIME];

        $checkDate = clone $dateLikeEventZone;
        $checkDate->setTime(
            (floor($startTime / 3600) % 24),
            (floor($startTime / 60) % 60),
            (floor($startTime) % 60)
        );
        if ($durationMinutes > 0) {
            $lower = clone $checkDate;
            $upper = clone $checkDate;
            $upper->add(new DateInterval('PT' . $durationMinutes . 'M'));
        } else {
            $lower = clone $checkDate;
            $upper = clone $checkDate;
            $lower->sub(new DateInterval('PT' . $durationMinutes . 'M'));
        }
        $maxCountDown = self::MAX_COUNT_NEXT_PREV_CALCS;
        $flagChange = false;
        while ($maxCountDown > 0) {
            $flagCheckMonth = (
                (2 ** ($checkDate->format('n') - 1)) & $allowedMonths
            );
            $flagAllowedWeekday = (
                (2 ** $checkDate->format('w')) & $allowedWeekdays
            );
            if ($reverseNthDay) {
                $numberOfDay = (int)$checkDate->format('t')
                    - (int)$checkDate->format('j')
                    + 1;
            } else {
                $numberOfDay = (int)$checkDate->format('j');
            }
            $flagNumerberOfDayInMonth = (
                (2 ** (ceil($numberOfDay / 7) - 1)) & $allowedNthDay
            );
            $flagActive = ($lower > $dateLikeEventZone);
            // check Month
            $flagActive = $flagActive && $flagCheckMonth;
            // Check Weekday
            $flagActive = $flagActive && $flagAllowedWeekday;
            // check Day in Month
            $flagActive = $flagActive && $flagNumerberOfDayInMonth;
            // build last range used. if no valid range exist, then use the next range relative to the current
            if ($flagActive) {
                $flagChange = true;
                break;
            }
            if (!$flagCheckMonth) {
                $checkDate->add(new DateInterval('P1M'));
                // reset to the first on month
                $checkDate->setDate(
                    ((int)$checkDate->format('Y')),
                    ((int)$checkDate->format('m')),
                    1
                );
                if ($durationMinutes > 0) {
                    $lower = clone $checkDate;
                    $upper = clone $checkDate;
                    $upper->add(new DateInterval('PT' . $durationMinutes . 'M'));
                } else {
                    $lower = clone $checkDate;
                    $upper = clone $checkDate;
                    $lower->sub(new DateInterval('PT' . $durationMinutes . 'M'));
                }
            } else {
                $checkDate->add(new DateInterval('P1D'));
                $upper->add(new DateInterval('P1D'));
                $lower->add(new DateInterval('P1D'));
            }
            $maxCountDown--;
        }
        if ($maxCountDown <= 0) {
            throw new TimerException(
                'The algorithm for `nextActive` made ' . self::MAX_COUNT_NEXT_PREV_CALCS . ' calculations and ' .
                'could not find a solution for the next range in your' .
                ' timerproblem of WeekdayInMonthTimer. The testday was `' .
                $dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`' .
                'The parameter were:' . print_r($params, true),
                1665152348
            );
        }

        if ($flagChange) {
            $result->setBeginning($lower);
            $result->setEnding($upper);
            $result->setResultExist(true);
        }

        return $this->validateUltimateRangeForNextRange($result, $params, $dateLikeEventZone);
    }

    /**
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);

        $durationMinutes = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        $allowedWeekdays = (int)($params[self::ARG_ACTIVE_WEEKDAY] ?? 127);
        $allowedMonths = (int)(
        (array_key_exists(self::ARG_ACTIVE_MONTH, $params)) ?
            $params[self::ARG_ACTIVE_MONTH] :
            self::ARG_ACTIVE_MONTH_ALL
        );
        $allowedNthDay = (int)($params[self::ARG_NTH_WEEKDAY_IN_MONTH] ?? 31);
        $reverseNthDay = (array_key_exists(self::ARG_START_COUNT_AT_END, $params) &&
            ($params[self::ARG_START_COUNT_AT_END] !== false));
        $startTime = (int)$params[self::ARG_REQ_START_TIME];

        $checkDate = clone $dateLikeEventZone;
        $checkDate->setTime(
            (floor($startTime / 3600) % 24),
            (floor($startTime / 60) % 60),
            (floor($startTime) % 60)
        );
        if ($durationMinutes > 0) {
            $lower = clone $checkDate;
            $upper = clone $checkDate;
            $upper->add(new DateInterval('PT' . $durationMinutes . 'M'));
        } else {
            $lower = clone $checkDate;
            $upper = clone $checkDate;
            $lower->sub(new DateInterval('PT' . $durationMinutes . 'M'));
        }
        $maxCountDown = self::MAX_COUNT_NEXT_PREV_CALCS;
        $flagChange = false;
        while ($maxCountDown > 0) {
            $flagCheckMonth = (
                (2 ** ($checkDate->format('n') - 1)) & $allowedMonths
            );
            $flagAllowedWeekday = (
                (2 ** $checkDate->format('w')) & $allowedWeekdays
            );
            if ($reverseNthDay) {
                $numberOfDay = (int)$checkDate->format('t')
                    - (int)$checkDate->format('j')
                    + 1;
            } else {
                $numberOfDay = (int)$checkDate->format('j');
            }
            $flagNumerberOfDayInMonth = (
                (2 ** (ceil($numberOfDay / 7) - 1)) & $allowedNthDay
            );
            $flagActive = ($upper < $dateLikeEventZone);
            // check Month
            $flagActive = $flagActive && $flagCheckMonth;
            // Check Weekday
            $flagActive = $flagActive && $flagAllowedWeekday;
            // check Day in Month
            $flagActive = $flagActive && $flagNumerberOfDayInMonth;
            // build last range used. if no valid range exist, then use the next range relative to the current
            if ($flagActive) {
                $flagChange = true;
                break;
            }
            if (!$flagCheckMonth) {
                // reset to the last day of previous month
                $checkDate->setDate(
                    ((int)$checkDate->format('Y')),
                    ((int)$checkDate->format('m')),
                    1
                );
                $checkDate->sub(new DateInterval('P1D'));
                if ($durationMinutes > 0) {
                    $lower = clone $checkDate;
                    $upper = clone $checkDate;
                    $upper->add(new DateInterval('PT' . $durationMinutes . 'M'));
                } else {
                    $lower = clone $checkDate;
                    $upper = clone $checkDate;
                    $lower->sub(new DateInterval('PT' . $durationMinutes . 'M'));
                }
            } else {
                $checkDate->sub(new DateInterval('P1D'));
                $upper->sub(new DateInterval('P1D'));
                $lower->sub(new DateInterval('P1D'));
            }
            $maxCountDown--;
        }
        if ($maxCountDown <= 0) {
            throw new TimerException(
                'The algorithm for `prevActive` made ' . self::MAX_COUNT_NEXT_PREV_CALCS . ' calculations and ' .
                'could not find a solution for the previous range in your' .
                ' timerproblem of WeekdayInMonthTimer. The testday was `' .
                $dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`' .
                'The parameter were:' . print_r($params, true),
                1665152348
            );
        }

        if ($flagChange) {
            $result->setBeginning($lower);
            $result->setEnding($upper);
            $result->setResultExist(true);
        }
        return $this->validateUltimateRangeForPrevRange($result, $params, $dateLikeEventZone);
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
        $this->lastIsActiveResult->setResultExist($flag);
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
}
