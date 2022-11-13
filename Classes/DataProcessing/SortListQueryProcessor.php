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
use Porthd\Timer\Domain\Model\InternalFlow\LoopLimiter;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
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
 * tt_content.timer_timersimul.20 = FLUIDTEMPLATE
 * tt_content.timer_timersimul.20 {
 *
 *     dataProcessing.10 = Porthd\Timer\DataProcessing\RangeListQueryProcessor
 *     dataProcessing.10 {
 *         # regular if syntax
 *         # if.isTrue.field = record
 *
 *         # name of the corroeponding field, which contain al list of timerbased
 *         field = myfiles
 *
 *         # The target variable to be handed to the ContentObject again, can
 *         # be used in Fluid e.g. to iterate over the objects. defaults to
 *         # "records" when not defined
 *         # + stdWrap
 *         as = myevents
 *
 *         # number of resulting objects, which should be given by sorted order default-Value is 25
 *         hartBreak = 11
 *
 *         # define the given order - next or prev default is next
 *         # direction previous => 'Reverse equals to true' mean, that all End-limits of event are lower or equal to the datestatLimit in `datetimeStart`
 *         # direction next => 'Reverse equals to false' mean, that all start-limits of events are equal ior greater than the datestatLimit in `datetimeStart`
 *         # reverse = true # default false (direction next)
 *
 *         # `maxLate` define the limit date to stop the list of events. The format is defined by `datetimeFormat` (default: Y-m-d h:i)
 *         # `maxCount` define the number of events limt date to stop the list of events
 *     }
 * }
 *
 */
class SortListQueryProcessor implements DataProcessorInterface
{
    /**
     * @var ContentDataProcessor
     */
    protected $contentDataProcessor;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
    }

    /**
     * Fetches records from the database as an array
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     *
     * @return array the processed data as key/value store
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        // If the field is not given, exit
        $fieldName = $cObj->stdWrapValue('fieldName', $processorConfiguration, 'myrecords');
        if ((empty($fieldName)) || (empty($processedData[$fieldName]))) {
            return $processedData;
        }

        // The variable to be used within the result
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'sortedrecords');

        // get recordlist from former processed datas
        $imageList = $processedData[$fieldName];

        $timerEventZone = self::validateInternArguments($processorConfiguration);
        /** @var LoopLimiter $loopLimiter */
        $loopLimiter = ListOfEventsService::getListRestrictions($processorConfiguration, $timerEventZone);
        $flagReverse = ((isset($processorConfiguration[TimerConst::ARGUMENT_REVERSE])) ?
            (in_array($processorConfiguration[TimerConst::ARGUMENT_REVERSE], [1, true, 'true', 'TRUE', '1'])) :
            false
        );
        $maxCount = (((isset($processorConfiguration[TimerConst::ARGUMENT_MAX_COUNT])) && ((int)$processorConfiguration[TimerConst::ARGUMENT_MAX_COUNT] > 0)) ?
            ((int)$processorConfiguration[TimerConst::ARGUMENT_MAX_COUNT]) :
            TimerConst::SAVE_LIMIT_MAX_EVENTS
        );
        $listOfEvents = ListOfEventsService::generateEventsListFromTimerList(
            $imageList,
            $timerEventZone,
            $loopLimiter,
            $flagReverse,
            $maxCount
        );


        $processedRecordVariables = [];
        foreach ($listOfEvents as $key => $record) {
            $processedRecordVariables[$key] = ['data' => $record];
        }

        $processedData[$targetVariableName] = $processedRecordVariables;

        return $processedData;
    }

    /**
     * @param array $arguments
     * @return DateTime
     * @throws TimerException
     */
    protected static function validateInternArguments(array $arguments): DateTime
    {
        $timeZone = ((isset($arguments[TimerConst::ARGUMENT_ACTIVEZONE])) ?: date_default_timezone_get());
        if (!TcaUtility::isTimeZoneInList($timeZone)) {
            throw new TimerException(
                'The given timezone `' . $timeZone . '` is unkopnw. Check the spelling and upper-/lower-case.',
                1248729524
            );
        }
        if (isset($arguments[TimerConst::ARGUMENT_DATETIME_START])) {
            $timeFormat = ((isset($arguments[TimerConst::ARGUMENT_DATETIME_FORMAT])) ?
                $arguments[TimerConst::ARGUMENT_DATETIME_FORMAT] :
                TimerInterface::TIMER_FORMAT_DATETIME
            );
            if (
                ($frontendDateTime = DateTime::createFromFormat(
                    $timeFormat,
                    $arguments[TimerConst::ARGUMENT_DATETIME_START],
                    new DateTimeZone($timeZone))
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
            $frontendDateTime->setTimezone( new DateTimeZone($timeZone));
        }
        return $frontendDateTime;

    }

}
