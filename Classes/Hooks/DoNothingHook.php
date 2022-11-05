<?php

namespace Porthd\Timer\Hooks;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 Dr. Dieter Porthd <info@mobger.de>
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

use Porthd\Checker\Interfaces\DataDiggerOnAlternativeWayInterface;
use Porthd\Checker\Utility\Repository\GeneralRepository;
use Porthd\Timer\Constants\TimerConst;


class DoNothingHook
{

    /**
     * @param $paramArray
     * @return mixed
     */
    public static function modifyDefaultTimezoneByHook($paramArray)
    {
        // only an example for copy
        // do what ever you want, to get the wished timezone
        return ($paramArray['default'] ?: TimerConst::DEFAULT_TIME_ZONE);
    }

}