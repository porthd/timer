<?php

namespace Porthd\Timer\DataProcessing;

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

use DateInterval;
use DateTime;
use DateTimeZone;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Services\HolidaycalendarService;
use Porthd\Timer\Utilities\ConvertDateUtility;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use Psr\Log\LoggerAwareTrait;
use stdClass;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Context\Context;
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
 *
 */
class HolidaycalendarProcessor implements DataProcessorInterface
{
    use LoggerAwareTrait;

    protected const ATTR_START_DOT = 'start.';
    protected const ATTR_STOP_DOT = 'stop.';
    protected const ATTR_YEAR = 'year';
    protected const ATTR_MONTH = 'month';
    protected const ATTR_DAY = 'day';
    protected const ATTR_DAY_BEFORE = 'dayfbefore';
    protected const ATTR_CALENDAR = 'calendar';
    protected const ATTR_TIMEZONE = 'timezone';
    protected const ATTR_LOCALE = 'locale';

    protected const ATTR_ALIAS_PATH = 'aliasPath';
    protected const ATTR_ALIAS_CONFIG = 'aliasConfig';
    protected const ATTR_HOLIDAY_PATH = 'holidayPath';
    protected const ATTR_HOLIDAY_CONFIG = 'holidayConfig';
    protected const ATTR_FLEX_DB_FIELD = 'flexDbField';
    protected const ATTR_PATH_FLEX_FIELD = 'pathFlexField';
    protected const ATTR_PATH_FAL_FIELD = 'falFlexField';
    protected const ATTR_AS = 'as';

//
    protected const DEFAULT_AS = 'holidayList';
    protected const YAML_HOLIDAY_ADD = 'add';
    protected const YAML_HOLIDAY_ADD_ALIAS = 'alias';
    protected const RESULT_HOLIDAY_DATE = 'date';
    protected const RESULT_HOLIDAY_CALENDAR = 'cal';

    protected const LOCALE_EN_GB_UTF = 'en_GB.utf-8';
    const DEFAULT_START_STOP_MONTH = 1;
    const DEFAULT_START_STOP_DAY = 1;
    const DEFAULT_START_STOP_FLAG = true;

    /**
     * @var HolidaycalendarService
     */
    protected $holidaycalendarService;


