<?php

declare(strict_types=1);

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

use DateTime;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\PeriodListTimer;
use Porthd\Timer\DataProcessing\Trait\GeneralDataProcessorTrait;
use Porthd\Timer\DataProcessing\Trait\GeneralDataProcessorTraitInterface;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\CacheService;
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
 *              start {
 *                  # use the format-parameter defined in https://www.php.net/manual/en/datetime.format.php
 *                  # escaping of named parameters with the backslash in example \T
 *                  format = Y-m-d\TH:i:s
 *                  # allowed are only `diffDaysDatetime`, `startDatetime` und `endDatetime`,because these are automatically created datetime-Object for the list
 *                  #   These fields are datetime-object and they are generated from the estimated fields `start`and `stop` by this dataprocessor
 *                  source = startDatetime
 *              }
 *              end {
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
 *          ## if the dataprocessor ist NOT related on the tt_content or on the pages, then define her the corrosponding table in the database for the FAL-reference
 *          #tablename = tx_something
 *
 *     }
 *
 */
class PeriodlistProcessor implements DataProcessorInterface, GeneralDataProcessorTraitInterface
{
    use GeneralDataProcessorTrait;
    use LoggerAwareTrait;

    //    required Attributes

    // optional Attribute perhaps with main defaults
    protected const ATTR_FLEX_FIELD = 'field';
    protected const ATTR_FLEX_SELECTORFIELD = 'selectorfield';
    protected const ATTR_TABLENAME = 'tablename';
    protected const ATTR_LIMIT_DOT_LIST = 'limit.';
    protected const ATTR_LIMIT_DOT_LOWER = 'lower';
    protected const ATTR_LIMIT_DOT_UPPER = 'upper';
    protected const ATTR_FLAG_START = 'flagStart';
    protected const ATTR_MAX_COUNT = 'maxCount';

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
    protected const ATTR_DATE_TO_STRING = 'dateToString';
    protected const ATTR_DATE_TO_STRING_DOT = 'dateToString.';
    protected const ATTR_DATE_TO_STRING_SOURCE = 'source';
    protected const ATTR_DATE_TO_STRING_FORMAT = 'format';

    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @var CacheService
     */
    protected $cacheManager;

    /**
     * @var YamlFileLoader
     */
    protected $yamlFileLoader;

    /**
     * @var PeriodListTimer
     */
    protected $periodListTimer;

