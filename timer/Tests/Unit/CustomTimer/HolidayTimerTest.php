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

use DateInterval;
use DateTime;
use DateTimeZone;
use Porthd\Timer\CustomTimer\DailyTimer;
use Porthd\Timer\CustomTimer\DatePeriodTimer;
use Porthd\Timer\CustomTimer\DefaultTimer;
use Porthd\Timer\CustomTimer\EasterRelTimer;
use Porthd\Timer\CustomTimer\HolidayTimer;
use Porthd\Timer\CustomTimer\MoonphaseRelTimer;
use Porthd\Timer\CustomTimer\MoonriseRelTimer;
use Porthd\Timer\CustomTimer\PeriodListTimer;
use Porthd\Timer\CustomTimer\RangeListTimer;
use Porthd\Timer\CustomTimer\SunriseRelTimer;
use Porthd\Timer\CustomTimer\WeekdayInMonthTimer;
use Porthd\Timer\CustomTimer\WeekdaylyTimer;
use Porthd\Timer\Services\HolidaycalendarService;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Repository\ListingRepository;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\ConfigurationUtility;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HolidayTimerTest extends TestCase
{
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE = TimerInterface::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerInterface::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerInterface::ARG_ULTIMATE_RANGE_END;
    protected const NAME_TIMER = 'txTimerHoliday';
    protected const SOME_NOT_EMPTY_VALUE = 'some value';
    protected const ALLOWED_TIME_ZONE = 'UTC';


    /**
     * @var PeriodListTimer
     */
    protected $subject = null;

    protected function simulatePartOfGlobalsTypo3Array()
    {

        $GLOBALS['TYPO3_CONF_VARS'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['timer'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['timer']['changeListOfTimezones'] = [];

        $listOfTimerClasses = [
            DailyTimer::class, // => 1
            DatePeriodTimer::class, // => 2
            DefaultTimer::class, // => 4
            EasterRelTimer::class,
            MoonphaseRelTimer::class,
            MoonriseRelTimer::class,
            PeriodListTimer::class,
            RangeListTimer::class,
            SunriseRelTimer::class,
            WeekdayInMonthTimer::class,
            WeekdaylyTimer::class,
            HolidayTimer::class,
        ];
        $activateBitWiseTimerClasses = 4095;
        ConfigurationUtility::addExtLocalconfTimerAdding(
            $activateBitWiseTimerClasses,
            $listOfTimerClasses
        );
        $GLOBALS['EXEC_TIME'] = 1609088941; // 12/27/2020 @ 5:09pm (UTC)
    }

    protected function resolveGlobalsTypo3Array()
    {
        // unset($GLOBALS);
        $GLOBALS['TYPO3_CONF_VARS'] = [];
        $GLOBALS['EXEC_TIME'] = 0;
    }

    protected function initializeEnvoiroment(): void
    {
        $testToProjectPath = getenv('TYPO3_TEST_TEST_TO_PROJECT_PATH') ?: '../../../../../';
        $projectPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $testToProjectPath . '');
        $publicPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $testToProjectPath . 'web/');
        $varPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $testToProjectPath . 'var/');
        $configPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $testToProjectPath . 'config/');
        $currentScript = __DIR__;
        $os = 'WINDOWS';

        Environment::initialize(
            new ApplicationContext('Testing'),
            false,
            true,
            $projectPath,
            $publicPath,
            $varPath,
            $configPath,
            $currentScript,
            $os
        );
    }


    protected function initializeCachingConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][TimerConst::CACHE_IDENT_TIMER_YAMLLIST] ??= [];
        if (!array_key_exists('frontend', $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][TimerConst::CACHE_IDENT_TIMER_YAMLLIST])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][TimerConst::CACHE_IDENT_TIMER_YAMLLIST]['frontend'] = VariableFrontend::class;
        }
        $myCacheInstance = GeneralUtility::makeInstance(CacheManager::class);
        $myCacheInstance->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeEnvoiroment();
        $this->initializeCachingConfiguration();
        ////        $myCacheInstance->flushCaches(); // flush caches to create them~
        ////        $myCacheInstance->getCache(TimerConst::CACHE_IDENT_TIMER_YAMLLIST);
        $this->simulatePartOfGlobalsTypo3Array();
        /** @var ListingRepository $listingRepository */
        $yamlFileLoader = new YamlFileLoader();
        $holiydaycalendarService = new HolidaycalendarService();
        $this->subject = new HolidayTimer($holiydaycalendarService, $yamlFileLoader);

        ExtensionManagementUtility::setPackageManager(new PackageManager(new DependencyOrderingService()));
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

    public static function dataProviderTest()
    {
        return [
            ['result' => true,],
        ];
    }

    /**
     * the ultimate green test
     * @dataProvider dataProviderTest
     * @test
     */
    public function checkIfIAmGreenDataProvider($result)
    {
        $this->assertEquals((true), ($result), 'I should an evergreen, but I am incomplete! :-)');
    }

    /**
     * @test
     */
    public function selfName()
    {
        $result = $this->subject::selfName();
        $expected = self::NAME_TIMER;
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
        $result = array_values($result);
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
            'message' => 'The timezone of the active frontend will be shown, because the active-part of the parameter is 1. The value of the timezone will not be validated.',
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
     *  !!!!!! Attention
     *  !!!!!! This test are specific for a ddev/docker enviroment, because of reduce realPath by '/var/www/html/'
     *  !!!!!! It may fail in other enviroment
     *
     * @return array[]
     *
     */
    public static function dataProviderValidateGeneralByVariationArgumentsInParam()
    {
        $rest = [
            'relMinToSelectedTimerEvent' => '720',
            'durationMinutes' => '120',
            'timerHolidaysFilePath' => '/..' . substr(realpath(__DIR__ . '/../../../../timer/Documentation/Examples_HolidayTimer.yml'), strlen('var/www/html/',)),
            'timerHolidaysFalRelation' => '12,13',
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
        //         Variation for useTimeZoneOfFrontend
        foreach ([
                     [null, false],
                     [false, true],
                     ['false', true],
                     [new DateTime(), false],
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
     * !!!!!! Attention
     * !!!!!! This test are specific for a ddev/docker enviroment, because of reduce realPath by '/var/www/html/'
     * !!!!!! It may fail in other enviroment
     *
     * @return array
     */
    public static function dataProviderValidateSpeciallByVariationArgumentsInParam()
    {
        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];
        $rest = [
            'relMinToSelectedTimerEvent' => '720',
            'durationMinutes' => '120',
            'timerHolidaysFilePath' => '/..' . substr(realpath(__DIR__ . '/../../../../timer/Documentation/Examples_HolidayTimer.yml'), strlen('var/www/html/',)),
            'timerHolidaysFalRelation' => '12,13',
        ];
        $result = [];
        /* test allowed minimal structure */
        $result[] = [
            'message' => 'The test randomly is correct.',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'required' => $rest,
                'general' => $general,
            ],
        ];
        $helpRest = $rest;
        // Doofie = silly dump
        $helpRest['timerHolidaysFilePath'] = 'pathForDoofies';
        $result[] = [
            'message' => 'Introduce a wrong path and get the estimated result: false',
            'expects' => [
                'result' => false,
            ],
            'params' => [
                'required' => $helpRest,
                'general' => $general,
            ],
        ];
        // unset
        foreach ([
                     'relMinToSelectedTimerEvent' => false,
                     'durationMinutes' => false,
                     'timerHolidaysFilePath' => true,
                     'timerHolidaysFalRelation' => true,
                 ] as $key => $expected) {
            $helpRest = [];
            foreach ($rest as $index => $value) {
                if ($index !== $key) {
                    $helpRest[$index] = $value;
                }
            }
            $result[] = [
                'message' => 'Remove one Item ' . $key . ' and get the estimated result: ' .
                    ($expected ? 'true' : 'false'),
                'expects' => [
                    'result' => $expected,
                ],
                'params' => [
                    'required' => $helpRest,
                    'general' => $general,
                ],
            ];
        }
        foreach ([
                     '0' => false,
                     '12' => true,
                     '12,13' => true,
                     '12,doofie' => false,
                     'doofie' => false,
                     123 => true,
                 ] as $key => $expected) {
            $helpRest = $rest;
            // Doofie = silly dump
            $helpRest['timerHolidaysFalRelation'] = $key;
            $result[] = [
                'message' => 'Path is set (!)ever. Introduce the id-/id-list ' . $key . '  and get the estimated result: ' .
                    ($expected ? 'true' : 'false'),

                'expects' => [
                    'result' => $expected,
                ],
                'params' => [
                    'required' => $helpRest,
                    'general' => $general,
                ],
            ];
        }
        // check for optional
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
            $testPath = realpath(__DIR__ . '/../../../../');
            $testRealPath = realpath(__DIR__ . '/../../../../../web/');
            $yamlFileLoader = new YamlFileLoader();
            $holidaycalendarService = new HolidaycalendarService();

            $TestIncludeFinder = $this->getMockBuilder(HolidayTimer::class)
                ->setConstructorArgs([$holidaycalendarService, $yamlFileLoader])
                ->onlyMethods(['getExtentionPathByEnviroment', 'getPublicPathByEnviroment'])->getMock();

            $TestIncludeFinder
                ->expects(self::any())
                ->method('getExtentionPathByEnviroment')
                ->will(self::returnValue($testPath));
            $TestIncludeFinder
                ->expects(self::any())
                ->method('getPublicPathByEnviroment')
                ->will(self::returnValue($testRealPath));
            $paramTest = array_merge($params['required'], $params['general']);
            $hellp = $TestIncludeFinder->validate($paramTest);
            $this->assertEquals(
                $expects['result'],
                $TestIncludeFinder->validate($paramTest),
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
        // pathes relative to rootpage
        $rest = [
            'relMinToSelectedTimerEvent' => '720',
            'durationMinutes' => '120',
            'timerHolidaysFilePath' => '/..' . substr(realpath(__DIR__ . '/../../../../timer/Documentation/Examples_HolidayTimer.csv'), strlen('var/www/html/',)),
            'timerHolidaysFalRelation' => '0',
        ];

        $result = [];
        /* test allowed random (minimal) structure */
        /**
         * Konfiguraton
         * ohne Vorbidden
         * weihnachten 2022 25 Sonntag 26. Montag
         * Aktiv            hidden
         */
        foreach ([
                     ['date' => '2022-12-24 11:59:59', 'expects' => false, 'holiday' => 'before active christmasEve',],
                     ['date' => '2022-12-24 12:00:00', 'expects' => true, 'holiday' => 'active christmasEve',],
                     ['date' => '2022-12-24 13:00:00', 'expects' => true, 'holiday' => 'active christmasEve',],
                     ['date' => '2022-12-24 14:00:00', 'expects' => true, 'holiday' => 'active christmasEve',],
                     ['date' => '2022-12-24 14:00:01', 'expects' => false, 'holiday' => ' after active christmasEve',],
                     // check of different holidays
                     ['date' => '2022-12-24 13:00:00', 'expects' => true, 'holiday' => 'active christmasEve',],
                     ['date' => '2022-12-27 13:00:00', 'expects' => true, 'holiday' => 'christmas (shift So to Tu)',],
                     ['date' => '2023-01-09 13:00:00', 'expects' => true, 'holiday' => 'day of adults in Japan (2. Monday in januar)',],
                     ['date' => '2023-02-20 13:00:00', 'expects' => true, 'holiday' => '48 days before easter',],
                     ['date' => '2023-07-14 13:00:00', 'expects' => true, 'holiday' => 'Matariki',],
                     ['date' => '2023-03-20 13:00:00', 'expects' => true, 'holiday' => 'spring (defined by day-night-equal)',],
                     ['date' => '2023-09-23 13:00:00', 'expects' => true, 'holiday' => 'autumn (defined by day-night-equal, no shift on sunday by one day)',],
                     ['date' => '2024-09-23 13:00:00', 'expects' => true, 'holiday' => 'autumn (defined by day-night-equal, shift on sunday by one day)',],
                     ['date' => '2024-11-01 13:00:00', 'expects' => false, 'holiday' => 'multiyear (this holiday every two years since 2023)',],
                     ['date' => '2023-11-01 13:00:00', 'expects' => true, 'holiday' => 'multiyear (this holiday every two years since 2023)',],
                     ['date' => '2023-11-22 13:00:00', 'expects' => true, 'holiday' => 'prayer and repentance day (wendesday before the fifth sunday befor christmas)',],
                     ['date' => '2023-01-22 13:00:00', 'expects' => false, 'holiday' => 'chinese new year (use non-gregorian calendar; not allowed for chinese-calendar; buggy PHP)',],
                     ['date' => '2023-03-22 13:00:00', 'expects' => true, 'holiday' => 'ramadan (calculated) (use non-gregorian calendar for recalculation)',],
                     ['date' => '2023-05-05 13:00:00', 'expects' => true, 'holiday' => 'Buddha Purnima (vesakh) (use calculation of moonphase)',],
                     ['date' => '2023-09-16 13:00:00', 'expects' => true, 'holiday' => 'Rosch Haschana (use hebrew calendar, 1.1.)',],

                 ] as $params
        ) {
            $result[] = [
                'message' => 'The testValue `' . $params['date'] . '` (' . $params['holiday'] . ') defines an ' .
                    ($params['expects'] ? 'ACTIVE' : 'INACTIVE') . ' time. The testvalue is ' .
                    ($params['expects'] ? '' : 'not ') . 'part of an active interval.',
                'expects' => [
                    'result' => $params['expects'],
                ],
                'params' => [
                    'testValue' => $params['date'],
                    'testValueObj' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $params['date'],
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'general' => $general,
                    'required' => $rest,
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
            $configParams = array_merge($params['required'], $params['general']);
            $value = clone $params['testValueObj'];
            $this->assertEquals(
                $expects['result'],
                $this->subject->isActive($value, $configParams),
                'isActive: ' . $message
            );
            $this->assertEquals(
                $params['testValueObj'],
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
        // pathes relative to rootpage
        $rest = [
            'relMinToSelectedTimerEvent' => '720',
            'durationMinutes' => '120',
            'timerHolidaysFilePath' => '/..' . substr(realpath(__DIR__ . '/../../../../timer/Documentation/Examples_HolidayTimer.csv'), strlen('var/www/html/',)),
            'timerHolidaysFalRelation' => '0',
        ];

        $result = [];

        foreach ([
                     ['date' => '2022-12-03 11:59:00', 'begin' => '2022-12-24 12:00:00', 'end' => '2022-12-24 14:00:00'],
                     ['date' => '2022-12-24 11:59:00', 'begin' => '2022-12-24 12:00:00', 'end' => '2022-12-24 14:00:00'],
                     ['date' => '2022-12-24 12:00:00', 'begin' => '2022-12-27 12:00:00', 'end' => '2022-12-27 14:00:00'],
                     ['date' => '2022-12-27 12:00:00', 'begin' => '2023-01-09 12:00:00', 'end' => '2023-01-09 14:00:00'],
                     ['date' => '2023-01-09 12:00:00', 'begin' => '2023-02-20 12:00:00', 'end' => '2023-02-20 14:00:00'],
                     ['date' => '2023-02-20 12:00:00', 'begin' => '2023-03-20 12:00:00', 'end' => '2023-03-20 14:00:00'],
                     ['date' => '2023-03-20 12:00:00', 'begin' => '2023-03-22 12:00:00', 'end' => '2023-03-22 14:00:00'],
                     ['date' => '2023-03-22 12:00:00', 'begin' => '2023-05-05 12:00:00', 'end' => '2023-05-05 14:00:00'],
                     ['date' => '2023-05-05 12:00:00', 'begin' => '2023-07-14 12:00:00', 'end' => '2023-07-14 14:00:00'],
                     ['date' => '2023-07-14 12:00:00', 'begin' => '2023-09-16 12:00:00', 'end' => '2023-09-16 14:00:00'],
                     ['date' => '2023-09-16 12:00:00', 'begin' => '2023-09-23 12:00:00', 'end' => '2023-09-23 14:00:00'],
                     ['date' => '2023-09-23 12:00:00', 'begin' => '2023-11-01 12:00:00', 'end' => '2023-11-01 14:00:00'],
                     ['date' => '2023-11-01 12:00:00', 'begin' => '2023-11-22 12:00:00', 'end' => '2023-11-22 14:00:00'],
                     ['date' => '2023-11-22 12:00:00', 'begin' => '2023-11-26 12:00:00', 'end' => '2023-11-26 14:00:00'],
                     ['date' => '2023-11-26 12:00:00', 'begin' => '2023-11-29 12:00:00', 'end' => '2023-11-29 14:00:00'],
                     ['date' => '2023-11-29 12:00:00', 'begin' => '2023-11-30 12:00:00', 'end' => '2023-11-30 14:00:00'],
                     ['date' => '2023-11-30 12:00:00', 'begin' => '2023-12-03 12:00:00', 'end' => '2023-12-03 14:00:00'],
                     ['date' => '2023-12-03 12:00:00', 'begin' => '2023-12-24 12:00:00', 'end' => '2023-12-24 14:00:00'],
                 ] as $item
        ) {
            $result[] = [
                'message' => 'The testValue `' . $item['date'] . '` leads to the next range [`' . $item['begin'] . '`, `' . $item['end'] . '`].',
                'expects' => [
                    'result' => [
                        'beginning' => $item['begin'],
                        'ending' => $item['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'testValue' => $item['date'],
                    'testValueObj' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $item['date'],
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'general' => $general,
                    'required' => $rest,
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
            $setting = array_merge($params['required'], $params['general']);
            $testValue = clone $params['testValueObj'];
            /** @var TimerStartStopRange $result */
            $result = $this->subject->nextActive($testValue, $setting);
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
        // pathes relative to rootpage
        $rest = [
            'relMinToSelectedTimerEvent' => '720',
            'durationMinutes' => '120',
            'timerHolidaysFilePath' => '/..' . substr(realpath(__DIR__ . '/../../../../timer/Documentation/Examples_HolidayTimer.csv'), strlen('var/www/html/',)),
            'timerHolidaysFalRelation' => '0',
        ];

        $result = [];

        foreach ([
                     ['date' => '2022-12-24 14:00:00', 'begin' => '2022-11-30 12:00:00', 'end' => '2022-11-30 14:00:00'],
                     ['date' => '2022-12-24 14:01:00', 'begin' => '2022-12-24 12:00:00', 'end' => '2022-12-24 14:00:00'],
                     ['date' => '2022-12-25 14:00:00', 'begin' => '2022-12-24 12:00:00', 'end' => '2022-12-24 14:00:00'],
                     ['date' => '2022-12-27 14:00:00', 'begin' => '2022-12-24 12:00:00', 'end' => '2022-12-24 14:00:00'],
                     ['date' => '2023-01-09 14:00:00', 'begin' => '2022-12-27 12:00:00', 'end' => '2022-12-27 14:00:00'],
                     ['date' => '2023-02-20 14:00:00', 'begin' => '2023-01-09 12:00:00', 'end' => '2023-01-09 14:00:00'],
                     ['date' => '2023-03-20 14:00:00', 'begin' => '2023-02-20 12:00:00', 'end' => '2023-02-20 14:00:00'],
                     ['date' => '2023-03-22 14:00:00', 'begin' => '2023-03-20 12:00:00', 'end' => '2023-03-20 14:00:00'],
                     ['date' => '2023-05-05 14:00:00', 'begin' => '2023-03-22 12:00:00', 'end' => '2023-03-22 14:00:00'],
                     ['date' => '2023-07-14 14:00:00', 'begin' => '2023-05-05 12:00:00', 'end' => '2023-05-05 14:00:00'],
                     ['date' => '2023-09-16 14:00:00', 'begin' => '2023-07-14 12:00:00', 'end' => '2023-07-14 14:00:00'],
                     ['date' => '2023-09-23 14:00:00', 'begin' => '2023-09-16 12:00:00', 'end' => '2023-09-16 14:00:00'],
                     ['date' => '2023-11-01 14:00:00', 'begin' => '2023-09-23 12:00:00', 'end' => '2023-09-23 14:00:00'],
                     ['date' => '2023-11-22 14:00:00', 'begin' => '2023-11-01 12:00:00', 'end' => '2023-11-01 14:00:00'],
                     ['date' => '2023-11-26 14:00:00', 'begin' => '2023-11-22 12:00:00', 'end' => '2023-11-22 14:00:00'],
                     ['date' => '2023-11-29 14:00:00', 'begin' => '2023-11-26 12:00:00', 'end' => '2023-11-26 14:00:00'],
                     ['date' => '2023-11-30 14:00:00', 'begin' => '2023-11-29 12:00:00', 'end' => '2023-11-29 14:00:00'],
                     ['date' => '2023-12-03 14:00:00', 'begin' => '2023-11-30 12:00:00', 'end' => '2023-11-30 14:00:00'],
                     ['date' => '2023-12-24 14:00:00', 'begin' => '2023-12-03 12:00:00', 'end' => '2023-12-03 14:00:00'],
                     ['date' => '2023-12-25 14:00:00', 'begin' => '2023-12-24 12:00:00', 'end' => '2023-12-24 14:00:00'],
                     ['date' => '2023-12-25 14:01:00', 'begin' => '2023-12-25 12:00:00', 'end' => '2023-12-25 14:00:00'],
                 ] as $item
        ) {
            $result[] = [
                'message' => 'The testValue `' . $item['date'] . '` leads to the next range [`' . $item['begin'] . '`, `' . $item['end'] . '`].'
                    . ' ' . (empty($item['msg']) ? '' : $item['msg']),
                'expects' => [
                    'result' => [
                        'beginning' => $item['begin'],
                        'ending' => $item['end'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'testValue' => $item['date'],
                    'testValueObj' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $item['date'],
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'general' => $general,
                    'required' => $rest,
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
            $setting = array_merge($params['required'], $params['general']);
            $testValue = clone $params['testValueObj'];
            /** @var TimerStartStopRange $result */
            $result = $this->subject->prevActive($testValue, $setting);
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
