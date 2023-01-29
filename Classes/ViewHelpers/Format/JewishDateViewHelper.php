<?php

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

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Utilities\JewishDateUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Formats an object implementing :php:`\DateTimeInterface`. It is similiar to the viewhelper of this extension `timer:format.<timer:format.jewishDate >{dateObject}</timer:format.date>jewishDate`.
 * The main difference is that the date is transferred to the lunar-orientated jewish calendar.
 *
 * Examples
 * ========
 *
 * Defaults
 * --------
 *
 * ::
 *
 *    <timer:format.<timer:format.date format="H:i" >{dateObject}</timer:format.date>
 *
 * ``5741-12-13``
 * Depending on the current date.
 *
 * Custom date format
 * ------------------
 *
 * ::
 *
 *    <timer:format.<timer:format.date format="H:i e" timezone="Asia/Bangkok" >{dateObject}</timer:format.datejewishDate>
 *
 * ``01:23``
 * Depending on the current time.
 *
 * Custom date format with timezone
 * --------------------------------
 *
 * ::
 *
 *    <timer:format.<timer:format.date format="d.m.Y - H:i:s" >+1 week 2 days 4 hours 2 seconds</timer:format.date>jewishDate format="H:i e" timezone="UTC" hideHumanityComment="true">{dateObject}</timer:format.jewishDate>
 *    <timer:format.jewishDate format="H:i e" timezone="Asia/Bangkok" >{dateObject}</timer:format.jewishDate>
 *
 * ``01:23 UTC``
 * ``08:23 Asia/Bangkok``
 * Depending on the current time and the .
 *
 * Relative date with given time
 * -----------------------------
 *
 * ::
 *
 *    <timer:format.jewishDate format="Y" base="{dateObject}">-1 year</timer:format.jewishDate>
 *
 * ``2016``
 * Assuming dateObject is in 2017.
 *
 * strtotime string
 * ----------------
 *
 * ::
 *
 *    <timer:format.jewishDate format="d.m.Y - H:i:s" >+1 week 2 days 4 hours 2 seconds</timer:format.jewishDate>
 *
 * ``13.12.1980 - 21:03:42``
 * Depending on the current time, see https://www.php.net/manual/function.strtotime.php.
 *
 * ATTENTION: Localized dates using strftime date format will NOT work
 * -------------------------------------------------------------------
 *
 * ::
 *
 *    <timer:format.jewishDate format="%d. %B %Y" >{dateObject}</timer:format.jewishDate>
 *
 * ``13. Dezember 1980``
 * Depending on the current date and defined locale. In the example you see the 1980-12-13 in a german locale.
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {timer:format.jewishDate(date: dateObject)}
 *
 * ``1980-12-13``
 * Depending on the value of ``{dateObject}``.
 *
 * @deprecated since v11, will be removed in v12
 *
 */
class JewishDateViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    public const ARG_FORMAT = 'format';
    public const ARG_BASE = 'base';
    public const ARG_TIMEZONE = 'timezone';
    public const ARG_DATE = 'date';

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
            self::ARG_DATE,
            'mixed',
            'A greogian-date: Either an object implementing DateTimeInterface or a string that is accepted by DateTime constructor'
        );
        $this->registerArgument(
            self::ARG_FORMAT,
            'string',
            'Format String with the following notations: w = Numeric representation of the day of the week beginning with zero for sunday, D = shortcut for weekday (translated), d = day-number with leading zero, j = day of the month without leading zeros, t = Number of days in the given month, n = simple month-number , m = month-number with leading zero, M = full monthname, F = full monthname, y = two digit year , Y = all digits of the year, a =	Lowercase Ante meridiem and Post meridiem (am or pm) ,A =	Uppercase Ante meridiem and Post meridiem (AM or PM), g = 12-hour format of an hour without leading zeros (1 through 12), G = 24-hour format of an hour without leading zeros (0 through 23), h = 12-hour format of an hour with leading zeros (01 through 12), H = 24-hour format of an hour with leading zeros (01 through 23), i = two digit minute number, s = two digit second-number, U = seconds since the Unix Epoch (January 1 1970 00:00:00 GMT), e = Timezone identifier, I = (capital i) Whether or not the date is in daylight saving time 1 if Daylight Saving Time, 0 otherwise., O = Difference to Greenwich time (GMT) without colon between hours and minutes, P = Difference to Greenwich time (GMT) with colon between hours and minutes, p = The same as P, but returns Z instead of +00:00 (available as of PHP 8.0.0), T = Timezone abbreviation, if known; otherwise the GMT offset., Z = Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive., U = Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT), \ = escape the following char. All other character are used as shown.',
            false,
            'Y/M/d'
        );
        $this->registerArgument(
            self::ARG_BASE,
            'mixed',
            'A base time (an object implementing DateTimeInterface or a string) used if $date is a relative date specification. Defaults to current time in the georgian format.'
        );
        $this->registerArgument(
            self::ARG_TIMEZONE,
            'string',
            'Define an individual timezone for the output of the date. Defaults is the currently used timezone defined by `date_default_timezone_get()`.'
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
        $format = ' ' . ($arguments[self::ARG_FORMAT] ?? 'Y/M/d'); // leading ' ' (space) needed for preg_replace at the end of viewhelper
        if (empty($arguments['timezone'])) {
            $context = GeneralUtility::makeInstance(Context::class);
            // Reading the current data instead of $GLOBALS
            $timezone = $context->getPropertyFromAspect('date', 'timezone');
        } else {
            $timezone = $arguments['timezone'];
        }
        $base = $arguments[self::ARG_BASE] ?? GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect(
            self::ARG_DATE,
            'timestamp'
        );
        if (is_string($base)) {
            $base = trim($base);
        }

        $date = $renderChildrenClosure();
        if ($date === null) {
            return '';
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

        if ((!$date instanceof DateTime) && (!$date instanceof DateTimeImmutable)) {
            try {
                $base = $base instanceof \DateTimeInterface ? (int)$base->format('U') : (int)strtotime((MathUtility::canBeInterpretedAsInteger($base) ? '@' : '') . $base);
                $dateTimestamp = strtotime((MathUtility::canBeInterpretedAsInteger($date) ? '@' : '') . $date, $base);
                $date = new DateTime();
                $date->setTimestamp($dateTimestamp);
            } catch (\Exception $exception) {
                throw new Exception('"' . print_r($date, true) . '" could not be parsed by \DateTime ' .
                    'constructor or the timezone `' . print_r(
                        $timezone,
                        true
                    ) . '` is wrong/unallowed: ' . $exception->getMessage(), 1241722579);
            }
        }
        $date->setTimezone(new DateTimeZone($timezone));
        $dateTimeParts = JewishDateUtility::formatJewishDateFromDateTime($date, $format);
        // fill format-string and remove leading space
        $result = $format;
        foreach ($dateTimeParts as $key => $item) {
            $escapeItem = preg_replace('/(\D)/', '\\\\${1}', $item);
            $result = preg_replace(
                '/([^\\\\])' . $key . '/',
                '${1}' . $escapeItem,
                $result
            );
        }
        $result = substr($result, 1);
        $result = str_replace('\\', '', $result);
        return $result ;
    }
}