    /**
     * @param HolidaycalendarService|null $holidaycalendarService
     */
    public function __construct(?HolidaycalendarService $holidaycalendarService = null)
    {
        if ($holidaycalendarService === null) {
            $this->holidaycalendarService = GeneralUtility::makeInstance(HolidaycalendarService::class);
        } else {
            $this->holidaycalendarService = $holidaycalendarService;
        }

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
        $timeRangeInfo = $this->getTimeRangeInformations($processorConfiguration, $cObj);
        $aliasPath = $this->getAliasForCalendarYaml($processorConfiguration, $cObj, $processedData);
        //  in the holidayPath is not defined, an exception will be thrown
        $holidayPath = $this->getHolidayForCalendarYaml($processorConfiguration, $cObj, $processedData);
        if (array_key_exists(self::ATTR_AS, $processorConfiguration)) {
            $as = $cObj->stdWrapValue(self::ATTR_AS, $processorConfiguration, false);
        } else {
            $as = self::DEFAULT_AS;
        }

        // Read the Yamle-Parts fram a separated aliasfile
        $aliasArray = [];
        $yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        // exetract the holiday- and alias-parts from the current holiday-File
        if (!empty($aliasPath)) {
            $aliasArray = CustomTimerUtility::readListFromFileOrUrl($aliasPath, $yamlFileLoader);
        }
        if (empty($holidayPath)) {
            throw new TimerException(
                ' The yaml-list with the holidays is not found. Ther may be an error in the typoscript.  ' .
                'Make a screenshot and inform the webmaster.',
                1677394183
            );
        }
        $holidayArray = CustomTimerUtility::readListFromFileOrUrl($holidayPath, $yamlFileLoader);

        // merge the alias-dateinto the array of the holidays
        if (!empty($aliasArray)) {
            // merge the both alias-parts and the merge them into the holiday-List
            foreach ($holidayArray as &$item) {
                if ((isset($item[self::YAML_HOLIDAY_ADD][self::YAML_HOLIDAY_ADD_ALIAS])) &&
                    (!empty($item[self::YAML_HOLIDAY_ADD][self::YAML_HOLIDAY_ADD_ALIAS])) &&
                    (!empty($aliasArray[$item[self::YAML_HOLIDAY_ADD][self::YAML_HOLIDAY_ADD_ALIAS]]))
                ) {
                    $item[self::YAML_HOLIDAY_ADD] = array_merge($aliasArray[$item[self::YAML_HOLIDAY_ADD][self::YAML_HOLIDAY_ADD_ALIAS]],
                        $item[self::YAML_HOLIDAY_ADD]);
                }
            }
        }

        // Generate the list of holidays for the range
        $holidayList = [];
        $startDateGregorianOrig = $this->detectGregorianDate($timeRangeInfo, false);
        $startDateGregorianOrig->sub(new DateInterval('P1D'));
        $stopDateGregorian = $this->detectGregorianDate($timeRangeInfo, true);
//        $hilidayCalendarService = GeneralUtility::makeInstance(HolidaycalendarService::class);
        foreach ($holidayArray as $holiday) {
            $startDateGregorian = $startDateGregorianOrig;
            if (!$this->holidaycalendarService->forbiddenCalendar($holiday)) {
                while ($stopDateGregorian >= ($startDateGregorian = $this->holidaycalendarService->nextHoliday(
                        $timeRangeInfo->locale,
                        $startDateGregorian,
                        $holiday
                    ))
                ) {
                    $holidayList[] = [
                        self::RESULT_HOLIDAY_DATE => $startDateGregorian,
                        self::RESULT_HOLIDAY_CALENDAR => $holiday,
                    ];
                    $startDateGregorian->add(new DateInterval('P1D'));
                    // @todo allow custom hook of DataFormat
                }
            }
        }
        // @todo flag to allow sorting by date or by custom function

        // The result in the holiday-list should not be deleted or schould have priority.
        $processedData[$as] = $holidayList;

        return $processedData;
    }

    protected function detectGregorianDate(stdClass $timeRangeInfo, bool $stopTime = false)
    {
        if ($stopTime) {
            if ($timeRangeInfo->calendar === ConvertDateUtility::DEFAULT_CALENDAR) {
                $result = new DateTime();
                $result->setTimezone(new DateTimeZone($timeRangeInfo->timezone));
                $result->setTime(0, 0, 0);
                $result->setDate($timeRangeInfo->stopYear, $timeRangeInfo->stopMonth, $timeRangeInfo->stopDay);
            } else {

                $dateString = $timeRangeInfo->stopYear . '/' . $timeRangeInfo->stopMonth . '/' . $timeRangeInfo->stopDay . ' ' .
                    '00:00:00';
                $result = ConvertDateUtility::convertFromCalendarToDateTime(
                    $timeRangeInfo->locale,
                    $timeRangeInfo->calendar,
                    $dateString,
                    $timeRangeInfo->timezone
                );
            }
            if ($timeRangeInfo->flagStopDayBefore) {
                $result->sub(new DateInterval('P1D'));
            }
        } else {
            if ($timeRangeInfo->calendar === ConvertDateUtility::DEFAULT_CALENDAR) {
                $result = new DateTime();
                $result->setTimezone(new DateTimeZone($timeRangeInfo->timezone));
                $result->setTime(0, 0, 0);
                $result->setDate($timeRangeInfo->startYear, $timeRangeInfo->startMonth, $timeRangeInfo->startDay);
            } else {

                $dateString = $timeRangeInfo->startYear . '/' . $timeRangeInfo->startMonth . '/' . $timeRangeInfo->startDay . ' ' .
                    '00:00:00';
                $result = ConvertDateUtility::convertFromCalendarToDateTime(
                    $timeRangeInfo->locale,
                    $timeRangeInfo->calendar,
                    $dateString,
                    $timeRangeInfo->timezone
                );

            }

        }
        return $result;
    }

