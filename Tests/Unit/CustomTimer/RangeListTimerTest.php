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

use Porthd\Timer\CustomTimer\DailyTimer;
use Porthd\Timer\CustomTimer\DatePeriodTimer;
use Porthd\Timer\CustomTimer\DefaultTimer;
use Porthd\Timer\CustomTimer\EasterRelTimer;
use Porthd\Timer\CustomTimer\MoonphaseRelTimer;
use Porthd\Timer\CustomTimer\MoonriseRelTimer;
use Porthd\Timer\CustomTimer\PeriodListTimer;
use Porthd\Timer\CustomTimer\RangeListTimer;
use Porthd\Timer\CustomTimer\SunriseRelTimer;
use Porthd\Timer\CustomTimer\WeekdayInMonthTimer;
use Porthd\Timer\CustomTimer\WeekdaylyTimer;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Cache\CacheManager;
use DateInterval;
use DateTime;

use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Domain\Repository\ListingRepository;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Services\ListOfTimerService;
use Porthd\Timer\Utilities\ConfigurationUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RangeListTimerTest extends TestCase
{
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerInterface::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE = TimerInterface::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerInterface::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerInterface::ARG_ULTIMATE_RANGE_END;
    protected const NAME_TIMER = 'txTimerRangeList';
    protected const SOME_NOT_EMPTY_VALUE = 'some value';
    protected const ALLOWED_TIME_ZONE = 'UTC';


    /**
     * @var RangeListTimer
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
        ];
        $activateBitWiseTimerClasses = 2047;
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
        $this->simulatePartOfGlobalsTypo3Array();
        /** @var ListingRepository $listingRepository */
        $listingRepository = GeneralUtility::makeInstance(ListingRepository::class);
        $yamlFileLoader = new YamlFileLoader();
        $timerList = new ListOfTimerService();
        $this->subject = GeneralUtility::makeInstance(RangeListTimer::class, ...[null, $listingRepository, $yamlFileLoader, $timerList]);
        $projectPath = '/var/www/html';
        Environment::initialize(
            new ApplicationContext('Testing'),
            false,
            true,
            $projectPath,
            $projectPath . '/web',
            $projectPath . '/var',
            $projectPath . '/config',
            $projectPath . '/typo3',
            'win'
        );
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
            $this->assertTrue((false), 'The File-path should contain `' . TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH . '`, so that the TCA-attribute-action `onChange` will work correctly. ');
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
            'recursiveLoopLimit' => '',
            'yamlActiveFilePath' => '',
            //
            'yamlForbiddenFilePath' => '',
            //
            'databaseActiveRangeList' => '1,2',
            // At least `databaseActiveRangeList` or `yamlActiveFilePath` must contain some valid content
            'databaseForbiddenRangeList' => '',
            //
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
                     [null, false], [false, true], ['false', true], [new Datetime(), false],
                     ['hallo', false],
                     ['0', true], [0.0, true], ["0.0", false],
                     ['true', true], ['1', true], [1, true],
                     [1.0, true], ['1.0', false],] as $value) {
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
            'message' => 'The test randomly is correct.',
            'expects' => [
                'result' => true,
            ],
            'params' => [
                'required' => [
                    'yamlActiveFilePath' => 'EXT:timer/Tests/Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                    // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                    'yamlForbiddenFilePath' => '',
                    'databaseActiveRangeList' => '1,2',
                    // At least one must contain numbers and a comaseparated List of numbers or can be empty, if 'yamlActiveFilePath' is filled
                    'databaseForbiddenRangeList' => '1',
                    // At least one must contain numbers and a comaseparated List of numbers or can be empty
                ],
                'optional' => [
                    'recursiveLoopLimit' => '',
                ],
                'general' => $general,
            ],
        ];
        // check for optional
        foreach ([
                     'recursiveLoopLimit',
                     'yamlActiveFilePath',
                     'yamlForbiddenFilePath',
                     'databaseActiveRangeList',
                     'databaseForbiddenRangeList',
                 ] as $myUnset) {
            $item = [
                'message' => 'The test does not fails, because only one parameter `' . $myUnset . '` is missing.The list is already defined.',
                'expects' => [
                    'result' => true,
                ],
                'params' => [
                    'required' => [
                        'yamlActiveFilePath' => 'EXT:timer/Tests/Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                        // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                        'yamlForbiddenFilePath' => '',
                        'databaseActiveRangeList' => '1,2',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty, if 'yamlActiveFilePath' is filled
                        'databaseForbiddenRangeList' => '1',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty
                    ],
                    'optional' => [
                        'recursiveLoopLimit' => '10',
                    ],
                    'general' => $general,
                ],
            ];
            unset($item['params']['required'][$myUnset]);
            $result[] = $item;
        }

        // check for the special requirement, that ther is not a correct definition in an active Path
        foreach ([
                     ['yaml' => '', 'database' => ''],
                     ['yaml' => 'unset', 'database' => ''],
                     ['yaml' => '', 'database' => 'unset'],
                     ['yaml' => 'unset', 'database' => 'unset'],
                 ] as $myResetArray) {
            foreach ([
                         ['yaml' => '', 'database' => ''],
                         [
                             'yaml' => 'EXT:timer/Tests/Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                             'database' => '',
                         ],
                         ['yaml' => '', 'database' => '1,2'],
                         [
                             'yaml' => 'EXT:timer/Tests/Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                             'database' => '1,2',
                         ],
                     ] as $myForbidden) {
                $item = [
                    'message' => 'The test fails, because there is no active definition for active Timers, ' .
                        'because is [`yaml`|`databse`] is defined as ["' . $myResetArray['yaml'] . '"|"' . $myResetArray['database'] . '"]. ' .
                        'The variation of Forbidden definition of  [`yaml`|`databse`] (["' . $myForbidden['yaml'] .
                        '"|"' . $myForbidden['database'] . '"]) has no effect.',
                    'expects' => [
                        'result' => false,
                    ],
                    'params' => [
                        'required' => [
                            'yamlActiveFilePath' => $myResetArray['yaml'],
                            // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                            'yamlForbiddenFilePath' => $myForbidden['yaml'],
                            'databaseActiveRangeList' => $myResetArray['database'],
                            // At least one must contain numbers and a comaseparated List of numbers or can be empty, if 'yamlActiveFilePath' is filled
                            'databaseForbiddenRangeList' => $myForbidden['database'],
                            // At least one must contain numbers and a comaseparated List of numbers or can be empty
                        ],
                        'optional' => [
                            'recursiveLoopLimit' => '',
                        ],
                        'general' => $general,
                    ],
                ];
                if ($myResetArray['yaml'] === 'unset') {
                    unset($item['params']['required']['yamlActiveFilePath']);
                }
                if ($myResetArray['database'] === 'unset') {
                    unset($item['params']['required']['yamlActiveFilePath']);
                }
                $result[] = $item;
            }
        }

        // variation of requiered parmeters
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

            $TestIncludeFinder = $this->getMockBuilder(RangeListTimer::class)
                ->onlyMethods(['getExtentionPathByEnviroment', 'getPublicPathByEnviroment'])->getMock();

            $TestIncludeFinder
                ->expects(self::any())
                ->method('getExtentionPathByEnviroment')
                ->will(self::returnValue($testPath));
            $TestIncludeFinder
                ->expects(self::any())
                ->method('getPublicPathByEnviroment')
                ->will(self::returnValue($testPath));
            $paramTest = array_merge($params['required'], $params['optional'], $params['general']);
            $this->assertEquals(
                $expects['result'],
                $TestIncludeFinder->validate($paramTest),
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
        /** the function of TYPO3 `getFileAbsFileName` don't allow files outside the webpath */
        $prefixPath = '/var/www/html/web/typo3conf/ext/timer/Tests/Unit/CustomTimer';
        $general = [
            'useTimeZoneOfFrontend' => 0,
            'timeZoneOfEvent' => 'Europe/Berlin',
            'ultimateBeginningTimer' => '0001-01-01 00:00:00',
            'ultimateEndingTimer' => '9999-12-31 23:59:59',
        ];

        $result = [];
        /* test allowed random (minimal) structure */
        $result[] = [
            'message' => 'The testValue `2022-12-26 05:59:59` defines  an INACTIVE time. The testvalue is not part of an active interval.',
            'expects' => [
                'result' => false,
            ],
            'params' => [
                'testValue' => '2022-12-26 05:59:59',
                'testValueObj' => date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    '2022-12-26 05:59:59',
                    new DateTimeZone('Europe/Berlin')
                ),
                'required' => [
                    'yamlActiveFilePath' => $prefixPath . '/../../Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                    // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                    'yamlForbiddenFilePath' => $prefixPath . '/../../Tests/Fixture/CustomTimer/RangeListeTimerForbiddenYaml.yaml',
                    'databaseActiveRangeList' => '',
                    // At least one must contain numbers and a comaseparated List of numbers or can be empty, if 'yamlActiveFilePath' is filled
                    'databaseForbiddenRangeList' => '',
                    // At least one must contain numbers and a comaseparated List of numbers or can be empty
                ],
                'optional' => [
                    'recursiveLoopLimit' => '10',
                ],
                'general' => $general,
            ],
        ];
        /**
         * Konfiguraton
         * ohne Vorbidden
         * weihnachten 2022 25 Sonntag 26. Montag
         * Aktiv            hidden
         * Mo: 06:00-12:00  + 00:00-24:00 Chr. 2022
         * Di. 06:00-12:00  03:00 07:00
         * Mi  02:00-12:00  03:00 07:00
         * Do  02:00-12:00  03:00 07:00
         * Fr. 02:00-12:00  03:00 07:00
         * Sa. 02:00-12:00  03:00 07:00
         * So. 02:00-12:00  03:00 07:00 + 00:00-24:00 Chr. 2022
         *
         * Testdaten            Act/Hid OnlyActive
         * - 26.12.2022 05:59:59    nein    nein
         * - 26.12.2022 06:00:59    nein    ja
         * - 26.12.2022 12:00:59   nein    nein
         * - 26.12.2022 12:01:59   nein    nein
         * - 19.12.2022 05:59:59    nein    nein
         * - 19.12.2022 06:00:59    ja      ja
         * - 19.12.2022 07:00:59    ja      ja
         * - 19.12.2022 12:00:59   ja      ja
         * - 19.12.2022 12:01:59   nein    nein
         * - 25.12.2022 01:59:59   nein    nein
         * - 25.12.2022 02:00:59   nein    ja
         * - 25.12.2022 12:00:59   nein    ja
         * - 25.12.2022 12:01:59   nein    nein
         * - 18.12.2022 01:59:59   nein    nein
         * - 18.12.2022 02:00:59   ja      ja
         * - 18.12.2022 03:00:59   nein    ja
         * - 18.12.2022 07:00:59   nein    ja
         * - 18.12.2022 07:01:59   ja      ja
         * - 18.12.2022 12:00:59   ja      ja
         * - 18.12.2022 12:01:59   nein    nein
         */
        foreach ([
                     ['testDateTime' => '2022-12-26 05:59:59', 'hiddenActive' => false, 'active' => false,],
                     ['testDateTime' => '2022-12-26 06:00:59', 'hiddenActive' => false, 'active' => true,],
                     ['testDateTime' => '2022-12-26 11:59:59', 'hiddenActive' => false, 'active' => true,],
                     ['testDateTime' => '2022-12-26 12:00:59', 'hiddenActive' => false, 'active' => false,],
                     ['testDateTime' => '2022-12-26 12:01:59', 'hiddenActive' => false, 'active' => false,],
                     ['testDateTime' => '2022-12-19 05:59:59', 'hiddenActive' => false, 'active' => false,],
                     ['testDateTime' => '2022-12-19 06:00:00', 'hiddenActive' => true, 'active' => true,],
                     ['testDateTime' => '2022-12-19 06:00:59', 'hiddenActive' => true, 'active' => true,],
                     ['testDateTime' => '2022-12-19 07:00:59', 'hiddenActive' => true, 'active' => true,],
                     ['testDateTime' => '2022-12-19 11:59:59', 'hiddenActive' => true, 'active' => true,],
                     ['testDateTime' => '2022-12-19 12:00:00', 'hiddenActive' => true, 'active' => true,],
                     ['testDateTime' => '2022-12-19 12:00:01', 'hiddenActive' => false, 'active' => false,],
                     ['testDateTime' => '2022-12-25 01:59:59', 'hiddenActive' => false, 'active' => false,],
                     ['testDateTime' => '2022-12-25 02:00:59', 'hiddenActive' => false, 'active' => true,],
                     ['testDateTime' => '2022-12-25 12:00:00', 'hiddenActive' => false, 'active' => true,],
                     ['testDateTime' => '2022-12-25 12:00:01', 'hiddenActive' => false, 'active' => false,],
                     ['testDateTime' => '2022-12-18 01:59:59', 'hiddenActive' => false, 'active' => false,],
                     ['testDateTime' => '2022-12-18 02:00:00', 'hiddenActive' => true, 'active' => true,],
                     ['testDateTime' => '2022-12-18 03:00:00', 'hiddenActive' => false, 'active' => true,],
                     ['testDateTime' => '2022-12-18 06:59:59', 'hiddenActive' => false, 'active' => true,],
                     ['testDateTime' => '2022-12-18 07:00:00', 'hiddenActive' => false, 'active' => true,],
                     ['testDateTime' => '2022-12-18 07:00:01', 'hiddenActive' => true, 'active' => true,],
                     ['testDateTime' => '2022-12-18 07:00:59', 'hiddenActive' => true, 'active' => true,],
                     ['testDateTime' => '2022-12-18 07:01:59', 'hiddenActive' => true, 'active' => true,],
                     ['testDateTime' => '2022-12-18 12:00:00', 'hiddenActive' => true, 'active' => true,],
                     ['testDateTime' => '2022-12-18 12:00:01', 'hiddenActive' => false, 'active' => false,],
                 ] as $params
        ) {
            $result[] = [
                'message' => 'The testValue `' . $params['testDateTime'] . '` defines' .
                    ($params['hiddenActive'] ? ' an active time' : ' an INACTIVE time') . ' for the active-hidden-combination.' .
                    ($params['active'] ? '' : ' The testvalue is not part of an active interval.') .
                    ((($params['hiddenActive'] === false) && ($params['active'] === true)) ? ' The testvalue is at least part of one hidden timeslot and included by an timeslot for active parts.' : ''),
                'expects' => [
                    'result' => $params['hiddenActive'],
                ],
                'params' => [
                    'testValue' => $params['testDateTime'],
                    'testValueObj' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $params['testDateTime'],
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'required' => [
//                        'yamlActiveFilePath' => 'EXT:timer/Tests/Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml', // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                        'yamlActiveFilePath' => $prefixPath . '/../../Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                        // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                        'yamlForbiddenFilePath' => $prefixPath . '/../../Fixture/CustomTimer/RangeListeTimerForbiddenYaml.yaml',
                        'databaseActiveRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty, if 'yamlActiveFilePath' is filled
                        'databaseForbiddenRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty
                    ],
                    'optional' => [
                        'recursiveLoopLimit' => '10',
                    ],
                    'general' => $general,
                ],
            ];
            $result[] = [
                'message' => 'The testValue `' . $params['testDateTime'] . '` defines ' .
                    ($params['hiddenActive'] ? ' an active time' : ' an INACTIVE time') . ' for the ONLY ACTIVE combination.' .
                    ($params['active'] ? '' : ' The testvalue is not part of an active interval.') .
                    ((($params['hiddenActive'] === false) && ($params['active'] === true)) ? ' The testvalue is at least part of one hidden timeslot and included by an timeslot for active parts.' : ''),
                'expects' => [
                    'result' => $params['active'],
                ],
                'params' => [
                    'testValue' => $params['testDateTime'],
                    'testValueObj' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $params['testDateTime'],
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'required' => [
                        'yamlActiveFilePath' => $prefixPath . '/../../Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                        // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                        'yamlForbiddenFilePath' => '',
                        // no forbidden-definitions
                        'databaseActiveRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty, if 'yamlActiveFilePath' is filled
                        'databaseForbiddenRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty
                    ],
                    'optional' => [
                        'recursiveLoopLimit' => '10',
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
            $configParams = array_merge($params['required'], $params['optional'], $params['general']);
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
        $prefixPath = '/var/www/html/web/typo3conf/ext/timer/Tests/Unit/CustomTimer';
        /**
         * Konfiguraton
         * ohne Vorbidden
         * weihnachten 2022 25 Sonntag 26. Montag
         * Aktiv            hidden
         * Mo: 06:00-12:00  + 00:00-24:00 Chr. 2022
         * Di. 06:00-12:00  03:00 07:00
         * Mi  02:00-12:00  03:00 07:00
         * Do  02:00-12:00  03:00 07:00
         * Fr. 02:00-12:00  03:00 07:00
         * Sa. 02:00-12:00  03:00 07:00
         * So. 02:00-12:00  03:00 07:00 + 00:00-24:00 Chr. 2022
         *
         * Testdaten            Act/Hid OnlyActive
         * - 26.12.2022 05:59:59    nein    nein
         * - 26.12.2022 06:00:59    nein    ja
         * - 26.12.2022 12:00:59   nein    nein
         * - 26.12.2022 12:01:59   nein    nein
         * - 19.12.2022 05:59:59    nein    nein
         * - 19.12.2022 06:00:59    ja      ja
         * - 19.12.2022 07:00:59    ja      ja
         * - 19.12.2022 12:00:59   ja      ja
         * - 19.12.2022 12:01:59   nein    nein
         * - 25.12.2022 01:59:59   nein    nein
         * - 25.12.2022 02:00:59   nein    ja
         * - 25.12.2022 12:00:59   nein    ja
         * - 25.12.2022 12:01:59   nein    nein
         * - 18.12.2022 01:59:59   nein    nein
         * - 18.12.2022 02:00:59   ja      ja
         * - 18.12.2022 03:00:59   nein    ja
         * - 18.12.2022 07:00:59   nein    ja
         * - 18.12.2022 07:01:59   ja      ja
         * - 18.12.2022 12:00:59   ja      ja
         * - 18.12.2022 12:01:59   nein    nein
         */

        $result = [];
        /* test allowed random (minimal) structure */
        $itemList = [];
        $itemList[] = [
            'testValue' => '2022-12-26 05:59:59',
            'msg' => 'not part of an active range of times and tested near beginning-border',
            'beginOnlyActive' => '2022-12-26 06:00:00',
            'beginWithForbidden' => '2022-12-27 07:00:00',
            'endOnlyActive' => '2022-12-26 12:00:00',
            'endWithForbidden' => '2022-12-27 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-19 05:59:59',
            'msg' => 'not part of an active range of times and tested near beginning-border',
            'beginOnlyActive' => '2022-12-19 06:00:00',
            'beginWithForbidden' => '2022-12-19 06:00:00',
            'endOnlyActive' => '2022-12-19 12:00:00',
            'endWithForbidden' => '2022-12-19 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-19 06:00:00',
            'msg' => 'part of an active range of times and tested near beginning-border',
            'beginOnlyActive' => '2022-12-20 06:00:00',
            'beginWithForbidden' => '2022-12-20 07:00:00',
            'endOnlyActive' => '2022-12-20 12:00:00',
            'endWithForbidden' => '2022-12-20 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-26 06:00:00',
            'msg' => 'part of an active and concurrent forbidden range of times and tested near beginning-border',
            'beginOnlyActive' => '2022-12-27 06:00:00',
            'beginWithForbidden' => '2022-12-27 07:00:00',
            'endOnlyActive' => '2022-12-27 12:00:00',
            'endWithForbidden' => '2022-12-27 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-25 12:00:01',
            'msg' => 'not part of an active range of times and tested near ending-border',
            'beginOnlyActive' => '2022-12-26 06:00:00',
            'beginWithForbidden' => '2022-12-27 07:00:00',
            'endOnlyActive' => '2022-12-26 12:00:00',
            'endWithForbidden' => '2022-12-27 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-18 12:00:01',
            'msg' => 'not part of an active range of times and tested near ending-border',
            'beginOnlyActive' => '2022-12-19 06:00:00',
            'beginWithForbidden' => '2022-12-19 06:00:00',
            'endOnlyActive' => '2022-12-19 12:00:00',
            'endWithForbidden' => '2022-12-19 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-19 12:00:00',
            'msg' => 'part of an active range of times and tested near ending-border',
            'beginOnlyActive' => '2022-12-20 06:00:00',
            'beginWithForbidden' => '2022-12-20 07:00:00',
            'endOnlyActive' => '2022-12-20 12:00:00',
            'endWithForbidden' => '2022-12-20 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-25 12:00:00',
            'msg' => 'part of an active and concurrent forbidden range of times and tested near ending-border',
            'beginOnlyActive' => '2022-12-26 06:00:00',
            'beginWithForbidden' => '2022-12-27 07:00:00',
            'endOnlyActive' => '2022-12-26 12:00:00',
            'endWithForbidden' => '2022-12-27 12:00:00',
        ];
        $addWith = '. The timerange is build by active and forbidden parts';
        $addOnly = '. The timerange is only build by active parts';
        foreach ($itemList as $item) {
            $result[] = [
                'message' => 'The testValue `' . $item['testValue'] . '` is ' . $item['msg'] . $addOnly . ' in the nextActive-Test.',
                'expects' => [
                    'result' => [
                        'beginning' => $item['beginOnlyActive'],
                        'ending' => $item['endOnlyActive'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'testValue' => $item['testValue'],
                    'testValueObj' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $item['testValue'],
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'required' => [
                        'yamlActiveFilePath' => $prefixPath . '/../../Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                        // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                        'yamlForbiddenFilePath' => '',
                        'databaseActiveRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty, if 'yamlActiveFilePath' is filled
                        'databaseForbiddenRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty
                    ],
                    'optional' => [

                    ],
                    'general' => $general,
                ],
            ];
            $result[] = [
                'message' => 'The testValue `' . $item['testValue'] . '` is ' . $item['msg'] . $addWith . ' in the nextActive-Test.',
                'expects' => [
                    'result' => [
                        'beginning' => $item['beginWithForbidden'],
                        'ending' => $item['endWithForbidden'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'testValue' => $item['testValue'],
                    'testValueObj' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $item['testValue'],
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'required' => [
                        'yamlActiveFilePath' => $prefixPath . '/../../Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                        // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                        'yamlForbiddenFilePath' => $prefixPath . '/../../Fixture/CustomTimer/RangeListeTimerForbiddenYaml.yaml',
                        'databaseActiveRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty, if 'yamlActiveFilePath' is filled
                        'databaseForbiddenRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty
                    ],
                    'optional' => [
                        'recursiveLoopLimit' => '10',
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
            $setting = array_merge($params['required'], $params['optional'], $params['general']);
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
        $prefixPath = '/var/www/html/web/typo3conf/ext/timer/Tests/Unit/CustomTimer';
        /**
         * Konfiguraton
         * ohne Vorbidden
         * weihnachten 2022 25 Sonntag 26. Montag
         * Aktiv            hidden
         * Mo: 06:00-12:00  + 00:00-24:00 Chr. 2022
         * Di. 06:00-12:00  03:00 07:00
         * Mi  02:00-12:00  03:00 07:00
         * Do  02:00-12:00  03:00 07:00
         * Fr. 02:00-12:00  03:00 07:00
         * Sa. 02:00-12:00  03:00 07:00
         * So. 02:00-12:00  03:00 07:00 + 00:00-24:00 Chr. 2022
         *
         * Testdaten            Act/Hid OnlyActive
         * - 26.12.2022 05:59:59    nein    nein
         * - 26.12.2022 06:00:59    nein    ja
         * - 26.12.2022 12:00:59   nein    nein
         * - 26.12.2022 12:01:59   nein    nein
         * - 19.12.2022 05:59:59    nein    nein
         * - 19.12.2022 06:00:59    ja      ja
         * - 19.12.2022 07:00:59    ja      ja
         * - 19.12.2022 12:00:59   ja      ja
         * - 19.12.2022 12:01:59   nein    nein
         * - 25.12.2022 01:59:59   nein    nein
         * - 25.12.2022 02:00:59   nein    ja
         * - 25.12.2022 12:00:59   nein    ja
         * - 25.12.2022 12:01:59   nein    nein
         * - 18.12.2022 01:59:59   nein    nein
         * - 18.12.2022 02:00:59   ja      ja
         * - 18.12.2022 03:00:59   nein    ja
         * - 18.12.2022 07:00:59   nein    ja
         * - 18.12.2022 07:01:59   ja      ja
         * - 18.12.2022 12:00:59   ja      ja
         * - 18.12.2022 12:01:59   nein    nein
         */

        $result = [];
        /* test allowed random (minimal) structure */
        $itemList = [];
        $itemList[] = [
            'testValue' => '2022-12-26 05:59:59',
            'msg' => 'not part of an active range of times and tested near beginning-border',
            'beginOnlyActive' => '2022-12-25 02:00:00',
            'beginWithForbidden' => '2022-12-24 07:00:00',
            'endOnlyActive' => '2022-12-25 12:00:00',
            'endWithForbidden' => '2022-12-24 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-19 05:59:59',
            'msg' => 'not part of an active range of times and tested near beginning-border',
            'beginOnlyActive' => '2022-12-18 02:00:00',
            'beginWithForbidden' => '2022-12-18 07:00:00',
            'endOnlyActive' => '2022-12-18 12:00:00',
            'endWithForbidden' => '2022-12-18 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-18 12:00:59',
            'msg' => 'not part of an active range of times and tested for the range at the previous day',
            'beginOnlyActive' => '2022-12-18 02:00:00',
            'beginWithForbidden' => '2022-12-18 07:00:00',
            'endOnlyActive' => '2022-12-18 12:00:00',
            'endWithForbidden' => '2022-12-18 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-18 07:00:59',
            'msg' => 'part of an active range of times for forbidden and only active cases and tested for the previous range at the previous day',
            'beginOnlyActive' => '2022-12-17 02:00:00',
            'beginWithForbidden' => '2022-12-18 02:00:00',
            'endOnlyActive' => '2022-12-17 12:00:00',
            'endWithForbidden' => '2022-12-18 03:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-18 03:00:59',
            'msg' => 'part of a forbidden range in an active range of times and tested for the previous range off the current active range  (with forbidden) oder for the previous range on the previous day',
            'beginOnlyActive' => '2022-12-17 02:00:00',
            'beginWithForbidden' => '2022-12-18 02:00:00',
            'endOnlyActive' => '2022-12-17 12:00:00',
            'endWithForbidden' => '2022-12-18 03:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-18 02:59:59',
            'msg' => 'part of the second former part in an active range of times and tested for the previous range off the current active range  (with forbidden) oder for the previous range on the previous day',
            'beginOnlyActive' => '2022-12-17 02:00:00',
            'beginWithForbidden' => '2022-12-17 07:00:00',
            'endOnlyActive' => '2022-12-17 12:00:00',
            'endWithForbidden' => '2022-12-17 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-18 01:59:59',
            'msg' => 'not part of an active range of times and tested previous range at the previous day',
            'beginOnlyActive' => '2022-12-17 02:00:00',
            'beginWithForbidden' => '2022-12-17 07:00:00',
            'endOnlyActive' => '2022-12-17 12:00:00',
            'endWithForbidden' => '2022-12-17 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-19 06:00:00',
            'msg' => 'part of an active range of times and tested for the previous range in the previous day',
            'beginOnlyActive' => '2022-12-18 02:00:00',
            'beginWithForbidden' => '2022-12-18 07:00:00',
            'endOnlyActive' => '2022-12-18 12:00:00',
            'endWithForbidden' => '2022-12-18 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-26 06:00:00',
            'msg' => ' part of an active and concurrent forbidden range of times and tested for the previous range at the previous day (only active) or at the pre-previous day (with forbidden)',
            'beginOnlyActive' => '2022-12-25 02:00:00',
            'beginWithForbidden' => '2022-12-24 07:00:00',
            'endOnlyActive' => '2022-12-25 12:00:00',
            'endWithForbidden' => '2022-12-24 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-26 00:00:00',
            'msg' => 'part of an forbidden range of times and tested for the previous range for the previous range at the previous day (only active) or at the pre-previous day (with forbidden)',
            'beginOnlyActive' => '2022-12-25 02:00:00',
            'beginWithForbidden' => '2022-12-24 07:00:00',
            'endOnlyActive' => '2022-12-25 12:00:00',
            'endWithForbidden' => '2022-12-24 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-25 12:01:00',
            'msg' => 'not part of a active range & part of a forbidden range and tested for the previous range at the same day (active only) or at the previous day (with forbidden) ',
            'beginOnlyActive' => '2022-12-25 02:00:00',
            'beginWithForbidden' => '2022-12-24 07:00:00',
            'endOnlyActive' => '2022-12-25 12:00:00',
            'endWithForbidden' => '2022-12-24 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-18 12:00:01',
            'msg' => 'not part of an active or forbidden range of times  and tested previous range at the same day',
            'beginOnlyActive' => '2022-12-18 02:00:00',
            'beginWithForbidden' => '2022-12-18 07:00:00',
            'endOnlyActive' => '2022-12-18 12:00:00',
            'endWithForbidden' => '2022-12-18 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-19 12:00:00',
            'msg' => 'part of an active range of times & not pat of a forbidden range and tested for the previous range at the previous day (active only) oder at the current day (with forbidden)',
            'beginOnlyActive' => '2022-12-18 02:00:00',
            'beginWithForbidden' => '2022-12-18 07:00:00',
            'endOnlyActive' => '2022-12-18 12:00:00',
            'endWithForbidden' => '2022-12-18 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-25 12:00:00',
            'msg' => 'part of an active and concurrent forbidden range of times and tested for the previous range at the previous  day',
            'beginOnlyActive' => '2022-12-24 02:00:00',
            'beginWithForbidden' => '2022-12-24 07:00:00',
            'endOnlyActive' => '2022-12-24 12:00:00',
            'endWithForbidden' => '2022-12-24 12:00:00',
        ];
        $itemList[] = [
            'testValue' => '2022-12-25 07:00:00',
            'msg' => 'although part of an active and concurrent forbidden range of times and tested for the previous range at the previous  day',
            'beginOnlyActive' => '2022-12-24 02:00:00',
            'beginWithForbidden' => '2022-12-24 07:00:00',
            'endOnlyActive' => '2022-12-24 12:00:00',
            'endWithForbidden' => '2022-12-24 12:00:00',
        ];
        foreach ($itemList as $item) {
            $result[] = [
                'message' => 'The testValue `' . $item['testValue'] . '` is ' . $item['msg'] .
                    '. The timerange is only build by active parts' .
                    ' in the prevActive-Test.',
                'expects' => [
                    'result' => [
                        'beginning' => $item['beginOnlyActive'],
                        'ending' => $item['endOnlyActive'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'testValue' => $item['testValue'],
                    'testValueObj' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $item['testValue'],
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'required' => [
                        'yamlActiveFilePath' => $prefixPath . '/../../Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                        // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                        'yamlForbiddenFilePath' => '',
                        'databaseActiveRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty, if 'yamlActiveFilePath' is filled
                        'databaseForbiddenRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty
                    ],
                    'optional' => [
                        'recursiveLoopLimit' => '10',
                    ],
                    'general' => $general,
                ],
            ];
            $result[] = [
                'message' => 'The testValue `' . $item['testValue'] . '` is ' . $item['msg'] .
                    '. The timerange is build by active and forbidden parts' .
                    ' in the prevActive-Test.',
                'expects' => [
                    'result' => [
                        'beginning' => $item['beginWithForbidden'],
                        'ending' => $item['endWithForbidden'],
                        'exist' => true,
                    ],
                ],
                'params' => [
                    'testValue' => $item['testValue'],
                    'testValueObj' => date_create_from_format(
                        TimerInterface::TIMER_FORMAT_DATETIME,
                        $item['testValue'],
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'required' => [
                        'yamlActiveFilePath' => $prefixPath . '/../../Fixture/CustomTimer/RangeListeTimerActiveYaml.yaml',
                        // At least one must containa an existing file or can be empty, if `databaseActiveRangeList` is filled
                        'yamlForbiddenFilePath' => $prefixPath . '/../../Fixture/CustomTimer/RangeListeTimerForbiddenYaml.yaml',
                        'databaseActiveRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty, if 'yamlActiveFilePath' is filled
                        'databaseForbiddenRangeList' => '',
                        // At least one must contain numbers and a comaseparated List of numbers or can be empty
                    ],
                    'optional' => [
                        'recursiveLoopLimit' => '10',
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
            $setting = array_merge($params['required'], $params['optional'], $params['general']);
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
