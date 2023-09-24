<?php
declare(strict_types=1);

namespace Porthd\Timer\Tests\Unit\Utilities;

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
use Porthd\Timer\Utilities\DateTimeUtility;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\Interfaces\TimerInterface;

class DateTimeUtilityTest extends TestCase
{
    public function dataProviderformatForZoneGivenDateTimeObjectAndTimeZone()
    {
        $outputFormat = TimerInterface::TIMER_FORMAT_DATETIME;
        $dateString = '2010-07-05T00:00:05';
        $timezones = [
            'Europe/Amsterdam',
            'America/New_York',
            'Pacific/Honolulu',
            'Pacific/Kiritimati',
        ];

        $result = [
            [
                'message' => 'Test and exspection contains the same timezone. No difference exspeted.',
                [
                    'utcDateTimeString' => (new DateTime($dateString, new DateTimeZone("UTC")))->format($outputFormat),
                    'toUtcEqual' => true,
                ],
                [
                    'zone' => 'UTC',
                    'dateTimeString' => $dateString, // new DateTime($dateString, new DateTimeZone("UTC")),
                    'format' => $outputFormat,
                ],
            ],
        ];
        foreach ($timezones as $timezone) {
            $result[] = [
                'message' => 'Test UTC-time against a recalculated datetime for simliar Timestring in the timezone `' . $timezone . '`.',
                [
                    'utcDateTimeString' => (new DateTime($dateString, new DateTimeZone("UTC")))->format($outputFormat),
                    'toUtcEqual' => true,
                ],
                [
                    'zone' => $timezone,
                    'dateTimeString' => $dateString, // new DateTime($dateString, new DateTimeZone("UTC")),
                    'format' => $outputFormat,
                ],
            ];
        }
        return $result;
    }

    /**
     * Id on't work currently, because of dependencys to TYPO3-Framework 20190315
     *
     * @dataProvider dataProviderformatForZoneGivenDateTimeObjectAndTimeZone
     * @test
     */
    public function formatForZoneGivenDateTimeObjectAndTimeZone($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or emopty dataprovider');
        } else {
            // The DateTime does not calculate. It only makes an entry of both parameter. If you did not define a zone, php choose the zone of his system.
            // In PHP for typo3 is/should be the default timezone 'UTC'.
            $testDateTime = new DateTime($params['dateTimeString'], new DateTimeZone($params['zone']));
            $utcTestTime = new DateTime($params['dateTimeString'], new DateTimeZone('UTC'));
            // The DateTime-Object store every time the UTC-value odf Time an the TimeZone
            // The offset is relativ to the UTC-time. This implies the substraction:  Offset = Time(TimeZone)-time(UTC)[seconds]
            $offsetToUtc = (new DateTimeZone($params['zone']))->getOffset($utcTestTime);
            // $offsetToZone = (new DateTimeZone('UTC'))->getOffset($testDateTime); // this is everytime zeror
            // normalize stored DateTime to UTC teime, so that in the TimeZone the Value of DateTimeString will be shown
            if ($offsetToUtc >= 0) {
                $testDateTime->add(new DateInterval('PT' . abs($offsetToUtc) . 'S'));
            } else {
                $testDateTime->sub(new DateInterval('PT' . abs($offsetToUtc) . 'S'));
            }

            $resultDateTimeString = DateTimeUtility::formatForZone($testDateTime, $params['format']);
            $this->assertEquals(
                $resultDateTimeString,
                $expects['utcDateTimeString'],
                'recalculated? (' . $message . ')'
            );
            $this->assertEquals(
                ($resultDateTimeString === $expects['utcDateTimeString']),
                $expects['toUtcEqual'],
                'Equal to UTC-String? (' . $message . ')'
            );
        }
    }
}
