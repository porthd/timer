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
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Interfaces\TimerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class WeekdayInMonthTimerTest extends TestCase
{
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE =TimerInterface::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerInterface::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerInterface::ARG_ULTIMATE_RANGE_END;
    protected const NAME_TIMER = 'txTimerWeekdayInMonth';
    protected const SOME_NOT_EMPTY_VALUE = 'some value';
    protected const ALLOWED_TIME_ZONE = 'UTC';


    /**
     * @var WeekdayInMonthTimer
     */
    protected $subject = null;

    protected function simulatePartOfGlobalsTypo3Array()
    {
        $GLOBALS = [];
        $GLOBALS['TYPO3_CONF_VARS'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['timer'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['timer']['changeListOfTimezones'] = [];
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
        $this->subject = new WeekdayInMonthTimer();
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
            'The second term must the name of the timer.');
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
        if (strpos($filePath, TimerConst::MARK_OF_FILE_EXT_FOLDER_IN_FILEPATH) === 0) {
            $resultPath = $rootPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR .
                substr($filePath,
                    strlen(TimerConst::MARK_OF_FILE_EXT_FOLDER_IN_FILEPATH));
        } else if (strpos($filePath, TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH) === 0) {
            $resultPath = $rootPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR .
                substr($filePath,
                    strlen(TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH));
            $this->assertTrue((false),'The File-path should contain `'.TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH.'`, so that the TCA-attribute-action `onChange` will work correctly. ');
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
        $testDate = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-31 12:00:00', new DateTimeZone('Europe/Berlin'));
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
            'nthWeekdayInMonth' => '3',
            'activeWeekday' => '31',
            'startCountAtEnd' => '0',
            'startTimeSeconds' => '43200',
            'durationMinutes' => '120',
            'activeMonth' => '2047',
        ];
        $result = [];
        // variation of obsolete parameter
        $list = [
            'useTimeZoneOfFrontend' => false,
            'timeZoneOfEvent' => false,
            'ultimateBeginningTimer' => false,
            'ultimateEndingTimer' => false,
        ];
        foreach ($list as $unsetParam => $expects
        ) {

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
        foreach ([
                     [null, false], [false,true],['false',true], [new Datetime(), false],
                     ['hallo',false],
                     ['0',true],[0.0,true],["0.0",false],
                     ['true',true],['1',true],[1,true],
                     [1.0,true],['1.0',false],] as $value) {
            $result[] = [
                'message' => 'The validation is okay, because the parameter `useTimeZoneOfFrontend` is required and will tested for type.',
                [
                    'result' => $value[1],
                ],
                [
                    'rest' => $rest,
                    'general' => [
                        'useTimeZoneOfFrontend' => $value[0],
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        // Variation for useTimeZoneOfFrontend
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
                        'useTimeZoneOfFrontend' => 1,
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
                        'useTimeZoneOfFrontend' => 1,
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
                        'useTimeZoneOfFrontend' => 1,
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
                    'nthWeekdayInMonth' => '3',
                    'activeWeekday' => '31',
                    'startCountAtEnd' => '0',
                    'startTimeSeconds' => '43200',
                    'durationMinutes' => '120',
                    'activeMonth' => '2047',
                ],
                'optional' => [

                ],
                'general' => $general,
            ],
        ];
        // unset a required parameter to provoke an failing
        foreach ([
                     'nthWeekdayInMonth',
                     'activeWeekday',
                     'startTimeSeconds',
                     'durationMinutes',
                 ] as $myUnset
        ) {
            $item = [
                'message' => 'The test fails, because the parameter `' . $myUnset . '` is missing.(being unsetted)',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'nthWeekdayInMonth' => '3',
                        'activeWeekday' => '31',
                        'startCountAtEnd' => '0',
                        'startTimeSeconds' => '43200',
                        'durationMinutes' => '120',
                        'activeMonth' => '2047',
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];
            unset($item['params']['required'][$myUnset]);
            $result[] = $item;
        }
        // unset a required parameter to provoke an failing
        foreach (['startCountAtEnd', 'activeMonth',] as $myUnset
        ) {
            $item = [
                'message' => 'The test fails, because the parameter `' . $myUnset . '` is missing.(being unsetted)',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'required' => [
                        'nthWeekdayInMonth' => '3',
                        'activeWeekday' => '31',
                        'startCountAtEnd' => '0',
                        'startTimeSeconds' => '43200',
                        'durationMinutes' => '120',
                        'activeMonth' => '2047',
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

        // variation of durationMinutes
        foreach ([
                     1440 => false,
                     -1440 => false,
                     -1439 => true,
                     1439 => true,
                     '-1439' => true,
                     '1439' => true,
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
                        'nthWeekdayInMonth' => '3',
                        'activeWeekday' => '31',
                        'startCountAtEnd' => '0',
                        'startTimeSeconds' => '43200',
                        'durationMinutes' => $myMin,
                        'activeMonth' => '2047',
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


    public function dataProviderGetTimeZoneOfEvent()
    {

        $result = [];
        /* test allowed minimal structure */
        $result[] = [
            'message' => 'The timezone of the parameter will be shown. The value of the timezone will not be validated.',
            [
                'result' => 'Kauderwelsch/Murz',
            ],
            [
                'params' => [
                    TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                ],
                'active' => 'Lauder/Furz',
            ],
        ];
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
            'message' => 'The timezone of the parameter will be shown, because the active-part of the parameter is 0. The value of the timezone will not be validated.',
            [
                'result' => 'Kauderwelsch/Murz',
            ],
            [
                'params' => [
                    TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                    TimerInterface::ARG_USE_ACTIVE_TIMEZONE => 0,
                ],
                'active' => 'Lauder/Furz',
            ],
        ];
        $result[] = [
            'message' => 'The timezone of the Active will be shown, because the active-part of the parameter is 1. The value of the timezone will not be validated.',
            [
                'result' => 'Lauder/Furz',
            ],
            [
                'params' => [
                    TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT => 'Kauderwelsch/Murz',
                    TimerInterface::ARG_USE_ACTIVE_TIMEZONE => 1,
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

    public function dataProviderIsActive()
    {
        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];
        $result = [];
        /**
         * examples for active Regions
         *
         */
        // simple-test
        foreach ([
                     '2022-01-07 14:10:00' => true, // 1. fr
                     '2022-01-14 16:01:00' => false, // 2. fr
                     '2022-01-14 16:00:00' => true, // 2. fr
                     '2022-01-14 14:00:00' => true, // 2. fr
                     '2022-01-14 13:59:00' => false, // 2. fr
                     '2022-01-21 14:10:00' => false, // 3. fr not allowed by selected day in month
                     '2022-02-04 14:10:00' => true, // 1. fr
                     '2022-02-11 16:01:00' => false, // 2. fr
                     '2022-02-11 16:00:00' => true, // 2. fr
                     '2022-02-11 14:00:00' => true, // 2. fr
                     '2022-02-11 13:59:00' => false, // 2. fr
                     '2022-02-18 14:10:00' => false, // 3. fr not allowed by selected day in month
                     '2022-03-04 14:10:00' => true, // 1. fr
                     '2022-03-11 16:01:00' => false, // 2. fr
                     '2022-03-11 16:00:00' => true, // 2. fr
                     '2022-03-11 14:00:00' => true, // 2. fr
                     '2022-03-11 13:59:00' => false, // 2. fr
                     '2022-03-18 14:10:00' => false, // 3. fr not allowed by selected day in month
                     '2022-04-01 14:10:00' => false, // 1. fr - not part of allowed month
                     '2022-04-08 16:01:00' => false, // 2. fr - not part of allowed month
                     '2022-04-08 16:00:00' => false, // 2. fr - not part of allowed month
                     '2022-04-08 14:00:00' => false, // 2. fr - not part of allowed month
                     '2022-04-08 13:59:00' => false, // 2. fr - not part of allowed month
                     '2022-04-15 14:10:00' => false, // 3. fr - not part of allowed month
                 ]
                 as $dateString => $flagResult
        ) {
            $result[] = [
                'message' => 'The date-time `' . $dateString . '`(Europe/Berlin) ' .
                    (($flagResult) ? 'is' : 'is not ') . ' active in the range of two hours, which is allowed for the date with the following attributes: ' .
                    'The date is the first or second weekday in the month. The date is part of friday, saturday oder sunday. ' .
                    'The date is in the range from 14:00 to 16:00. The date is in the month jan, feb or mar.',
                'expects' => [
                    'result' => $flagResult,
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateString,
                        new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'nthWeekdayInMonth' => '3',
                        // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                        'activeWeekday' => '97',
                        // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                        'startCountAtEnd' => false,
                        // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                        'startTimeSeconds' => 50400,
                        // 14:00 = 43200+7200 // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                        'activeMonth' => '7',
                        // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                        'durationMinutes' => '120',
                        // Minutes!!!!
                    ],
                    'general' => $general,

                ],
            ];

        }
        // variation of weekday in Month and order of it
        foreach ([
                     1 => '2022-04-01 14:00:00', // 1. th
                     2 => '2022-04-08 14:00:00', // 2. th
                     4 => '2022-04-15 14:00:00', // 3. th
                     8 => '2022-04-22 14:00:00', // 4. th
                     16 => '2022-04-29 14:00:00', // 5. th
                     -1 => '2022-09-30 14:00:00', // 1. th - startCountAtEnd=true
                     -2 => '2022-09-23 14:00:00', // 2. th - startCountAtEnd=true
                     -4 => '2022-09-16 14:00:00', // 3. th - startCountAtEnd=true
                     -8 => '2022-09-09 14:00:00', // 4. th - startCountAtEnd=true
                     -16 => '2022-09-02 14:00:00', // 5. th - startCountAtEnd=true
                 ]
                 as $nthDay => $dateStringOkayStart
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart,
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->add(new DateInterval('PT120M'));
            $dateFailEnd = clone $dateOkayEnd;
            $dateFailEnd->add(new DateInterval('PT1M'));
            $dateFailStart = clone $dateOkayStart;
            $dateFailStart->sub(new DateInterval('PT1M'));
            foreach ([[$dateOkayStart, true], [$dateOkayEnd, true], [$dateFailStart, false], [$dateFailEnd, false],]
                     as $helper) {
                $result[] = [
                    'message' => 'The date-time `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                        (($helper[1]) ? 'is' : 'is not ') . ' active in the range of two hours, which is allowed for the date with the following attributes: ' .
                        'The date is the ' . abs($nthDay) . 'nth weekday in the month. ' . (($nthDay < 0) ? '' : ' The order or Nth beginn at the end of the month. ') .
                        'The date is part of friday, saturday oder sunday. ' .
                        'The date is in the range from 14:00 to 16:00. The date is in the month jan, feb or mar.',
                    'expects' => [
                        'result' => $helper[1],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => abs($nthDay),
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => '97',
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => ($nthDay < 0),
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => 50400,
                            // 14:00 = 43200+7200 // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => '2047',
                            // every month allowed
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

                    ],
                ];
            }
        }
        // variation of Month
        $mapActiveDayWeekday = [
            1 => 'sunday',
            2 => 'monday',
            4 => 'tuesday',
            8 => 'wendesday',
            16 => 'thursday',
            32 => 'friday',
            64 => 'saturday',

        ];
        foreach ([0, 1, 2, 3, 4] as $addWeek) {
            foreach ([
                         1 => '2022-05-01 21:00:00', // So.
                         2 => '2022-05-02 21:00:00', // Mo
                         4 => '2022-05-03 21:00:00', // Tu
                         8 => '2022-05-04 21:00:00', // We
                         16 => '2022-05-05 21:00:00', // th
                         32 => '2022-05-06 21:00:00', // fr
                         64 => '2022-05-07 21:00:00', // sa
                     ]
                     as $activeDay => $dateStringOkayStart
            ) {
                $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart,
                    new DateTimeZone('Europe/Berlin'));
                if ($addWeek > 0) {
                    $currentDate = clone $dateOkayStart;
                    $currentDate->add(new DateInterval('P' . (7 * $addWeek) . 'D'));
                    if ($currentDate->format('m') !== $dateOkayStart->format('m')) {
                        continue;
                    }
                    $dateOkayStart = $currentDate;
                }
                $dateOkayEnd = clone $dateOkayStart;
                $dateOkayEnd->add(new DateInterval('PT120M'));
                $dateFailEnd = clone $dateOkayEnd;
                $dateFailEnd->add(new DateInterval('PT1M'));
                $dateFailStart = clone $dateOkayStart;
                $dateFailStart->sub(new DateInterval('PT1M'));
                foreach ([[$dateOkayStart, true], [$dateOkayEnd, true], [$dateFailStart, false], [$dateFailEnd, false],]
                         as $helper) {
                    $result[] = [
                        'message' => 'The date-time `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                            (($helper[1]) ? 'is' : 'is not ') . '  active in the range of two hours, ' .
                            'which is allowed for the date with the following attributes: ' .
                            'The date is the ' . ($addWeek + 1) . 'nth weekday in the month. ' .
                            'The date is part of ' . $mapActiveDayWeekday[$activeDay] . '. ' .
                            'The date is in the range from 14:00 to 16:00. The date is in the month jan, feb or mar.',
                        'expects' => [
                            'result' => $helper[1],
                        ],
                        'params' => [
                            'value' => $helper[0],
                            'setting' => [
                                'nthWeekdayInMonth' => (2 ** $addWeek),
                                // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                                'activeWeekday' => $activeDay,
                                // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                                'startCountAtEnd' => false,
                                // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                                'startTimeSeconds' => 75600,
                                // = 86400-10800 = 21:00,
                                // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                                'activeMonth' => '2047',
                                // every month allowed
                                // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                                'durationMinutes' => '120',
                                // Minutes!!!!
                            ],
                            'general' => $general,

                        ],
                    ];
                }
            }
        }
        // variation of Month
        $mapActiveToMonth = [
            1 => 'january',
            2 => 'february',
            4 => 'march',
            8 => 'april',
            16 => 'may',
            32 => 'june',
            64 => 'july',
            128 => 'august',
            256 => 'september',
            512 => 'october',
            1024 => 'november',
            2048 => 'december',
        ];
        foreach ([
                     1 => '2022-01-01 21:00:00',
                     2 => '2022-02-05 21:00:00',
                     4 => '2022-03-05 21:00:00',
                     8 => '2022-04-02 21:00:00',
                     16 => '2022-05-07 21:00:00',
                     32 => '2022-06-04 21:00:00',
                     64 => '2022-07-02 21:00:00',
                     128 => '2022-08-06 21:00:00',
                     256 => '2022-09-03 21:00:00',
                     512 => '2022-10-01 21:00:00',
                     1024 => '2022-11-05 21:00:00',
                     2048 => '2022-12-03 21:00:00',
                 ]
                 as $activeMonth => $dateStringOkayStart
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart,
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->add(new DateInterval('PT120M'));
            $dateFailEnd = clone $dateOkayEnd;
            $dateFailEnd->add(new DateInterval('PT1M'));
            $dateFailStart = clone $dateOkayStart;
            $dateFailStart->sub(new DateInterval('PT1M'));
            foreach ([[$dateOkayStart, true], [$dateOkayEnd, true], [$dateFailStart, false], [$dateFailEnd, false],]
                     as $helper) {
                $result[] = [
                    'message' => 'The date-time `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                        (($helper[1]) ? 'is' : 'is not ') . '  active in the range of two hours, ' .
                        'which is allowed for the date with the following attributes: ' .
                        'The date is the first saturday in the month `' . $mapActiveToMonth[$activeMonth] . '`. ' .
                        'The date is in the range from 14:00 to 16:00.',
                    'expects' => [
                        'result' => $helper[1],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => 1,
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => 64,
                            // 64 = saturday
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => false,
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => 75600,
                            // = 86400-10800 = 21:00,
                            // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => $activeMonth,
                            // every month allowed
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

                    ],
                ];
            }
        }
        // variation of start
        foreach ([
                     0 => '2022-01-01 00:00:00',
                     3600 => '2022-01-01 01:00:00',
                     7200 => '2022-01-01 02:00:00',
                     79200 => '2022-01-01 22:00:00',
                     82800 => '2022-01-01 23:00:00',
                     86340 => '2022-01-01 23:59:00',
                 ]
                 as $activeStartTimeInSoconds => $dateStringOkayStart
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart,
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->add(new DateInterval('PT120M'));
            $dateFailEnd = clone $dateOkayEnd;
            $dateFailEnd->add(new DateInterval('PT1M'));
            $dateFailStart = clone $dateOkayStart;
            $dateFailStart->sub(new DateInterval('PT1M'));
            foreach ([[$dateOkayEnd, true], [$dateOkayStart, true], [$dateFailStart, false], [$dateFailEnd, false],]
                     as $helper) {
                $result[] = [
                    'message' => 'The date-time `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                        (($helper[1]) ? 'is' : 'is not ') . '  active in the range of two hours, ' .
                        'which is allowed for the date with the following attributes: ' .
                        'The date is the first saturday in the january of 2022. ' .
                        'The date is in the range from 14:00 to 16:00.',
                    'expects' => [
                        'result' => $helper[1],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => 1,
                            // 1.1.2022
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => 64,
                            // 64 = saturday
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => false,
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => $activeStartTimeInSoconds,
                            // = 86400-10800 = 21:00,
                            // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => '1',
                            // every month allowed
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

                    ],
                ];
            }
        }

        return $result;
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

            $setting = array_merge($params['setting'], $params['general']);

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

        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];
        $result = [];
        // s-imple-test
        foreach ([
                     '2022-07-01 14:00:00' => ['beginning' => '2022-07-02 14:00:00', 'ending' => '2022-07-02 16:00:00'],
                     '2022-07-02 14:00:00' => ['beginning' => '2022-07-03 14:00:00', 'ending' => '2022-07-03 16:00:00'],
                     '2022-07-03 14:00:00' => ['beginning' => '2022-08-05 14:00:00', 'ending' => '2022-08-05 16:00:00'],
                     // 1. fr
                 ]
                 as $dateString => $expection
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateString,
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->add(new DateInterval('PT120M'));
            $dateBeforeNewStart = clone $dateOkayStart;
            $dateBeforeNewStart->add(new DateInterval('P1D'));
            $dateBeforeNewStart->sub(new DateInterval('PT1M'));
            foreach ([
                         [$dateOkayStart, true],
                         [$dateOkayEnd, true],
                         [$dateBeforeNewStart, true],
                     ]
                     as $helper) {

                $result[] = [
                    'message' => 'The next  date-time relative to `' . $dateString . '`(Europe/Berlin) ' .
                        ' is active in the range of two hours and starts at `' . $expection['beginning'] . '`.',
                    'expects' => [
                        'result' => [
                            'beginning' => $expection['beginning'],
                            'ending' => $expection['ending'],
                            'exist' => $helper[1],
                        ],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => '1',
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => '97',
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => false,
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => 50400,
                            // 14:00 = 43200+7200 // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => (64+128), // july and august
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

                    ],
                ];

            }
        }
        // variation of weekday in Month and order of it
        foreach ([
                     1 => ['2022-10-01 14:00:00',0], // 1. th
                     2 => ['2022-10-08 14:00:00',1], // 2. th
                     4 => ['2022-10-15 14:00:00',1], // 3. th
                     8 => ['2022-10-22 14:00:00',1], // 4. th
                     16 => ['2022-10-29 14:00:00',1], // 5. th
                     -1 => ['2022-10-29 14:00:00',8], // 1. th - startCountAtEnd=true
                     -2 => ['2022-10-22 14:00:00',8], // 2. th - startCountAtEnd=true
                     -4 => ['2022-10-15 14:00:00',8], // 3. th - startCountAtEnd=true
                     -8 => ['2022-10-08 14:00:00',0], // 4. th - startCountAtEnd=true
//                     -16 => ['2022-10-01 14:00:00',0], // 5. th - startCountAtEnd=true // not simple to define for the next date in November
                 ]
                 as $nthDay => $dateStringOkayStart
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart[0],
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->add(new DateInterval('PT120M'));
            $dateFailEnd = clone $dateOkayEnd;
            $dateFailEnd->add(new DateInterval('PT1M'));
            foreach ([
                [$dateOkayStart, ['beginning' => '2022-11-05 14:00:00', 'ending' => '2022-11-05 16:00:00', 'exist'=>true,],],
                         [$dateOkayEnd, ['beginning' => '2022-11-05 14:00:00', 'ending' => '2022-11-05 16:00:00', 'exist'=>true,],],
                         [$dateFailEnd, ['beginning' => '2022-11-05 14:00:00', 'ending' => '2022-11-05 16:00:00', 'exist'=>true,],],
                         ]
                     as $helper) {
                $result[] = [
                    'message' => 'The date-time `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                        (($helper[1]) ? 'is' : 'is not ') . ' active in the range of two hours, which is allowed for the date with the following attributes: ' .
                        'The date is the ' . abs($nthDay) . 'nth weekday in the month. ' . (($nthDay < 0) ? '' : ' The order or Nth beginn at the end of the month. ') .
                        'The date is part of friday, saturday oder sunday. ' .
                        'The date is in the range from 14:00 to 16:00. The date is in the month jan, feb or mar.',
                    'expects' => [
                        'result' => $helper[1],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => abs($nthDay)+$dateStringOkayStart[1],
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => '64',
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => ($nthDay < 0),
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => 50400,
                            // 14:00 = 43200+7200 // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => '2047',
                            // every month allowed
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

                    ],
                ];
            }
        }
        // variation of Month
        $mapActiveDayWeekday = [
            1 => 'sunday',
            2 => 'monday',
            4 => 'tuesday',
            8 => 'wendesday',
            16 => 'thursday',
            32 => 'friday',
            64 => 'saturday',

        ];
        foreach ([0, 1, 2] as $addWeek) { // 3, 4 won't work for the automatic logic
            foreach ([
                         1 => '2022-05-01 21:00:00', // So.
                         2 => '2022-05-02 21:00:00', // Mo
                         4 => '2022-05-03 21:00:00', // Tu
                         8 => '2022-05-04 21:00:00', // We
                         16 => '2022-05-05 21:00:00', // th
                         32 => '2022-05-06 21:00:00', // fr
                         64 => '2022-05-07 21:00:00', // sa
                     ]
                     as $activeDay => $dateStringOkayStart
            ) {
                $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart,
                    new DateTimeZone('Europe/Berlin'));
                if ($addWeek > 0) {
                    $currentDate = clone $dateOkayStart;
                    $currentDate->add(new DateInterval('P' . (7 * $addWeek) . 'D'));
                    if ($currentDate->format('m') !== $dateOkayStart->format('m')) {
                        continue;
                    }
                    $dateOkayStart = $currentDate;
                }
                $dateOkayEnd = clone $dateOkayStart;
                $dateOkayEnd->add(new DateInterval('PT120M'));
                $dateFailEnd = clone $dateOkayEnd;
                $dateFailEnd->add(new DateInterval('PT1M'));
                $dateFailStart = clone $dateOkayStart;
                $dateFailStart->sub(new DateInterval('PT1M'));
                $startCalc = clone $dateOkayStart;
                $startCalc->add(new DateInterval('P7D'));
                $endCalc = clone $dateOkayEnd;
                $endCalc->add(new DateInterval('P7D'));
                foreach ([
                             [$dateOkayStart, ['beginning' => $startCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $endCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                             [$dateOkayEnd, ['beginning' => $startCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $endCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
//                             [$dateFailStart, ['beginning' => $startCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $endCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                             [$dateFailEnd, ['beginning' => $startCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $endCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                         ]
                         as $helper) {
                    $result[] = [
                        'message' => 'The next range for the date-time `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                            ' is `' . $helper[1]['beginning'] . '` to `' . $helper[1]['ending'] . '`.' .
                            'The startdate is the ' . ($addWeek + 2) . 'nth weekday in the month. ' .
                            'The startdate is part of ' . $mapActiveDayWeekday[$activeDay] . '. ',
                        'expects' => [
                            'result' => $helper[1],
                        ],
                        'params' => [
                            'value' => $helper[0],
                            'setting' => [
                                'nthWeekdayInMonth' => (2 ** $addWeek) + (2 ** ($addWeek + 1)),
                                // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                                'activeWeekday' => $activeDay,
                                // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                                'startCountAtEnd' => false,
                                // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                                'startTimeSeconds' => 75600,
                                // = 86400-10800 = 21:00,
                                // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                                'activeMonth' => '2047',
                                // every month allowed
                                // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                                'durationMinutes' => '120',
                                // Minutes!!!!
                            ],
                            'general' => $general,

                        ],
                    ];
                }
            }
        }

        // variation of Month
        $mapActiveToMonth = [
            1 => 'january',
            2 => 'february',
            4 => 'march',
            8 => 'april',
            16 => 'may',
            32 => 'june',
            64 => 'july',
            128 => 'august',
            256 => 'september',
            512 => 'october',
            1024 => 'november',
            2048 => 'december',
        ];
        foreach ([
                     1 => '2022-01-01 21:00:00',
                     2 => '2022-02-05 21:00:00',
                     4 => '2022-03-05 21:00:00',
                     8 => '2022-04-02 21:00:00',
                     16 => '2022-05-07 21:00:00',
                     32 => '2022-06-04 21:00:00',
                     64 => '2022-07-02 21:00:00',
                     128 => '2022-08-06 21:00:00',
                     256 => '2022-09-03 21:00:00',
                     512 => '2022-10-01 21:00:00',
                     1024 => '2022-11-05 21:00:00',
                     2048 => '2022-12-03 21:00:00',
                 ]
                 as $activeMonth => $dateStringOkayStart
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart,
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->add(new DateInterval('PT120M'));
            $dateFailEnd = clone $dateOkayEnd;
            $dateFailEnd->add(new DateInterval('PT1M'));
            $nextYearStart = clone $dateOkayStart;
            $nextYearStart->add(new DateInterval('P1Y'));
            if ($dateOkayStart->format('d') > 1) {
                $nextYearStart->sub(new DateInterval('P1D'));
            } else {
                $nextYearStart->add(new DateInterval('P6D'));
            }
            $nextYearEnd = clone $nextYearStart;
            $nextYearEnd->add(new DateInterval('PT120M'));
            foreach ([
                         [$dateOkayStart,
                            [
                                 'beginning' => $nextYearStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'ending' => $nextYearEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'exist' => true,
                             ],
                         ],
                         [$dateOkayEnd,
                             [
                                 'beginning' => $nextYearStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'ending' => $nextYearEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'exist' => true,
                             ],
                         ],
                         [$dateFailEnd,
                             [
                                 'beginning' => $nextYearStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'ending' => $nextYearEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'exist' => true,
                             ],
                         ],
                     ]
                     as $helper) {
                $result[] = [
                    'message' => 'The rext date-time relative to `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                        'will be the range in the following year `' . $helper[1]['beginning'] . '` to `' . $helper[1]['ending'] . '`, ' .
                        'which is allowed for the date with the following attributes: ' .
                        'The date is the first saturday in the month `' . $mapActiveToMonth[$activeMonth] . '`. ' .
                        'The date has a range of two hours.',
                    'expects' => [
                        'result' => $helper[1],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => 1,
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => 64,
                            // 64 = saturday
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => false,
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => 75600,
                            // = 86400-10800 = 21:00,
                            // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => $activeMonth,
                            // every month allowed
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

                    ],
                ];
            }
        }
        // variation of start
        foreach ([
                     0 => '2022-01-01 00:00:00',
                     3600 => '2022-01-01 01:00:00',
                     7200 => '2022-01-01 02:00:00',
                     79200 => '2022-01-01 22:00:00',
                     82800 => '2022-01-01 23:00:00',
                     86340 => '2022-01-01 23:59:00',
                 ]
                 as $activeStartTimeInSoconds => $dateStringOkayStart
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart,
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->add(new DateInterval('PT120M'));
            $dateFailEnd = clone $dateOkayEnd;
            $dateFailEnd->add(new DateInterval('PT1M'));
            $nextRangeStart = clone $dateOkayStart;
            $nextRangeStart->add(new DateInterval('P1D'));
            $nextRangeEnd = clone $dateOkayEnd;
            $nextRangeEnd->add(new DateInterval('P1D'));
            foreach ([
                [$dateOkayEnd,  ['beginning' => $nextRangeStart->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $nextRangeEnd->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                         [$dateOkayStart,  ['beginning' => $nextRangeStart->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $nextRangeEnd->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                         [$dateFailEnd,  ['beginning' => $nextRangeStart->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $nextRangeEnd->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                         ]
                     as $helper) {
                $result[] = [
                    'message' => 'The date-time `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                        'will lead to the next 120 minutes-range (`'.$nextRangeStart->format(TimerInterface::TIMER_FORMAT_DATETIME).
                        '`,`'.$nextRangeEnd->format(TimerInterface::TIMER_FORMAT_DATETIME).'`), ' .
                        'which is allowed for every day in the month beginning at `'.$activeStartTimeInSoconds.
                        '`(seconds from midnight). ',
                    'expects' => [
                        'result' => $helper[1],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => 31,
                            // 1.1.2022
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => 127,
                            // 64 = saturday
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => false,
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => $activeStartTimeInSoconds,
                            // = 86400-10800 = 21:00,
                            // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => 2047,
                            // every month allowed
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

                    ],
                ];
            }
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

            $setting = array_merge($params['setting'], $params['general']);

            $value = clone $params['value'];

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

    public function dataProviderPrevActive()
    {

        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];
        $result = [];
        // s-imple-test
        foreach ([
                     '2022-07-01 14:00:00' => ['beginning' => '2022-06-05 14:00:00', 'ending' => '2022-06-05 16:00:00'],
                     '2022-07-02 14:00:00' => ['beginning' => '2022-07-01 14:00:00', 'ending' => '2022-07-01 16:00:00'],
                     '2022-07-03 14:00:00' => ['beginning' => '2022-07-02 14:00:00', 'ending' => '2022-07-02 16:00:00'],
                     // 1. fr
                 ]
                 as $dateString => $expection
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateString,
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->add(new DateInterval('PT120M'));
            $dateAfterNewEnding = clone $dateOkayEnd;
            $dateAfterNewEnding->sub(new DateInterval('P1D'));
            $dateAfterNewEnding->add(new DateInterval('PT1M'));
            foreach ([
                         [$dateOkayStart, true],
                         [$dateOkayEnd, true],
                         [$dateAfterNewEnding, true],
                     ]
                     as $helper) {

                $result[] = [
                    'message' => 'The nearest previous date-time relative to `' . $dateString . '`(Europe/Berlin) ' .
                        'is active in the range of two hours and starts at `' . $expection['beginning'] . '`. '.
                       'The allowed day must be a first,Friday, Satrurday or Sunday in june, july or august.',
                    'expects' => [
                        'result' => [
                            'beginning' => $expection['beginning'],
                            'ending' => $expection['ending'],
                            'exist' => $helper[1],
                        ],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => '1',
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => '97',
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => false,
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => 50400,
                            // 14:00 = 43200+7200 // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => (32+64+128), // july and august
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

                    ],
                ];

            }
        }
        // variation of weekday in Month and order of it
        foreach ([
                     1 => ['2022-10-01 16:00:00',16], // 1. th
                     2 => ['2022-10-07 16:00:00',16], // 2. th
                     4 => ['2022-10-14 16:00:00',16], // 3. th
                     8 => ['2022-10-21 16:00:00',16], // 4. th
//                     16 => ['2022-11-05 16:00:00',0], // 5. th
                     16 => ['2022-12-30 16:00:00',0], // 5. th // the november has no 5Th friday, the next month is september
                     -1 => ['2022-10-28 16:00:00',0], // 1. th - startCountAtEnd=true
                     -2 => ['2022-10-21 16:00:00',1], // 2. th - startCountAtEnd=true
                     -4 => ['2022-10-14 16:00:00',3], // 3. th - startCountAtEnd=true
                     -8 => ['2022-10-07 16:00:00',7], // 4. th - startCountAtEnd=true
//                     -16 => ['2022-12-02 16:00:00',15], // 5. th - startCountAtEnd=true // not simple to define for the next date in November
                 ]
                 as $nthDay => $dateStringOkayStart
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart[0],
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->sub(new DateInterval('PT120M'));
            $dateFailEnd = clone $dateOkayEnd;
            $dateFailEnd->sub(new DateInterval('PT1M'));
            foreach ([
                [$dateOkayStart, ['beginning' => '2022-09-30 14:00:00', 'ending' => '2022-09-30 16:00:00', 'exist'=>true,],],
                         [$dateOkayEnd, ['beginning' => '2022-09-30 14:00:00', 'ending' => '2022-09-30 16:00:00', 'exist'=>true,],],
                         [$dateFailEnd, ['beginning' => '2022-09-30 14:00:00', 'ending' => '2022-09-30 16:00:00', 'exist'=>true,],],
                         ]
                     as $helper) {
                $result[] = [
                    'message' => 'The date-time `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                        (($helper[1]) ? 'is' : 'is not ') . ' active in the range of two hours, which is allowed for the date with the following attributes: ' .
                        'The date is the ' . abs($nthDay) . 'nth weekday in the month. ' . (($nthDay < 0) ? '' : ' The order or Nth beginn at the end of the month. ') .
                        'The date is part of friday, saturday oder sunday. ' .
                        'The date is in the range from 14:00 to 16:00. The date is in the month jan, feb or mar.',
                    'expects' => [
                        'result' => $helper[1],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => abs($nthDay)+$dateStringOkayStart[1],
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => '32', // 64 = saturday
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => ($nthDay < 0),
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => 50400,
                            // 14:00 = 43200+7200 // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => '2047',
                            // every month allowed
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

                    ],
                ];
            }
        }
        // variation of weekday
        $mapActiveDayWeekday = [
            1 => 'sunday',
            2 => 'monday',
            4 => 'tuesday',
            8 => 'wednesday',
            16 => 'thursday',
            32 => 'friday',
            64 => 'saturday',

        ];
        foreach ([
            0,
                     1,
                     2] as $subWeek) { // 3, 4 won't work for the automatic logic
            foreach ([
                         1 => '2022-05-22 23:00:00', // So.
                         2 => '2022-05-23 23:00:00', // Mo
                         4 => '2022-05-24 23:00:00', // Tu
                         8 => '2022-05-25 23:00:00', // We
                         16 => '2022-05-26 23:00:00', // th
                         32 => '2022-05-27 23:00:00', // fr
                         64 => '2022-05-28 23:00:00', // sa
                     ]
                     as $activeDay => $dateStringOkayStart
            ) {
                $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart,
                    new DateTimeZone('Europe/Berlin'));
                if ($subWeek > 0) {
                    $currentDate = clone $dateOkayStart;
                    $currentDate->sub(new DateInterval('P' . (7 * $subWeek) . 'D'));
                    if ($currentDate->format('m') !== $dateOkayStart->format('m')) {
                        continue;
                    }
                    $dateOkayStart = $currentDate;
                }
                $dateOkayEnd = clone $dateOkayStart;
                $dateOkayEnd->sub(new DateInterval('PT120M'));
                $dateFailEnd = clone $dateOkayEnd;
                $dateFailEnd->sub(new DateInterval('PT1M'));
                $dateFailStart = clone $dateOkayStart;
                $dateFailStart->sub(new DateInterval('PT1M'));
                $startCalc = clone $dateOkayEnd;  // change order, because startingis defined by the upper border
                $startCalc->sub(new DateInterval('P7D'));
                $endCalc = clone $dateOkayStart;
                $endCalc->sub(new DateInterval('P7D'));
                foreach ([
                             [$dateOkayStart, ['beginning' => $startCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $endCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                             [$dateOkayEnd, ['beginning' => $startCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $endCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
//                             [$dateFailStart, ['beginning' => $startCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $endCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                             [$dateFailEnd, ['beginning' => $startCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $endCalc->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                         ]
                         as $helper) {
                    $result[] = [
                        'message' => 'The prev range for the date-time `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                            ' is `' . $helper[1]['beginning'] . '` to `' . $helper[1]['ending'] . '`.' .
                            'The startdate is the ' . ($subWeek + 2) . 'nth weekday in the month. ' .
                            'The startdate is part of ' . $mapActiveDayWeekday[$activeDay] . '. ',
                        'expects' => [
                            'result' => $helper[1],
                        ],
                        'params' => [
                            'value' => $helper[0],
                            'setting' => [
                                'nthWeekdayInMonth' => (2 ** (3-$subWeek)) + (2 ** (3-$subWeek - 1)),
                                // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                                'activeWeekday' => $activeDay,
                                // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                                'startCountAtEnd' => false,
                                // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                                'startTimeSeconds' => 75600,
                                // = 86400-10800 = 21:00,
                                // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                                'activeMonth' => 4095,
                                // every month allowed
                                // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                                'durationMinutes' => '120',
                                // Minutes!!!!
                            ],
                            'general' => $general,

                        ],
                    ];
                }
            }
        }


        // variation of Month
        $mapActiveToMonth = [
            1 => 'january',
            2 => 'february',
            4 => 'march',
            8 => 'april',
            16 => 'may',
            32 => 'june',
            64 => 'july',
            128 => 'august',
            256 => 'september',
            512 => 'october',
            1024 => 'november',
            2048 => 'december',
        ];
        foreach ([
                     1 => '2022-01-01 21:00:00',
                     2 => '2022-02-05 21:00:00',
                     4 => '2022-03-05 21:00:00',
                     8 => '2022-04-02 21:00:00',
                     16 => '2022-05-07 21:00:00',
                     32 => '2022-06-04 21:00:00',
                     64 => '2022-07-02 21:00:00',
                     128 => '2022-08-06 21:00:00',
                     256 => '2022-09-03 21:00:00',
                     512 => '2022-10-01 21:00:00',
                     1024 => '2022-11-05 21:00:00',
                     2048 => '2022-12-03 21:00:00',
                 ]
                 as $activeMonth => $dateStringOkayStart
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart,
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->add(new DateInterval('PT120M'));
            $dateFailEnd = clone $dateOkayStart;
            $dateFailEnd->sub(new DateInterval('PT1M'));
            $prevYearStart = clone $dateOkayStart;
            //            calculate the date for the 1 saturday in the month a year before
            $prevYearStart->sub(new DateInterval('P1Y'));
            if ($dateOkayStart->format('d') <7) {
                $prevYearStart->add(new DateInterval('P1D'));
            } else {
                $prevYearStart->sub(new DateInterval('P6D'));
            }
            $prevYearEnd = clone $prevYearStart;
            $prevYearEnd->add(new DateInterval('PT120M'));
            foreach ([
                         [$dateOkayStart,
                            [
                                 'beginning' => $prevYearStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'ending' => $prevYearEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'exist' => true,
                             ],
                         ],
                         [$dateOkayEnd,
                             [
                                 'beginning' => $prevYearStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'ending' => $prevYearEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'exist' => true,
                             ],
                         ],
                         [$dateFailEnd,
                             [
                                 'beginning' => $prevYearStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'ending' => $prevYearEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                                 'exist' => true,
                             ],
                         ],
                     ]
                     as $helper) {
                $result[] = [
                    'message' => 'The rext date-time relative to `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                        'will be the range in the following year `' . $helper[1]['beginning'] . '` to `' . $helper[1]['ending'] . '`, ' .
                        'which is allowed for the date with the following attributes: ' .
                        'The date is the first saturday in the month `' . $mapActiveToMonth[$activeMonth] . '`. ' .
                        'The date has a range of two hours.',
                    'expects' => [
                        'result' => $helper[1],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => 1,
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => 64,
                            // 64 = saturday
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => false,
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => 75600,
                            // = 86400-10800 = 21:00,
                            // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => $activeMonth,
                            // every month allowed
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

                    ],
                ];
            }
        }
        // variation of starttime
        foreach ([
                     0 => '2022-01-01 00:00:00',
                     3600 => '2022-01-01 01:00:00',
                     7200 => '2022-01-01 02:00:00',
                     79200 => '2022-01-01 22:00:00',
                     82800 => '2022-01-01 23:00:00',
                     86340 => '2022-01-01 23:59:00',
                 ]
                 as $activeStartTimeInSoconds => $dateStringOkayStart
        ) {
            $dateOkayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateStringOkayStart,
                new DateTimeZone('Europe/Berlin'));
            $dateOkayEnd = clone $dateOkayStart;
            $dateOkayEnd->add(new DateInterval('PT120M'));
            $dateFailEnd = clone $dateOkayStart;
            $dateFailEnd->sub(new DateInterval('PT1M'));
            $nextRangeStart = clone $dateOkayStart;
            $nextRangeStart->sub(new DateInterval('P1D'));
            $nextRangeEnd = clone $dateOkayEnd;
            $nextRangeEnd->sub(new DateInterval('P1D'));
            foreach ([
                [$dateOkayEnd,  ['beginning' => $nextRangeStart->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $nextRangeEnd->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                         [$dateOkayStart,  ['beginning' => $nextRangeStart->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $nextRangeEnd->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                         [$dateFailEnd,  ['beginning' => $nextRangeStart->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'ending' => $nextRangeEnd->format(TimerInterface::TIMER_FORMAT_DATETIME) , 'exist' => true,]],
                         ]
                     as $helper) {
                $result[] = [
                    'message' => 'The date-time `' . $helper[0]->format(TimerInterface::TIMER_FORMAT_DATETIME) . '`(Europe/Berlin) ' .
                        'will lead to the next 120 minutes-range (`'.$nextRangeStart->format(TimerInterface::TIMER_FORMAT_DATETIME).
                        '`,`'.$nextRangeEnd->format(TimerInterface::TIMER_FORMAT_DATETIME).'`), ' .
                        'which is allowed for every day in the month beginning at `'.$activeStartTimeInSoconds.
                        '`(seconds from midnight). ',
                    'expects' => [
                        'result' => $helper[1],
                    ],
                    'params' => [
                        'value' => $helper[0],
                        'setting' => [
                            'nthWeekdayInMonth' => 31,
                            // 1.1.2022
                            // first or second //  additional/bitwise => 1 = first, 2 = second, 4 = third, 8 = forth, 16 = fifth, if it exist for the month. All is in combination with StartCountAtEnd
                            'activeWeekday' => 127,
                            // 64 = saturday
                            // 97 = friday, Saturday or sunday // additional/bitwise => 1 = sunday, 2 = monday, 4= tuesday, ... , 64 = saturday.
                            'startCountAtEnd' => false,
                            // boolean: true = count the nTh weekday from the start of the month, false = count the nTh weekday from the end of the month,
                            'startTimeSeconds' => $activeStartTimeInSoconds,
                            // = 86400-10800 = 21:00,
                            // Delay from midnight in SECONDS (not minutes) Number between 0 and 86399
                            'activeMonth' => 4095,
                            // every month allowed
                            // jan, feb, mar = 7 //  additional/bitwise => 1 = january, 2 = february, 4 = march, ... , 2048 = december
                            'durationMinutes' => '120',
                            // Minutes!!!!
                        ],
                        'general' => $general,

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

            $setting = array_merge($params['setting'], $params['general']);

            $value = clone $params['value'];

            /** @var TimerStartStopRange $result */
            $result = $this->subject->prevActive($value, $setting);
            $flag = ($result->getBeginning()->format(TimerInterface::TIMER_FORMAT_DATETIME) === $expects['result']['beginning']);
            $flag = $flag && ($result->getEnding()->format(TimerInterface::TIMER_FORMAT_DATETIME) === $expects['result']['ending']);
            $flag = $flag && ($result->hasResultExist() === $expects['result']['exist']);
            $this->assertTrue(
                ($flag),
                'prevActive: ' . $message . "\nExpected: : " . print_r($expects['result'], true)
            );

        }
    }

}
