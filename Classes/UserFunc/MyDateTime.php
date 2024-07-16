<?php

declare(strict_types=1);

namespace Porthd\Timer\UserFunc;

use DateTime;
use DateTimeZone;
use Porthd\Timer\Exception\MappingException;
use TYPO3\CMS\Core\Utility\MathUtility;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2023 Dr. Dieter Porth <info@mobger.de>
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

/**
 * Fetch records from the database, using the default .select syntax from TypoScript.
 *
 * This way, e.g. a FLUIDTEMPLATE cObject can iterate over the array of records.
 *
 * Example TypoScript configuration:
 *
 */
final class MyDateTime
{
    protected const GERMAN_TIME_FORMAT = 'd.m.Y H:i:s';

    /**
     * @param DateTime $dateTime
     * @param string $format
     * @param string $timeZoneId
     * @return string
     * @throws MappingException
     */
    public function formatDateTime(DateTime $dateTime, string $format = self::GERMAN_TIME_FORMAT, string $timeZoneId = ''): string
    {

        // fix the dateTime for the current timezone
        if (!empty($timeZoneId)) {

            try {
                $timeZone = new DateTimeZone($timeZoneId);
            } catch (\Exception $e) {
                throw new MappingException(
                    'The timezone identifier `' . $timeZoneId . '` is unknown in the PHP-System. ' . "\n" .
                    'The Exceptionmessage was: ' . $e->getMessage() . '[' . $e->getCode() . ']',
                    1719665619
                );

            }
        } else {
            $timeZone = date_default_timezone_get();
        }
        $dateTime->setTimezone($timeZone);

        // output of the result
        return $dateTime->format($format);
    }
}
