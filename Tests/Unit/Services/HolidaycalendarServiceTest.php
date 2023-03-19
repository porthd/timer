<?php

namespace Porthd\Timer\Services;

use DateInterval;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use ReflectionClass;

class HolidaycalendarServiceTest extends TestCase
{

    /**
     * @var HolidaycalendarService
     */
    protected $subject = null;


    protected function resolveGlobalsTypo3Array()
    {
        unset($GLOBALS);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new HolidaycalendarService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }


    /**
     * https://stackoverflow.com/questions/249664/best-practices-to-test-protected-methods-with-phpunit
     *
     * Get a private or protected method for testing/documentation purposes.
     * How to use for MyClass->foo():
     *      $cls = new MyClass();
     *      $foo = PHPUnitUtil::getPrivateMethod($cls, 'foo');
     *      $foo->invoke($cls, $...);
     * @param $obj
     * @param $name
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    public static function getPrivateMethod($obj, $name)
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function dataProviderGetGregorianDateForFixedTypeBySelectedExamples()
    {
        $result = [];
        $testDateTime = new DateTime();
        $testDateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
        $testDateTime->setTime(0, 0, 0);
        $testDateTime->setDate(2023, 12, 25);

        $testNewOthodoxChristmas = new DateTime();
        $testNewOthodoxChristmas->setTimezone(new DateTimeZone('Europe/Berlin'));
        $testNewOthodoxChristmas->setTime(0, 0, 0);
        $testNewOthodoxChristmas->setDate(2024, 01, 07);
        $testNewRocChristmas = new DateTime();
        $testNewRocChristmas->setTimezone(new DateTimeZone('Europe/Berlin'));
        $testNewRocChristmas->setTime(0, 0, 0);
        $testNewRocChristmas->setDate(2023, 12, 25);
        $testDateTimeNext = clone $testDateTime;
        $testDateTimeNext->setDate(2024, 12, 25);
        $testDateTimePrev = clone $testDateTime;
        $testDateTimePrev->setDate(2022, 12, 25);
        $startDatePre = clone $testDateTime;
        $startDatePre->sub(new DateInterval('P20D'));
        $startDatePrePrev = clone $testDateTime;
        $startDatePrePrev->sub(new DateInterval('P1Y20D'));
        $startDatePost = clone $testDateTime;
        $startDatePost->add(new DateInterval('P20D'));

        $christmasArgGregorian = [
            'month' => 12,
            'day' => 25,
            'calendar' => 'gregorian',
            'type' => 'fixed',
        ];
        $christmasArgJulian = $christmasArgGregorian;
        $christmasArgJulian['calendar'] = 'julian';
        $christmasArgRoc = $christmasArgGregorian;
        $christmasArgRoc['calendar'] = 'roc';
        foreach ([
                     ['arg' => $christmasArgGregorian, 'date' => $testDateTime],
                     ['arg' => $christmasArgJulian, 'date' => $testNewOthodoxChristmas],
                     ['arg' => $christmasArgRoc, 'date' => $testNewRocChristmas],
                 ] as $holidayArg
        ) {
            foreach (['de_DE.utf-8', 'en_US'] as $locale) {
                $addYear = 0;
                $result[] = [
                    'message' => 'The fixed christmas date is detected correctly for the locale `' . $locale . '`. ' .
                        'The other parameters are startdate ' . $startDatePre->format('d.m.Y') . ', addYear (' .
                        $addYear . ')',
                    'expects' => [
                        'result' => $holidayArg['date'],
                    ],
                    'params' => [
                        'locale' => $locale,
                        'startDate' => $startDatePre,
                        'holidayArg' => $holidayArg['arg'],
                        'addYear' => $addYear,
                    ],
                ];
                $addYear = 0;
                $addYearHoliday = clone $holidayArg['date'];
                $addYearHoliday->add(new DateInterval('P1Y'));
                $result[] = [
                    'message' => 'The fixed christmas date is detected correctly for the locale `' . $locale . '`.' .
                        'the other paremeters are startdate ' . $startDatePost->format('d.m.Y') . ', addYear (' .
                        $addYear . ')',
                    'expects' => [
                        'result' => $addYearHoliday,
                    ],
                    'params' => [
                        'locale' => $locale,
                        'startDate' => $startDatePost,
                        'holidayArg' => $holidayArg['arg'],
                        'addYear' => $addYear,
                    ],
                ];
                $addYear = 1;
                $result[] = [
                    'message' => 'The fixed christmas date is detected correctly for the locale `' . $locale . '`.' .
                        'the other paremeters are startdate ' . $startDatePrePrev->format('d.m.Y') . ', addYear (' .
                        $addYear . ')',
                    'expects' => [
                        'result' => $holidayArg['date'],
                    ],
                    'params' => [
                        'locale' => $locale,
                        'startDate' => $startDatePrePrev,
                        'holidayArg' => $holidayArg['arg'],
                        'addYear' => $addYear,
                    ],
                ];
            }
        }

        return $result;
    }

    /**
     * @dataProvider dataProviderGetGregorianDateForFixedTypeBySelectedExamples
     * @test
     */
    public function getGregorianDateForFixedTypeBySelectedExamples($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {

            $method = self::getPrivateMethod($this->subject, 'getGregorianDateForFixedType');

            $result = $method->invokeArgs($this->subject,
                [$params['locale'], $params['startDate'], $params['holidayArg'], $params['addYear']]);
            $this->assertEquals($expects['result'], $result->getBeginning(), $message); // whatever your assertion is
        }

    }

