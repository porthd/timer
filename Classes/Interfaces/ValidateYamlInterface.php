<?php

namespace Porthd\Timer\Interfaces;

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
use Porthd\Timer\Exception\TimerException;

interface ValidateYamlInterface
{
    /**
     * The method checks, if a flatten array the ending should lower than the date in DateLikeEventZone, if it is possible
     *
     * @param array $yamlConfig
     * @param $pathOfYamlFile
     * @throws TimerException
     *
     */
    public function validateYamlOrException(array $yamlConfig, $pathOfYamlFile): void;

}