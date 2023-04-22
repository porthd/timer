<?php

namespace Porthd\Timer\CustomTimer;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2023 Dr. Dieter Porth <info@mobger.de>
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
use DateTimeZone;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\GeneralTimerUtility;

class CalendarDateRelTimer implements TimerInterface
{
    use GeneralTimerTrait;

    public const TIMER_NAME = 'txTimerCalendarDateRel';


    protected const ARG_NAMED_DATE_MIDNIGHT = 'namedDateMidnight';
    protected const ARG_REL_MIN_TO_SELECTED_TIMER_EVENT = 'relMinToSelectedTimerEvent';
    protected const ARG_REQ_REL_TO_MIN = -475200;
    protected const ARG_REQ_REL_TO_MAX = 475200;
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_REQ_DURMIN_MIN = -475200;
    protected const ARG_REQ_DURMIN_FORBIDDEN = 0;
    protected const ARG_REQ_DURMIN_MAX = 475200;
    protected const ARG_EVENT_TITLE = 'eventtitle';
    protected const ARG_EVENT_CALENDAR = 'calendar';
    protected const ARG_LIST_EVENT_CALENDAR = [
        'customSelected',
        'buddhist',
        'chinese',
        'coptic',
        'dangi',
        'ethiopic',
        'ethiopic',
        'gregorian',
        'hebrew',
        'indian',
        'islamic',
        'islamicCivil',
        'islamicRgsa',
        'islamicTbla',
        'islamicUmalqura',
        'japanese',
        'persian',
        'roc',
        'julian',
    ];
    protected const ARG_EVENT_IDENT = 'identifier';
    protected const ARG_EVENT_DAY = 'day';
    protected const ARG_EVENT_MONTH = 'month';
    protected const ARG_EVENT_TAG = 'tag';
    protected const ARG_LIST_EVENT_TAG = [
        'religion',
        'politics',
        'culture',
        'history',
        'economics',
    ];
    protected const ARG_EVENT_RANK = 'rank';
    protected const ARG_EVENT_LOCALE = 'locale';

    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/CalendarDateRelTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_NAMED_DATE_MIDNIGHT,
        self::ARG_REQ_DURATION_MINUTES,

        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_EVENT_TITLE,
        self::ARG_EVENT_CALENDAR,
        self::ARG_EVENT_IDENT,
        self::ARG_EVENT_DAY,
        self::ARG_EVENT_MONTH,
        self::ARG_EVENT_TAG,
        self::ARG_EVENT_RANK,
        self::ARG_EVENT_LOCALE,
        self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT,
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
     * tested
     *
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }

    /**
     * tested
     *
     * @return array<mixed>
     */
    public static function getSelectorItem(): array
    {
        return [
            TimerConst::TCA_ITEMS_LABEL => 'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerCalendarDateRel.select.name',
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
     * tested
     *
     * @return array<mixed>
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
    }


    /**
     * tested
     * tested
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
        $flag = $flag && $this->validateDurationMinutes($params);
        $flag = $flag && $this->validateEventDefinition($params);
        $flag = $flag && $this->validateRelMinToSelectedTimerEvent($params);
        $countOptions = $this->validateOptional($params);
        return $flag && ($countOptions >= 0) &&
            ($countOptions <= count(self::ARG_OPT_LIST));
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
        return (
            (($number - $floatNumber) == 0) &&
            ($number >= self::ARG_REQ_DURMIN_MIN) &&
            ($number !== self::ARG_REQ_DURMIN_FORBIDDEN) &&
            ($number <= self::ARG_REQ_DURMIN_MAX)
        );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateEventDefinition(array $params = []): bool
    {
        if ((int)$params[self::ARG_NAMED_DATE_MIDNIGHT] !== 0) {
            return true;
        }
        $flag = true;
        $flag = $flag && (!empty($params[self::ARG_EVENT_TITLE]));
        $flag = $flag && in_array($params[self::ARG_EVENT_CALENDAR], self::ARG_LIST_EVENT_CALENDAR);
        $flag = $flag && (!empty($params[self::ARG_EVENT_IDENT]));
        $flag = $flag && ($params[self::ARG_EVENT_DAY] >= 1) && ($params[self::ARG_EVENT_DAY] <= 32);
        $flag = $flag && ($params[self::ARG_EVENT_MONTH] >= 1) && ($params[self::ARG_EVENT_MONTH] <= 13);
        $flag = $flag && in_array($params[self::ARG_EVENT_TAG], self::ARG_LIST_EVENT_TAG);
        $flag = $flag && ($params[self::ARG_EVENT_RANK] >= 0) && ($params[self::ARG_EVENT_RANK] <= 5);

        return $flag;
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateRelMinToSelectedTimerEvent(array $params = []): bool
    {
        $number = (int)$params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] ?: 0; // what will happen with float
        $floatNumber = (float)$params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] ?: 0;
        return (
            ($number - $floatNumber == 0) &&
            ($number >= self::ARG_REQ_REL_TO_MIN) &&
            ($number <= self::ARG_REQ_REL_TO_MAX)
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
     * tested
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
     * tested
     *
     * check, if the timer ist for this time active
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return bool
     */
    public function isActive(DateTime $dateLikeEventZone, $params = []): bool
    {
        return true;
//        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
//            $result = new TimerStartStopRange();
//            $result->failAllActive($dateLikeEventZone);
//            $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
//            return $result->getResultExist();
//        }
//
//        $testRanges = $this->calcDefinedRangesByStartDateTime($dateLikeEventZone, $params);
//
//        $flag = false;
//        $start = clone $dateLikeEventZone;
//        $start->sub(new DateInterval('PT30S'));
//        $stop = clone $dateLikeEventZone;
//        $stop->add(new DateInterval('PT30S'));
//        $flagFirst = true;
//        foreach ([2, 1, 0, -1, -2,] as $index) {
//            if ($testRanges[$index]['begin'] <= $dateLikeEventZone) {
//                if ($flagFirst) {
//                    $start = clone $testRanges[$index]['begin'];
//                    $stop = clone $testRanges[$index]['end'];
//                    $flagFirst = false;
//                }
//                if ($dateLikeEventZone <= $testRanges[$index]['end']) {
//                    $flag = true;
//                    $start = clone $testRanges[$index]['begin'];
//                    $stop = clone $testRanges[$index]['end'];
//                    break;
//                }
//            }
//        }
//        $this->setIsActiveResult($start, $stop, $flag, $dateLikeEventZone, $params);
//        return $this->lastIsActiveResult->getResultExist();
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
     * tested
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
//        $result->failAllActive($dateLikeEventZone);
        return $result;
//
//        $relToDateMin = (int)(
//        array_key_exists(self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT, $params) ?
//            $params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] :
//            0
//        );
//        $relInterval = new DateInterval('PT' . abs($relToDateMin) . 'M');
//        $durationMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
//        $durInterval = new DateInterval('PT' . abs($durationMin) . 'M');
//        $methodId = $this->detectCalendar($params);
//        $testDay = clone $dateLikeEventZone;
//        $yearInterval = new DateInterval(('P1Y'));
//        $testDay->sub($yearInterval);
//        $testDay->sub($yearInterval);
//        $testDay->sub($yearInterval);
//        $flagRebuild = false;
//        for ($i = 0; $i < 7; $i++) {
//            $checkday = $this->detectDefinedDayInYear($testDay, $params[self::ARG_NAMED_DATE_MIDNIGHT], $methodId);
//            if ($relToDateMin >= 0) {
//                $checkday->add($relInterval);
//            } else {
//                $checkday->sub($relInterval);
//            }
//            if ($durationMin >= 0) {
//                if ($dateLikeEventZone <= $checkday) {
//                    $flagRebuild = true;
//                    break;
//                }
//            } else {
//                $checkday->sub($durInterval);
//                if ($dateLikeEventZone <= $checkday) {
//                    $flagRebuild = true;
//                    break;
//                }
//            }
//            $testDay->add($yearInterval);
//        }
//        if ($flagRebuild === true) {
//            $result->setBeginning($checkday);
//            $checkday->add($durInterval);
//            $result->setEnding($checkday);
//            $result->setResultExist(true);
//        }
//
//        return $this->validateUltimateRangeForNextRange($result, $params, $dateLikeEventZone);
    }

    /**
     * tested
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);
        return $result;

//        $relToDateMin = (int)(
//        array_key_exists(self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT, $params) ?
//            $params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] :
//            0
//        );
//        $relInterval = new DateInterval('PT' . abs($relToDateMin) . 'M');
//        $durationMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
//        $durInterval = new DateInterval('PT' . abs($durationMin) . 'M');
//        $methodId = $this->detectCalendar($params);
//        $testDay = clone $dateLikeEventZone;
//        $yearInterval = new DateInterval(('P1Y'));
//        $testDay->add($yearInterval);
//        $testDay->add($yearInterval);
//        $testDay->add($yearInterval);
//        $flagRebuild = false;
//        for ($i = 0; $i < 7; $i++) {
//            $checkday = $this->detectDefinedDayInYear($testDay, $params[self::ARG_NAMED_DATE_MIDNIGHT], $methodId);
//            if ($relToDateMin >= 0) {
//                $checkday->add($relInterval);
//            } else {
//                $checkday->sub($relInterval);
//            }
//            if ($checkday < $dateLikeEventZone) {
//                if ($durationMin < 0) {
//                    $flagRebuild = true;
//                    break;
//                }
//                $checkday->add($durInterval);
//                if ($checkday < $dateLikeEventZone) { // $checkday mark now the end of the range
//                    $flagRebuild = true;
//                    break;
//                }
//            }
//            $testDay->sub($yearInterval);
//        }
//        if ($flagRebuild === true) {
//            $result->setEnding($checkday);  // datetime object will be cloned in internal variable
//            $checkday->sub($durInterval);
//            $result->setBeginning($checkday);
//            $result->setResultExist(true);
//        }
//        return $this->validateUltimateRangeForPrevRange($result, $params, $dateLikeEventZone);
    }

    /**
     * Is this method of integer-mapping removable? Or is it helpful, to use named values for the flexform-parameter in the future
     *
     * @param array<mixed> $params
     * @return int
     */
    protected function detectCalendar($params = []): int
    {
        $calendar = (
        (array_key_exists(self::ARG_CALENDAR_USE, $params)) ?
            ($params[self::ARG_CALENDAR_USE]) :
            0
        );
        // the following constants are available, because composer required the calendar-extension for php
        switch ($calendar) {
            case '1':
            case 1:
                $result = CAL_EASTER_ROMAN;
                break;
            case '2':
            case 2:
                $result = CAL_EASTER_ALWAYS_GREGORIAN;
                break;
            case '3':
            case 3:
                $result = CAL_EASTER_ALWAYS_JULIAN;
                break;
            default:
                $result = CAL_EASTER_DEFAULT;
                break;
        }
        return $result;
    }

    /**
     * @param DateTime $testDateTime
     * @param string $dateName
     * @param int $methodId
     * @return DateTime
     * @throws Exception
     */
    protected function detectDefinedDayInYear(DateTime $testDateTime, string $dateName, int $methodId): DateTime
    {
        $result = $this->getEasterDatetime(
            $testDateTime->getTimezone(),
            (int)$testDateTime->format('Y'),
            $methodId
        );
        switch ($dateName) {
            case self::ARG_NAMED_DATE_GOOD_FRIDAY:
                $result->sub(new DateInterval('P2D'));
                break;
            case self::ARG_NAMED_DATE_EASTER:
//                $result = $easter;
                break;
            case self::ARG_NAMED_DATE_ASCENSION_OF_CHRIST:
                $result->add(new DateInterval('P39D'));
                break;
            case self::ARG_NAMED_DATE_PENTECOST:
                $result->add(new DateInterval('P49D'));
                break;
            case self::ARG_NAMED_DATE_FIRST_ADVENT:
                $result = new DateTime($testDateTime->format('Y') . '-12-25 00:00:00', $testDateTime->getTimezone());
                $diff = (((int)$result->format('w') === 0) ? 7 : $result->format('w')) + 21;
                $result->sub(new DateInterval('P' . $diff . 'D'));
                break;
            case self::ARG_NAMED_DATE_STUPID_DAY:
                $result = new DateTime($testDateTime->format('Y') . '-04-16 00:00:00', $testDateTime->getTimezone());
                break;
            case self::ARG_NAMED_DATE_TOWL_DAY:
                $result = new DateTime($testDateTime->format('Y') . '-05-25 00:00:00', $testDateTime->getTimezone());
                break;
            case self::ARG_NAMED_DATE_NEW_YEAR:
                $result = new DateTime($testDateTime->format('Y') . '-01-01 00:00:00', $testDateTime->getTimezone());
                break;
            case self::ARG_NAMED_DATE_SILVESTER:
                $result = new DateTime($testDateTime->format('Y') . '-12-31 00:00:00', $testDateTime->getTimezone());
                break;
            case self::ARG_NAMED_DATE_LABOURDAY:
                $result = new DateTime($testDateTime->format('Y') . '-05-01 00:00:00', $testDateTime->getTimezone());
                break;
            case self::ARG_NAMED_DATE_CHRISTMAS:
                $result = new DateTime($testDateTime->format('Y') . '-12-25 00:00:00', $testDateTime->getTimezone());
                break;
            case self::ARG_NAMED_DATE_ROSE_MONDAY:
                $result->sub(new DateInterval('P48D'));
                break;
            default:
//                $result = $easter;
                break;
        }
        return $result;
    }


    /**
     * @param DateTimeZone $timezone
     * @param int|string $year
     * @param string|int $method
     * @return DateTime
     * @throws Exception
     */
    protected function getEasterDatetime(DateTimeZone $timezone, $year, $method): DateTime
    {
        $base = new DateTime("$year-03-21 00:00:00", $timezone);
        $days = easter_days($year, $method);

        return $base->add(new DateInterval('P' . $days . 'D'));
    }

    /**
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return array<mixed>
     * @throws Exception
     */
    protected function calcDefinedRangesByStartDateTime(DateTime $dateLikeEventZone, array $params): array
    {
        $relToDateMin = (int)(
        array_key_exists(self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT, $params) ?
            $params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] :
            0
        );
        $relInterval = new DateInterval('PT' . abs($relToDateMin) . 'M');
        $durationMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        $durInterval = new DateInterval('PT' . abs($durationMin) . 'M');
        $methodId = $this->detectCalendar($params);
        $testDay = clone $dateLikeEventZone;
        $yearInterval = new DateInterval(('P1Y'));
        $testDay->sub($yearInterval);
        $testDay->sub($yearInterval);
        $ranges = [];
        foreach ([-2, -1, 0, 1, 2] as $index) {
            $checkday = $this->detectDefinedDayInYear($testDay, $params[self::ARG_NAMED_DATE_MIDNIGHT], $methodId);
            if ($relToDateMin >= 0) {
                $checkday->add($relInterval);
            } else {
                $checkday->sub($relInterval);
            }
            if ($durationMin > 0) {
                $ranges[$index]['begin'] = clone $checkday;
                $checkday->add($durInterval);
                $ranges[$index]['end'] = clone $checkday;
            } else {
                $ranges[$index]['end'] = clone $checkday;
                $checkday->sub($durInterval);
                $ranges[$index]['begin'] = clone $checkday;
            }
            $testDay->add($yearInterval);
        }

        return $ranges;
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
