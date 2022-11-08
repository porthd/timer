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

use DateInterval;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SunriseRelTimerTest extends TestCase
{
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE =TimerInterface::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerInterface::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerInterface::ARG_ULTIMATE_RANGE_END;
    protected const NAME_TIMER = 'txTimerSunriseRel';
    protected const SOME_NOT_EMPTY_VALUE = 'some value';
    protected const ALLOWED_TIME_ZONE = 'UTC';


    /**
     * @var SunriseRelTimer
     */
    protected $subject = null;

    protected function simulatePartOfGlobalsTypo3Array()
    {
        $GLOBALS = [];
        $GLOBALS['TYPO3_CONF_VARS'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['timer'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['timer']['changeListOfTimezones'] = [];
        $GLOBALS['EXEC_TIME'] = 1609088941; // 12/27/2020 @ 5:09pm (UTC)
    }

    protected function resolveGlobalsTypo3Array()
    {
        unset($GLOBALS);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->simulatePartOfGlobalsTypo3Array();
        $this->subject = new SunriseRelTimer();
    }

    protected function tearDown(): void
    {
        $this->resolveGlobalsTypo3Array();
        parent::tearDown();
    }

    /**
     * the ultimate green test
     * @test
     */
    public function checkIfIAmGreen()
    {
        $this->assertEquals((true), (true), 'I should an evergreen, but I am incoplete! :)');
    }

    /**
     * @test
     */
    public function selfName()
    {
        $this->assertEquals(self::NAME_TIMER,
            $this->subject::selfName(),
            'The name musst be defined.');
    }


    /**
     * @test
     */
    public function getSelectorItem()
    {
        $result = $this->subject->getSelectorItem();
        $this->assertIsArray($result,
            'The result must be an array.');
        $this->assertGreaterThan(1,
            count($result),
            'The array  must contain at least two items.');
        $this->assertIsString($result[0],
            'The first item must be an string.');
        $this->assertEquals($result[1],
            self::NAME_TIMER,
            'The first item must be an string.');
    }

    /**
     * @test
     */
    public function getFlexformItem()
    {
        $result = $this->subject->getFlexformItem();
        $this->assertIsArray($result,
            'The result must be an array.');
        $this->assertEquals(1,
            count($result),
            'The array  must contain one Item.');
        $this->assertEquals(array_keys($result),
            [self::NAME_TIMER],
            'The key must the name of the timer.');
        $this->assertIsString($result[self::NAME_TIMER],
            'The value must be type of string.');
        $rootPath = $_ENV['TYPO3_PATH_ROOT']; //Test relative to root-Path beginning in  ...web/
        $filePath = $result[self::NAME_TIMER];
        if (strpos($filePath, TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH) === 0) {
            $resultPath = $rootPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . substr($filePath,
                    strlen(TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH));
        } else {
            $resultPath = $rootPath . DIRECTORY_SEPARATOR . $filePath;
        }
        $flag = (!empty($resultPath)) && file_exists($resultPath);
        $this->assertTrue($flag,
            'The file with the flexform content exist.');
        $fileContent = GeneralUtility::getURL($resultPath);
        $flexArray = simplexml_load_string($fileContent);
        $this->assertTrue((!(!$flexArray)),
            'The filecontent is valid xml.');
    }

    public function dataProvider_isAllowedInRange()
    {
        $testDate = date_create_from_format('Y-m-d H:i:s', '2020-12-31 12:00:00', new DateTimeZone('Europe/Berlin'));
        $minusOneSecond = clone $testDate;
        $minusOneSecond->sub(new DateInterval('PT1S'));
        $addOneSecond = clone $testDate;
        $addOneSecond->add(new DateInterval('PT1S'));
        $rest = [];
        $result = [];

        $result[] = [
            'message' => 'The testdate is valid, if the testdate is in the middle of the ultimate range..',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'testValue' => $testDate,
                'rest' => $rest,
                'general' => [
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The validation will be okay. if the ultimate start DateTime-Zone start at the same time.',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'testValue' => $testDate,
                'rest' => $rest,
                'general' => [
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '2020-12-31 12:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The validation will be fail. if the ultimate start DateTime-Zone starts one second later.',
            'expects' => [
                'result' => false,
            ],
            'params' => [
                'testValue' => $minusOneSecond,
                'rest' => $rest,
                'general' => [
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '2020-12-31 12:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The validation will be okay. if the ultimate start DateTime-Zone end at the same time.',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'testValue' => $testDate,
                'rest' => $rest,
                'general' => [
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '2020-12-31 12:00:00',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The validation will be okay. if the ultimate start DateTime-Zone ends one second earlier.',
            'expects' => [
                'result' => false,
            ],
            'params' => [
                'testValue' => $addOneSecond,
                'rest' => $rest,
                'general' => [
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '2020-12-31 12:00:00',
                ],
            ],
        ];
        return $result;
    }

    /**
     * @dataProvider dataProvider_isAllowedInRange
     * @test
     */
    public function isAllowedInRange($message, $expects, $params)
    {

        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {

            $paramTest = array_merge($params['rest'], $params['general']);
            $testValue = $params['testValue'];
            $this->assertEquals(
                $expects['result'],
                $this->subject->isAllowedInRange($testValue, $paramTest),
                $message
            );

        }
    }

    /**
     * @return array[]
     */
    public function dataProviderValidateGeneralByVariationArgumentsInParam()
    {
        $rest = [
            'sunPosition' => 'sunrise',
            'relMinToSelectedTimerEvent' => '12',
            'durationMinutes' => '120',
            'durationNatural' => 'defined',
            'latitude' => '53.0792962', // latitude Bremen 	53.0792962
            'longitude' => '8.8016937', // longitude Bremen 	8.8016937
        ];
        $result = [];
        // variation of obsolete parameter
        $list = [
            'useTimeZoneOfFrontend' => true,
            'timeZoneOfEvent' => true,
            'ultimateBeginningTimer' => false,
            'ultimateEndingTimer' => false,
        ];
        foreach ($list as $unsetParam => $expects
        ) {
            if (empty($expects)) {
                continue;
            }

            $item = [
                'message' => 'The validation will ' . ($expects ? 'be okay' : 'fail') . ', if the parameter `' . $unsetParam . '` is missing.',
                'expects' => [
                    'result' => $expects,
                ],
                'params' => [
                    'rest' => $rest,
                    'general' => [
                        'useTimeZoneOfFrontend' => 0,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            unset($item['params']['general'][$unsetParam]);
            $result[] = $item;
        }
        // Variation for useTimeZoneOfFrontend
        foreach ([null, false, new Datetime(), 'hallo', ''] as $value) {
            $result[] = [
                'message' => 'The validation is okay, because the parameter `useTimeZoneOfFrontend` is optional and will not tested for type.',
                [
                    'result' => true,
                ],
                [
                    'rest' => $rest,
                    'general' => [
                        'useTimeZoneOfFrontend' => $value,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
//        // Variation for useTimeZoneOfFrontend
        foreach ([
                     'UTC' => true,
                     '' => false,
                     'Europe/Berlin' => true,
                     'Kumpel/Dumpel' => false,
                 ] as $zoneVal => $expects) {
            $result[] = [
                'message' => 'The validation of `timeZoneOfEvent` will ' . ($expects ? 'be okay' : 'fail') .
                    ', if the parameter for `timeZoneOfEvent` is ' . $zoneVal . '.',
                [
                    'result' => $expects,
                ],
                [
                    'rest' => $rest,
                    'general' => [
                        'timeZoneOfEvent' => $zoneVal,
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];

        }
        // Variation for ultimateBeginningTimer
        foreach ([
                     '0002-01-01 13:00:00' => true,
                     '0000-01-01 00:00:00' => true,
                     '-1111-01-01 00:00:00' => false,
                     '' => false,
                 ] as $timeVal => $expects) {
            $result[] = [
                'message' => 'The validation of `ultimateBeginningTimer` will ' . ($expects ? 'be okay' : 'fail') .
                    ', if the parameter is `' . $timeVal . '`.',
                [
                    'result' => $expects,
                ],
                [
                    'rest' => $rest,
                    'general' => [
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => $timeVal,
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        // Variation for ultimateEndingTimer
        foreach ([
                     '0002-01-01 13:00:00' => true,
                     '0000-01-01 00:00:00' => true,
                     '-1111-01-01 00:00:00' => false,
                     '' => false,
                 ] as $timeVal => $expects) {
            $result[] = [
                'message' => 'The validation of `ultimateEndingTimer` will ' . ($expects ? 'be okay' : 'fail') .
                    ', if the parameter is `' . $timeVal . '`.',
                [
                    'result' => $expects,
                ],
                [
                    'rest' => $rest,
                    'general' => [
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => $timeVal,
                    ],
                ],
            ];

        }
        return $result;
    }

    /**
     * @dataProvider dataProviderValidateGeneralByVariationArgumentsInParam
     * @test
     */
    public function validateGeneralByVariationArgumentsInParam($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or emopty dataprovider');
        } else {

            $paramTest = array_merge($params['rest'], $params['general']);
            $this->assertEquals(
                $expects['result'],
                $this->subject->validate($paramTest),
                $message
            );

        }
    }

    /**
     * @return array[]
     */
    public function dataProviderValidateSpeciallByVariationArgumentsInParam()
    {
        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];

        $result = [];
        /* test allowed minimal structure */
        $result[] = [
            'message' => 'The test randomly is correct.',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'required' => [
                    'sunPosition' => 'sunrise',
                    'relMinToSelectedTimerEvent' => '12',
                    'durationMinutes' => '120',
                    'durationNatural' => 'defined',
                    'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                    'longitude' => '8.8016937', // longitude Bremen 	8.8016937
                ],
                'optional' => [

                ],
                'general' => $general,
            ],
        ];
        // unset some parameters to provoke an failing
        foreach (['sunPosition', 'durationMinutes', 'latitude', 'longitude', 'durationNatural'] as $myUnset) {
            $item = [
                'message' => 'The test fails, because the parameter `' . $myUnset . '` is missing.(being unsetted)',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'sunPosition' => 'sunrise',
                        'relMinToSelectedTimerEvent' => '12',
                        'durationMinutes' => '120',
                        'durationNatural' => 'defined',
                        'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                        'longitude' => '8.8016937', // longitude Bremen 	8.8016937
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];
            unset($item['params']['required'][$myUnset]);
            $result[] = $item;
        }
        // unset some parameters to provoke an failing
        foreach (['relMinToSelectedTimerEvent',] as $myUnset) {
            $item = [
                'message' => 'The parameter `' . $myUnset . '` is missing, but the test is okay. The parameter is optional.',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'required' => [
                        'sunPosition' => 'sunrise',
                        'relMinToSelectedTimerEvent' => '12',
                        'durationMinutes' => '120',
                        'durationNatural' => 'defined',
                        'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                        'longitude' => '8.8016937', // longitude Bremen 	8.8016937
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];
            unset($item['params']['required'][$myUnset]);
            $result[] = $item;
        }
        // variation of requiered parmeters
        // Variation moonrise missing
        foreach ([
                     'sunrise' => true,
                     'sunset' => true,
                     'transit' => true,
                     'civil_twilight_begin' => true,
                     'civil_twilight_end' => true,
                     'nautical_twilight_begin' => true,
                     'nautical_twilight_end' => true,
                     'astronomical_twilight_begin' => true,
                     'astronomical_twilight_end' => true,
                     'twilight' => false,
                     1 => false,
                     0 => false,
                     'moonhigh' => false,
                 ] as $sunPosition => $myExpects) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The test for sunPosition with `' . $sunPosition .
                    ($myExpects ? '` is correct' : '` is NOT correct') . '.',
                [
                    'result' => $myExpects,
                ],
                [
                    'required' => [
                        'sunPosition' => $sunPosition, // Variation
                        'relMinToSelectedTimerEvent' => '12',
                        'durationMinutes' => '120', // Variation
                        'durationNatural' => 'defined',
                        'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                        'longitude' => '8.8016937', // longitude Bremen 	8.8016937
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];
        }

        foreach ([
                     0 => false,
                     'sunrise' => true,
                     'sunset' => true,
                     'transit' => true,
                     'civil_twilight_begin' => true,
                     'civil_twilight_end' => true,
                     'nautical_twilight_begin' => true,
                     'nautical_twilight_end' => true,
                     'astronomical_twilight_begin' => true,
                     'astronomical_twilight_end' => true,
                     'defined' => true,
                     'twilight' => false,
                     1 => false,
                     'moonhigh' => false,
                 ] as $naturalDuration => $myExpects) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The test for naturalDuration with `' . $naturalDuration .
                    ($myExpects ? '` is correct' : '` is NOT correct') . '.',
                [
                    'result' => $myExpects,
                ],
                [
                    'required' => [
                        'sunPosition' => 'sunrise', // Variation
                        'relMinToSelectedTimerEvent' => '12',
                        'durationMinutes' => '120', // Variation
                        'durationNatural' => $naturalDuration,
                        'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                        'longitude' => '8.8016937', // longitude Bremen 	8.8016937
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];
        }

        // variation of durationMinutes
        foreach ([
                     1341 => false,
                     -1341 => false,
                     -1340 => true,
                     1340 => true,
                     '-1340' => true,
                     '1340' => true,
                     '-100' => true,
                     '10' => true,
                     '-10.1' => false,
                     '10.1' => false,
                     '-10.0' => false,
                     '10.0' => false,
                     0 => true,
                     '0.0' => false,
                     1 => true,
                     '-1' => true,
                 ] as $myMin => $myExpects
        ) {
            $result[] = [
                'message' => 'The test for relMinToSelectedTimerEvent with `' . $myMin .
                    ($myExpects ? '` is correct' : '` is NOT correct') . '.',
                'expects' => [
                    'result' => $myExpects,
                ],
                'params' => [
                    'required' => [
                        'sunPosition' => 'sunrise',
                        'relMinToSelectedTimerEvent' => $myMin, // Variation
                        'durationMinutes' => '120',
                        'durationNatural' => 'defined',
                        'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                        'longitude' => '8.8016937', // longitude Bremen 	8.8016937
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];

        }

        // variation of durationMinutes
        foreach ([
                     1341 => false,
                     -1341 => false,
                     -1340 => true,
                     1340 => true,
                     '-1340' => true,
                     '1340' => true,
                     '-100' => true,
                     '10' => true,
                     '-10.1' => false,
                     '10.1' => false,
                     '-10.0' => false,
                     '10.0' => false,
                     0 => false,
                     '0.0' => false,
                     1 => true,
                     '-1' => true,
                 ] as $myMin => $myExpects
        ) {
            $result[] = [
                'message' => 'The test for durationMinutes with `' . $myMin .
                    ($myExpects ? '` is correct' : '` is NOT correct') . '.',
                'expects' => [
                    'result' => $myExpects,
                ],
                'params' => [
                    'required' => [
                        'sunPosition' => 'sunrise',
                        'relMinToSelectedTimerEvent' => '12',
                        'durationMinutes' => $myMin, // Variation
                        'durationNatural' => 'defined',
                        'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                        'longitude' => '8.8016937', // longitude Bremen 	8.8016937
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];

        }
        // variation of Latitude
        foreach ([
                     91 => false,
                     '90.01' => false,
                     90 => true,
                     '90' => true,
                     '55.3' => true,
                     '0' => true,
                     '-0,0' => true,
                     0 => true,
                     '+0.0' => true,
                     -91 => false,
                     '-90.01' => false,
                     -90 => true,
                     '-90' => true,
                     '-55.3' => true,
                 ] as $myLati => $myExpects
        ) {
            $result[] = [
                'message' => 'The test for latitude with `' . $myLati .
                    ($myExpects ? '` is correct' : '` is NOT correct') . '.',
                'expects' => [
                    'result' => $myExpects,
                ],
                'params' => [
                    'required' => [
                        'sunPosition' => 'sunrise',
                        'relMinToSelectedTimerEvent' => '12',
                        'durationMinutes' => '120',
                        'durationNatural' => 'defined',
                        'latitude' => $myLati, // Variation
                        'longitude' => '8.8016937', // longitude Bremen 	8.8016937
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];

        }

        // variation of Latitude
        foreach ([
                     181 => false,
                     '180.01' => false,
                     180 => true,
                     '180' => true,
                     '55.3' => true,
                     '0' => true,
                     '-0,0' => true,
                     0 => true,
                     '+0.0' => true,
                     -181 => false,
                     '-180.01' => false,
                     -180 => true,
                     '-180' => true,
                     '-55.3' => true,
                 ] as $myLongi => $myExpects
        ) {
            $result[] = [
                'message' => 'The test for latitude with `' . $myLongi .
                    ($myExpects ? '` is correct' : '` is NOT correct') . '.',
                'expects' => [
                    'result' => $myExpects,
                ],
                'params' => [
                    'required' => [
                        'sunPosition' => 'sunrise',
                        'relMinToSelectedTimerEvent' => '12',
                        'durationMinutes' => '120',
                        'durationNatural' => 'defined',
                        'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                        'longitude' => $myLongi, // Variation
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];

        }


        return $result;
    }

    /**
     * @dataProvider dataProviderValidateSpeciallByVariationArgumentsInParam
     * @test
     */
    public function validateSpeciallByVariationArgumentsInParam($message, $expects, $params)
    {

        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or emopty dataprovider');
        } else {

            $paramTest = array_merge($params['required'], $params['optional'], $params['general']);
            $this->assertEquals(
                $expects['result'],
                $this->subject->validate($paramTest),
                $message
            );

        }
    }

    public function dataProviderIsActive()
    {
        /**
         * Location
         * Latitude 53.073635
         * Longitude    8.806422
         * DMS Lat    53° 4' 25.0860'' N
         * DMS Long    8° 48' 23.1192'' E
         * UTM Easting    487,031.03
         * UTM Northing    5,880,479.38
         * Category    Cities
         * Country Code    DE
         * Zoom Level    11
         *
         * 'Summertime'
         * Astronomische Morgendämmerung    02:09 - 03:55    1 Std. 45 Min.
         * Nautische Morgendämmerung    03:55 - 04:55    1 Std.
         * Bürgerliche Morgendämmerung    04:55 - 05:39    43 Min.
         * Sonnenaufgang    05:39
         * Golden Hour    05:39 - 06:28    49 Min.
         * Zenit    13:31
         * Golden Hour    20:33 - 21:23    49 Min.
         * Sonnenuntergang    21:23
         * Bürgerliche Abenddämmerung    21:23 - 22:06    43 Min.
         * Nautische Abenddämmerung    22:06 - 23:07    1 Std.
         * Astronomische Abenddämmerung    23:07 - 00:52    1 Std. 45 Min.
         */
        $latitude = 53.073635;
        $longitude = 8.806422;
        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];

        $result = [];
//        'pos' => sunrise, sunset, transit, civil_twilight_begin, civil_twilight_end, nautical_twilight_begin, nautical_twilight_end, astronomical_twilight_begin, astronomical_twilight_end
//        foreach ([
//
//                     [
//                         'start' => '2022-07-30 05:49:00',
//                         'time' => '2022-07-30 05:49:00',
//                         'pos' => 'sunrise',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 21:33:00',
//                         'time' => '2022-07-30 21:33:00',
//                         'pos' => 'sunset',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 13:41:00',
//                         'time' => '2022-07-30 13:41:00',
//                         'pos' => 'transit',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 05:05:00',
//                         'time' => '2022-07-30 05:05:00',
//                         'pos' => 'civil_twilight_begin',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 22:16:00',
//                         'time' => '2022-07-30 22:16:00',
//                         'pos' => 'civil_twilight_end',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 04:05:00',
//                         'time' => '2022-07-30 04:05:00',
//                         'pos' => 'nautical_twilight_begin',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 23:17:00',
//                         'time' => '2022-07-30 23:17:00',
//                         'pos' => 'nautical_twilight_end',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 02:19:00',
//                         'time' => '2022-07-30 02:19:00',
//                         'pos' => 'astronomical_twilight_begin',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-31 01:02:00',
//                         'time' => '2022-07-31 01:02:00',
//                         'pos' => 'astronomical_twilight_end',
//                         'active' => true,
//                     ],
//
//                     [
//                         'start' => '2022-07-30 07:51:00',
//                         'time' => '2022-07-30 07:51:00',
//                         'pos' => 'sunrise',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 23:35:00',
//                         'time' => '2022-07-30 23:35:00',
//                         'pos' => 'sunset',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 15:43:00',
//                         'time' => '2022-07-30 15:43:00',
//                         'pos' => 'transit',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 07:07:00',
//                         'time' => '2022-07-30 07:07:00',
//                         'pos' => 'civil_twilight_begin',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-31 00:18:00',
//                         'time' => '2022-07-31 00:18:00',
//                         'pos' => 'civil_twilight_end',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 06:07:00',
//                         'time' => '2022-07-30 06:07:00',
//                         'pos' => 'nautical_twilight_begin',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-31 01:19:00',
//                         'time' => '2022-07-31 01:19:00',
//                         'pos' => 'nautical_twilight_end',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 04:21:00',
//                         'time' => '2022-07-30 04:21:00',
//                         'pos' => 'astronomical_twilight_begin',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-31 03:07:00',
//                         'time' => '2022-07-31 03:07:00',
//                         'pos' => 'astronomical_twilight_end',
//                         'active' => false,
//                     ],
//
//                     [
//                         'start' => '2022-07-30 07:50:00',
//                         'time' => '2022-07-30 07:50:00',
//                         'pos' => 'sunrise',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 23:34:00',
//                         'time' => '2022-07-30 23:34:00',
//                         'pos' => 'sunset',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 15:42:00',
//                         'time' => '2022-07-30 15:42:00',
//                         'pos' => 'transit',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 07:06:00',
//                         'time' => '2022-07-30 07:06:00',
//                         'pos' => 'civil_twilight_begin',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-31 00:17:00',
//                         'time' => '2022-07-31 00:17:00',
//                         'pos' => 'civil_twilight_end',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 06:06:00',
//                         'time' => '2022-07-30 06:06:00',
//                         'pos' => 'nautical_twilight_begin',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-31 01:18:00',
//                         'time' => '2022-07-31 01:18:00',
//                         'pos' => 'nautical_twilight_end',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-30 04:20:00',
//                         'time' => '2022-07-30 04:20:00',
//                         'pos' => 'astronomical_twilight_begin',
//                         'active' => true,
//                     ],
//                     [
//                         'start' => '2022-07-31 03:05:00',
//                         'time' => '2022-07-31 03:05:00',
//                         'pos' => 'astronomical_twilight_end',
//                         'active' => true,
//                     ],
//
//                     [
//                         'start' => '2022-07-30 05:48:00',
//                         'time' => '2022-07-30 05:48:00',
//                         'pos' => 'sunrise',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 21:32:00',
//                         'time' => '2022-07-30 21:32:00',
//                         'pos' => 'sunset',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 13:40:00',
//                         'time' => '2022-07-30 13:40:00',
//                         'pos' => 'transit',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 05:04:00',
//                         'time' => '2022-07-30 05:04:00',
//                         'pos' => 'civil_twilight_begin',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 22:15:00',
//                         'time' => '2022-07-30 22:15:00',
//                         'pos' => 'civil_twilight_end',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 04:04:00',
//                         'time' => '2022-07-30 04:04:00',
//                         'pos' => 'nautical_twilight_begin',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 23:16:00',
//                         'time' => '2022-07-30 23:16:00',
//                         'pos' => 'nautical_twilight_end',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-30 02:18:00',
//                         'time' => '2022-07-30 02:18:00',
//                         'pos' => 'astronomical_twilight_begin',
//                         'active' => false,
//                     ],
//                     [
//                         'start' => '2022-07-31 01:01:00',
//                         'time' => '2022-07-31 01:01:00',
//                         'pos' => 'astronomical_twilight_end',
//                         'active' => false,
//                     ],
//
//
//                 ] as $param) {
//            $result[] = [
//                'message' => 'The estimates date of sunposition `' . $param['pos'] . ' is `' .
//                    ($param['pos'] ? 'ACTIVE' : 'NOT active') . '` at time `' . $param['time'] . '`.',
//                'expects' => [
//                    'result' => $param['active'],
//                ],
//                'params' => [
//                    'testvalue' => date_create_from_format('Y-m-d H:i:s', $param['start'],
//                        new DateTimeZone('Europe/Berlin')),
//                    'required' => [
//                        'sunPosition' => $param['pos'],
//                        'relMinToSelectedTimerEvent' => '10',
//                        'durationMinutes' => '120',
//                        'durationNatural' => 'defined',
//                        'latitude' => $latitude, // latitude Bremen 	53.0792962
//                        'longitude' => $longitude, // Variation
//                    ],
//                    'optional' => [
//
//                    ],
//                    'general' => $general,
//                ],
//            ];
//        }

        foreach ([
                     'sunrise' => [ 'start' => '2022-07-30 05:39:00', 'order' => 3, 'startNext' => '2022-07-31 05:40:00'],
                     'sunset' => [ 'start' => '2022-07-30 21:23:00', 'order' => 5, 'startNext' => '2022-07-31 21:21:00'],
                     'transit' => [ 'start' => '2022-07-30 13:31:00', 'order' => 4, 'startNext' => '2022-07-31 13:31:00'],
                     'civil_twilight_begin' => [ 'start' => '2022-07-30 04:55:00', 'order' => 2, 'startNext' => '2022-07-31 04:57:00'],
                     'civil_twilight_end' => [ 'start' => '2022-07-30 22:06:00', 'order' => 6, 'startNext' => '2022-07-31 22:04:00'],
                     'nautical_twilight_begin' => [ 'start' => '2022-07-30 03:55:00', 'order' => 1, 'startNext' => '2022-07-31 03:58:00'],
                     'nautical_twilight_end' => [ 'start' => '2022-07-30 23:07:00', 'order' => 7, 'startNext' => '2022-07-31 23:04:00'],
                     'astronomical_twilight_begin' => [ 'start' => '2022-07-30 02:09:00', 'order' => 0, 'startNext' => '2022-07-31 02:18:00'],
                     'astronomical_twilight_end' => [ 'start' => '2022-07-31 00:52:00', 'order' => 8, 'startNext' => '2022-08-01 00:43:00'],
                 ] as $startPos => $startTime
        ) {
            foreach ([
                         [
                             'start' => '2022-07-30 02:00:00',
                             'time' => '2022-07-30 21:33:00',
                             'pos' => 'defined',
                             'active' => false,
                             'order' => 10000,
                         ],

                         [
                             'start' => '2022-07-30 05:39:00',
                             'time' => '2022-07-30 05:39:00',
                             'pos' => 'sunrise',
                             'active' => true,
                             'order' => 3,
                         ],
            ['start' => '2022-07-30 21:23:00', 'time' => '2022-07-30 21:23:00', 'pos' => 'sunset', 'active' => true, 'order' => 5,],
            ['start' => '2022-07-30 13:31:00', 'time' => '2022-07-30 13:31:00', 'pos' => 'transit', 'active' => true, 'order' => 4,],
            ['start' => '2022-07-30 04:55:00', 'time' => '2022-07-30 04:55:00', 'pos' => 'civil_twilight_begin', 'active' => true, 'order' => 2,],
            ['start' => '2022-07-30 22:06:00', 'time' => '2022-07-30 22:06:00', 'pos' => 'civil_twilight_end', 'active' => true, 'order' => 6,],
            ['start' => '2022-07-30 03:55:00', 'time' => '2022-07-30 03:55:00', 'pos' => 'nautical_twilight_begin', 'active' => true, 'order' => 1,],
            ['start' => '2022-07-30 23:07:00', 'time' => '2022-07-30 23:07:00', 'pos' => 'nautical_twilight_end', 'active' => true, 'order' => 7,],
            ['start' => '2022-07-30 02:09:00', 'time' => '2022-07-30 02:09:00', 'pos' => 'astronomical_twilight_begin', 'active' => true, 'order' => 0,],
            ['start' => '2022-07-31 00:52:00', 'time' => '2022-07-31 00:52:00', 'pos' => 'astronomical_twilight_end', 'active' => true, 'order' => 8,],
                     ] as $param
            ) {
                $result[] = [
                    'message' => 'The estimates natural timegap from `' . $startPos . '` to `' . $param['pos'] . '` is `' .
                        ($param['pos'] ? 'ACTIVE' : 'NOT active') . '` at time `' . $param['time'] . '`.',
                    'expects' => [
                        'result' => $param['active'],
                    ],
                    'params' => [
                        'testvalue' => date_create_from_format('Y-m-d H:i:s', $param['start'],
                            new DateTimeZone('Europe/Berlin')),
                        'required' => [
                            'sunPosition' => $startPos,
                            'relMinToSelectedTimerEvent' => '0',  // will be ignored
                            'durationMinutes' => '0', // will be ignored
                            'durationNatural' => $param['pos'],
                            'latitude' => $latitude, // latitude Bremen 	53.0792962
                            'longitude' => $longitude, // Variation
                        ],
                        'optional' => [

                        ],
                        'general' => $general,
                    ],
                ];
                $result[] = [
                    'message' => 'The estimates natural timegap from `' . $startPos . '` to `' . $param['pos'] .
                        '` is `ACTIVE` at time `' . $startTime['start'] . '`.',
                    'expects' => [
                        'result' =>  true, // because one minite minimum-gap
                    ],
                    'params' => [
                        'testvalue' => date_create_from_format('Y-m-d H:i:s',
                            (($startTime['order']< $param['order'])?$startTime['start']:$startTime['startNext']),
                            new DateTimeZone('Europe/Berlin')),
                        'required' => [
                            'sunPosition' => $startPos,
                            'relMinToSelectedTimerEvent' => '0',  // will be ignored, if $param['pos'] !== 'defined'
                            'durationMinutes' => '0', // will be ignored, if $param['pos'] !== 'defined'
                            'durationNatural' => $param['pos'],
                            'latitude' => $latitude, // latitude Bremen 	53.0792962
                            'longitude' => $longitude, // Variation
                        ],
                        'optional' => [

                        ],
                        'general' => $general,
                    ],
                ];
            }
        }
        return $result;
    }

    public function dataProviderGetTimeZoneOfEvent()
    {

        $result = [];
        /* test allowed minimal structure */
//        $result[] = [
//            'message' => 'The timezone of the parameter will be shown. The value of the timezone will not be validated.',
//            [
//                'result' => 'Kauderwelsch/Murz',
//            ],
//            [
//                'params' => [
//                    TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
//                ],
//                'active' => 'Lauder/Furz',
//            ],
//        ];
        $result[] = [
            'message' => 'The timezone is missing in the parameter. The Active-Timezone  will be returned.',
            [
                'result' => 'Lauder/Furz',
            ],
            [
                'params' => [

                ],
                'active' => 'Lauder/Furz',
            ],
        ];
        $result[] = [
            'message' => 'The active timezone will be shown, because the defined-part ofist not part of the allowed Timezonelist. The active Timezone itself will not be validated.',
            [
                'result' => 'Kauderwelsch/Murz',
            ],
            [
                'params' => [
                    TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                   TimerInterface::ARG_USE_ACTIVE_TIMEZONE => '',
                ],
                'active' => 'Lauder/Furz',
            ],
        ];
        $result[] = [
            'message' => 'The timezone of the parameter will be shown, because the active-part of the parameter is PHP-empty (Zero). The value of the timezone will not be validated.',
            [
                'result' => 'Lauder/Furz',
            ],
            [
                'params' => [
                    TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                   TimerInterface::ARG_USE_ACTIVE_TIMEZONE => 0,
                ],
                'active' => 'Lauder/Furz',
            ],
        ];
        foreach (['true', true, 'TRUE', 1, '1'] as $testAllowActive) {
            $result[] = [
                'message' => 'The active timezone will be shown, because the parameter for it is active `' .
                    print_r($testAllowActive, true) . '`. The value of the timezone will not be validated.',
                [
                    'result' => 'Lauder/Furz',
                ],
                [
                    'params' => [
                        TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                       TimerInterface::ARG_USE_ACTIVE_TIMEZONE => $testAllowActive, // Variation
                    ],
                    'active' => 'Lauder/Furz',
                ],
            ];
        }
        $result[] = [
            'message' => 'The active zone will be shown instead of The timezone of the parameter, because the parameter is not a string (=name). The value of the timezone will not be validated.',
            [
                'result' => 'Lauder/Furz',
            ],
            [
                'params' => [
                    TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT => 7200,
                   TimerInterface::ARG_USE_ACTIVE_TIMEZONE => 0,
                ],
                'active' => 'Lauder/Furz',
            ],
        ];
        $result[] = [
            'message' => 'The timezone of the active zone will be show, because the active-part of the parameter is not PHP-empty (true). The value of the timezone will not be validated.',
            [
                'result' => 'Lauder/Furz',
            ],
            [
                'params' => [
                    TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                   TimerInterface::ARG_USE_ACTIVE_TIMEZONE => true,
                ],
                'active' => 'Lauder/Furz',
            ],
        ];
        return $result;
    }

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
            $result = $this->subject->getTimeZoneOfEvent($activeZone, $myParams);

            $this->assertEquals(
                $expects['result'],
                $result,
                $message
            );
        }
    }

    /**
     * @dataProvider dataProviderIsActive
     * @test
     */
    public function isActive($message, $expects, $params)
    {

        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {

            $setting = array_merge($params['required'], $params['general'], $params['optional']);
            $value = clone $params['testvalue'];
            $this->assertEquals(
                $expects['result'],
                $this->subject->isActive($value, $setting),
                'isActive: ' . $message
            );
            $this->assertEquals(
                $params['testvalue'],
                $value,
                'isActive: The object of Date is unchanged.'
            );

        }
    }


    public function dataProviderNextActive()
    {


        $result = [];
//        '2022-06-16 13:51:00' => false,
////                     '2022-06-16 13:52:00' => true, // calc:.:  	Tue Jun 14 2022 13:52:37 Europe/berlin
//                     '2022-06-16 13:53:00' => true,
        $result[] = [
            'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
            'expects' => [
                'result' => [
                    'beginning' => '2022-06-16 13:52:00',
                    'ending' => '2022-06-16 15:53:00',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format('Y-m-d H:i:s', '2022-06-16 13:51:00',
                    new DateTimeZone('Europe/Berlin')),
                'setting' => [
                    'moonPhase' => 'full_moon',
                    'relMinToSelectedTimerEvent' => '2880',  //= 2 days delay to the fullmoon ('2022-06-14 13:52:00')
                    'durationMinutes' => '120',

                    'useTimeZoneOfFrontend' => 0,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        return $result;
    }

    /**
     * @dataProvider dataProviderNextActive
     * @test
     */
    public function nextActive($message, $expects, $params)
    {

        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {

            $setting = $params['setting'];
            $value = $params['value'];
            /** @var TimerStartStopRange $result */
            $result = $this->subject->nextActive($value, $setting);
            $flag = ($result->getBeginning()->format(TimerInterface::TIMER_FORMAT_DATETIME) === $expects['result']['beginning']);
            $flag = $flag && ($result->getEnding()->format(TimerInterface::TIMER_FORMAT_DATETIME) === $expects['result']['ending']);
            $flag = $flag && ($result->hasResultExist() === $expects['result']['exist']);
            $this->assertTrue(
                ($flag),
                'nextActive: ' . $message . "\nExpected: : " . print_r($expects['result'], true)
            );

        }
    }

}
