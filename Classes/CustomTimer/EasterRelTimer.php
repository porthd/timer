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
use DateTimeZone;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class EasterRelTimer implements TimerInterface
{
    public const TIMER_NAME = 'txTimerEasterRel';


    protected const ARG_NAMED_DATE_MIDNIGHT = 'namedDateMidnight';
    protected const ARG_NAMED_DATE_MIDNIGHT_DEFAULT = self::ARG_NAMED_DATE_EASTER;
    protected const ARG_MIN_NAMED_DATE_MIDNIGHT = 0;
    protected const ARG_MAX_NAMED_DATE_MIDNIGHT = 6;
    protected const ARG_NAMED_DATE_EASTER = 'easter';
    protected const ARG_NAMED_DATE_ASCENSION_OF_CHRIST = 'ascension';
    protected const ARG_NAMED_DATE_PENTECOST = 'pentecost';
    protected const ARG_NAMED_DATE_FIRST_ADVENT = 'firstadvent';
    protected const ARG_NAMED_DATE_CHRISTMAS = 'christmas';
    protected const ARG_NAMED_DATE_ROSE_MONDAY = 'rosemonday';
    protected const ARG_NAMED_DATE_GOOD_FRIDAY = 'goodfriday';
    protected const ARG_NAMED_DATE_TOWL_DAY = 'towlday';
    protected const ARG_REL_MIN_TO_SELECTED_TIMER_EVENT = 'relMinToSelectedTimerEvent';
    protected const ARG_REQ_REL_TO_MIN = -475200;
    protected const ARG_REQ_REL_TO_MAX = 475200;
    protected const ARG_CALENDAR_USE = 'calendarUse';
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_DURMIN_MIN = -475200;
    protected const ARG_DURMIN_MAX = 475200;

    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/EasterRelTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_NAMED_DATE_MIDNIGHT,
        self::ARG_REQ_DURATION_MINUTES,
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,
        self::ARG_CALENDAR_USE,
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
     * @var array
     */
    protected $lastIsActiveParams = [];

    /**
     * tested 20201230
     *
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }

    /**
     * tested 20201230
     *
     * @return array
     */
    public static function getSelectorItem(): array
    {
        return [
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerEasterRel.select.name',
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
     * tested 20201230
     *
     * @return array
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
    }


    /**
     * tested special 20201230
     * tested general 20201230
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
        $countRequired = $this->validateArguments($params);
        $flag = $flag && ($countRequired === count(self::ARG_REQ_LIST));
        $flag = $flag && $this->validateDurationMinutes($params);
        $flag = $flag && $this->validateNamedDateMidnight($params);
        $flag = $flag && $this->validateCalendarUse($params);
        $flag = $flag && $this->validateRelMinToSelectedTimerEvent($params);
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
        return !(isset($params[self::ARG_EVER_TIME_ZONE_OF_EVENT]))||
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
     * @return bool
     */
    public function validateArguments(array $params = []): int
    {
        $flag = 0;
        foreach (self::ARG_REQ_LIST as $key) {
            if (isset($params[$key])) {
                $flag++;
            }
        }
        return $flag;
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateDurationMinutes(array $params = []): bool
    {
        $value = (isset($params[self::ARG_REQ_DURATION_MINUTES]) ?
            $params[self::ARG_REQ_DURATION_MINUTES] :
            0
        );
        $number = (int)$value;
        return is_int($number) && ($number !== 0) && (($number - $value) === 0) &&
            ($number >= self::ARG_DURMIN_MIN) && ($number <= self::ARG_DURMIN_MAX);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateNamedDateMidnight(array $params = []): bool
    {
        $number = $params[self::ARG_NAMED_DATE_MIDNIGHT] ?: self::ARG_NAMED_DATE_MIDNIGHT_DEFAULT;
        $value = (int)$number;
        return is_numeric($number) && (($value - $number) === 0) &&
            ($value >= self::ARG_MIN_NAMED_DATE_MIDNIGHT) && ($value <= self::ARG_MAX_NAMED_DATE_MIDNIGHT);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateCalendarUse(array $params = []): bool
    {
        $number = ((!empty($params[self::ARG_CALENDAR_USE])) ? $params[self::ARG_CALENDAR_USE] : 0);
        $value = (int)$number;
        return (is_numeric($number) && (($value - $number) === 0) && in_array($value, [0, 1, 2, 3]));
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateRelMinToSelectedTimerEvent(array $params = []): bool
    {
        $number = (int)$params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] ?: 0; // what will happen with float
        $value = $params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] ?: 0;
        return is_int($number) && (($number - $value) === 0) &&
            ($number >= self::ARG_REQ_REL_TO_MIN) && ($number <= self::ARG_REQ_REL_TO_MAX);
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
     * tested 20220910
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
            return $result;
        }

        $testRanges = $this->calcDefinedRangesByStartDateTime($dateLikeEventZone, $params);

        $flag = false;
        $start = clone $dateLikeEventZone;
        $start->sub(new DateInterval('PT30S'));
        $stop = clone $dateLikeEventZone;
        $stop->add(new DateInterval('PT30S'));
        foreach ([2, 1, 0, -1, -2,] as $index) {
            if (($testRanges[$index]['begin'] <= $dateLikeEventZone) &&
                ($dateLikeEventZone <= $testRanges[$index]['end'])
            ) {
                $flag = true;
                $start = clone $testRanges[$index]['begin'];
                $stop = clone $testRanges[$index]['end'];
                break;
            }
        }
        $this->setIsActiveResult($start, $stop, $flag, $dateLikeEventZone, $params);
        return $this->lastIsActiveResult->getResultExist();
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
     * tested 20210110
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->failOnlyNextActive($dateLikeEventZone);


        $relToDateMin = (int)(isset($params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT]) ?
            $params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] :
            0
        );
        $relInterval = new DateInterval('PT' . abs($relToDateMin) . 'M');
        $durationMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        $durInterval = new DateInterval('PT' . abs($durationMin) . 'M');
        $method = $this->detectCalendar($params);
        $testDay = clone $dateLikeEventZone;
        $yearInterval = new DateInterval(('P1Y'));
        $testDay->sub($yearInterval);
        $testDay->sub($yearInterval);
        $testDay->sub($yearInterval);
        $flagRebuild = false;
        for ($i = 0; $i < 7; $i++) {
            $checkday = $this->detectDefinedDayInYearNew($testDay, $params[self::ARG_NAMED_DATE_MIDNIGHT], $method);
            if ($relToDateMin >= 0) {
                $checkday->add($relInterval);
            } else {
                $checkday->sub($relInterval);
            }
            if ($dateLikeEventZone < $checkday) {
                if ($durationMin > 0) {
                    $flagRebuild = true;
                    break;
                } else {
                    if ($dateLikeEventZone < ($checkday->sub($durInterval))) {
                        $flagRebuild = true;
                        break;
                    }
                }
            }
            $testDay->add($yearInterval);
        }
        if ($flagRebuild === true) {
            $result->setBeginning($checkday);
            $checkday->add($durInterval);
            $result->setEnding($checkday);
            $result->setResultExist(true);
        }
        if ((!$this->isAllowedInRange($result->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($result->getEnding(), $params))
        ) {
            $result->failOnlyNextActive($dateLikeEventZone);
        }
        return $result;
    }

    /**
     * tested 20210110
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->failOnlyNextActive($dateLikeEventZone);

        $relToDateMin = (int)(isset($params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT]) ?
            $params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] :
            0
        );
        $relInterval = new DateInterval('PT' . abs($relToDateMin) . 'M');
        $durationMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        $durInterval = new DateInterval('PT' . abs($durationMin) . 'M');
        $method = $this->detectCalendar($params);
        $testDay = clone $dateLikeEventZone;
        $yearInterval = new DateInterval(('P1Y'));
        $testDay->add($yearInterval);
        $testDay->add($yearInterval);
        $testDay->add($yearInterval);
        $flagRebuild = false;
        for ($i = 0; $i < 7; $i++) {
            $checkday = $this->detectDefinedDayInYearNew($testDay, $params[self::ARG_NAMED_DATE_MIDNIGHT], $method);
            if ($relToDateMin >= 0) {
                $checkday->add($relInterval);
            } else {
                $checkday->sub($relInterval);
            }
            if ($checkday < $dateLikeEventZone) {
                if ($durationMin < 0) { // $checkday mark the end of the range
                    $flagRebuild = true;
                    break;
                } else {
                    if (($checkday->add($durInterval)) < $dateLikeEventZone) {
                        $flagRebuild = true;  // $checkday mark the end of the range
                        break;
                    }
                }
            }
            $testDay->sub($yearInterval);
        }
        if ($flagRebuild === true) {
            $result->setEnding($checkday);
            $checkday->sub($durInterval);
            $result->setBeginning($checkday);
            $result->setResultExist(true);
        }

        if ((!$this->isAllowedInRange($result->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($result->getEnding(), $params))
        ) {
            $result->failOnlyNextActive($dateLikeEventZone);
        }
        return $result;
    }

    /**
     * @param array $params
     * @return int|mixed
     */
    protected function detectCalendar($params = [])
    {
        $calendar = (int)((isset($params[self::ARG_CALENDAR_USE])) ?
            ($params[self::ARG_CALENDAR_USE]) :
            0
        );
        switch ($calendar) {
            case 1:
                $result = ((defined(CAL_EASTER_ROMAN)) ? CAL_EASTER_ROMAN : $params[self::ARG_CALENDAR_USE]);
                break;
            case 2:
                $result = ((defined(CAL_EASTER_ALWAYS_GREGORIAN)) ? CAL_EASTER_ALWAYS_GREGORIAN : $params[self::ARG_CALENDAR_USE]);
                break;
            case 3:
                $result = ((defined(CAL_EASTER_ALWAYS_JULIAN)) ? CAL_EASTER_ALWAYS_JULIAN : $params[self::ARG_CALENDAR_USE]);
                break;
            default :
                $result = ((defined(CAL_EASTER_DEFAULT)) ? CAL_EASTER_DEFAULT : 0);
                break;
        }
        return $result;
    }

    /**
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return DateTime
     * @throws Exception
     */
    protected function detectDefinedDayInYear(DateTime $testDateTime, $params = []): DateTime
    {

        $method = $this->detectCalendar($params);
        $result = $this->getEasterDatetime(
            $testDateTime->getTimezone(),
            (int)$testDateTime->format('Y'),
            $method
        );
        switch ($params[self::ARG_NAMED_DATE_MIDNIGHT]) {
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
                switch ($method) {
                    case 0:
                        if (((int)$testDateTime->format('Y')) > 1752) {
                            $result = $this->getGreogorianFirstAdvent($testDateTime);
                        } else {
                            $result = $this->getJulianFirstAdvent($testDateTime);
                        }
                        break;
                    case 1:
                        if (((int)$testDateTime->format('Y')) > 1582) {
                            $result = $this->getGreogorianFirstAdvent($testDateTime);
                        } else {
                            $result = $this->getJulianFirstAdvent($testDateTime);
                        }
                        break;
                    case 2:
                        $result = $this->getJulianFirstAdvent($testDateTime);
                        break;
                    case 3:
                        $result = $this->getJulianFirstAdvent($testDateTime);
                        break;
                    default :
                        $result = $this->getJulianFirstAdvent($testDateTime);
                        break;
                }
                break;
            case self::ARG_NAMED_DATE_CHRISTMAS:
                $result = new DateTime($testDateTime->format('Y') . '-12-25 00:00:00', $testDateTime->getTimezone());
                break;
            case self::ARG_NAMED_DATE_TOWL_DAY:
                $result = new DateTime($testDateTime->format('Y') . '-05-25 00:00:00', $testDateTime->getTimezone());
                break;
            case self::ARG_NAMED_DATE_ROSE_MONDAY:
                $result->sub(new DateInterval('P48D'));
                break;
            default :
//                $result = $easter;
                break;

        }
        return $result;
    }

    /**
     * @param DateTime $dateLikeEventZone
     * @param int $method
     * @return DateTime
     * @throws Exception
     */
    protected function detectDefinedDayInYearNew(DateTime $testDateTime, $dateId, $method): DateTime
    {
        $result = $this->getEasterDatetime(
            $testDateTime->getTimezone(),
            (int)$testDateTime->format('Y'),
            $method
        );
        switch ($dateId) {
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
            case self::ARG_NAMED_DATE_TOWL_DAY:
                $result = new DateTime($testDateTime->format('Y') . '-05-25 00:00:00', $testDateTime->getTimezone());
                break;
            case self::ARG_NAMED_DATE_CHRISTMAS:
                $result = new DateTime($testDateTime->format('Y') . '-12-25 00:00:00', $testDateTime->getTimezone());
                break;
            case self::ARG_NAMED_DATE_ROSE_MONDAY:
                $result->sub(new DateInterval('P48D'));
                break;
            default :
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
     * @param array $params
     * @return DateTime
     * @throws Exception
     */
    protected function calcDefinedStartDateTime(DateTime $dateLikeEventZone, array $params): DateTime
    {
        $definedDay = $this->detectDefinedDayInYear($dateLikeEventZone, $params);
        $minStart = (int)$params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT];
        if ($minStart > 0) {
            $definedDay->add(new DateInterval('PT' . $minStart . 'M'));
        } else {
            $definedDay->sub(new DateInterval('PT' . abs($minStart) . 'M'));
        }
        return $definedDay;
    }

    /**
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return array
     * @throws Exception
     */
    protected function calcDefinedRangesByStartDateTime(DateTime $dateLikeEventZone, array $params): array
    {

        $relToDateMin = (int)(isset($params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT]) ?
            $params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] :
            0
        );
        $relInterval = new DateInterval('PT' . abs($relToDateMin) . 'M');
        $durationMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        $durInterval = new DateInterval('PT' . abs($durationMin) . 'M');
        $method = $this->detectCalendar($params);
        $testDay = clone $dateLikeEventZone;
        $yearInterval = new DateInterval(('P1Y'));
        $testDay->sub($yearInterval);
        $testDay->sub($yearInterval);
        $ranges = [];
        foreach ([-2, -1, 0, 1, 2] as $index) {
            $checkday = $this->detectDefinedDayInYearNew($testDay, $params[self::ARG_NAMED_DATE_MIDNIGHT], $method);
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
     * @param DateTime $testDateTime
     * @return DateTime
     * @throws Exception
     */
    protected function getGreogorianFirstAdvent(DateTime $testDateTime): DateTime
    {
        $result = new DateTime($testDateTime->format('Y') . '-12-25 00:00:00', $testDateTime->getTimezone());
        $weekday = (int)$result->format('w');
        $weekday = ((empty($weekday)) ? 7 : $weekday);
        $diff = $weekday + 21;
        $result->sub(new DateInterval('P' . $diff . 'D'));
        return $result;

    }

    /**
     * @param DateTime $testDateTime
     * @return DateTime
     * @throws Exception
     */
    protected function getJulianFirstAdvent(DateTime $testDateTime): DateTime
    {
        $result = new DateTime($testDateTime->format('Y') . '-12-25 00:00:00', $testDateTime->getTimezone());
        $julianDate = gregoriantojd(12, 25, $testDateTime->format('Y'));
        $weekday = jddayofweek($julianDate);
        $weekday = ((empty($weekday)) ? 7 : $weekday);
        $diff = $weekday + 21;
        $result->sub(new DateInterval('P' . $diff . 'D'));
        return $result;
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
        $this->lastIsActiveResult->setResultExist($flag);
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

}