<?php

namespace Porthd\Timer\Tests\CustomTimer;

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
use Porthd\Timer\CustomTimer\MoonphaseRelTimer;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MoonphaseRelTimerTest extends TestCase
{
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE = TimerInterface::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerInterface::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerInterface::ARG_ULTIMATE_RANGE_END;
    protected const NAME_TIMER = 'txTimerMoonphaseRel';
    protected const SOME_NOT_EMPTY_VALUE = 'some value';
    protected const ALLOWED_TIME_ZONE = 'UTC';


    /**
     * @var MoonphaseRelTimer
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
        $this->subject = new MoonphaseRelTimer();
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
        $rootPath = $_ENV['TYPO3_PATH_ROOT']; // this is the
        // projecktpath
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
            $this->assertTrue(
                (false),
                'The File-path should contain `' . TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH . '`, so that the TCA-attribute-action `onChange` will work correctly. '
            );
        } else {
            $resultPath = $rootPath . DIRECTORY_SEPARATOR . $filePath;
        }
        $flag = (!empty($resultPath)) && file_exists($resultPath);
        $this->assertTrue(
            $flag,
            'The file with the flexform content does not exist.'
        );
        $fileContent = GeneralUtility::getURL($resultPath);
        $flexArray = simplexml_load_string($fileContent);
        $this->assertTrue(
            (!(!$flexArray)),
            'The filecontent is valid xml.'
        );
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

    /**
     * @return array[]
     */
    public function dataProviderValidateGeneralByVariationArgumentsInParam()
    {
        $rest = [
            'moonPhase' => 'full_moon',
            'relMinToSelectedTimerEvent' => '2880',
            'durationMinutes' => '120',
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
                    'moonPhase' => 'full_moon',
                    'relMinToSelectedTimerEvent' => '2880',
                    'durationMinutes' => '120',
                ],
                'optional' => [

                ],
                'general' => $general,
            ],
        ];
        // unset some parameters to provoke an failing
        foreach (['moonPhase', 'relMinToSelectedTimerEvent', 'durationMinutes'] as $myUnset) {
            $item = [
                'message' => 'The test fails, because the parameter `' . $myUnset . '` is missing.(being unsetted)',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'moonPhase' => 'full_moon',
                        'relMinToSelectedTimerEvent' => '2880',
                        'durationMinutes' => '120',
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
                     'new_moon' => true,
                     'first_quarter' => true,
                     'full_moon' => true,
                     'last_quarter' => true,
                     1 => false,
                     0 => false,
                     'moon_high' => false,
                 ] as $moonphase => $myExpects) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The test for moonphase with `' . $moonphase .
                    ($myExpects ? '` is correct' : '` is NOT correct') . '.',
                [
                    'result' => $myExpects,
                ],
                [
                    'required' => [
                        'moonPhase' => $moonphase,
                        'relMinToSelectedTimerEvent' => '2880',
                        'durationMinutes' => '120',
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];
        }
        //         variation of durationMinutes
        //         '0.0'=>false, '-10.0' => false, '10.0' => false minus the same integer resolve not an integer zero
        foreach ([
                     28801 => false,
                     -28801 => false,
                     -28800 => true,
                     28800 => true,
                     '-28800' => true,
                     '28800' => true,
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
                        'moonPhase' => 'full_moon',
                        'relMinToSelectedTimerEvent' => $myMin,
                        'durationMinutes' => '120',
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];
        }

        // variation of durationMinutes
        $list = [
            28801 => false,
            -28801 => false,
            -28800 => true,
            28800 => true,
//                     '-28800' => true, // is identical to abowe
//                     '28800' => true,  // is identical to above
            '-100' => true,
            '10 ' => false,
            //
            '10' => true,
            //
            '-10.1' => false,
            '10.1' => false,
            '-10.0' => false,
            '10.0' => false,
            // 10.0 => false,  // not allowed; autoconversion of (float)10.0 to (int)10, which will overide the correct entry
            0 => false,
            '0.0' => false,
            1 => true,
            '-1' => true,
        ];
        foreach ($list as $myMin => $myExpects
        ) {
            $result[] = [
                'message' => 'The test for durationMinutes with `' . $myMin .
                    ($myExpects ? '` is correct' : '` is NOT correct') . '.',
                'expects' => [
                    'result' => $myExpects,
                ],
                'params' => [
                    'required' => [
                        'moonPhase' => 'full_moon',
                        'relMinToSelectedTimerEvent' => '2880',
                        'durationMinutes' => $myMin,
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
            'message' => 'The timezone of the parameter will be shown, because the active-part of the parameter is `0`. The value of the timezone will not be validated.',
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
            'message' => 'The timezone of the active will be shown, because the active-part of the parameter is 1. The value of the timezone will not be validated.',
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

        /**
         * https://www.fr.de/wissen/vollmond-2022-mond-neumond-naechster-wann-himmel-mondkalender-monat-mondphasen-91156836.html
         *
         * Vollmond im Januar 2022:    18. Januar 2022, 00.48 Uhr
         * Vollmond im Februar 2022:    16. Februar 2022, 17.56 Uhr
         * Vollmond im März 2022:    18. März 2022, 08.17 Uhr
         * Vollmond im April 2022:    16. April 2022, 20.55 Uhr
         * Vollmond im Mai 2022:    16. Mai 2022, 06.14 Uhr
         * Vollmond im Juni 2022:    14. Juni 2022, 13.51 Uhr (round up-Minute 13:50:31 for example)
         * Vollmond im Juli 2022:    13. Juli 2022, 20.37 Uhr
         * Vollmond im August 2022:    12. August 2022, 03.35 Uhr
         * Vollmond im September 2022:    10. September 2022, 11.59 Uhr
         * Vollmond im Oktober 2022:    09. Oktober 2022, 22.54 Uhr
         * Vollmond im November 2022:    08. November 2022, 12.02 Uhr
         * Vollmond im Dezember 2022:    08. Dezember 2022, 05.08 Uhr
         *
         * https://www.vollmond.info/de/vollmond-kalender.html
         * Dienstag, 14. Juni 2022, 13:51:48 Uhr
         */

        foreach ([
                     '2022-06-16 11:00:00' => false,
                     '2022-06-16 13:50:00' => false,
                     '2022-06-16 13:51:00' => false,
//                     '2022-06-16 13:52:00' => true, // calc:.:  	Tue Jun 14 2022 13:52:37 Europe/berlin
                     '2022-06-16 13:53:00' => true,
                     '2022-06-16 13:54:00' => true,
                     '2022-06-16 14:00:00' => true,
                     '2022-06-16 15:00:00' => true,
                     '2022-06-16 15:49:00' => true,
                     '2022-06-16 15:50:00' => true,
                     '2022-06-16 15:51:00' => true,
//                     '2022-06-16 15:52:00' => true, // calc:.:  	Tue Jun 14 2022 13:52:37 Europe/berlin vs. 14. Juni 2022, 13:51:48 Uhr
                     '2022-06-16 15:53:00' => false,
                     '2022-06-16 16:00:00' => false,

                     '2022-06-17 11:00:00' => false,
                     '2022-06-30 12:00:00' => false,
// check, if variation in minutecaused by calculation-error
//
//https://www.vollmond.info/de/vollmond-kalender/2033.html
// Montag, 13. Juni 2033, 01:19:18 Uhr + 2 days
                     '2033-06-15 01:18:00' => false,
                     '2033-06-15 01:19:00' => false,
                     '2033-06-15 01:20:00' => false,
                     '2033-06-15 01:21:00' => false,
                     '2033-06-15 01:22:00' => true,
                     '2033-06-15 01:24:00' => true,
                     '2033-06-15 01:25:00' => true,
                     '2033-06-15 02:25:00' => true,
                     '2033-06-15 03:25:00' => false,
                     //
// https://nextfullmoon.org/de/mondkalender-monat/june/2050
//    Vollmond Sonntag, 5. Juni 2050, 11:53:59 (GMT+2)
//    (UTC: Sonntag, 5. Juni 2050, 09:53:59) + 2 tage
                     '2050-06-07 09:50:00' => false,
                     '2050-06-07 10:50:00' => false,
                     '2050-06-07 10:53:00' => false,
                     '2050-06-07 10:54:00' => true,
                     // php seam to think, thatr in 2050 the summertime is not longer allowed
                     '2050-06-07 10:55:00' => true,
                     '2050-06-07 10:57:00' => true,
                     '2050-06-07 11:00:00' => true,
                     '2050-06-07 11:10:00' => true,
                     '2050-06-07 11:20:00' => true,
                     '2050-06-07 11:30:00' => true,
                     '2050-06-07 11:50:00' => true,
                     '2050-06-07 11:51:00' => true,
                     '2050-06-07 12:49:00' => true,
                     '2050-06-07 12:53:00' => true,
                     '2050-06-07 12:54:00' => false,
                     '2050-06-07 13:00:00' => false,
// https://nextfullmoon.org/de/mondkalender-monat/dezember/2075
//    Vollmond Sonntag, 22. Dezember 2075, 09:50:16 (GMT+1)
//    (UTC: Sonntag, 22. Dezember 2075, 08:50:16)
                     '2075-12-24 09:48:00' => false,
                     '2075-12-24 09:49:00' => false,
                     '2075-12-24 09:50:00' => false,
                     '2075-12-24 09:51:00' => true,
                     '2075-12-24 09:52:00' => true,
                     '2075-12-24 11:48:00' => true,
                     '2075-12-24 11:49:00' => true,
                     '2075-12-24 11:50:00' => true,
                     '2075-12-24 11:51:00' => false,
                     '2075-12-24 11:52:00' => false,
                 ]
                 as $dateString => $flagResult
        ) {
            $result[] = [
                'message' => 'The date-time  `' . $dateString . '` ' . (($flagResult) ? 'must be' : 'must not be') . ' active.',
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
                        'moonPhase' => 'full_moon',
                        'relMinToSelectedTimerEvent' => '2880',  //= 2 day rdelayto the fullmoon
                        'durationMinutes' => '120',

                        'useTimeZoneOfFrontend' => 0,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        foreach (['2075-12-24 09:50:00', '2075-12-24 09:51:00'] as $dateString) {
            $result[] = [
                'message' => 'The date-time  `' . $dateString . '` must never be active, baecause the timegamp is zero (0)r.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'moonPhase' => 'full_moon',
                        'relMinToSelectedTimerEvent' => '2880',  //= 2 day rdelayto the fullmoon
                        'durationMinutes' => 0,

                        'useTimeZoneOfFrontend' => 0,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        foreach ([
                     120 => [
                         '2075-12-24 09:50:00',
                         '2075-12-24 09:51:00',
                         '2075-12-24 11:50:00',
                         '2075-12-24 11:51:00',
                     ],
                     -120 => [
                         '2075-12-24 07:50:00',
                         '2075-12-24 07:51:00',
                         '2075-12-24 09:50:00',
                         '2075-12-24 09:51:00',
                     ],
                     14400 => [
                         '2075-12-24 09:50:00',
                         '2075-12-24 09:51:00',
                         '2076-01-03 09:50:00',
                         '2076-01-03 09:51:00',
                     ],
                     -14400 => [
                         '2075-12-14 09:50:00',
                         '2075-12-14 09:51:00',
                         '2075-12-24 09:50:00',
                         '2075-12-24 09:51:00',
                     ],
                 ] as $gap => $timeList
        ) {
            foreach ($timeList as $key => $dateString) {
                $flagResult = (!in_array($key, [0, 3]));
                $result[] = [
                    'message' => 'The date-time  `' . $dateString . '` ' . (($flagResult) ? 'must be' : 'must not be') . ' active by variation of timegap',
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
                            'moonPhase' => 'full_moon',
                            'relMinToSelectedTimerEvent' => '2880',  //= 2 day rdelayto the fullmoon
                            'durationMinutes' => $gap,

                            'useTimeZoneOfFrontend' => 0,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
            }
        }

        foreach ([
                     0 => [
                         '2075-12-22 09:50:00',
                         '2075-12-22 09:51:00',
                         '2075-12-22 11:50:00',
                         '2075-12-22 11:51:00',
                     ],
                     -180 => [
                         '2075-12-22 06:50:00',
                         '2075-12-22 06:51:00',
                         '2075-12-22 08:50:00',
                         '2075-12-22 08:51:00',
                     ],
                     1440 => [
                         '2075-12-23 09:50:00',
                         '2075-12-23 09:51:00',
                         '2075-12-23 11:50:00',
                         '2075-12-23 11:51:00',
                     ],
                     -2880 => [
                         '2075-12-20 09:50:00',
                         '2075-12-20 09:51:00',
                         '2075-12-20 11:50:00',
                         '2075-12-20 11:51:00',
                     ],
                     -14400 => [
                         '2075-12-12 09:50:00',
                         '2075-12-12 09:51:00',
                         '2075-12-12 11:50:00',
                         '2075-12-12 11:51:00',
                     ],
                     14400 => [
                         '2076-01-01 09:50:00',
                         '2076-01-01 09:51:00',
                         '2076-01-01 11:50:00',
                         '2076-01-01 11:51:00',
                     ],
                 ] as $shiftMinute => $timeList
        ) {
            foreach ($timeList as $key => $dateString) {
                $flagResult = (!in_array($key, [0, 3]));
                $result[] = [
                    'message' => 'The date-time  `' . $dateString . '` ' . (($flagResult) ? 'must be' : 'must not be') .
                        ' active by variation of distance relative to full_moon',
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
                            'moonPhase' => 'full_moon',
                            'relMinToSelectedTimerEvent' => $shiftMinute,  //= 2 day rdelayto the fullmoon
                            'durationMinutes' => '120',

                            'useTimeZoneOfFrontend' => 0,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
            }
        }

        /**
         * https://nextfullmoon.org/de/mondkalender-monat/dezember/2075
         * Vollmond Sonntag, 22. Dezember 2075, 09:50:16 (GMT+1)
         * Neumond Sonntag, 8. Dezember 2075, 00:05:12 (GMT+1)
         * Erstes Viertel Samstag, 14. Dezember 2075, 15:40:20 (GMT+1)
         * Letztes Viertel Montag, 30. Dezember 2075, 14:23:11 (GMT+1)
         */
        foreach ([
                     'new_moon' => [
                         '2075-12-08 00:05:00',
                         '2075-12-08 00:06:00',
                         '2075-12-08 02:05:00',
                         '2075-12-08 02:06:00',
                     ],
                     'first_quarter' => [
                         '2075-12-14 15:40:00',
                         '2075-12-14 15:41:00',
                         '2075-12-14 17:40:00',
                         '2075-12-14 17:41:00',
                     ],
                     'full_moon' => [
                         '2075-12-22 09:50:00',
                         '2075-12-22 09:51:00',
                         '2075-12-22 11:50:00',
                         '2075-12-22 11:51:00',
                     ],
                     'last_quarter' => [
                         '2075-12-30 14:23:00',
                         '2075-12-30 14:24:00',
                         '2075-12-30 16:23:00',
                         '2075-12-30 16:24:00',
                     ],
                 ] as $typeOfMoon => $timeList
        ) {
            foreach ($timeList as $key => $dateString) {
                $flagResult = (!in_array($key, [0, 3]));
                $result[] = [
                    'message' => 'The date-time  `' . $dateString . '` ' . (($flagResult) ? 'must be' : 'must not be') .
                        ' active for the type of moon `' . $typeOfMoon . '`.',
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
                            'moonPhase' => $typeOfMoon,
                            'relMinToSelectedTimerEvent' => '0',  //= 2 day rdelayto the fullmoon
                            'durationMinutes' => '120',

                            'useTimeZoneOfFrontend' => 0,
                            'timeZoneOfEvent' => 'Europe/Berlin',
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
//        '2022-06-16 13:51:00' => false,
//                     '2022-06-16 13:52:00' => true, // calc:.:  	Tue Jun 14 2022 13:52:37 Europe/berlin
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
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2022-06-16 13:51:00',
                    new DateTimeZone('Europe/Berlin')
                ),
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
        $result[] = [
            'message' => 'The nextRange in this example is correctly detected, because the active Time ist part of an active range. ',
            'expects' => [
                'result' => [
                    'beginning' => '2022-07-15 20:38:00',
                    // fullmoon calculated by algorithm : 13. Juli 2022, 20.37 instead of   13. Juli 2022, 20:37
                    'ending' => '2022-07-15 22:39:00',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2022-06-16 13:53:00',
                    new DateTimeZone('Europe/Berlin')
                ),
                'setting' => [
                    'moonPhase' => 'full_moon',
                    'relMinToSelectedTimerEvent' => '2880',  //= 2 day relative to the fullmoon
                    'durationMinutes' => '120',

                    'useTimeZoneOfFrontend' => 0,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];

        /**
         * https://www.fr.de/wissen/vollmond-2022-mond-neumond-naechster-wann-himmel-mondkalender-monat-mondphasen-91156836.html
         *
         * Vollmond im Januar 2022:    18. Januar 2022, 00.48 Uhr
         * Vollmond im Februar 2022:    16. Februar 2022, 17.56 Uhr
         * Vollmond im März 2022:    18. März 2022, 08.17 Uhr
         * Vollmond im April 2022:    16. April 2022, 20.55 Uhr
         * Vollmond im Mai 2022:    16. Mai 2022, 06.14 Uhr
         * Vollmond im Juni 2022:    14. Juni 2022, 13.51 Uhr (round up-Minute 13:50:31 for example)
         * Vollmond im Juli 2022:    13. Juli 2022, 20.37 Uhr
         * Vollmond im August 2022:    12. August 2022, 03.35 Uhr
         * Vollmond im September 2022:    10. September 2022, 11.59 Uhr
         * Vollmond im Oktober 2022:    09. Oktober 2022, 22.54 Uhr
         * Vollmond im November 2022:    08. November 2022, 12.02 Uhr
         * Vollmond im Dezember 2022:    08. Dezember 2022, 05.08 Uhr
         *
         * https://www.vollmond.info/de/vollmond-kalender.html
         * Dienstag, 14. Juni 2022, 13:51:48 Uhr
         */


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

    public function dataProviderPrevActive()
    {
        $result = [];
//        '2022-06-16 13:51:00' => false,
//                     '2022-06-16 13:52:00' => true, // calc:.:  	Tue Jun 14 2022 13:52:37 Europe/berlin
//                     '2022-06-16 13:53:00' => true,
        $result[] = [
            'message' => 'The prevRange in this example is correctly detected, because the startdate ist part of the an active range. ',
            'expects' => [
                'result' => [
                    'beginning' => '2022-06-16 13:52:00',
                    'ending' => '2022-06-16 15:53:00',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2022-07-15 22:38:00',
                    new DateTimeZone('Europe/Berlin')
                ),
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
        $result[] = [
            'message' => 'The prevRange in this example is correctly detected, because the startdate is one minute higher than the previous active range',
            'expects' => [
                'result' => [
                    'beginning' => '2022-07-15 20:38:00',
                    // fullmoon calculated by algorithm : 13. Juli 2022, 20.37 instead of   13. Juli 2022, 20:37
                    'ending' => '2022-07-15 22:39:00',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2022-07-15 22:41:00',
                    new DateTimeZone('Europe/Berlin')
                ),
                'setting' => [
                    'moonPhase' => 'full_moon',
                    'relMinToSelectedTimerEvent' => '2880',  //= 2 day rdelayto the fullmoon
                    'durationMinutes' => '120',

                    'useTimeZoneOfFrontend' => 0,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The prevRange in this example is correctly detected, because the startdate is one minute higher than the previous active range',
            'expects' => [
                'result' => [
                    'beginning' => '2022-07-15 20:38:00',
                    // fullmoon calculated by algorithm : 13. Juli 2022, 20.37 instead of   13. Juli 2022, 20:37
                    'ending' => '2022-07-15 22:39:00',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2022-07-15 23:41:00',
                    new DateTimeZone('Europe/Berlin')
                ),
                'setting' => [
                    'moonPhase' => 'full_moon',
                    'relMinToSelectedTimerEvent' => '2880',  //= 2 day rdelayto the fullmoon
                    'durationMinutes' => '120',

                    'useTimeZoneOfFrontend' => 0,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The prevRange in this example is correctly detected, because the startdate is one minute higher than the previous active range',
            'expects' => [
                'result' => [
                    'beginning' => '2022-07-15 20:38:00',
                    // fullmoon calculated by algorithm : 13. Juli 2022, 20.37 instead of   13. Juli 2022, 20:37
                    'ending' => '2022-07-15 22:39:00',
                    'exist' => true,
                ],
            ],
            'params' => [
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2022-07-16 00:41:00',
                    new DateTimeZone('Europe/Berlin')
                ),
                'setting' => [
                    'moonPhase' => 'full_moon',
                    'relMinToSelectedTimerEvent' => '2880',  //= 2 day rdelayto the fullmoon
                    'durationMinutes' => '120',

                    'useTimeZoneOfFrontend' => 0,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];

        /**
         * https://www.fr.de/wissen/vollmond-2022-mond-neumond-naechster-wann-himmel-mondkalender-monat-mondphasen-91156836.html
         *
         * Vollmond im Januar 2022:    18. Januar 2022, 00.48 Uhr
         * Vollmond im Februar 2022:    16. Februar 2022, 17.56 Uhr
         * Vollmond im März 2022:    18. März 2022, 08.17 Uhr
         * Vollmond im April 2022:    16. April 2022, 20.55 Uhr
         * Vollmond im Mai 2022:    16. Mai 2022, 06.14 Uhr
         * Vollmond im Juni 2022:    14. Juni 2022, 13.51 Uhr (round up-Minute 13:50:31 for example)
         * Vollmond im Juli 2022:    13. Juli 2022, 20.37 Uhr
         * Vollmond im August 2022:    12. August 2022, 03.35 Uhr
         * Vollmond im September 2022:    10. September 2022, 11.59 Uhr
         * Vollmond im Oktober 2022:    09. Oktober 2022, 22.54 Uhr
         * Vollmond im November 2022:    08. November 2022, 12.02 Uhr
         * Vollmond im Dezember 2022:    08. Dezember 2022, 05.08 Uhr
         *
         * https://www.vollmond.info/de/vollmond-kalender.html
         * Dienstag, 14. Juni 2022, 13:51:48 Uhr
         */


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
            $setting = $params['setting'];
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
