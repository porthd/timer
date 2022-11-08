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

use DateTime;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;

interface TimerInterface
{
    // @todo change to enuum after change to PHP >=8.1
    public const ENUM_RANGE_NOT_IN_RANGE = 'no';
    public const ENUM_RANGE_INCLUDE_ALL_RANGE = 'all';
    public const ENUM_RANGE_IN_RANGE_WITH_LOWER_LIMIT = 'low';
    public const ENUM_RANGE_IN_RANGE_WITH_HIGHER_LIMIT = 'high';
    public const ENUM_RANGE_INCLUDED = 'part';
    public const ENUM_RANGE_LIST = [
        self::ENUM_RANGE_NOT_IN_RANGE,
        self::ENUM_RANGE_INCLUDE_ALL_RANGE,
        self::ENUM_RANGE_IN_RANGE_WITH_LOWER_LIMIT,
        self::ENUM_RANGE_IN_RANGE_WITH_HIGHER_LIMIT,
        self::ENUM_RANGE_INCLUDED,
    ];

    public const ARG_USE_ACTIVE_TIMEZONE = 'useTimeZoneOfFrontend';
    public const ARG_EVER_TIME_ZONE_OF_EVENT = 'timeZoneOfEvent';
    public const ARG_ULTIMATE_RANGE_BEGINN = 'ultimateBeginningTimer';
    public const ARG_ULTIMATE_RANGE_END = 'ultimateEndingTimer';

    public const TIMER_FORMAT_DATE = 'Y-m-d';
    public const TIMER_FORMAT_TIME = 'H:i:s';
    public const TIMER_FORMAT_DATETIME = self::TIMER_FORMAT_DATE . ' ' . self::TIMER_FORMAT_TIME;


    /**
     * Give your timer an unique individual name. The name should only contain the following chars:[a-zA-Z0-9-_]
     * It is good prctivce to combine in CamelCase the vendor of your extension and a decriptive title for your timer
     *
     * @return string
     */
    public static function selfName(): string;

    /**
     * This method define the item-value for the field tx_timer_selector, which has the structure  ['title','identifier'].
     *
     * @return string[]
     */
    public static function getSelectorItem(): array;

    /**
     * You may have two different situations.
     * 1. Situation: Every day the background-picture should be overridden from  9:00 to 18:00 by the special picture.
     *    In this case you needs to set 'useTimeZoneOfFrontend' (or an other named flexform-variable) to true.
     * 2. Situation: A global videostream-concert will begin at 12:00 AM in Berlin. You will set the timezone in
     *    the flexform-variable 'timeZoneOfEvent' (which you can name individually in your custom timer).
     *    In that 2. case you needs to set 'useTimeZoneOfFrontend' (or an other named flexform-variable) to false.
     * Each event-time must be defined for its special timezone. This is needed to sychronize it with the timezone in the frontend.
     * The method decide, wether the date is fixed to a special timezone or to the timezone of the frontend
     *
     * @param string $activeZoneName Name of the active timezone
     * @param array $params A simplified array with the direct parameters (originally derived from  Felxform)
     * @return string
     */
    public function getTimeZoneOfEvent($activeZoneName, array $params = []): string;

    /**
     * This method define an entry into an assoative-array for the timer-Identifier with the structure
     * ['identifier','<flexform or path to flexform-file>'].
     *
     * @return array
     */
    public static function getFlexformItem(): array;

    /**
     * The method test, if the parameter are valid or not
     *
     * @param array $params A simplified array with the direct parameters
     * @return bool
     */
    public function validate(array $params = []): bool;

    /**
     * It check, if the current date is in the range of ultimate periods
     * The range-variables of the flexforms are often named  'ultimateBeginningTimer' and 'ultimateEndingTimer'
     * This function allows makes it easy to restrikt periods ny counter or by range-dates.
     * If a timer only allows endless period, he will return forever a true.
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas
     * @param array $params The flexform-string converted to an array
     * @return bool
     */
    public function isAllowedInRange(DateTime $dateLikeEventZone, $params = []): bool;

    /**
     *
     * check, if an interval of the timer is for this datetime in $dateLikeEventZone
     * Remark 1: The base of timer-calculation is the timezone of $dateLikeEventZone
     * Remark 2: The usage of `ultimateBeginningTimer` and `ultimateEndingTimer` will not be analysed.
     *           This is done by the method `isAllowedInRange`.
     * Remark 3: IsActive should store the last Range
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas by method getTimeZoneOfEvent
     * @param array $params The flexform-string converted to an array
     * @return bool
     */
    public function isActive(DateTime $dateLikeEventZone, $params = []): bool;

    /**
     *
     * It give back the range of begin and end of range, if the datetime $dateLikeEventZone is included.
     * It may call isActive, if $dateLikeEventZone differ from the dateTime-value, which ist called by the last isActive()
     * Renark 1: This method is needed to calculate the forbidden ranges in the RangeTimer
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas by method getTimeZoneOfEvent
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange;

    /**
     *   the beginning should greater than the date in DateLikeEventZone, if it is possible
     *   if the nextRange do NOT exist,
     *     - the beginning and ending can be lower than the DateLikeEventZone-Date AND
     *     - the flag resultExist in TimerStartStopRange must set to false
     * Remark 1: The base of timer-calöculation is the timezone of $dateLikeEventZone
     * Remark 2: The usage of `ultimateBeginningTimer` and `ultimateEndingTimer` will not be analysed.
     *           This is done by the method `isAllowedInRange`.
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas by method getTimeZoneOfEvent
     * @param array $params The flexform-string converted to an array
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange;

    /**
     *   the ending should lower than the date in DateLikeEventZone, if it is possible
     *   if the prevRange do NOT exist,
     *     - the beginning and ending can be greater than the DateLikeEventZone-Date AND
     *     - the flag resultExist in TimerStartStopRange must set to false
     * Remark 1: The base of timer-calöculation is the timezone of $dateLikeEventZone
     * Remark 2: The usage of `ultimateBeginningTimer` and `ultimateEndingTimer` will not be analysed.
     *           This is done by the method `isAllowedInRange`.
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas by method getTimeZoneOfEvent
     * @param array $params The flexform-string converted to an array
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange;

}