    public function dataProviderGetGregorianDateForFixedWeekendTypeBySelectedExamples()
    {
        $result = [];

        $christmasArgGregorian = [
            'month' => 12,
            'day' => 25,
            'calendar' => 'gregorian',
            'type' => 'fixedshifting',
            'statusCount' => '3,2,1,0,-1,-2,-3',
        ];
        //        26.12.2020 = saturday
        //        26.12.2021 = sunday
        //        26.12.2022 = monday
        //        26.12.2023 = tuesday
        $generalResult = date_create_from_format('Y-m-d', '2022-12-29');
        $generalResult->setTime(0, 0, 0);
        foreach ([
                     '2022-12-26',
                     '2022-12-27',
                     '2022-12-28',
                     '2022-12-29',
                     '2022-12-30',
                     '2022-12-31',
                 ] as $holidayString) {
            $list = explode('-', $holidayString);
            $christmasArgGregorian['day'] = (int)$list[2];;
            $result[] = [
                'message' => 'The fixed holiday (' . $holidayString . ') is shifted to the nearest thursday. The shifting works fine, what the variation of the holidays shows. ',
                'expects' => [
                    'result' => $generalResult,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => $christmasArgGregorian,
                    'startDate' => DateTime::createFromFormat('Y-m-d H:i:s', '2022-03-08' . ' 00:00:00'),
                    'addYear' => 0,
                ],
            ];
        }
        $christmasArgGregorian = [
            'month' => 1,
            'day' => 1,
            'calendar' => 'gregorian',
            'type' => 'fixedshifting',
            'statusCount' => '3,2,1,0,-1,-2,-3',
        ];
        // the missing weekday
        $result[] = [
            'message' => 'The fixed holiday (' . $holidayString . ') is shifted to the nearest thursday. The shifting works fine, what the variation of the holidays shows. ',
            'expects' => [
                'result' => $generalResult,
            ],
            'params' => [
                'locale' => 'de_DE',
                'holidayArg' => $christmasArgGregorian,
                'startDate' => DateTime::createFromFormat('Y-m-d H:i:s', '2023-03-01' . ' 00:00:00'),
                'addYear' => 0,
            ],
        ];

        return $result;
    }

    /**
     * @dataProvider dataProviderGetGregorianDateForFixedShiftingTypeBySelectedExamples
     * @test
     */
    public function getGregorianDateForFixedShiftingTypeBySelectedExamples($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {

            $method = self::getPrivateMethod($this->subject, 'getGregorianDateForFixedShiftingType');

            $result = $method->invokeArgs($this->subject,
                [$params['locale'], $params['startDate'], $params['holidayArg'], $params['addYear']]);

            $this->assertEquals($expects['result'], $result->getBeginning(), $message); // whatever your assertion is
        }

    }

