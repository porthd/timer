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

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\PeriodListTimer;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 *     dataProcessing.10 = Porthd\Timer\DataProcessing\PeriodListDataProcessor
 *     dataProcessing.10 {
 *          # regular if syntax
 *          #if.isTrue.field = record
 *
 *          limit {
 *          lower = TEXT
 *              lower {
 *                  data = date:U
 *                  strftime = %Y-%m-%d %H:%M:%S
 *              }
 *
 *              #upper = TEXT
 *              #upper {
 *              #    data = date:U
 *              #    strftime = %Y-%m-%d %H:%M:%S#
 *              #}
 *          }
 *
 *          dateToString {
 *              startJson {
 *                  # use the format-parameter defined in https://www.php.net/manual/en/datetime.format.php
 *                  # escaping of named parameters with the backslash in example \T
 *                  format = Y-m-d\TH:i:s
 *                  # allowed are only `diffDaysDatetime`, `startDatetime` und `endDatetime`,because these are automatically created datetime-Object for the list
 *                  #   These fields are datetime-object and they are generated from the estimated fields `start`and `stop` by this dataprocessor
 *                  source = startDatetime
 *              }
 *              endJson {
 *                  format = Y-m-d\TH:i:s
 *                  source = stopDatetime
 *              }
 *          }
 *
 *          maxCount = 10
 *          # output variable with the resulting list
 *          # default-value is `periodlist`
 *          as = periodlist
 *
 *          ## default is `flagStart = 1`,  => that the upper and lower limit use the attribute `start` as reference for the list
 *          ## default is `flagStart = 0`,  => that the upper and lower limit use the attribute `stop` as reference for the list
 *          #flagStart = false
 *
 *     }
 *
 */
class PeriodlistProcessor implements DataProcessorInterface
{
    use LoggerAwareTrait;

    //    required Attributes

    // optional Attribute perhaps with main defaults
    protected const ATTR_IF_DOT = 'if.';
    protected const ATTR_FLEX_FIELD = 'field';
    protected const DEFAULT_FLEX_FIELD = TimerConst::TIMER_FIELD_FLEX_ACTIVE;
    protected const ATTR_FLEX_SELECTORFIELD = 'selectorfield';
    protected const DEFAULT_SELECTOR_FIELD = 'tx_timer_selector';
    protected const ATTR_LIMIT_DOT_LIST = 'limit.';
    protected const ATTR_LIMIT_DOT_LOWER = 'lower';
    protected const ATTR_LIMIT_DOT_UPPER = 'upper';
    protected const ATTR_FLAG_START = 'flagStart';
    protected const ATTR_MAX_COUNT = 'maxCount';
    protected const ATTR_TIME_ZONE_DEFAULT = 'defaultZone';
    protected const ATTR_RESULT_VARIABLE_NAME = 'as';
    protected const ADDITIONAL_POST_KEY_FOR_START_DATETIME = 'startDatetime';
    protected const ADDITIONAL_POST_KEY_FOR_STOP_DATETIME = 'stopDatetime';
    protected const ADDITIONAL_POST_KEY_FOR_DIFF_DAYS = 'diffDaysDatetime';
    protected const ADDITIONAL_POST_KEY_LIST = [
        self::ADDITIONAL_POST_KEY_FOR_START_DATETIME,
        self::ADDITIONAL_POST_KEY_FOR_STOP_DATETIME,
        self::ADDITIONAL_POST_KEY_FOR_DIFF_DAYS,
    ];


