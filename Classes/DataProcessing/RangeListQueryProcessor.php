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

use DateTime;
use DateTimeZone;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\DataProcessing\Trait\GeneralDataProcessorTrait;
use Porthd\Timer\DataProcessing\Trait\GeneralDataProcessorTraitInterface;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Domain\Model\InternalFlow\LoopLimiter;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Services\HolidaycalendarService;
use Porthd\Timer\Services\ListOfEventsService;
use Porthd\Timer\Utilities\DateTimeUtility;
use Porthd\Timer\Utilities\TcaUtility;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\CacheService;
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
 *     dataProcessing.10 = Porthd\Timer\DataProcessing\RangeListQueryProcessor
 *     dataProcessing.10 {
 *         # if no of the follwoing parameter ist set, the list contains 10 events at least
 *         # It is similiar defined to the parameter in the viewhelper RangeList
 *         # `maxLate` define the limit date to stop the list of events. The format is defined by `datetimeFormat` (default: Y-m-d h:i)
 *         # `maxCount` define the number of events limt date to stop the list of events
 *
 *         # regular if syntax
 *         # if.isTrue.field = record
 *
 *         # define the list of parameters, which are similiar to the viewhelper RangeList
 *         # the shown values are the optional parameters
 *         # time-zone of the frontend
 *         # timezone =
 *
 *         # if `datetimeStart` ist not defined, the current date will be used
 *         # datetimeStart =
 *
 *         # if `datetimeFormat` ist not defined, you have to use the format 'Y-m-d h:i'
 *         # for the coding of dates see https://www.php.net/manual/en/datetime.createfromformat.php
 *         # datetimeFormat =
 *
 *         # 'Reverse equals to true' mean, that all End-limits of event are lower or equal to the datestatLimit in `datetimeStart`
 *         # 'Reverse equals to false' mean, that all start-limits of events are equal ior greater than the datestatLimit in `datetimeStart`
 *         # reverse = true # default false
 *
 *         # the table name from which the data is fetched from
 *         # + stdWrap
 *         table = tx_timer_domain_model_event
 *
 *         # All properties from .select :ref:`select` can be used directly
 *         # + stdWrap
 *         orderBy = sorting
 *
 *         # The target variable to be handed to the ContentObject again, can
 *         # be used in Fluid e.g. to iterate over the objects. defaults to
 *         # "records" when not defined
 *         # + stdWrap
 *         as = myevents
 *
 *     }
 *
 */
class RangeListQueryProcessor implements DataProcessorInterface, GeneralDataProcessorTraitInterface
{

    use GeneralDataProcessorTrait;
    use LoggerAwareTrait;

    protected const PARAMETER_TABLE = 'table';

    /**
     * @var ContentDataProcessor
     */
    protected $contentDataProcessor;

    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @var CacheService
     */
    protected $cacheManager;

