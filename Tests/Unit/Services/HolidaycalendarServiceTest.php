<?php

namespace Porthd\Timer\Services;

use DateInterval;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
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
                    'message' => 'The fixed christmas date is detected correctly for the locale `' . $locale . '`.' .
                        'the other paremeters are startdate ' . $startDatePre->format('d.m.Y') . ', addYear (' .
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

            $this->assertEquals($expects['result'], $result, $message); // whatever your assertion is
        }

    }

    public function dataProviderGetGregorianDateForFixedWeekendTypeBySelectedExamples()
    {
        $result = [];

        $christmasArgGregorian = [
            'month' => 12,
            'day' => 25,
            'calendar' => 'gregorian',
            'type' => 'fixedweekend',
            'status' => '1',
        ];
        $boxingArgGregorian = [
            'month' => 12,
            'day' => 26,
            'calendar' => 'gregorian',
            'type' => 'fixedweekend',
            'status' => '-1',
        ];
        $testingArgGregorian = [
            'month' => 12,
            'day' => 19,
            'calendar' => 'gregorian',
            'type' => 'fixedweekend',
            'status' => '0',
        ];
        //        26.12.2020 = saturday
        //        26.12.2021 = sunday
        //        26.12.2022 = monday
        //        26.12.2023 = tuesday
        foreach ([
                     2019 => ['2019-12-25', '2019-12-26', '2019-12-19'],
                     2020 => ['2020-12-25', '2020-12-28', '2020-12-21'],
                     2021 => ['2021-12-27', '2021-12-28', '2021-12-20'],
                     2022 => ['2022-12-27', '2022-12-26', '2022-12-19'],
                     2023 => ['2023-12-25', '2023-12-26', '2023-12-19'],
                 ] as $year => $list) {
            $result[] = [
                'message' => 'The fixed first christmas date is detected correctly for the fixed locale `de_DE`. ' .
                    'The other paremeters are startdate 25.12.' . $year . ' will cause with the flag the holiday ' . $list[0] . '.',
                'expects' => [
                    'result' => DateTime::createFromFormat('Y-m-d H:i:s', $list[0] . ' 00:00:00'),
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'startDate' => DateTime::createFromFormat('Y-m-d H:i:s', $year . '-12-25 00:00:00'),
                    'holidayArg' => $christmasArgGregorian,
                    'addYear' => 0,
                ],
            ];
            $result[] = [
                'message' => 'The fixed first christmas date is detected correctly for the fixed locale `de_DE`. ' .
                    'The other paremeters are startdate 26.12.' . $year . ' will cause with the flag the holiday ' . $list[1] . '.',
                'expects' => [
                    'result' => DateTime::createFromFormat('Y-m-d H:i:s', $list[1] . ' 00:00:00'),
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'startDate' => DateTime::createFromFormat('Y-m-d H:i:s', $year . '-12-26 00:00:00'),
                    'holidayArg' => $boxingArgGregorian,
                    'addYear' => 0,
                ],
            ];
            $result[] = [
                'message' => 'The fixed first christmas date is detected correctly for the fixed locale `de_DE`. ' .
                    'The other paremeters are startdate 19.12.' . $year . ' will cause with the flag the holiday ' . $list[1] . '.',
                'expects' => [
                    'result' => DateTime::createFromFormat('Y-m-d H:i:s', $list[2] . ' 00:00:00'),
                ],
                'params' => [
                    'locale' => 'de_DE',
                    'startDate' => DateTime::createFromFormat('Y-m-d H:i:s', $year . '-12-19 00:00:00'),
                    'holidayArg' => $testingArgGregorian,
                    'addYear' => 0,
                ],
            ];
        }

        return $result;
    }

    /**
     * @dataProvider dataProviderGetGregorianDateForFixedWeekendTypeBySelectedExamples
     * @test
     */
    public function getGregorianDateForFixedWeekendTypeBySelectedExamples($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {

            $method = self::getPrivateMethod($this->subject, 'getGregorianDateForFixedWeekendType');

            $result = $method->invokeArgs($this->subject,
                [$params['locale'], $params['startDate'], $params['holidayArg'], $params['addYear']]);

            $this->assertEquals($expects['result'], $result, $message); // whatever your assertion is
        }

    }


}
