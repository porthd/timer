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

class WeekdaylyTimerTest extends TestCase
{
    protected const NAME_TIMER = 'txTimerWeekdayly';
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE = TimerInterface::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerInterface::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerInterface::ARG_ULTIMATE_RANGE_END;
    protected const SOME_NOT_EMPTY_VALUE = 'some value';
    protected const ALLOWED_TIME_ZONE = 'UTC';


    protected const SOME_DURATION_TIME = 60;

    /**
     * @var WeekdaylyTimer
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
        $this->subject = new WeekdaylyTimer();
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
        $this->assertEquals(
            self::NAME_TIMER,
            $this->subject::selfName(),
            'The name musst be defined.'
        );
    }

    /**
     * @test
     */
    public function getSelectorItem()
    {
        $result = $this->subject->getSelectorItem();
        $this->assertIsArray(
            $result,
            'The result must be an array.'
        );
        $this->assertGreaterThan(
            1,
            count($result),
            'The array  must contain at least two items.'
        );
        $this->assertIsString(
            $result[0],
            'The first item must be an string.'
        );
        $this->assertEquals(
            $result[1],
            self::NAME_TIMER,
            'The second term must the name of the timer.'
        );
    }

    /**
     * @test
     */
    public function getFlexformItem()
    {
        $result = $this->subject->getFlexformItem();
        $this->assertIsArray(
            $result,
            'The result must be an array.'
        );
        $this->assertEquals(
            1,
            count($result),
            'The array  must contain one Item.'
        );
        $this->assertEquals(
            array_keys($result),
            [self::NAME_TIMER],
            'The key must the name of the timer.'
        );
        $this->assertIsString(
            $result[self::NAME_TIMER],
            'The value must be type of string.'
        );
        $rootPath = $_ENV['TYPO3_PATH_ROOT']; //Test relative to root-Path beginning in  ...web/
        $filePath = $result[self::NAME_TIMER];
        if (strpos($filePath, TimerConst::MARK_OF_FILE_EXT_FOLDER_IN_FILEPATH) === 0) {
            $resultPath = $rootPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR .
                substr(
                    $filePath,
                    strlen(TimerConst::MARK_OF_FILE_EXT_FOLDER_IN_FILEPATH)
                );
        } else {
            if (strpos($filePath, TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH) === 0) {
                $resultPath = $rootPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR .
                    substr(
                        $filePath,
                        strlen(TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH)
                    );
                $this->assertTrue(
                    (false),
                    'The File-path should contain `' . TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH . '`, so that the TCA-attribute-action `onChange` will work correctly. '
                );
            } else {
                $resultPath = $rootPath . DIRECTORY_SEPARATOR . $filePath;
            }
        }
        $flag = (!empty($resultPath)) && file_exists($resultPath);
        $this->assertTrue(
            $flag,
            'The file with the flexform content exist.'
        );
        $fileContent = GeneralUtility::getURL($resultPath);
        $flexArray = simplexml_load_string($fileContent);
        $this->assertTrue(
            (!(!$flexArray)),
            'The filecontent is valid xml.'
        );
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
                    'activeWeekday' => 96, // test
                ],
                'optional' => [

                ],
                'general' => $general,
                'obsolete' => [

                ],
            ],
        ];
        // variation of requiered parmeters
        foreach (['activeWeekday',] as $item) {
            $undefined = [
                'message' => 'The test is incorrect, because `' . $item . '` in the needed arguments is missing.',
                [
                    'result' => false,
                ],
                [
                    'required' => [
                        'activeWeekday' => 96, // unset
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
        }

        // Variation of Parameter'activeWeekday'
        foreach ([
                     1 => true,
                     2 => true,
                     4 => true,
                     8 => true,
                     16 => true,
                     32 => true,
                     64 => true,
                     127 => true,
                     67 => true,
                     '32.1' => false,
                     0 => false,
                     128 => false,
                     -1 => false,
                     -2 => false,
                 ] as $value => $res
        ) {
            $singleOptional = [
                'message' => 'The test for `activeWeekday` ' . ($res ? 'is okay' : 'failed') .
                    ', because `activeWeekday` has the numeric value `' . $value . '`.',
                'expects' => [
                    'result' => $res,
                ],
                'params' => [
                    'required' => [
                        'activeWeekday' => $value, // Variation
                    ],
                    'optional' => [
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
                        'activeWeekday' => ($value . ''),  // Variation
                    ],
                    'optional' => [
                    ],
                    'general' => $general,
                    'obsolete' => [

                    ],
                ],
            ];
            $result[] = $singleOptional;
        }
        foreach ([[12], new DateTime('now')] as $value) {
            $result[] = [
                'message' => 'The test for `activeWeekday` failed' .
                    ', because `activeWeekday` must be an integer between 1 and 128 not a `' . print_r(
                        $value,
                        true
                    ) . '`.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'activeWeekday' => $value, // Variation
                    ],
                    'optional' => [
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
                'result' => false,
            ],
            'params' => [
                'required' => [
                    'activeWeekday' => null, // test
                ],
                'optional' => [
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
                    'activeWeekday' => 32.1,  // test
                ],
                'optional' => [
                ],
                'general' => $general,
                'obsolete' => [

                ],
            ],
        ];

        // all variations of optional parmeters and variation of all optional-parameters
        $allOptionalTogether = [
            'message' => 'The test is okay, because all optional arguments are present.',
            [
                'result' => true,
            ],
            [
                'required' => [
                    'activeWeekday' => 96,
                ],
                'optional' => [
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
                     [null, false],
                     [false, true],
                     ['false', true],
                     [new Datetime(), false],
                     ['hallo', false],
                     ['0', true],
                     [0.0, true],
                     ["0.0", false],
                     ['true', true],
                     ['1', true],
                     [1, true],
                     [1.0, true],
                     ['1.0', false],
                 ] as $value) {
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


    public function dataProvider_isAllowedInRange()
    {
        $testDate = date_create_from_format(
            TimerInterface::TIMER_FORMAT_DATETIME,
            '2020-12-31 12:00:00',
            new DateTimeZone('Europe/Berlin')
        );
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
        $result = [];
        // variation Weekday and date
        foreach ([
                     1 => '2021-01-04',
                     2 => '2021-01-05',
                     4 => '2021-01-06',
                     8 => '2021-01-07',
                     16 => '2021-01-08',
                     32 => '2021-01-09',
                     64 => '2021-01-10',
                 ] as $activeWeekday => $dateString) {
            foreach ([
                         '00:00:00' => 'PT1S',
                         '06:00:00' => 'PT6H1S',
                         '12:00:00' => 'PT12H1S',
                         '23:59:59' => 'PT24H',
                     ] as $okayTime => $failSub) {
                $okayDate = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $dateString . ' ' . $okayTime,
                    new DateTimeZone('Europe/Berlin')
                );
                foreach (['', 'P7D', 'P2W', 'P10W', 'P1400D'] as $key => $addOkay) {
                    if (!empty($addOkay)) {
                        if ($key % 2 === 0) {
                            $okayDate->add(new DateInterval($addOkay));
                        } else {
                            $okayDate->sub(new DateInterval($addOkay));
                        }
                    }
                    $failDateBelow = clone $okayDate;
                    $failDateBelow->sub(new DateInterval($failSub)); //
                    $failDateAbove = clone $failDateBelow; // = 23:59:59 Yesterday to okayDay
                    $failDateAbove->add(new DateInterval('P1DT2S')); // 00:00:01 tomorrow to okayday
                    $result[] = [
                        'message' => 'The dateTime  `' . $dateString . $okayTime .
                            '` will be active for the Weekday with key `' . $activeWeekday . '`.',
                        'expects' => [
                            'result' => true,
                        ],
                        'params' => [
                            'value' => $okayDate, // variated relative to variation with respect to result
                            'setting' => [
                                'activeWeekday' => $activeWeekday, // // variation
                                // general
                                'useTimeZoneOfFrontend' => true,
                                'timeZoneOfEvent' => 'Europe/Berlin',
                                'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                                'ultimateEndingTimer' => '9999-12-31 23:59:59',
                            ],
                        ],
                    ];
                    $result[] = [
                        'message' => 'The dateTime  `' . $dateString . $okayTime . '` subbed by `' . $failSub .
                            '`  not be active for the Weekday with key `' . $activeWeekday . '`.',
                        'expects' => [
                            'result' => false,
                        ],
                        'params' => [
                            'value' => $failDateBelow,  // variated relative to variation with respect to result
                            'setting' => [
                                'activeWeekday' => $activeWeekday, // variation
                                // general
                                'useTimeZoneOfFrontend' => true,
                                'timeZoneOfEvent' => 'Europe/Berlin',
                                'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                                'ultimateEndingTimer' => '9999-12-31 23:59:59',
                            ],
                        ],
                    ];
                    $result[] = [
                        'message' => 'The dateTime  `' . $dateString . $okayTime . '` subbed by `' . $failSub .
                            '` and added `P1DT2S`(One Day Two Seconds) not be active for the Weekday with key `' .
                            $activeWeekday . '`.',
                        'expects' => [
                            'result' => false,
                        ],
                        'params' => [
                            'value' => $failDateAbove,  // variated relative to variation with respect to result
                            'setting' => [
                                'activeWeekday' => $activeWeekday, // variation
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
        }

        for ($i = 1; $i < 128; $i += 2) {
            $result[] = [
                'message' => 'The dateTime `` will be active for the Weekday-Kombination key `' . $i .
                    '`. (mondayindex optional plus other weekday(s)',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        '2021-01-04 12:00:00',
                        new DateTimeZone('Europe/Berlin')
                    ), // variated relative to variation with respect to result
                    'setting' => [
                        'activeWeekday' => $i, // // variation
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // 3. The Variation of `timeZoneOfEvent` and `useTimeZoneOfFrontend` is not relevant
        foreach ([true, false] as $useTimeZoneOfFrontend) {
            foreach ([
                         'UTC',
                         'Europe/Berlin',
                         'Australia/Eucla',
                         'America/Detroit',
                         'Pacific/Fiji',
                         'Indian/Chagos',
                     ] as $timezoneName) {
                $check = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2020-12-27 11:00:00',
                    new DateTimeZone('Europe/Berlin')
                );
                $result[] = [
                    'message' => 'The date with additional Interval  will be NOT active. It works independently to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' => clone $check,
                        'setting' => [
                            'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $check->add(new DateInterval('PT13H'));
                $result[] = [
                    'message' => 'The date with additional Interval  will be active. It works independently to the timezone `' . $timezoneName . '`.',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' => clone $check,
                        'setting' => [
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
        foreach ([
                     '0001-01-01 00:00:00',
                     '2020-12-27 11:00:00',
                     '2020-12-27 13:00:00',
                     '2020-12-27 18:00:00',
                     '9999-12-31 23:59:59',
                 ] as $timeString) {
            $check = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                '2020-12-27 11:00:00',
                new DateTimeZone('Europe/Berlin')
            );
            $result[] = [
                'message' => 'The teststring for ultimate endtime `' . $timeString . '` extend the ultimate range to `2020-12-27 11:00:00` or abowe for an positive result. ',
                'expects' => [
                    'result' => ('2020-12-27 11:00:00' <= $timeString),
                ],
                'params' => [
                    'value' => clone $check,
                    'setting' => [
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => false, // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => '2020-12-27 11:00:00',
                        'ultimateEndingTimer' => $timeString,
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The teststring for ultimate begintime `' . $timeString . '` extend the ultimate range to `2020-12-27 11:00:00` or below for an positive result. ',
                'expects' => [
                    'result' => ('2020-12-27 11:00:00' >= $timeString),
                ],
                'params' => [
                    'value' => clone $check,
                    'setting' => [
                        'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                        'useTimeZoneOfFrontend' => false, // Variation
                        'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                        'ultimateBeginningTimer' => $timeString,
                        'ultimateEndingTimer' => '2020-12-27 11:00:00',
                    ],
                ],
            ];


            $check->add(new DateInterval('PT13H'));
            if ('2020-12-27 11:00:00' >= $timeString) {
                $result[] = [
                    'message' => 'The date with additional Interval  will be active. It is independ to the ultimate-parameter. ',
                    'expects' => [
                        'result' => false,
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
                            'ultimateEndingTimer' => '2020-12-29 13:00:00',
                        ],
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
        // 1. rondomly Test with positiv result
        $result[] = [
            'message' => 'The nextRange in this example is correctly detected. ',
            'expects' => [
                'result' => [
                    'beginning' => '2020-12-27 00:00:00',
                    'ending' => '2020-12-27 23:59:59',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-26 11:00:00', new DateTimeZone('Europe/Berlin')),
                'setting' => [
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
        foreach (['2020-12-28 12:00:00', '2020-12-28 15:00:00', '2020-12-30 12:00:00', '2021-01-01 12:00:00', '2021-01-01 23:59:59'] as $testTime) {
            $result[] = [
                'message' => 'The nextRange is detected correctly for the date `' . $testTime . '` because the days are not part of the allowed weekdays. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2021-01-02 00:00:00',
                        'ending' => '2021-01-02 23:59:59',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testTime, new DateTimeZone('Europe/Berlin')),
                    'setting' => [
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
            $nextWeekDate = $nextWeek->format('Y-m-d');
            $tomorrow = DateTime::createFromFormat('Y-m-d', $testDate);
            $tomorrow->add(new DateInterval('P1D'));
            $tomorrowDate = $tomorrow->format('Y-m-d');
            foreach (['00:00:00','12:00:00', '23:59:59'] as $time) {
                $result[] = [
                    'message' => 'The nextRange `'.$nextWeekDate.'` is detected correctly on the same day for the date `' . $testDate . ' '.$time.
                        '`, because it lies in the future.',
                    'expects' => [
                        'result' => [
                            'beginning' => $nextWeekDate . ' 00:00:00',
                            'ending' => $nextWeekDate . ' 23:59:59',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testDate . ' '.$time, new DateTimeZone('Europe/Berlin')),
                        'setting' => [
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
                    'message' => 'The nextRange `'.$nextWeekDate.
                        '` is detected correctly on the same day for the `tomorrow`-date `' . $tomorrowDate . ' '.$time.
                        '`, because it lies in the future.',
                    'expects' => [
                        'result' => [
                            'beginning' => $nextWeekDate . ' 00:00:00',
                            'ending' => $nextWeekDate . ' 23:59:59',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $tomorrowDate . ' '.$time, new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'activeWeekday' => $weekday, // =only sunday and saturday 2020-12-27 is a sunday
                        // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
            }
            $yesterday = DateTime::createFromFormat('Y-m-d', $testDate);
            $yesterday->sub(new DateInterval('P1D'));
            $result[] = [
                'message' => 'The testdate `'.$testDate.'` is detected correctly for the range, relative to yesterday-Date'.
                    '`, because the range of the testdate lies in the future.',
                'expects' => [
                    'result' => [
                        'beginning' => $testDate . ' 00:00:00',
                        'ending' => $testDate . ' 23:59:59',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $yesterday->format('Y-m-d') . ' 12:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'activeWeekday' => $weekday, // =only sunday and saturday 2020-12-27 is a sunday
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
                    'message' => 'The nextRange is correctly detected. It works independently to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-27 00:00:00',
                            'ending' => '2020-12-27 23:59:59',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-26 11:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The nextRange is correctly detected. It works independently to the timezone `' . $timezoneName . '`.',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-27 00:00:00',
                            'ending' => '2020-12-27 23:59:59',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-26 15:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
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
        foreach ([
                     '0001-01-01 00:00:00',
                     '2020-12-27 11:00:00',
                     '2020-12-27 13:00:00',
                     '2020-12-27 18:00:00',
                     '2020-12-31 18:00:00',
                     '2021-01-02 23:59:58',
                     '9999-12-31 23:59:59',
                 ] as $timeString) {
            if (($timeString >= '2020-12-27 13:00:00') &&
                ($timeString < '2021-01-02 00:00:00') // diasallow next sunday
            ) {  // allow only correctly ordered times
                $result[] = [
                    'message' => 'The nextRange fails, because the last nextRange is disallowed by the ultimate parameter.  ' .
                        'The begin is `2020-12-27 13:00:00` and the variated end is `' . $timeString . '`. ',
                    'expects' => [
                        'result' => [
                            'beginning' => '-7980-12-26 11:00:00',
                            'ending' => '2020-12-26 10:59:59',
                            'exist' => false,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-26 11:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'activeWeekday' => 96, // =only sunday and saturday 2020-12-27 is a sunday
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '2020-12-27 13:00:00',
                            'ultimateEndingTimer' => $timeString, // Variation
                        ],
                    ],
                ];
            }
            if ($timeString <= '2020-12-27 13:00:00') { // allow only correctly ordered times
                $result[] = [
                    'message' => 'The nextRange fails, because the last nextRange is disallowed by the ultimate parameter.  ' .
                        'The end is `2020-12-27 13:00:00` and the variated beginn is `' . $timeString . '`. ',
                    'expects' => [
                        'result' => [
                            'beginning' => '-7980-12-26 11:00:00',
                            'ending' => '2020-12-26 10:59:59',
                            'exist' => false,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-26 11:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
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
        // 1. rondomly Test with positiv result
        $result[] = [
            'message' => 'The prevRange in this example is correctly detected. ',
            'expects' => [
                'result' => [
                    'beginning' => '2020-12-27 00:00:00',
                    'ending' => '2020-12-27 23:59:59',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-28 11:00:00', new DateTimeZone('Europe/Berlin')),
                'setting' => [
                    'activeWeekday' => 65, // =only sunday and monday 2020-12-27 is a prev sunday
                    // general
                    'useTimeZoneOfFrontend' => 'true', // Variation
                    'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        // 1.b check the weekend-Funktion
        foreach (['2020-12-29 00:00:00', '2020-12-29 15:00:00', '2020-12-30 12:00:00', '2021-01-01 12:00:00', '2021-01-01 23:59:59'] as $testTime) {
            $result[] = [
                'message' => 'The prevRange is detected correctly for the date `' . $testTime . '` because the days are not part of the allowed weekdays. ',
                'expects' => [
                    'result' => [
                        'beginning' => '2020-12-28 00:00:00',
                        'ending' => '2020-12-28 23:59:59',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testTime, new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'activeWeekday' => 65, // =only sunday and monday 2020-12-27 is a prev sunday
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
            $prevWeekDate = $prevWeek->format('Y-m-d');
            $yesterday = DateTime::createFromFormat('Y-m-d', $testDate);
            $yesterday->sub(new DateInterval('P1D'));
            $yesterdayDate = $yesterday->format('Y-m-d');
            foreach (['00:00:00','12:00:00', '23:59:59'] as $time) {
                $result[] = [
                    'message' => 'The prevRange `'.$prevWeekDate.'` is detected correctly on the same day for the date `' . $testDate . ' '.$time.
                        '`, because it lies in the future.',
                    'expects' => [
                        'result' => [
                            'beginning' => $prevWeekDate . ' 00:00:00',
                            'ending' => $prevWeekDate . ' 23:59:59',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testDate . ' '.$time, new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'activeWeekday' => $weekday, // Variation in combination with Date
                            // general
                            'useTimeZoneOfFrontend' => 'true',
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The prevRange `'.$prevWeekDate.
                        '` is detected correctly on the same day for the `tomorrow`-date `' . $yesterdayDate . ' '.$time.
                        '`, because it lies in the future.',
                    'expects' => [
                        'result' => [
                            'beginning' => $prevWeekDate . ' 00:00:00',
                            'ending' => $prevWeekDate . ' 23:59:59',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $yesterdayDate . ' '.$time, new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'activeWeekday' => $weekday, // Variation in combination with Date
                            // general
                            'useTimeZoneOfFrontend' => 'true',
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
            }
            $tomorrow = DateTime::createFromFormat('Y-m-d', $testDate);
            $tomorrow->add(new DateInterval('P1D'));
            $result[] = [
                'message' => 'The testdate `'.$testDate.'` is detected correctly for the range, relative to yesterday-Date'.
                    '`, because the range of the testdate lies in the future.',
                'expects' => [
                    'result' => [
                        'beginning' => $testDate . ' 00:00:00',
                        'ending' => $testDate . ' 23:59:59',
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $tomorrow->format('Y-m-d') . ' 12:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'activeWeekday' => $weekday, // Variation in combination with Date
                        // general
                        'useTimeZoneOfFrontend' => 'true',
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
                    'message' => 'The prevRange is correctly detected. It works independently to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-27 00:00:00',
                            'ending' => '2020-12-27 23:59:59',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-28 11:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'activeWeekday' => 65, // =only sunday and monday 2020-12-27 is a prev sunday
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The prevRange is correctly detected. It works independently to the timezone `' . $timezoneName . '`.',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-27 00:00:00',
                            'ending' => '2020-12-27 23:59:59',
                            'exist' => true,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-28 15:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'activeWeekday' => 65, // =only sunday and monday 2020-12-27 is a prev sunday
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
        foreach ([
                     '0001-01-01 00:00:00',
                     '2020-12-27 11:00:00',
                     '2020-12-27 13:00:00',
                     '2020-12-27 18:00:00',
                     '9999-12-31 23:59:59',
                 ] as $timeString) {
            if ($timeString >= '2020-12-27 13:00:00') {
                $result[] = [
                    'message' => 'The prevRange is correctly detected relative to `2020-12-28 11:00:00`, '.
                        'but it don`t fit the ultimate range, '.
                        'which is between the fixed begin `2020-12-27 13:00:00` and the iterated end `'.$timeString.'`.',
                    'expects' => [
                        'result' => [
                            'beginning' => '2020-12-28 11:00:01',
                            'ending' => '12020-12-28 11:00:00',
                            'exist' => false,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-28 11:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'activeWeekday' => 65, // =only sunday and monday 2020-12-27 is a prev sunday
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '2020-12-27 13:00:00',
                            'ultimateEndingTimer' => $timeString, // Variation
                        ],
                    ],
                ];
            }
            if ($timeString <= '2020-12-27 13:00:00') {
                $result[] = [
                    'message' => 'The prevRange is correctly detected relative to `2020-12-28 11:00:00`, '.
                        'but it don`t fit the ultimate range, '.
                        'which is between the fixed end `2020-12-27 13:00:00` and the iterated begin `'.$timeString.'`.',
                    'expects' => [
                        'result' => [
//                            'beginning' => '2020-12-27 00:00:00',
//                            'ending' => '2020-12-27 23:59:59',
//                            'exist' => true,
                            'beginning' => '2020-12-28 11:00:01',
                            'ending' => '12020-12-28 11:00:00',
                            'exist' => false,
                        ],
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-28 11:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'activeWeekday' => 65, // =only sunday and monday 2020-12-27 is a prev sunday
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => $timeString, // Variation
                            'ultimateEndingTimer' => '2020-12-27 13:00:00',
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
