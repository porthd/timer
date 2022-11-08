<?php

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
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Exception\TimerException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
class CustomTimerUtility
{

    protected const DEFAULT_BEGIN_PERIOD = "1753-01-01 00:00:00";  // Because of calandar-relaunch (Julian, modern-counting
    protected const DEFAULT_ENDING_PERIOD = "9999-12-31 23:59:59";


    /**
     * @param string $startDateTime
     * @param string $stopDateTime
     * @return string|null
     */
    public static function generateGeneralBeginEnd(string $startDateTime, string $stopDateTime)
    {
        if ($startDateTime === self::DEFAULT_BEGIN_PERIOD) {
            if ($stopDateTime === self::DEFAULT_ENDING_PERIOD) {
                $result = LocalizationUtility::translate(
                    'content.timer.periodMessage.general.beginEnd.default',
                    TimerConst::EXTENSION_NAME
                );

            } else {
                $result = LocalizationUtility::translate(
                    'content.timer.periodMessage.general.beginEnd.stop.1',
                    TimerConst::EXTENSION_NAME,
                    [
                        $stopDateTime,
                    ]
                );

            }
        } else {
            if ($stopDateTime === self::DEFAULT_ENDING_PERIOD) {
                $result = LocalizationUtility::translate(
                    'content.timer.periodMessage.general.beginEnd.start.1',
                    TimerConst::EXTENSION_NAME,
                    [
                        $startDateTime,
                    ]
                );

            } else {
                $result = LocalizationUtility::translate(
                    'content.timer.periodMessage.general.beginEnd.2',
                    TimerConst::EXTENSION_NAME,
                    [
                        $startDateTime,
                        $stopDateTime,
                    ]
                );
            }

        }

        return $result;

    }

    /**
     * @param $timeZoneOfEvent
     * @param $flagActiveTimeZone
     * @return string|null
     */
    public static function generateGeneralTimeZone($timeZoneOfEvent, $flagActiveTimeZone)
    {
        if (!empty($timeZoneOfEvent)) {
            $generalTimeZone = LocalizationUtility::translate(
                'content.timer.periodMessage.general.timeZone.event.1',
                TimerConst::EXTENSION_NAME,
                [
                    $timeZoneOfEvent,
                ]
            );
        } else {
            if (empty($flagActiveTimeZone)) {
                $context = GeneralUtility::makeInstance(Context::class);
                // Reading the current data instead of $GLOBALS['EXEC_TIME']
                $currentZone = $context->getPropertyFromAspect('date', 'timezone');
                $generalTimeZone = LocalizationUtility::translate(
                    'content.timer.periodMessage.general.timeZone.server.1',
                    TimerConst::EXTENSION_NAME,
                    [
                        $currentZone,
                    ]
                );
            } else {
                $generalTimeZone = LocalizationUtility::translate(
                    'content.timer.periodMessage.general.timeZone.surfer',
                    TimerConst::EXTENSION_NAME
                );
            }
        }
        return $generalTimeZone;
    }


    /**
     * @param $startSecondsOfDay
     * @param $durationMin
     * @return string
     */
    public static function generateGeneralStartAndDuration($startSecondsOfDay, $durationMin)
    {
        $plusMinus = ($durationMin > 0) ? 'plus' : 'minus';
        $startTime = (date_create_from_format(
            'U',
            $startSecondsOfDay
        ))->format(TimerConst::TIMER_FORMAT_TIME);
        $durationMinReadble = self::generateReadbleTimeFromMin((int)$durationMin);
        return (LocalizationUtility::translate(
                'content.timer.periodMessage.general.period.' . $plusMinus . '.2',
                TimerConst::EXTENSION_NAME,
                [
                    $startTime,
                    $durationMinReadble,
                ]
            ) ?? '');
    }

