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
 * Example TypoScript configuration for periodically changing backgroundimages :
 *
 * lib.dataprocessor.timer.media.pages >
 * lib.dataprocessor.timer.media.pages  = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
 * lib.dataprocessor.timer.media.pages {
 *     #    references.fieldName = media
 *     #    references.table = pages
 *     references.data = levelmedia: -1, slide
 *     as = myfiles
 *     dataProcessing {
 *         10 = Porthd\Timer\DataProcessing\SortListQueryProcessor
 *         10 {
 *             # name of the inputfield with the field, which contains the timerdefinition
 *             # default: `myrecords`
 *             fieldName = myfiles
 *             # name of the outputfiled in the fluid-template
 *             # default: `sortedrecords`
 *             as = mysortedfiles
 *
 *             # The dataprocessor will use the timerdefinition to produce with the nextActive-method a stream of event
 *             # The stream will be sorted by the starttimes or by the endtime, if reverse is true.
 *             # the maxcount defines the number of entries. (Be careful. Think about the performance)
 *             maxCount = 11
 *             # planed
 *             # Instead of the `maxcount` it should although be allowed to use the maxdate to define upper/lower limit of your list.
 *             # The definition of `maxcount` will override this definition.
 *             # The dateTimeFormat is defined by `datetimeFormat` (see below) or its default
 *             # maxLate = 2025-01-01 00:00:00
 *             # Instead of a discret date in `maxLate` you can use a relative timegap, too. Use the parameter `maxGap` for it.
 *             # The definition of `maxLate` will override this parameter.
 *             # The dateTimeFormat is defined by `datetimeFormat` (see below) or its default
 *             # maxGap = P1M
 *             # List-length-order from high to low: `maxCount` > `maxLate` > `maxGap`
 *             # If all three are present, the parameter `maxCount`with the value 25 will be used as fallback.
 *
 *             # reverse = false: The ordering will grow into the future
 *             # reverse = true: The ordering will grow into the past
 *             reverse = false
 *
 *             # Define the timezone, which should be used. The Name of the timezone must be valid.
 *             # Default: it is defined in the AdditionalConfiguration.php or in the LocalConfiguration.php or by the PHP-settings
 *             # Example:
 *             # timezone = Europe/Berlin
 *
 *             # Define the Dateformat for your datetimeStart.
 *             # Use the notation of the php-function DateTime::createFromFormat(...)
 *             # Default is `Y-m-d H:i:s` i.e. `2023-01-16 07:05:03`
 *             # datetimeFormat = Y-m-d H:i:s
 *
 *             # Define the start/end-date for your list in the predefined format.
 *             # Default: current dateTime
 *             # datetimeStart = 2023-01-16 07:05:03;
 *
 *         }
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
        if (array_key_exists('if.', $processorConfiguration) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        // If the field is not given, exit
        $fieldName = $cObj->stdWrapValue(TimerConst::ARGUMENT_FIELDNAME, $processorConfiguration, 'myrecords');
        if ((empty($fieldName)) || (empty($processedData[$fieldName]))) {
            return $processedData;
        }

        // The variable to be used within the result
        $targetVariableName = $cObj->stdWrapValue(TimerConst::ARGUMENT_AS, $processorConfiguration, 'sortedrecords');

        // get recordlist from former processed datas
        $imageList = $processedData[$fieldName];

        /** @var LoopLimiter $loopLimiter */
        $loopLimiter = new LoopLimiter();
        ListOfEventsService::getDatetimeRestrictions($cObj, $processorConfiguration, $loopLimiter);
        $timerEventZone = self::validateInternArguments($cObj, $processorConfiguration, $loopLimiter->getDatetimeFormat());
        ListOfEventsService::getListRestrictions($cObj, $processorConfiguration, $loopLimiter, $timerEventZone);

        $listOfEvents = ListOfEventsService::generateEventsListFromTimerList(
            $imageList,
            $timerEventZone,
            $loopLimiter
        );


        $processedRecordVariables = [];
        foreach ($listOfEvents as $key => $record) {
            $processedRecordVariables[$key] = ['data' => $record];
        }

        $processedData[$targetVariableName] = $processedRecordVariables;

        return $processedData;
    }

    /**
     * @param array<mixed> $arguments
     * @return DateTime
     * @throws TimerException
     */
    protected static function validateInternArguments(
        ContentObjectRenderer $cObj,
        array $arguments,
        string $timeFormat = TimerInterface::TIMER_FORMAT_DATETIME
    ): DateTime {
        $timeZone = $cObj->stdWrapValue(TimerConst::ARGUMENT_ACTIVEZONE, $arguments, date_default_timezone_get());
        if (!TcaUtility::isTimeZoneInList($timeZone)) {
            throw new TimerException(
                'The given timezone `' . $timeZone . '` is unknown. Check the spelling and upper-/lower-case.',
                1248729524
            );
        }

        $flagDateStarttime = (
            array_key_exists(TimerConst::ARGUMENT_DATETIME_START, $arguments) ||
            (array_key_exists(TimerConst::ARGUMENT_DATETIME_START.'.', $arguments))
        );

        if ($flagDateStarttime) {
            $startTimeString = $cObj->stdWrapValue(TimerConst::ARGUMENT_DATETIME_START, $arguments);
            if (
                (
                    $frontendDateTime = DateTime::createFromFormat(
                        $timeFormat,
                        $startTimeString,
                        new DateTimeZone($timeZone)
                    )
                ) === false
            ) {
                throw new TimerException(
                    'The date-string `' . $arguments[TimerConst::ARGUMENT_DATETIME_START] . '` could not converted to a datetime-Object. ' .
                    'Check your format `' . $timeFormat . '` for datetime in the typoscript of the '.
                    'dataprocessor `SortListQueryProcessor` and your datetime-zone `' . $timeZone . '`. ',
                    1673162866
                );
            }
            return $frontendDateTime;
        }
        $utcTime = DateTimeUtility::getCurrentTime();
        $frontendDateTime = new DateTime('@' . $utcTime);
        $frontendDateTime->setTimezone(new DateTimeZone($timeZone));
        return $frontendDateTime;
    }
}