    /**
     * @param array $processorConfiguration
     * @param ContentObjectRenderer $cObj
     * @return stdClass
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function getTimeRangeInformations(array $processorConfiguration, ContentObjectRenderer $cObj): stdClass
    {
        $holidayInfo = new stdClass();
        $context = GeneralUtility::makeInstance(Context::class);
        $systemDate = $context->getPropertyFromAspect('date', 'full');
        if (array_key_exists(self::ATTR_START_DOT, $processorConfiguration)) {
            $holidayInfo->startYear = (int)$cObj->stdWrapValue(self::ATTR_YEAR,
                $processorConfiguration[self::ATTR_START_DOT],
                $systemDate->format('Y')
            );
            $holidayInfo->startMonth = (int)$cObj->stdWrapValue(self::ATTR_MONTH,
                $processorConfiguration[self::ATTR_START_DOT],
                self::DEFAULT_START_STOP_MONTH
            );
            $holidayInfo->startDay = (int)$cObj->stdWrapValue(self::ATTR_DAY,
                $processorConfiguration[self::ATTR_START_DOT],
                self::DEFAULT_START_STOP_DAY
            );
        } else {
            $holidayInfo->startYear = $systemDate->format('Y');
            $holidayInfo->startMonth = self::DEFAULT_START_STOP_MONTH;
            $holidayInfo->startDay = self::DEFAULT_START_STOP_DAY;
        }

        if (array_key_exists(self::ATTR_STOP_DOT, $processorConfiguration)) {
            $holidayInfo->stopYear = (int)$cObj->stdWrapValue(self::ATTR_YEAR,
                $processorConfiguration[self::ATTR_STOP_DOT],
                $systemDate->format('Y') + 2
            );
            $holidayInfo->stopMonth = (int)$cObj->stdWrapValue(self::ATTR_MONTH,
                $processorConfiguration[self::ATTR_STOP_DOT],
                self::DEFAULT_START_STOP_MONTH
            );
            $holidayInfo->stopDay = (int)$cObj->stdWrapValue(self::ATTR_DAY,
                $processorConfiguration[self::ATTR_STOP_DOT],
                self::DEFAULT_START_STOP_DAY
            );
            $holidayInfo->flagStopDayBefore = (bool)$cObj->stdWrapValue(self::ATTR_DAY_BEFORE,
                $processorConfiguration[self::ATTR_STOP_DOT],
                self::DEFAULT_START_STOP_FLAG
            );
        } else {
            $holidayInfo->stopYear = $systemDate->format('Y') + 2;
            $holidayInfo->stopMonth = self::DEFAULT_START_STOP_MONTH;
            $holidayInfo->stopDay = self::DEFAULT_START_STOP_DAY;
            $holidayInfo->flagStopDayBefore = self::DEFAULT_START_STOP_FLAG;
        }

        $holidayInfo->calendar = (string)$cObj->stdWrapValue(self::ATTR_CALENDAR,
            $processorConfiguration,
            ConvertDateUtility::DEFAULT_CALENDAR
        );
        if (in_array($holidayInfo->calendar, ConvertDateUtility::DEFAULT_LIST_DEFECTIVE_CALENDAR, true)) {
            throw new TimerException(
                'The IntlDateFormatter has a bug, which micalulate the gregorian date starting with ' .
                'a chinese date. I hope the bug will be fixed in a former PHP-version. Please support the bug-report ' .
                '(https://github.com/php/php-src/issues/10484).',
                1676794852
            );
        }

        $holidayInfo->timezone = (string)$cObj->stdWrapValue(self::ATTR_TIMEZONE,
            $processorConfiguration,
            $context->getPropertyFromAspect('date', 'timezone')
        );

        $defaultLocale = (explode('.', $this->getSystemLocale(), 2))[0];
        $holidayInfo->locale = (string)$cObj->stdWrapValue(self::ATTR_LOCALE,
            $processorConfiguration,
            $defaultLocale
        );
        $testLocale = $holidayInfo->locale;
        if (strpos($testLocale, '.') !== false) {
            $testLocale = substr(
                $testLocale,
                0,
                strpos($testLocale, '.')
            );
        }

        // validate the entries or throw an exception
        ConvertDateUtility::allowedLocaleCalendarTimezone(
            $testLocale,
            $holidayInfo->calendar,
            $holidayInfo->timezone
        );
        return $holidayInfo;
    }

    /**
     * @return string
     */
    protected function getSystemLocale()
    {
        if (empty($GLOBALS['TYPO3_CONF_VAR']['SYS']['systemLocale'])) {
            return self::LOCALE_EN_GB_UTF;
        }
        return (string)$GLOBALS['TYPO3_CONF_VAR']['SYS']['systemLocale'];
    }

