<?php

namespace Porthd\Timer\CustomTimer;

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
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatePeriodTimerTest extends TestCase
{
    protected const NAME_TIMER = 'txTimerDatePeriod';
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE =TimerConst::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerConst::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerConst::ARG_ULTIMATE_RANGE_END;


    /**
     * @var DatePeriodTimer
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
        $this->subject = new DatePeriodTimer();
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
            $resultPath = $rootPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . substr($filePath, strlen(TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH));
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
        // seltone working configuration
        $result[] = [
            'message' => 'The test is correct, because all needed arguments are used.',
            [
                'result' => true,
            ],
            [
                'required' => [
                    'startTimeSeconds' => '2020-12-28 13:12:59',
                    'durationMinutes' => 120,
                    'periodLength' => 10,
                    'periodUnit' => 'DM',
                ],
                'optional' => [

                ],
                'general' => $general,
                'obsolete' => [

                ],
            ],
        ];
        // Variation of starttime
        foreach ([1609152276 => false, '2020-12-28 13:12:59' => true, '28.12.2020 13:12:59' => false, '' => false] as $dateTimeDef => $myRes) {
            $result[] = [
                'message' => 'The variation of startTimeSeconds `' . $dateTimeDef . '` ' . ($myRes ? ' will be okay.' : 'will fail.'),
                [
                    'result' => $myRes,
                ],
                [
                    'required' => [
                        'startTimeSeconds' => $dateTimeDef, // Variation
                        'durationMinutes' => 120,
                        'periodLength' => 10,
                        'periodUnit' => 'DM',
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
        }
        // Variation of durationMinutes
        //floats counld not be key  of arrays in PHP
        foreach ([1000000000 => true, 120 => true, '100.1' => false, '-0.1' => false, 0 => false, 0.9999999 => false, '112' => true, '112.2' => false, -120 => true] as $variant => $myRes) {
            $result[] = [
                'message' => 'The variation of durationTime `' . $variant . '` ' . ($myRes ? ' will be okay.' : 'will fail.'),
                [
                    'result' => $myRes,
                ],
                [
                    'required' => [
                        'startTimeSeconds' => '2020-12-28 13:12:59',
                        'durationMinutes' => $variant,
                        'periodLength' => 10,
                        'periodUnit' => 'DM',
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
        }
        // Variation of periodLength
        foreach ([-100 => false, -0.1 => false, 0 => false, 0.9999999 => false, '1' => true, '2.2' => true, 120 => true] as $variant => $myRes) {
            $result[] = [
                'message' => 'The variation of periodLength `' . $variant . '` ' . ($myRes ? ' will be okay.' : 'will fail.'),
                [
                    'result' => $myRes,
                ],
                [
                    'required' => [
                        'startTimeSeconds' => '2020-12-28 13:12:59',
                        'durationMinutes' => 120,
                        'periodLength' => $variant,
                        'periodUnit' => 'DM',
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
        }
        // Variation of periodUnit
        foreach (['M' => false, '' => false, 2 => false, 'TS' => false,
                     'tm' => true, 'tM' => true, 'Tm' => true, 'TM' => true, 'TH' => true, 'DD' => true,
                     'DW' => true, 'DM' => true, 'DY' => true,] as $variant => $myRes
        ) {
            $result[] = [
                'message' => 'The variation of periodUnit `' . $variant . '` ' . ($myRes ? ' will be okay.' : 'will fail. ') .
                    'Allowed are only [ `TM`, `TH`, `DD`, `DW`, `DM`, `DY`]. The unit for seconds `TS` is not allowed. ' .
                    'Uppercase and lowercase are equal.',
                [
                    'result' => $myRes,
                ],
                [
                    'required' => [
                        'startTimeSeconds' => '2020-12-28 13:12:59',
                        'durationMinutes' => 120,
                        'periodLength' => 1,
                        'periodUnit' => $variant,
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
        }

        // variation of requiered parmeters
        foreach (['startTimeSeconds', 'durationMinutes', 'periodLength', 'periodUnit',] as $item) {
            $undefined = [
                'message' => 'The test is incorrect, because `' . $item . '` in the needed arguments is missing.',
                [
                    'result' => false,
                ],
                [
                    'required' => [
                        'startTimeSeconds' => '2020-12-28 13:12:59',
                        'durationMinutes' => 120,
                        'periodLength' => 10,
                        'periodUnit' => 'DM',
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
            unset($undefined[1]['required'][$item]);
            $result[] = $undefined;
            foreach (['null' => null, 'zero' => 0, 'false' => false, 'empty' => ''] as $key => $value) {
                $failDefined = [
                    'message' => 'The test is not correct, because `' . $item . '` in the needed arguments is set to `' . $key . '`.',
                    [
                        'result' => false,
                    ],
                    [
                        'required' => [
                            'startTimeSeconds' => '2020-12-28 13:12:59',
                            'durationMinutes' => 120,
                            'periodLength' => 10,
                            'periodUnit' => 'DM',
                        ],
                        'optional' => [

                        ],
                        'general' => $general,
                        'obsolete' => [

                        ],
                    ],
                ];
                $failDefined[1]['required'][$item] = $value;
                $result[] = $failDefined;
            }
        }
        $result[] = [
            'message' => 'The test results won`t fail, because the item `obsolete` is part of the parameters.',
            [
                'result' => true,
            ],
            [
                'required' => [
                    'startTimeSeconds' => '2020-12-28 13:12:59',
                    'durationMinutes' => 120,
                    'periodLength' => 10,
                    'periodUnit' => 'DM',
                ],
                'optional' => [

                ],
                'general' => $general,
                'obsolete' => [
                    'dummy' => 'not defined value',
                ],
            ],
        ];
        // no tests about optional parameters
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
            $paramTest = array_merge($params['required'], $params['optional'], $params['obsolete'], $params['general']);
            $flag = $this->subject->validate($paramTest);
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
    public function dataProviderValidateGeneralByVariationArgumentsInParam()
    {
        $rest = [
            'startTimeSeconds' => '2020-12-28 13:12:59',
            'durationMinutes' => 120,
            'periodLength' => 12,
            'periodUnit' => 'TM',
        ];
        $result = [];
        // variation of obsolete parameter
        $list = ['useTimeZoneOfFrontend' => true,
            'timeZoneOfEvent' => true,
            'ultimateBeginningTimer' => false,
            'ultimateEndingTimer' => false,];
        foreach ($list as $unsetParam => $expects
        ) {
            if (empty($expects)) continue;

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
        foreach ([null, false, new Datetime(), 'hallo', ''] as $value)
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
//        // Variation for useTimeZoneOfFrontend
        foreach (['UTC' => true, '' => false, 'Europe/Berlin' => true, 'Kumpel/Dumpel' => false] as $zoneVal => $expects) {
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
        foreach (['0002-01-01 13:00:00' => true, '0000-01-01 00:00:00' => true, '-1111-01-01 00:00:00' => false, '' => false] as $timeVal => $expects) {
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
        foreach (['0002-01-01 13:00:00' => true, '0000-01-01 00:00:00' => true, '-1111-01-01 00:00:00' => false, '' => false] as $timeVal => $expects) {
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

    public function dataProviderIsActive()
    {
        $result = [];

        // 1. Test with variation of Time and positive durationminutes
        // + 5. Test the Day-Overlay for the active period
        foreach (['PT1M' => false, 'PT1H' => true, 'PT2H' => true, 'PT3H' => true, 'PT3H1S' => false, 'P7DT2H' => true, 'P14DT2H' => true, 'P8WT2H' => true, 'P8WT3H1S' => false,] as $diff => $expects) {


            $check = date_create_from_format('Y-m-d H:i:s', '2020-12-20 11:00:00', new DateTimeZone('Europe/Berlin'));
            $check->add(new DateInterval($diff));
            $result[] = [
                'message' => 'The date with the add `' . $diff . '` will ' . ($expects ? 'be active.' : 'be NOT active.') .
                    'The period will repeat  after one week. There are no ultimate restrictions.',
                'expects' => [
                    'result' => $expects,
                ],
                'params' => [
                    'value' => clone $check,
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 12:00:00',
                        'durationMinutes' => 120,
                        'periodLength' => 1,
                        'periodUnit' => 'DW',
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // 2. Test with variation of Time and negative durationminutes
        foreach (['PT1M' => false, 'PT1H' => true, 'PT2H' => true, 'PT3H' => true, 'PT3H1S' => false, 'P7DT2H' => true, 'P14DT2H' => true, 'P8WT2H' => true, 'P8WT3H1S' => false,] as $diff => $expects) {
//        foreach (['P7DT2H' => true, 'P14DT2H' => true, 'P8WT2H' => true, 'P8WT3H1S' => false,] as $diff => $expects)  {

            $check = date_create_from_format('Y-m-d H:i:s', '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin'));
            $check->add(new DateInterval($diff));
            $result[] = [
                'message' => 'The date with minus-duration-time  `' . $diff . '` will ' . ($expects ? 'be active.' : 'be NOT active.'),
                'expects' => [
                    'result' => $expects,
                ],
                'params' => [
                    'value' => $check,
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 14:00:00',
                        'durationMinutes' => -120,
                        'periodLength' => 1,
                        'periodUnit' => 'DW',
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // 3. The Variation of `timeZoneOfEvent` and `useTimeZoneOfFrontend` is not relevant
        foreach ([true, false] as $useTimeZoneOfFrontend) {
            foreach (['UTC', 'Europe/Berlin', 'Australia/Eucla', 'America/Detroit', 'Pacific/Fiji', 'Indian/Chagos'] as $timezoneName) {
                $result[] = [
                    'message' => 'The date with additional Interval  will be NOT active. It is indepent to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 14:00:00',
                            'durationMinutes' => -120,
                            'periodLength' => 1,
                            'periodUnit' => 'DW',
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The date with additional Interval  will be active. It is indepent to the timezone `' . $timezoneName . '`.',
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 12:00:00',
                            'durationMinutes' => 120,
                            'periodLength' => 1,
                            'periodUnit' => 'DW',
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend,
                            'timeZoneOfEvent' => $timezoneName, // dynamic see below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];

            }
        }

        // 4. The variation of Variate third Parameter `ultimateBeginningTimer` and `ultimateEndingTimer`
        foreach (['0001-01-01 00:00:00', '2020-12-27 11:00:00', '2020-12-27 13:00:00', '2020-12-27 18:00:00', '9999-12-31 23:59:59',] as $timeString) {
            $check = date_create_from_format('Y-m-d H:i:s', '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin'));
            $result[] = [
                'message' => 'The date with additional Interval  will be NOT active. It is independ to the ultimate-parameter. ',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'value' => clone $check,
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 14:00:00',
                        'durationMinutes' => -120,
                        'periodLength' => 1,
                        'periodUnit' => 'DW',
                        // general
                        'useTimeZoneOfFrontend' => false, // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '2020-12-27 13:00:00',
                        'ultimateEndingTimer' => $timeString,
                    ],
                ],
            ];
            $check->add(new DateInterval('PT2H'));
            $result[] = [
                'message' => 'The date with additional Interval  will be active. It is independ to the ultimate-parameter. ',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'value' => clone $check,
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 14:00:00',
                        'durationMinutes' => -120,
                        'periodLength' => 1,
                        'periodUnit' => 'DW',
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin', // dynamic see below
                        'ultimateBeginningTimer' => $timeString,
                        'ultimateEndingTimer' => '2020-12-27 13:00:00',
                    ],
                ],
            ];

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
//                    TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
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
                    TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                   TimerConst::ARG_USE_ACTIVE_TIMEZONE => '',
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
                    TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                   TimerConst::ARG_USE_ACTIVE_TIMEZONE => 0,
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
                        TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                       TimerConst::ARG_USE_ACTIVE_TIMEZONE => $testAllowActive, // Variation
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
                    TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT => 7200,
                   TimerConst::ARG_USE_ACTIVE_TIMEZONE => 0,
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
                    TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                   TimerConst::ARG_USE_ACTIVE_TIMEZONE => true,
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

            $setting = array_merge($params['setting']);
            $value = clone $params['value'];
            $this->assertEquals(
                $expects['result'],
                $this->subject->isActive($value, $setting),
                'isActive: ' . $message
            );
            $this->assertEquals(
                $params['value'],
                $value,
                'isActive: The object of Date is unchanged.'
            );

        }
    }


    public function dataProviderNextActive()
    {


        $result = [];
        // 1. rondomly Test
        $result[] = [
            'message' => 'The nextRange in this example is correctly detected. ',
            'expects' => [
                'result' => [
                    'beginning' => '2020-12-27 12:00:00',
                    'ending' => '2020-12-27 14:00:00',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                'setting' => [
                    'startTimeSeconds' => '2020-12-27 14:00:00',
                    'durationMinutes' => -120,
                    'periodLength' => 1,
                    'periodUnit' => 'DW',
                    // general
                    'useTimeZoneOfFrontend' => 'true', // Variation
                    'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The nextRange in this example is correctly detected. ',
            'expects' => [
                'result' => [
                    'beginning' => '2020-12-31 23:00:00',
                    'ending' => '2021-01-01 01:00:00',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-25 11:00:00', new DateTimeZone('Europe/Berlin')),
                'setting' => [
                    'startTimeSeconds' => '2020-12-31 23:00:00',
                    'durationMinutes' => 120,
                    'periodLength' => 1,
                    'periodUnit' => 'DW',
                    // general
                    'useTimeZoneOfFrontend' => 'true', // Variation
                    'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        // Variation for different Date and Intervalls in futre and past
        foreach (['2020-12-27 11:00:00' => '2020-12-27', '2020-12-27 12:00:00' => '2021-01-03',
                     '2020-02-23 12:00:00' => '2020-03-01', '2021-02-21 12:00:00' => '2021-02-28',] as $testDate => $nextRange) {
            $result[] = [
                'message' => 'The nextRange is correctly detected for the Startdate `'.$testDate.'`. ',
                'expects' => [
                    'result' => [
                        'beginning' => $nextRange . ' 12:00:00',
                        'ending' => $nextRange . ' 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', $testDate, new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 14:00:00',
                        'durationMinutes' => -120,
                        'periodLength' => 1,
                        'periodUnit' => 'DW',
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        // the period must be longer than the active period
        // Variation of Unit
        // Remarkt for TM:the next valid stop-range relativly to the testtime 13:00 will beginn at 15:10 - not 14:10. There is no warning for overlapping.  The periodlength should be greater than the durationminutes.
        foreach (['TH' => '2020-12-28 00:00:00', 'DD' => '2021-01-06 14:00:00', 'DW' => '2021-03-07 14:00:00',
                     'DM' => '2021-10-27 14:00:00','DY' => '2030-12-27 14:00:00',] as $testUnit => $startRange
        ) {
            $ending = date_create_from_format('Y-m-d H:i:s', $startRange, new DateTimeZone('Europe/Berlin'));
            $beginning = clone $ending;
            $beginning->sub(new DateInterval('PT120M'));
            $result[] = [
                'message' => 'The nextRange is correctly detected for the testUnit `' . $testUnit . '`. (only unit. length constant)',
                'expects' => [
                    'result' => [
                        'beginning' => $beginning->format('Y-m-d H:i:s'),
                        'ending' => $ending->format('Y-m-d H:i:s'),
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 14:00:00',
                        'durationMinutes' => -120,
                        'periodLength' => 10,
                        'periodUnit' => $testUnit,
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // the period must be longer than the active period
        // Variation of Unit
        // Remarkt for TM:the next valid stop-range relativly to the testtime 13:00 will beginn at 15:10 - not 14:10. There is no warning for overlapping.  The periodlength should be greater than the durationminutes.
        foreach (['TM' => '2020-12-27 13:15:00', 'tm' => '2020-12-27 13:15:00', 'tM' => '2020-12-27 13:15:00',
                     ] as $testUnit => $startRange) {
            $ending = date_create_from_format('Y-m-d H:i:s', $startRange, new DateTimeZone('Europe/Berlin'));
            $beginning = clone $ending;
            $beginning->sub(new DateInterval('PT10M'));
            $result[] = [
                'message' => 'The nextRange is correctly detected for the testUnit `' . $testUnit . '`. (only unit. length constant)',
                'expects' => [
                    'result' => [
                        'beginning' => $beginning->format('Y-m-d H:i:s'),
                        'ending' => $ending->format('Y-m-d H:i:s'),
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 12:55:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 13:00:00',
                        'durationMinutes' => -10,
                        'periodLength' => 15,
                        'periodUnit' => $testUnit,
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        // the period must be longer than the active period
        // Variation Remarkt for TM:the next valid stop-range relativly to the testtime 13:00 will beginn at 15:10 - not 14:10. There is no warning for overlapping.  The periodlength should be greater than the durationminutes.
        foreach ([3, 5, 12, 13] as $length) {
            foreach ([ 'DD' => 'P1D', 'DW' => 'P1W', 'DM' => 'P1M', 'DY' => 'P1Y',] as $testUnit => $step) {
                $beginning = date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin'));
                for ($i = 0; $i < $length; $i++) {
                    $beginning->add(new DateInterval($step));
                }
                $ending = clone $beginning;
                $ending->add(new DateInterval('PT120M'));
                $result[] = [
                    'message' => 'The nextRange is correctly detected for the testUnit `' . $testUnit . '`. (Period-Length-Test)',
                    'expects' => [
                        'result' => [
                            'beginning' => $beginning->format('Y-m-d H:i:s'),
                            'ending' => $ending->format('Y-m-d H:i:s'),
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 13:00:00',
                            'durationMinutes' => 120,
                            'periodLength' => $length,
                            'periodUnit' => $testUnit,
                            // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The nextRange is correctly detected for the testUnit `' . $testUnit . '`. (Period-Length-Test)',
                    'expects' => [
                        'result' => [
                            'beginning' => $beginning->format('Y-m-d H:i:s'),
                            'ending' => $ending->format('Y-m-d H:i:s'),
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 15:00:00',
                            'durationMinutes' => -120,
                            'periodLength' => $length,
                            'periodUnit' => $testUnit,
                            // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
            }
        }
        // the period must be longer than the active period
        // Variation Remarkt for TM:the next valid stop-range relativly to the testtime 13:00 will beginn at 15:10 - not 14:10. There is no warning for overlapping.  The periodlength should be greater than the durationminutes.
        foreach ([150,1440] as $length) {
            foreach (['TM' => 'PT1M',] as $testUnit => $step) {
                $beginning = date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin'));
                for ($i = 0; $i < $length; $i++) {
                    $beginning->add(new DateInterval($step));
                }
                $ending = clone $beginning;
                $ending->add(new DateInterval('PT120M'));
                $result[] = [
                    'message' => 'The nextRange is correctly detected for the testUnit `' . $testUnit . '`. (Period-Length-Test)',
                    'expects' => [
                        'result' => [
                            'beginning' => $beginning->format('Y-m-d H:i:s'),
                            'ending' => $ending->format('Y-m-d H:i:s'),
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 13:00:00',
                            'durationMinutes' => 120,
                            'periodLength' => $length,
                            'periodUnit' => $testUnit,
                            // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The nextRange is correctly detected for the testUnit `' . $testUnit . '`. (Period-Length-Test)',
                    'expects' => [
                        'result' => [
                            'beginning' => $beginning->format('Y-m-d H:i:s'),
                            'ending' => $ending->format('Y-m-d H:i:s'),
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 15:00:00',
                            'durationMinutes' => -120,
                            'periodLength' => $length,
                            'periodUnit' => $testUnit,
                            // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
            }
        }
        // 22100 min = 15 Tage 8 h 20 min
        foreach ([120 => '2020-12-27 15:00:00', 22100 => '2021-01-11 21:20:00', 144000 => '2021-04-06 13:00:00'] as $duration => $endingString) {
            // $step = date_create_from_format('Y-m-d H:i:s', '2019-12-27 13:00:00', new DateTimeZone('Europe/Berlin'));
            // $step->add(new DateInterval('PT'.$duration.'M'));
            $result[] = [

                'message' => 'The nextRange is correctly detected for the duration-minutes `' . $duration . '`. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2020-12-27 13:00:00',
                        'ending' => $endingString,
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2019-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 13:00:00',
                        'durationMinutes' => $duration,
                        'periodLength' => 1,
                        'periodUnit' => 'DY',
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        // Variation odf the starttime
        foreach (['1735-12-27 22:00:00' => '2019-12-27 22:00:00', '2135-12-27 22:00:00' => '2019-12-27 22:00:00'] as $startTime => $nextBegin) {
            // $step = date_create_from_format('Y-m-d H:i:s', '2019-12-27 13:00:00', new DateTimeZone('Europe/Berlin'));
            // $step->add(new DateInterval('PT'.$duration.'M'));
            $nextEnd = date_create_from_format('Y-m-d H:i:s', $nextBegin, new DateTimeZone('Europe/Berlin'));

            $reversEnd = clone $nextEnd;
            $nextEnd->add(new DateInterval('PT240M'));
            $reversEnd->sub(new DateInterval('PT240M'));
            $result[] = [

                'message' => 'The nextRange is correctly detected for the Startime `' . $startTime . '`. ',
                'expects' => [
                    'result' => [
                        'beginning' => $nextBegin,
                        'ending' => $nextEnd->format('Y-m-d H:i:s'),
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2019-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => $startTime,
                        'durationMinutes' => 240,
                        'periodLength' => 12,
                        'periodUnit' => 'DM',  // 12 Monate = 1 jahr
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [

                'message' => 'The nextRange is correctly detected for the Startime `' . $startTime . '` by reversed definition. ',
                'expects' => [
                    'result' => [
                        'beginning' => $reversEnd->format('Y-m-d H:i:s'),
                        'ending' => $nextBegin,
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2019-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => $startTime,
                        'durationMinutes' => -240,
                        'periodLength' => 1,
                        'periodUnit' => 'DY',
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }


        // 3. The Variation of `timeZoneOfEvent` and `useTimeZoneOfFrontend` is not relevant
        foreach ([true, false] as $useTimeZoneOfFrontend) {
            foreach (['UTC', 'Europe/Berlin', 'Australia/Eucla', 'America/Detroit', 'Pacific/Fiji', 'Indian/Chagos'] as $timezoneName) {
                $result[] = [
                    'message' => 'The nextRange is correctly detected. It is indepent to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-27 12:00:00',
                            'ending' => '2020-12-27 14:00:00',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 14:00:00',
                            'durationMinutes' => -120,
                            'periodLength' => 1,
                            'periodUnit' => 'DW',
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The nextRange is correctly detected. It is indepent to the timezone `' . $timezoneName . '`.',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-27 12:00:00',
                            'ending' => '2020-12-27 14:00:00',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 12:00:00',
                            'durationMinutes' => 120,
                            'periodLength' => 1,
                            'periodUnit' => 'DW',
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend,
                            'timeZoneOfEvent' => $timezoneName, // dynamic
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];

            }
        }
        // 4. The variation of Variate third Parameter `ultimateBeginningTimer` and `ultimateEndingTimer`
        foreach (['0001-01-01 00:00:00', '2020-12-27 11:00:00', '2020-12-27 13:00:00', '2020-12-27 18:00:00', '9999-12-31 23:59:59',] as $timeString) {
            $result[] = [
                'message' => 'The nextRange is correctly detected. It is independ to the ultimate-parameter. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2020-12-27 12:00:00',
                        'ending' => '2020-12-27 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 14:00:00',
                        'durationMinutes' => -120,
                        'periodLength' => 1,
                        'periodUnit' => 'DW',
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '2020-12-27 13:00:00',
                        'ultimateEndingTimer' => $timeString, // Variation
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The nextRange is correctly detected.  It is independ to the ultimate-parameter. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2020-12-27 12:00:00',
                        'ending' => '2020-12-27 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 12:00:00',
                        'durationMinutes' => 120,
                        'periodLength' => 1,
                        'periodUnit' => 'DW',
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => $timeString, // Variation
                        'ultimateEndingTimer' => '2020-12-27 13:00:00',
                    ],
                ],
            ];
        }
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

            $setting = array_merge($params['setting']);
            $value = $params['value'];
            /** @var TimerStartStopRange $result */
            $result = $this->subject->nextActive($value, $setting);
            $flag = ($result->getBeginning()->format(TimerConst::TIMER_FORMAT_DATETIME) === $expects['result']['beginning']);
            $flag = $flag && ($result->getEnding()->format(TimerConst::TIMER_FORMAT_DATETIME) === $expects['result']['ending']);
            $flag = $flag && ($result->hasResultExist() === $expects['result']['exist']);
            $this->assertTrue(
                ($flag),
                'nextActive: ' . $message . "\nExpected: : " . print_r($expects['result'], true)
            );
        }
    }

    public function dataProviderPrevActive()
    {
        $result = [];
        // 1. rondomly Test
        $result[] = [
            'message' => 'The prevRange in this example is correctly detected. ',
            'expects' => [
                'result' => [
                    'beginning' => '2020-12-27 12:00:00',
                    'ending' => '2020-12-27 14:00:00',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin')),
                'setting' => [
                    'startTimeSeconds' => '2020-12-27 14:00:00',
                    'durationMinutes' => -120,
                    'periodLength' => 1,
                    'periodUnit' => 'DW',
                    // general
                    'useTimeZoneOfFrontend' => 'true', // Variation
                    'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The prevRange in this example is correctly detected. ',
            'expects' => [
                'result' => [
                    'beginning' => '2020-12-31 23:00:00',
                    'ending' => '2021-01-01 01:00:00',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format('Y-m-d H:i:s', '2021-01-05 02:00:00', new DateTimeZone('Europe/Berlin')),
                'setting' => [
                    'startTimeSeconds' => '2020-12-31 23:00:00',
                    'durationMinutes' => 120,
                    'periodLength' => 1,
                    'periodUnit' => 'DW',
                    // general
                    'useTimeZoneOfFrontend' => 'true', // Variation
                    'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];

        // Variation for different Date and Intervalls in futre and past
        foreach (['2020-12-27 15:00:00' => '2020-12-27', '2020-12-27 12:00:00' => '2020-12-20',
                     '2020-02-23 12:00:00' => '2020-02-16', '2021-02-21 12:00:00' => '2021-02-14',] as $testDate => $prevRange) {
            $result[] = [
                'message' => 'The prevRange is correctly detected for the Startdate `' . $testDate . '`. ',
                'expects' => [
                    'result' => [
                        'beginning' => $prevRange . ' 12:00:00',
                        'ending' => $prevRange . ' 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', $testDate, new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 14:00:00',
                        'durationMinutes' => -120,
                        'periodLength' => 1,
                        'periodUnit' => 'DW',
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // the period must be longer than the active period
        //         Variation of Unit
        //         Remarkt for TM:the next valid stop-range relativly to the testtime 13:00 will beginn at 15:10 - not 14:10. There is no warning for overlapping.  The periodlength should be greater than the durationminutes.
        foreach ([   'TH' => '2020-12-27 04:00:00', 'DD' => '2020-12-17 14:00:00', 'DW' => '2020-10-18 14:00:00',
                     'DM' => '2020-02-27 14:00:00', 'DY' => '2010-12-27 14:00:00',] as $testUnit => $startRange
        ) {
            $ending = date_create_from_format('Y-m-d H:i:s', $startRange, new DateTimeZone('Europe/Berlin'));
            $beginning = clone $ending;
            $beginning->sub(new DateInterval('PT120M'));
            $result[] = [
                'message' => 'The prevRange is correctly detected for the testUnit `' . $testUnit . '`. (variate Unit)',
                'expects' => [
                    'result' => [
                        'beginning' => $beginning->format('Y-m-d H:i:s'),
                        'ending' => $ending->format('Y-m-d H:i:s'),
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 14:00:00',
                        'durationMinutes' => -120,
                        'periodLength' => 10,
                        'periodUnit' => $testUnit,
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // the period must be longer than the active period
        // resolve upper and lower-case for units
        foreach (['TM' => '2020-12-27 12:45:00', 'tm' => '2020-12-27 12:45:00',
                     'tM' => '2020-12-27 12:45:00',] as $testUnit => $startRange
        ) {
            $ending = date_create_from_format('Y-m-d H:i:s', $startRange, new DateTimeZone('Europe/Berlin'));
            $beginning = clone $ending;
            $beginning->sub(new DateInterval('PT10M'));
            $result[] = [
                'message' => 'The prevRange is correctly detected for the testUnit `' . $testUnit . '`. (variate Unit ii)',
                'expects' => [
                    'result' => [
                        'beginning' => $beginning->format('Y-m-d H:i:s'),
                        'ending' => $ending->format('Y-m-d H:i:s'),
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 13:00:00',
                        'durationMinutes' => -10,
                        'periodLength' => 15,
                        'periodUnit' => $testUnit,
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }


        // Variation odf the starttime
        foreach (['1735-12-27 22:00:00' => '2019-12-27 22:00:00', '2135-12-27 22:00:00' => '2019-12-27 22:00:00'] as $startTime => $nextBegin) {
            // $step = date_create_from_format('Y-m-d H:i:s', '2019-12-27 13:00:00', new DateTimeZone('Europe/Berlin'));
            // $step->add(new DateInterval('PT'.$duration.'M'));
            $nextEnd = date_create_from_format('Y-m-d H:i:s', $nextBegin, new DateTimeZone('Europe/Berlin'));

            $reversEnd = clone $nextEnd;
            $nextEnd->add(new DateInterval('PT240M'));
            $reversEnd->sub(new DateInterval('PT240M'));
            $result[] = [

                'message' => 'The prevRange is correctly detected for the Startime `' . $startTime . '`. ',
                'expects' => [
                    'result' => [
                        'beginning' => $nextBegin,
                        'ending' => $nextEnd->format('Y-m-d H:i:s'),
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2019-12-28 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => $startTime,
                        'durationMinutes' => 240,
                        'periodLength' => 12,
                        'periodUnit' => 'DM',  // 12 Monate = 1 jahr
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [

                'message' => 'The prevRange is correctly detected for the Startime `' . $startTime . '` by reversed definition. ',
                'expects' => [
                    'result' => [
                        'beginning' => $reversEnd->format('Y-m-d H:i:s'),
                        'ending' => $nextBegin,
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2019-12-28 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => $startTime,
                        'durationMinutes' => -240,
                        'periodLength' => 1,
                        'periodUnit' => 'DY',
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        // 3. The Variation of `timeZoneOfEvent` and `useTimeZoneOfFrontend` is not relevant
        foreach ([true, false] as $useTimeZoneOfFrontend) {
            foreach (['UTC', 'Europe/Berlin', 'Australia/Eucla', 'America/Detroit', 'Pacific/Fiji', 'Indian/Chagos'] as $timezoneName) {
                $result[] = [
                    'message' => 'The nextRange is correctly detected. It is indepent to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-27 12:00:00',
                            'ending' => '2020-12-27 14:00:00',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 14:00:00',
                            'durationMinutes' => -120,
                            'periodLength' => 1,
                            'periodUnit' => 'DW',
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The nextRange is correctly detected. It is indepent to the timezone `' . $timezoneName . '`.',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-27 12:00:00',
                            'ending' => '2020-12-27 14:00:00',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 12:00:00',
                            'durationMinutes' => 120,
                            'periodLength' => 1,
                            'periodUnit' => 'DW',
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend,
                            'timeZoneOfEvent' => $timezoneName, // dynamic
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];

            }
        }
        // 4. The variation of Variate third Parameter `ultimateBeginningTimer` and `ultimateEndingTimer`
        foreach (['0001-01-01 00:00:00', '2020-12-27 11:00:00', '2020-12-27 13:00:00', '2020-12-27 18:00:00', '9999-12-31 23:59:59',] as $timeString) {
            $result[] = [
                'message' => 'The nextRange is correctly detected. It is independ to the ultimate-parameter. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2020-12-27 12:00:00',
                        'ending' => '2020-12-27 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 14:00:00',
                        'durationMinutes' => -120,
                        'periodLength' => 1,
                        'periodUnit' => 'DW',
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '2020-12-27 13:00:00',
                        'ultimateEndingTimer' => $timeString, // Variation
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The nextRange is correctly detected.  It is independ to the ultimate-parameter. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2020-12-27 12:00:00',
                        'ending' => '2020-12-27 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 12:00:00',
                        'durationMinutes' => 120,
                        'periodLength' => 1,
                        'periodUnit' => 'DW',
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => $timeString, // Variation
                        'ultimateEndingTimer' => '2020-12-27 13:00:00',
                    ],
                ],
            ];

        }

        // 22100 min = 15 Tage 8 h 20 min
        foreach ([120 => '2018-12-27 15:00:00', 22100 => '2019-01-11 21:20:00', 144000 => '2019-04-06 13:00:00'] as $duration => $endingString) {
            // $step = date_create_from_format('Y-m-d H:i:s', '2019-12-27 13:00:00', new DateTimeZone('Europe/Berlin'));
            // $step->add(new DateInterval('PT'.$duration.'M'));
            $result[] = [

                'message' => 'The prevRange is correctly detected for the duration-minutes `' . $duration . '`. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2018-12-27 13:00:00',
                        'ending' => $endingString,
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format('Y-m-d H:i:s', '2019-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => '2020-12-27 13:00:00',
                        'durationMinutes' => $duration,
                        'periodLength' => 1,
                        'periodUnit' => 'DY',
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // the period must be longer than the active period
        // Variation Remarkt for TM:the next valid stop-range relativly to the testtime 13:00 will beginn at 15:10 - not 14:10. There is no warning for overlapping.  The periodlength should be greater than the durationminutes.
        // There is no check against overlapping
        foreach ([3, 5, 12, 13,150] as $length) {
            foreach (['TH' => 'PT1H', 'DD' => 'P1D', 'DW' => 'P1W', 'DM' => 'P1M', 'DY' => 'P1Y',] as $testUnit => $step) {
                $beginning = date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin'));
                for ($i = 0; $i < $length; $i++) {
                    $beginning->sub(new DateInterval($step));
                }
                $ending = clone $beginning;
                $ending->add(new DateInterval('PT120M'));
                $result[] = [
                    'message' => 'The prevRange is correctly detected for the testUnit `' . $testUnit .
                        '`. (Variation of length `'.$length.'` and unit `'.$testUnit.'`) ',
                    'expects' => [
                        'result' => [
                            'beginning' => $beginning->format('Y-m-d H:i:s'),
                            'ending' => $ending->format('Y-m-d H:i:s'),
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 13:00:00',
                            'durationMinutes' => 120,
                            'periodLength' => $length,
                            'periodUnit' => $testUnit,
                            // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The prevRange is correctly detected for the testUnit `' . $testUnit .
                        '`. (Variation of length `'.$length.'` and unit `'.$testUnit.'`) ',
                    'expects' => [
                        'result' => [
                            'beginning' => $beginning->format('Y-m-d H:i:s'),
                            'ending' => $ending->format('Y-m-d H:i:s'),
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 15:00:00',
                            'durationMinutes' => -120,
                            'periodLength' => $length,
                            'periodUnit' => $testUnit,
                            // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
            }
        }

        // the period must be longer than the active period
        // Variation Remarkt for TM:the next valid stop-range relativly to the testtime 13:00 will beginn at 15:10 - not 14:10. There is no warning for overlapping.  The periodlength should be greater than the durationminutes.
        // There is no check against overlapping
        foreach ([150, 1440] as $length) {
            foreach (['TM' => 'PT1M', ] as $testUnit => $step) {
                $beginning = date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin'));
                for ($i = 0; $i < $length; $i++) {
                    $beginning->sub(new DateInterval($step));
                }
                $ending = clone $beginning;
                $ending->add(new DateInterval('PT120M'));
                $result[] = [
                    'message' => 'The prevRange is correctly detected for the testUnit `' . $testUnit .
                        '`. (Variation of length `'.$length.'` and unit `'.$testUnit.'`) ',
                    'expects' => [
                        'result' => [
                            'beginning' => $beginning->format('Y-m-d H:i:s'),
                            'ending' => $ending->format('Y-m-d H:i:s'),
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 13:00:00',
                            'durationMinutes' => 120,
                            'periodLength' => $length,
                            'periodUnit' => $testUnit,
                            // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The prevRange is correctly detected for the testUnit `' . $testUnit .
                        '`. (Variation of length `'.$length.'` and unit `'.$testUnit.'`) ',
                    'expects' => [
                        'result' => [
                            'beginning' => $beginning->format('Y-m-d H:i:s'),
                            'ending' => $ending->format('Y-m-d H:i:s'),
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format('Y-m-d H:i:s', '2020-12-27 13:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => '2020-12-27 15:00:00',
                            'durationMinutes' => -120,
                            'periodLength' => $length,
                            'periodUnit' => $testUnit,
                            // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
            }
        }

        return $result;
    }

    /**
     * @dataProvider dataProviderPrevActive
     * @test
     */
    public function prevActive($message, $expects, $params)
    {

        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {

            $setting = array_merge($params['setting']);
            $value = $params['value'];
            /** @var TimerStartStopRange $result */
            $result = $this->subject->prevActive($value, $setting);
            $flag = ($result->getBeginning()->format(TimerConst::TIMER_FORMAT_DATETIME) === $expects['result']['beginning']);
            $flag = $flag && ($result->getEnding()->format(TimerConst::TIMER_FORMAT_DATETIME) === $expects['result']['ending']);
            $flag = $flag && ($result->hasResultExist() === $expects['result']['exist']);
            $this->assertTrue(
                ($flag),
                'prevActive: ' . $message . "\nExpected: : " . print_r($expects['result'], true)
            );

        }
    }

}
