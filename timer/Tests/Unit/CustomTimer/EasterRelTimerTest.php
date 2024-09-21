<?php

declare(strict_types=1);

namespace Porthd\Timer\Tests\Unit\CustomTimer;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2022 Dr. Dieter Porth <info@mobger.de>
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

use Porthd\Timer\CustomTimer\EasterRelTimer;
use TYPO3\CMS\Core\Context\Context;
use Cassandra\Date;
use DateInterval;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EasterRelTimerTest extends TestCase
{
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE = TimerInterface::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerInterface::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerInterface::ARG_ULTIMATE_RANGE_END;
    protected const NAME_TIMER = 'txTimerEasterRel';
    protected const SOME_NOT_EMPTY_VALUE = 'some value';
    protected const ALLOWED_TIME_ZONE = 'UTC';


    /**
     * @var EasterRelTimer
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
        $this->subject = new EasterRelTimer();
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


    public static function dataProvider_isAllowedInRange()
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
    public static function dataProviderValidateGeneralByVariationArgumentsInParam()
    {
        $rest = [
            'namedDateMidnight' => 'easter', // = easter-sunday
            'relMinToSelectedTimerEvent' => 1440, // 1440 min = 1d
            'calendarUse' => 0, // = default ,
            'durationMinutes' => 120, // active time from the beginnung of the Period
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
    public static function dataProviderValidateSpeciallByVariationArgumentsInParam()
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
                    'namedDateMidnight' => 'easter', // = easter-sunday
                    'relMinToSelectedTimerEvent' => 1440, // 1440 min = 1d
                    'calendarUse' => 0, // = default ,
                    'durationMinutes' => 120, // active time from the beginnung of the Period
                ],
                'general' => $general,
            ],
        ];
        //        variation of allowed `namedDateMidnight`
        foreach ([
                     'easter',
                     'ascension',
                     'pentecost',
                     'firstadvent',
                     'christmas',
                     'rosemonday',
                     'goodfriday',
                     'towlday',
                     'stupidday',
                     'newyear', 'silvester', 'labourday',
                 ] as $dateIdentifier) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The variation of the `namedDateMidnight` is correct by using the id `' . $dateIdentifier . '`.',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'required' => [
                        'namedDateMidnight' => $dateIdentifier, // = easter-sunday
                        'relMinToSelectedTimerEvent' => 1440, // 1440 min = 1d
                        'calendarUse' => 0, // = default ,
                        'durationMinutes' => 120, // active time from the beginnung of the Period
                    ],
                    'general' => $general,
                ],
            ];
        }
        //        variation of `namedDateMidnight`
        foreach ([7, -1, -2, 'kennIchNicht'] as $dateIdentifier) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The variation of the `namedDateMidnight` is NOT correct by using the number `' . $dateIdentifier . '`.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'namedDateMidnight' => $dateIdentifier, // = easter-sunday
                        'relMinToSelectedTimerEvent' => 1440, // 1440 min = 1d
                        'calendarUse' => 0, // = default ,
                        'durationMinutes' => 120, // active time from the beginnung of the Period
                    ],
                    'general' => $general,
                ],
            ];
        }
        // variation of `relMinToSelectedTimerEvent`
        foreach ([
                     null,
                     '',
                     -475200,
                     -10000,
                     -1000,
                     '-100',
                     -10,
                     -2,
                     -1,
                     0,
                     '0',
                     1,
                     2,
                     10,
                     100,
                     100000,
                     '475200',
                 ] as $DateNumber) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The variation of the `relMinToSelectedTimerEvent` is  correct by using the number `' . $DateNumber . '`.',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'required' => [
                        'namedDateMidnight' => 'ascension',
                        'relMinToSelectedTimerEvent' => $DateNumber, // 1440 min = 1d
                        'calendarUse' => 0, // = default ,
                        'durationMinutes' => 120, // active time from the beginnung of the Period
                    ],
                    'general' => $general,
                ],
            ];
        }
        //        variation of `relMinToSelectedTimerEvent`
        foreach ([-475201, 475201, -10.1, 10.1, '-10.1', '10.1'] as $DateNumber) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The variation of the `relMinToSelectedTimerEvent` is NOT  correct by using the number `' . $DateNumber . '`.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'namedDateMidnight' => 'ascension', // = easter-sunday
                        'relMinToSelectedTimerEvent' => $DateNumber, // 1440 min = 1d
                        'calendarUse' => 0, // = default ,
                        'durationMinutes' => 120, // active time from the beginnung of the Period
                    ],
                    'general' => $general,
                ],
            ];
        }
        //        variation of `durationMinutes`
        foreach (['475200', 120, 1, -1, '-10', -475200] as $DateNumber) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The variation of the `durationMinutes` is correct by using the number `' . $DateNumber . '`.',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'required' => [
                        'namedDateMidnight' => 'ascension', // = easter-sunday
                        'relMinToSelectedTimerEvent' => 120, // 1440 min = 1d
                        'calendarUse' => 0, // = default ,
                        'durationMinutes' => $DateNumber, // active time from the beginnung of the Period
                    ],
                    'general' => $general,
                ],
            ];
        }
        //        variation of `durationMinutes`
        foreach (['', null, 0, '0', -1.2, '-10.1',] as $DateNumber) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The variation of the `durationMinutes` is NOT correct by using the number `' . $DateNumber . '`.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'namedDateMidnight' => 'ascension', // = easter-sunday
                        'relMinToSelectedTimerEvent' => 120, // 1440 min = 1d
                        'calendarUse' => 0, // = default ,
                        'durationMinutes' => $DateNumber, // active time from the beginnung of the Period
                    ],
                    'general' => $general,
                ],
            ];
        }
        //        variation of `calendarUse`
        $result[] = [
            'message' => 'The variation of the `calendarUse` is optional.',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'required' => [
                    'namedDateMidnight' => 'ascension', // = easter-sunday
                    'relMinToSelectedTimerEvent' => 120, // 1440 min = 1d
                    'durationMinutes' => 120, // active time from the beginnung of the Period
                ],
                'general' => $general,
            ],
        ];


        //        variation of `calendarUse`
        foreach (['', null, 0, 1, 2, '2', 3] as $DateNumber) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The variation of the `calendarUse` is correct by using the number `' . $DateNumber . '`.',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'required' => [
                        'namedDateMidnight' => 'ascension', // = easter-sunday
                        'relMinToSelectedTimerEvent' => 120, // 1440 min = 1d
                        'calendarUse' => $DateNumber, // = default ,
                        'durationMinutes' => 120, // active time from the beginnung of the Period
                    ],
                    'general' => $general,
                ],
            ];
        }
        //        variation of `durationMinutes`
        foreach ([-1.2, '-1.1',] as $DateNumber) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The variation of the `calendarUse` is NOT correct by using the number `' . $DateNumber . '`.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'namedDateMidnight' => 'ascension', // = easter-sunday
                        'relMinToSelectedTimerEvent' => 120, // 1440 min = 1d
                        'calendarUse' => $DateNumber, // = default ,
                        'durationMinutes' => 120, // active time from the beginnung of the Period
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
            $paramTest = array_merge($params['required'], $params['general']);
            $this->assertEquals(
                $expects['result'],
                $this->subject->validate($paramTest),
                $message
            );
        }
    }


    public static function dataProviderIsActive()
    {
        $result = [];
        //         random active
        $result[] = [
            'message' => 'The selected date is christmas between 12:00 and 14:00  `. It is active.',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-25 13:00:00', new DateTimeZone('Europe/Berlin')),
                'setting' => [
                    'namedDateMidnight' => 'christmas', // Christmas
                    'relMinToSelectedTimerEvent' => 720, //  12:00
                    'calendarUse' => 0,
                    'durationMinutes' => 120,
                    // general
                    'useTimeZoneOfFrontend' => false,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The selected date is christmas between 12:00 and 14:00  `. It is NOT active.',
            'expects' => [
                'result' => false,
            ],
            'params' => [
                'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-25 11:00:00', new DateTimeZone('Europe/Berlin')),
                'setting' => [
                    'namedDateMidnight' => 'christmas', // Christmas
                    'relMinToSelectedTimerEvent' => 840, //  14:00
                    'calendarUse' => 0,
                    'durationMinutes' => -120,
                    // general
                    'useTimeZoneOfFrontend' => false,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];

        // dates-variation see http://www.kleiner-kalender.de/rubrik/christentum.html
        foreach (['easter' => '2021-04-04', 'stupidday' => '2021-04-16', 'ascension' => '2021-05-13', 'pentecost' => '2021-05-23', 'firstadvent' => '2021-11-28',
                     'towlday' => '2021-05-25', 'christmas' => '2021-12-25', 'rosemonday' => '2022-02-28',
                     'newyear' => '2021-01-01', 'silvester' => '2020-12-31', 'labourday' => '2022-05-01',
                     'goodfriday' => '2022-04-15',] as $dateIndex => $testDate
        ) {
            $result[] = [
                'message' => 'The dateIndex `' . $dateIndex . '` will be active for the testDate `' . $testDate . '`. It is NOT active. ',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testDate . ' 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'namedDateMidnight' => $dateIndex, // Variation
                        'relMinToSelectedTimerEvent' => 840, //  14:00
                        'calendarUse' => 0,
                        'durationMinutes' => 120,
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The dateIndex `' . $dateIndex . '` will be active for the testDate `' . $testDate . '`. It is active. ',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $testDate . ' 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'namedDateMidnight' => $dateIndex, // Variation
                        'relMinToSelectedTimerEvent' => 840, //  14:00
                        'calendarUse' => 0,
                        'durationMinutes' => -120,
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }
        // see for other calculations https://www.nvf.ch/zw/ostern.asp?Jahr=2021isited 20201231
        // see remarks at https://www.php.net/manual/de/calendar.constants.php visited 20201231
        // julian calendar see https://www.nvf.ch/ostern.asp visited 20201231
        foreach ([
                     ['index' => 'easter', 'date' => '2050-04-10', 'type' => 0, 'info' => ' Check 2038-problem of php-function `easter_date`',],
                     ['index' => 'easter', 'date' => '1753-04-22', 'type' => 0, 'info' => ' Julian above 1752 in britannien too, ab 1753 gregorian',],
                     ['index' => 'easter', 'date' => '1753-04-22', 'type' => 1, 'info' => ' Julian abowe 1582 in modern europe, ab 1583 (gregorian)',],
                     ['index' => 'easter', 'date' => '1753-04-22', 'type' => 2, 'info' => ' easter uses grepor calendar for ever',],
                     ['index' => 'easter', 'date' => '1753-04-11', 'type' => 3, 'info' => ' easter uses Julian calendar for ever',],
                     ['index' => 'easter', 'date' => '1752-03-29', 'type' => 0, 'info' => ' Julian above 1752 in britannien too, ab 1753 gregorian',],
                     ['index' => 'easter', 'date' => '1752-04-02', 'type' => 1, 'info' => ' Julian abowe 1582 in modern europe, ab 1583 (gregorian)',],
                     ['index' => 'easter', 'date' => '1752-04-02', 'type' => 2, 'info' => ' easter uses grepor calendar for ever',],
                     ['index' => 'easter', 'date' => '1752-03-29', 'type' => 3, 'info' => ' easter uses Julian calendar for ever',],
                     ['index' => 'easter', 'date' => '1584-04-19', 'type' => 0, 'info' => ' Julian above 1752 in britannien too, ab 1753 gregorian',],
                     ['index' => 'easter', 'date' => '1584-04-01', 'type' => 1, 'info' => ' Julian abowe 1582 in modern europe, ab 1583 (gregorian)',],
                     ['index' => 'easter', 'date' => '1584-04-01', 'type' => 2, 'info' => ' easter uses grepor calendar for ever',],
                     ['index' => 'easter', 'date' => '1584-04-19', 'type' => 3, 'info' => ' easter uses Julian calendar for ever',],
                     ['index' => 'easter', 'date' => '1500-04-19', 'type' => 0, 'info' => ' Julian above 1752 in britannien too, ab 1753 gregorian',],
                     ['index' => 'easter', 'date' => '1500-04-19', 'type' => 1, 'info' => ' Julian abowe 1582 in modern europe, ab 1583 (gregorian)',],
                     ['index' => 'easter', 'date' => '1500-03-25', 'type' => 2, 'info' => ' easter uses grepor calendar for ever. There was no reference-calulation fond in Internet for this. ',],
                     ['index' => 'easter', 'date' => '1500-04-19', 'type' => 3, 'info' => ' easter uses Julian calendar for ever',],
                 ] as $test
        ) {
            $result[] = [
                'message' => 'The dateIndex `' . $test['index'] . '` will be active for the testDate `' . $test['date'] . '`. It is NOT active. ' . $test['info'],
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $test['date'] . ' 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'namedDateMidnight' => $test['index'], // Variation
                        'relMinToSelectedTimerEvent' => 840, //  14:00
                        'calendarUse' => $test['type'],
                        'durationMinutes' => 120,
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
            $result[] = [
                'message' => 'The dateIndex `' . $test['index'] . '` will be active for the testDate `' . $test['date'] . '`. It is NOT active. ' . $test['info'],
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $test['date'] . ' 13:00:00', new DateTimeZone('Europe/Berlin')),
                    'setting' => [
                        'namedDateMidnight' => $test['index'], // Variation
                        'relMinToSelectedTimerEvent' => 840, //  14:00
                        'calendarUse' => $test['type'],
                        'durationMinutes' => -120,
                        // general
                        'useTimeZoneOfFrontend' => true,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // see for other calculations https://www.nvf.ch/zw/ostern.asp?Jahr=2021isited 20201231
        // see remarks at https://www.php.net/manual/de/calendar.constants.php visited 20201231
        // julian calendar see https://www.nvf.ch/ostern.asp visited 20201231
        foreach ([2, 10, 120, 1440, 14400, 400000] as $duration) {
            foreach ([-1, 1] as $factor) {
                $duration *= $factor;
                $flag = ($duration > 0);
                $result[] = [
                    'message' => 'The duratioonminute `' . $duration . '` will make it active by including. at 12:01:',
                    'expects' => [
                        'result' => $flag,
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2050-04-10 12:01:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'namedDateMidnight' => 'easter', // Variation
                            'relMinToSelectedTimerEvent' => 720, // 720 min = 12:00
                            'calendarUse' => 0,
                            'durationMinutes' => $duration,
                            // general
                            'useTimeZoneOfFrontend' => true,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The duratioonminute `' . $duration . '` will make it active by including. at 11:59',
                    'expects' => [
                        'result' => (!$flag),
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2050-04-10 11:59:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'namedDateMidnight' => 'easter', // Variation
                            'relMinToSelectedTimerEvent' => 720, // 720 min = 12:00
                            'calendarUse' => 0,
                            'durationMinutes' => $duration,
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

        // Check the timer ist part of the border
        // see for other calculations https://www.nvf.ch/zw/ostern.asp?Jahr=2021isited 20201231
        // see remarks at https://www.php.net/manual/de/calendar.constants.php visited 20201231
        // julian calendar see https://www.nvf.ch/ostern.asp visited 20201231
        foreach ([-400000, -2000, 10, 1440, 400000] as $duration) {
            foreach ([-400000, -2003, 10, 1440, 400000] as $relative) {
                $diff = $duration + $relative;
                if ($diff > 0) {
                    $first = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2050-04-10 12:00:00', new DateTimeZone('Europe/Berlin'));
                    $first->add(new DateInterval('PT' . $diff . 'M'));
                } elseif ($diff < 0) {
                    $first = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2050-04-10 12:00:00', new DateTimeZone('Europe/Berlin'));
                    $first->sub(new DateInterval('PT' . abs($diff) . 'M'));
                } else {
                    $first = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2050-04-10 12:00:00', new DateTimeZone('Europe/Berlin'));
                }
                $flagSecond = (($diff < 0) && (abs($diff) > abs($relative))) || (($diff > 0) && (abs($diff) > abs($relative))) || (($diff === 0) && ($relative < $duration));
                $flagThird = (!$flagSecond);
                $result[] = [
                    'message' => 'First: The duratioonminute `' . $duration . '` and relative `' . ($relative) . '` will make it active by including. at ' . $first->format('d.m.Y H:i:s'),
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' => clone $first,
                        'setting' => [
                            'namedDateMidnight' => 'easter', // Variation
                            'relMinToSelectedTimerEvent' => ($relative + 720), // 720 min = 12:00
                            'calendarUse' => 0,
                            'durationMinutes' => $duration,
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

        // element is every timm in the border
        // see for other calculations https://www.nvf.ch/zw/ostern.asp?Jahr=2021isited 20201231
        // see remarks at https://www.php.net/manual/de/calendar.constants.php visited 20201231
        // julian calendar see https://www.nvf.ch/ostern.asp visited 20201231
        foreach ([-400000, -2000, 10, 1440, 400000] as $duration) {
            foreach ([-400000, -2003, 10, 1440, 400000] as $relative) {
                $diff = $duration + $relative;
                if ($diff > 0) {
                    $first = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2050-04-10 12:00:00', new DateTimeZone('Europe/Berlin'));
                    $first->add(new DateInterval('PT' . $diff . 'M'));
                } elseif ($diff < 0) {
                    $first = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2050-04-10 12:00:00', new DateTimeZone('Europe/Berlin'));
                    $first->sub(new DateInterval('PT' . abs($diff) . 'M'));
                } else {
                    $first = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2050-04-10 12:00:00', new DateTimeZone('Europe/Berlin'));
                }
                if ($duration < 0) {
                    $second = clone $first;
                    $second->sub(new DateInterval('PT2M'));
                    $first->add(new DateInterval('PT2M'));
                } else {
                    $second = clone $first;
                    $second->add(new DateInterval('PT2M'));
                    $first->sub(new DateInterval('PT2M'));
                }
                $result[] = [
                    'message' => 'The date is two minutes away from the active border in the active part. ' .
                        'The duratioonminute `' . $duration . '` and relative `' . ($relative) .
                        '` will make it active by including. at ' . $first->format('d.m.Y H:i:s'),
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' => $first,
                        'setting' => [
                            'namedDateMidnight' => 'easter', // Variation
                            'relMinToSelectedTimerEvent' => ($relative + 720), // 720 min = 12:00
                            'calendarUse' => 0,
                            'durationMinutes' => $duration,
                            // general
                            'useTimeZoneOfFrontend' => true,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The date is two minutes away from the active border outside of the active part. ' .
                        'The duratioonminute `' . $duration . '` and relative `' . ($relative) .
                        '` will make it active by including. at ' . $second->format('d.m.Y H:i:s'),
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' => $second,
                        'setting' => [
                            'namedDateMidnight' => 'easter', // Variation
                            'relMinToSelectedTimerEvent' => ($relative + 720), // 720 min = 12:00
                            'calendarUse' => 0,
                            'durationMinutes' => $duration,
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

        // 3. The Variation of `timeZoneOfEvent` and `useTimeZoneOfFrontend` is not relevant
        foreach ([true, false] as $useTimeZoneOfFrontend) {
            foreach (['UTC', 'Europe/Berlin', 'Australia/Eucla', 'America/Detroit', 'Pacific/Fiji', 'Indian/Chagos'] as $timezoneName) {
                $result[] = [
                    'message' => 'The date with additional Interval miss the current Time by one second. It`s NOT active. ' .
                        'It works independently to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2020-12-25 11:59:59', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'namedDateMidnight' => 'christmas', // Christmas
                            'relMinToSelectedTimerEvent' => 840, //  14:00
                            'calendarUse' => 0,
                            'durationMinutes' => -120,
                            // general
                            'useTimeZoneOfFrontend' => $useTimeZoneOfFrontend, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The date with additional Interval  will be active. It works independently to the timezone `' . $timezoneName . '`.',
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2021-04-05 13:59:59', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'namedDateMidnight' => 'easter', // easter 4.4.21
                            'relMinToSelectedTimerEvent' => 2160, //  Oster Montag 12:00
                            'calendarUse' => 0, // MNormal
                            'durationMinutes' => 120, // bis 14:00
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
                 ] as $beginnTimeString) {
            foreach ([
                         '0001-01-01 00:00:00',
                         '2020-12-27 11:00:00',
                         '2020-12-27 13:00:00',
                         '2020-12-27 18:00:00',
                         '9999-12-31 23:59:59',
                     ] as $endTimeString) {
                $check = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2021-04-05 11:59:59',
                    new DateTimeZone('Europe/Berlin')
                );
                $result[] = [
                    'message' => 'The date ' . $check->format('d.m.Y H:i:s') . ' miss the start-interval by one second. ' .
                        'It is independ to the ultimate-parameter. [' . $beginnTimeString . '//' . $endTimeString . ']',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' => clone $check,
                        'setting' => [
                            'namedDateMidnight' => 'easter', // easter 4.4.21
                            'relMinToSelectedTimerEvent' => 2160, //  Oster Montag 12:00
                            'calendarUse' => 0, // MNormal
                            'durationMinutes' => 120, // bis 14:00
                            // general
                            'useTimeZoneOfFrontend' => false, // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => $beginnTimeString,   // later than active-Zone
                            'ultimateEndingTimer' => $endTimeString,
                        ],
                    ],
                ];
                $check->add(new DateInterval('PT2H'));
                $result[] = [
                    'message' => 'The date ' . $check->format('d.m.Y H:i:s') . ' is  one second before ending. ' .
                        'It is independ to the ultimate-parameter. [' . $beginnTimeString . '//' . $endTimeString . ']',
                    'expects' => [
                        'result' => ((($beginnTimeString <= $check->format(TimerInterface::TIMER_FORMAT_DATETIME)) &&
                            ($endTimeString >= $check->format(TimerInterface::TIMER_FORMAT_DATETIME))) ?
                            true :
                            false),
                    ],
                    'params' => [
                        'value' => clone $check,
                        'setting' => [
                            'namedDateMidnight' => 'easter', // easter 4.4.21
                            'relMinToSelectedTimerEvent' => 2160, //  Oster Montag 12:00
                            'calendarUse' => 0, // MNormal
                            'durationMinutes' => 120, // bis 14:00
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin', // dynamic see below
                            'ultimateBeginningTimer' => $beginnTimeString,   // later than active-Zone
                            'ultimateEndingTimer' => $endTimeString,
                        ],
                    ],
                ];
                $check->add(new DateInterval('PT2S'));
                $result[] = [
                    'message' => 'The date ' . $check->format('d.m.Y H:i:s') . ' is  one second after ending. It`s NOT active. ' .
                        'It is independ to the ultimate-parameter. [' . $beginnTimeString . '//' . $endTimeString . ']',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' => clone $check,
                        'setting' => [
                            'namedDateMidnight' => 'easter', // easter 4.4.21
                            'relMinToSelectedTimerEvent' => 2160, //  Oster Montag 12:00
                            'calendarUse' => 0, // MNormal
                            'durationMinutes' => 120, // bis 14:00
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin', // dynamic see below
                            'ultimateBeginningTimer' => $beginnTimeString,   // later than active-Zone
                            'ultimateEndingTimer' => $endTimeString,
                        ],
                    ],
                ];
            }
        }
        return $result;
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
            'message' => 'The timezone of the parameter will be shown, because the active-part of the parameter is `1`. The value of the timezone will not be validated.',
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


    public static function dataProviderNextActive()
    {
        // Variation for starte time and Datetype
        /// Easterday cal by https://www.nvf.ch/ostern.asp
        $mapName = [
            'easter' => 'easter',
            'ascension' => 'ascending',
            'pentecost' => 'pentecost',
            'firstadvent' => '1. Advent',
            'christmas' => 'christmas',
            'rosemonday' => 'rose monday',
            'goodfriday' => 'good friday',
        ];

        $result = [];
        // rondomly Test
        $result[] = [
            'message' => 'The selected date is christmas between 12:00 and 14:00 in the next year, ' .
                'because the current time is in an active part.',
            'expects' => [
                'beginning' => '2021-12-25 12:00:00',
                'ending' => '2021-12-25 14:00:00',
                'exist' => true,
            ],
            'params' => [
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2020-12-25 13:00:00',
                    new DateTimeZone('Europe/Berlin')
                ),
                'setting' => [
                    'namedDateMidnight' => 'christmas', // Christmas
                    'relMinToSelectedTimerEvent' => 720, //  12:00
                    'calendarUse' => 0,
                    'durationMinutes' => 120,
                    // general
                    'useTimeZoneOfFrontend' => false,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The selected date is christmas between 12:00 and 14:00 in the next year, ' .
                'because the current time is before the  active part in the current year.',
            'expects' => [
                'beginning' => '2020-12-25 12:00:00',
                'ending' => '2020-12-25 14:00:00',
                'exist' => true,
            ],
            'params' => [
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2020-12-25 11:00:00',
                    new DateTimeZone('Europe/Berlin')
                ),
                'setting' => [
                    'namedDateMidnight' => 'christmas', // Christmas
                    'relMinToSelectedTimerEvent' => 840, //  14:00
                    'calendarUse' => 0,
                    'durationMinutes' => -120,
                    // general
                    'useTimeZoneOfFrontend' => false,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        foreach (
            [
                'easter' => [
                    '1400-01-18 11:00:00' => '1400-04-18',
                    '1584-04-19 11:00:00' => '1584-04-19',
                    '1752-03-29 12:00:00' => '1753-04-22',
                    '1754-04-14 13:00:00' => '1755-03-30',
                    '2020-04-12 14:00:00' => '2021-04-04',
                    '2050-04-10 18:00:00' => '2051-04-02',
                ], // easter
                'ascension' => [
                    '1400-01-27 11:00:00' => '1400-05-27',
                    '1584-05-28 11:00:00' => '1584-05-28',
                    '1752-05-07 12:00:00' => '1753-05-31',
                    '1754-05-23 13:00:00' => '1755-05-08',
                    '2020-05-21 14:00:00' => '2021-05-13',
                    '2050-05-19 18:00:00' => '2051-05-11',
                ], // ascending
                'pentecost' => [
                    '1400-01-06 11:00:00' => '1400-06-06',
                    '1584-06-07 11:00:00' => '1584-06-07',
                    '1752-05-17 12:00:00' => '1753-06-10',
                    '1754-06-02 13:00:00' => '1755-05-18',
                    '2020-05-31 14:00:00' => '2021-05-23',
                    '2050-05-29 18:00:00' => '2051-05-21',
                ], // pentecost
                'firstadvent' => [
                    '1400-01-25 11:00:00' => '1400-11-30',
                    '1584-12-02 11:00:00' => '1584-12-02',
                    '1752-12-03 12:00:00' => '1753-12-02',
                    '1754-12-01 13:00:00' => '1755-11-30',
                    '2020-11-29 14:00:00' => '2021-11-28',
                    '2050-11-27 18:00:00' => '2051-12-03',
                ], // 1. advent
                'christmas' => [
                    '1400-03-25 11:00:00' => '1400-12-25',
                    '1584-12-25 11:00:00' => '1584-12-25',
                    '1752-12-25 12:00:00' => '1753-12-25',
                    '1754-12-25 13:00:00' => '1755-12-25',
                    '2020-12-25 14:00:00' => '2021-12-25',
                    '2050-12-25 18:00:00' => '2051-12-25',
                ], // Christmas
                'rosemonday' => [
                    '1400-01-01 11:00:00' => '1400-03-01',
                    '1584-03-02 11:00:00' => '1584-03-02',
                    '1752-02-10 12:00:00' => '1753-03-05',
                    '1754-02-25 13:00:00' => '1755-02-10',
                    '2020-02-24 14:00:00' => '2021-02-15',
                    '2050-02-21 18:00:00' => '2051-02-13',
                ], // rose monday
                'goodfriday' => [
                    '1400-01-16 11:00:00' => '1400-04-16',
                    '1584-04-17 11:00:00' => '1584-04-17',
                    '1752-03-27 12:00:00' => '1753-04-20',
                    '1754-04-12 13:00:00' => '1755-03-28',
                    '2020-04-12 14:00:00' => '2021-04-02',
                    '2050-04-08 18:00:00' => '2051-03-31',
                ], // good friday
            ] as $namedDate => $list
        ) {
            foreach ($list as $testDate => $expectDate) {
                $result[] = [
                    'message' => 'The nextRange for ' . $mapName[$namedDate] . ' is correctly detected for the Startdate `' . $testDate . '`. ',
                    'expects' => [
                        'beginning' => $expectDate . ' 12:00:00',
                        'ending' => $expectDate . ' 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            $testDate,
                            new DateTimeZone('Europe/Berlin')
                        ), // Variation
                        'setting' => [
                            'namedDateMidnight' => $namedDate, // variation
                            'relMinToSelectedTimerEvent' => 720, //  12:00
                            'calendarUse' => 0,
                            'durationMinutes' => 120,
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

        // Variation for duration with systematic variation of datekey and corrosponding date
        foreach ([-430000, -43000, -400, -4, 4, 40, 4000, 430000] as $duration) {
            foreach ([
                         'easter' => '2020-04-12',
                         'ascension' => '2020-05-21',
                         'pentecost' => '2020-05-31',
                         'firstadvent' => '2020-11-29',
                         'christmas' => '2020-12-25',
                         'rosemonday' => '2020-02-24',
                         'goodfriday' => '2020-04-10',
                     ] as $dateKey => $dateString
            ) {
                $expectEaster = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $dateString . ' 12:00:00',
                    new DateTimeZone('Europe/Berlin')
                );
                if ($duration > 0) {
                    $easterStart = clone $expectEaster;
                    $easterEnd = clone $expectEaster;
                    $easterEnd->add(new DateInterval('PT' . abs($duration) . 'M'));
                } else {
                    $easterEnd = clone $expectEaster;
                    $easterStart = clone $expectEaster;
                    $easterStart->sub(new DateInterval('PT' . abs($duration) . 'M'));
                }
                $check = clone $easterStart;
                $check->sub(new DateInterval('PT1M'));
                $result[] = [
                    'message' => 'The nextRange for duration at time `' . $check->format(TimerInterface::TIMER_FORMAT_DATETIME) . '` is okay with the easter-day-Parameter.',
                    'expects' => [
                        'beginning' => $easterStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'ending' => $easterEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => clone $check, // 1 minute before border
                        'setting' => [
                            'namedDateMidnight' => $dateKey, // Variation
                            'relMinToSelectedTimerEvent' => 720, //  12:00
                            'calendarUse' => 0,
                            'durationMinutes' => $duration, // Variation
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

        // Variation of duration and relToMin
        foreach ([-430000, -400, -4, 40, 430000] as $relToInMin) {
            foreach ([-430001, -43001, -401, 5, 41, 430001] as $duration) {
                foreach ([
                             'easter' => '2020-04-12',
                             'ascension' => '2020-05-21',
                             'pentecost' => '2020-05-31',
                             'firstadvent' => '2020-11-29',
                             'christmas' => '2020-12-25',
                             'rosemonday' => '2020-02-24',
                             'goodfriday' => '2020-04-10',
                         ] as $dateKey => $dateString) {
                    $expectEaster = date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString . ' 12:00:00',
                        new DateTimeZone('Europe/Berlin')
                    );
                    if ($relToInMin > 0) {
                        $expectEaster->add(new DateInterval(('PT' . $relToInMin . 'M')));
                    } else {
                        $expectEaster->sub(new DateInterval(('PT' . abs($relToInMin) . 'M')));
                    }
                    if ($duration > 0) {
                        $easterStart = clone $expectEaster;
                        $easterEnd = clone $expectEaster;
                        $easterEnd->add(new DateInterval('PT' . abs($duration) . 'M'));
                    } else {
                        $easterEnd = clone $expectEaster;
                        $easterStart = clone $expectEaster;
                        $easterStart->sub(new DateInterval('PT' . abs($duration) . 'M'));
                    }
                    $check = clone $easterEnd;
                    $check->add(new DateInterval('PT1M'));
                    $result[] = [
                        'message' => 'The nextRange for duration `' . $duration .
                            '` at time `' . $check->format(TimerInterface::TIMER_FORMAT_DATETIME) . '` is okay with the variation of the relative-gap `' .
                            $relToInMin . '` and with the date-type-parameter.',
                        'expects' => [
                            'beginning' => $easterStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                            'ending' => $easterEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                            'exist' => true,
                        ],
                        'params' => [
                            'value' => clone $check, // 1 minute before border
                            'setting' => [
                                'namedDateMidnight' => $dateKey, // Variation
                                'relMinToSelectedTimerEvent' => $relToInMin, //  Variation
                                'calendarUse' => 0,
                                'durationMinutes' => $duration, // Variation
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
        }

        // Variation of duration and relToMin
        $myMapName = [
            'easter' => 'easter',
            'ascension' => 'ascending',
            'pentecost' => 'pentecost',
            'firstadvent' => '1. Advent',
            'christmas' => 'christmas',
            'rosemonday' => 'rose monday',
            'goodfriday' => 'good friday',
        ];

        // see https://www.php.net/manual/de/calendar.constants.php
        // calculation of dates for ranges with of easter related days with https://www.nvf.ch/zw/ostern.asp and https://www.nvf.ch/ostern.asp
        // Method 0 easter with gregorian calendar until 1753; 1752 and below with julian calendar
        // Methods 1 eastger greagor until 15833; 1552 and below with julian calendar
        // Methode 2 easter ever gregorian calendar
        // Methode 3 easter ever julian calendar
        // the first two items in '2'=> list were manually calculated
        foreach ([
                     '0' => [
                         'easter' => '1400-04-18',
                         'ascension' => '1582-05-24',
                         'pentecost' => '1583-05-19',
                         'firstadvent' => '1752-11-29',
                         'christmas' => '1752-12-25',
                         'rosemonday' => '1752-02-10',
                         'goodfriday' => '2020-04-10',
                     ],
                     '1' => [
                         'easter' => '1400-04-18',
                         'ascension' => '1582-05-24',
                         'pentecost' => '1583-05-29',
                         'firstadvent' => '1752-12-03',
                         'christmas' => '1752-12-25',
                         'rosemonday' => '1752-02-11',
                         'goodfriday' => '2020-04-10',
                     ],
                     '2' => [
                         'easter' => '1400-04-20',
                         'ascension' => '1582-05-27',
                         'pentecost' => '1583-05-29',
                         'firstadvent' => '1752-12-03',
                         'christmas' => '1752-12-25',
                         'rosemonday' => '1752-02-11',
                         'goodfriday' => '2020-04-10',
                     ],
                     '3' => [
                         'easter' => '1400-04-18',
                         'ascension' => '1582-05-24',
                         'pentecost' => '1583-05-19',
                         'firstadvent' => '1752-11-29',
                         'christmas' => '1752-12-25',
                         'rosemonday' => '1752-02-10',
                         'goodfriday' => '2020-04-04',
                     ],
                     'null' => [
                         'easter' => '1400-04-18',
                         'ascension' => '1582-05-24',
                         'pentecost' => '1583-05-19',
                         'firstadvent' => '1752-11-29',
                         'christmas' => '1752-12-25',
                         'rosemonday' => '1752-02-10',
                         'goodfriday' => '2020-04-10',
                     ],
                 ] as $method => $list) {
            foreach ($list as $dateKey => $dateString) {
                $start = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $dateString . ' 12:00:00',
                    new DateTimeZone('Europe/Berlin')
                );
                $end = clone $start;
                $end->add(new DateInterval('PT120M'));
                // below  the estimated start-border
                $check = clone $start;
                $check->sub(new DateInterval('PT1M'));
                $myItem = [
                    'message' => 'The nextRange for variationof  method `' . print_r($method, true) .
                        '` at time `' . $check->format(TimerInterface::TIMER_FORMAT_DATETIME) . '` with definition of date `' . $myMapName[$dateKey] . '` .',
                    'expects' => [
                        'beginning' => $start->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'ending' => $end->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => clone $check, // 1 minute before border
                        'setting' => [
                            'namedDateMidnight' => $dateKey, // Variation
                            'relMinToSelectedTimerEvent' => 720, //  12:00
                            'calendarUse' => $method, // Variation
                            'durationMinutes' => 120,
                            // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                if ($method === null) {
                    unset($myItem['params']['setting']['calendarUse']);  // method set method to zero, if the parameter is missing
                }
                $result[] = $myItem;
            }
        }


        // okay 20210109

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
                $result[] = [
                    'message' => 'The nextRange is correctly detected. It works independently to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'beginning' => '2021-12-25 12:00:00',
                        'ending' => '2021-12-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-25 13:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'christmas', // Christmas
                            'relMinToSelectedTimerEvent' => 720, //  12:00
                            'calendarUse' => 0,
                            'durationMinutes' => 120,
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
                        'beginning' => '2020-12-25 12:00:00',
                        'ending' => '2020-12-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-25 11:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'christmas', // Christmas
                            'relMinToSelectedTimerEvent' => 840, //  14:00
                            'calendarUse' => 0,
                            'durationMinutes' => -120,
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
                     '2020-12-25 11:00:00',
                     '2020-12-25 12:00:00',
                     '2020-12-25 13:00:00',
                     '2020-12-25 14:00:00',
                     '2020-12-25 18:00:00',
                     '2021-12-25 11:00:00',
                     '2021-12-25 12:00:00',
                     '2021-12-25 13:00:00',
                     '2021-12-25 14:00:00',
                     '2021-12-25 18:00:00',
                     '9999-12-31 23:59:59',
                 ] as $timeString) {
            if ($timeString >= '2021-12-25 14:00:00') {
                $result[] = [
                    'message' => 'the testtime ist part of an active range. The nextRange is correctly detected for the `2020-12-25 13:00:00` with the ultimate ending `' .
                        $timeString . '`.',
                    'expects' => [
                        'beginning' => '2021-12-25 12:00:00',
                        'ending' => '2021-12-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-25 13:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'christmas', // Christmas
                            'relMinToSelectedTimerEvent' => 720, //  12:00
                            'calendarUse' => 0,
                            'durationMinutes' => 120,
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '2020-12-25 11:00:00',
                            'ultimateEndingTimer' => $timeString, // Variation
                        ],
                    ],
                ];
            }
            if ($timeString <= '2020-12-25 11:00:00') {
                $result[] = [
                    'message' => 'The tsttime is not part of an active Range. The nextRange is correctly detected for the `2020-12-25 11:00:00` with the beginning `' .
                        $timeString . '`.',
                    'expects' => [
                        'beginning' => '2020-12-25 12:00:00',
                        'ending' => '2020-12-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-25 11:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'christmas', // Christmas
                            'relMinToSelectedTimerEvent' => 840, //  14:00
                            'calendarUse' => 0,
                            'durationMinutes' => -120,
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
            $diffBeginObj = $result->getBeginning()->diff(date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $expects['beginning']
            ));
            $diffEndObj = $result->getEnding()->diff(date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $expects['ending']
            ));
            $diffBegin = (int)$diffBeginObj->format('%i');
            $diffEnd = (int)$diffEndObj->format('%i');
            $flag = (
                ($result->getBeginning()->format(TimerInterface::TIMER_FORMAT_DATETIME) === $expects['beginning']) ||
                (abs($diffBegin) <= 60) // The second paert is addexd to prevend errors because of the missing hour on summertime // see comment an the end of the code
            );
            $flag = $flag && (($result->getEnding()->format(TimerInterface::TIMER_FORMAT_DATETIME) === $expects['ending']) ||
                    (abs($diffEnd) <= 60)); // The second paert is addexd to prevend errors because of the missing hour on summertime // see comment an the end of the code
            $flag = $flag && ($result->hasResultExist() === $expects['exist']);
            $this->assertTrue(
                ($flag),
                'nextActive: ' . $message . "\nExpected: : " . print_r($expects, true)
            );
        }
    }


    public static function dataProviderPrevActive()
    {
        $result = [];
        // rondomly Test
        $result[] = [
            'message' => 'The selected date is christmas between 12:00 and 14:00 in the prev year, ' .
                'because the current time is in an active part.',
            'expects' => [
                'beginning' => '2021-12-25 12:00:00',
                'ending' => '2021-12-25 14:00:00',
                'exist' => true,
            ],
            'params' => [
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2022-12-25 11:00:00',
                    new DateTimeZone('Europe/Berlin')
                ),
                'setting' => [
                    'namedDateMidnight' => 'christmas', // Christmas
                    'relMinToSelectedTimerEvent' => 720, //  12:00
                    'calendarUse' => 0,
                    'durationMinutes' => 120,
                    // general
                    'useTimeZoneOfFrontend' => false,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];
        $result[] = [
            'message' => 'The selected date is christmas between 12:00 and 14:00 in the prev year, ' .
                'because the current time is before the  active part in the current year.',
            'expects' => [
                'beginning' => '2020-12-25 12:00:00',
                'ending' => '2020-12-25 14:00:00',
                'exist' => true,
            ],
            'params' => [
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2020-12-25 15:00:00',
                    new DateTimeZone('Europe/Berlin')
                ),
                'setting' => [
                    'namedDateMidnight' => 'christmas', // Christmas
                    'relMinToSelectedTimerEvent' => 840, //  14:00
                    'calendarUse' => 0,
                    'durationMinutes' => -120,
                    // general
                    'useTimeZoneOfFrontend' => false,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];

        // Variation for starte time and Datetype
        /// Easterday cal by https://www.nvf.ch/ostern.asp
        $mapName = [
            'easter' => 'easter',
            'ascension' => 'ascending',
            'pentecost' => 'pentecost',
            'firstadvent' => '1. Advent',
            'christmas' => 'christmas',
            'rosemonday' => 'rose mondey',
            'goodfriday' => 'good friday',
        ];
        foreach (
            [
                'easter' => [
                    '1400-10-18 15:00:00' => '1400-04-18',
                    '1584-04-19 15:00:00' => '1584-04-19',
                    '1753-04-22 12:00:00' => '1752-03-29',
                    '1755-03-30 13:00:00' => '1754-04-14',
                    '2021-04-04 14:00:00' => '2020-04-12',
                    '2051-04-02 11:00:00' => '2050-04-10',
                ], // easter
                'ascension' => [
                    '1400-10-27 11:00:00' => '1400-05-27',
                    '1584-05-28 15:00:00' => '1584-05-28',
                    '1753-05-31 12:00:00' => '1752-05-07',
                    '1755-05-08 13:00:00' => '1754-05-23',
                    '2021-05-13 14:00:00' => '2020-05-21',
                    '2051-05-11 11:00:00' => '2050-05-19',
                ], // ascending
                'pentecost' => [
                    '1400-10-06 11:00:00' => '1400-06-06',
                    '1584-06-07 15:00:00' => '1584-06-07',
                    '1753-06-10 12:00:00' => '1752-05-17',
                    '1755-05-18 13:00:00' => '1754-06-02',
                    '2021-05-23 14:00:00' => '2020-05-31',
                    '2051-05-21 11:00:00' => '2050-05-29',
                ], // pentecost
                'firstadvent' => [
                    '1400-12-25 11:00:00' => '1400-11-30',
                    '1584-12-02 15:00:00' => '1584-12-02',
                    '1753-12-02 12:00:00' => '1752-12-03',
                    '1755-11-30 13:00:00' => '1754-12-01',
                    '2021-11-28 14:00:00' => '2020-11-29',
                    '2051-12-03 11:00:00' => '2050-11-27',
                ], // 1. advent
                'christmas' => [
                    '1400-12-31 11:00:00' => '1400-12-25',
                    '1584-12-25 18:00:00' => '1584-12-25',
                    '1753-12-25 12:00:00' => '1752-12-25',
                    '1755-12-25 13:00:00' => '1754-12-25',
                    '2021-12-25 14:00:00' => '2020-12-25',
                    '2051-12-25 11:00:00' => '2050-12-25',
                ], // Christmas
                'rosemonday' => [
                    '1400-04-01 11:00:00' => '1400-03-01',
                    '1584-03-02 15:00:00' => '1584-03-02',
                    '1753-03-05 12:00:00' => '1752-02-10',
                    '1755-02-10 13:00:00' => '1754-02-25',
                    '2021-02-15 14:00:00' => '2020-02-24',
                    '2051-02-13 03:00:00' => '2050-02-21',
                ], // rose monday
                'goodfriday' => [
                    '1400-05-16 11:00:00' => '1400-04-16',
                    '1584-04-17 15:00:00' => '1584-04-17',
                    '1753-04-20 12:00:00' => '1752-03-27',
                    '1755-03-28 13:00:00' => '1754-04-12',
                    '2021-04-02 14:00:00' => '2020-04-12',
                    '2051-03-31 18:00:00' => '2050-04-08',
                ], // good friday
            ] as $namedDate => $list
        ) {
            foreach ($list as $testDate => $expectDate) {
                $result[] = [
                    'message' => 'The prevRange for ' . $mapName[$namedDate] . ' is correctly detected for the Startdate `' . $testDate . '`. ',
                    'expects' => [
                        'beginning' => $expectDate . ' 12:00:00',
                        'ending' => $expectDate . ' 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            $testDate,
                            new DateTimeZone('Europe/Berlin')
                        ), // Variation
                        'setting' => [
                            'namedDateMidnight' => $namedDate, // variation
                            'relMinToSelectedTimerEvent' => 720, //  12:00
                            'calendarUse' => 0,
                            'durationMinutes' => 120,
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


        // Variation for duration with systematic variation of datekey and corrosponding date
        foreach ([-430000, -43000, -400, -4, 4, 40, 4000, 430000] as $duration) {
            foreach ([
                         'easter' => '2020-04-12',
                         'ascension' => '2020-05-21',
                         'pentecost' => '2020-05-31',
                         'firstadvent' => '2020-11-29',
                         'christmas' => '2020-12-25',
                         'rosemonday' => '2020-02-24',
                         'goodfriday' => '2020-04-10',
                     ] as $dateKey => $dateString) {
                $expectEaster = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $dateString . ' 12:00:00',
                    new DateTimeZone('Europe/Berlin')
                );
                if ($duration > 0) {
                    $easterStart = clone $expectEaster;
                    $easterEnd = clone $expectEaster;
                    $easterEnd->add(new DateInterval('PT' . abs($duration) . 'M'));
                } else {
                    $easterEnd = clone $expectEaster;
                    $easterStart = clone $expectEaster;
                    $easterStart->sub(new DateInterval('PT' . abs($duration) . 'M'));
                }
                $check = clone $easterStart;
                $check->sub(new DateInterval('PT1M'));
                $result[] = [
                    'message' => 'The prevRange for duration at time `' . $check->format(TimerInterface::TIMER_FORMAT_DATETIME) . '` is okay with the easter-day-Parameter.',
                    'expects' => [
                        'beginning' => $easterStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'ending' => $easterEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => clone $check, // 1 minute before border
                        'setting' => [
                            'namedDateMidnight' => $dateKey, // Variation
                            'relMinToSelectedTimerEvent' => 720, //  12:00
                            'calendarUse' => 0,
                            'durationMinutes' => $duration, // Variation
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

        // Variation of duration and relToMin
        foreach ([-430000, -400, -4, 40, 430000] as $relToInMin) {
            foreach ([-430001, -43001, -401, 5, 41, 430001] as $duration) {
                foreach ([
                             'easter' => '2020-04-12',
                             'ascension' => '2020-05-21',
                             'pentecost' => '2020-05-31',
                             'firstadvent' => '2020-11-29',
                             'christmas' => '2020-12-25',
                             'rosemonday' => '2020-02-24',
                             'goodfriday' => '2020-04-10',
                         ] as $dateKey => $dateString) {
                    $expectEaster = date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $dateString . ' 12:00:00',
                        new DateTimeZone('Europe/Berlin')
                    );
                    if ($relToInMin > 0) {
                        $expectEaster->add(new DateInterval(('PT' . $relToInMin . 'M')));
                    } else {
                        $expectEaster->sub(new DateInterval(('PT' . abs($relToInMin) . 'M')));
                    }
                    if ($duration > 0) {
                        $easterStart = clone $expectEaster;
                        $easterEnd = clone $expectEaster;
                        $easterEnd->add(new DateInterval('PT' . abs($duration) . 'M'));
                    } else {
                        $easterEnd = clone $expectEaster;
                        $easterStart = clone $expectEaster;
                        $easterStart->sub(new DateInterval('PT' . abs($duration) . 'M'));
                    }
                    $check = clone $easterEnd;
                    $check->add(new DateInterval('PT1M'));
                    $result[] = [
                        'message' => 'The prevRange for duration `' . $duration .
                            '` at time `' . $check->format(TimerInterface::TIMER_FORMAT_DATETIME) . '` is okay with the variation of the relative-gap `' .
                            $relToInMin . '` and with the date-type-parameter.',
                        'expects' => [
                            'beginning' => $easterStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                            'ending' => $easterEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                            'exist' => true,
                        ],
                        'params' => [
                            'value' => clone $check, // 1 minute before border
                            'setting' => [
                                'namedDateMidnight' => $dateKey, // Variation
                                'relMinToSelectedTimerEvent' => $relToInMin, //  Variation
                                'calendarUse' => 0,
                                'durationMinutes' => $duration, // Variation
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
        }

        // Variation of duration and relToMin
        $myMapName = [
            'easter' => 'easter',
            'ascension' => 'ascending',
            'pentecost' => 'pentecost',
            'firstadvent' => '1. Advent',
            'christmas' => 'christmas',
            'rosemonday' => 'rose monday',
            'goodfriday' => 'good friday',
        ];
        // see https://www.php.net/manual/de/calendar.constants.php
        // cal of easter related days with https://www.nvf.ch/zw/ostern.asp and https://www.nvf.ch/ostern.asp
        // Method 0 easter with gregorian calendar until 1753; 1752 and below with julian calendar
        // Methods 1 eastger greagor until 15833; 1552 and below with julian calendar
        // Methode 2 easter ever gregorian calendar
        // Methode 3 easter ever julian calendar
        foreach ([
                     '0' => [
                         'easter' => '1400-04-18',
                         'ascension' => '1582-05-24',
                         'pentecost' => '1583-05-19',
                         'firstadvent' => '1752-11-29',
                         'christmas' => '1752-12-25',
                         'rosemonday' => '1752-02-10',
                         'goodfriday' => '2020-04-10',
                     ],
                     '1' => [
                         'easter' => '1400-04-18',
                         'ascension' => '1582-05-24',
                         'pentecost' => '1583-05-29',
                         'firstadvent' => '1752-12-03',
                         'christmas' => '1752-12-25',
                         'rosemonday' => '1752-02-11',
                         'goodfriday' => '2020-04-10',
                     ],
                     '2' => [
                         'easter' => '1400-04-20',
                         'ascension' => '1582-05-27',
                         'pentecost' => '1583-05-29',
                         'firstadvent' => '1752-12-03',
                         'christmas' => '1752-12-25',
                         'rosemonday' => '1752-02-11',
                         'goodfriday' => '2020-04-10',
                     ],
                     '3' => [
                         'easter' => '1400-04-18',
                         'ascension' => '1582-05-24',
                         'pentecost' => '1583-05-19',
                         'firstadvent' => '1752-11-29',
                         'christmas' => '1752-12-25',
                         'rosemonday' => '1752-02-10',
                         'goodfriday' => '2020-04-04',
                     ],
                     'null' => [
                         'easter' => '1400-04-18',
                         'ascension' => '1582-05-24',
                         'pentecost' => '1583-05-19',
                         'firstadvent' => '1752-11-29',
                         'christmas' => '1752-12-25',
                         'rosemonday' => '1752-02-10',
                         'goodfriday' => '2020-04-10',
                     ],
                 ] as $method => $list) {
            foreach ($list as $dateKey => $dateString) {
                $start = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $dateString . ' 12:00:00',
                    new DateTimeZone('Europe/Berlin')
                );
                $end = clone $start;
                $end->add(new DateInterval('PT120M'));
                // above the estimated end-border
                $check = clone $end;
                $check->add(new DateInterval('PT1M'));
                $myItem = [
                    'message' => 'The nextRange for variationof  method `' . print_r($method, true) .
                        '` at time `' . $check->format(TimerInterface::TIMER_FORMAT_DATETIME) . '` with definition of date `' . $myMapName[$dateKey] . '` .',
                    'expects' => [
                        'beginning' => $start->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'ending' => $end->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => clone $check, // 1 minute before border
                        'setting' => [
                            'namedDateMidnight' => $dateKey, // Variation
                            'relMinToSelectedTimerEvent' => 720, //  12:00
                            'calendarUse' => $method, // Variation
                            'durationMinutes' => 120,
                            // general
                            'useTimeZoneOfFrontend' => 'true', // Variation
                            'timeZoneOfEvent' => 'Europe/Berlin',  // static se  below
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                if ($method === null) {
                    unset($myItem['params']['setting']['calendarUse']);  // method set method to zero, if the parameter is missing
                }
                $result[] = $myItem;
            }
        }


        // okay 20210110
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
                $result[] = [
                    'message' => 'The prevRange is correctly detected. It works independently to the timezone `' . $timezoneName . '`. ',
                    'expects' => [
                        'beginning' => '2019-12-25 12:00:00',
                        'ending' => '2019-12-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-25 13:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'christmas', // Christmas
                            'relMinToSelectedTimerEvent' => 720, //  12:00
                            'calendarUse' => 0,
                            'durationMinutes' => 120,
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
                        'beginning' => '2020-12-25 12:00:00',
                        'ending' => '2020-12-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-25 15:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'christmas', // Christmas
                            'relMinToSelectedTimerEvent' => 840, //  14:00
                            'calendarUse' => 0,
                            'durationMinutes' => -120,
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
                     '2019-12-25 11:00:00',
                     '2019-12-25 14:00:00',
                     '2019-12-25 18:00:00',
                     '2020-12-25 11:00:00',
                     '2020-12-25 13:00:00',
                     '2020-12-25 18:00:00',
                     '9999-12-31 23:59:59',
                 ] as $timeString) {
            if ($timeString >= '2019-12-25 14:00:00') {
                $result[] = [
                    'message' => 'The prevRange is correctly detected with the endinglimit `' . $timeString .
                        '`. The testtime is part of an active range. ',
                    'expects' => [
                        'beginning' => '2019-12-25 12:00:00',
                        'ending' => '2019-12-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-25 13:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'christmas', // Christmas
                            'relMinToSelectedTimerEvent' => 720, //  12:00
                            'calendarUse' => 0,
                            'durationMinutes' => 120,
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '2019-12-25 12:00:00',
                            'ultimateEndingTimer' => $timeString, // Variation
                        ],
                    ],
                ];
            }
            if ($timeString <= '2019-12-25 12:00:00') {
                $result[] = [
                    'message' => 'The prevRange is correctly detected with the endinglimit `' . $timeString .
                        '`. The testtime is part of an active range. ',
                    'expects' => [
                        'beginning' => '2020-12-25 12:00:00',
                        'ending' => '2020-12-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2020-12-25 15:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'christmas', // Christmas
                            'relMinToSelectedTimerEvent' => 840, //  14:00
                            'calendarUse' => 0,
                            'durationMinutes' => -120,
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
            $diffBeginObj = $result->getBeginning()->diff(date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $expects['beginning']
            ));
            $diffEndObj = $result->getEnding()->diff(date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $expects['ending']
            ));
            $diffBegin = (int)$diffBeginObj->format('%i');
            $diffEnd = (int)$diffEndObj->format('%i');
            $flag = (
                ($result->getBeginning()->format(TimerInterface::TIMER_FORMAT_DATETIME) === $expects['beginning']) ||
                (abs($diffBegin) <= 60) // The second paert is addexd to prevend errors because of the missing hour on summertime // see comment an the end of the code
            );
            $flag = $flag && (($result->getEnding()->format(TimerInterface::TIMER_FORMAT_DATETIME) === $expects['ending']) ||
                    (abs($diffEnd) <= 60)); // The second paert is addexd to prevend errors because of the missing hour on summertime // see comment an the end of the code
            $flag = $flag && ($result->hasResultExist() === $expects['exist']);
            $this->assertTrue(
                ($flag),
                'prev
                Active: ' . $message . "\nExpected: : " . print_r($expects, true)
            );
        }
    }
}
