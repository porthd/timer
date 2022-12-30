<?php

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
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Domain\Model\InternalFlow\LoopLimiter;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Services\ListOfEventsService;
use Porthd\Timer\Utilities\DateTimeUtility;
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
 *
 *     dataProcessing.10 = Porthd\Timer\DataProcessing\RangeListQueryProcessor
 *     dataProcessing.10 {
 *         # if no of the follwoing parameter ist set, the list contains 10 events at least
 *         # It is similiar defined to the parameter in the viewhelper RangeList
 *         # `maxLate` define the limit date to stop the list of events. The format is defined by `datetimeFormat` (default: Y-m-d h:i)
 *         # `maxCount` define the number of events limt date to stop the list of events
 *         # public const ARGUMENT_MAX_LATE = 'maxLate';
 *         # regular if syntax
 *         # if.isTrue.field = record
 *
 *         # if the starttime is different from the current time.
 *         # datetimeStart =
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
 *         pidInList = 13,14
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
class RangeListQueryProcessor implements DataProcessorInterface
{
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
    ): array {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        // the table to query, if none given, exit
        $tableName = $cObj->stdWrapValue('table', $processorConfiguration);
        if (empty($tableName)) {
            return $processedData;
        }
        if (isset($processorConfiguration['table.'])) {
            unset($processorConfiguration['table.']);
        }
        if (isset($processorConfiguration['table'])) {
            unset($processorConfiguration['table']);
        }

        // The variable to be used within the result
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'records');

        // Execute a SQL statement to fetch the records
        $records = $cObj->getRecords($tableName, $processorConfiguration);

        $eventsTimerList = $records;
        $timerEventZone = self::validateInternArguments($processorConfiguration);
        /** @var LoopLimiter $loopLimiter */
        $loopLimiter = ListOfEventsService::getListRestrictions($processorConfiguration, $timerEventZone);
        $flagReverse = (
            (isset($processorConfiguration[TimerConst::ARGUMENT_REVERSE])) ?
            (in_array($processorConfiguration[TimerConst::ARGUMENT_REVERSE], [1, true, 'true', 'TRUE', '1'])) :
            false
        );
        $maxCount = (
            ((isset($processorConfiguration[TimerConst::ARGUMENT_MAX_COUNT])) && ((int)$processorConfiguration[TimerConst::ARGUMENT_MAX_COUNT] > 0)) ?
            ((int)$processorConfiguration[TimerConst::ARGUMENT_MAX_COUNT]) :
            TimerConst::SAVE_LIMIT_MAX_EVENTS
        );
        $listOfEvents = ListOfEventsService::generateEventsListFromTimerList(
            $eventsTimerList,
            $timerEventZone,
            $loopLimiter,
            $flagReverse,
            $maxCount
        );


        $processedRecordVariables = [];
        foreach ($listOfEvents as $key => $record) {
            /** @var ContentObjectRenderer $recordContentObjectRenderer */
            $recordContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $recordContentObjectRenderer->start($record, $tableName);
            $processedRecordVariables[$key] = ['data' => $record];
            $processedRecordVariables[$key] = $this->contentDataProcessor->process($recordContentObjectRenderer, $processorConfiguration, $processedRecordVariables[$key]);
        }

        $processedData[$targetVariableName] = $processedRecordVariables;

        return $processedData;
    }

    /**
     * @param array<mixed> $arguments
     * @return DateTime
     * @throws TimerException
     */
    protected static function validateInternArguments(array $arguments): DateTime
    {
        $timeZone = ((isset($arguments[TimerConst::ARGUMENT_ACTIVEZONE])) ?: date_default_timezone_get());
        if (!TcaUtility::isTimeZoneInList($timeZone)) {
            throw new TimerException(
                'The given timezone `' . $timeZone . '` is unknown. Check the spelling and upper-/lower-case.',
                1248729524
            );
        }
        if (isset($arguments[TimerConst::ARGUMENT_DATETIME_START])) {
            $timeFormat = (
                (isset($arguments[TimerConst::ARGUMENT_DATETIME_FORMAT])) ?
                $arguments[TimerConst::ARGUMENT_DATETIME_FORMAT] :
                TimerInterface::TIMER_FORMAT_DATETIME
            );
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
                    'The date-string `' . $arguments[TimerConst::ARGUMENT_DATETIME_START] . '`could not converted to a datetime-Object. ' .
                    'Check especially your date-time `' . $timeFormat . '` and your datetime-zone `' . $timeZone . '`. ',
                    1248733534
                );
            }
        } else {
            $utcTime = DateTimeUtility::getCurrentTime();
            $frontendDateTime = new DateTime('@' . $utcTime);
            $frontendDateTime->setTimezone(new DateTimeZone($timeZone));
        }
        if ((isset($arguments[TimerConst::ARGUMENT_MAX_LATE])) &&
            (
                false === DateTime::createFromFormat(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $arguments[TimerConst::ARGUMENT_MAX_LATE],
                    new DateTimeZone($timeZone)
                )
            )
        ) {
            throw new TimerException(
                'The date-string `' . $arguments[TimerConst::ARGUMENT_MAX_LATE] . '`could not converted to a datetime-Object. ' .
                'Check especially your format of date-time (should be: `' . TimerInterface::TIMER_FORMAT_DATETIME . '`). ',
                1648555534
            );
        }

        return $frontendDateTime;
    }
}
