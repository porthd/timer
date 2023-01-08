<?php

namespace Porthd\Timer\DataProcessing;

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

use Porthd\Timer\Exception\TimerException;
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
 *
 *      20 = Porthd\Timer\DataProcessing\MapperProcessor
 *      20 {
 *
 *          # regular if syntax
 *          #if.isTrue.field = record
 *
 *          inputfield = periodlist
 *          # Each field must part of periodlist
 *          # every entry must be some formal
 *          generic {
 *              id {
 *                  pretext = event
 *                  posttext = holiday
 *                  type = index
 *              }
 *              calendarId {
 *                  pretext = cal1
 *                  posttext =
 *                  type = constant
 *              }
 *          }
 *
 *          mapping {
 *              # sourceFieldName = targetFieldName
 *              title = title
 *              startJson = start
 *              endJson = end
 *          }
 *
 *          # outputformat has the values `array`,`json`
 *          # if the outputformat is unknown, json will be the default
 *          outputFormat = json
 *          # output variable with the resulting list
 *          # default-value is `periodlist`
 *          asString = periodListJson
 *
 *      }
 *
 */
class MappingProcessor implements DataProcessorInterface
{
    //    required Attributes

    // optional Attribute perhaps with main defaults
    protected const ATTR_IF_DOT = 'if.';
    protected const ATTR_FLEX_INPUT_FIELD = 'inputfield';
    protected const DEFAULT_FLEX_FIELD = 'periodlist';
    protected const ATTR_FLEX_OUTPUTFIELD = 'asString';
    protected const DEFAULT_OUTPUTFIELD = 'periodListJson';
    protected const ATTR_GENERIC_DOT = 'generic.';
    protected const ATTR_GENERIC_PRETEXT = 'pretext';
    protected const DEFAULT_GENERIC_PRETEXT = '';
    protected const ATTR_GENERIC_POSTTEXT = 'posttext';
    protected const DEFAULT_GENERIC_POSTTEXT = '';
    protected const ATTR_GENERIC_TYPE = 'type';
    protected const DEFAULT_GENERIC_TYPE = self::VAL_GENERIC_TYPE_INDEX;
    protected const VAL_GENERIC_TYPE_INDEX = 'index';
    protected const VAL_GENERIC_TYPE_CONSTANT = 'constant';
    protected const ATTR_MAPPING_DOT = 'mapping.';
    protected const ATTR_OUTPUT_FORMAT = 'outputFormat';
    protected const VAL_OUTPUT_FORMAT_JSON = 'json';
    protected const VAL_OUTPUT_FORMAT_ARRAY = 'array';
    protected const LIST_OUTPUT_FORMAT = [
        self::VAL_OUTPUT_FORMAT_ARRAY,
        self::VAL_OUTPUT_FORMAT_JSON,
    ];
    protected const DEFAULT_OUTPUT_FORMAT = self::VAL_OUTPUT_FORMAT_JSON;


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
     * @param array<mixed> &$contentObjectConfiguration The configuration of Content Object
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
        if (array_key_exists(self::ATTR_OUTPUT_FORMAT, $processorConfiguration)) {
            $outputFormat = $cObj->stdWrapValue(self::ATTR_OUTPUT_FORMAT, $processorConfiguration, false);
        } else {
            $outputFormat = self::DEFAULT_OUTPUT_FORMAT;
        }
        if (array_key_exists(self::ATTR_FLEX_INPUT_FIELD, $processorConfiguration)) {
            $inputFieldName = $cObj->stdWrapValue(self::ATTR_FLEX_INPUT_FIELD, $processorConfiguration, false);
        } else {
            $inputFieldName = self::DEFAULT_FLEX_FIELD;
        }
        if (array_key_exists(self::ATTR_FLEX_OUTPUTFIELD, $processorConfiguration)) {
            $outputFieldName = $cObj->stdWrapValue(self::ATTR_FLEX_OUTPUTFIELD, $processorConfiguration, false);
        } else {
            $outputFieldName = self::DEFAULT_OUTPUTFIELD;
        }
        if (array_key_exists($outputFieldName, $processedData)) {
            throw new TimerException(
                'The new fieldname `' . $outputFieldName . '` is already defined. ' .
                'Check the typoscript-configuration of your dataprocessor `' . self::class . '`.',
                1668758629
            );
        }
        if ((array_key_exists(self::ATTR_IF_DOT, $processorConfiguration) && !$cObj->checkIf($processorConfiguration[self::ATTR_IF_DOT])) ||
            (empty($processedData[$inputFieldName])) ||
            (!is_array($processedData[$inputFieldName]))
        ) {
            return $processedData;
        }

