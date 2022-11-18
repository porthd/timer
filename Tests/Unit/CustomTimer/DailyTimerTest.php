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
use Porthd\Timer\Utilities\GeneralTimerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DailyTimerTest extends TestCase
{
    protected const NAME_TIMER = 'txTimerDaily';
    protected const SOME_NOT_EMPTY_VALUE = 'some value';
    protected const ALLOWED_TIME_ZONE = 'UTC';



    /**
     * @var DailyTimer
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
        $this->subject = new DailyTimer();
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
    public function selfNameForCorrectOutput()
    {
        $this->assertEquals(self::NAME_TIMER,
            $this->subject::selfName(),
            'The name musst be defined.');
    }

    /**
     * @test
     */
    public function getSelectorItemForCorrectOutput()
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
     * @return array[]
     */
    public function dataProviderValidateSpecialByVariationArgumentsInParam()
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
            'message' => 'The test is correct, because all needed arguments are used.',
            [
                'result' => true,
            ],
            [
                'required' => [
                    'startTimeSeconds' => 43200,
                    'durationMinutes' => 120,
                ],
                'optional' => [

                ],
                'general' => $general,
                'obsolete' => [

                ],
            ],
        ];
        // variation of requiered parmeters
        foreach (['startTimeSeconds', 'durationMinutes',] as $item) {
            $undefined = [
                'message' => 'The test is incorrect, because `' . $item . '` in the needed arguments is missing.',
                [
                    'result' => false,
                ],
                [
                    'required' => [
                        'startTimeSeconds' => 43200,
                        'durationMinutes' => 120,
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
            unset($undefined[1]['required'][$item]);   // test
            $result[] = $undefined;
            foreach (['null' => null, 'zero' => 0, 'false' => false, 'empty' => ''] as $key => $value) {
                $failDefined = [
                    'message' => 'The test is not correct, because `' . $item . '` in the needed arguments is set to `' . $key . '`.',
                    [
                        'result' => false,
                    ],
                    [
                        'required' => [
                            'startTimeSeconds' => 43200,
                            'durationMinutes' => 120,
                        ],
                        'optional' => [

                        ],
                        'general' => $general,
                        'obsolete' => [

                        ],
                    ],
                ];
                $failDefined[1]['required'][$item] = $value;  // test special types
                $result[] = $failDefined;
            }
        }
        // single variation of optional parmeters and variation of all optional-parameters
        foreach (['activeWeekday' => 96,] as $item => $value) {
            $singleOptional = [
                'message' => 'The test is okay, because one of the optional arguments (`' . $item . '`) is present.',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'required' => [
                        'startTimeSeconds' => 43200,
                        'durationMinutes' => 120,
                    ],
                    'optional' => [
                        $item => $value, // Variation
                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
            unset($singleOptional['params']['optional'][$item]);
            $result[] = $singleOptional;
        }
        // single variation of optional parmeters `activeWeekday`

        foreach ([1 => true, 2 => true, 4 => true, 8 => true, 16 => true, 32 => true, 64 => true, 127 => true, 67 => true,
                     '32.1' => false, 0 => false, 128 => false, -1 => false, -2 => false,] as $value => $res
        ) {
            $singleOptional = [
                'message' => 'The test for `activeWeekday` ' . ($res ? 'is okay' : 'failed') .
                    ', because `activeWeekday` has the numeric value `' . $value . '`.',
                'expects' => [
                    'result' => $res,
                ],
                'params' => [
                    'required' => [
                        'startTimeSeconds' => 43200,
                        'durationMinutes' => 120,
                    ],
                    'optional' => [
                        'activeWeekday' => $value, // Variation
                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
            $result[] = $singleOptional;
            $singleOptional = [
                'message' => 'The test for `activeWeekday` ' . ($res ? 'is okay' : 'failed') .
                    ', because `activeWeekday` has the string value `' . $value . '`.',
                'expects' => [
                    'result' => $res,
                ],
                'params' => [
                    'required' => [
                        'startTimeSeconds' => 43200,
                        'durationMinutes' => 120,
                    ],
                    'optional' => [
                        'activeWeekday' => ($value . ''),  // Variation
                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
            $result[] = $singleOptional;
        }
        foreach ([[12],  new DateTime('now')] as $value) {
            $result[] = [
                'message' => 'The test for `activeWeekday` failed' .
                    ', because `activeWeekday` must be an integer between 1 and 128 not a `' . print_r($value, true) . '`.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'startTimeSeconds' => 43200,
                        'durationMinutes' => 120,
                    ],
                    'optional' => [
                        'activeWeekday' => $value, // Variation
                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
        }
        $result[] = [
            'message' => 'The test for `activeWeekday` will be okay' .
                ', because a `null` in `activeWeekday` is not allowed.',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'required' => [
                    'startTimeSeconds' => 43200,
                    'durationMinutes' => 120,
                ],
                'optional' => [
                    'activeWeekday' => null, // test
                ],
                'general' => $general,
                'obsolete' => [

                ],
            ],
        ];
        $result[] = [
            'message' => 'The test for `activeWeekday` will be okay' .
                ', because a float-number  in `activeWeekday` is not allowed.',
            'expects' => [
                'result' => false,
            ],
            'params' => [
                'required' => [
                    'startTimeSeconds' => 43200,
                    'durationMinutes' => 120,
                ],
                'optional' => [
                    'activeWeekday' => 32.1,  // test
                ],
                'general' => $general,
                'obsolete' => [

                ],
            ],
        ];

        // single variation of optional parmeters and variation of all optional-parameters
        foreach (['activeWeekday'=>96,] as $item => $res) {
            $singleOptional = [
                'message' => 'The test is okay, because one of the optional arguments (`' . $item . '`) is present.',
                [
                    'result' => true,
                ],
                [
                    'required' => [
                        'startTimeSeconds' => 43200,
                        'durationMinutes' => 120,
                    ],
                    'optional' => [
                        $item => $res, // variation
                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
            $result[] = $singleOptional;
        }
        // all variations of optional parmeters and variation of all optional-parameters
        $allOptionalTogether = [
            'message' => 'The test is okay, because all optional arguments are present.',
            [
                'result' => true,
            ],
            [
                'required' => [
                    'startTimeSeconds' => 43200,
                    'durationMinutes' => 120,
                ],
                'optional' => [
                    'activeWeekday' => 96,
                ],
                'general' => $general,
                'obsolete' => [

                ],
            ],
        ];
        $result[] = $allOptionalTogether;

        // variation of requiered parmeters
        $result[] = [
            'message' => 'The test results is okay, because the item `obsolete` parameters should be ignored as an undefined parameter.',
            [
                'result' => true,
            ],
            [
                'required' => [
                    'startTimeSeconds' => 43200,
                    'durationMinutes' => 120,
                ],
                'optional' => [
                    'activeWeekday' => 96,
                ],
                'general' => $general,
                'obsolete' => [
                    'dummy' => 'not defined value',  // test -
                ],
            ],
        ];
        // variation of startTimeSeconds
        /// Attentioon second argument
        foreach (['12:35:00' => false, '26:35:00' => false, 'asdfaf' => false, 43200 => true] as $value => $expected) {
            $result[] = [
                'message' => 'The test ' . ($expected ? 'is okay' : 'failed') . ' with the value `' . $value . '` in the parameter `starttime`.',
                [
                    'result' => $expected, // variation
                ],
                [
                    'required' => [
                        'startTimeSeconds' => $value, // variation
                        'durationMinutes' => 120,
                    ],
                    'optional' => [
                        'activeWeekday' => 96,
                    ],
                    'general' => $general,
                    'obsolete' => [
                        'dummy' => 'not defined value',
                    ],
                ],
            ];
        }
        // variation of startTimeSeconds
        foreach (['-1440' => false, '-1439' => true, '0' => false, '1439' => true, '1440' => false,] as $value => $expected) {
            $result[] = [
                'message' => 'The test ' . ($expected ? 'is okay' : 'failed') . ' with the value `' . $value . '` in the parameter `durationMinutes`.',
                [
                    'result' => $expected, // variation
                ],
                [
                    'required' => [
                        'startTimeSeconds' => 43200,
                        'durationMinutes' => $value, // variation
                    ],
                    'optional' => [
                        'activeWeekday' => 96,
                    ],
                    'general' => $general,
                    'obsolete' => [
                        'dummy' => 'not defined value',
                    ],
                ],
            ];
        }
        // variation of obsolete parameter
        $result[] = [
            'message' => 'The test results is okay, because the item `obsolete` parameters should be ignored as an undefined parameter.',
            [
                'result' => true,
            ],
            [
                'required' => [
                    'startTimeSeconds' => 43200,
                    'durationMinutes' => 120,
                ],
                'optional' => [
                    'activeWeekday' => 96,
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
     * @dataProvider dataProviderValidateSpecialByVariationArgumentsInParam
     * @test
     */
    public function validateSpecialByVariationArgumentsInParam($message, $expects, $params)
    {

        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or emopty dataprovider');
        } else {

            $paramTest = array_merge($params['required'], $params['optional'], $params['general'], $params['obsolete']);
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
            'startTimeSeconds' => 43200,
            'durationMinutes' => 120,
            'activeWeekday' => 96,
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

    public function dataProviderIsAllowedInRange()
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
     * @dataProvider dataProviderIsAllowedInRange
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
        foreach ([1 => '2021-01-04', 2 => '2021-01-05', 4 => '2021-01-06', 8 => '2021-01-07', 16 => '2021-01-08',
                     32 => '2021-01-09', 64 => '2021-01-10'] as $activeWeekday => $dateString) {
            $result[] = [
                'message' => 'The date  `' . $dateString . '` will be active for the key `' . $activeWeekday . '`.',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateString . ' 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => $activeWeekday, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The date  `' . $dateString . '` will be NOT active for the key `' . $activeWeekday . '`.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $dateString . ' 11:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 50400, // =12:00
                        'durationMinutes' => -120, // =2Std
                        'activeWeekday' => $activeWeekday, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];

        }
        foreach ([0, 43200, 80000, 82799, 82800, 86399, 86359] as $starttimeSeconds) {
            $check = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2021-01-04 ' . '00:00:00', new DateTimeZone('Europe/Berlin'));
            $check->add(new DateInterval('PT' . $starttimeSeconds . 'S'));
            $check->sub(new DateInterval('PT60M'));
            $result[] = [
                'message' => 'The startime in seconds `' . $starttimeSeconds . '` will be active for the day 4. Jan 2020.',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'value' => $check,
                    'setting' => [
                        'startTimeSeconds' => $starttimeSeconds, // =12:00
                        'durationMinutes' => -120, // =2Std
                        'activeWeekday' => 1, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The startime in seconds `' . $starttimeSeconds . '` will NOT be active for the day 4. Jan 2020..',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'value' => $check,
                    'setting' => [
                        'startTimeSeconds' => $starttimeSeconds, // =12:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => 1, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        foreach ([1 => 0, 10 => 0, 120 => 0, 1439 => 1] as $durMin => $corDayCheck) {
            foreach ([-1, 1] as $factor) {

                $check = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2021-01-04 ' . '12:00:00', new DateTimeZone('Europe/Berlin'));
                if ($corDayCheck > 0) {
                    if ($factor < 0) {
                        $check->sub(new DateInterval('P1D'));
                    } else {
                        $check->add(new DateInterval('P1D'));
                    }
                }
                $result[] = [
                    'message' => 'The Range in minutes `' . ($factor * $durMin) . '` will be active for the day 4. Jan 2020 12:00:00.',
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' => $check,
                        'setting' => [
                            'startTimeSeconds' => (43200 - $factor * $durMin * 60 + 4 * 86400) % 86400, // =12:00
                            'durationMinutes' => $factor * $durMin, // =2Std
                            'activeWeekday' => 1, // =only sunday and saturday 2020-12-27 is a sunday
                            // general
                            'useTimeZoneOfFrontend' => true,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The Range in minutes `' . ($factor * $durMin) . '` will NOT be active for the day 4. Jan 2020 12:00:00.',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' => $check,
                        'setting' => [
                            'startTimeSeconds' => (43200 - $factor * $durMin * 60 - $factor * 60 + 4 * 86400) % 86400, // =12:00
                            'durationMinutes' => $factor * $durMin, // =2Std
                            'activeWeekday' => 1, // =only sunday and saturday 2020-12-27 is a sunday
                            // general
                            'useTimeZoneOfFrontend' => true,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];

            }
        }

        // 1. Test with variation of Time and positive durationminutes
        // + 5. Test the Day-Overlay for the active period
        foreach (['PT1M' => false, 'PT1H' => true, 'PT2H' => true, 'PT3H' => true, 'PT3H1S' => false, 'P1DT2H' => false, 'P5DT2H' => false, 'P6DT2H' => true, 'P6DT3H1S' => false,] as $diff => $expects) {
            $check = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin'));
            $checkOverlayStart = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 22:00:00', new DateTimeZone('Europe/Berlin'));
            $checkOverlayEnd = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-26 22:00:00', new DateTimeZone('Europe/Berlin'));
            $check->add(new DateInterval($diff));
            $checkOverlayStart->add(new DateInterval($diff));
            $checkOverlayEnd->add(new DateInterval($diff));
            $result[] = [
                'message' => 'The date with additional Interval `' . $diff . '` will ' . ($expects ? 'be active.' : 'be NOT active.'),
                'expects' => [
                    'result' => $expects,
                ],
                'params' => [
                    'value' => clone $check,
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The date with additional Interval `' . $diff . '` will ' . ($expects ? 'be active.' : 'be NOT active.') .
                    'The active period has an day-overflow with positive durationminutes. Remarkable is the Day of the start. ',
                'expects' => [
                    'result' => $expects,
                ],
                'params' => [
                    'value' => clone $checkOverlayStart,
                    'setting' => [
                        'startTimeSeconds' => 82800, // =23:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The date with minus-duration-time  `' . $diff . '` will ' . ($expects ? 'be active.' : 'be NOT active.') .
                    'The active period has an day-overflow with negative durationminutes. Remarkable is the Day of the start. ',
                'expects' => [
                    'result' => $expects,
                ],
                'params' => [
                    'value' => clone $checkOverlayEnd,
                    'setting' => [
                        'startTimeSeconds' => 3600, // =12:00 = 43200 + 7200 = 14:00
                        'durationMinutes' => -120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // 2. Test with variation of Time and negative durationminutes
        foreach (['PT1M' => false, 'PT1H' => true, 'PT2H' => true, 'PT3H' => true, 'PT3H1S' => false, 'P1DT2H' => false, 'P5DT2H' => false, 'P6DT2H' => true, 'P6DT3H1S' => false,] as $diff => $expects) {

            $check = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin'));
            $check->add(new DateInterval($diff));
            $result[] = [
                'message' => 'The date with minus-duration-time  `' . $diff . '` will ' . ($expects ? 'be active.' : 'be NOT active.'),
                'expects' => [
                    'result' => $expects,
                ],
                'params' => [
                    'value' => $check,
                    'setting' => [
                        'startTimeSeconds' => 50400, // =12:00 = 43200 + 7200 = 14:00
                        'durationMinutes' => -120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
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
                $check = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin'));
                $result[] = [
                    'message' => 'The date with additional Interval  will be NOT active. It is indepent to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' => clone $check,
                        'setting' => [
                            'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                            'durationMinutes' => 120, // =2Std
                            'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $check->add(new DateInterval('PT2H'));
                $result[] = [
                    'message' => 'The date with additional Interval  will be active. It is indepent to the timezone `' . $timezoneName . '`.',
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' => clone $check,
                        'setting' => [
                            'startTimeSeconds' => 50400, // =14:00 //// in seconds relative to 0:00
                            'durationMinutes' => -120, // =2Std
                            'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
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
            $check = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin'));
            $result[] = [
                'message' => 'The date with additional Interval  will be NOT active. It is independ to the ultimate-parameter. ',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'value' => clone $check,
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
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
                        'startTimeSeconds' => 50400, // =14:00 //// in seconds relative to 0:00
                        'durationMinutes' => -120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
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
                'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                'setting' => [
                    'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                    'durationMinutes' => 120, // =2Std
                    'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                    // general
                    'useTimeZoneOfFrontend' => 'true', // Variation
                    'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        // 1.b check the weekend-Funktion
        foreach (['2020-12-27 12:00:00', '2020-12-27 15:00:00', '2020-12-30 12:00:00', '2021-01-01 12:00:00', '2021-01-02 11:59:59'] as $testTime) {
            $result[] = [
                'message' => 'The nextRange is detected correctly for the date `' . $testTime . '` because the days are not part of the allowed weekdays. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2021-01-02 12:00:00',
                        'ending' => '2021-01-02 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testTime, new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        // 2 check each weekday separately
        foreach ([64 => '2020-12-27', 1 => '2020-12-28', 2 => '2020-12-29', 4 => '2020-12-30', 8 => '2020-12-31', 16 => '2021-01-01', 32 => '2021-01-02',] as $weekday => $testDate) {
            $nextWeek = DateTime::createFromFormat('Y-m-d', $testDate);
            $nextWeek->add(new DateInterval('P7D'));
            $result[] = [
                'message' => 'The nextRange is detected correctly on the same day for the date `' . $testDate . ' 11:59:59`, because it lies in the future.',
                'expects' => [
                    'result' => [
                        'beginning' => $testDate . ' 12:00:00',
                        'ending' => $testDate . ' 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testDate . ' 11:59:59', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => $weekday, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The nextRange (next week) is detected correctly for the date `' . $testDate . ' 13:00:00`, because the range on the current date ist touched.',
                'expects' => [
                    'result' => [
                        'beginning' => $nextWeek->format('Y-m-d') . ' 12:00:00',
                        'ending' => $nextWeek->format('Y-m-d') . ' 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testDate . ' 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => $weekday, // Variation
                        // general
                        'useTimeZoneOfFrontend' => 'true',
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The randomly selected nextRange (next week) is detected correctly for the date `' . $testDate . ' 14:00:01`, because the range of the current day ended earlier. with the ',
                'expects' => [
                    'result' => [
                        'beginning' => $nextWeek->format('Y-m-d') . ' 12:00:00',
                        'ending' => $nextWeek->format('Y-m-d') . ' 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testDate . ' 14:00:01', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => $weekday, // Variation
                        // general
                        'useTimeZoneOfFrontend' => 'true',
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        // 2. Variate first Parameter

        // 3. The Variation of `timeZoneOfEvent` and `useTimeZoneOfFrontend` is not relevant
        foreach ([true, false] as $useTimeZoneOfFrontend) {
            foreach (['UTC', 'Europe/Berlin', 'Australia/Eucla', 'America/Detroit', 'Pacific/Fiji', 'Indian/Chagos'] as $timezoneName) {
                $check = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin'));
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
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                            'durationMinutes' => 120, // =2Std
                            'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $check->add(new DateInterval('PT2H'));
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
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => 50400, // =14:00 //// in seconds relative to 0:00
                            'durationMinutes' => -120, // =2Std
                            'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
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
            $check = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin'));
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
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '2020-12-27 13:00:00',
                        'ultimateEndingTimer' => $timeString, // Variation
                    ],
                ],
            ];
            $check->add(new DateInterval('PT2H'));
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
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 11:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 50400, // =14:00 //// in seconds relative to 0:00
                        'durationMinutes' => -120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
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
                'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin')),
                'setting' => [
                    'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                    'durationMinutes' => 120, // =2Std
                    'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                    // general
                    'useTimeZoneOfFrontend' => 'true', // Variation
                    'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        // 1.b check the weekend-Funktion
        foreach (['2020-12-27 15:00:00', '2020-12-27 15:00:00', '2020-12-30 12:00:00', '2021-01-01 12:00:00', '2021-01-02 11:59:59'] as $testTime) {
            $result[] = [
                'message' => 'The prevRange is detected correctly for the date `' . $testTime . '` because the days are not part of the allowed weekdays. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2020-12-27 12:00:00',
                        'ending' => '2020-12-27 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testTime, new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        // 2 check each weekday separately
        foreach ([64 => '2020-12-27', 1 => '2020-12-28', 2 => '2020-12-29', 4 => '2020-12-30', 8 => '2020-12-31', 16 => '2021-01-01', 32 => '2021-01-02',] as $weekday => $testDate) {
            $prevWeek = DateTime::createFromFormat('Y-m-d', $testDate);
            $prevWeek->sub(new DateInterval('P7D'));
            $result[] = [
                'message' => 'The prevRange is detected correctly on the same day for the date `' . $testDate . ' 11:59:59`, because it lies in the future.',
                'expects' => [
                    'result' => [
                        'beginning' => $testDate . ' 12:00:00',
                        'ending' => $testDate . ' 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testDate . ' 14:00:01', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => $weekday, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => 'true', // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The prevRange (next week) is detected correctly for the date `' . $testDate . ' 13:00:00`, because the range on the current date ist touched.',
                'expects' => [
                    'result' => [
                        'beginning' => $prevWeek->format('Y-m-d') . ' 12:00:00',
                        'ending' => $prevWeek->format('Y-m-d') . ' 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testDate . ' 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => $weekday, // Variation
                        // general
                        'useTimeZoneOfFrontend' => 'true',
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The randomly selected prevRange (next week) is detected correctly for the date `' . $testDate . ' 11:59:59`, because the range of the current day ended earlier. with the ',
                'expects' => [
                    'result' => [
                        'beginning' => $prevWeek->format('Y-m-d') . ' 12:00:00',
                        'ending' => $prevWeek->format('Y-m-d') . ' 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testDate . ' 11:59:59', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => $weekday, // Variation
                        // general
                        'useTimeZoneOfFrontend' => 'true',
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
                $check = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin'));
                $result[] = [
                    'message' => 'The prevRange is correctly detected. It is indepent to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-27 12:00:00',
                            'ending' => '2020-12-27 14:00:00',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                            'durationMinutes' => 120, // =2Std
                            'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $check->add(new DateInterval('PT2H'));
                $result[] = [
                    'message' => 'The prevRange is correctly detected. It is indepent to the timezone `' . $timezoneName . '`.',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-27 12:00:00',
                            'ending' => '2020-12-27 14:00:00',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'startTimeSeconds' => 50400, // =14:00 //// in seconds relative to 0:00
                            'durationMinutes' => -120, // =2Std
                            'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
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
            $check = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin'));
            $result[] = [
                'message' => 'The prevRange is correctly detected. It is independ to the ultimate-parameter. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2020-12-27 12:00:00',
                        'ending' => '2020-12-27 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 43200, // =12:00 // in seconds relative to 0:00
                        'durationMinutes' => 120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '2020-12-27 13:00:00',
                        'ultimateEndingTimer' => $timeString, // Variation
                    ],
                ],
            ];
            $check->add(new DateInterval('PT2H'));
            $result[] = [
                'message' => 'The prevRange is correctly detected. It is independ to the ultimate-parameter. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2020-12-27 12:00:00',
                        'ending' => '2020-12-27 14:00:00',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-27 15:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'startTimeSeconds' => 50400, // =14:00 //// in seconds relative to 0:00
                        'durationMinutes' => -120, // =2Std
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
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
