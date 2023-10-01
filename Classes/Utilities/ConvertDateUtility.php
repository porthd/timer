<?php
declare(strict_types=1);

namespace Porthd\Timer\Utilities;

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

use DateTime;
use DateTimeZone;
use Exception;
use IntlCalendar;
use IntlDateFormatter;
use IntlGregorianCalendar;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Exception\TimerException;
use ResourceBundle;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class Calendar Conversion.
 * influenced by
 * - get basic information https://stackoverflow.com/questions/32540529/get-list-of-calendars-timezones-locales-in-php-icu-intldateformatter/68690406#68690406
 * - first hint about documentation-problems https://stackoverflow.com/questions/43332341/php-intl-can-convert-a-gregorian-date-to-other-calendar-type
 * - german text https://runebook.dev/de/docs/php/intlcalendar.fromdatetime
 * - example for usage of format https://stackoverflow.com/questions/8744952/formatting-datetime-object-respecting-localegetdefault
 * - helpful for my refactoring https://www.php.de/forum/webentwicklung/php-fortgeschrittene/1607278-chinesisches-datum-westliches-datum-fehlerhaft-falsche-konfiguriert-oder-php-bug
 *   thanks to `hellbringer`(https://www.php.de/member/42971-hellbringer)
 * - helpful for understanding my problem: https://github.com/php/doc-en/issues/2246
 *   thanks to `cmb69` (https://github.com/cmb69) for his/her hints
 *
 */
class ConvertDateUtility
{
    public const DEFECT_INTL_DATE_FORMATTER_LIST = [
        'chinese',
        'dangi',
    ];

    public const DEFAULT_CALENDAR = 'gregorian';
    public const DEFAULT_JULIAN_CALENDAR = 'julian';
    public const DEFAULT_HEBREW_CALENDAR = 'hebrew';

    public const DEFAULT_LOCALE = 'en';
    protected const MAP_ESCAPE_CHAR = '\\';
    protected const MAP_TRANSFORM_STRFTIME_TO_DATETIME_FORMAT = [
        '%a' => 'D',
        '%A' => 'l',
        '%d' => 'd',
        '%e' => 'j',
        '%j' => '-z-increment',
        '%u' => 'N',
        '%w' => 'w',
        '%U' => '-weekspecial',
        '%V' => 'W',
        '%W' => '-weekspecial2',
        '%b' => 'M',
        '%B' => 'F',
        '%h' => '-locale-M',
        '%m' => 'm',
        '%C' => '-centuryNumber',
        '%g' => '-o-reduceTo2Digits',
        '%G' => 'o',
        '%y' => 'y',
        '%Y' => 'Y',
        '%H' => 'H',
        '%k' => 'G',
        '%I' => 'h',
        '%l' => 'g',
        '%M' => 'i',
        '%p' => 'A',
        '%P' => 'a',
        '%r' => 'h:i:s A',
        '%R' => 'H:i',
        '%S' => 's',
        '%T' => 'H:i:s',
        '%X' => '-locale-H.i.s',
        '%z' => 'O',
        '%Z' => 'e',
        '%c' => '-locale-m.d.y_H.i.s',
        '%D' => 'm/d/y',
        '%F' => 'Y-m-d',
        '%s' => 'U',
        '%x' => '-locale-m.d.y',
        '%n' => '-return',
        '%t' => '-tab',
//        '%%'=>'\%',  // use explode, to mask this code in the mapping
    ];
    protected const MAP_TRANSFORM_ICU_FORMAT_TO_PHP_FORMAT = [
        'dd' => 'd',
        'E' => 'D',
        'd' => 'j',
        'EEEE' => 'l',
        'e' => 'N',
        'D' => 'z',
        'w' => 'W',
        'MMMM' => 'F',
        'MM' => 'm',
        'MMM' => 'M',
        'M' => 'n',
        'Y' => 'o',
        'yyyy' => 'Y',
        'yy' => 'y',
        'bbbbb' => 'a',
        'aa' => 'A',
        'h' => 'g',
        'H' => 'G',
        'hh' => 'h',
        'HH' => 'H',
        'mm' => 'i',
        'ss' => 's',
        'A' => 'u',
        'SSS' => 'v',
        'vvvv' => 'e',
        'Z' => 'O',
        'ZZZ' => 'P',
        'ZZ' => 'p',
        'v' => 'T',
    ];
    protected const MAP_TRANSFORM_PHP_FORMAT_TO_EMPTY = [
        'S',
        'w',
        't',
        'L',
        'X',
        'x',
        'B',
        'I',
        'Z',
        'c',
        'r',
        'U',
    ];
    public const INTL_DATE_FORMATTER_DEFAULT_PATTERN = 'yyyy/MM/dd HH:mm:ss';
    public const PHP_DATE_FORMATTER_DEFAULT_PATTERN = 'Y/m/d H:i:s';
    public const INTL_DATE_FORMATTER_DEFAULT_LENGTH = 19;
    /**
     * @var string[]
     */
    protected static $calendars = [];
    /**
     * @var string[]
     */
    protected static $locales = [];

    /**
     * @return string[]
     */
    protected static function getAllCalendars(): array
    {
        if (empty(self::$calendars)) {
            $bundle = new ResourceBundle('', 'ICUDATA');
            $calendarList = $bundle->get('calendar');
            foreach ($calendarList as $n => $v) {
                self::$calendars[] = $n;
            }
        }
        return self::$calendars;
    }

    /**
     * @return string[]
     */
    protected static function getAllLocales(): array
    {
        if (empty(self::$locales)) {
            /** @var ResourceBundle $bundle */
            $bundle = new ResourceBundle('', 'ICUDATA');
            self::$locales = $bundle->getLocales('');
        }
        return self::$locales;
    }

    /**
     * @param string $locale
     * @param string $calendar
     * @param string $timeZoneName
     * @return void
     * @throws TimerException
     */
    public static function allowedLocaleCalendarTimezone(string $locale, string $calendar, string $timeZoneName)
    {
        if ((!in_array($calendar, self::getAllCalendars())) ||
            (!in_array($locale, self::getAllLocales())) ||
            (!self::isValidTimezoneId($timeZoneName))
        ) {
            throw new TimerException(
                'The needed calandar `' . $calendar . '`. ' . 'Or the needed locale `' . $locale . '` is not defined. ' .
                '$Or the needed timeZone `' . $timeZoneName . '` is not defined. ' .
                'Check for type error or typecast errors. ' .
                ' Otherwise make a screenshot and inform the webmaster.' . "\n" .
                'Allowed Calendars: (' . implode(',', self::$calendars) . ')' . "\n" .
                'Allowed locales: (' . implode(',', self::$locales) . ')' . "\n" .
                'Allowed Timezones: (' . implode(',', DateTimeZone::listIdentifiers()) . ')',
                1673710564
            );
        }

    }
    /**
     * @param string $locale
     * @param string $calendar
     * @param string $formatedDateIcuYYYYSlashMMSlashddSpaceHHColonmmColonss
     * @param string $timeZoneName
     * @return DateTime
     * @throws TimerException
     */
    public static function convertFromCalendarToDateTime(
        string $locale,
        string $calendar,
        string $formatedDateIcuYYYYSlashMMSlashddSpaceHHColonmmColonss,
        string $timeZoneName = TimerConst::INTERNAL_TIMEZONE
    ): DateTime {

        if ($calendar === TimerConst::ADDITIONAL_CALENDAR_JULIAN) {
            $calendar = TimerConst::FAKE_CALENDAR_JULIAN_BY_GREGORIAN;
            $list = explode(' ', $formatedDateIcuYYYYSlashMMSlashddSpaceHHColonmmColonss);
            [$year, $month, $day] = array_map(
                'intval',
                explode('/', $list[0])
            );

            $julianDay = juliantojd(
                (int)$month,
                (int)$day,
                (int)$year
            );
            $monthDayYear = jdtogregorian($julianDay);
            [$month, $day, $year] = array_map('intval', explode('/', $monthDayYear));
            $day = str_pad(((string)$day), 2, '0', STR_PAD_LEFT);
            $month = str_pad(((string)$month), 2, '0', STR_PAD_LEFT);
            if (abs($year) < 10000) {
                $year = str_pad(((string)abs($year)), 4, '0', STR_PAD_LEFT);
            } else {
                $year = (string)$year;
            }
            $list[0] = implode('/', [$year, $month, $day]);
            $formatedDateIcuYYYYSlashMMSlashddSpaceHHColonmmColonss = implode(' ', $list);
        }
        $timeZone = new DateTimeZone($timeZoneName);
        $traditionalFormatter = new IntlDateFormatter(
            $locale . '@calendar=' . $calendar,
            IntlDateFormatter::SHORT,
            IntlDateFormatter::SHORT,
            $timeZone,
            IntlDateFormatter::TRADITIONAL,
            self::INTL_DATE_FORMATTER_DEFAULT_PATTERN
        );

        $parsedTimestamp = $traditionalFormatter->parse($formatedDateIcuYYYYSlashMMSlashddSpaceHHColonmmColonss);
        if ($parsedTimestamp === false) {
            throw new TimerException(
                'The IntlDateFormatter give be an false. The date `' .
                $formatedDateIcuYYYYSlashMMSlashddSpaceHHColonmmColonss . '` could not be recognized.' .
                ' Perhaps datestring did not fullfill the format `year/month/day hour:minute:second`.' .
                ' Make a screenshot and inform the webmaster.',
                1675003765
            );
        }
        $dateTime = new DateTime('@' . ((int)$parsedTimestamp));
        $dateTime->setTimezone(new DateTimeZone($timeZoneName));
        return $dateTime;
    }

    /**
     * @param DateTime $dateTime
     * @param string $locale
     * @param string $format
     * @return string
     */
    public static function formatDateTimeInIcuFormat(
        DateTime $dateTime,
        string $locale,
        string $format
    ): string {
        // format a gregorian-time by ICU
        $cal = IntlCalendar::fromDateTime($dateTime);
        return (IntlDateFormatter::formatObject($cal, $format, $locale) ?: '');
    }


    /**
     * @param string $locale
     * @param string $calendar
     * @param DateTime $dateTime
     * @param bool $flagFormat
     * @param string $icuFormat
     * @return string
     */
    public static function convertFromDateTimeToCalendar(
        string $locale,
        string $calendar,
        DateTime $dateTime,
        bool $flagFormat = true,
        string $icuFormat = self::INTL_DATE_FORMATTER_DEFAULT_PATTERN
    ): string {
        $myDate = clone $dateTime;
        if ($calendar === TimerConst::ADDITIONAL_CALENDAR_JULIAN) {
            $calendar = TimerConst::FAKE_CALENDAR_JULIAN_BY_GREGORIAN;
            $julianDay = gregoriantojd(
                (int)$myDate->format('m'),
                (int)$myDate->format('d'),
                (int)$myDate->format('Y')
            );
            $monthDayYear = jdtojulian($julianDay);
            [$month, $day, $year] = explode('/', $monthDayYear);
            $myDate->setDate(
                ((int)$year),
                ((int)$month),
                ((int)$day)
            );
        }

        if ($flagFormat) {
            $format = $icuFormat;
        } else {
            $format = self::mapFormatPhpDateTimeToIcuDateTime($icuFormat);
        }

        $traditionalFormatter = new IntlDateFormatter(
            $locale . '@calendar=' . $calendar,
            IntlDateFormatter::SHORT,
            IntlDateFormatter::SHORT,
            $myDate->getTimezone(),
            IntlDateFormatter::TRADITIONAL,
            $format
        );

        return $traditionalFormatter->format($myDate);
    }

    /**
     * @param string $timezoneId
     * @return bool
     */
    protected static function isValidTimezoneId(string $timezoneId): bool
    {
        try {
            new DateTimeZone($timezoneId);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @param string $calendar
     * @param string $locale
     * @param string $timeZone
     * @param string $pattern
     * @return IntlDateFormatter
     * @throws TimerException
     */
    protected static function getDateFormatter(
        string $calendar,
        string $locale,
        string $timeZone,
        string $pattern
    ): IntlDateFormatter {
        if ((!in_array($calendar, self::getAllCalendars())) ||
            (!in_array($locale, self::getAllLocales())) ||
            (!self::isValidTimezoneId($timeZone))
        ) {
            throw new TimerException(
                'The needed calandar `' . $calendar . '`. ' . 'Or the needed locale `' . $locale . '` is not defined. ' .
                '$Or the needed timeZone `' . $timeZone . '` is not defined. ' .
                'Check for type error or typecast errors. ' .
                ' Otherwise make a screenshot and inform the webmaster.' .
                'Allowed Calendars: (' . implode(',', self::$calendars) . ')' .
                'Allowed locales: (' . implode(',', self::$locales) . ')' .
                'Allowed Timezones: (' . implode(',', DateTimeZone::listIdentifiers()) . ')',
                1673711486
            );
        }
        if ($calendar === self::DEFAULT_CALENDAR) {
            $cal = IntlGregorianCalendar::createInstance(new DateTimeZone($timeZone), $locale);
            $formatter = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::SHORT,
                IntlDateFormatter::SHORT,
                $timeZone,
                $cal,
                $pattern
            );
        } else {
            $cal = IntlCalendar::createInstance(new DateTimeZone($timeZone), $locale . '@calendar=' . $calendar);
            $formatter = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::SHORT,
                IntlDateFormatter::SHORT,
                $timeZone,
                $cal,
                $pattern
            );
        }
        $formatter->setLenient(true);
        return $formatter;
    }

    /**
     * Polyfill: planed to replace the strftime-method in the viewhelper timer:format.date
     *
     * @param DateTime $date
     * @param string $strftimeFormat
     * @return string
     * @throws TimerException
     */
    public static function mapStrftimeFormatToDateTimeFormat(DateTime $date, string $strftimeFormat)
    {
        $list = explode('%%', $strftimeFormat);
        $result = [];
        foreach ($list as $part) {
            $toggle = false;
            $resultPart = '';
            $charList = preg_split('//u', $part, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($charList as $char) {
                if ($char === '%') {
                    $toggle = true;
                } else {
                    if ($toggle) {
                        $toggle = false;
                        $key = '%' . $char;
                        if (!array_key_exists($key, self::MAP_TRANSFORM_STRFTIME_TO_DATETIME_FORMAT)) {
                            throw new TimerException(
                                'The key `' . $key . '` is not defined for the conversion in strftime. ' .
                                ' The full format string is `' . $strftimeFormat . '`.',
                                1674373010
                            );
                        }
                        $action = self::MAP_TRANSFORM_STRFTIME_TO_DATETIME_FORMAT['%' . $char];
                        if ($action[0] === '-') {
                            switch ($action) {
                                case '-z-increment':
                                    $dayOfYearInc = (string)((int)$date->format('z') + 1);
                                    $resultPart .= str_pad($dayOfYearInc, 3, "0", STR_PAD_LEFT);
                                    break;
                                case '-weekspecial':
                                    $dayOfYear = (int)$date->format('z');
                                    $weekday = (int)$date->format('w');
                                    $weekBySunday = (string)((int)ceil(($dayOfYear - $weekday + 1) / 7));
                                    $resultPart .= str_pad($weekBySunday, 2, "0", STR_PAD_LEFT);

                                    break;
                                case '-weekspecial2':
                                    $dayOfYear = (int)$date->format('z');
                                    $weekday = (int)$date->format('N') - 1;
                                    $weekByMonday = (string)((int)ceil(($dayOfYear - $weekday + 1) / 7));
                                    $resultPart .= str_pad($weekByMonday, 2, "0", STR_PAD_LEFT);

                                    break;
                                case '-locale-M':
                                    $shortMonth = self::getShortMonthTranlation($date);
                                    $resultPart .= $shortMonth;
                                    break;
                                case '-centuryNumber':
                                    $resultPart .= substr($date->format('Y'), 0, -2);
                                    break;
                                case '-o-reduceTo2Digits':
                                    $oValue = (string)((int)$date->format('o') % 100);
                                    $resultPart .= str_pad($oValue, 2, "0", STR_PAD_LEFT);
                                    break;
                                case '-locale-H.i.s':
                                    $resultPart .= (LocalizationUtility::translate(
                                        'timer.mapping.strftime.make-locale-H.i.s',
                                        TimerConst::EXTENSION_NAME
                                    ) ?? 'H:i:s');
                                    break;
                                case '-locale-m.d.y_H.i.s':
                                    $shortMonth = self::getShortMonthTranlation($date);
                                    $fullMonth = self::getFullMonthTranlation($date);
                                    $resultPart .= (LocalizationUtility::translate(
                                        'timer.mapping.strftime.make-locale-m.d.y_H.i.s',
                                        TimerConst::EXTENSION_NAME,
                                        [$shortMonth, $fullMonth]
                                    ) ?? 'm/d/Y H:i:s');
                                    break;
                                case '-locale-m.d.y':
                                    $shortMonth = self::getShortMonthTranlation($date);
                                    $fullMonth = self::getFullMonthTranlation($date);
                                    $resultPart .= (LocalizationUtility::translate(
                                        'timer.mapping.strftime.make-locale-m.d.y',
                                        TimerConst::EXTENSION_NAME,
                                        [$shortMonth, $fullMonth]
                                    ) ?? 'm/d/Y H:i:s');
                                    break;
                                case '-return':
                                    $resultPart .= chr(10);
                                    break;
                                case '-tab':
                                    $resultPart .= chr(9);
                                    break;
                            }
                        } else {
                            $resultPart .= $action;
                        }
                    } else {
                        $resultPart .= '\\' . $char;
                    }
                }
            }
            $result[] = $resultPart;
        }
        return implode('%', $result);
    }

    /**
     * @param string $phpFormat
     * @return string
     */
    protected static function mapFormatPhpDateTimeToIcuDateTime(string $phpFormat): string
    {
        $format = '';
        $text = '';
        while (mb_strlen($phpFormat) > 0) {
            $firstChar = mb_substr($phpFormat, 0, 1);
            $phpFormat = mb_substr($phpFormat, 1);
            if ($firstChar === self::MAP_ESCAPE_CHAR) {
                $firstChar = mb_substr($phpFormat, 0, 1);
                $phpFormat = mb_substr($phpFormat, 1);
                if ($firstChar === "'") {
                    $text = $text . "''";
                } else {
                    $text = $text . $firstChar;
                }
            } else {
                if ($firstChar === '') {
                    if (!empty($text)) {
                        $format = $format . "'" . $text . "'";
                        $text = '';
                    }
                    $format = $format . $firstChar;
                } else {
                    if (!in_array($firstChar, self::MAP_TRANSFORM_PHP_FORMAT_TO_EMPTY)) {
                        if (($key = array_search($firstChar, self::MAP_TRANSFORM_ICU_FORMAT_TO_PHP_FORMAT)) !== false) {
                            if (!empty($text)) {
                                $format = $format . "'" . $text . "'";
                                $text = '';
                            }
                            $format = $format . $key;
                        } else {
                            $text = $text . $firstChar;
                        }
                    }
                }
            }
        }
        if (!empty($text)) {
            $format = $format . "'" . $text . "'";
        }
        return $format;
    }

    /**
     * @param DateTime $date
     * @return string
     */
    protected static function getShortMonthTranlation(DateTime $date): string
    {
        $month = $date->format('M');
        $helpMonth = LocalizationUtility::translate(
            'timer.mapping.strftime.help-locale-shortmonthname.' . $month,
            TimerConst::EXTENSION_NAME
        );
        if ($helpMonth !== null) {
            $helpList = preg_split('//u', $helpMonth, -1, PREG_SPLIT_NO_EMPTY);
            $resultMonth = '';
            foreach ($helpList as $helpChar) {
                $resultMonth .= '\\' . $helpChar;
            }
            $helpMonth = $resultMonth;
        }
        return 'M';
    }

    /**
     * @param DateTime $date
     * @return string
     */
    protected static function getFullMonthTranlation(DateTime $date): string
    {
        $month = $date->format('m');
        $helpMonth = LocalizationUtility::translate(
            'timer.mapping.strftime.help-locale-fullmonthname.' . $month,
            TimerConst::EXTENSION_NAME
        );
        if ($helpMonth !== null) {
            $helpList = preg_split('//u', $helpMonth, -1, PREG_SPLIT_NO_EMPTY);
            $resultMonth = '';
            foreach ($helpList as $helpChar) {
                $resultMonth .= '\\' . $helpChar;
            }
            return $resultMonth;
        }
        return 'm';
    }

    /**
     * @param string $nameCalendar
     * @return void
     */
    public static function validateCalendarNameOrThrowException(string $nameCalendar): void
    {
        $bundle = new ResourceBundle('', 'ICUDATA');
        $calendarNames = [
            TimerConst::ADDITIONAL_CALENDAR_JULIAN,
        ];
        $calendars = $bundle->get('calendar');
        foreach ($calendars as $n => $v) {
            $calendarNames[] = $n;
        }
        if (!in_array($nameCalendar, $calendarNames, true)) {
            throw new TimerException(
                'The name of the calender `' . $nameCalendar . '` ist not part of the array of allowed ' .
                'calendarnames `' . implode(',', $calendarNames) . '`. ' .
                'Check your spelling, check the typecasting or trim the value. Otherwise make ' .
                'a screenshot and inform the webmaster. ',
                1675498101
            );
        }
    }
}