    /**
     * @param FrontendInterface $cache
     * @param CacheService $cacheManager
     */
    public function __construct(
        FrontendInterface $cache,
        CacheService      $cacheManager,
        PeriodListTimer   $periodListTimer,
        YamlFileLoader    $yamlFileLoader
    )
    {
        $this->cache = $cache;
        $this->cacheManager = $cacheManager;
        $this->periodListTimer = $periodListTimer;
        $this->yamlFileLoader = $yamlFileLoader;
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
        array                 $contentObjectConfiguration,
        array                 $processorConfiguration,
        array                 $processedData
    )
    {
        $targetVariableName = $cObj->stdWrapValue(
            TimerConst::ARGUMENT_AS,
            $processorConfiguration,
            self::DEFAULT_RESULT_VARIABLE_NAME
        );

        // Reasons to stop this dataprocessor
        if (array_key_exists(self::ATTR_FLEX_FIELD, $processorConfiguration)) {
            $flexFieldName = $cObj->stdWrapValue(
                self::ATTR_FLEX_FIELD,
                $processorConfiguration,
                TimerConst::TIMER_FIELD_FLEX_ACTIVE
            );
        } else {
            $flexFieldName = TimerConst::TIMER_FIELD_FLEX_ACTIVE;
        }
        if (array_key_exists(self::ATTR_FLEX_SELECTORFIELD, $processorConfiguration)) {
            $selectorFieldName = $cObj->stdWrapValue(
                self::ATTR_FLEX_SELECTORFIELD,
                $processorConfiguration,
                TimerConst::TIMER_FIELD_SELECTOR
            );
        } else {
            $selectorFieldName = TimerConst::TIMER_FIELD_SELECTOR;
        }
        if ((!array_key_exists($selectorFieldName, $processedData[self::OUTPUT_KEY_DATA])) ||
            (empty($processedData[self::OUTPUT_KEY_DATA][$flexFieldName])) ||
            (
                (array_key_exists(TimerConst::ARGUMENT_IF_DOT, $processorConfiguration)) &&
                (!$cObj->checkIf($processorConfiguration[TimerConst::ARGUMENT_IF_DOT]))
            ) ||
            (trim($processedData[self::OUTPUT_KEY_DATA][$selectorFieldName]) !== PeriodListTimer::TIMER_NAME)
        ) {
            return $processedData;
        }

        // prepare caching
        [$pageUid, $pageContentOrElementUid, $cacheIdentifier] = $this->generateCacheIdentifier(
            $processedData,
            $targetVariableName
        );
        $myResult = $this->cache->get($cacheIdentifier);
        if ($myResult === false) {
            [$cacheTime, $cacheCalc] = $this->detectCacheTimeSet($cObj, $processorConfiguration);

            // detect the current list in the yaml-file
            $flexFormParameterString = $processedData[self::OUTPUT_KEY_DATA][$flexFieldName];
            $flexFormParamRawList = GeneralUtility::xml2array($flexFormParameterString);
            $paramList = TcaUtility::flexformArrayFlatten($flexFormParamRawList);

            $yamlFal = $paramList[PeriodListTimer::ARG_YAML_PERIOD_FAL_INFO] ?? '';
            // Make FAL in timer usable by defining the corresponding table and uid
            if (isset($processedData['data']['doktype'], $processedData['data']['is_siteroot'])) {
                $paramList[TimerConst::TIMER_RELATION_TABLE] = 'pages';
            } elseif (isset($processedData['data']['CType'], $processedData['data']['list_type'])) {
                $paramList[TimerConst::TIMER_RELATION_TABLE] = 'tt_content';
            } else {
                if (array_key_exists(self::ATTR_TABLENAME, $processorConfiguration)) {
                    $paramList[TimerConst::TIMER_RELATION_TABLE] = $cObj->stdWrapValue(self::ATTR_TABLENAME, $processorConfiguration, 'tt_content');
                } else {
                    if (!empty($yamlFal)) {
                        throw new TimerException(
                            ' The FAL is defined but could not be detected. The needed table for the data is missing. It is not the `tt_content` or the `pages`.' .
                            'Make a screenshot and inform the webmaster.',
                            1677394183
                        );
                    }
                }
            }
            $paramList[TimerConst::TIMER_RELATION_UID] = (int)$processedData['data']['uid'];
            if (empty(($yamlFile = $paramList[PeriodListTimer::ARG_YAML_PERIOD_FILE_PATH]))) {
                $rawResultFile = [];
            } else {
                $rawResultFile = CustomTimerUtility::readListFromFileOrUrl(
                    $yamlFile,
                    $this->yamlFileLoader,
                    $this->periodListTimer,
                    $this->logger
                );
                if (array_key_exists(PeriodListTimer::YAML_MAIN_KEY_PERIODLIST, $rawResultFile)) {
                    $rawResultFile = $rawResultFile[PeriodListTimer::YAML_MAIN_KEY_PERIODLIST];
                } // else $rawResultFile without yaml-help-layer}
            }

            $rawResultFalList = CustomTimerUtility::readListsFromFalFiles(
                $yamlFal,
                $paramList[TimerConst::TIMER_RELATION_TABLE],
                $paramList[TimerConst::TIMER_RELATION_UID],
                $this->yamlFileLoader,
                $this->logger
            );
            $resultList = [];
            // normalize about the help-layer in the yaml-file
            foreach ($rawResultFalList as $entry) {
                if (array_key_exists(PeriodListTimer::YAML_MAIN_KEY_PERIODLIST, $entry)) {
                    $resultList[] = $entry[PeriodListTimer::YAML_MAIN_KEY_PERIODLIST];
                } else {
                    $resultList[] = $entry;
                }
            }
            $rawResult = array_merge($rawResultFile, ...$resultList);
            if (empty($rawResult)) {
                throw new TimerException(
                    ' There is no periodlist defined. Please check the configuration for defined relations to periodlist-files and the existence of the declared files. ' .
                    'If everything seems okay, then make a screenshot and inform the webmaster.',
                    1677906108
                );
            }

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
                $flagStart = (in_array($flagValue, [0, '', '0', false, null, 'false', 0.0, [],], true) ?
                    false :
                    true);
            }
            // The variable to be used within the result
            $maxCount = (int)$cObj->stdWrapValue(self::ATTR_MAX_COUNT, $processorConfiguration, self::DEFAULT_MAX_COUNT);

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

            $dateTimeStopCase = '';
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

            if (array_key_exists(self::ATTR_DATE_TO_STRING_DOT, $processorConfiguration)) {
                $myHelp = $processorConfiguration[self::ATTR_DATE_TO_STRING_DOT];
            } elseif (array_key_exists(self::ATTR_DATE_TO_STRING, $processorConfiguration)) {
                $myHelp = $processorConfiguration[self::ATTR_DATE_TO_STRING];

            } else {
                $myHelp = [];
            }
            foreach ($myHelp as $newFieldKeyDot => $params) {
                $refField = $params[self::ATTR_DATE_TO_STRING_SOURCE] ?? '';
                if (!in_array($refField, self::ADDITIONAL_POST_KEY_LIST, true)) {
                    throw new TimerException(
                        'The source-field has defined `' . $refField . '`. Only allowed are the parameter ' .
                        implode(' and ', self::ADDITIONAL_POST_KEY_LIST) . '.' .
                        ' Check your typoscript-definition.',
                        1668761336
                    );
                }
                $newFieldKey = trim($newFieldKeyDot, '.');
                $format = $params[self::ATTR_DATE_TO_STRING_FORMAT] ?? TimerInterface::TIMER_FORMAT_DATETIME;
                foreach ($result as $key => $item) {
                    if (array_key_exists($newFieldKey, $result[$key])) {
                        throw new TimerException(
                            'The action `' . self::ATTR_DATE_TO_STRING_DOT . '` want to write to the existing field `' . $newFieldKey .
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

            $myTags = [
                'pages_' . $pageUid,
                'pages',
                'periodlist_' . $pageContentOrElementUid,
                'periodlist',
            ];
            $myResult = [
                'as' => $targetVariableName,
                'periodList' => $result,
            ];
            if (($cacheCalc) ||
                ($cacheTime > 0)
            ) {
                // clear page-cache
                // todo build a singleton, to call this only once in a request
                $this->cacheManager->clearPageCache([$pageUid]);
                if ($cacheCalc) {
                    $this->cache->set($cacheIdentifier, $myResult, $myTags);
                } else {
                    $this->cache->set($cacheIdentifier, $myResult, $myTags, $cacheTime);

                }
            }
        }
        if (!empty($myResult)) {
            // The result in the holiday-list should not be deleted or schould have priority.
            $processedData[$myResult['as']] = $myResult['periodList'];
        }
        // allow the call of a Dataprocessor in a dataprocessor
        return $processedData;
    }
}
