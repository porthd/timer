<?php

declare(strict_types=1);

namespace Porthd\Timer\DataProcessing;

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
use Porthd\Timer\CustomTimer\PeriodListTimer;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Fetch records from the database, using the default .select syntax from TypoScript.
 *
 * This way, e.g. a FLUIDTEMPLATE cObject can iterate over the array of records.
 *
 * Example TypoScript configuration:
 *
 * The table must contain the flex-field `tx_timer_timer` and the string-field `tx_timer_selector`.
 *
 *     dataProcessing {
 *          10 = Porthd\Timer\DataProcessing\FlexToArrayProcessor
 *          10 {
 *              # regular if syntax
 *              #if.isTrue.field = record
 *
 *              # field with flexform-array
 *              # default is 'tx_timer_timer'
 *              # field = tx_timer_timer
 *
 *              # field with selector for flexform-array
 *              # default is 'tx_timer_selector'
 *              # selectorField = tx_timer_selector
 *
 *              # A definition of flattenkeys will override the default definition.
 *              #   the attributes `timer` and `general` are used as sheet-names in my customTimer-flexforms
 *              #   The following defintion is the default: `data,general,timer,sDEF,lDEF,vDEF`
 *              flattenkeys = data,general,timer,sDEF,lDEF,vDEF
 *
 *              # output variable with the resulting list
 *              as = flexarray
 *              }
 *          }
 *     }
 *
 */
class FlexToArrayProcessor implements DataProcessorInterface
{
    protected const ATTR_FLEX_FIELD = 'field';
    protected const DEFAULT_FLEX_FIELD = TimerConst::TIMER_FIELD_FLEX_ACTIVE;
    protected const ATTR_IF_DOT = 'if.';
    protected const OUTPUT_ARG_DATA = 'data';
    protected const ATTR_RESULT_VARIABLE_NAME = 'as';
    protected const DEFAULT_RESULT_VARIABLE_NAME = 'flattenflex';
    protected const ATTR_FLATTENKEYS = 'flattenkeys';
    protected const DEFAULT_FLATTENKEYS = 'data,general,timer,sDEF,lDEF,vDEF';
    /**
     * @var ContentDataProcessor
     */
    protected $contentDataProcessor;

    public function __construct()
    {
        $this->contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
    }


    /**
     * Fetches records from the database as an array
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array<mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<mixed> $processorConfiguration The configuration of this processor
     * @param array<mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     *
     * @return array<mixed> the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        if (array_key_exists(self::ATTR_FLEX_FIELD, $processorConfiguration)) {
            $flexFieldName = $cObj->stdWrapValue(self::ATTR_FLEX_FIELD, $processorConfiguration, false);
        } else {
            $flexFieldName = self::DEFAULT_FLEX_FIELD;
        }
        if (
            (array_key_exists(self::ATTR_IF_DOT, $processorConfiguration)) &&
            (!$cObj->checkIf($processorConfiguration[self::ATTR_IF_DOT]))
        ) {
            return $processedData;
        }
        $singleElement = null;
        if (!empty($processedData[self::OUTPUT_ARG_DATA][$flexFieldName])) {
            $flexString = $processedData[self::OUTPUT_ARG_DATA][$flexFieldName];
            $stringFlatKeys = $cObj->stdWrapValue(
                self::ATTR_FLATTENKEYS,
                $processorConfiguration,
                self::DEFAULT_FLATTENKEYS
            );
            $singleElementRaw = GeneralUtility::xml2array($flexString);
            if ((is_string($singleElementRaw)) && (substr($singleElementRaw, 0, strlen('Line ')) === 'Line ')) {
                throw new TimerException(
                    'The flexform-string in the field `' . $flexFieldName . '` could not be resolved.' .
                    ' Check the reason for the incorrect flexform-string: `' . $flexString . '`',
                    1668690059
                );
            }
            $listFlatKeys = array_filter(
                array_map(
                    'trim',
                    explode(',', $stringFlatKeys)
                )
            );
            if (empty($listFlatKeys)) {
                $singleElement= $singleElementRaw;
            } else {
                $singleElement = TcaUtility::flexformArrayFlatten($singleElementRaw, $listFlatKeys);
            }
        }

        $targetVariableName = $cObj->stdWrapValue(
            self::ATTR_RESULT_VARIABLE_NAME,
            $processorConfiguration,
            self::DEFAULT_RESULT_VARIABLE_NAME
        );

        $processedData[$targetVariableName] = $singleElement;

        return $processedData;
    }
}