        $result = [];
        foreach (($processorConfiguration[self::ATTR_GENERIC_DOT] ?? []) as $newFieldDotName => $genericConfig) {
            $newFieldName = trim($newFieldDotName, '.');
            $pretext = ((!empty($genericConfig[self::ATTR_GENERIC_PRETEXT])) ? $genericConfig[self::ATTR_GENERIC_PRETEXT] : self::DEFAULT_GENERIC_PRETEXT);
            $posttext = ((!empty($genericConfig[self::ATTR_GENERIC_POSTTEXT])) ? $genericConfig[self::ATTR_GENERIC_POSTTEXT] : self::DEFAULT_GENERIC_POSTTEXT);
            $type = ((!empty($genericConfig[self::ATTR_GENERIC_TYPE])) ? $genericConfig[self::ATTR_GENERIC_TYPE] : self::DEFAULT_GENERIC_TYPE);
            foreach ($processedData[$inputFieldName] as $mapKey => $mapItem) {
                if (!array_key_exists($mapKey, $result)) {
                    $result[$mapKey] = [];
                }
                switch ($type) {
                    case self::VAL_GENERIC_TYPE_INDEX:
                        $result[$mapKey][$newFieldName] = $pretext . $mapKey . $posttext;
                        break;
                    case self::VAL_GENERIC_TYPE_CONSTANT:
                        $result[$mapKey][$newFieldName] = $pretext . $posttext;
                        break;
                    default:
                        throw new TimerException(
                            'The given type `' . $type . '` is unknown. for the mapping of `' . $outputFieldName . '`. ' .
                            'Check the typoscript-configuration of your dataprocessor `' . self::class . '`.',
                            1668757843
                        );
                }
            }
        }
        foreach (($processorConfiguration[self::ATTR_MAPPING_DOT] ?? []) as $oldFileName => $mappingText) {
            $newFieldName = ((!empty($mappingText)) ? (string)$mappingText : $oldFileName);
            foreach ($processedData[$inputFieldName] as $mapKey => $mapItem) {
                if ((array_key_exists($newFieldName, $result[$mapKey])) ||
                    (!array_key_exists($oldFileName, $mapItem))
                ) {
                    throw new TimerException(
                        'The new fieldname `' . $newFieldName . '` is already defined in the new mapping-array ' .
                        'or the old fieldname `' . $oldFileName . '` is not defined. ' .
                        'Overriding is forbidden and a mapping-field must be defined. ' .
                        'Check the typoscript-configuration of your dataprocessor `' . self::class . '`.',
                        1668757952
                    );
                }
                $result[$mapKey][$newFieldName] = $mapItem[$oldFileName];
            }
        }
        switch ($outputFormat) {
            case self::VAL_OUTPUT_FORMAT_JSON:
                $processedData[$outputFieldName] = json_encode(
                    $result,
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                );
                break;
            case self::VAL_OUTPUT_FORMAT_ARRAY:
                $processedData[$outputFieldName] = $result;
                break;
            default:
                throw new TimerException(
                    'The outputformat `' . $outputFormat . '` is not defined. ' .
                    'Allowed are only one of the case-sensitive list `' . self::VAL_OUTPUT_FORMAT_JSON . ', ' . self::VAL_OUTPUT_FORMAT_ARRAY . '`.' .
                    'Check the typoscript-configuration of your dataprocessor `' . self::class . '`.',
                    1668762815
                );
        }

        // allow the call of a Dataprocessor in a dataprocessor
        return $processedData;
    }
}
