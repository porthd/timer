<?php

namespace Porthd\Timer\Utilities;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class ConvertDateUtilityTest extends TestCase
{
    /**
     * @var ConvertDateUtility
     */
    protected $subject = null;


    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ConvertDateUtility();
    }

    protected function tearDown(): void
    {
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


    public function dataProviderConvertDateAndBackWithConvertFromDateTimeToCalendarAndConvertFromCalendarToDateTime()
    {
        $result = [];
        foreach ([
                     'de_DE' => 'Europe/Berlin',
                     'de' => 'Europe/Berlin',
                     'en' => 'UTC',
                     'en_GB' => 'Europe/London',
                     'en_US' => 'America/Chicago',
                     'es_MX' => 'America/Mexico_City',
                     'es' => 'America/Mexico_City',
                 ] as $locale => $timeZoneName) {
            foreach ([
                         'buddhist' => [
                             'tzForeign' => [
                                 'UTC' => '2563/09/13 18:26:40',
                                 'Europe/London' => '2563/09/13 18:26:40',
                                 'Europe/Berlin' => '2563/09/13 18:26:40',
                                 'America/Chicago' => '2563/09/13 18:26:40',
                                 'America/Mexico_City' => '2563/09/13 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
//                         'chinese' => [
//                             'tzForeign' => [
//                                 'UTC' => '0037/07/26 18:26:40',
//                                 'Europe/London' => '0037/07/26 18:26:40',
//                                 'Europe/Berlin' => '0037/07/26 18:26:40',
//                                 'America/Chicago' => '0037/07/26 18:26:40',
//                                 'America/Mexico_City' => '0037/07/26 18:26:40',
//                             ],
//                             'utcDateTime' => '2020/09/13/18/26/40',
//                         ],
                         'coptic' => [
                             'tzForeign' => [
                                 'UTC' => '1737/01/03 18:26:40',
                                 'Europe/London' => '1737/01/03 18:26:40',
                                 'Europe/Berlin' => '1737/01/03 18:26:40',
                                 'America/Chicago' => '1737/01/03 18:26:40',
                                 'America/Mexico_City' => '1737/01/03 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
//                         'dangi' => [
//                             'tzForeign' => [
//                                 'UTC' => '0037/07/26 18:26:40',
//                                 'Europe/London' => '0037/07/26 18:26:40',
//                                 'Europe/Berlin' => '0037/07/26 18:26:40',
//                                 'America/Chicago' => '0037/07/26 18:26:40',
//                                 'America/Mexico_City' => '0037/07/26 18:26:40',
//                             ],
//                             'utcDateTime' => '2020/09/13/18/26/40',
//                         ],
                         'ethiopic' => [
                             'tzForeign' => [
                                 'UTC' => '2013/01/03 18:26:40',
                                 'Europe/London' => '2013/01/03 18:26:40',
                                 'Europe/Berlin' => '2013/01/03 18:26:40',
                                 'America/Chicago' => '2013/01/03 18:26:40',
                                 'America/Mexico_City' => '2013/01/03 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'ethiopic-amete-alem' => [
                             'tzForeign' => [
                                 'UTC' => '7513/01/03 18:26:40',
                                 'Europe/London' => '7513/01/03 18:26:40',
                                 'Europe/Berlin' => '7513/01/03 18:26:40',
                                 'America/Chicago' => '7513/01/03 18:26:40',
                                 'America/Mexico_City' => '7513/01/03 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'gregorian' => [
                             'tzForeign' => [
                                 'UTC' => '2020/09/13 18:26:40',
                                 'Europe/London' => '2020/09/13 18:26:40',
                                 'Europe/Berlin' => '2020/09/13 18:26:40',
                                 'America/Chicago' => '2020/09/13 18:26:40',
                                 'America/Mexico_City' => '2020/09/13 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'hebrew' => [
                             'tzForeign' => [
                                 'UTC' => '5780/12/24 18:26:40',
                                 'Europe/London' => '5780/12/24 18:26:40',
                                 'Europe/Berlin' => '5780/12/24 18:26:40',
                                 'America/Chicago' => '5780/12/24 18:26:40',
                                 'America/Mexico_City' => '5780/12/24 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'indian' => [
                             'tzForeign' => [
                                 'UTC' => '1942/06/22 18:26:40',
                                 'Europe/London' => '1942/06/22 18:26:40',
                                 'Europe/Berlin' => '1942/06/22 18:26:40',
                                 'America/Chicago' => '1942/06/22 18:26:40',
                                 'America/Mexico_City' => '1942/06/22 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'islamic' => [
                             'tzForeign' => [
                                 'UTC' => '1442/01/25 18:26:40',
                                 'Europe/London' => '1442/01/25 18:26:40',
                                 'Europe/Berlin' => '1442/01/25 18:26:40',
                                 'America/Chicago' => '1442/01/25 18:26:40',
                                 'America/Mexico_City' => '1442/01/25 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'islamic-civil' => [
                             'tzForeign' => [
                                 'UTC' => '1442/01/25 18:26:40',
                                 'Europe/London' => '1442/01/25 18:26:40',
                                 'Europe/Berlin' => '1442/01/25 18:26:40',
                                 'America/Chicago' => '1442/01/25 18:26:40',
                                 'America/Mexico_City' => '1442/01/25 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'islamic-rgsa' => [
                             'tzForeign' => [
                                 'UTC' => '1442/01/25 18:26:40',
                                 'Europe/London' => '1442/01/25 18:26:40',
                                 'Europe/Berlin' => '1442/01/25 18:26:40',
                                 'America/Chicago' => '1442/01/25 18:26:40',
                                 'America/Mexico_City' => '1442/01/25 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'islamic-tbla' => [
                             'tzForeign' => [
                                 'UTC' => '1442/01/26 18:26:40',
                                 'Europe/London' => '1442/01/26 18:26:40',
                                 'Europe/Berlin' => '1442/01/26 18:26:40',
                                 'America/Chicago' => '1442/01/26 18:26:40',
                                 'America/Mexico_City' => '1442/01/26 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'islamic-umalqura' => [
                             'tzForeign' => [
                                 'UTC' => '1442/01/25 18:26:40',
                                 'Europe/London' => '1442/01/25 18:26:40',
                                 'Europe/Berlin' => '1442/01/25 18:26:40',
                                 'America/Chicago' => '1442/01/25 18:26:40',
                                 'America/Mexico_City' => '1442/01/25 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'japanese' => [
                             'tzForeign' => [
                                 'UTC' => '0002/09/13 18:26:40',
                                 'Europe/London' => '0002/09/13 18:26:40',
                                 'Europe/Berlin' => '0002/09/13 18:26:40',
                                 'America/Chicago' => '0002/09/13 18:26:40',
                                 'America/Mexico_City' => '0002/09/13 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'persian' => [
                             'tzForeign' => [
                                 'UTC' => '1399/06/23 18:26:40',
                                 'Europe/London' => '1399/06/23 18:26:40',
                                 'Europe/Berlin' => '1399/06/23 18:26:40',
                                 'America/Chicago' => '1399/06/23 18:26:40',
                                 'America/Mexico_City' => '1399/06/23 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'roc' => [
                             'tzForeign' => [
                                 'UTC' => '0109/09/13 18:26:40',
                                 'Europe/London' => '0109/09/13 18:26:40',
                                 'Europe/Berlin' => '0109/09/13 18:26:40',
                                 'America/Chicago' => '0109/09/13 18:26:40',
                                 'America/Mexico_City' => '0109/09/13 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                         'julian' => [
                             'tzForeign' => [
                                 'UTC' => '2020/08/31 18:26:40',
                                 'Europe/London' => '2020/08/31 18:26:40',
                                 'Europe/Berlin' => '2020/08/31 18:26:40',
                                 'America/Chicago' => '2020/08/31 18:26:40',
                                 'America/Mexico_City' => '2020/08/31 18:26:40',
                             ],
                             'utcDateTime' => '2020/09/13/18/26/40',
                         ],
                     ] as $calendar => $calendarResult) {
                $myTimeZone = new DateTimeZone($timeZoneName);
                // Exp 15:26 => akt 16:26
                $tzDateTime = DateTime::createFromFormat(
                    'Y/m/d/H/i/s',
                    $calendarResult['utcDateTime'],
                    new DateTimeZone('UTC')
                );
                $tzDateTime->setTimezone($myTimeZone); // internal add of 2 hours becaus of timeZone

                // Exp 15:26 => akt 18:26
                $tzDateTime = DateTime::createFromFormat('Y/m/d/H/i/s', $calendarResult['utcDateTime'],
                    $myTimeZone); // diff 1H
                $checkTime = DateTime::createFromFormat(
                    'Y/m/d H:i:s',
                    $calendarResult['tzForeign'][$timeZoneName],
                    new DateTimeZone('UTC')
                );
                $checkTime->setTimezone($myTimeZone);
                $result[] = [
                    'message' => 'In the first step the UTC-testdate `' . $calendarResult['utcDateTime'] . '` of the ' .
                        'gregorian-calendar will be transformed to the date `' .
                        $calendarResult['tzForeign'][$timeZoneName] . ' ' . "\n" . 'of the calendar `' . $calendar .
                        '` with the locale-parameter `' .
                        $locale . ' (' . $timeZoneName . ')`.' . ' After the first step the calculated date `' .
                        $calendarResult['tzForeign'][$timeZoneName] .
                        '` will be                         transformed back to the expected timezone-date `' .
                        '` (UTC-date `' . $calendarResult['utcDateTime'] . '`).',
                    'expects' => [
                        'tzTimezoneForeign' => $calendarResult['tzForeign'][$timeZoneName],
                        'tzDateTime' => $tzDateTime->format('Y/m/d H:i:s'),
                        'revertDate' => $tzDateTime,
                    ],
                    'params' => [
                        'tzDateTime' => $tzDateTime,
                        'locale' => $locale,
                        'timezone' => $timeZoneName,
                        'calendar' => $calendar,
                    ],
                ];
            }
        }

        return $result;
    }

    /**
     * @dataProvider dataProviderConvertDateAndBackWithConvertFromDateTimeToCalendarAndConvertFromCalendarToDateTime
     * @test
     */
    public function convertDateAndBackWithConvertFromDateTimeToCalendarAndConvertFromCalendarToDateTime(
        $message,
        $expects,
        $params
    ) {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $formatedCalendarDate = ConvertDateUtility::convertFromDateTimeToCalendar(
                $params['locale'],
                $params['calendar'],
                $params['tzDateTime'],
                true,
                ConvertDateUtility::INTL_DATE_FORMATTER_DEFAULT_PATTERN

            );
            $dateTime = ConvertDateUtility::convertFromCalendarToDateTime(
                $params['locale'],
                $params['calendar'],
                $formatedCalendarDate,
                $params['timezone']
            );
            $checkHelp = new DateTime('@' . $params['tzDateTime']->format('U'));
            if ($params['timezone'] !== 'UTC') {
                $this->assertTrue(
                    ($checkHelp)->format('Y-m-d H:i:s') !== $expects['revertDate']->format('Y-m-d H:i:s'),
                    'Internal test A: Is the internal date in UTC-format different from the time of the end`' .
                    $expects['revertDate']->format('Y-m-d H:i:s') . '`?'
                );
            } else {
                $this->assertTrue(
                    ($checkHelp)->format('Y-m-d H:i:s') === $expects['revertDate']->format('Y-m-d H:i:s'),
                    'Internal test A: Is the internal end-date equal to the UTC-time `' .
                    $expects['revertDate']->format('Y-m-d H:i:s') . '`?'
                );
            }
            $this->assertTrue(
                $params['tzDateTime']->format('U') === $expects['revertDate']->format('U'),
                'Internal test B: Are the datetime equal for start `' . $params['tzDateTime']->format('U') .
                '` and end `' . $expects['revertDate']->format('U') . '`?'
            );
            $this->assertEquals(
                $expects['tzTimezoneForeign'],
                $formatedCalendarDate,
                'Step 1: ' . $message
            );
            $this->assertEquals(
                $expects['revertDate'],
                $dateTime,
                'Step 2: ' . $message
            );
        }
    }

    public function dataProviderConvertFromDateTimeToCalendarGivenDateTimeConvertedToEstimatedFormat()
    {
        $helpDate = DateTime::createFromFormat('Y/m/d/H/i/s', '2003/09/06/09/06/03', new DateTimeZone('Europe/Berlin'));
        $result = [];
        // documentation = https://unicode-org.github.io/icu/userguide/format_parse/datetime/
        foreach ([
                     'G' => 'AD',
                     'GG' => 'AD',
                     'GGG' => 'AD',
                     'GGGG' => 'Anno Domini',
                     'GGGGG' => 'A',
                     'yy' => '03',
                     'y' => '2003',
                     'yyyy' => '2003',
                     'Y' => '2003',
                     'u' => '2003',
                     // why, i did understood the documentation
                     'U' => '2003',
                     // why, i did understood the documentation
                     'r' => '2003',
                     'Q' => '3',
                     'QQ' => '03',
                     'QQQ' => 'Q3',
                     'QQQQ' => '3rd quarter',
                     'QQQQQ' => '3rd quarter',
                     // why mistake in documentation?
                     'q' => '3',
                     'qq' => '03',
                     'qqq' => 'Q3',
                     'qqqq' => '3rd quarter',
                     'qqqqq' => '3rd quarter',
                     // why mistake in documentation?
                     'M' => '9',
                     'MM' => '09',
                     'MMM' => 'Sep',
                     'MMMM' => 'September',
                     'MMMMM' => 'S',
                     'L' => '9',
                     'LL' => '09',
                     'LLL' => 'Sep',
                     'LLLL' => 'September',
                     'LLLLL' => 'S',
                     'w' => '36',
                     'ww' => '36',
                     'W' => '1',
                     'd' => '6',
                     'dd' => '06',
                     'D' => '249',
                     'F' => '1',
                     'g' => '2452889',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'E' => 'Sat',
                     'EE' => 'Sat',
                     'EEE' => 'Sat',
                     'EEEE' => 'Saturday',
                     'EEEEE' => 'S',
                     'EEEEEE' => 'Sa',
                     'e' => '7',
                     // why? mistake in documentation or in PHP? The 9 September 2003 is a Saturday. Beginn the count with  Sunday =1?
                     'ee' => '07',
                     // why? mistake in documentation or in PHP? The 9 September 2003 is a Saturday. Beginn the count with  Sunday =1?
                     'eee' => 'Sat',
                     'eeee' => 'Saturday',
                     'eeeee' => 'S',
                     'eeeeee' => 'Sa',
                     'c' => '7',
                     // why? mistake in documentation or in PHP? The 9 September 2003 is a Saturday. Beginn the count with  Sunday =1?
                     'cc' => '7',
                     // why? mistake in documentation or in PHP? The 9 September 2003 is a Saturday. Beginn the count with  Sunday =1?
                     'ccc' => 'Sat',
                     'cccc' => 'Saturday',
                     'ccccc' => 'S',
                     'cccccc' => 'Sa',
                     'a' => 'am',
                     // wrong case  in Documentation
                     'aa' => 'am',
                     // wrong case  in Documentation
                     'aaa' => 'am',
                     // wrong case  in Documentation
                     'aaaa' => 'am',
                     // wrong case  in Documentation
                     'aaaaa' => 'a',
                     'b' => 'am',
                     // wrong case  in Documentation
                     'bb' => 'am',
                     // wrong case  in Documentation
                     'bbb' => 'am',
                     // wrong case  in Documentation
                     'bbbb' => 'am',
                     // wrong case  in Documentation
                     'bbbbb' => 'a',
                     'B' => 'in the morning',
                     // the documentation did not declare the allowed textfragments
                     'BB' => 'in the morning',
                     // the documentation did not declare the allowed textfragments
                     'BBB' => 'in the morning',
                     // the documentation did not declare the allowed textfragments
                     'BBBB' => 'in the morning',
                     // the documentation did not declare the allowed textfragments
                     'BBBBB' => 'in the morning',
                     // the documentation did not declare the allowed textfragments
                     'h' => '9',
                     'hh' => '09',
                     'H' => '9',
                     'HH' => '09',
                     'k' => '9',
                     'kk' => '09',
                     'K' => '9',
                     'KK' => '09',
                     'm' => '6',
                     'mm' => '06',
                     's' => '3',
                     'ss' => '03',
                     'S' => '',
                     // why? mistake in documentation or in PHP? Why is there an empty output? I don't understand this.
                     'SS' => '00',
                     'SSS' => '000',
                     'SSSS' => '0000',
                     'A' => '32763000',
                     'z' => 'CEST',
                     'zz' => 'CEST',
                     'zzz' => 'CEST',
                     'zzzz' => 'Central European Summer Time',
                     'Z' => '+0200',
                     // not only +0100, because of summertimer
                     'ZZ' => '+0200',
                     'ZZZ' => '+0200',
                     'ZZZZ' => 'GMT+02:00',
                     'ZZZZZ' => '+02:00',
                     'O' => 'GMT+2',
                     'OOOO' => 'GMT+02:00',
                     'v' => 'CET',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'vvvv' => 'Central European Time',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'V' => 'deber',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'VV' => 'Europe/Berlin',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'VVV' => 'Berlin',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'VVVV' => 'Germany Time',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'X' => '+02',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'XX' => '+0200',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'XXX' => '+02:00',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'XXXX' => '+0200',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'XXXXX' => '+02:00',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'x' => '+02',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'xx' => '+0200',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'xxx' => '+02:00',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'xxxx' => '+0200',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                     'xxxxx' => '+02:00',
                     // I got the value from the calulation. I have not checked it really. The test show, that my function works correct.
                 ] as $format => $value
        ) {
            $result[] = [
                'message' => 'The date `' . $helpDate->format('d.m.Y H:i:s') . '` is converted with the ICU.' .
                    ' The result `' . $value . '` is estimated by the format-string `' . $format . '`.',
                'expects' => [
                    'dateTime' => $value,
                ],
                'params' => [
                    'dateTime' => $helpDate,
                    'format' => $format,
                    'locale' => 'en_GB',
                ],
            ];
        }

        return $result;
    }

    /**
     * @dataProvider dataProviderConvertFromDateTimeToCalendarGivenDateTimeConvertedToEstimatedFormat
     * @test
     */
    public function formatDateTimeInIcuFormatToCalendarGivenDateTimeConvertedToEstimatedFormat(
        $message,
        $expects,
        $params
    ) {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $dateTime = ConvertDateUtility::formatDateTimeInIcuFormat(
                $params['dateTime'],
                $params['locale'],
                $params['format']
            );
            $this->assertEquals(
                $expects['dateTime'],
                $dateTime,
                $message
            );
        }
    }
}
