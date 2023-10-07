<?php

declare(strict_types=1);

namespace Porthd\Timer\DataProcessing\Trait;

use DateTime;
use Porthd\Timer\Constants\TimerConst;
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
trait GeneralDataProcessorTrait
{
    /**
     * @param array $processedData
     * @param string $fieldname
     * @return array
     */
    public function generateCacheIdentifier(array &$processedData, string $fieldname): array
    {
        // detect type of data
        $flagPage = isset($processedData['data']['doktype'], $processedData['data']['is_siteroot']);
        $flagContent = isset($processedData['data']['CType'], $processedData['data']['list_type']);
        $flagData = (!($flagPage || $flagContent));
        $pageUid = (
        ($flagPage) ?
            $processedData['data']['pid'] :
            $processedData['data']['uid']
        );
        if ($flagContent) {
            $add = 'cont';
            $pageContentOrElementUid = $processedData['data']['uid'];
            $cType = $processedData['data']['CType'];
            $listType = $processedData['data']['list_type'];
        } else {
            if ($flagPage) {
                $add = 'page';
                $pageContentOrElementUid = $processedData['data']['uid'];
                $cType = $processedData['data']['doktype'];
                $listType = $processedData['data']['tstamp'];
            } else {
                $add = 'data';
                $pageContentOrElementUid = $processedData['data']['uid'];
                $cType = (
                (isset($processedData['data']['crdate'])) ?
                    $processedData['data']['crdate'] :
                    'noCrdate'
                );
                $listType = (
                (isset($processedData['data']['tstamp'])) ?
                    $processedData['data']['tstamp'] :
                    'noTStamp'
                );
            }
        }
        $languageUid = (
        (isset($processedData['data']['sys_language_uid'])) ?
            $processedData['data']['sys_language_uid'] :
            '_upsLang'
        );
        $cacheIdentifier = md5(__CLASS__ . "#$pageUid#$cType#$listType#$languageUid#$fieldname#" . __LINE__) . $add . $pageContentOrElementUid;
        return [$pageUid, $pageContentOrElementUid, $cacheIdentifier];
    }

    /**
     * @param ContentObjectRenderer $cObj
     * @param array<mixed> $processorConfiguration
     * @return array<mixed>
     */
    public function detectCacheTimeSet(
        ContentObjectRenderer $cObj,
        array                 $processorConfiguration
    ): array
    {
        $cacheValue = (string)$cObj->stdWrapValue(
            TimerConst::ARGUMENT_CACHE,
            $processorConfiguration,
            'default'
        );
        switch (strtolower($cacheValue)) {
            case 'no':
            case 'null':
            case 'none':
            case '0':
            case '':
                $cacheTime = 0;
                $cacheCalc = false;
                break;
            case 'default':
                $cacheTime = 0;
                $cacheCalc = true;
                break;
            default:
                $cacheTime = abs((int)$cacheValue);
                $cacheCalc = false;
                break;
        }
        return [$cacheTime, $cacheCalc];
    }


    /**
     * @param int $cacheTime
     * @param bool $cacheCalc
     * @param DateTime $dateTimeStopCase
     * @param int $currentTimestamp
     * @return int|null
     */
    public function calculateSimpleTimeDependedCacheTime(
        int      $cacheTime,
        bool     $cacheCalc,
        DateTime $dateTimeStopCase,
        int      $currentTimestamp
    )
    {
        $myLifeTime = null;
        if ($cacheCalc) {
            $tempMyLifeTime = ($dateTimeStopCase->getTimestamp() - $currentTimestamp);
            // the caching-times depend on the next change in the future
            if ($tempMyLifeTime > 0) {
                $myLifeTime = $tempMyLifeTime + 1;
            } else {
                $myLifeTime = $tempMyLifeTime - 1;
            }
        } else {
            if ($cacheTime > 0) {
                // define fixed time
                $myLifeTime = $cacheTime;
            }
        }
        return $myLifeTime;
    }

}
