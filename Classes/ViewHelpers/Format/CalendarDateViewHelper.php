<?php

declare(strict_types=1);

namespace Porthd\Timer\ViewHelpers\Format;

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
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\ConvertDateUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Formats an object implementing :php:`\DateTimeInterface`.
 * It is similiar to the viewhelper of this extension `<timer:format.date >{dateObject}</timer:format.date>`.
 * The main difference is that
 * 1. you can select the calendar of your input-string and the calendar for your output.
 * 2. you can select the rules for formating the date [PHP DateTimeInterfaxce::format()],[PHP strftime()] and [ICU-formating rules].
 *
 * For mor examples see template `timersimul.html` in this extension
 *
 * Examples
 * ========
 *
 * Defaults
 * --------
 *
 * :: template
 * ICU-Format - inline
 *    {timer:format.calendarDate(calendartarget:'persian',locale:'de_DE',format:'dd.MM.yyyy HH:mm:ss',flagformat:'1',date:'1600000000')}
 * or
 * php DateTime-format - tag-version
 *
 *    <timer:format.calendarDate calendartarget="persian" locale="de_DE" format="d.m.Y  H:i:s" flagformat="0">
 *        1600000000
 *    </timer:format.calendarDate>
 *
 * ::
 *
 * Output
 *  ``23.06.1399 14:26:40``
 *
 * // equal to the gregorian date: 13.09.2020 14:26:40
 *
 */
class CalendarDateViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    public const ARG_FLAG_ICUFORMAT = 'flagformat';
    public const ARG_FORMAT = 'format';
    public const ARG_BASE = 'base';
    public const ARG_TIMEZONE = 'timezone';
    public const ARG_DATE = 'date';
    public const ARG_CALENDAR_STRING = 'datestring';
    public const ARG_FROM_CALENDAR = 'calendarsource';
    public const ARG_TO_CALENDAR = 'calendartarget';
    public const ARG_LOCALE = 'locale';
    public const DEFAULT_FORMAT_YMDHIS = 'Y/m/d H:i:s';
    public const FORMAT_PHP_DATETIME = 0;
    public const FORMAT_ICU_DATETIME = 1;
    public const FORMAT_PHP_STRFTIME = 2;

    /**
     * Needed as child node's output can return a DateTime object which can't be escaped
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        $this->registerArgument(
            self::ARG_BASE,
            'mixed',
            'A base time (an object implementing DateTimeInterface or a string) used if $date is a ' .
            'relative date specification. Defaults to current time in the georgian format. '
        );
        $this->registerArgument(
            self::ARG_FROM_CALENDAR,
            'mixed',
            'This entry defines only the calendar for the value in `' . self::ARG_CALENDAR_STRING .
            '`.if nothing is defined, it will use the western calendar (gregorian) as reference. ',
            false,
            ConvertDateUtility::DEFAULT_CALENDAR
        );
        $this->registerArgument(
            self::ARG_TO_CALENDAR,
            'mixed',
            'This entry defines the calendar for the output by name. If nothing is defined, it will use ' .
            'the western calendar (gregorian) as reference. Allowed are the calendars of the IntlDateFormatter ' .
            '(see https://www.php.net/manual/en/intldateformatter.create.php). Zusätzlich wird für die ' .
            ' Feiertage bei einigen orthodoxen christlichen Kirchen zusätzlich auch der ' .
            'Julianische Kalender (julian) unterstützt. ',
            false,
            ConvertDateUtility::DEFAULT_CALENDAR
        );
        $this->registerArgument(
            self::ARG_CALENDAR_STRING,
            'string',
            'This argument has more priority than the attribute`' . self::ARG_DATE . '`. I contains a string ' .
            'with the must-have-structure `year/month/day hour:minute:second`. The year must have four digits. The ' .
            'other numbers must have two digits.It will be used in the calendar, which is defined ' .
            'in the attribute `' . self::ARG_FROM_CALENDAR . '`. '
        );
        $this->registerArgument(
            self::ARG_DATE,
            'mixed',
            'If the attribute `' . self::ARG_DATE . '` is not set, this attribute will define a ' .
            'DateTime-object. As value either an object implementing DateTimeInterface or a string that is accepted. ' .
            'The date will be converted to the calendar, defined by the attribute `' . self::ARG_FROM_CALENDAR .
            '`. The output of the date ist defined by the format. '
        );
        $this->registerArgument(
            self::ARG_FORMAT,
            'string',
            'Format String can follow three different formating rules: ' .
            'the rules for strftime() [https://www.php.net/manual/en/function.strftime.php], ' .
            'the rules for DatetimeInterface::format() [https://www.php.net/manual/en/datetime.format] or ' .
            'the ICU-rules [https://unicode-org.github.io/icu/userguide/format_parse/datetime/]. ',
            false,
            self::DEFAULT_FORMAT_YMDHIS
        );
        $this->registerArgument(
            self::ARG_FLAG_ICUFORMAT,
            'int',
            'If this is `' . self::FORMAT_PHP_DATETIME . '`, empty or containing an undefined integer, ' .
            'the php-dateTime-format (https://www.php.net/manual/en/datetime.format.php) will be used. If this ' .
            'is `' . self::FORMAT_ICU_DATETIME . '`, the output is defined by the format-definitions of ICU' .
            '(https://unicode-org.github.io/icu/userguide/format_parse/datetime/). If this is `' .
            self::FORMAT_PHP_STRFTIME . '`, the output is defined by the format-definitions of ' .
            'strftime (https://www.php.net/manual/en/function.strftime).',
            false,
            0
        );
        $this->registerArgument(
            self::ARG_LOCALE,
            'mixed',
            'Shortcut of the locale i.e. de_DE, en or something similar. It will be used for the output. ',
            false,
            ConvertDateUtility::DEFAULT_LOCALE
        );
        $this->registerArgument(
            self::ARG_TIMEZONE,
            'string',
            'This attribute is only used, if the attribute `' . self::ARG_CALENDAR_STRING .
            '` is defined and used. Define an individual timezone for the output of the date i.e `Europe/Berlin`. ' .
            'See https://www.php.net/manual/en/timezones.php for more informations. Defaults is the currently used ' .
            'timezone defined by `date_default_timezone_get()`. '
        );
    }

    /**
     * @param array<mixed> $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     * @throws Exception
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $dateRaw = $renderChildrenClosure();
        [$fromCalendar, $toCalendar, $locale, $flagFormat, $format, $timezone, $base, $date, $calendarString] =
            self::readArguments($arguments, $dateRaw);

        // @todo check against PHP-versions, until the bug is fixed
        if (in_array($fromCalendar, ConvertDateUtility::DEFAULT_LIST_DEFECTIVE_CALENDAR)) {
            return LocalizationUtility::translate(
                'calendarDateViewHelper.php.error',
                TimerConst::EXTENSION_NAME
            );
        }

        if ((empty($calendarString)) ||
            ($dateRaw instanceof DateTimeInterface) ||
            ($fromCalendar === ConvertDateUtility::DEFAULT_CALENDAR)
        ) {
            // convert date and time from DateTime (gregoprian calendar) into non-gregorian calendar
            if ((!$date instanceof DateTime) && (!$date instanceof DateTimeImmutable)) {
                try {
                    $base = $base instanceof \DateTimeInterface ? (int)$base->format('U') : (int)strtotime((MathUtility::canBeInterpretedAsInteger($base) ? '@' : '') . $base);
                    $dateTimestamp = strtotime(
                        (MathUtility::canBeInterpretedAsInteger($date) ? '@' : '') . $date,
                        $base
                    );
                    $date = new DateTime();
                    $date->setTimestamp($dateTimestamp);
                } catch (\Exception $exception) {
                    throw new TimerException('"' . print_r($date, true) . '" could not be parsed by \DateTime ' .
                        'constructor or the timezone `' . print_r(
                            $timezone,
                            true
                        ) . '` is wrong/unallowed: ' . $exception->getMessage(), 1241722579);
                }
            }
            $date->setTimezone(new DateTimeZone($timezone));
            $result = self::switchFormatAndConvertDateToCalendar($flagFormat, $locale, $toCalendar, $date, $format);
            return $result;
        }

        self::validCalendarStringOrThrowException($calendarString);
        $greorgianDateFromForeignCalendar = ConvertDateUtility::convertFromCalendarToDateTime(
            $locale,
            $fromCalendar,
            $calendarString,
        );
        if ($toCalendar === ConvertDateUtility::DEFAULT_CALENDAR) {
            // convert date and time from non-gregorian calendar into gregorian calendar

            return self::switchFormatAndConvertCalandarInDateTimeString(
                $flagFormat,
                $greorgianDateFromForeignCalendar,
                $locale,
                $format
            );
        }
        // convert date and time from non-gregorian calendar into (other?) non-gregorian calendar
        $gregorianResult = self::switchFormatAndConvertCalandarInDateTimeString(
            self::FORMAT_ICU_DATETIME,
            $greorgianDateFromForeignCalendar,
            $locale,
            ConvertDateUtility::INTL_DATE_FORMATTER_DEFAULT_PATTERN
        );
        $gregorianDateTime = DateTime::createFromFormat(ConvertDateUtility::PHP_DATE_FORMATTER_DEFAULT_PATTERN, $gregorianResult);
        return self::switchFormatAndConvertDateToCalendar(
            $flagFormat,
            $locale,
            $toCalendar,
            $gregorianDateTime,
            $format
        );
    }

    /**
     * @param array<mixed> $arguments
     * @param null|string|int|DateTimeInterface $date
     * @return array<mixed>
     * @throws AspectNotFoundException
     */
    private static function readArguments(array $arguments, $date): array
    {
        $fromCalendar = (
        (empty($arguments[self::ARG_FROM_CALENDAR])) ?
            ConvertDateUtility::DEFAULT_CALENDAR :
            $arguments[self::ARG_FROM_CALENDAR]
        );
        ConvertDateUtility::validateCalendarNameOrThrowException($fromCalendar);
        $toCalendar = (
        (empty($arguments[self::ARG_TO_CALENDAR])) ?
            ConvertDateUtility::DEFAULT_CALENDAR :
            $arguments[self::ARG_TO_CALENDAR]
        );
        ConvertDateUtility::validateCalendarNameOrThrowException($toCalendar);
        $locale = (
        (empty($arguments[self::ARG_LOCALE])) ?
            ConvertDateUtility::DEFAULT_LOCALE :
            $arguments[self::ARG_LOCALE]
        );

        $flagFormat = (
        (empty($arguments[self::ARG_FLAG_ICUFORMAT])) ?
            0 :
            (int)$arguments[self::ARG_FLAG_ICUFORMAT]
        );
        $flagFormat = (
        (($flagFormat > 2) || ($flagFormat < 0)) ?
            0 :
            $flagFormat
        );
        $format = (
        (empty($arguments[self::ARG_FORMAT])) ?
            self::DEFAULT_FORMAT_YMDHIS :
            $arguments[self::ARG_FORMAT]
        );
        $calendarString = (
        (empty($arguments[self::ARG_CALENDAR_STRING])) ?
            '' :
            $arguments[self::ARG_CALENDAR_STRING]
        );

        if (empty($arguments[self::ARG_TIMEZONE])) {
//            $context = GeneralUtility::makeInstance(Context::class);
//            // Reading the current data instead of $GLOBALS
//            $timezone = $context->getPropertyFromAspect('date', self::self::ARG_TIMEZONE);
            $timezone = date_default_timezone_get();
        } else {
            $timezone = $arguments[self::ARG_TIMEZONE];
        }
        $base = $arguments[self::ARG_BASE] ?? GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect(
            self::ARG_DATE,
            'timestamp'
        );
        if (is_string($base)) {
            $base = trim($base);
        }

        if ($date === null) {
            $date = (
            (empty($arguments[self::ARG_DATE])) ?
                0 :
                (string)$arguments[self::ARG_DATE]
            );
        }

        if (is_string($date)) {
            $date = trim($date);
        }

        if ($date === '') {
            $date = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect(
                self::ARG_DATE,
                'timestamp',
                'now'
            );
        }

        return [$fromCalendar, $toCalendar, $locale, $flagFormat, $format, $timezone, $base, $date, $calendarString];
    }


    /**
     * @param string $calendarString
     * @return void
     * @throws TimerException
     */
    protected static function validCalendarStringOrThrowException(string $calendarString): void
    {
        [$dateString, $timeString] = explode(' ', $calendarString);
        [$fullYear, $month, $day] = array_map(
            'intval',
            explode('/', $dateString)
        );
        [$hour, $minute, $second] = array_map(
            'intval',
            explode(':', $timeString)
        );

        // 13 month => https://de.wikipedia.org/wiki/Internationaler_Ewiger_Kalender is not part of this function
        if ((strlen($calendarString) < ConvertDateUtility::INTL_DATE_FORMATTER_DEFAULT_LENGTH) || // 19= length for 'yyyy/MM/dd HH:mm:ss'
            ($month < 1) || ($month > 13) || // chinese lunar calendar has 13 month (leap-month)
            ($day < 1) || ($day > 31) ||
            ($hour < 0) || ($hour > 23) ||
            ($minute < 0) || ($minute > 59) ||
            ($second < 0) || ($second > 59)
        ) {
            throw new TimerException(
                'The datestring `' . $calendarString . '` did not fullfill the format-critia.' .
                ' The gregorian former date `2 September 2023 BD 13:06:07` should be equal to `-2023/09/02 13:06:07`' .
                ' to fullfill the icu-format-criteria `' . ConvertDateUtility::INTL_DATE_FORMATTER_DEFAULT_PATTERN . '`.',
                1674899158
            );
        }
    }

    /**
     * @param int $flagFormat
     * @param string $locale
     * @param string $toCalendar
     * @param DateTime $date
     * @param string $format
     * @return string
     * @throws TimerException
     */
    protected static function switchFormatAndConvertDateToCalendar(
        int $flagFormat,
        string $locale,
        string $toCalendar,
        DateTime $date,
        string $format
    ): string {
        switch ($flagFormat) {
            case self::FORMAT_ICU_DATETIME:
                $result = ConvertDateUtility::convertFromDateTimeToCalendar(
                    $locale,
                    $toCalendar,
                    $date,
                    true,
                    $format
                );
                break;
            case self::FORMAT_PHP_STRFTIME:
                $newFormat = ConvertDateUtility::mapStrftimeFormatToDateTimeFormat($date, $format);
                $result = ConvertDateUtility::convertFromDateTimeToCalendar(
                    $locale,
                    $toCalendar,
                    $date,
                    false,
                    $newFormat
                );
                break;
            default:
                // self::>self::FORMAT_PHP_DATETIME
                $result = ConvertDateUtility::convertFromDateTimeToCalendar(
                    $locale,
                    $toCalendar,
                    $date,
                    false,
                    $format
                );
                break;
        }
        return $result;
    }

    /**
     * @param int $flagFormat
     * @param DateTime $greorgianDateFromForeignCalendar
     * @param string $locale
     * @param string $format
     * @return string
     * @throws TimerException
     */
    protected static function switchFormatAndConvertCalandarInDateTimeString(
        int $flagFormat,
        DateTime $greorgianDateFromForeignCalendar,
        string $locale,
        string $format
    ): string {
        // convert date and time from non-gregorian calendar into DateTime (gregoprian calendar)
        // self::>self::FORMAT_PHP_DATETIME
        switch ($flagFormat) {
            case self::FORMAT_ICU_DATETIME:
                $result = ConvertDateUtility::formatDateTimeInIcuFormat(
                    $greorgianDateFromForeignCalendar,
                    $locale,
                    $format
                );
                break;
            case self::FORMAT_PHP_STRFTIME:
                $newFormat = ConvertDateUtility::mapStrftimeFormatToDateTimeFormat(
                    $greorgianDateFromForeignCalendar,
                    $format
                );
                $result = $greorgianDateFromForeignCalendar->format($newFormat);
                break;
            default:

                $result = $greorgianDateFromForeignCalendar->format($format);
                break;
        }
        return $result;
    }
}
