<?php

declare(strict_types=1);

namespace Porthd\Timer\CustomTimer;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2023 Dr. Dieter Porthd <info@mobger.de>
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
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Services\HolidaycalendarService;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *
 */
class HolidayTimer implements TimerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    use GeneralTimerTrait;

    public const TIMER_NAME = 'txTimerHoliday';
    public const YAML_HOLIDAY_TIMER = 'holidayTimerList';
    public const YAML_HOLIDAY_IDENTIFIER = 'identifier';

    public const ARG_REL_MIN_TO_SELECTED_TIMER_EVENT = 'relMinToSelectedTimerEvent';
    public const ARG_DURATION_MINUTES = 'durationMinutes';
    public const ARG_CSV_FILE_HOLIDAY_FILE_PATH = 'timerHolidaysFilePath';

    public const ARG_CSV_FILE_HOLIDAY_FAL_INFO = 'timerHolidaysFalRelation';

    protected const LOCALE_EN_GB_UTF = 'en_GB.utf-8';


    protected const ARG_REL_MIN_TO_EVENT = 'relMinToSelectedTimerEvent';
    protected const ARG_REQ_REL_TO_MIN = -37439;
    protected const ARG_REQ_REL_TO_MAX = 37439;
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_REQ_DURMIN_MIN = -37439;
    protected const ARG_REQ_DURMIN_FORBIDDEN = 0;
    protected const ARG_REQ_DURMIN_MAX = 37439;


    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    protected const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/HolidayTimer.flexform',
    ];

    protected const ARG_OPT_LIST = [
        self::ARG_CSV_FILE_HOLIDAY_FILE_PATH,
        self::ARG_CSV_FILE_HOLIDAY_FAL_INFO,

    ];
    protected const ARG_REQ_LIST = [
        self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT,
        self::ARG_DURATION_MINUTES,

        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,
    ];
    protected const YAML_MAIN_KEY_HOLIDAYCALENDAR = 'holidaycalendar';

    /**
     * @var HolidaycalendarService
     */
    protected $holidaycalendarService;

    /**
     * @var YamlFileLoader
     */
    protected $yamlFileLoader;

    /**
     * @var TimerStartStopRange|null
     */
    protected $lastIsActiveResult;

    /**
     * @var int|null
     */
    protected $lastIsActiveTimestamp;

    /**
     * @var array<mixed>
     */
    protected $lastIsActiveParams = [];


    public function __construct(?HolidaycalendarService $holidaycalendarService = null, ?YamlFileLoader $yamlFileLoader = null)
    {
        if ($yamlFileLoader === null) {
            $this->yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        } else {
            $this->yamlFileLoader = $yamlFileLoader;
        }
        if ($holidaycalendarService === null) {
            $this->holidaycalendarService = GeneralUtility::makeInstance(HolidaycalendarService::class);
        } else {
            $this->holidaycalendarService = $holidaycalendarService;
        }
    }

    /**
     * tested 20230923
     *
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }


    /**
     * tested 20230923
     *
     * @return array<mixed>
     */
    public static function getSelectorItem(): array
    {
        return [
            TimerConst::TCA_ITEMS_LABEL => 'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerHoliday.select.name',
            TimerConst::TCA_ITEMS_VALUE => self::TIMER_NAME,
        ];
    }

    /**
     * tested 20230923
     *
     * @param string $activeZoneName
     * @param array<mixed> $params
     * @return string
     */
    public function getTimeZoneOfEvent($activeZoneName, array $params = []): string
    {
        return GeneralTimerUtility::getTimeZoneOfEvent($activeZoneName, $params);
    }

    /**
     * tested 20230923
     *
     * @return array<mixed>
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
    }

    /**
     * tested 20230923
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return bool
     */
    public function isAllowedInRange(DateTime $dateLikeEventZone, $params = []): bool
    {
        // use of the trait-function
        return $this->generalIsAllowedInRange($dateLikeEventZone, $params);
    }

    /**
     * tested general 20230923
     * tested special
     *
     * The method test, if the parameter are valid or not
     * remark: This method must not be tested, if the sub-methods are valid.
     * @param array<mixed> $params
     * @return bool
     */
    public function validate(array $params = []): bool
    {
        $flag = $this->validateZone($params);
        $flag = $flag && $this->validateFlagZone($params);
        $flag = $flag && $this->validateUltimate($params);
        $countRequired = $this->validateCountArguments($params);
        $flag = $flag && ($countRequired === count(self::ARG_REQ_LIST));
        $flag = $flag && $this->validateDurationMinutes($params);
        $flag = $flag && $this->validateRelMinToEvent($params);
        $flag = $flag && $this->validateFilePath(self::ARG_CSV_FILE_HOLIDAY_FILE_PATH, $params);
        $flag = $flag && $this->validateFileFalIdIfExist(self::ARG_CSV_FILE_HOLIDAY_FAL_INFO, $params);
        $countOptions = $this->validateOptional($params);
        // one of the two optionals are needed
        return $flag && ($countOptions >= 1) &&
            ($countOptions <= count(self::ARG_OPT_LIST));
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return int
     */
    protected function validateCountArguments(array $params = []): int
    {
        return $this->countParamsInList(self::ARG_REQ_LIST, $params);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return int
     */
    protected function validateOptional(array $params = []): int
    {
        return $this->countParamsInList(self::ARG_OPT_LIST, $params);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateRelMinToEvent(array $params = []): bool
    {
        $value = (
        isset($params[self::ARG_REL_MIN_TO_EVENT]) ?
            $params[self::ARG_REL_MIN_TO_EVENT] :
            0
        );
        $number = (int)$value;
        return is_int($number) && (($number - $value) === 0) &&
            ($number >= self::ARG_REQ_REL_TO_MIN) && ($number <= self::ARG_REQ_REL_TO_MAX);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateDurationMinutes(array $params = []): bool
    {
        if (!array_key_exists(self::ARG_REQ_DURATION_MINUTES, $params)) {
            return false;
        }
        $number = (int)($params[self::ARG_REQ_DURATION_MINUTES] ?: 0); // what will happen with float
        $floatNumber = (float)($params[self::ARG_REQ_DURATION_MINUTES] ?: 0);
        $flagCheck = ($number - $floatNumber == 0);
        if (is_string($params[self::ARG_REQ_DURATION_MINUTES])) {
            $flagCheck = (bool)preg_match('/^\d+$/', $params[self::ARG_REQ_DURATION_MINUTES]);
        }
        return (
            ($flagCheck) &&
            ($number >= self::ARG_REQ_DURMIN_MIN) &&
            ($number !== self::ARG_REQ_DURMIN_FORBIDDEN) &&
            ($number <= self::ARG_REQ_DURMIN_MAX)
        );
    }


    /**
     * tested 20231001
     *
     * check, if the timer ist for this time active
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return bool
     */
    public function isActive(DateTime $dateLikeEventZone, $params = []): bool
    {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);
        $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            return $result->getResultExist();
        }

        // $listOfSeparatedDates contains a list of holidays (or other repating events)
        $holidayArrayFile = $this->readHolidyaListFromFileOrUrl($params);
        $locale = $this->getSystemLocale();
        // check, if one holiday-definition is part of the active range
        $startDate = clone $dateLikeEventZone;

        // recalculate active range to date of holiday based on the current date
        $durationMin = (int)($params[self::ARG_REQ_DURATION_MINUTES] ?? 0);
        if ($durationMin == 0) {

            return $result->getResultExist();
        }
        $relMin = (int)($params[self::ARG_REL_MIN_TO_EVENT] ?? 0);
        if ($relMin > 0) {
            $startDate->sub(new DateInterval('PT' . abs($relMin) . 'M'));
        } elseif ($relMin < 0) {
            $startDate->add(new DateInterval('PT' . abs($relMin) . 'M'));
        }
        if ($durationMin > 0) {
            $stopDate = clone $startDate;
            $startDate->sub(new DateInterval('PT' . abs($durationMin) . 'M'));
        } else {
            $stopDate = clone $startDate;
            $stopDate->add(new DateInterval('PT' . abs($durationMin) . 'M'));
        }

        // check, if there is one definition of holiday, which works
        $flag = false;

        foreach ($holidayArrayFile as $holiday) {
            // one specific calendar in the PHP-extension intl-date-formatter don't work properly with the
            // recalculation into the gregorian calendar, so its use is forbidden
            if (!$this->holidaycalendarService->forbiddenCalendar($holiday)) {
                // midnight start of holiday is $timeRange->getBeginning()
                // for not every-year holidays, that must be checked with $timerRange->getResultExist()
                $timerRange = $this->holidaycalendarService->currentHoliday(
                    $locale,
                    $startDate,
                    $holiday
                );
                if (($timerRange->getResultExist()) &&
                    ($startDate <= $timerRange->getBeginning()) &&
                    ($stopDate >= $timerRange->getBeginning())
                ) {
                    $result = $timerRange;
                    // calculate last Range
                    $flag = true;
                    break;
                }

                if (($startDate->format('Y') !== $stopDate->format('Y')) ||
                    // maybe there is a yearchange in the non-gregorian calendar, then the year of the result should not be the same
                    ($startDate->format('Y') !== $timerRange->getBeginning()->format('Y'))
                ) {
                    $timerRange = $this->holidaycalendarService->currentHoliday(
                        $locale,
                        $stopDate,
                        $holiday
                    );
                    if (($timerRange->getResultExist()) &&
                        ($startDate <= $timerRange->getBeginning()) &&
                        ($stopDate >= $timerRange->getBeginning())
                    ) {
                        $result = $timerRange;
                        // calculate last Range
                        $flag = true;
                        break;
                    } //
                }
            }
        }
        $result = $this->validateUltimateRangeForNextRange($result, $params, $dateLikeEventZone);
        $this->setIsActiveResult(
            $result->getBeginning(),
            $result->getEnding(),
            ($flag && $result->getResultExist()),
            $dateLikeEventZone,
            $params
        );
        return $flag;
    }

    /**
     * tested
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult(DateTime $dateLikeEventZone, array $params = []): TimerStartStopRange
    {
        return $this->getLastIsActiveResult($dateLikeEventZone, $params);
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested 20231001
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);
        $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            return $result;
        }

        // $listOfSeparatedDates contains a list of holidays (or other repating events)
        $holidayArrayFile = $this->readHolidyaListFromFileOrUrl($params);
        $locale = $this->getSystemLocale();
        // check, if one holiday-definition is part of the active range
        $startDate = clone $dateLikeEventZone;

        // recalculate active range to date of holiday based on the current date
        $durationMin = (int)($params[self::ARG_REQ_DURATION_MINUTES] ?? 0);
        if ($durationMin == 0) {

            return $result;
        }
        $relMin = (int)($params[self::ARG_REL_MIN_TO_EVENT] ?? 0);
        if ($relMin > 0) {
            $startDate->sub(new DateInterval('PT' . abs($relMin) . 'M'));
        } elseif ($relMin < 0) {
            $startDate->add(new DateInterval('PT' . abs($relMin) . 'M'));
        }
        if ($durationMin > 0) {
            $stopDate = clone $startDate;
            $startDate->sub(new DateInterval('PT' . abs($durationMin) . 'M'));
        } else {
            $stopDate = clone $startDate;
            $stopDate->add(new DateInterval('PT' . abs($durationMin) . 'M'));
        }

        // check, if there is one definition of holiday, which works
        $flag = false;

        foreach ($holidayArrayFile as $holiday) {
            // recalculation into the gregorian calendar, so its use is forbidden
            // one specific calendar in the PHP-extension intl-date-formatter don't work properly with the
            // recalculation into the gregorian calendar, so its use is forbidden
            if (!$this->holidaycalendarService->forbiddenCalendar($holiday)) {
                // midnight start of holiday is $timeRange->getBeginning()
                // for not every-year holidays, that must be checked with $timerRange->getResultExist()
                // timerrange contains the raw holiday, the result must be recalculated at the end
                $timerRange = $this->holidaycalendarService->nextHoliday(
                    $locale,
                    $stopDate,
                    $holiday
                );
                if (($flag === false) &&
                    ($timerRange->getResultExist())
                ) {
                    $result = clone $timerRange;
                    $flag = true;
                } else {
                    if (($timerRange->getResultExist()) &&
                        ($timerRange->getBeginning() < $result->getBeginning()) &&
                        ($startDate < $timerRange->getBeginning())
                    ) {
                        // nearer to startdate
                        $result = clone $timerRange;
                    }
                }
            }
        }
        // recalculate the holiday to the estimated active Range
        if (($flag) &&
            ($result->getResultExist())
        ) {
            $refStartDate = $result->getBeginning();
            if ($relMin > 0) {
                $refStartDate->add(new DateInterval('PT' . abs($relMin) . 'M'));
            } elseif ($relMin < 0) {
                $refStartDate->sub(new DateInterval('PT' . abs($relMin) . 'M'));
            }
            if ($durationMin < 0) {
                $refStopDate = clone $refStartDate;
                $refStartDate->sub(new DateInterval('PT' . abs($durationMin) . 'M'));
            } else {
                $refStopDate = clone $refStartDate;
                $refStopDate->add(new DateInterval('PT' . abs($durationMin) . 'M'));
            }
            $result->setBeginning($refStartDate);
            $result->setEnding($refStopDate);
        }
        return $this->validateUltimateRangeForNextRange($result, $params, $dateLikeEventZone);
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested 20231001
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);
        $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            return $result;
        }

        // $listOfSeparatedDates contains a list of holidays (or other repating events)
        $holidayArrayFile = $this->readHolidyaListFromFileOrUrl($params);
        $locale = $this->getSystemLocale();
        // check, if one holiday-definition is part of the active range
        $startDate = clone $dateLikeEventZone;

        // recalculate active range to date of holiday based on the current date
        $durationMin = (int)($params[self::ARG_REQ_DURATION_MINUTES] ?? 0);
        if ($durationMin == 0) {
            return $result;
        }
        $relMin = (int)($params[self::ARG_REL_MIN_TO_EVENT] ?? 0);
        if ($relMin > 0) {
            $startDate->sub(new DateInterval('PT' . abs($relMin) . 'M'));
        } elseif ($relMin < 0) {
            $startDate->add(new DateInterval('PT' . abs($relMin) . 'M'));
        }
        if ($durationMin > 0) {
            $stopDate = clone $startDate;
            $startDate->sub(new DateInterval('PT' . abs($durationMin) . 'M'));
        } else {
            $stopDate = clone $startDate;
            $stopDate->add(new DateInterval('PT' . abs($durationMin) . 'M'));
        }

        // check, if there is one definition of holiday, which works
        $flag = false;
        foreach ($holidayArrayFile as $index => $holiday) {
            // recalculation into the gregorian calendar, so its use is forbidden
            // one specific calendar in the PHP-extension intl-date-formatter don't work properly with the
            // recalculation into the gregorian calendar, so its use is forbidden
            if (!$this->holidaycalendarService->forbiddenCalendar($holiday)) {
                // midnight start of holiday is $timeRange->getBeginning()
                // for not every-year holidays, that must be checked with $timerRange->getResultExist()
                // timerrange contains the raw holiday, the result must be recalculated at the end
                $timerRange = $this->holidaycalendarService->prevHoliday(
                    $locale,
                    $startDate,
                    $holiday
                );
                if (($flag === false) &&
                    ($timerRange->getResultExist())
                ) {
                    $result = clone $timerRange;
                    $flag = true;
                } else {
                    if (($timerRange->getResultExist()) &&
                        ($timerRange->getEnding() > $result->getEnding()) &&
                        ($startDate > $timerRange->getBeginning())
                    ) {
                        // nearer to startdate
                        $result = clone $timerRange;
                    }
                }
            }
        }
        // recalculate the holiday to the estimated active Range
        if (($flag) &&
            ($result->getResultExist())
        ) {
            $refStartDate = $result->getBeginning();
            if ($relMin > 0) {
                $refStartDate->add(new DateInterval('PT' . abs($relMin) . 'M'));
            } elseif ($relMin < 0) {
                $refStartDate->sub(new DateInterval('PT' . abs($relMin) . 'M'));
            }
            if ($durationMin < 0) {
                $refStopDate = clone $refStartDate;
                $refStartDate->sub(new DateInterval('PT' . abs($durationMin) . 'M'));
            } else {
                $refStopDate = clone $refStartDate;
                $refStopDate->add(new DateInterval('PT' . abs($durationMin) . 'M'));
            }
            $result->setBeginning($refStartDate);
            $result->setEnding($refStopDate);
        }

        return $this->validateUltimateRangeForPrevRange($result, $params, $dateLikeEventZone);
    }

    /**
     * @param array<mixed> $params
     * @return array<mixed>
     */
    protected function readHolidyaListFromFileOrUrl(array $params): array
    {
        if ((!array_key_exists(self::ARG_CSV_FILE_HOLIDAY_FILE_PATH, $params)) &&
            ($params[self::ARG_CSV_FILE_HOLIDAY_FAL_INFO] < 1)
        ) {
            return [];
        }
        // no check of the yaml-file with the method `validateYamlOrException`
        // the extension of the file defines, if it is a csv-file or a yaml-file
        $fileResult = [];
        if (!empty($params[self::ARG_CSV_FILE_HOLIDAY_FILE_PATH])) {
            $rootPath = Environment::getPublicPath();
            $fullPath = $rootPath . $params[self::ARG_CSV_FILE_HOLIDAY_FILE_PATH];
            $fileResult = CustomTimerUtility::readListFromFileOrUrl(
                $fullPath,
                $this->yamlFileLoader,
                null,
                $this->logger
            );
        }
        if (array_key_exists(self::YAML_HOLIDAY_TIMER, $fileResult)) {
            // normalize the array from the yaml-file to the array from the csv-file
            $fileResult = $fileResult[self::YAML_HOLIDAY_TIMER];
        } // else $fileResult without yaml-help-layer
        $falRawResult = CustomTimerUtility::readListsFromFalFiles(
            $params[self::ARG_CSV_FILE_HOLIDAY_FAL_INFO],
            ($params[TimerConst::TIMER_RELATION_TABLE] ?? ''),
            ($params[TimerConst::TIMER_RELATION_UID] ?? 0),
            $this->yamlFileLoader,
            $this->logger
        );
        $resultList = [];
        // normalize about the help-layer in the yaml-file
        foreach ($falRawResult as $entry) {
            if (array_key_exists(self::YAML_HOLIDAY_TIMER, $entry)) {
                $resultList[] = $entry[self::YAML_HOLIDAY_TIMER];
            } else {
                $resultList[] = $entry;
            }
        }
        // remove lines without information in field 'identifier'
        $finalList = array_merge($fileResult, ...$resultList);
        $emptyList = [];
        foreach ($finalList as $key => $item) {
            if ((!array_key_exists(self::YAML_HOLIDAY_IDENTIFIER, $item)) ||
                (empty($item[self::YAML_HOLIDAY_IDENTIFIER]))
            ) {
                $emptyList[] = $key;
            }
        }
        foreach ($emptyList as $key => $item) {
            if (empty($item)) {
                unset($finalList[$key]);
            }
        }
        return $finalList;
    }


    /**
     * @param DateTime $dateStart
     * @param DateTime $dateStop
     * @param bool $flag
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return void
     */
    protected function setIsActiveResult(
        DateTime $dateStart,
        DateTime $dateStop,
        bool     $flag,
        DateTime $dateLikeEventZone,
        array    $params = []
    ): void
    {
        if (empty($this->lastIsActiveResult)) {
            $this->lastIsActiveResult = new TimerStartStopRange();
        }
        $this->lastIsActiveResult->setBeginning($dateStart);
        $this->lastIsActiveResult->setEnding($dateStop);
        $this->lastIsActiveResult->setResultExist($flag && (($dateStart <= $dateLikeEventZone) && ($dateLikeEventZone <= $dateStop)));
        $this->lastIsActiveTimestamp = $dateLikeEventZone->getTimestamp();
        $this->lastIsActiveParams = $params;
    }

    /**
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    protected function getLastIsActiveResult(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        if (empty($this->lastIsActiveResult)) {
            $this->lastIsActiveResult = new TimerStartStopRange();
            $this->lastIsActiveTimestamp = $dateLikeEventZone->getTimestamp() + 1; // trigger isActive() in the next step
        }

        if ((is_null($this->lastIsActiveTimestamp)) ||
            ($this->lastIsActiveTimestamp !== $dateLikeEventZone->getTimestamp()) ||
            (md5(json_encode($this->lastIsActiveParams)) !== md5(json_encode($params)))
        ) {
            $this->isActive($dateLikeEventZone, $params);
        }
        return clone $this->lastIsActiveResult;
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

}