    protected const OUTPUT_KEY_DATA = 'data';
    protected const DEFAULT_MAX_COUNT = '25';
    protected const DEFAULT_RESULT_VARIABLE_NAME = 'periodlist';


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
        if (array_key_exists(self::ATTR_FLEX_FIELD, $processorConfiguration)) {
            $flexFieldName = $cObj->stdWrapValue(self::ATTR_FLEX_FIELD, $processorConfiguration, false);
        } else {
            $flexFieldName = self::DEFAULT_FLEX_FIELD;
        }
        if (array_key_exists(self::ATTR_FLEX_SELECTORFIELD, $processorConfiguration)) {
            $selectorFieldName = $cObj->stdWrapValue(self::ATTR_FLEX_SELECTORFIELD, $processorConfiguration, false);
        } else {
            $selectorFieldName = self::DEFAULT_SELECTOR_FIELD;
        }
        if ((array_key_exists(self::ATTR_IF_DOT, $processorConfiguration) && !$cObj->checkIf($processorConfiguration[self::ATTR_IF_DOT])) ||
            (!array_key_exists($selectorFieldName, $processedData[self::OUTPUT_KEY_DATA])) ||
            (empty($processedData[self::OUTPUT_KEY_DATA][$flexFieldName])) ||
            (trim($processedData[self::OUTPUT_KEY_DATA][$selectorFieldName]) !== PeriodListTimer::TIMER_NAME)
        ) {
            return $processedData;
        }

        // detect the current list in the yaml-file
        $periodListTimer = GeneralUtility::makeInstance(PeriodListTimer::class);
        $yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $flexFormParameterString = $processedData[self::OUTPUT_KEY_DATA][$flexFieldName];
        $flexFormParamRawList = GeneralUtility::xml2array($flexFormParameterString);
        $paramList = TcaUtility::flexformArrayFlatten($flexFormParamRawList);

        if (!array_key_exists(PeriodListTimer::ARG_YAML_PERIOD_FILE_PATH, $paramList)) {
            return $processedData;
        }
        // Make FAL in timer usable by defining the corresponding table and uid
        $paramList[TimerConst::TIMER_RELATION_TABLE] = 'tt_content';
        $paramList[TimerConst::TIMER_RELATION_UID] = (int)$processedData['data']['uid'];
        $yamlFile = $paramList[PeriodListTimer::ARG_YAML_PERIOD_FILE_PATH];
        $rawResultFile = CustomTimerUtility::readListFromYamlFileFromPathOrUrl($yamlFile, $yamlFileLoader, $periodListTimer, $this->logger);
        $rawResultFile = $rawResultFile[PeriodListTimer::YAML_MAIN_KEY_PERIODLIST] ?? [];
        $yamlFal = $paramList[PeriodListTimer::ARG_YAML_PERIOD_FAL_INFO];
        $rawResultFalList = CustomTimerUtility::readListFromYamlFilesInFal(
            $yamlFal,
            $paramList[TimerConst::TIMER_RELATION_TABLE],
            $paramList[TimerConst::TIMER_RELATION_UID],
            $yamlFileLoader,
            $this->logger
        );
        $rawResultFal = array_column($rawResultFalList, PeriodListTimer::YAML_MAIN_KEY_PERIODLIST);