    /**
     * @param array $contentObjectConfiguration
     * @param string $fieldName
     * @param string $flexFormFieldName
     * @return string
     */
    protected function getPathForCalendarFromFlexform(
        array $processedData,
        string $fieldName = 'pi_flexform',
        string $flexFormFieldName = 'aliasPath'

    ): string {
        $flexFormString = $processedData[$fieldName];
        if (empty($flexFormString)) {
            throw new TimerException(
                'There must be a string in the field `' . $fieldName . '`. That field is empty. ' .
                'The declared may perhaps be wrong in spelling or typecase. Please check your typoscript. ' .
                'If everything seems to be okay, then make a screenshot and inform the webmaster.',
                1676702635
            );
        }

        $flexFormArrayRaw = GeneralUtility::xml2array($flexFormString);
        $flexFormArray = TcaUtility::flexformArrayFlatten($flexFormArrayRaw, TimerConst::DEFAULT_FLATTEN_KEYS_LIST);
        if (!array_key_exists($flexFormFieldName, $flexFormArray)) {
            throw new TimerException(
                'There must be a string in the field `' . $fieldName . '`. That field is empty. ' .
                'The declared may perhaps be wrong in spelling or typecase. Please check your typoscript. ' .
                'If everything seems to be okay, then make a screenshot and inform the webmaster.',
                1676702635
            );
        }
        return trim($flexFormArray[$flexFormFieldName]);
    }

    /**
     * @param array $processorConfiguration
     * @param ContentObjectRenderer $cObj
     * @param array $contentObjectConfiguration
     * @return bool|int|string|null
     * @throws TimerException
     */
    protected function getAliasForCalendarYaml(
        array $processorConfiguration,
        ContentObjectRenderer $cObj,
        array $processedData
    ) {
        if (array_key_exists(self::ATTR_ALIAS_CONFIG, $processorConfiguration)) {
            if (array_key_exists(self::ATTR_FLEX_DB_FIELD, $processorConfiguration)) {
                $flagError = false;
                $fieldWithFlexform = $cObj->stdWrapValue(self::ATTR_FLEX_DB_FIELD,
                    $processorConfiguration[self::ATTR_ALIAS_CONFIG], false);
            } else {
                $flagError = true;
            }
            $flagPath = true;
            if (array_key_exists(self::ATTR_PATH_FLEX_FIELD, $processorConfiguration)) {
                $pathInFlexformField = $cObj->stdWrapValue(self::ATTR_PATH_FLEX_FIELD,
                    $processorConfiguration[self::ATTR_ALIAS_CONFIG], false);
                $flagPath = false;
            }
            if (($flagError) &&
                (array_key_exists(self::ATTR_PATH_FAL_FIELD, $processorConfiguration))
            ) {
                $pathInFlexformField = $cObj->stdWrapValue(self::ATTR_PATH_FAL_FIELD,
                    $processorConfiguration[self::ATTR_ALIAS_CONFIG], false);
                $flagPath = false;
            }
            if (($flagError) || ($flagPath)) {
                throw new TimerException(
                    'There must have at least the parameter `' . self::ATTR_ALIAS_PATH .
                    '` or the parameter `' . self::ATTR_ALIAS_CONFIG . '` in the typoscript. ' .
                    'the last parameter will include the parameter `' . self::ATTR_FLEX_DB_FIELD .
                    '` and `' . self::ATTR_PATH_FLEX_FIELD . '`. Please check your typoscript. ' .
                    'If everything seems to be okay, then make a screenshot and inform the webmaster.',
                    1676702644
                );
            }
            $aliasPath = $this->getPathForCalendarFromFlexform(
                $processedData,
                $fieldWithFlexform,
                $pathInFlexformField
            );
        } else {
            if (array_key_exists(self::ATTR_ALIAS_PATH, $processorConfiguration)) {
                $aliasPath = $cObj->stdWrapValue(self::ATTR_ALIAS_PATH, $processorConfiguration, false);
            } else {
                $aliasPath = '';
            }

        }
        return $aliasPath;
    }