    /**
     * @param int $checkMin
     * @return mixed|string|null
     */
    public static function generateReadbleTimeFromMin(int $checkMin)
    {
        $minutes = abs($checkMin);
        $result['min'] = floor($minutes % 60);
        $hourMin = floor($minutes / 60);
        $result['hour'] = floor($hourMin % 24);
        $dayHour = floor($hourMin / 24);
        $result['day'] = floor($dayHour % 7);
        $result['week'] = floor($dayHour / 7);
        $cascade = [];
        foreach (['week' => 'w', 'day' => 'd', 'hour' => 'h', 'min' => 'min'] as $key => $unit) {
            if ($result[$key] !== 0) {
                if ($result[$key] > 1) {
                    $cascade[] = (LocalizationUtility::translate(
                            'content.timer.periodMessage.general.timeparts.' . $key . '.many.1',
                            TimerConst::EXTENSION_NAME,
                            [
                                $result[$key],
                            ]
                        ) ?? $result[$key] . ' ' . $unit);
                } else {
                    $cascade[] = (LocalizationUtility::translate(
                            'content.timer.periodMessage.general.timeparts.' . $key . '.single',
                            TimerConst::EXTENSION_NAME
                        ) ?? $result[$key] . ' ' . $unit);
                }
            }
        }
        if (empty($cascade)) {
            return (LocalizationUtility::translate(
                    'content.timer.periodMessage.general.timeparts.zero',
                    TimerConst::EXTENSION_NAME
                ) ?? '0 min');
        }
        if (count($cascade) === 1) {
            return array_pop($cascade);
        }

        $last = array_pop($cascade);
        $rest = implode(', ', $cascade);
        return (LocalizationUtility::translate(
                'content.timer.periodMessage.general.timeparts.combine.2',
                TimerConst::EXTENSION_NAME,
                [$rest, $last]
            ) ?? $rest . ', ' . $last
        );
    }

    public static function getParameterActiveWeekday($activeWeekday)
    {
        $result = 127;
        if ((isset($activeWeekday)) &&
            (is_numeric($activeWeekday))
        ) {
            $value = (int)$activeWeekday;
            $diff = $activeWeekday - $value;
            // <127 because at least one weekday schuold not be set
            if (($diff === 0) && ($value > 0) && ($value < 128)) {
                return $value;
            }
        }

        return $result;
    }


    /**
     * @param int $checkMin
     * @return mixed|string|null
     */
    public static function generateGeneralActiveWeekdayList(int $activeWeekday)
    {
        if (!empty($activeWeekday)) {
            $weekdays = [];
            $bitsOfWeekdays = self::getParameterActiveWeekday($activeWeekday);
            foreach ([
                         'mo' => 1,
                         'tu' => 2,
                         'we' => 4,
                         'th' => 8,
                         'fr' => 16,
                         'sa' => 32,
                         'su' => 64,
                     ] as $dayShortcut => $bit
            ) {
                if (($bit & $bitsOfWeekdays) === $bit) {
                    $weekdays[] = LocalizationUtility::translate(
                        'content.timer.periodMessage.general.weekday.' . $dayShortcut,
                        TimerConst::EXTENSION_NAME
                    );
                }
            }
            $countWeekdays = count($weekdays);
            if ($countWeekdays === 0) {
                $result = '';
            } else {
                if ($countWeekdays >= 2) {
                    $last = array_pop($weekdays);
                    $weekdaysText = implode(', ', $weekdays);
                    $result = LocalizationUtility::translate(
                        'content.timer.periodMessage.dailyTimer.weekdayList.more.1',
                        TimerConst::EXTENSION_NAME,
                        [$weekdaysText, $last]
                    );
                } else {
                    $result = LocalizationUtility::translate(
                        'content.timer.periodMessage.dailyTimer.weekdayList.one.1',
                        TimerConst::EXTENSION_NAME,
                        [$weekdays[0]]
                    );
                }
            }

            return $result;
        }
        return '';
    }
}