        $rawResult = array_merge($rawResultFile, ...$rawResultFal);
        // detect the other parameters
        $upper = null;
        $lower = null;
        $lowerDateString = null;
        $upperDateString = null;
        if (array_key_exists(self::ATTR_LIMIT_DOT_LIST, $processorConfiguration)) {
            $lowerDateString = $cObj->stdWrapValue(
                self::ATTR_LIMIT_DOT_LOWER,
                $processorConfiguration[self::ATTR_LIMIT_DOT_LIST],
                null
            );
            $lower = (
                ($lowerDateString !== null) ?
                date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $lowerDateString) :
                null
            );
            $upperDateString = $cObj->stdWrapValue(
                self::ATTR_LIMIT_DOT_UPPER,
                $processorConfiguration[self::ATTR_LIMIT_DOT_LIST],
                null
            );
            $upper = (
                ($lowerDateString !== null) ?
                date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME, $upperDateString) :
                null
            );
        }
        $flagStart = true;
        if (array_key_exists(self::ATTR_FLAG_START, $processorConfiguration)) {
            $flagValue = $cObj->stdWrapValue(self::ATTR_FLAG_START, $processorConfiguration, true);
            $flagValue = is_string($flagValue) ? trim(strtolower($flagValue)) : $flagValue;
            $flagStart = (in_array($flagValue, [0, '', '0', false, null, 'false', 0.0, []], true) ?
                false :
                true);
        }
        // The variable to be used within the result
        $maxCount = (int)$cObj->stdWrapValue(self::ATTR_MAX_COUNT, $processorConfiguration, self::DEFAULT_MAX_COUNT);
        $targetVariableName = $cObj->stdWrapValue(
            self::ATTR_RESULT_VARIABLE_NAME,
            $processorConfiguration,
            self::DEFAULT_RESULT_VARIABLE_NAME
        );

        // sortiere $rawResult
        $referenceKey = (
            ($flagStart) ?
            PeriodListTimer::YAML_ITEMS_KEY_START :
            PeriodListTimer::YAML_ITEMS_KEY_STOP
        );
        usort($rawResult, function ($item1, $item2) use ($referenceKey) {
            return $item1[$referenceKey] <=> $item2[$referenceKey];
        });

        $result = [];

        foreach ($rawResult as $item) {
            if ($maxCount <= 0) {
                break;
            }
            if (($lower === null) ||
                (
                    ($lowerDateString !== null) &&
                ($lowerDateString < $item[$referenceKey])
                )
            ) {
                if (($upper !== null) &&
                    ($upperDateString !== null) &&
                    ($upperDateString > $item[$referenceKey])
                ) {
                    break;
                }
                $item[self::ADDITIONAL_POST_KEY_FOR_START_DATETIME] = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $item[PeriodListTimer::YAML_ITEMS_KEY_START]
                );
                $item[self::ADDITIONAL_POST_KEY_FOR_STOP_DATETIME] = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $item[PeriodListTimer::YAML_ITEMS_KEY_STOP]
                );
                $item[self::ADDITIONAL_POST_KEY_FOR_DIFF_DAYS] = ceil(
                    abs(
                        $item[self::ADDITIONAL_POST_KEY_FOR_START_DATETIME]->getTimestamp() -
                        $item[self::ADDITIONAL_POST_KEY_FOR_STOP_DATETIME]->getTimestamp()
                    ) / 86400
                );
                $result[] = $item;
                $maxCount--;
            }
        }

        foreach (($processorConfiguration['dateToString.'] ?? []) as $newFieldKeyDot => $params) {
            $refField = $params['source'];
            if (!in_array($refField, self::ADDITIONAL_POST_KEY_LIST, true)) {
                throw new TimerException(
                    'The source-field has defined `' . $refField . '`. Only allowed are the parameter ' .
                    implode(' and ', self::ADDITIONAL_POST_KEY_LIST) . '.' .
                    ' Check your typoscript-definition.',
                    1668761336
                );
            }
            $newFieldKey = trim($newFieldKeyDot, '.');
            $format = $params['format'];
            foreach ($result as $key => $item) {
                if (array_key_exists($newFieldKey, $result[$key])) {
                    throw new TimerException(
                        'The action `' . 'dateToString.' . '` want to write to the existing field `' . $newFieldKey .
                        '` in your dataprocessor `' . self::class . '`. Check your typoscript-definition.',
                        1668758652
                    );
                }
                $result[$key][$newFieldKey] = $item[$refField]->format($format);
                if ($result[$key][$newFieldKey] === false) {
                    throw new TimerException(
                        'The format `' . print_r($format, true) . '` caused an error in your ' .
                        'dataprocessor `' . self::class . '`. Check your typoscript-definition.',
                        1668761698
                    );
                }
            }
        }
        $processedData[$targetVariableName] = $result;
        // allow the call of a Dataprocessor in a dataprocessor
        return $processedData;
    }
}