    public function dataProviderGetGregorianDateForFixedMultiTypeTypeBySelectedExamples()
    {
        $result = [];

        $christmasArgGregorian = [
            'month' => 12,
            'day' => 25,
            'calendar' => 'gregorian',
            'type' => 'fixedmultiyear',
            'statusCount' => '3',
            'status' => '2021',
        ];
        //        26.12.2020 = saturday
        //        26.12.2021 = sunday
        //        26.12.2022 = monday
        //        26.12.2023 = tuesday
        $generalResult = date_create_from_format('Y-m-d', '2022-12-29');
        $generalResult->setTime(0, 0, 0);
        foreach ([
                     '2018' => '2018-12-25',
                     '2019' => false,
                     '2020' => false,
                     '2021' => '2021-12-25',
                     '2022' => false,
                     '2023' => false,
                     '2024' => '2024-12-25',
                     '2025' => false,
                     '2026' => false,
                 ] as $year => $resultDate) {
            $startDate = date_create_from_format('Y-m-d', $year . '-12-29');
            $startDate->setTime(0, 0, 0);
            $result[] = [
                'message' => 'The multi-year fixed holiday (25.12. all three year; refered to 2021) is correctly determined.' .
                    '  ',
                'expects' => [
                    'result' => ($resultDate ?
                        date_create_from_format('Y-m-d H:i:s', $resultDate . ' 00:00:00') :
                        $resultDate),
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => $christmasArgGregorian,
                    'startDate' => $startDate,
                    'addYear' => 0,
                ],
            ];
        }

        return $result;
    }

    /**
     * @dataProvider dataProviderGetGregorianDateForFixedMultiTypeTypeBySelectedExamples
     * @test
     */
    public function getGregorianDateForFixedMultiTypeBySelectedExamples($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {

            $method = self::getPrivateMethod($this->subject, 'getGregorianDateForFixedMultiType');
            /** @var TimerStartStopRange $result */
            $result = $method->invokeArgs($this->subject,
                [$params['locale'], $params['startDate'], $params['holidayArg'], $params['addYear']]);
            if ($expects['result'] === false) {

                $this->assertFalse($result->hasResultExist(), $message); // whatever your assertion is
            } else {

                $this->assertEquals($expects['result'], $result->getBeginning(),
                    $message); // whatever your assertion is
            }
        }

    }

