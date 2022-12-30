<?php

namespace Porthd\Timer\CustomTimer;

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

use DateInterval;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Interfaces\TimerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class JewishHolidayTimerTest extends TestCase
{
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE = TimerInterface::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerInterface::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerInterface::ARG_ULTIMATE_RANGE_END;
    protected const NAME_TIMER = 'txTimerJewishHoliday';
    protected const SOME_NOT_EMPTY_VALUE = 'some value';
    protected const ALLOWED_TIME_ZONE = 'UTC';


    /**
     * @var JewishHolidayTimer
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
        $this->subject = new JewishHolidayTimer();
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
            'namedDateMidnight' => 'YomKippur', // = YomKippur
            'relMinToSelectedTimerEvent' => 1440, // 1440 min = 1d
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
            'message' => 'The test is correct, because all needed arguments are used.',
            [
                'result' => true,
            ],
            [
                'required' => [
                    'namedDateMidnight' => 'YomKippur', // = YomKippur 2022 = 4.10
                    'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d; 720 = 12:00 AM
                    'durationMinutes' => 120, // active time from the beginnung of the Period
                ],
                'general' => $general,
            ],
        ];
        //        Variation of allowed `namedDateMidnight`
        foreach ([
                     'ErevRoshHashanah',
                     'RoshHashanahI',
                     'RoshHashanahII',
                     'TzomGedaliah',
                     'ErevYomKippur',
                     'YomKippur',
                     'ErevSukkot',
                     'SukkotI',
                     'SukkotII_DiasporOnly',
                     'HolHamoedSukkot_inIsraelInDiaspora',
                     'HolHamoedSukkot_inIsrael',
                     'HolHamoedSukkot_inDiaspora',
                     'HoshanaRabbah',
                     'SheminiAzeret_inDiaspora',
                     'SheminiAzeretSimchatTorah_inIsrael',
                     'SimchatTorah_inDiaspora',
                     'IsruChagTishri_inIsrael',
                     'IsruChagTishri_inDiaspora',
                     'HanukkahI',
                     'HanukkahII',
                     'HanukkahIII',
                     'HanukkahIV',
                     'HanukkahV',
                     'HanukkahVI',
                     'HanukkahVII',
                     'HanukkahVIII',
                     'TzomTevet',
                     'TuBShevat',
                     'PurimKatan',
                     'ShushanPurimKatan',
                     'TaAnithEsther',
                     'Purim',
                     'ShushanPurim',
                     'ShushanPurimPost',
                     'ShushanPurimPur',
                     'ShabbatHagadol',
                     'ErevPesach',
                     'PesachI',
                     'PesachII_DiasporOnly',
                     'HolHamoedPesach_inIsraelInDiaspora',
                     'HolHamoedPesach_inIsrael',
                     'HolHamoedPesach_inDiaspora',
                     'PesachVII',
                     'PesachVIII_DiasporOnly',
                     'IsruChagNisan_inIsrael',
                     'IsruChagNisan_inDiaspora',
                     'YomHashoah',
                     'YomHazikaron',
                     'YomHaAtzmaut',
                     'PesachSheini',
                     'LagBOmer',
                     'YomYerushalayim',
                     'ErevShavuot',
                     'ShavuotI',
                     'ShavuotII_DiasporOnly',
                     'IsruChagSivan_inIsrael',
                     'IsruChagSivan_inDiaspora',
                     'TzomTammuz',
                     'TishaBAv',
                     'TuBAv',
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
                        'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d; 720 = 12:00 AM
                        'durationMinutes' => 120, // active time from the beginnung of the Period
                    ],
                    'general' => $general,
                ],
            ];
        }
        //        Variation of unknown `namedDateMidnight`
        foreach ([
                     null,
                     7,
                     -1,
                     -2,
                     'kennIchNicht',
                     'easter',
                     'ascension',
                     'pentecost',
                     'firstadvent',
                     'christmas',
                     'rosemonday',
                     'goodfriday',
                     'towlday',
                 ] as $dateIdentifier) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The variation of the `namedDateMidnight` is NOT correct by using the number `' . $dateIdentifier . '`.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'namedDateMidnight' => $dateIdentifier, // = easter-sunday
                        'relMinToSelectedTimerEvent' => 1440, // 1440 min = 1d; 720 = 12:00 AM
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
                        'namedDateMidnight' => 'YomKippur', // = YomKippur
                        'relMinToSelectedTimerEvent' => $DateNumber, // 1440 min = 1d
                        'durationMinutes' => 120, // active time from the beginnung of the Period
                    ],
                    'general' => $general,
                ],
            ];
        }
        //        not allowed variation of `relMinToSelectedTimerEvent`
        foreach ([-475201, 475201, -10.1, 10.1, '-10.1', '10.1'] as $DateNumber) {
            /* test allowed minimal structure */
            $result[] = [
                'message' => 'The variation of the `relMinToSelectedTimerEvent` is NOT  correct by using the number `' . $DateNumber . '`.',
                'expects' => [
                    'result' => false,
                ],
                'params' => [
                    'required' => [
                        'namedDateMidnight' => 'YomKippur', // = YomKippur
                        'relMinToSelectedTimerEvent' => $DateNumber, // 1440 min = 1d
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
                        'namedDateMidnight' => 'YomKippur', // = YomKippur
                        'relMinToSelectedTimerEvent' => 1440, // 1440 min = 1d
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
                        'namedDateMidnight' => 'YomKippur', // = YomKippur
                        'relMinToSelectedTimerEvent' => 1440, // 1440 min = 1d
                        'durationMinutes' => $DateNumber, // active time from the beginnung of the Period
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

    public function dataProviderIsActive()
    {
        $result = [];
        // random active
        $result[] = [
            'message' => 'The selected date is Yom Kippur 2022 between 12:00 and 14:00. It is active.',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'value' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2022-10-05 13:00:00',
                    new DateTimeZone('Europe/Berlin')
                ),
                'setting' => [
                    'namedDateMidnight' => 'YomKippur', // = YomKippur
                    'relMinToSelectedTimerEvent' => 720, // 720 min = 12:00 AM
                    'durationMinutes' => 120, // active time from the beginnung of the Period
                    // general
                    'useTimeZoneOfFrontend' => false,
                    'timeZoneOfEvent' => 'Europe/Berlin',
                    'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                    'ultimateEndingTimer' => '9999-12-31 23:59:59',
                ],
            ],
        ];

        foreach ([2, 10, 120, 1440, 14400, 400000] as $duration) {
            foreach ([-1, 1] as $factor) {
                $duration *= $factor;
                $flag = ($duration > 0);
                $helpDate =  date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2022-10-05 12:00:00', new DateTimeZone('Europe/Berlin'));

                $result[] = [
                    'message' => 'The minutes of duration `' . $duration . '` will make it active. The border `'.
                        $helpDate->format('Y-m-d H:i:s').'` is part of the active range.',
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2022-10-05 12:00:00', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 720 min = 12:00 AM
                            'durationMinutes' => $duration, // active time from the beginnung of the Period
                            // general
                            'useTimeZoneOfFrontend' => true,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $otherBorder = clone $helpDate;
                $failBorder = clone $helpDate;
                if ($flag) {
                    $failBorder->sub(new DateInterval('PT1S'));
                    $otherBorder->add(new DateInterval('PT'.abs($duration).'M'));
                    $failOtherBorder = clone $otherBorder;
                    $failOtherBorder->add(new DateInterval('PT1S'));
                } else {
                    $failBorder->add(new DateInterval('PT1S'));
                    $otherBorder->sub(new DateInterval('PT'.abs($duration).'M'));
                    $failOtherBorder = clone $otherBorder;
                    $failOtherBorder->sub(new DateInterval('PT1S'));
                }
                $result[] = [
                    'message' => 'The minutes of duration `' . $duration . '` will make it active. The border `'.
                        $otherBorder->format('Y-m-d H:i:s').'` is part of the active range.',
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' =>$otherBorder,
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 720 min = 12:00 AM
                            'durationMinutes' => $duration, // active time from the beginnung of the Period
                            // general
                            'useTimeZoneOfFrontend' => true,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The minutes of duration `' . $duration . '` will be detected as inactive. The border `'.
                        $failBorder->format('Y-m-d H:i:s').'` is NOT part of the active range.',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' =>$failBorder,
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 720 min = 12:00 AM
                            'durationMinutes' => $duration, // active time from the beginnung of the Period
                            // general
                            'useTimeZoneOfFrontend' => true,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The minutes of duration `' . $duration . '` will be detected as inactive. The border `'.
                        $failOtherBorder->format('Y-m-d H:i:s').'` is NOT part of the active range.',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' =>$failOtherBorder,
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 720 min = 12:00 AM
                            'durationMinutes' => $duration, // active time from the beginnung of the Period
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

        foreach ([0, 2, 10, 120, 1440, 14400, 400000] as $relToYomKippur) {
            foreach ([-1, 1] as $factor) {
                $relToYomKippur *= $factor;
                $flag = ($relToYomKippur > 0);
                $helpDate =  date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2022-10-05 00:00:00', new DateTimeZone('Europe/Berlin'));
                if ($flag) {
                    $helpDate->add(new DateInterval('PT'.abs($relToYomKippur).'M'));
                } else {
                    $helpDate->sub(new DateInterval('PT'.abs($relToYomKippur).'M'));
                }
                $result[] = [
                    'message' => 'The minutes `' . $relToYomKippur . '` relative to 0:00:00 Yom Kippur will shift the two hours of active range. The border `'.
                        $helpDate->format('Y-m-d H:i:s').'` is part of the active range.',
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' => clone $helpDate,
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => $relToYomKippur, // 720 min = 12:00 AM
                            'durationMinutes' => 120, // active time from the beginnung of the Period
                            // general
                            'useTimeZoneOfFrontend' => true,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $otherBorder = clone $helpDate;
                $otherBorder->add(new DateInterval('PT120M'));
                $failBorder = clone $helpDate;
                $failBorder->sub(new DateInterval('PT1S'));
                $failOtherBorder = clone $helpDate;
                $failOtherBorder->add(new DateInterval('PT120M1S'));
                $result[] = [
                    'message' => 'The minutes `' . $relToYomKippur . '` relative to 0:00:00 Yom Kippur will shift the two hours of active range. The border `'.
                        $otherBorder->format('Y-m-d H:i:s').'` is part of the active range.',
                    'expects' => [
                        'result' => true,
                    ],
                    'params' => [
                        'value' =>$otherBorder,
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => $relToYomKippur, // 720 min = 12:00 AM
                            'durationMinutes' => 120, // active time from the beginnung of the Period
                            // general
                            'useTimeZoneOfFrontend' => true,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The minutes `' . $relToYomKippur . '` relative to 0:00:00 Yom Kippur will shift the two hours of active range. The border `'.
                        $failBorder->format('Y-m-d H:i:s').'` is NOT part of the active range.',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' =>$failBorder,
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 720 min = 12:00 AM
                            'durationMinutes' => $relToYomKippur, // active time from the beginnung of the Period
                            // general
                            'useTimeZoneOfFrontend' => true,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                            'ultimateEndingTimer' => '9999-12-31 23:59:59',
                        ],
                    ],
                ];
                $result[] = [
                    'message' => 'The minutes `' . $relToYomKippur . '` relative to 0:00:00 Yom Kippur will shift the two hours of active range. The border `'.
                        $failOtherBorder->format('Y-m-d H:i:s').'` is NOT part of the active range.',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'value' =>$failOtherBorder,
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 720 min = 12:00 AM
                            'durationMinutes' => $relToYomKippur, // active time from the beginnung of the Period
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
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2022-10-05 11:59:59', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
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
                        'value' => date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, '2022-10-05 13:59:59', new DateTimeZone('Europe/Berlin')),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
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
                     '2022-10-05 11:00:00',
                     '2022-10-05 13:00:00',
                     '2022-10-05 18:00:00',
                     '9999-12-31 23:59:59',
                 ] as $beginnTimeString) {
            foreach ([
                         '0001-01-01 00:00:00',
                         '2022-10-05 11:00:00',
                         '2022-10-05 13:00:00',
                         '2022-10-05 18:00:00',
                         '9999-12-31 23:59:59',
                     ] as $endTimeString) {
                $check = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2022-10-05 11:59:59',
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
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
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
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
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
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
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
        // Variation for starte time and Datetype
        // https://www.chabad.org/holidays/JewishNewYear/template_cdo/aid/671893/jewish/When-Is-Yom-Kippur-in-2022-2023-2024-and-2025.htm
        // list of Yom Kippur
        //    5.10.2022
        //    24.09.2023
        //        12.10.2024
        //    2.10.2025
        $result = [];
        // rondomly Test
        foreach (['2023-10-05 12:00:00','2023-10-05 14:00:00','2023-01-01 00:00:00','2023-09-24 11:59:59',] as $checkDate) {
            $result[] = [
                'message' => 'The selected date for the next range is yom kippur in 2023 between 12:00 and 14:00. ' .
                    'It will work, because the current time is between the ranges or at least part of the previous active range.',
                'expects' => [
                    'beginning' => '2023-09-25 12:00:00',
                    'ending' => '2023-09-25 14:00:00',
                    'exist' => true,
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $checkDate,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'namedDateMidnight' => 'YomKippur', // = YomKippur
                        'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                        'durationMinutes' => 120, // active time from the beginnung of the Period
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // Variation for duration with systematic variation of datekey and corrosponding date
        foreach ([-430000, -43000, -400, -4, 4, 40, 4000, 430000] as $duration) {
            $expectYomKippur = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                '2023-09-25 12:00:00',
                new DateTimeZone('Europe/Berlin')
            );
            if ($duration > 0) {
                $yomKippurStart = clone $expectYomKippur;
                $yomKippurEnd = clone $expectYomKippur;
                $yomKippurEnd->add(new DateInterval('PT' . abs($duration) . 'M'));
            } else {
                $yomKippurEnd = clone $expectYomKippur;
                $yomKippurStart = clone $expectYomKippur;
                $yomKippurStart->sub(new DateInterval('PT' . abs($duration) . 'M'));
            }
            $check = clone $yomKippurStart;
            $check->sub(new DateInterval('PT1S'));
            $result[] = [
                'message' => 'The nextRange for duration at time `' . $check->format(TimerInterface::TIMER_FORMAT_DATETIME) .
                    '` is okay with the yom-kippur-day-Parameter.',
                'expects' => [
                    'beginning' => $yomKippurStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                    'ending' => $yomKippurEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                    'exist' => true,
                ],
                'params' => [
                    'value' => clone $check, // 1 minute before border
                    'setting' => [
                        'namedDateMidnight' => 'YomKippur', // Variation
                        'relMinToSelectedTimerEvent' => 720, //  12:00
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

        // Variation for duration with systematic variation of datekey and corrosponding date
        foreach ([-430000, -43000, -400, -4, 0, 4, 40, 4000, 430000] as $minRelToHoliday) {
            $expectYomKippur = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                '2023-09-25 12:00:00',
                new DateTimeZone('Europe/Berlin')
            );
            $check2 = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                '2022-10-05 12:00:00',
                new DateTimeZone('Europe/Berlin')
            );
            $check3 = clone $check2;
            $check3->add(new DateInterval('PT120M'));
            ;
            if ($minRelToHoliday > 0) {
                $expectYomKippur->add(new DateInterval('PT'.abs($minRelToHoliday).'M'));
                $check2->add(new DateInterval('PT'.abs($minRelToHoliday).'M'));
                $check3->add(new DateInterval('PT'.abs($minRelToHoliday).'M'));
            } elseif ($minRelToHoliday === 0) {
                // nothing to do
            } else {
                $expectYomKippur->sub(new DateInterval('PT'.abs($minRelToHoliday).'M'));
                $check2->sub(new DateInterval('PT'.abs($minRelToHoliday).'M'));
                $check3->sub(new DateInterval('PT'.abs($minRelToHoliday).'M'));
            }
            $yomKippurStart = clone $expectYomKippur;
            $yomKippurEnd = clone $expectYomKippur;
            $yomKippurEnd->add(new DateInterval('PT120M'));

            $check = clone $yomKippurStart;
            $check->sub(new DateInterval('PT1S'));
            foreach ([$check, $check2, $check3, ] as $testDate) {
                $result[] = [
                    'message' => 'The nextRange for relativ-minuntes-to-event at time `' .
                        $testDate->format(TimerInterface::TIMER_FORMAT_DATETIME) .
                        '` is okay with the yom-kippur-day-Parameter.',
                    'expects' => [
                        'beginning' => $yomKippurStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'ending' => $yomKippurEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => clone $testDate, // 1 minute before border
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // Variation
                            'relMinToSelectedTimerEvent' => $minRelToHoliday, //  12:00
                            'durationMinutes' => 120, // Variation
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
                        'beginning' => '2023-09-25 12:00:00',
                        'ending' => '2023-09-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2022-10-05 12:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
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
                        'beginning' => '2022-10-05 12:00:00',
                        'ending' => '2022-10-05 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2022-10-05 11:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
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
                     '2022-10-05 11:00:00',
                     '2022-10-05 12:00:00',
                     '2022-10-05 13:00:00',
                     '2022-10-05 14:00:00',
                     '2022-10-05 18:00:00',
                     '2023-09-25 11:00:00',
                     '2023-09-25 12:00:00',
                     '2023-09-25 13:00:00',
                     '2023-09-25 14:00:00',
                     '2023-09-25 18:00:00',
                     '9999-12-31 23:59:59',
                 ] as $timeString) {
            if ($timeString >= '2023-09-25 14:00:00') {
                $result[] = [
                    'message' => 'the testtime is part of an active range. The nextRange is correctly detected for the '.
                        '`'.'2022-10-05 13:00:00'.'` with the ultimate ending `' .
                        $timeString . '`.',
                    'expects' => [
                        'beginning' => '2023-09-25 12:00:00',
                        'ending' => '2023-09-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2022-10-05 13:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '2022-10-05 13:00:00',
                            'ultimateEndingTimer' => $timeString, // Variation
                        ],
                    ],
                ];
            }
            if ($timeString <= '2022-10-05 13:00:00') {
                $result[] = [
                    'message' => 'The testtime is not part of an active Range. The nextRange is correctly detected for the '.
                        '`2022-10-05 13:00:00` with the beginning `' .
                        $timeString . '`.',
                    'expects' => [
                        'beginning' => '2023-09-25 12:00:00',
                        'ending' => '2023-09-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2022-10-05 13:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => $timeString, // Variation
                            'ultimateEndingTimer' => '2023-09-25 14:00:00',
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


    public function dataProviderPrevActive()
    {
        // Variation for starte time and Datetype
        // https://www.chabad.org/holidays/JewishNewYear/template_cdo/aid/671893/jewish/When-Is-Yom-Kippur-in-2022-2023-2024-and-2025.htm
        // list of Yom Kippur
        //    5.10.2022
        //    24.09.2023
        //        12.10.2024
        //    2.10.2025
        $result = [];
        // rondomly Test
        foreach (['2024-10-12 12:00:00','2024-10-12 14:00:00','2023-12-31 00:00:00','2023-09-24 14:00:01',] as $checkDate) {
            $result[] = [
                'message' => 'The selected date will lead to the range between 12:00 and 14:00 of the yom kippur-day in 2023. ' .
                    'It will work, because  the current time is between that and the next range or at least part of the next range.',
                'expects' => [
                    'beginning' => '2023-09-25 12:00:00',
                    'ending' => '2023-09-25 14:00:00',
                    'exist' => true,
                ],
                'params' => [
                    'value' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $checkDate,
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'setting' => [
                        'namedDateMidnight' => 'YomKippur', // = YomKippur
                        'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                        'durationMinutes' => 120, // active time from the beginnung of the Period
                        // general
                        'useTimeZoneOfFrontend' => false,
                        'timeZoneOfEvent' => 'Europe/Berlin',
                        'ultimateBeginningTimer' => '0001-01-01 00:00:00',
                        'ultimateEndingTimer' => '9999-12-31 23:59:59',
                    ],
                ],
            ];
        }

        // Variation for duration with systematic variation of datekey and corrosponding date
        foreach ([-430000, -43000, -400, -4, 4, 40, 4000, 430000] as $duration) {
            $expectYomKippur = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                '2023-09-25 12:00:00',
                new DateTimeZone('Europe/Berlin')
            );
            if ($duration > 0) {
                $yomKippurStart = clone $expectYomKippur;
                $yomKippurEnd = clone $expectYomKippur;
                $yomKippurEnd->add(new DateInterval('PT' . abs($duration) . 'M'));
            } else {
                $yomKippurEnd = clone $expectYomKippur;
                $yomKippurStart = clone $expectYomKippur;
                $yomKippurStart->sub(new DateInterval('PT' . abs($duration) . 'M'));
            }
            $check = clone $yomKippurEnd;
            $check->add(new DateInterval('PT1S'));
            $result[] = [
                'message' => 'The prevRange for duration at time `' . $check->format(TimerInterface::TIMER_FORMAT_DATETIME) .
                    '` is okay with the yom-kippur-day-Parameter.',
                'expects' => [
                    'beginning' => $yomKippurStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                    'ending' => $yomKippurEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                    'exist' => true,
                ],
                'params' => [
                    'value' => clone $check, // 1 minute before border
                    'setting' => [
                        'namedDateMidnight' => 'YomKippur', // Variation
                        'relMinToSelectedTimerEvent' => 720, //  12:00
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

        // Variation for duration with systematic variation of datekey and corrosponding date
        foreach ([-430000, -43000, -400, -4, 0, 4, 40, 4000, 430000] as $minRelToHoliday) {
            $expectYomKippur = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                '2023-09-25 12:00:00',
                new DateTimeZone('Europe/Berlin')
            );
            $check2 = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                '2024-10-12 12:00:00',
                new DateTimeZone('Europe/Berlin')
            );
            $check3 = clone $check2;
            $check3->add(new DateInterval('PT120M'));
            if ($minRelToHoliday > 0) {
                $expectYomKippur->add(new DateInterval('PT'.abs($minRelToHoliday).'M'));
                $check2->add(new DateInterval('PT'.abs($minRelToHoliday).'M'));
                $check3->add(new DateInterval('PT'.abs($minRelToHoliday).'M'));
            } elseif ($minRelToHoliday === 0) {
                // nothing to do
            } else {
                $expectYomKippur->sub(new DateInterval('PT'.abs($minRelToHoliday).'M'));
                $check2->sub(new DateInterval('PT'.abs($minRelToHoliday).'M'));
                $check3->sub(new DateInterval('PT'.abs($minRelToHoliday).'M'));
            }
            $yomKippurStart = clone $expectYomKippur;
            $yomKippurEnd = clone $expectYomKippur;
            $yomKippurEnd->add(new DateInterval('PT120M'));

            $check = clone $yomKippurEnd;
            $check->add(new DateInterval('PT1S'));
            foreach ([$check, $check2, $check3, ] as $testDate) {
                $result[] = [
                    'message' => 'The prevRange for relativ-minuntes-to-event at time `' . $testDate->format(TimerInterface::TIMER_FORMAT_DATETIME) . '` is okay with the yom-kippur-day-Parameter.',
                    'expects' => [
                        'beginning' => $yomKippurStart->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'ending' => $yomKippurEnd->format(TimerInterface::TIMER_FORMAT_DATETIME),
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => clone $testDate, // 1 minute before border
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // Variation
                            'relMinToSelectedTimerEvent' => $minRelToHoliday, //  12:00
                            'durationMinutes' => 120, // Variation
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
                        'beginning' => '2023-09-25 12:00:00',
                        'ending' => '2023-09-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2024-10-12 13:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
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
                        'beginning' => '2022-10-05 12:00:00',
                        'ending' => '2022-10-05 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2024-10-12 13:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
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
                     '2022-10-05 11:00:00',
                     '2022-10-05 12:00:00',
                     '2022-10-05 13:00:00',
                     '2022-10-05 14:00:00',
                     '2022-10-05 18:00:00',
                     '2023-09-25 11:00:00',
                     '2023-09-25 12:00:00',
                     '2023-09-25 13:00:00',
                     '2023-09-25 14:00:00',
                     '2023-09-25 18:00:00',
                     '9999-12-31 23:59:59',
                 ] as $timeString) {
            if ($timeString >= '2024-10-12 13:00:00') {
                $result[] = [
                    'message' => 'the testtime is part of an active range. The prevRange is correctly detected for the '.
                        '`'.'2024-10-12 13:00:00'.'` with the ultimate ending `' .
                        $timeString . '`.',
                    'expects' => [
                        'beginning' => '2023-09-25 12:00:00',
                        'ending' => '2023-09-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2024-10-12 13:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => '2023-09-25 12:00:00',
                            'ultimateEndingTimer' => $timeString, // Variation
                        ],
                    ],
                ];
            }
            if ($timeString <= '2023-09-25 12:00:00') {
                $result[] = [
                    'message' => 'The testtime is not part of an active Range. The prevRange is correctly detected for the '.
                        '`2024-10-12 13:00:00` with the beginning `' .
                        $timeString . '`.',
                    'expects' => [
                        'beginning' => '2023-09-25 12:00:00',
                        'ending' => '2023-09-25 14:00:00',
                        'exist' => true,
                    ],
                    'params' => [
                        'value' => date_create_from_format(
                            TimerInterface::TIMER_FORMAT_DATETIME,
                            '2024-10-12 13:00:00',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'setting' => [
                            'namedDateMidnight' => 'YomKippur', // = YomKippur
                            'relMinToSelectedTimerEvent' => 720, // 1440 min = 1d
                            'durationMinutes' => 120, // active time from the beginnung of the Period
                            // general
                            'useTimeZoneOfFrontend' => false,
                            'timeZoneOfEvent' => 'Europe/Berlin',
                            'ultimateBeginningTimer' => $timeString, // Variation
                            'ultimateEndingTimer' => '2024-10-12 13:00:00',
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
