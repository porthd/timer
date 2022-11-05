<?php

namespace Porthd\Timer\Utilities;

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

use DateInterval;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\Exception\TimerException;

class GeneralTimerUtilityTest extends TestCase
{




    /**
     * @dataProvider dataProviderGetTimeZoneOfEvent
     * @test
     */
    public function getTimeZoneOfEvent($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or emopty dataprovider');
        } else {

            $myParams = $params['params'];
            $activeZone = $params['active'];
            $result = GeneralTimerUtility::getTimeZoneOfEvent($activeZone, $myParams);

            $this->assertEquals(
                $expects['result'],
                $result,
                $message
            );
        }
    }

}