    public function dataProviderGetGregorianDateForFixedRelatedTypeBySelectedExamples()
    {
        $result = [];

        //        26.12.2020 = saturday
        //        26.12.2021 = sunday
        //        26.12.2022 = monday // 18.12 // 11.12 / 4.12 /27.11 / 20.11 // (4 days) 16.11
        //        26.12.2023 = tuesday
        $generalResult = date_create_from_format('Y-m-d', '2022-1-2');
        $generalResult->setTime(0, 0, 0);
        foreach ([
                     '2023-01-01' => [7, 1, 0],
                     '2022-12-18' => [7, -1, 0],
                     '2022-12-11' => [7, -2, 0],
                     '2022-12-04' => [7, -3, 0],
                     '2022-11-27' => [7, -4, 0],
                     '2022-11-20' => [7, -5, 0],
                     '2022-11-16' => [7, -5, -4],
                 ] as $myDate => $params) {
            $resultDate = date_create_from_format('Y-m-d', $myDate);
            $resultDate->setTime(0, 0, 0);
            $result[] = [
                'message' => 'The fixed related date (' . $myDate . ') is correctly determined. ',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'month' => 12,
                        'day' => 25,
                        'calendar' => 'gregorian',
                        'type' => 'fixedrelated',
                        'secDayCount' => $params[2],
                        'statusCount' => $params[1],
                        'status' => $params[0],
                    ],
                    'startDate' => date_create_from_format('Y-m-d', '2022-12-29'),
                    'addYear' => 0,
                ],
            ];
            $helpDate = date_create_from_format('Y-m-d', '2021-12-29');
            $result[] = [
                'message' => 'The fixed related date (' . $myDate . ') is correctly determined. The startdate ' .
                    'is one year belaow and the parameter addYaer is set to one.',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'month' => 12,
                        'day' => 25,
                        'calendar' => 'gregorian',
                        'type' => 'fixedrelated',
                        'secDayCount' => $params[2],
                        'statusCount' => $params[1],
                        'status' => $params[0],
                    ],
                    'startDate' => $helpDate,
                    'addYear' => 1,
                ],
            ];
        }

        return $result;
    }

    /**
     * @dataProvider dataProviderGetGregorianDateForFixedRelatedTypeBySelectedExamples
     * @test
     */
    public function getGregorianDateForFixedRelatedTypeBySelectedExamples($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $method = self::getPrivateMethod($this->subject, 'getGregorianDateForFixedRelatedType');
            /** @var TimerStartStopRange $result */
            $result = $method->invokeArgs($this->subject,
                [$params['locale'], $params['startDate'], $params['holidayArg'], $params['addYear']]);
            $this->assertEquals($expects['result'], $result->getBeginning(), $message); // whatever your assertion is
        }
    }

    public function dataProviderGetGregorianDateForSeasonTypeBySelectedExamples()
    {
        $result = [];

//        spring  20.3.2023
//        summer 21.6.2023
//        autumn 23.9.2023
//        winter 22.12.2023
//
        $generalResult = date_create_from_format('Y-m-d', '2023-1-2');
        $generalResult->setTime(0, 0, 0);
        foreach ([
                     '2023-03-20' => [1],
                     '2023-06-21' => [2],
                     '2023-09-23' => [3],
                     '2023-12-22' => [4],
                 ] as $myDate => $params) {
            $resultDate = date_create_from_format('Y-m-d', $myDate);
            $resultDate->setTime(0, 0, 0);
            $result[] = [
                'message' => 'The season date (' . $myDate . ') is correctly determined. ',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'calendar' => 'gregorian',
                        'type' => 'season',
                        'status' => $params[0],
                    ],
                    'startDate' => date_create_from_format('Y-m-d', '2023-12-29'),
                    'addYear' => 0,
                ],
            ];
            $helpDate = date_create_from_format('Y-m-d', '2022-12-29');
            $result[] = [
                'message' => 'The season date (' . $myDate . ') is correctly determined. The startdate ' .
                    'is one year belaow and the parameter addYaer is set to one.',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'calendar' => 'gregorian',
                        'type' => 'season',
                        'status' => $params[0],
                    ],
                    'startDate' => $helpDate,
                    'addYear' => 1,
                ],
            ];
        }

        return $result;
    }

    /**
     * @dataProvider dataProviderGetGregorianDateForSeasonTypeBySelectedExamples
     * @test
     */
    public function getGregorianDateForSeasonTypeBySelectedExamples($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $method = self::getPrivateMethod($this->subject, 'getGregorianDateForSeasonType');
            /** @var TimerStartStopRange $result */
            $result = $method->invokeArgs($this->subject,
                [$params['locale'], $params['startDate'], $params['holidayArg'], $params['addYear']]);
            $this->assertEquals($expects['result'], $result->getBeginning(), $message); // whatever your assertion is
        }

    }

    public function dataProviderGetGregorianDateForEasterlyTypeBySelectedExamples()
    {
        $result = [];
//        Ostern 9.4.2023
//    28.5.2023 pfingsten
//
        $generalResult = date_create_from_format('Y-m-d', '2023-1-2');
        $generalResult->setTime(0, 0, 0);
        foreach ([
                     '2023-04-09' => [0],
                     '2023-04-10' => [1],
                     '2023-04-06' => [-3],
                     '2023-05-28' => [49],
                 ] as $myDate => $params) {
            $resultDate = date_create_from_format('Y-m-d', $myDate);
            $resultDate->setTime(0, 0, 0);
            $result[] = [
                'message' => 'The easter-related date (' . $myDate . ') is correctly determined. ',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'calendar' => 'gregorian',
                        'type' => 'easterly',
                        'statusCount' => $params[0],
                    ],
                    'startDate' => date_create_from_format('Y-m-d', '2023-12-29'),
                    'addYear' => 0,
                ],
            ];
            $helpDate = date_create_from_format('Y-m-d', '2022-12-29');
            $result[] = [
                'message' => 'The easter-related date (' . $myDate . ') is correctly determined. ' .
                    'The startdate ' .
                    'is one year belaow and the parameter addYaer is set to one.',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'calendar' => 'gregorian',
                        'type' => 'easterly',
                        'statusCount' => $params[0],
                    ],
                    'startDate' => $helpDate,
                    'addYear' => 1,
                ],
            ];
        }
        return $result;
    }

    /**
     * @dataProvider dataProviderGetGregorianDateForEasterlyTypeBySelectedExamples
     * @test
     */
    public function getGregorianDateForEasterlyTypeBySelectedExamples($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $method = self::getPrivateMethod($this->subject, 'getGregorianDateForEasterlyType');
            /** @var TimerStartStopRange $result */
            $result = $method->invokeArgs($this->subject,
                [$params['locale'], $params['startDate'], $params['holidayArg'], $params['addYear']]);
            $this->assertEquals($expects['result'], $result->getBeginning(), $message); // whatever your assertion is
        }

    }

    public function dataProviderGetGregorianDateForWeekdaylyTypeBySelectedExamples()
    {
        $result = [];
//        3.3.2023 friday
//        10.3.2023 friday
//        17.3.2023 friday
//        24.3.2023 friday
//        31.3.2023 friday
//
        // march 1.3.2023 is a wednesday
        // march the 31.3.2023 is a Saturday
        $generalResult = date_create_from_format('Y-m-d', '2023-1-2');
        $generalResult->setTime(0, 0, 0);
        foreach ([
                     '2023-03-03' => [1, 5],
                     '2023-03-10' => [2, 5],
                     '2023-03-17' => [3, 5],
                     '2023-03-24' => [4, 5],
                     '2023-03-31' => [5, 5],
                     '2023-03-03' => [-5, 5],
                     '2023-03-10' => [-4, 5],
                     '2023-03-17' => [-3, 5],
                     '2023-03-24' => [-2, 5],
                     '2023-03-31' => [-1, 5],
                     '2023-03-08' => [2, 3],
                     '2023-03-09' => [2, 4],
                     '2023-03-10' => [2, 5],
                     '2023-03-11' => [2, 6],
                     '2023-03-12' => [2, 7],
                     '2023-03-13' => [2, 1],
                     '2023-03-14' => [2, 2],
                     '2023-03-10' => [-4, 5],
                     '2023-03-09' => [-4, 4],
                     '2023-03-08' => [-4, 3],
                     '2023-03-07' => [-4, 2],
                     '2023-03-06' => [-4, 1],
                     '2023-03-05' => [-4, 7],
                     '2023-03-04' => [-4, 6],
                 ] as $myDate => $params) {
            $resultDate = date_create_from_format('Y-m-d', $myDate);
            $resultDate->setTime(0, 0, 0);
            $result[] = [
                'message' => 'The related to the n-th weekday in the month date (' . $myDate . ') is correctly determined. ',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'calendar' => 'gregorian',
                        'type' => 'weekdayly',
                        'month' => '3', // 5 = Friday
                        'status' => $params[1], // 5 = Friday
                        'statusCount' => $params[0],
                    ],
                    'startDate' => date_create_from_format('Y-m-d', '2023-12-29'),
                    'addYear' => 0,
                ],
            ];
            $helpDate = date_create_from_format('Y-m-d', '2022-12-29');
            $result[] = [
                'message' => 'The related to the n-th weekday in the month  date (' . $myDate . ') is correctly determined. ' .
                    'The startdate ' .
                    'is one year belaow and the parameter addYaer is set to one.',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'calendar' => 'gregorian',
                        'type' => 'weekdayly',
                        'month' => '3', // 5 = Friday
                        'status' => $params[1], // 5 = Friday
                        'statusCount' => $params[0],
                    ],
                    'startDate' => $helpDate,
                    'addYear' => 1,
                ],
            ];
        }
        return $result;
    }

    /**
     * @dataProvider dataProviderGetGregorianDateForWeekdaylyTypeBySelectedExamples
     * @test
     */
    public function getGregorianDateForWeekdaylyTypeBySelectedExamples($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $method = self::getPrivateMethod($this->subject, 'getGregorianDateForWeekdaylyType');
            /** @var TimerStartStopRange $result */
            $result = $method->invokeArgs($this->subject,
                [$params['locale'], $params['startDate'], $params['holidayArg'], $params['addYear']]);
            $this->assertEquals($expects['result'], $result->getBeginning(), $message); // whatever your assertion is
        }

    }

    public function dataProviderGetGregorianDateForMatarikiTypeBySelectedExamples()
    {
        $result = [];
//        Matariki at         '2023' => '2023-7-14', or         '2027' => '2027-6-25',

        $generalResult = date_create_from_format('Y-m-d', '2023-1-2');
        $generalResult->setTime(0, 0, 0);
        foreach ([
                     '2023-07-14' => 2023,
                     '2027-06-25' => 2027,
                 ] as $myDate => $year) {
            $resultDate = date_create_from_format('Y-m-d', $myDate);
            $resultDate->setTime(0, 0, 0);
            $result[] = [
                'message' => 'The matariki-related date (' . $myDate . ') is correctly determined. ',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'calendar' => 'gregorian',
                        'type' => 'matariki',
                    ],
                    'startDate' => date_create_from_format('Y-m-d', $year . '-12-29'),
                    'addYear' => 0,
                ],
            ];
            $helpDate = date_create_from_format('Y-m-d', ($year - 1) . '-12-29');
            $result[] = [
                'message' => 'The matariki-related date (' . $myDate . ') is correctly determined. ' .
                    'The startdate ' .
                    'is one year belaow and the parameter addYaer is set to one.',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'calendar' => 'gregorian',
                        'type' => 'matariki',
                    ],
                    'startDate' => $helpDate,
                    'addYear' => 1,
                ],
            ];
        }
        return $result;
    }

    /**
     * @dataProvider dataProviderGetGregorianDateForMatarikiTypeBySelectedExamples
     * @test
     */
    public function getGregorianDateForMatarikiTypeBySelectedExamples($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $method = self::getPrivateMethod($this->subject, 'getGregorianDateForMatarikiType');
            /** @var TimerStartStopRange $result */
            $result = $method->invokeArgs($this->subject,
                [$params['locale'], $params['startDate'], $params['holidayArg'], $params['addYear']]);
            $this->assertEquals($expects['result'], $result->getBeginning(), $message); // whatever your assertion is
        }

    }

    public function dataProviderGetGregorianDateForMoonInMonthTypeBySelectedExamples()
    {
        $result = [];
        // 01.12.2024 	1. Neumond Dezember 2024 	Deutschland
        //15.12.2024 	Vollmond Dezember 2024 	Deutschland
        //30.12.2024 	2. Neumond Dezember 2024 	Deutschland
        //01.05.2026 	1. Vollmond Mai 2026 	Deutschland
        //16.05.2026 	Neumond Mai 2026 	Deutschland
        //31.05.2026 	2. Vollmond Mai 2026 (Blue Moon) 	Deutschland
        //15.06.2026 	Neumond Juni 2026 	Deutschland
//        Matariki at         '2023' => '2023-7-14', or         '2027' => '2027-6-25',

        $generalResult = date_create_from_format('Y-m-d', '2023-1-2');
        $generalResult->setTime(0, 0, 0);
        foreach ([
                     '2024-12-01' => [12, 0, 1, 2024],
//                     '2024-12-30' => [12,0,2,2024],
                     '2026-05-01' => [5, 2, 1, 2026],
//                     '2026-05-31' => [5,2,2,2026],
                 ] as $myDate => $params) {
            $resultDate = date_create_from_format('Y-m-d', $myDate);
            $resultDate->setTime(0, 0, 0);
            $result[] = [
                'message' => 'The moon-in-month-related date (' . $myDate . ') is correctly determined. ',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'calendar' => 'gregorian',
                        'type' => 'mooninmonth',
                        'month' => $params[0],
                        'status' => $params[1],
                        'statusCount' => $params[2],
                    ],
                    'startDate' => date_create_from_format('Y-m-d', $params[3] . '-12-29'),
                    'addYear' => 0,
                ],
            ];
            $helpDate = date_create_from_format('Y-m-d', ($params[3] - 1) . '-12-29');
            $result[] = [
                'message' => 'The moon-in-month-related date (' . $myDate . ') is correctly determined. ' .
                    'The startdate ' .
                    'is one year belaow and the parameter addYaer is set to one.',
                'expects' => [
                    'result' => $resultDate,
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'holidayArg' => [
                        'calendar' => 'gregorian',
                        'type' => 'mooninmonth',
                        'month' => $params[0],
                        'status' => $params[1],
                        'statusCount' => $params[2],
                    ],
                    'startDate' => $helpDate,
                    'addYear' => 1,
                ],
            ];
        }
        return $result;
    }

    /**
     * @dataProvider dataProviderGetGregorianDateForMoonInMonthTypeBySelectedExamples
     * @test
     */
    public function getGregorianDateForMoonInMonthTypeBySelectedExamples($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $method = self::getPrivateMethod($this->subject, 'getGregorianDateForMoonInMonthType');
            /** @var TimerStartStopRange $result */
            $result = $method->invokeArgs($this->subject,
                [$params['locale'], $params['startDate'], $params['holidayArg'], $params['addYear']]);
            $this->assertEquals($expects['result'], $result->getBeginning(), $message); // whatever your assertion is
        }

    }

}
