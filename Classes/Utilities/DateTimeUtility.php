<?php

declare(strict_types=1);

namespace Porthd\Timer\Utilities;

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
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Exception\TimerException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The concept of the DateTime-Object in Frameworks is horrible. You can selectect bewtween a PHP
 * You have an Editor in New York, who wants to add his date in Local time.
 * You Store the date
 *
 *       //Enter your code here, enjoy!
 *
 *       $defDate = new \DateTime('1970/03/05 00:00:00'); // Contain the TimeZomne of the Server
 *                                                        // Bad Coding, because you Don't know
 *       $myDate = new \DateTime('1970/03/05 00:00:00', new \DateTimeZone('Pacific/Honolulu'));
 *                                                        // Contain the local time of Honolulu
 *
 *       // an later in Code
 *       echo($myDate->format('Y/m/d H:i:s')); // No surprise. You see 1970/03/05 00:00:00
 *       echo('<pre>'."\n");
 *       var_dump($defDate); // local Version of PHP-Server (cloud,...)
 *       echo('<pre>'."\n");
 *       var_dump($myDate);  // local Version of Honolulu (although in the Cloud)
 *       echo('<pre>'."\n");
 *       $myDate->setTimezone(new DateTimeZone( 'UTC')); // rebuild the time for the new timezone
 *       var_dump($myDate);
 *       echo($myDate->format('Y/m/d H:i:s')); // Real Horror. You don't see 1970/03/05 00:00:00, because Time is recalculated
 *
 * Problem you cant use Format, to generate the Output for a wisched Time-Zone
 * Class DateTimeUtility
 * @package Porthd\Timer\Utilities
 */
class DateTimeUtility
{
    public const BASE_TEST_DATE = '1980/1/1';


    protected const KEY_UNIT_MINUTE = 'TM';
    protected const KEY_UNIT_HOUR = 'TH';
    protected const KEY_UNIT_DAY = 'DD';
    protected const KEY_UNIT_WEEK = 'DW';
    protected const KEY_UNIT_MONTH = 'DM';
    protected const KEY_UNIT_YEAR = 'DY';

    /**
     * test: ?
     *
     * format a date including for the inherit zone of the date
     *
     * @param DateTime $date
     * @param string $format
     * @return string
     * @throws TimerException
     */
    public static function formatForZone(DateTime $date, string $format): string
    {
        /** @var DateTime $clone */
        $clone = clone $date;
        /** @var DateTimeZone $cloneZone */
        $cloneZone = $clone->getTimezone();
        $seconds = $cloneZone->getOffset($clone);
        /** @var DateInterval $offset */
        $offset = new DateInterval('PT' . abs($seconds) . 'S');
        if ($seconds > 0) {
            $clone->sub($offset);
        } else {
            if ($seconds < 0) {
                $clone->add($offset);
            }
        }
        return $clone->format($format);
    }


    /**
     * * test: ?
     *
     * @param DateTime $dateValue
     * @param string $eventZone
     * @param string $activeZone
     * @return DateTime
     * @throws TimerException
     */
    public static function normalizeTimezoneGap(DateTime $dateValue, $eventZone, $activeZone = '')
    {
        $myDate = clone $dateValue;
        $offset = DateTimeUtility::getTimezoneOffset($eventZone, $activeZone);
        if ($offset > 0) {
            $myDate->add(new DateInterval('PT' . $offset . 'S'));
        } else {
            $myDate->sub(new DateInterval('PT' . abs($offset) . 'S'));
        }
        return $myDate;
    }


    /**
     * test: ?
     * calculate the gap between two Timezone in seconds
     * if only one is give, the timegap is zero
     * if the Timezone is undefined, the method throws an exception
     *
     * @param string $eventTimeZone the name of the timezone i.e. 'europe/berlin' (TimerConst::DEFAULT_TIMEZONE), which has a gap to the base timezone
     * @param string $frontendTimeZone name of the base timezone
     * @return int timegag in seconds (positive an negative)
     * @throws TimerException
     */
    public static function getTimezoneOffset(
        string $eventTimeZone = TimerConst::DEFAULT_TIMEZONE,
        string $frontendTimeZone = TimerConst::DEFAULT_TIMEZONE
    ): int {
        if ((empty($frontendTimeZone)) || ($frontendTimeZone === $eventTimeZone)) {
            return 0;
        }
        $eventTimeZoneObj = new DateTimeZone($eventTimeZone);
        $frontendTimeZoneObj = new DateTimeZone($frontendTimeZone);
        $eventTime = new DateTime(
            DateTimeUtility::BASE_TEST_DATE,
            $eventTimeZoneObj
        ); // php does not calculate the GMT timestimp
        $frontendTime = new DateTime(DateTimeUtility::BASE_TEST_DATE, $frontendTimeZoneObj);
        return ($eventTimeZoneObj->getOffset($eventTime) - $frontendTimeZoneObj->getOffset($frontendTime));
    }

