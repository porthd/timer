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

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\TimerInterface;
use Porthd\Timer\Exception\TimerException;


class GeneralTimerUtility
{

    /**
     * tested
     *
     * @param string $activeZoneName
     * @param array $params
     * @return string
     */
    public static function getTimeZoneOfEvent(string $activeZoneName, array $params = []): string
    {
        if ((isset($params[TimerInterface::ARG_USE_ACTIVE_TIMEZONE])) &&
            (in_array($params[TimerInterface::ARG_USE_ACTIVE_TIMEZONE], ['1', 'true', 'TRUE', 1, true]))
        ) {
            return $activeZoneName;
        }
        if ((isset($params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT])) &&
            (is_numeric($params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT])) &&
            (($params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT] - (int)$params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT]) === 0)
        ) {
            $paramTimeZoneName = timezone_name_from_abbr("", ((int)$params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT]), 0);
        } else {
            if (!empty($params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT])) {
                $paramTimeZoneName = $params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT];
            } else {
                $paramTimeZoneName = $activeZoneName;
            }
        }
        if ($paramTimeZoneName === false) {
            throw new TimerException(
                'The timezone-definition in de Timer-Flexform `' . TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT . '` is unknown. `'
                . $params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT] .
                '`. Check the Definition in the current flexform or in the definitions for the RangeListTimer.',
                134456897
            );

        }
        return ((isset($params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT])) && (is_string($params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT])) ?
            $paramTimeZoneName :
            $activeZoneName
        );
    }

}