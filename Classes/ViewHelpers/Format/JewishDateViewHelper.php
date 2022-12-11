<?php

namespace Porthd\Timer\ViewHelpers\Format;

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

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Utilities\JewishDateUtility;
use PorthD\Wysiwyg\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * define as viewhelper similiar to `f:format.date` for a date in the jewish calendar based a gregorian date
 *
 */
class JewishDateViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * Needed as child node's output can return a DateTime object which can't be escaped
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('date', 'mixed',
            'A greogian-date: Either an object implementing DateTimeInterface or a string that is accepted by DateTime constructor');
        $this->registerArgument('format', 'string',
            'Format String with the following notations: w = Numeric representation of the day of the week beginning with zero for sunday, D = shortcut for weekday (translated), d = day-number with leading zero, j = day of the month without leading zeros, t = Number of days in the given month, n = simple month-number , m = month-number with leading zero, M = full monthname, F = full monthname, y = two digit year , Y = all digits of the year, a =	Lowercase Ante meridiem and Post meridiem (am or pm) ,A =	Uppercase Ante meridiem and Post meridiem (AM or PM), g = 12-hour format of an hour without leading zeros (1 through 12), G = 24-hour format of an hour without leading zeros (0 through 23), h = 12-hour format of an hour with leading zeros (01 through 12), H = 24-hour format of an hour with leading zeros (01 through 23), i = two digit minute number, s = two digit second-number, U = seconds since the Unix Epoch (January 1 1970 00:00:00 GMT), e = Timezone identifier, I = (capital i) Whether or not the date is in daylight saving time 1 if Daylight Saving Time, 0 otherwise., O = Difference to Greenwich time (GMT) without colon between hours and minutes, P = Difference to Greenwich time (GMT) with colon between hours and minutes, p = The same as P, but returns Z instead of +00:00 (available as of PHP 8.0.0), T = Timezone abbreviation, if known; otherwise the GMT offset., Z = Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive., U = Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT), \ = escape the following char. All other character are used as shown.',
            false, '');
        $this->registerArgument('base', 'mixed',
            'A base time (an object implementing DateTimeInterface or a string) used if $date is a relative date specification. Defaults to current time in the georgian format.');
        $this->registerArgument('timezone', 'string',
            'Define an individual timezone for the output of the date. Defaults is the currently used timezone defined by `date_default_timezone_get()`.');
    }

    /**
     * @param array $arguments
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
        $format = ' ' . ($arguments['format'] ?? 'd M Y'); // leading ' ' (space) needed for preg_replace at the end of viewhelper
        $timezone = (empty($arguments['timezone']) ?
            date_default_timezone_get() :
            $arguments['timezone']
        );
        $base = $arguments['base'] ?? GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date',
            'timestamp');
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
            $date = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp', 'now');
        }

        if (!$date instanceof \DateTimeInterface) {
            try {
                $base = $base instanceof \DateTimeInterface ? (int)$base->format('U') : (int)strtotime((MathUtility::canBeInterpretedAsInteger($base) ? '@' : '') . $base);
                $dateTimestamp = strtotime((MathUtility::canBeInterpretedAsInteger($date) ? '@' : '') . $date, $base);
                $dateTime = new \DateTime('@' . $dateTimestamp);
                $dateTime->setTimezone(new \DateTimeZone($timezone));
            } catch (\Exception $exception) {
                throw new Exception('"' . print_r($date, true) . '" could not be parsed by \DateTime ' .
                    'constructor or the timezone `' . print_r($timezone,
                        true) . '` is wrong/unallowed: ' . $exception->getMessage(), 1241722579);
            }
        } else {
            $dateTime = $date;
        }
        $dateTimeParts = JewishDateUtility::formatJewishDateFromDateTime($dateTime, $format);
        $dateTimeParts['D'] = LocalizationUtility::translate(
            $dateTimeParts['D'],
            TimerConst::EXTENSION_NAME
        ); // translation of weekday-name
        $dateTimeParts['F'] = LocalizationUtility::translate(
            str_replace(
                ' ',
                '_',
                $dateTimeParts['F']
            ),
            TimerConst::EXTENSION_NAME
        ); // translation month
        // fill format-string and remove leading space
        $result = substr(
            preg_replace(
                array_keys($dateTimeParts),
                array_values($dateTimeParts),
                $format
            ), 1
        );
        if (empty($result)) {
            return '';
        }
        return implode(
            '\\',
            str_replace(
                '\\',
                '',
                explode('\\\\', $result)
            )
        );

    }

}
