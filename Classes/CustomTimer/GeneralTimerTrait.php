<?php

namespace Porthd\Timer\CustomTimer;

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
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateUltimate(array $params = []): bool
    {
        $flag = (!empty($params[TimerInterface::ARG_ULTIMATE_RANGE_BEGINN]));
        $flag = ($flag && (false !== date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $params[TimerInterface::ARG_ULTIMATE_RANGE_BEGINN])
            )
        );
        $flag = $flag && (!empty($params[TimerInterface::ARG_ULTIMATE_RANGE_END]));
        return ($flag && (false !== date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $params[TimerInterface::ARG_ULTIMATE_RANGE_END])
            )
        );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateFlagZone(array $params = []): bool
    {
        return( (isset($params[TimerInterface::ARG_USE_ACTIVE_TIMEZONE])) &&
            (!is_array($params[TimerInterface::ARG_USE_ACTIVE_TIMEZONE]) &&
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
     * @param array $params
     * @return bool
     */
    protected function validateZone(array $params = []): bool
    {
        return isset($params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT]) &&
            TcaUtility::isTimeZoneInList(
                $params[TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT]
            );
    }
}
