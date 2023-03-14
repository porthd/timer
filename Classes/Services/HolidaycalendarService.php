<?php

namespace Porthd\Timer\Services;

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
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\StrangerCode\MoonPhase\Solaris\MoonPhase;
use Porthd\Timer\CustomTimer\StrangerCode\Season\Season;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\ConvertDateUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HolidaycalendarService
{

    protected const ATTR_ARG = 'arg';
    protected const ATTR_ARG_CALENDAR = 'calendar';
    protected const ATTR_ARG_TYPE = 'type';
    protected const ATTR_ARG_TIMER = 'timer';
    protected const ATTR_ARG_TYPE_FIXED = 'fixed';
    protected const ATTR_ARG_TYPE_FIXEDMULTI = 'fixedmultiyear';
    protected const ATTR_ARG_TYPE_FIXEDSHIFTING = 'fixedshifting';
    protected const ATTR_ARG_TYPE_FIXEDRELATED = 'fixedrelated';
    protected const ATTR_ARG_TYPE_SEASON = 'season';
    protected const ATTR_ARG_TYPE_SEASONSHIFTING = 'seasonshifting';
    protected const ATTR_ARG_TYPE_EASTERLY = 'easterly';
    protected const ATTR_ARG_TYPE_WEEKDAYLY = 'weekdayly';
    protected const ATTR_ARG_TYPE_MOONINMONTH = 'mooninmonth';
    protected const ATTR_ARG_TYPE_LIST = [
        self::ATTR_ARG_TYPE_FIXED,
        self::ATTR_ARG_TYPE_FIXEDSHIFTING,
        self::ATTR_ARG_TYPE_FIXEDMULTI,
        self::ATTR_ARG_TYPE_FIXEDRELATED,
        self::ATTR_ARG_TYPE_SEASON,
        self::ATTR_ARG_TYPE_SEASONSHIFTING,
        self::ATTR_ARG_TYPE_EASTERLY,
        self::ATTR_ARG_TYPE_WEEKDAYLY,
        self::ATTR_ARG_TYPE_MOONINMONTH,
    ];
    protected const ATTR_ARG_DAY = 'day';
    protected const ATTR_ARG_MONTH = 'month';
    protected const ATTR_ARG_STATUS = 'status';
    protected const ATTR_ARG_START = 'startYear';
    protected const ATTR_ARG_STOP = 'stopYear';
    protected const ATTR_ARG_STATUSCOUNT = 'statusCount';
    protected const ATTR_ARG_SECDAYCOUNT = 'secDayCount';

    protected const LIST_MOON_PHASE = [
        0 => 'new_moon',
        1 => 'first_quarter',
        2 => 'full_moon',
        3 => 'last_quarter',
    ];



    public function forbiddenCalendar(array $holidayItem): bool
    {
        return ($holidayItem[self::ATTR_ARG][self::ATTR_ARG_CALENDAR] === 'chinese');
    }

    /**
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayItem
     * @return TimerStartStopRange
     */
    public function nextHoliday(string $locale, DateTime $startDate, array $holidayItem): TimerStartStopRange
    {
        $holidayArg = $holidayItem[self::ATTR_ARG];
        $addYear = 0;
        $timerRange = $this->getGregorianDateForHoliday($locale, $startDate, $holidayArg, $addYear);
        if ($timerRange->getBeginning() >= $startDate) {
            return $timerRange;
        }
        $addYear++;
        return $this->getGregorianDateForHoliday($locale, $startDate, $holidayArg, $addYear);
    }

    /**
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayArg
     * @param int $addYear
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function getGregorianDateForHoliday(
        string $locale,
        DateTime $startDate,
        array $holidayArg,
        int $addYear = 0
    ): TimerStartStopRange {
        switch ($holidayArg[self::ATTR_ARG_TYPE]) {
            case self::ATTR_ARG_TYPE_FIXED:
                $timerRange = $this->getGregorianDateForFixedType($locale, $startDate, $holidayArg, $addYear);
                break;
            case self::ATTR_ARG_TYPE_FIXEDSHIFTING:
                $timerRange = $this->getGregorianDateForFixedShiftingType($locale, $startDate, $holidayArg,
                    $addYear);
                break;
            case self::ATTR_ARG_TYPE_FIXEDMULTI:
                $timerRange = $this->getGregorianDateForFixedMultiType($locale, $startDate, $holidayArg,
                    $addYear);
                break;
            case self::ATTR_ARG_TYPE_FIXEDRELATED:
                $timerRange = $this->getGregorianDateForFixedRelatedType($locale, $startDate, $holidayArg,
                    $addYear);
                break;
            case self::ATTR_ARG_TYPE_SEASON:
                $timerRange = $this->getGregorianDateForSeasonType($locale, $startDate, $holidayArg,
                    $addYear);
                break;
            case self::ATTR_ARG_TYPE_SEASONSHIFTING:
                $timerRange = $this->getGregorianDateForSeasonShiftingType($locale, $startDate, $holidayArg,
                    $addYear);
                break;
            case self::ATTR_ARG_TYPE_EASTERLY:
                $timerRange = $this->getGregorianDateForEasterlyType($locale, $startDate, $holidayArg, $addYear);
                break;
            case self::ATTR_ARG_TYPE_WEEKDAYLY:
                $timerRange = $this->getGregorianDateForWeekdaylyType($locale, $startDate, $holidayArg, $addYear);
                break;
            case self::ATTR_ARG_TYPE_MOONINMONTH:
                $timerRange = $this->getGregorianDateForMoonInMonthType($locale, $startDate, $holidayArg,
                    $addYear);
                break;
            default :
                /** @var ListOfTimerService $listOfTimerSevice */
                $listOfTimerSevice = GeneralUtility::makeInstance(ListOfTimerService::class);
                $flagTimer = $listOfTimerSevice->validateSelector($holidayArg[self::ATTR_ARG_TYPE]);
                if ($flagTimer) {
                    /** @var TimerStartStopRange $timerRange */
                    $timerRange = $listOfTimerSevice->nextActive($holidayArg[self::ATTR_ARG_TYPE], $startDate,
                        $holidayArg[self::ATTR_ARG_TIMER]);
                } else {
                    throw new TimerException(
                        'The type of the holiday calculation (`' . $holidayArg[self::ATTR_ARG_TYPE] .
                        '`) is unknown. Check for the correct wording and for typemistakes. If everythings seems okay, ' .
                        'then make a screenshot and inform the webmaster. ' .
                        '(allowed types: ' . implode(',', self::ATTR_ARG_TYPE_LIST) . ')',
                        1677389029
                    );
                }
        }
        return $timerRange;
    }

    /**
     * tested: 20230219 (without $flag)
     * tested: (with Flag see test for `getGregorianDateForFixedShiftingType`)
     * 20230225 => function not fully tested for the hebrew-calendar
     *
     * The method determines the year of the corresponding calendar system from a Gregorian date. The year is used
     * to determine a specific date. If necessary, an integer is added to the year. The date is then converted
     * to the corresponding Gregorian date.
     *
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayArg
     * @param int $addYear
     * @param $flagShifting
     * @param $flagMultiYear
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function getGregorianDateForFixedType(
        string $locale,
        DateTime $startDate,
        array $holidayArg,
        int $addYear,
        $flagShifting = false,
        $flagMultiYear = false
    ): TimerStartStopRange {
        /** @var TimerStartStopRange $timeRange */
        $timeRange = new TimerStartStopRange();
        if ($holidayArg[self::ATTR_ARG_CALENDAR] === ConvertDateUtility::DEFAULT_CALENDAR) {
            $holidayDate = new DateTime();
            $holidayDate->setTimezone($startDate->getTimezone());
            $holidayDate->setTime(0, 0, 0);
            $setYear = (int)$startDate->format('Y') + $addYear;
            $holidayDate->setDate(
                $setYear,
                (int)$holidayArg[self::ATTR_ARG_MONTH],
                (int)$holidayArg[self::ATTR_ARG_DAY]
            );
        } else {
            $refYear = clone $startDate;
            $calendarStartDateString = ConvertDateUtility::convertFromDateTimeToCalendar(
                $locale,
                $holidayArg[self::ATTR_ARG_CALENDAR],
                $refYear,
                true,
                ConvertDateUtility::INTL_DATE_FORMATTER_DEFAULT_PATTERN
            );
            $setYear = (int)(substr($calendarStartDateString, 0, strpos($calendarStartDateString, '/')))
                + $addYear;
            switch ($holidayArg[self::ATTR_ARG_CALENDAR]) {
                case ConvertDateUtility::DEFAULT_HEBREW_CALENDAR:
                    $myMonth = (int)$holidayArg[self::ATTR_ARG_MONTH];
                    // there is no chance to detect directly by the IntlDateCalendar the lepYear in the hebrew calendar
                    $leapYear = in_array(($setYear % 19), [0, 3, 6, 8, 11, 14, 17], true);
                    if (($leapYear) &&
                        ($myMonth >= 6) &&
                        ($myMonth <= 12)
                    ) {
                        $myMonth++;
                        // if the field `arg.status` is filled and the leapmonth is available,
                        // the leapmonth of the hebrew-calendar would be used
                        if (($myMonth === 6) &&
                            (!empty($holidayArg[self::ATTR_ARG_STATUS]))
                        ) {
                            $myMonth--;
                        }
                    }
                    $fixedDateCalendar = str_pad($setYear, 4, '0', STR_PAD_LEFT) . '/'
                        . str_pad($myMonth, 2, '0', STR_PAD_LEFT) . '/'
                        . str_pad($holidayArg[self::ATTR_ARG_DAY], 2, '0', STR_PAD_LEFT) . ' '
                        . '00:00:00';
                    break;
                default:
                    $fixedDateCalendar = str_pad($setYear, 4, '0', STR_PAD_LEFT) . '/'
                        . str_pad($holidayArg[self::ATTR_ARG_MONTH], 2, '0', STR_PAD_LEFT) . '/'
                        . str_pad($holidayArg[self::ATTR_ARG_DAY], 2, '0', STR_PAD_LEFT) . ' '
                        . '00:00:00';
                    break;
            }
            $holidayDate = ConvertDateUtility::convertFromCalendarToDateTime(
                $locale,
                $holidayArg[self::ATTR_ARG_CALENDAR],
                $fixedDateCalendar,
                $startDate->getTimezone()->getName()
            );
        }
        $this->setTimeRangeFlagForHoliday($timeRange, $holidayArg, $setYear);

        if ($flagShifting) {
            $weekday = (int)$holidayDate->format('w');
            // shift list by one day
            $index = ($weekday + 6) % 7;
            $shiftListMoToSu = $holidayArg[self::ATTR_ARG_STATUSCOUNT];
            $shiftListMoToSuList = array_filter(
                array_map(
                    'intval',
                    explode(',', $shiftListMoToSu)
                )
            );
            $shiftDays = $shiftListMoToSuList[$index];
            if ($shiftDays < 0) {
                $holidayDate->sub(new DateInterval(('P' . abs($shiftDays) . 'D')));
            } elseif ($shiftDays > 0) {
                $holidayDate->add(new DateInterval(('P' . $shiftDays . 'D')));
            }
        }
        $this->setBeginnengAndEndingInTimeRange($timeRange, $holidayDate);
        if ($flagMultiYear) {
            $refYear = $holidayArg[self::ATTR_ARG_STATUS];
            $modulo = $holidayArg[self::ATTR_ARG_STATUSCOUNT];
            if (($setYear - $refYear) % $modulo !== 0) {
                $timeRange->setResultExist(false);
            }
        }

        return $timeRange;
    }

    /**
     * tested:
     *
     * The method determines the year of the corresponding calendar system from a Gregorian date. The year is used
     * to determine a specific date. If necessary, an integer is added to the year. The date is then converted
     * to the corresponding Gregorian date.
     *
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayArg
     * @param int $addYear
     * @return TimerStartStopRange
     */
    protected function getGregorianDateForFixedShiftingType(
        string $locale,
        DateTime $startDate,
        array $holidayArg,
        int $addYear
    ): TimerStartStopRange {
        return $this->getGregorianDateForFixedType($locale, $startDate, $holidayArg, $addYear, true);
    }

    /**
     * tested:
     *
     * The method determines the year of the corresponding calendar system from a Gregorian date. The year is used
     * to determine a specific date. If necessary, an integer is added to the year. The date is then converted
     * to the corresponding Gregorian date.
     *
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayArg
     * @param int $addYear
     * @return TimerStartStopRange
     */
    protected function getGregorianDateForFixedMultiType(
        string $locale,
        DateTime $startDate,
        array $holidayArg,
        int $addYear
    ): TimerStartStopRange {
        return $this->getGregorianDateForFixedType($locale, $startDate, $holidayArg, $addYear, false, true);
    }

    /**
     * tested:
     *
     * The method determines the year of the corresponding calendar system from a Gregorian date. The year is used
     * to determine a specific date, which is related by a special weekday and a gap of weeks. In some special cases
     * there is additional defined a daygap relativ to the predicted date.
     * The Doy of preayer is r
     *
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayArg
     * @param int $addYear
     * @return TimerStartStopRange
     */
    protected function getGregorianDateForFixedRelatedType(
        string $locale,
        DateTime $startDate,
        array $holidayArg,
        int $addYear
    ): TimerStartStopRange {
        $resultRange = $this->getGregorianDateForFixedType($locale, $startDate, $holidayArg, $addYear);
        if ($resultRange->hasResultExist()) {
            $currentDate = $resultRange->getBeginning();
            $currentWeekday = (int)$currentDate->format('w');
            $weekday = ((int)$holidayArg[self::ATTR_ARG_STATUS]) % 7;
            $counts = (int)$holidayArg[self::ATTR_ARG_STATUSCOUNT];
            $dayGap = (int)$holidayArg[self::ATTR_ARG_SECDAYCOUNT];
            $daysOfWeeks = (abs($counts) - 1) * 7;
            if ($counts > 0) {
                $daysToFirst = (($weekday === $currentWeekday) ?
                    7 :
                    (7 - $currentWeekday + $weekday) % 7
                );
                $days = $daysOfWeeks + $daysToFirst + $dayGap;

            } else {
                $daysToFirst = (($weekday === $currentWeekday) ?
                    7 :
                    (7 - $weekday + $currentWeekday) % 7
                );
                $days = -($daysOfWeeks + $daysToFirst) + $dayGap;

            }

            if ($days > 0) {
                $currentDate->add(new DateInterval('P' . $days . 'D'));
            } elseif ($days < 0) {
                $currentDate->sub(new DateInterval('P' . abs($days) . 'D'));
            }
            $resultRange->setBeginning($currentDate);
            $currentDate->setTime(23, 59, 59);
            $resultRange->setEnding($currentDate);
        }
        return $resultRange;
    }

    /**
     * tested:
     *
     * The method determines the year of the corresponding calendar system from a Gregorian date. The year is used
     * to determine a specific date. If necessary, an integer is added to the year. The date is then converted
     * to the corresponding Gregorian date.
     *
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayArg
     * @param int $addYear
     * @param bool $flagShifting
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function getGregorianDateForSeasonType(
        string $locale,
        DateTime $startDate,
        array $holidayArg,
        int $addYear,
        bool $flagShifting = false
    ): TimerStartStopRange {
        /** @var TimerStartStopRange $timeRange */
        $timeRange = new TimerStartStopRange();
        $seasonService = GeneralUtility::makeInstance(Season::class);
        $seasonService->datum();
        $setYear = (int)$startDate->format('Y') + $addYear;
        $seasonList = $seasonService->saison($setYear);
        if ((in_array((int)$holidayArg[self::ATTR_ARG_STATUS], [1, 2, 3, 4,], true))) {
            $season = TimerConst::LIST_SEASON_OF_YEAR[((int)$holidayArg[self::ATTR_ARG_STATUS] - 1)];
        } else {
            $season = $holidayArg[self::ATTR_ARG_STATUS];
        }
        if (!in_array($season, TimerConst::LIST_SEASON_OF_YEAR, true)) {
            throw new TimerException(
                'The value `' . $holidayArg[self::ATTR_ARG_STATUS] . '` could not interpreted as a number or as a ' .
                'one of the seasons: ' . implode(',', TimerConst::LIST_SEASON_OF_YEAR),
                1677703136
            );
        }
        $seasonTime = new DateTime('@' . $seasonList[$season]);
        $seasonTime->setTimezone($startDate->getTimezone());
        if ($flagShifting) {
            $weekday = (int)$seasonTime->format('w');
            // shift list by one day
            $index = ($weekday + 6) % 7;
            $shiftListMoToSu = $holidayArg[self::ATTR_ARG_STATUSCOUNT];
            $shiftListMoToSuList = array_filter(
                array_map(
                    'intval',
                    explode(',', $shiftListMoToSu)
                )
            );
            $shiftDays = $shiftListMoToSuList[$index];
            if ($shiftDays < 0) {
                $seasonTime->sub(new DateInterval(('P' . abs($shiftDays) . 'D')));
            } elseif ($shiftDays > 0) {
                $seasonTime->add(new DateInterval(('P' . $shiftDays . 'D')));
            }
        }

        $this->setTimeRangeFlagForHoliday($timeRange, $holidayArg, $setYear);
        $this->setBeginnengAndEndingInTimeRange($timeRange, $seasonTime);

        return $timeRange;
    }

    /**
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayArg
     * @param int $addYear
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function getGregorianDateForSeasonShiftingType(
        string $locale,
        DateTime $startDate,
        array $holidayArg,
        int $addYear
    ): TimerStartStopRange {
        return $this->getGregorianDateForSeasonType(
            $locale,
            $startDate,
            $holidayArg,
            $addYear,
            true
        );
    }

    /**
     * tested:
     *
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayArg
     * @param int $addYear
     * @return TimerStartStopRange
     */
    protected function getGregorianDateForEasterlyType(
        string $locale,
        DateTime $startDate,
        array $holidayArg,
        int $addYear
    ): TimerStartStopRange {
        /** @var TimerStartStopRange $timeRange */
        $timeRange = new TimerStartStopRange();

        $year = (int)$startDate->format('Y') + $addYear;
        switch ($holidayArg[self::ATTR_ARG_CALENDAR]) {
            case ConvertDateUtility::DEFAULT_CALENDAR:
                $easterStamp = easter_date($year, CAL_EASTER_ALWAYS_GREGORIAN);
                break;
            case ConvertDateUtility::DEFAULT_JULIAN_CALENDAR:
                $easterStamp = easter_date($year, CAL_EASTER_ALWAYS_JULIAN);
                break;
            default:
                throw new TimerException(
                    'For the easter-function is only the calendar-system `gregorian` or `julian` allowed. ' .
                    'You tried to use `' . $holidayArg[self::ATTR_ARG_CALENDAR] . '`. Please check your list of holidays.' .
                    'the arguments for the calender are: ' . print_r($holidayArg, true),
                    1676823965
                );
        }
        $result = clone $startDate;
        $result->setTimestamp($easterStamp);
        if ((isset($holidayArg[self::ATTR_ARG_STATUSCOUNT])) && (!empty($holidayArg[self::ATTR_ARG_STATUSCOUNT]))) {
            $days = (int)$holidayArg[self::ATTR_ARG_STATUSCOUNT];
            if ($days > 0) {
                $result->add(new DateInterval('P' . $days . 'D'));
            } else {
                $result->sub(new DateInterval('P' . abs($days) . 'D'));
            }
        }
        $this->setTimeRangeFlagForHoliday($timeRange, $holidayArg, (int)$result->format('Y'));
        $this->setBeginnengAndEndingInTimeRange($timeRange, $result);
        return $timeRange;
    }


    /**
     * tested:
     *
     *
     *      * Mother's Day is always the second Sunday in May. The method below determines the corresponding i-th day
     * of the week in the selected month for any calendar. The days of the week are numbered:
     * 1=Moon day/Monday, 2=Mars day/Tuesday, 3=Mercury day/Wednesday, 4=Jupiter day/Thursday, 5=Venus day/Friday,
     * 6=Saturn day/Saturday, 7=Sunday/Sunday,.
     * This algorithm is intended to determine specific days of the week relative to a remaining day in a month.
     * So it works in the same way as with the problem described above, except that the reference day is not the first
     * day in the month. In this way, for example, you can easily determine the days for the first, second, thord and
     * forth Advent relative to the first Christmas Day.
     *
     * german Version: Der Muttertag ist immer der zweite Sonntag im Mai. Die nachfolgende Methode bestimmt also
     * für beliebige Kalender den entsprechenden i-ten Wochentag im ausgewählten Monat. Die Wochentage werden
     * nummeriert: 1=Mond tag/Montag, 2=Marstag/Diensttag, 3=Merkurtag/Mittwoch,
     * 4=Jupitertag/Donnerstag,5=Venustag/Freitag,6=Saturntag/Sonnabend, 7=Sonnetag/Sonntag.
     *
     * Dieser Algorithmus soll bestimmte Wochentage relativ zu einem bleig´bigen Tage in einem Monat bestimmen.
     * Er funktioniert also analog wie bei dem obige beschriebenen Problem, nur dass der Bezugstag nicht der  1.
     * des Monats sein muss. Auf diese Wiese kann man zum Beispiel leicht die Adventstage relativ zum ersten
     * Weihnachtstag bestimmen.
     *
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayArg
     * @param int $addYear
     * @return TimerStartStopRange
     */
    protected function getGregorianDateForWeekdaylyType(
        string $locale,
        DateTime $startDate,
        array $holidayArg,
        int $addYear
    ): TimerStartStopRange {
        /** @var TimerStartStopRange $timeRange */
        $timeRange = new TimerStartStopRange();
        $helpDay = (int)(($holidayArg[self::ATTR_ARG_DAY] > 0) ?
            $holidayArg[self::ATTR_ARG_DAY] :
            1
        );
        if ($holidayArg[self::ATTR_ARG_CALENDAR] === ConvertDateUtility::DEFAULT_CALENDAR) {
            $holidayDate = new DateTime();
            $holidayDate->setTimezone($startDate->getTimezone());
            $holidayDate->setTime(0, 0, 0);
            $setYear = (int)$startDate->format('Y') + $addYear;
            $holidayDate->setDate(
                $setYear,
                (int)$holidayArg[self::ATTR_ARG_MONTH],
                $helpDay
            );
        } else {
            $refYear = clone $startDate;
            $calendarStartDateString = ConvertDateUtility::convertFromDateTimeToCalendar(
                $locale,
                $holidayArg[self::ATTR_ARG_CALENDAR],
                $refYear,
                true,
                ConvertDateUtility::INTL_DATE_FORMATTER_DEFAULT_PATTERN
            );
            $setYear = (int)(substr($calendarStartDateString, 0, strpos($calendarStartDateString, '/')))
                + $addYear;
            $helpDay = $holidayArg[self::ATTR_ARG_DAY];
            $fixedDateCalendar = str_pad($setYear, 4, '0', STR_PAD_LEFT) . '/'
                . str_pad($holidayArg[self::ATTR_ARG_MONTH], 2, '0', STR_PAD_LEFT) . '/'
                . str_pad($helpDay, 2, '0', STR_PAD_LEFT) . ' '
                . '00:00:00';
            $holidayDate = ConvertDateUtility::convertFromCalendarToDateTime(
                $locale,
                $holidayArg[self::ATTR_ARG_CALENDAR],
                $fixedDateCalendar,
                $startDate->getTimezone()->getName()
            );
        }
        $this->setTimeRangeFlagForHoliday($timeRange, $holidayArg, $setYear);

        // 0 = sunday, 6 = saturday
        $numberOfWeekday = $holidayDate->format('w');
        // transfered to counting scheme abowe
        $wishedWeekday = ((int)$holidayArg[self::ATTR_ARG_STATUS] ?: 7) % 7;
        $addDays = (7 + $wishedWeekday - $numberOfWeekday) % 7;
        $wishedCountWeekday = (
            (int)(empty($holidayArg[self::ATTR_ARG_STATUSCOUNT])) ?
                1 :
                $holidayArg[self::ATTR_ARG_STATUSCOUNT]
            ) - 1;
        $addDays += 7 * $wishedCountWeekday;
        if ($addDays !== 0) {
            if ($addDays > 0) {
                $holidayDate->add(new DateInterval('P' . $addDays . 'D'));
            } else {
                $holidayDate->sub(new DateInterval('P' . abs($addDays) . 'D'));
            }
        }
        // handele holidyas like `Buß- und Betttag` - the wendesday before the fifth sunday before the first christmas day
        if (empty($holidayArg[self::ATTR_ARG_SECDAYCOUNT])) {
            $secondCount = (int)$holidayArg[self::ATTR_ARG_SECDAYCOUNT];
            if ($secondCount > 0) {
                $holidayDate->add(new DateInterval('P' . $secondCount . 'D'));
            } else {
                $holidayDate->sub(new DateInterval('P' . abs($secondCount) . 'D'));
            }
        }

        $this->setBeginnengAndEndingInTimeRange($timeRange, $holidayDate);
        return $timeRange;
    }


    /**
     * tested:
     *
     * @param string $locale
     * @param DateTime $startDate
     * @param array $holidayArg
     * @param int $addYear
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function getGregorianDateForMoonInMonthType(
        string $locale,
        DateTime $startDate,
        array $holidayArg,
        int $addYear
    ): TimerStartStopRange {
        /** @var TimerStartStopRange $timeRange */
        $timeRange = new TimerStartStopRange();
        $monthNumber = (int)$holidayArg[self::ATTR_ARG_MONTH];
        if ($holidayArg[self::ATTR_ARG_CALENDAR] === ConvertDateUtility::DEFAULT_CALENDAR) {
            $holidayDate = new DateTime();
            $holidayDate->setTimezone($startDate->getTimezone());
            $holidayDate->setTime(0, 0, 0);
            $setYear = (int)$startDate->format('Y') + $addYear;
            $holidayDate->setDate(
                $setYear,
                $monthNumber,
                1
            );
        } else {
            $refYear = clone $startDate;
            $calendarStartDateString = ConvertDateUtility::convertFromDateTimeToCalendar(
                $locale,
                $holidayArg[self::ATTR_ARG_CALENDAR],
                $refYear,
                true,
                ConvertDateUtility::INTL_DATE_FORMATTER_DEFAULT_PATTERN
            );
            $setYear = (int)(substr($calendarStartDateString, 0, strpos($calendarStartDateString, '/')))
                + $addYear;

            $fixedDateCalendar = str_pad((string)$setYear, 4, '0', STR_PAD_LEFT) . '/'
                . str_pad((string)$monthNumber, 2, '0', STR_PAD_LEFT) . '/'
                . str_pad('1', 2, '0', STR_PAD_LEFT) . ' '
                . '00:00:00';
            $holidayDate = ConvertDateUtility::convertFromCalendarToDateTime(
                $locale,
                $holidayArg[self::ATTR_ARG_CALENDAR],
                $fixedDateCalendar,
                $startDate->getTimezone()->getName()
            );
        }
        $this->setTimeRangeFlagForHoliday($timeRange, $holidayArg, $setYear);

        $moonPhaseRaw = $holidayArg[self::ATTR_ARG_STATUS];
        $listKey = array_keys(self::LIST_MOON_PHASE);
        $listValues = array_values(self::LIST_MOON_PHASE);
        if (in_array((int)$moonPhaseRaw, $listKey)) {
            $moonPhase = self::LIST_MOON_PHASE[(int)$moonPhaseRaw];
        } else {
            if (in_array((int)$moonPhaseRaw, $listValues)) {
                $moonPhase = $moonPhaseRaw;
            } else {
                throw new TimerException(
                    'The value for the moonphase (`' . $moonPhaseRaw . '`)must be one of the keys or values ' .
                    'in the following array:' . print_r(self::LIST_MOON_PHASE, true),
                    1677347995
                );
            }
        }
        $moonPhaseCalculator = new MoonPhase($holidayDate->getTimestamp() - 86400);
        $moonPhaseTStamp = $moonPhaseCalculator->get_phase($moonPhase);
        $nextMoonPhaseTStamp = $moonPhaseCalculator->get_phase('next_' . $moonPhase);

        // get the everytime the first moonnstatus in the month
        $holidayDateTime = new \DateTime();
        $holidayDateTime->setTimestamp($moonPhaseTStamp);
        $holidayDateTime->setTimezone($startDate->getTimezone());
        $holidayDateTime->setTime(0, 0, 0);
        // if the condition is true, the second moonphase should be choosed, if the secand moonphase is part of the same month in the original calendar-system
        if ((!empty($holidayArg[self::ATTR_ARG_STATUSCOUNT])) &&
            ((int)$holidayArg[self::ATTR_ARG_STATUSCOUNT] !== 1)
        ) {
            $holidayDateTest = new \DateTime();
            $holidayDateTest->setTimestamp($nextMoonPhaseTStamp);
            $holidayDateTest->setTimezone($startDate->getTimezone());
            $holidayDateTest->setTime(0, 0, 0);
            // check if second moonphase is still in the current month of the calendar
            if ($holidayArg[self::ATTR_ARG_CALENDAR] === ConvertDateUtility::DEFAULT_CALENDAR) {
                if ($monthNumber === (int)$holidayDateTest->format('m')) {
                    $holidayDateTime = $holidayDateTest;
                }

            } else {
                $calendarStartDateString = ConvertDateUtility::convertFromDateTimeToCalendar(
                    $locale,
                    $holidayArg[self::ATTR_ARG_CALENDAR],
                    $holidayDateTest,
                    true,
                    ConvertDateUtility::INTL_DATE_FORMATTER_DEFAULT_PATTERN
                );
                $calendarList = array_map('intval', explode('/', $calendarStartDateString));
                if ($holidayArg[self::ATTR_ARG_CALENDAR] === ConvertDateUtility::DEFAULT_HEBREW_CALENDAR) {
                    $leapYear = in_array(($calendarList[0] % 19), [0, 3, 6, 8, 11, 14, 17], true);
                    if (($leapYear) &&
                        ($monthNumber >= 6) &&
                        ($monthNumber <= 12)
                    ) {
                        // jum over the leapyear, except there is a value in `self::ATTR_ARG_SECDAYCOUNT`
                        $monthNumber++;
                        if (($monthNumber === 6) &&
                            (!empty($holidayArg[self::ATTR_ARG_SECDAYCOUNT]))
                        ) {
                            $monthNumber--;
                        }
                    }
                }
                if ($monthNumber === (int)$calendarList[1]) {
                    $holidayDateTime = $holidayDateTest;
                }
            }
            // get the everytime the first moonmstatus in the month
        }
        $this->setBeginnengAndEndingInTimeRange($timeRange, $holidayDate);
        return $timeRange;
    }

    /**
     * @param TimerStartStopRange $timeRange
     * @param array $holidayArg
     * @param int $setYear
     */
    protected function setTimeRangeFlagForHoliday(TimerStartStopRange $timeRange, array $holidayArg, int $setYear): void
    {
        $timeRange->setResultExist(true);
        if ((!empty($holidayArg[self::ATTR_ARG_START])) &&
            ($setYear < (int)$holidayArg[self::ATTR_ARG_START])) {
            $timeRange->setResultExist(false);
        }
        if ((!empty($holidayArg[self::ATTR_ARG_STOP])) &&
            ($setYear > (int)$holidayArg[self::ATTR_ARG_START])) {
            $timeRange->setResultExist(false);
        }
    }

    /**
     * @param TimerStartStopRange $timeRange
     * @param DateTime $holidayDate
     * @return void
     */
    protected function setBeginnengAndEndingInTimeRange(TimerStartStopRange $timeRange, DateTime $holidayDate): void
    {
        $timeRange->setBeginning($holidayDate);
        $holidayDate->setTime(23, 59, 59);
        $timeRange->setEnding($holidayDate);
    }

}