    /**
     * returns timestamp of Unix
     * @return int
     */
    public static function getCurrentTime()
    {
        return GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp') ?: time();
    }
    //
    //    /**
    //     * @param DateTime $destDateTime
    //     * @param DateTime $startTime
    //     * @param int $periodLength
    //     * @param string $periodUnit
    //     * @return int
    //     * @throws TimerException
    //     */
    //    public static function diffPeriod(DateTime $destDateTime, DateTime $startTime, int $periodLength, string $periodUnit): int
    //    {
    //        $calcDateTime = clone $destDateTime;
    //        $differenz = $calcDateTime->diff($startTime);
    //        switch ($periodUnit) {
    //            case self::KEY_UNIT_MINUTE:
    //                $rawCount = floor(
    //                    (($differenz->days ?: 0) * 1440 + (($differenz->h ?: 0) * 60) + ($differenz->i ?: 0)) / abs($periodLength)
    //                );
    //                break;
    //            case self::KEY_UNIT_HOUR:
    //                $rawCount = floor(
    //                    (($differenz->days ?: 0) * 24 + $differenz->h) / abs($periodLength)
    //                );
    //                break;
    //            case self::KEY_UNIT_DAY:
    //                $rawCount = floor(
    //                    ($differenz->days ?: 0) / abs($periodLength)
    //                );
    //
    //                break;
    //            case self::KEY_UNIT_WEEK:
    //                $rawCount = floor(
    //                    ($differenz->days ?: 0) / abs(7 * $periodLength)
    //                );
    //                break;
    //            case self::KEY_UNIT_MONTH:
    //                $rawCount = floor(
    //                    (($differenz->m ?: 0) + ($differenz->y ?: 0) * 12) / abs($periodLength)
    //                );
    //                break;
    //            case self::KEY_UNIT_YEAR:
    //                $rawCount = floor(
    //                    ($differenz->y ?: 0) / abs($periodLength)
    //                );
    //                break;
    //            default:
    //                throw new TimerException(
    //                    'The period-Unit is not defined by this extension `' .
    //                    TimerConst::EXTENSION_NAME . '`.' . ' Check your spelling and the definitions in the flexforms. ' .
    //                    'Allowed are `' . self::KEY_UNIT_MINUTE . '`,`' . self::KEY_UNIT_HOUR . '`,`' .
    //                    self::KEY_UNIT_DAY . '`,`' . self::KEY_UNIT_MONTH . '` and `' .
    //                    self::KEY_UNIT_YEAR . '`.',
    //                    1601984604
    //                );
    //        }
    //        if ($differenz->invert) {
    //            $toZeroCountPeriod = -$rawCount + 1;
    //        } else {
    //            $toZeroCountPeriod = $rawCount;
    //        }
    //        return (int)$toZeroCountPeriod;
    //    }

    /**
     * @param DateTime $destDateTime
     * @param DateTime $startTime
     * @param int $periodLength
     * @param string $periodUnit
     * @return int
     * @throws TimerException
     */
    public static function diffPeriod(DateTime $destDateTime, DateTime $startTime, int $periodLength, string $periodUnit): int
    {
        $calcDateTime = clone $destDateTime;
        $differenz = $calcDateTime->diff($startTime);
        $diffSeconds = $calcDateTime->getTimestamp() - $startTime->getTimestamp();
        $flagSeconds = true;
        // detect the periods, which are between the calc-date and dest-date
        switch ($periodUnit) {
            case self::KEY_UNIT_MINUTE:
                $diffMinutes = floor($diffSeconds / 60);
                $rawCount = floor($diffMinutes / abs($periodLength));
                break;
            case self::KEY_UNIT_HOUR:
                $diffHours = floor($diffSeconds / 3600);
                $rawCount = floor($diffHours / abs($periodLength));
                break;
            case self::KEY_UNIT_DAY:
                $days = floor($diffSeconds / 86400);
                $rawCount = floor($days / abs($periodLength));
                break;
            case self::KEY_UNIT_WEEK:
                $weeks = floor($diffSeconds / 604800);
                $rawCount = floor($weeks / abs($periodLength));
                break;
            case self::KEY_UNIT_MONTH:
                $rawCount = floor(
                    (($differenz->m ?: 0) + ($differenz->y ?: 0) * 12) / abs($periodLength)
                );
                $flagSeconds = false;
                break;
            case self::KEY_UNIT_YEAR:
                $rawCount = floor(
                    ($differenz->y ?: 0) / abs($periodLength)
                );
                $flagSeconds = false;
                break;
            default:
                throw new TimerException(
                    'The period-Unit is not defined by this extension `' .
                    TimerConst::EXTENSION_NAME . '`.' . ' Check your spelling and the definitions in the flexforms. ' .
                    'Allowed are `' . self::KEY_UNIT_MINUTE . '`,`' . self::KEY_UNIT_HOUR . '`,`' .
                    self::KEY_UNIT_DAY . '`,`' . self::KEY_UNIT_MONTH . '` and `' .
                    self::KEY_UNIT_YEAR . '`.',
                    1601984604
                );
        }
        if ($flagSeconds) {
            if ($rawCount < 0) {
                $toZeroCountPeriod = -$rawCount + 1;
            } else {
                $toZeroCountPeriod = $rawCount;
            }

        } else {
            if ($differenz->invert) {
                $toZeroCountPeriod = -$rawCount + 1;
            } else {
                $toZeroCountPeriod = $rawCount;
            }
        }
        return (int)$toZeroCountPeriod;
    }

    /**
     * @return DateTime|false
     * @throws AspectNotFoundException
     */
    public static function getCurrentExecTime()
    {
        $execTimeIso = GeneralUtility::makeInstance(Context::class)
            ->getPropertyFromAspect('date', 'iso');
        // Reading the current data instead of $GLOBALS['EXEC_TIME']
        return DateTime::createFromFormat(DATE_ATOM, $execTimeIso);
    }
}