    /**
     * @param array $processorConfiguration
     * @param ContentObjectRenderer $cObj
     * @param array $processedData
     * @return bool|int|string|null
     * @throws TimerException
     */
    protected function getHolidayForCalendarYaml(
        array $processorConfiguration,
        ContentObjectRenderer $cObj,
        array $processedData
    ) {

        if (array_key_exists(self::ATTR_HOLIDAY_CONFIG, $processorConfiguration)) {
            $flagError = true;
            if (array_key_exists(self::ATTR_FLEX_DB_FIELD, $processorConfiguration)) {

                $fieldWithFlexform = $cObj->stdWrapValue(self::ATTR_FLEX_DB_FIELD,
                    $processorConfiguration[self::ATTR_HOLIDAY_CONFIG], false);
            } else {
                $flagError = false;
            }
            if (($flagError) &&
                (array_key_exists(self::ATTR_PATH_FLEX_FIELD, $processorConfiguration))
            ) {
                $pathInFlexformField = $cObj->stdWrapValue(self::ATTR_PATH_FLEX_FIELD,
                    $processorConfiguration[self::ATTR_HOLIDAY_CONFIG], false);
            } else {
                throw new TimerException(
                    'There must have at least the parameter `' . self::ATTR_HOLIDAY_PATH .
                    '` or the parameter `' . self::ATTR_HOLIDAY_CONFIG . '` in the typoscript. ' .
                    'the last parameter will include the parameter `' . self::ATTR_FLEX_DB_FIELD .
                    '` and `' . self::ATTR_PATH_FLEX_FIELD . '`. Please check your typoscript. ' .
                    'If everything seems to be okay, then make a screenshot and inform the webmaster.',
                    1676702658
                );
            }
            $holidayYamlListPath = $this->getPathForCalendarFromFlexform(
                $processedData,
                $fieldWithFlexform,
                $pathInFlexformField
            );
        } else {
            if (array_key_exists(self::ATTR_HOLIDAY_PATH, $processorConfiguration)) {
                $holidayYamlListPath = $cObj->stdWrapValue(self::ATTR_HOLIDAY_PATH, $processorConfiguration, false);
            } else {
                throw new TimerException(
                    'There must have at least a path to a file with the list of holidays defined. ' .
                    'Nothing is found. Please check your typoscript. perhaps there is a misspelling in the parameter ' .
                    '`' . self::ATTR_HOLIDAY_PATH . '`or in the parameter `' . self::ATTR_HOLIDAY_CONFIG .
                    '` including `' . self::ATTR_FLEX_DB_FIELD . '` and `' . self::ATTR_PATH_FLEX_FIELD . '`. ' .
                    'If everything seems to be okay, then make a screenshot and inform the webmaster.',
                    1676702658
                );

            }

        }
        return $holidayYamlListPath;
    }
}