    /**
     * @param FrontendInterface $cache
     * @param CacheService $cacheManager
     * @param ContentDataProcessor $contentDataProcessor
     */
    public function __construct(FrontendInterface    $cache,
                                CacheService         $cacheManager,
                                ContentDataProcessor $contentDataProcessor)
    {
        $this->cache = $cache;
        $this->cacheManager = $cacheManager;
        $this->contentDataProcessor = $contentDataProcessor;
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
    ): array
    {
        // Reasons to stop this dataprocessor
        $tableName = $cObj->stdWrapValue(self::PARAMETER_TABLE, $processorConfiguration);
        if ((empty($tableName)) ||
            (
                (array_key_exists(TimerConst::ARGUMENT_IF_DOT, $processorConfiguration)) &&
                (!$cObj->checkIf($processorConfiguration[TimerConst::ARGUMENT_IF_DOT]))
            )
        ) {
            return $processedData;
        }

        // prepare caching
        [$pageUid, $pageContentOrElementUid, $cacheIdentifier] = $this->generateCacheIdentifier($processedData);
        $myResult = $this->cache->get($cacheIdentifier);
        if ($myResult === false) {
            [$cacheTime, $cacheCalc] = $this->detectCacheTimeSet($cObj, $processorConfiguration);
            // Reading the current data instead of $GLOBALS['EXEC_TIME']
            $currentTimestamp = (int)(GeneralUtility::makeInstance(Context::class))
                ->getPropertyFromAspect('date', 'timestamp');

            if (array_key_exists(self::PARAMETER_TABLE . '.', $processorConfiguration)) {
                unset($processorConfiguration[self::PARAMETER_TABLE . '.']);
            }
            if (array_key_exists(self::PARAMETER_TABLE, $processorConfiguration)) {
                unset($processorConfiguration[self::PARAMETER_TABLE]);
            }

            // The variable to be used within the result
            $targetVariableName = $cObj->stdWrapValue(TimerConst::ARGUMENT_AS, $processorConfiguration, 'records');

            // Execute a SQL statement to fetch the records
            $records = $cObj->getRecords($tableName, $processorConfiguration);

            $eventsTimerList = $records;
            /** @var LoopLimiter $loopLimiter */
            $loopLimiter = new LoopLimiter();
            // use parameter datetimeFormat && reverse
            ListOfEventsService::getDatetimeRestrictions($cObj, $processorConfiguration, $loopLimiter);
            $timerEventZone = $this->validateInternArguments(
                $cObj,
                $processorConfiguration,
                $loopLimiter->getDatetimeFormat()
            );
            // use paremeter maxCount, maxGap maxLate
            ListOfEventsService::getListRestrictions($cObj, $processorConfiguration, $loopLimiter, $timerEventZone);

            $listOfEvents = ListOfEventsService::generateEventsListFromTimerList(
                $eventsTimerList,
                $timerEventZone,
                $loopLimiter
            );


            $processedRecordVariables = [];
            $flagStopTimer = false;
            $dateTimeStopCase = new DateTime('@' . $currentTimestamp);
            foreach ($listOfEvents as $key => $record) {
                $processedRecordVariables[$key] = ['data' => $record];
                // check for more dataProcessor to act.
                $processedRecordVariables[$key] = $this->contentDataProcessor->process(
                    $cObj,
                    $processorConfiguration,
                    $processedRecordVariables[$key]
                );
                if ($record['range']->getBeginning()->getTimestamp() > $currentTimestamp) {
                    if ($flagStopTimer) {
                        if ($dateTimeStopCase > $record['range']->getBeginning()) {
                            $dateTimeStopCase = clone $record['range']->getBeginning();
                        }
                    } else {
                        $dateTimeStopCase = clone $record['range']->getBeginning();
                        $flagStopTimer = true;
                    }
                }

            }

            // the caching-times depend on the next change in the future
            // null = defaultvalue for cachetime
            $myLifeTime = $this->calculateSimpleTimeDependedCacheTime($cacheTime, $cacheCalc, $dateTimeStopCase, $currentTimestamp);
            if ($myLifeTime !== null) {
                $myTags = [
                    'pages_' . $pageUid,
                    'pages',
                    'rangeListQuery_' . $pageContentOrElementUid,
                    'rangeListQuery',
                ];
                $myResult = [
                    'as' => $targetVariableName,
                    'rangeListQuery' => $processedRecordVariables,
                ];
                // clear page-cache
                // todo build a singleton, to call this only once in a request
                $this->cacheManager->clearPageCache([$pageUid]);
                $this->cache->set($cacheIdentifier, $myResult, $myTags, $myLifeTime);
            }
        }
        // The result in the holiday-list should not be deleted or schould have priority.
        if (!empty($myResult)) {
            $processedData[$myResult['as']] = $myResult['rangeListQuery'];
        }
        return $processedData;
    }

    /**
     * @param ContentObjectRenderer $cObj
     * @param array<mixed> $arguments
     * @param string $timeFormat
     * @return DateTime
     * @throws TimerException
     */
    protected function validateInternArguments(
        ContentObjectRenderer $cObj,
        array  $arguments,
        string $timeFormat = TimerInterface::TIMER_FORMAT_DATETIME
    ): DateTime
    {
        $timeZone = ((array_key_exists(TimerConst::ARGUMENT_ACTIVEZONE, $arguments)) ?: date_default_timezone_get());
        if (!TcaUtility::isTimeZoneInList($timeZone)) {
            throw new TimerException(
                'The given timezone `' . $timeZone . '` is unknown. Check the spelling and upper-/lower-case.',
                1248729524
            );
        }
        if (array_key_exists(TimerConst::ARGUMENT_DATETIME_START, $arguments)) {
            if (
                (
                $frontendDateTime = DateTime::createFromFormat(
                    $timeFormat,
                    $arguments[TimerConst::ARGUMENT_DATETIME_START],
                    new DateTimeZone($timeZone)
                )
                ) === false
            ) {
                throw new TimerException(
                    'The date-string `' . $arguments[TimerConst::ARGUMENT_DATETIME_START] .
                    '` could not converted to a datetime-Object. ' .
                    'Check your format `' . $timeFormat . '` for datetime in the typoscript of the ' .
                    'dataprocessor `RangeListQueryProcessor` and your datetime-zone `' . $timeZone . '`. ',
                    1673162970
                );
            }
        } else {
            $utcTime = DateTimeUtility::getCurrentTime();
            $frontendDateTime = new DateTime('@' . $utcTime);
            $frontendDateTime->setTimezone(new DateTimeZone($timeZone));
        }
        return $frontendDateTime;
    }
}
