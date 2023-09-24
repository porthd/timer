<?php
declare(strict_types=1);

namespace Porthd\Timer\Tests\Unit\CustomTimer;

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

use Porthd\Timer\CustomTimer\MoonriseRelTimer;
use TYPO3\CMS\Core\Context\Context;
use DateInterval;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MoonriseRelTimerTest extends TestCase
{
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE =TimerInterface::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerInterface::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerInterface::ARG_ULTIMATE_RANGE_END;
    protected const NAME_TIMER = 'txTimerMoonriseRel';
    protected const SOME_NOT_EMPTY_VALUE = 'some value';
    protected const ALLOWED_TIME_ZONE = 'UTC';


    /**
     * @var MoonriseRelTimer
     */
    protected $subject = null;

    protected function simulatePartOfGlobalsTypo3Array()
    {

        $GLOBALS['TYPO3_CONF_VARS'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['timer'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['timer']['changeListOfTimezones'] = [];
        $GLOBALS['EXEC_TIME'] = 1609088941; // 12/27/2020 @ 5:09pm (UTC)
    }

    protected function resolveGlobalsTypo3Array()
    {
        // unset($GLOBALS);
        $GLOBALS['TYPO3_CONF_VARS'] = [];
        $GLOBALS['EXEC_TIME'] = 0;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->simulatePartOfGlobalsTypo3Array();
        $this->subject = new MoonriseRelTimer();
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
        $this->assertEquals((true), (true), 'I should an evergreen, but I am incomplete! :-)');
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
        $result = $this->subject::getSelectorItem();
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
        } elseif (strpos($filePath, TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH) === 0) {
            $resultPath = $rootPath . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR .
                substr(
                    $filePath,
                    strlen(TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH)
                );
            $this->assertTrue((false), 'The File-path should contain `'.TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH.'`, so that the TCA-attribute-action `onChange` will work correctly. ');
        } else {
            $resultPath = $rootPath . DIRECTORY_SEPARATOR . $filePath;
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

    public static function dataProvider_isAllowedInRange()
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
    public static function dataProviderValidateGeneralByVariationArgumentsInParam()
    {
        $rest = [
            'moonStatus' => 'moonrise',
            'relMinToSelectedTimerEvent' => '240',
            'durationMinutes' => '120',
            'latitude' => '53.0792962', // latitude Bremen 	53.0792962
            'longitude' => '8.8016937', // longitude Bremen 	8.8016937
        ];
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
    public static function dataProviderValidateSpeciallByVariationArgumentsInParam()
    {
        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];
        unset($result);
        $result = [];
        /* test allowed minimal structure */
        $result[] = [
            'message' => 'The test with a random setting is correct, because all needed arguments are used.',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'required' => [
                    'moonStatus' => 'moonrise',
                    'relMinToSelectedTimerEvent' => '240',
                    'durationMinutes' => '120',
                    'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                    'longitude' => '8.8016937', // longitude Bremen 	8.8016937
                ],
                'optional' => [

                ],
                'general' => $general,
            ],
        ];
        // Variation moonrise missing
        foreach (['moonStatus', 'relMinToSelectedTimerEvent', 'durationMinutes', 'latitude', 'longitude'] as $myUnset) {
            $item = [
                'message' => 'The test for moonStatus with `' . $myUnset . '` is NOT correct.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '240',
                        'durationMinutes' => '120',
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
        // Variation moonrise missing
        foreach ([
                     'moonrise' => true,
                     'moonset' => true,
                     1 => false,
                     0 => false,
                     'moonhigh' => false,
                 ] as $moonphase => $myExpects) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The test for moonStatus with `' . $moonphase .
                    ($myExpects ? '` is correct' : '` is NOT correct') . '.',
                [
                    'result' => $myExpects,
                ],
                [
                    'required' => [
                        'moonStatus' => $moonphase,
                        'relMinToSelectedTimerEvent' => '240',
                        'durationMinutes' => '120',
                        'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                        'longitude' => '8.8016937', // longitude Bremen 	8.8016937
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];
        }

        // variation of relMinToSelectedTimerEvent
        foreach ([
                     1440 => false,
                     -1440 => false,
                     -1439 => true,
                     1439 => true,
                     '-1439' => true,
                     '1439' => true,
                     '-10' => true,
                     '10' => true,
                     '-10.0' => true,
                     '10.0' => true,
                     0 => true,
                     '0.0' => true,
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
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => $myMin, // Variation
                        'durationMinutes' => '120',
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
                     1440 => false,
                     -1440 => false,
                     -1439 => true,
                     1439 => true,
                     '-1439' => true,
                     '1439' => true,
                     '-10 ' => false,
                     '-10' => true,
                     '10' => true,
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
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => $myMin, // Variation
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
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '120',
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
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '120',
                        'latitude' => '53.0792962', // latitude Bremen 	53.0792962
                        'longitude' => $myLongi, // Variation
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];
        }

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
            $paramTest = array_merge($params['required'], $params['optional'], $params['general']);
            $this->assertEquals(
                $expects['result'],
                $this->subject->validate($paramTest),
                $message
            );
        }
    }


    public static function dataProviderGetTimeZoneOfEvent()
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

    public static function dataProviderIsActive()
    {
        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];
        $longitude = 13.404954; // berlin
        $latitude = 52.520007; // berlin
        $result = [];
        /**
         * Beispiele für Aktive Bereichen Monaufgang(Aufg.) und Monduntergang(Unterg.) für Berlin
         * siehe https://vollmond-info.de/mondaufgang-und-monduntergang-im-september/
         * Datum                Aufg.   Unterg. Aufg.
         * 1. September 2022    12:10    21:42            21,0 %
         * 2. September 2022    13:35    22:02            30,5 %
         * 3. September 2022    15:01    22:30            41,0 %
         * 4. September 2022    16:23    23:12            52,2 %
         * 5. September 2022                    17:34   63,5 %
         * 6. September 2022            00:12    18:27    74,3 %
         * 7. September 2022            01:30    19:04    83,8 %
         * 8. September 2022            03:00    19:29    91,5 %
         * 9. September 2022            04:32    19:48    96,9 %
         * 10. September 2022        06:02    20:02    99,7 %
         * 11. September 2022        07:29    20:15    99,7 %
         * 12. September 2022        08:52    20:27    97,0 %
         * 13. September 2022        10:13    20:40    92,2 %
         * 14. September 2022        11:33    20:55    85,6 %
         * 15. September 2022        12:51    21:14    77,6 %
         * 16. September 2022        14:07    21:39    68,8 %
         * 17. September 2022        15:17    22:11    59,5 %
         * 18. September 2022        16:18    22:56    49,9 %
         * 19. September 2022        17:07    23:52    40,5 %
         * 20. September 2022        17:44        31,5 %
         * 21. September 2022    00:59    18:10        23,0 %
         * 22. September 2022    02:11    18:30        15,5 %
         * 23. September 2022    03:27    18:46        9,1 %
         * 24. September 2022    04:42    18:59        4,2 %
         * 25. September 2022    05:59    19:11        1,1 %
         * 26. September 2022    07:16    19:22        0,0 %
         * 27. September 2022    19:22    19:34        1,1 %
         * 28. September 2022    09:57    19:48        4,5 %
         * 29. September 2022    11:23    20:06        10,2 %
         * 30. September 2022    12:49    20:31        17,9 %
         *
         * add 2 hours
         * range = 2 hours
         */
        // test for moonrise
        foreach ([
                     '2022-09-01 12:10:00' => false,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 14:09:00' => false,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 14:10:00' => true,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 14:11:00' => true,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 16:09:00' => true,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 16:10:00' => true,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35 // 2 hour range
                     '2022-09-01 16:11:00' => false,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 21:42:00' => false,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 23:42:00' => false,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-02 13:35:00' => false,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-02 15:34:00' => false,
                     // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-02 15:35:00' => true,
                     // (next)moonrise 1. September 2022 	12:10, 2. September 2022 	13:35

                     '2022-09-20 01:51:00' => false,
                     // moonrise 19. September 2022 23:52
                     '2022-09-20 01:52:00' => true,
                     // moonrise 19. September 2022 23:52
                     '2022-09-20 01:53:00' => true,
                     // moonrise 19. September 2022 23:52
                     '2022-09-20 03:50:00' => true,
                     // moonrise 19. September 2022 23:52
                     '2022-09-20 03:51:00' => true,
                     // moonrise 19. September 2022 23:52
                     '2022-09-20 03:52:00' => true,
                     // moonrise 19. September 2022 23:52
                     '2022-09-20 03:53:00' => false,
                     // moonrise 19. September 2022 23:52

                     '2022-09-26 07:15:00' => false,
                     // moonrise(newmoon)  26. September 2022 	07:16
                     '2022-09-26 09:15:00' => false,
                     // moonrise(newmoon)  26. September 2022 	07:16
                     '2022-09-26 09:16:00' => true,
                     // moonrise(newmoon)  26. September 2022 	07:16
                     '2022-09-26 09:17:00' => true,
                     // moonrise(newmoon)  26. September 2022 	07:16
                 ]
                 as $dateString => $flagResult
        ) {
            $result[] = [
                'message' => 'The date-time `' . $dateString . '`(Europe/Berlin) ' .
                    (($flagResult) ? 'is' : 'is not ') . ' active in the range of two hours, which starts ' .
                    'two hours after the moonrise in berlin/europe.',
                'expects' => [
                    'result' => $flagResult,
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,

                ],
            ];
        }
        // test for moonset
        foreach ([
                     /**
                      * Datum                Aufg.   Unterg. Aufg.
                      * 1. September 2022    12:10    21:42            21,0 %
                      * 2. September 2022    13:35    22:02            30,5 %
                      * 6. September 2022            00:12    18:27    74,3 %
                      * 20. September 2022        17:44        31,5 %
                      * 26. September 2022    07:16    19:22        0,0 %
                      *
                      * sub 2 hours
                      * range = -2 hours
                      */
                     '2022-09-01 17:41:00' => false,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 -2h -2h
                     '2022-09-01 17:42:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 -2h -2h
                     '2022-09-01 19:41:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 -2h -2h
                     '2022-09-01 19:42:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 -2h -2h
                     '2022-09-01 19:43:00' => false,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 -2h -2h
                     '2022-09-02 18:01:00' => false,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 -2h -2h
                     '2022-09-02 18:02:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 -2h -2h
                     '2022-09-02 20:01:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 -2h -2h
                     '2022-09-02 20:02:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 -2h -2h
                     '2022-09-02 20:03:00' => false,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 -2h -2h

                     '2022-09-05 20:11:00' => false,
                     // moonset 6. September 2022    		00:12 	-2h -2h
                     '2022-09-05 20:12:00' => true,
                     // moonset 6. September 2022    		00:12 	-2h -2h
                     '2022-09-05 22:12:00' => true,
                     // moonset 6. September 2022    		00:12 	-2h -2h
                     '2022-09-05 22:13:00' => false,
                     // moonset 6. September 2022    		00:12 	-2h -2h

                     '2022-09-20 13:43:00' => false,
                     // moonset 20. September 2022   		17:44 	-2h -2h
                     '2022-09-20 13:44:00' => true,
                     // moonset 20. September 2022   		17:44 	-2h -2h
                     '2022-09-20 15:44:00' => true,
                     // moonset 20. September 2022   		17:44 	-2h -2h
                     '2022-09-20 15:45:00' => false,
                     // moonset 20. September 2022   		17:44 	-2h -2h

                     '2022-09-26 15:21:00' => false,
                     // moonset(newmoon) 26. September 2022 	07:16 	19:22 	-2h -2h
                     '2022-09-26 15:22:00' => true,
                     // moonset(newmoon) 26. September 2022 	07:16 	19:22 	-2h -2h
                     '2022-09-26 17:22:00' => true,
                     // moonset(newmoon) 26. September 2022 	07:16 	19:22 	-2h -2h
                     '2022-09-26 17:23:00' => false,
                     // moonset(newmoon) 26. September 2022 	07:16 	19:22 	-2h -2h

                 ]
                 as $dateString => $flagResult
        ) {
            $result[] = [
                'message' => 'The date-time `' . $dateString . '`(Europe/Berlin) ' .
                    (($flagResult) ? 'is' : 'is not ') . ' active in the range of two hours, which ends ' .
                    'two hours before the moonset in berlin/europe.',
                'expects' => [
                    'result' => $flagResult,
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonset',
                        'relMinToSelectedTimerEvent' => '-120',
                        'durationMinutes' => '-120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,

                ],
            ];
        }
        // test for moonrise
        foreach ([
                     '2022-09-01 10:09:00' => false, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 10:10:00' => true, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 12:09:00' => true, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 12:10:00' => true, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 12:11:00' => false, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-01 14:09:00' => false, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-02 13:36:00' => false, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-02 13:35:00' => true, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-02 12:35:00' => true, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-02 11:35:00' => true, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35
                     '2022-09-02 11:34:00' => false, // moonrise 1. September 2022 	12:10, 2. September 2022 	13:35

                     '2022-09-21 01:00:00' => false, // moonrise * 21. September 2022 	00:59
                     '2022-09-21 00:59:00' => true,   // moonrise * 21. September 2022 	00:59
                     '2022-09-21 00:00:00' => true,   // moonrise * 21. September 2022 	00:59
                     '2022-09-20 23:59:59' => true,   // moonrise * 21. September 2022 	00:59
                     '2022-09-20 23:00:00' => true,   // moonrise * 21. September 2022 	00:59
                     '2022-09-20 22:59:00' => true,   // moonrise * 21. September 2022 	00:59
                     '2022-09-20 22:58:59' => false,   // moonrise * 21. September 2022 	00:59
                     '2022-09-20 22:58:00' => false,   // moonrise * 21. September 2022 	00:59

                     '2022-09-26 07:17:00' => false,   // moonrise(newmoon)  26. September 2022 	07:16
                     '2022-09-26 07:16:00' => true,   // moonrise(newmoon)  26. September 2022 	07:16
                     '2022-09-26 05:16:00' => true,   // moonrise(newmoon)  26. September 2022 	07:16
                     '2022-09-26 05:15:00' => false,   // moonrise(newmoon)  26. September 2022 	07:16
                 ]
                 as $dateString => $flagResult
        ) {
            $result[] = [
                'message' => 'The date-time `' . $dateString . '`(Europe/Berlin) ' .
                    (($flagResult) ? 'is' : 'is not ') . ' active in the range of two hours, which starts ' .
                    'two hours before the moonrise in berlin/europe. (equal to starting 2 hours before moonrise to moonrise',
                'expects' => [
                    'result' => $flagResult,
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '-120',
                        'durationMinutes' => '120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,

                ],
            ];
        }
        // test for moonset
        foreach ([
                     /**
                      * Datum                Aufg.   Unterg. Aufg.
                      * 1. September 2022    12:10    21:42            21,0 %
                      * 2. September 2022    13:35    22:02            30,5 %
                      * 6. September 2022            00:12    18:27    74,3 %
                      * 20. September 2022        17:44        31,5 %
                      * 26. September 2022    07:16    19:22        0,0 %
                      *
                      * sub 2 hours
                      * range = -2 hours
                      */
                     '2022-09-01 21:41:00' => false,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 2h -2h
                     '2022-09-01 21:42:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 2h -2h
                     '2022-09-01 23:41:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 2h -2h
                     '2022-09-01 23:42:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 2h -2h
                     '2022-09-01 23:43:00' => false,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 2h -2h
                     '2022-09-02 22:01:00' => false,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 2h -2h
                     '2022-09-02 22:02:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 2h -2h
                     '2022-09-03 00:01:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 2h -2h
                     '2022-09-03 00:02:00' => true,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 2h -2h
                     '2022-09-03 00:03:00' => false,
                     // moonset 1. September 2022 	21:42, 2. September 2022 	22:02 2h -2h

                     '2022-09-06 00:11:00' => false,
                     // moonset 6. September 2022    		00:12 	2h -2h
                     '2022-09-06 00:12:00' => true,
                     // moonset 6. September 2022    		00:12 	2h -2h
                     '2022-09-06 02:12:00' => true,
                     // moonset 6. September 2022    		00:12 	2h -2h
                     '2022-09-06 02:13:00' => false,
                     // moonset 6. September 2022    		00:12 	2h -2h

                     '2022-09-20 17:43:00' => false,
                     // moonset 20. September 2022   		17:44 	2h -2h
                     '2022-09-20 17:44:00' => true,
                     // moonset 20. September 2022   		17:44 	2h -2h
                     '2022-09-20 19:44:00' => true,
                     // moonset 20. September 2022   		17:44 	2h -2h
                     '2022-09-20 19:45:00' => false,
                     // moonset 20. September 2022   		17:44 	2h -2h

                     '2022-09-26 19:21:00' => false,
                     // moonset(newmoon) 26. September 2022 	07:16 	19:22 	2h -2h
                     '2022-09-26 19:22:00' => true,
                     // moonset(newmoon) 26. September 2022 	07:16 	19:22 	2h -2h
                     '2022-09-26 21:22:00' => true,
                     // moonset(newmoon) 26. September 2022 	07:16 	19:22 	2h -2h
                     '2022-09-26 21:23:00' => false,
                     // moonset(newmoon) 26. September 2022 	07:16 	19:22 	2h -2h

                 ]
                 as $dateString => $flagResult
        ) {
            $result[] = [
                'message' => 'The date-time `' . $dateString . '`(Europe/Berlin) ' .
                    (($flagResult) ? 'is' : 'is not ') . ' active in the range of two hours, which ends ' .
                    'two hours after the moonset in berlin/europe. (equal to start directly at the moonset for the following range of 2 hours) ',
                'expects' => [
                    'result' => $flagResult,
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonset',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '-120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,

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

    public static function dataProviderNextActive()
    {
        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];
        $longitude = 13.404954; // berlin
        $latitude = 52.520007; // berlin
        $result = [];
        /**
         * Beispiele für Aktive Bereichen Monaufgang(Aufg.) und Monduntergang(Unterg.) für Berlin
         * siehe https://vollmond-info.de/mondaufgang-und-monduntergang-im-september/
         * Datum                Aufg.   Unterg. Aufg.
         * 1. September 2022    12:10    21:42            21,0 %
         * 2. September 2022    13:35    22:02            30,5 %
         * 3. September 2022    15:01    22:30            41,0 %
         * 4. September 2022    16:23    23:12            52,2 %
         * 5. September 2022                    17:34   63,5 %
         * 6. September 2022            00:12    18:27    74,3 %
         * 7. September 2022            01:30    19:04    83,8 %
         * 8. September 2022            03:00    19:29    91,5 %
         * 9. September 2022            04:32    19:48    96,9 %
         * 10. September 2022        06:02    20:02    99,7 %
         * 11. September 2022        07:29    20:15    99,7 %
         * 12. September 2022        08:52    20:27    97,0 %
         * 13. September 2022        10:13    20:40    92,2 %
         * 14. September 2022        11:33    20:55    85,6 %
         * 15. September 2022        12:51    21:14    77,6 %
         * 16. September 2022        14:07    21:39    68,8 %
         * 17. September 2022        15:17    22:11    59,5 %
         * 18. September 2022        16:18    22:56    49,9 %
         * 19. September 2022        17:07    23:52    40,5 %
         * 20. September 2022        17:44        31,5 %
         * 21. September 2022    00:59    18:10        23,0 %
         * 22. September 2022    02:11    18:30        15,5 %
         * 23. September 2022    03:27    18:46        9,1 %
         * 24. September 2022    04:42    18:59        4,2 %
         * 25. September 2022    05:59    19:11        1,1 %
         * 26. September 2022    07:16    19:22        0,0 %
         * 27. September 2022    19:22    19:34        1,1 %
         * 28. September 2022    09:57    19:48        4,5 %
         * 29. September 2022    11:23    20:06        10,2 %
         * 30. September 2022    12:49    20:31        17,9 %
         *
         * add 2 hours
         * range = 2 hours
         */
        // moonrise +2h +2h
        foreach ([
                     '2022-09-01 14:09:00' => ['beginn' => '2022-09-01 14:10:00', 'end' => '2022-09-01 16:10:00'],
                     '2022-09-01 14:10:00' => ['beginn' => '2022-09-02 15:35:00', 'end' => '2022-09-02 17:35:00'],
                     '2022-09-01 16:10:00' => ['beginn' => '2022-09-02 15:35:00', 'end' => '2022-09-02 17:35:00'],
                     '2022-09-02 15:34:00' => ['beginn' => '2022-09-02 15:35:00', 'end' => '2022-09-02 17:35:00'],
                     '2022-09-02 15:35:00' => ['beginn' => '2022-09-03 17:01:00', 'end' => '2022-09-03 19:01:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        // moonrise -2h +2h
        foreach ([
                     '2022-09-01 12:09:00' => ['beginn' => '2022-09-01 12:10:00', 'end' => '2022-09-01 14:10:00'],
                     '2022-09-01 12:10:00' => ['beginn' => '2022-09-02 13:35:00', 'end' => '2022-09-02 15:35:00'],
                     '2022-09-01 14:10:00' => ['beginn' => '2022-09-02 13:35:00', 'end' => '2022-09-02 15:35:00'],
                     '2022-09-02 13:34:00' => ['beginn' => '2022-09-02 13:35:00', 'end' => '2022-09-02 15:35:00'],
                     '2022-09-02 13:35:00' => ['beginn' => '2022-09-03 15:01:00', 'end' => '2022-09-03 17:01:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '-120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        // moonrise +2h -2h
        foreach ([
                     '2022-09-01 10:09:00' => ['beginn' => '2022-09-01 10:10:00', 'end' => '2022-09-01 12:10:00'],
                     '2022-09-01 10:10:00' => ['beginn' => '2022-09-02 11:35:00', 'end' => '2022-09-02 13:35:00'],
                     '2022-09-01 12:10:00' => ['beginn' => '2022-09-02 11:35:00', 'end' => '2022-09-02 13:35:00'],
                     '2022-09-02 11:34:00' => ['beginn' => '2022-09-02 11:35:00', 'end' => '2022-09-02 13:35:00'],
                     '2022-09-02 11:35:00' => ['beginn' => '2022-09-03 13:01:00', 'end' => '2022-09-03 15:01:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '-120',
                        'durationMinutes' => '120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        // moonrise -2h -2h
        foreach ([
                     '2022-09-01 08:09:00' => ['beginn' => '2022-09-01 08:10:00', 'end' => '2022-09-01 10:10:00'],
                     '2022-09-01 08:10:00' => ['beginn' => '2022-09-02 09:35:00', 'end' => '2022-09-02 11:35:00'],
                     '2022-09-01 10:10:00' => ['beginn' => '2022-09-02 09:35:00', 'end' => '2022-09-02 11:35:00'],
                     '2022-09-02 09:34:00' => ['beginn' => '2022-09-02 09:35:00', 'end' => '2022-09-02 11:35:00'],
                     '2022-09-02 09:35:00' => ['beginn' => '2022-09-03 11:01:00', 'end' => '2022-09-03 13:01:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '-120',
                        'durationMinutes' => '-120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        //  1. September 2022 	12:10 	21:42    		21,0 %
        //  2. September 2022 	13:35 	22:02    		30,5 %
        //  3. September 2022 	15:01 	22:30    		41,0 %
        // moonset +2h +2h
        foreach ([
                     '2022-09-01 23:41:00' => ['beginn' => '2022-09-01 23:42:00', 'end' => '2022-09-02 01:42:00'],
                     '2022-09-01 23:42:00' => ['beginn' => '2022-09-03 00:02:00', 'end' => '2022-09-03 02:02:00'],
                     '2022-09-02 01:42:00' => ['beginn' => '2022-09-03 00:02:00', 'end' => '2022-09-03 02:02:00'],
                     '2022-09-03 00:01:00' => ['beginn' => '2022-09-03 00:02:00', 'end' => '2022-09-03 02:02:00'],
                     '2022-09-03 00:02:00' => ['beginn' => '2022-09-04 00:30:00', 'end' => '2022-09-04 02:30:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonset',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        //  1. September 2022 	12:10 	21:42    		21,0 %
        //  2. September 2022 	13:35 	22:02    		30,5 %
        //  3. September 2022 	15:01 	22:30    		41,0 %
        // moonset -2h +2h
        foreach ([
                     '2022-09-01 21:41:00' => ['beginn' => '2022-09-01 21:42:00', 'end' => '2022-09-01 23:42:00'],
                     '2022-09-01 21:42:00' => ['beginn' => '2022-09-02 22:02:00', 'end' => '2022-09-03 00:02:00'],
                     '2022-09-01 23:42:00' => ['beginn' => '2022-09-02 22:02:00', 'end' => '2022-09-03 00:02:00'],
                     '2022-09-02 22:01:00' => ['beginn' => '2022-09-02 22:02:00', 'end' => '2022-09-03 00:02:00'],
                     '2022-09-02 22:02:00' => ['beginn' => '2022-09-03 22:30:00', 'end' => '2022-09-04 00:30:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonset',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '-120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        //  1. September 2022 	12:10 	21:42    		21,0 %
        //  2. September 2022 	13:35 	22:02    		30,5 %
        //  3. September 2022 	15:01 	22:30    		41,0 %
        // moonset +2h -2h
        foreach ([
                     '2022-09-01 19:41:00' => ['beginn' => '2022-09-01 19:42:00', 'end' => '2022-09-01 21:42:00'],
                     '2022-09-01 19:42:00' => ['beginn' => '2022-09-02 20:02:00', 'end' => '2022-09-02 22:02:00'],
                     '2022-09-01 21:42:00' => ['beginn' => '2022-09-02 20:02:00', 'end' => '2022-09-02 22:02:00'],
                     '2022-09-02 20:01:00' => ['beginn' => '2022-09-02 20:02:00', 'end' => '2022-09-02 22:02:00'],
                     '2022-09-02 20:02:00' => ['beginn' => '2022-09-03 20:30:00', 'end' => '2022-09-03 22:30:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonset',
                        'relMinToSelectedTimerEvent' => '-120',
                        'durationMinutes' => '120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        //  1. September 2022 	12:10 	21:42    		21,0 %
        //  2. September 2022 	13:35 	22:02    		30,5 %
        //  3. September 2022 	15:01 	22:30    		41,0 %
        // moonset -2h -2h
        foreach ([
                     '2022-09-01 17:41:00' => ['beginn' => '2022-09-01 17:42:00', 'end' => '2022-09-01 19:42:00'],
                     '2022-09-01 17:42:00' => ['beginn' => '2022-09-02 18:02:00', 'end' => '2022-09-02 20:02:00'],
                     '2022-09-01 19:42:00' => ['beginn' => '2022-09-02 18:02:00', 'end' => '2022-09-02 20:02:00'],
                     '2022-09-02 18:01:00' => ['beginn' => '2022-09-02 18:02:00', 'end' => '2022-09-02 20:02:00'],
                     '2022-09-02 18:02:00' => ['beginn' => '2022-09-03 18:30:00', 'end' => '2022-09-03 20:30:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonset',
                        'relMinToSelectedTimerEvent' => '-120',
                        'durationMinutes' => '-120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
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

    public static function dataProviderPrevActive()
    {
        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];
        $longitude = 13.404954; // berlin
        $latitude = 52.520007; // berlin
        $result = [];
        /**
         * Beispiele für Aktive Bereichen Monaufgang(Aufg.) und Monduntergang(Unterg.) für Berlin
         * siehe https://vollmond-info.de/mondaufgang-und-monduntergang-im-september/
         * Datum                Aufg.   Unterg. Aufg.
         * 1. September 2022    12:10    21:42            21,0 %
         * 2. September 2022    13:35    22:02            30,5 %
         * 3. September 2022    15:01    22:30            41,0 %
         * 4. September 2022    16:23    23:12            52,2 %
         * 5. September 2022                    17:34   63,5 %
         * 6. September 2022            00:12    18:27    74,3 %
         * 7. September 2022            01:30    19:04    83,8 %
         * 8. September 2022            03:00    19:29    91,5 %
         * 9. September 2022            04:32    19:48    96,9 %
         * 10. September 2022        06:02    20:02    99,7 %
         * 11. September 2022        07:29    20:15    99,7 %
         * 12. September 2022        08:52    20:27    97,0 %
         * 13. September 2022        10:13    20:40    92,2 %
         * 14. September 2022        11:33    20:55    85,6 %
         * 15. September 2022        12:51    21:14    77,6 %
         * 16. September 2022        14:07    21:39    68,8 %
         * 17. September 2022        15:17    22:11    59,5 %
         * 18. September 2022        16:18    22:56    49,9 %
         * 19. September 2022        17:07    23:52    40,5 %
         * 20. September 2022        17:44        31,5 %
         * 21. September 2022    00:59    18:10        23,0 %
         * 22. September 2022    02:11    18:30        15,5 %
         * 23. September 2022    03:27    18:46        9,1 %
         * 24. September 2022    04:42    18:59        4,2 %
         * 25. September 2022    05:59    19:11        1,1 %
         * 26. September 2022    07:16    19:22        0,0 %
         * 27. September 2022    19:22    19:34        1,1 %
         * 28. September 2022    09:57    19:48        4,5 %
         * 29. September 2022    11:23    20:06        10,2 %
         * 30. September 2022    12:49    20:31        17,9 %
         *
         * add 2 hours
         * range = 2 hours
         */
        // moonrise +2h +2h
        foreach ([
                     //         * 1. September 2022 	12:10 	21:42    		21,0 %
                     //         * 2. September 2022 	13:35 	22:02    		30,5 %
                     //         * 3. September 2022 	15:01 	22:30    		41,0 %
                     '2022-09-03 19:02:00' => ['beginn' => '2022-09-03 17:01:00', 'end' => '2022-09-03 19:01:00'],
                     '2022-09-03 19:01:00' => ['beginn' => '2022-09-02 15:35:00', 'end' => '2022-09-02 17:35:00'],
                     '2022-09-03 17:01:00' => ['beginn' => '2022-09-02 15:35:00', 'end' => '2022-09-02 17:35:00'],
                     '2022-09-02 17:36:00' => ['beginn' => '2022-09-02 15:35:00', 'end' => '2022-09-02 17:35:00'],
                     '2022-09-02 17:35:00' => ['beginn' => '2022-09-01 14:10:00', 'end' => '2022-09-01 16:10:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        // moonrise -2h +2h
        foreach ([
                     //         * 1. September 2022 	12:10 	21:42    		21,0 %
                     //         * 2. September 2022 	13:35 	22:02    		30,5 %
                     //         * 3. September 2022 	15:01 	22:30    		41,0 %
                     '2022-09-03 17:02:00' => ['beginn' => '2022-09-03 15:01:00', 'end' => '2022-09-03 17:01:00'],
                     '2022-09-03 17:01:00' => ['beginn' => '2022-09-02 13:35:00', 'end' => '2022-09-02 15:35:00'],
                     '2022-09-03 15:01:00' => ['beginn' => '2022-09-02 13:35:00', 'end' => '2022-09-02 15:35:00'],
                     '2022-09-02 15:36:00' => ['beginn' => '2022-09-02 13:35:00', 'end' => '2022-09-02 15:35:00'],
                     '2022-09-02 15:35:00' => ['beginn' => '2022-09-01 12:10:00', 'end' => '2022-09-01 14:10:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '-120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        // moonrise +2h -2h
        foreach ([
                     //         * 1. September 2022 	12:10 	21:42    		21,0 %
                     //         * 2. September 2022 	13:35 	22:02    		30,5 %
                     //         * 3. September 2022 	15:01 	22:30    		41,0 %
                     '2022-09-03 15:02:00' => ['beginn' => '2022-09-03 13:01:00', 'end' => '2022-09-03 15:01:00'],
                     '2022-09-03 15:01:00' => ['beginn' => '2022-09-02 11:35:00', 'end' => '2022-09-02 13:35:00'],
                     '2022-09-03 13:01:00' => ['beginn' => '2022-09-02 11:35:00', 'end' => '2022-09-02 13:35:00'],
                     '2022-09-02 13:36:00' => ['beginn' => '2022-09-02 11:35:00', 'end' => '2022-09-02 13:35:00'],
                     '2022-09-02 13:35:00' => ['beginn' => '2022-09-01 10:10:00', 'end' => '2022-09-01 12:10:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '-120',
                        'durationMinutes' => '120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        // moonrise -2h -2h
        foreach ([
                     //         * 1. September 2022 	12:10 	21:42    		21,0 %
                     //         * 2. September 2022 	13:35 	22:02    		30,5 %
                     //         * 3. September 2022 	15:01 	22:30    		41,0 %
                     '2022-09-03 13:02:00' => ['beginn' => '2022-09-03 11:01:00', 'end' => '2022-09-03 13:01:00'],
                     '2022-09-03 13:01:00' => ['beginn' => '2022-09-02 09:35:00', 'end' => '2022-09-02 11:35:00'],
                     '2022-09-03 11:01:00' => ['beginn' => '2022-09-02 09:35:00', 'end' => '2022-09-02 11:35:00'],
                     '2022-09-02 11:36:00' => ['beginn' => '2022-09-02 09:35:00', 'end' => '2022-09-02 11:35:00'],
                     '2022-09-02 11:35:00' => ['beginn' => '2022-09-01 08:10:00', 'end' => '2022-09-01 10:10:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonrise',
                        'relMinToSelectedTimerEvent' => '-120',
                        'durationMinutes' => '-120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        // moonset +2h +2h
        foreach ([
                     //         * 1. September 2022 	12:10 	21:42    		21,0 %
                     //         * 2. September 2022 	13:35 	22:02    		30,5 %
                     //         * 3. September 2022 	15:01 	22:30    		41,0 %
                     '2022-09-04 02:31:00' => ['beginn' => '2022-09-04 00:30:00', 'end' => '2022-09-04 02:30:00'],
                     '2022-09-04 02:30:00' => ['beginn' => '2022-09-03 00:02:00', 'end' => '2022-09-03 02:02:00'],
                     '2022-09-04 00:30:00' => ['beginn' => '2022-09-03 00:02:00', 'end' => '2022-09-03 02:02:00'],
                     '2022-09-03 02:03:00' => ['beginn' => '2022-09-03 00:02:00', 'end' => '2022-09-03 02:02:00'],
                     '2022-09-03 02:02:00' => ['beginn' => '2022-09-01 23:42:00', 'end' => '2022-09-02 01:42:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonset',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        // moonset -2h +2h
        foreach ([
                     //         * 1. September 2022 	12:10 	21:42    		21,0 %
                     //         * 2. September 2022 	13:35 	22:02    		30,5 %
                     //         * 3. September 2022 	15:01 	22:30    		41,0 %
                     '2022-09-04 00:31:00' => ['beginn' => '2022-09-03 22:30:00', 'end' => '2022-09-04 00:30:00'],
                     '2022-09-04 00:30:00' => ['beginn' => '2022-09-02 22:02:00', 'end' => '2022-09-03 00:02:00'],
                     '2022-09-03 22:30:00' => ['beginn' => '2022-09-02 22:02:00', 'end' => '2022-09-03 00:02:00'],
                     '2022-09-03 00:03:00' => ['beginn' => '2022-09-02 22:02:00', 'end' => '2022-09-03 00:02:00'],
                     '2022-09-03 00:02:00' => ['beginn' => '2022-09-01 21:42:00', 'end' => '2022-09-01 23:42:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonset',
                        'relMinToSelectedTimerEvent' => '120',
                        'durationMinutes' => '-120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        // moonset +2h -2h
        foreach ([
                     //         * 1. September 2022 	12:10 	21:42    		21,0 %
                     //         * 2. September 2022 	13:35 	22:02    		30,5 %
                     //         * 3. September 2022 	15:01 	22:30    		41,0 %
                     '2022-09-03 22:31:00' => ['beginn' => '2022-09-03 20:30:00', 'end' => '2022-09-03 22:30:00'],
                     '2022-09-03 22:30:00' => ['beginn' => '2022-09-02 20:02:00', 'end' => '2022-09-02 22:02:00'],
                     '2022-09-03 20:30:00' => ['beginn' => '2022-09-02 20:02:00', 'end' => '2022-09-02 22:02:00'],
                     '2022-09-02 22:03:00' => ['beginn' => '2022-09-02 20:02:00', 'end' => '2022-09-02 22:02:00'],
                     '2022-09-02 22:02:00' => ['beginn' => '2022-09-01 19:42:00', 'end' => '2022-09-01 21:42:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonset',
                        'relMinToSelectedTimerEvent' => '-120',
                        'durationMinutes' => '120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
                ],
            ];
        }
        // moonset -2h -2h
        foreach ([
                     //         * 1. September 2022 	12:10 	21:42    		21,0 %
                     //         * 2. September 2022 	13:35 	22:02    		30,5 %
                     //         * 3. September 2022 	15:01 	22:30    		41,0 %
                     '2022-09-03 20:31:00' => ['beginn' => '2022-09-03 18:30:00', 'end' => '2022-09-03 20:30:00'],
                     '2022-09-03 20:30:00' => ['beginn' => '2022-09-02 18:02:00', 'end' => '2022-09-02 20:02:00'],
                     '2022-09-03 18:30:00' => ['beginn' => '2022-09-02 18:02:00', 'end' => '2022-09-02 20:02:00'],
                     '2022-09-02 20:03:00' => ['beginn' => '2022-09-02 18:02:00', 'end' => '2022-09-02 20:02:00'],
                     '2022-09-02 20:02:00' => ['beginn' => '2022-09-01 17:42:00', 'end' => '2022-09-01 19:42:00'],
                 ] as $dateString => $expection
        ) {
            $result[] = [
                'message' => 'The nextRange in this example is correctly detected, because the active Range is one meute below the next active range.',
                'expects' => [
                    'result' => [
                        'beginning' => $expection['beginn'],
                        'ending' => $expection['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonStatus' => 'moonset',
                        'relMinToSelectedTimerEvent' => '-120',
                        'durationMinutes' => '-120',
                        'latitude' => $latitude, // Variation
                        'longitude' => $longitude, // longitude Bremen 	8.8016937
                    ],
                    'general' => $general,
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
