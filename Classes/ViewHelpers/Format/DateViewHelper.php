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
use DateTimeInterface;
use DateTimeZone;
use Porthd\Timer\Utilities\ConvertDateUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Formats an object implementing :php:`\DateTimeInterface`. It is similiar to the fluid-viewhelper `f:format.date`.
 * It extends it only with the attribute `timezone`. That allows the user to select the preferred timezone for the dateTime.
 *
 * Examples
 * ========
 *
 * Defaults
 * --------
 *
 * ::
 *
 *    <f:format.date>{dateObject}</f:format.date>
 *
 * ``1980-12-13``
 * Depending on the current date.
 *
 * Custom date format
 * ------------------
 *
 * ::
 *
 *    <f:format.date format="H:i">{dateObject}</f:format.date>
 *
 * ``01:23``
 * Depending on the current time.
 *
 * Custom date format with timezone
 * --------------------------------
 *
 * ::
 *
 *    <f:format.date format="H:i e" timezone="UTC">{dateObject}</f:format.date>
 *    <f:format.date format="H:i e" timezone="Asia/Bangkok">{dateObject}</f:format.date>
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
 *    <f:format.date format="Y" base="{dateObject}">-1 year</f:format.date>
 *
 * ``2016``
 * Assuming dateObject is in 2017.
 *
 * strtotime string
 * ----------------
 *
 * ::
 *
 *    <f:format.date format="d.m.Y - H:i:s">+1 week 2 days 4 hours 2 seconds</f:format.date>
 *
 * ``13.12.1980 - 21:03:42``
 * Depending on the current time, see https://www.php.net/manual/function.strtotime.php.
 *
 * Localized dates using strftime date format
 * ------------------------------------------
 *
 * ::
 *
 *    <f:format.date format="%d. %B %Y">{dateObject}</f:format.date>
 *
 * ``13. Dezember 1980``
 * Depending on the current date and defined locale. In the example you see the 1980-12-13 in a german locale.
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {f:format.date(date: dateObject)}
 *
 * ``1980-12-13``
 * Depending on the value of ``{dateObject}``.
 *
 * Inline notation (2nd variant)
 * -----------------------------
 *
 * ::
 *
 *    {dateObject -> f:format.date()}
 *
 * ``1980-12-13``
 * Depending on the value of ``{dateObject}``.
 */
class DateViewHelper extends AbstractViewHelper
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
    public function initializeArguments(): void
    {
        $this->registerArgument(
            'date',
            'mixed',
            'Either an object implementing DateTimeInterface or a string that is accepted by DateTime constructor'
        );
        $this->registerArgument(
            'format',
            'string',
            'Format String which is taken to format the Date/Time',
            false,
            ''
        );
        $this->registerArgument(
            'base',
            'mixed',
            'A base time (an object implementing DateTimeInterface or a string) used if $date is a relative date specification. Defaults to current time.'
        );
        $this->registerArgument(
            'timezone',
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
        $format = $arguments['format'] ?? '';

        if (empty($arguments['timezone'])) {
            // @todo wait, until the bug in the Context:class is resolved.
            //            $context = GeneralUtility::makeInstance(Context::class);
            //            // Reading the current data instead of $GLOBALS
            //            $timezone = $context->getPropertyFromAspect('date', 'timezone');
            $timezone = date_default_timezone_get();
        } else {
            $timezone = $arguments['timezone'];
        }
        $base = $arguments['base'] ?? GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect(
            'date',
            'timestamp'
        );
        if (is_string($base)) {
            $base = trim($base);
        }

        if ($format === '') {
            $format = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?: 'Y-m-d';
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

        if ((!$date instanceof DateTime) && (!$date instanceof DateTimeImmutable)) {
            try {
                $base = $base instanceof DateTimeInterface ? (int)$base->format('U') : (int)strtotime((MathUtility::canBeInterpretedAsInteger($base) ? '@' : '') . $base);
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

        if (str_contains($format, '%')) {
            // @todo Replace deprecated strftime in php 8.1. Suppress warning in v11.
//            return @strftime($format, (int)$date->format('U'));
            return ConvertDateUtility::mapStrftimeFormatToDateTimeFormat($date, $format);
        }
        return $date->format($format);
    }
}
