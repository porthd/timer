<?php

declare(strict_types=1);

namespace Porthd\Timer\DataProcessing\Trait;

use DateTime;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
 *
 */
interface GeneralDataProcessorTraitInterface
{
    /**
     * @param array<mixed> $processedData
     * @param string $fieldname
     * @return array<mixed>
     */
    public function generateCacheIdentifier(array &$processedData, string $fieldname): array;

    /**
     * @param ContentObjectRenderer $cObj
     * @param array<mixed> $processorConfiguration
     * @return array<mixed>
     */
    public function detectCacheTimeSet(ContentObjectRenderer $cObj, array $processorConfiguration): array;

    /**
     * @param int $cacheTime
     * @param bool $cacheCalc
     * @param DateTime $dateTimeStopCase
     * @param int $currentTimestamp
     * @return int|null
     *
     */
    public function calculateSimpleTimeDependedCacheTime(
        int      $cacheTime,
        bool     $cacheCalc,
        DateTime $dateTimeStopCase,
        int      $currentTimestamp
    );
}
