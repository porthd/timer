<?php

namespace Porthd\Timer\CustomTimer;

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


use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\DateTimeUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class DatePeriodTimer implements TimerInterface
{
    protected const TIMER_NAME = 'txTimerDatePeriod';
    protected const ARG_REQ_START_TIME = 'startTimeSeconds';
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_REQ_PERIOD_LENGTH = 'periodLength';
    protected const ARG_REQ_PERIOD_UNIT = 'periodUnit';

    protected const KEY_PREFIX_TIME = 'T';
    protected const KEY_PREFIX_DATE = 'D';

    protected const ARG_REQ_LIST = [
        self::ARG_REQ_START_TIME,
        self::ARG_REQ_DURATION_MINUTES,
        self::ARG_REQ_PERIOD_LENGTH,
        self::ARG_REQ_PERIOD_UNIT,
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_EVER_TIME_ZONE_OF_EVENT,
    ];

    /**
     * @var TimerStartStopRange|null
     */
    protected $lastIsActiveResult;

    /**
     * @var int|null
     */
    protected $lastIsActiveTimestamp;

    /**
     * @var array
     */
    protected $lastIsActiveParams = [];

    /**
     * tested 20201228
     *
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }


    /**
     * tested 20201228
     *
     * @return array
     *
     */
    public static function getSelectorItem(): array
    {
        return [
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerDatePeriod.select.name',
            self::TIMER_NAME,
        ];
    }

    /**
     * tested 20221009
     *
     * @param string $activeZoneName
     * @param array $params
     * @return string
     */
    public function getTimeZoneOfEvent($activeZoneName, array $params = []): string
    {
        return GeneralTimerUtility::getTimeZoneOfEvent($activeZoneName, $params);
    }

    /**
     * tested 20201228
     *
     * @return array
     */
    public static function getFlexformItem(): array
    {
        return [
            self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/DatePeriodTimer.flexform',
        ];
    }


    /**
     * tested special 20201228
     * tested general 20201228
     *
     * The method test, if the parameter are valid or not
     * remark: This method must not be tested, if the sub-methods are valid.
     *
     * @param array $params
     * @return bool
     */
    public function validate(array $params = []): bool
    {
        $flag = true;
        $flag = $flag && $this->validateZone($params);
        $flag = $flag && $this->validateUltimate($params);
        $countRequired = $this->validateArguments($params);
        $flag = $flag && ($countRequired === count(self::ARG_REQ_LIST));  // internal Check against change or requirement-definitions
        $countOptions = $this->validateOptional($params);
        $flag = $flag && ($countOptions >= 0) &&
            ($countOptions <= count(self::ARG_OPT_LIST));
        $flag = $flag && $this->validateStartTime($params);
        $flag = $flag && $this->validateDurationMinutes($params);
        $flag = $flag && $this->validatePeriodLength($params);
        return $flag && $this->validatePeriodUnit($params);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateZone(array $params = []): bool
    {
        return !(isset($params[self::ARG_EVER_TIME_ZONE_OF_EVENT])) ||
            TcaUtility::isTimeZoneInList(
                $params[self::ARG_EVER_TIME_ZONE_OF_EVENT]
            );
    }


    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateUltimate(array $params = []): bool
    {
        $flag = (!empty($params[self::ARG_ULTIMATE_RANGE_BEGINN]));
        $flag = $flag && (false !== date_create_from_format(
                    self::TIMER_FORMAT_DATETIME,
                    $params[self::ARG_ULTIMATE_RANGE_BEGINN]
                ));
        $flag = $flag && (!empty($params[self::ARG_ULTIMATE_RANGE_END]));
        return ($flag && (false !== date_create_from_format(
                    self::TIMER_FORMAT_DATETIME,
                    $params[self::ARG_ULTIMATE_RANGE_END]
                )));
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    public function validateArguments(array $params = []): int
    {
        $flag = 0;
        foreach (self::ARG_REQ_LIST as $key) {
            if (isset($params[$key])) {
                $flag++;
            }
        }
        return $flag;
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateStartTime(array $params = []): bool
    {
        return (
            DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME, $params[self::ARG_REQ_START_TIME]) !== false
        );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateDurationMinutes(array $params = []): bool
    {
        $value = (isset($params[self::ARG_REQ_DURATION_MINUTES]) ?
            $params[self::ARG_REQ_DURATION_MINUTES] :
            0
        );
        $number = (int)$value;
        return is_int($number) && ($number !== 0) && (($number - $value) === 0);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validatePeriodLength(array $params = []): bool
    {
        $number = (int)$params[self::ARG_REQ_PERIOD_LENGTH];
        return is_int($number) && ($number > 0);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validatePeriodUnit(array $params = []): bool
    {
        return in_array(strtoupper($params[self::ARG_REQ_PERIOD_UNIT]), ['TM', 'TH', 'DD', 'DW', 'DM', 'DY',]);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateOptional(array $params = []): int
    {
        $count = 0;
        foreach (self::ARG_OPT_LIST as $key) {
            if (isset($params[$key])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * tested 20201228
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return bool
     */
    public function isAllowedInRange(DateTime $dateLikeEventZone, $params = []): bool
    {
        return ($params[self::ARG_ULTIMATE_RANGE_BEGINN] <= $dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME)) &&
            ($dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME) <= $params[self::ARG_ULTIMATE_RANGE_END]);
    }

    /**
     * tested 20220910
     *
     * check, if the timer is for this time active
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas
     * @param array $params
     * @return bool
     */
    public function isActive(DateTime $dateLikeEventZone, $params = []): bool
    {
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            $result = new TimerStartStopRange();
            $result->failAllActive($dateLikeEventZone);
            $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
            return $result;
        }

        $delayMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];

        $timeString = $params[self::ARG_REQ_START_TIME];
        // if you use this, the UTC-timestamp will be one hour less relativ to the time below, if I had interpreted the results correctly???
        // The calculation of the summertime in de DateTime-Object is mysteriously to me! I don`t get it.
        //        $startTime = DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME,
        //            $timeString,
        //            $dateLikeEventZone->getTimezone()
        //        );

        $startTime = DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME,
            $timeString
        );
        $startTime->setTimezone($dateLikeEventZone->getTimezone());

        if ($delayMin >= 0) {
            $stopLimit = clone $startTime;
            $startLimit = clone $stopLimit;
            $stopLimit->add(new DateInterval('PT' . abs($delayMin) . 'M'));
            $flag = $this->detectPeriodForBorder($startLimit, $stopLimit, $params, $dateLikeEventZone);

        } else {
            $startLimit = clone $startTime;
            $stopLimit = clone $startLimit;
            $startLimit->sub(new DateInterval('PT' . abs($delayMin) . 'M'));
            $flag = $this->detectPeriodForBorder($startLimit, $stopLimit, $params, $dateLikeEventZone);
        }

        return $flag;

    }

    /**
     * tested 20201230
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        [$delayMin, $startTime, $unitValue, $unitPrefix, $unit] = $this->getParameterFromFlexParams($params,
            $dateLikeEventZone->getTimezone());
        if ($unitValue > 0) {
            return $this->nextFittingPeriodRange($startTime, $unitPrefix, $unitValue, $unit, $delayMin,
                $dateLikeEventZone);
        }
        // event happens only once
        $result = $this->nextFittingPeriodRange($startTime, $unitPrefix, $unitValue, $unit, $delayMin,
            $dateLikeEventZone);
        if ($result->getBeginning() < $dateLikeEventZone) {
            $result->setResultExist(false);
        }

        if ((!$this->isAllowedInRange($result->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($result->getEnding(), $params))
        ) {
            $result->failOnlyNextActive($dateLikeEventZone);
        }
        return $result;
    }


    /**
     * @param DateTime $startTime
     * @param string $unitPrefix
     * @param int $unitValue
     * @param string $unit
     * @param int $delayMin
     * @param DateTime $dateLikeEventZone
     * @return TimerStartStopRange
     * @throws Exception
     */
    protected function nextFittingPeriodRange(
        $startTime,
        $unitPrefix,
        $unitValue,
        $unit,
        $delayMin,
        DateTime $dateLikeEventZone
    ): TimerStartStopRange {
        $flag = false;
        $timeUnitCode = (($unitPrefix === self::KEY_PREFIX_TIME) ? self::KEY_PREFIX_TIME : self::KEY_PREFIX_DATE) . $unit;
        if ($unitValue > 0) {
            $periodsBelow = DateTimeUtility::diffPeriod($startTime, $dateLikeEventZone, $unitValue,
                    $timeUnitCode) - 2;  // I think, `-1` should although work.
        } else {
            $periodsBelow = 0; // event hapens only once
        }
        if ($periodsBelow > 0) {
            // find arange near actual starttime
            $startTime->add(new DateInterval('P' . $unitPrefix .
                ($periodsBelow * $unitValue) . $unit));
        } else {
            $startTime->sub(new DateInterval('P' . $unitPrefix .
                (abs($periodsBelow) * $unitValue) . $unit));
        }
        $flowCout = 0;
        do {
            if ($flag) {
                $startTime->add(new DateInterval('P' . $unitPrefix .
                    $unitValue . $unit));
            }
            $dateBorder = clone $startTime;
            if ($delayMin >= 0) {
                $startLimit = clone $dateBorder;
                $stopLimit = clone $dateBorder;
                $stopLimit->add(new DateInterval('PT' . abs($delayMin) . 'M'));
            } else {
                $startLimit = clone $dateBorder;
                $stopLimit = clone $dateBorder;
                $startLimit->sub(new DateInterval('PT' . abs($delayMin) . 'M'));
            }
            $flowCout++;
            $flag = true;
        } while (
            ($startLimit <= $dateLikeEventZone) &&
            ($unitValue > 0) &&
            ($flowCout < 5)
        );

        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->setBeginning($startLimit);
        $result->setEnding($stopLimit);
        return $result;
    }

    /**
     * tested 20201230
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        [$delayMin, $startTime, $unitValue, $unitPrefix, $unit] = $this->getParameterFromFlexParams($params,
            $dateLikeEventZone->getTimezone());
        if ($unitValue > 0) {
            return $this->prevFittingPeriodRange($startTime, $unitPrefix, $unitValue, $unit, $delayMin,
                $dateLikeEventZone);
        }
        // event happens only once
        $result = $this->prevFittingPeriodRange($startTime, $unitPrefix, $unitValue, $unit, $delayMin,
            $dateLikeEventZone);
        if ($result->getEnding() > $dateLikeEventZone) {
            $result->setResultExist(false);
        }

        if ((!$this->isAllowedInRange($result->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($result->getEnding(), $params))
        ) {
            $result->failOnlyNextActive($dateLikeEventZone);
        }
        return $result;
    }

    /**
     * @param array $params
     * @param DateTimeZone $timeZone
     * @return array
     */
    protected function getParameterFromFlexParams(array $params, DateTimeZone $timeZone): array
    {
        $delayMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];

        $timeString = $params[self::ARG_REQ_START_TIME];
        $startTime = DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME,
            $timeString,
            $timeZone
        );
        $unitValue = (int)$params[self::ARG_REQ_PERIOD_LENGTH];
        $unitValue = abs($unitValue);

        $unitRaw = strtoupper($params[self::ARG_REQ_PERIOD_UNIT]);
        $unitPrefix = strtoupper($unitRaw[0]);
        $unitPrefix = (($unitPrefix !== self::KEY_PREFIX_TIME) ? '' : $unitPrefix);
        $unit = (string)$unitRaw[1];
        return [$delayMin, $startTime, $unitValue, $unitPrefix, $unit];
    }


    /**
     * @param DateTime $startTime
     * @param string $unitPrefix
     * @param int $unitValue
     * @param string $unit
     * @param int $delayMin
     * @param DateTime $dateLikeEventZone
     * @return TimerStartStopRange
     * @throws Exception
     */
    protected function prevFittingPeriodRange(
        $startTime,
        $unitPrefix,
        $unitValue,
        $unit,
        $delayMin,
        DateTime $dateLikeEventZone
    ): TimerStartStopRange {
        $flag = false;
        $timeUnitCode = (($unitPrefix === self::KEY_PREFIX_TIME) ? self::KEY_PREFIX_TIME : self::KEY_PREFIX_DATE) . $unit;
        if ($unitValue > 0) {
            $periodsAfter = DateTimeUtility::diffPeriod($startTime, $dateLikeEventZone, $unitValue,
                    $timeUnitCode) + 3;  // I think, `-1` should although work.
        } else {
            $periodsAfter = 0; // event hapens only once
        }
        if ($periodsAfter > 0) {
            // find arange near actual starttime
            $startTime->add(new DateInterval('P' . $unitPrefix .
                ($periodsAfter * $unitValue) . $unit));
        } else {
            $startTime->sub(new DateInterval('P' . $unitPrefix .
                (abs($periodsAfter) * $unitValue) . $unit));
        }
        $flowCout = 0;
        do {
            if ($flag) {
                $startTime->sub(new DateInterval('P' . $unitPrefix .
                    $unitValue . $unit));
            }
            $dateBorder = clone $startTime;
            if ($delayMin >= 0) {
                $startLimit = clone $dateBorder;
                $stopLimit = clone $dateBorder;
                $stopLimit->add(new DateInterval('PT' . abs($delayMin) . 'M'));
            } else {
                $startLimit = clone $dateBorder;
                $stopLimit = clone $dateBorder;
                $startLimit->sub(new DateInterval('PT' . abs($delayMin) . 'M'));
            }
            $flowCout++;
            $flag = true;
        } while (
            ($stopLimit >= $dateLikeEventZone) &&
            ($unitValue > 0) &&
            ($flowCout < 8)
        );

        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $result->setBeginning($startLimit);
        $result->setEnding($stopLimit);
        return $result;
    }


//
//    /**
//     * @param DateTime $startTime
//     * @param string $unitPrefix
//     * @param int $unitValue
//     * @param string $unit
//     * @param int $delayMin
//     * @param DateTime $dateLikeEventZone
//     * @return TimerStartStopRange
//     * @throws \Exception
//     */
//    protected function prevFittingPeriodRange($startTime, $unitPrefix, $unitValue, $unit, $delayMin, DateTime $dateLikeEventZone): TimerStartStopRange
//    {
//        $flag = false;
//        $timeUnitCode = (($unitPrefix === self::KEY_PREFIX_TIME) ? self::KEY_PREFIX_TIME : self::KEY_PREFIX_DATE) . $unit;
//        if ($unitValue > 0) {
//            $periodsBelow = DateTimeUtility::diffPeriod($startTime, $dateLikeEventZone, $unitValue, $timeUnitCode) - 2;  // I think, `-1` should although work.
//        } else {
//            $periodsBelow = 0; // event hapens only once
//        }
//        if ($periodsBelow > 0) {
//            // find arange near actual starttime
//            $startTime->add(new \DateInterval('P' . $unitPrefix .
//                ($periodsBelow * $unitValue) . $unit));
//        } else {
//            $startTime->sub(new \DateInterval('P' . $unitPrefix .
//                (abs($periodsBelow) * $unitValue) . $unit));
//        }
//        $flowCout =0;
//        do {
//            if ($flag) {
//                $startTime->add(new \DateInterval('P' . $unitPrefix .
//                    $unitValue . $unit));
//            }
//            $dateBorder = clone $startTime;
//            if ($delayMin >= 0) {
//                $startLimit = clone $dateBorder;
//                $stopLimit = clone $dateBorder;
//                $stopLimit->add(new DateInterval('PT' . abs($delayMin) . 'M'));
//            } else {
//                $startLimit = clone $dateBorder;
//                $stopLimit = clone $dateBorder;
//                $startLimit->sub(new DateInterval('PT' . abs($delayMin) . 'M'));
//            }
//            if (($startLimit >= $dateLikeEventZone) ||
//                ($stopLimit >= $dateLikeEventZone)
//            ) {
//                break;
//            }
//            $oldStartLimit = $startLimit;
//            $oldStopLimit = $stopLimit;
//            $flowCout++;
//            $flag = true;
//        } while (
//            ($unitValue > 0) &&
//            ($flowCout < 5)
//        );
//
//        /** @var TimerStartStopRange $result */
//        $result = new TimerStartStopRange();
//        $result->setBeginning((isset($oldStartLimit)?$oldStartLimit:$startLimit));
//        $result->setEnding((isset($oldStopLimit)?$oldStopLimit: $stopLimit));
//        return $result;
//    }


    /**
     * @param bool $useStartLimitToCalc
     * @param DateTime $startLimit
     * @param DateTime $stopLimit
     * @param array $params
     * @param DateTime $dateLikeEventZone
     * @return bool
     */
    protected function detectPeriodForBorder(
        DateTime $startLimit,
        DateTime $stopLimit,
        array $params,
        DateTime $dateLikeEventZone
    ) {
        $unit = strtoupper($params[self::ARG_REQ_PERIOD_UNIT]);
        $length = (int)$params[self::ARG_REQ_PERIOD_LENGTH];

        switch ($unit) {
            case 'TM' :
                $factor = floor((($dateLikeEventZone->getTimestamp() - $startLimit->getTimestamp()) / 60) / $length);

                $prefix = 'T';
                $unit = 'M';
                break;
            case 'TH' :
                $factor = floor((($dateLikeEventZone->getTimestamp() - $startLimit->getTimestamp()) / 3600) / $length);
                $prefix = 'T';
                $unit = 'H';
                break;
            case 'DD' :
                $factor = floor((($dateLikeEventZone->getTimestamp() - $startLimit->getTimestamp()) / 86400) / $length);
                $prefix = '';
                $unit = 'D';
                break;
            case 'DW' :
                $factor = floor((($dateLikeEventZone->getTimestamp() - $startLimit->getTimestamp()) / 604800) / $length);
                $prefix = '';
                $unit = 'W';
                break;
            case 'DM' :
                $factor = floor((((int)$dateLikeEventZone->format('Y') * 12 + (int)$dateLikeEventZone->format('m')) -
                        ((int)$startLimit->format('Y') * 12 + (int)$startLimit->format('m'))) / $length);
                $prefix = '';
                $unit = 'M';
                break;
            case 'DY' :
                $factor = floor(((int)$dateLikeEventZone->format('Y') - (int)$startLimit->format('Y')) / $length);
                $prefix = '';
                $unit = 'Y';
                break;
            default:
                throw new TimerException(
                    'The periodUnit `' . $unit . '` for the timer txDatePeriod is not defined. ' .
                    'Allowed are only [TM, TH, DD, DW, DM, DY]',
                    1609180114

                );
                break;
        }

        [$testStart, $testStop] = $this->getRangeWithIncludeProbility($startLimit, $stopLimit, ($factor * $length),
            $prefix, $unit);
        $flag = ($testStart <= $dateLikeEventZone) && ($dateLikeEventZone <= $testStop);
        $this->setIsActiveResult($testStart, $testStop, $flag, $dateLikeEventZone, $params);
        return $this->lastIsActiveResult->getResultExist();
    }

    /**
     * tested:
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        return $this->getLastIsActiveResult($dateLikeEventZone, $params);
    }

    /**
     * @param DateTime $startLimit
     * @param DateTime $stopLimit
     * @param string $pLength
     * @param string $prefix
     * @param string $unit
     * @return DateTime[]
     * @throws Exception
     */
    protected function getRangeWithIncludeProbility(
        DateTime $startLimit,
        DateTime $stopLimit,
        $pLength,
        string $prefix,
        string $unit
    ): array {
        $testStart = clone $startLimit;
        $testStop = clone $stopLimit;
        if ($pLength > 0) {

            $testStart->add(new DateInterval('P' . $prefix . $pLength . $unit));
            $testStop->add(new DateInterval('P' . $prefix . $pLength . $unit));
        } else {

            $testStart->sub(new DateInterval('P' . $prefix . abs($pLength) . $unit));
            $testStop->sub(new DateInterval('P' . $prefix . abs($pLength) . $unit));
        }
        return [$testStart, $testStop];
    }


    /**
     * @param $dateStart
     * @param $dateStop
     * @param bool $flag
     * @param DateTime $dateLikeEventZone
     */
    protected function setIsActiveResult(
        $dateStart,
        $dateStop,
        bool $flag,
        DateTime $dateLikeEventZone,
        $params = []
    ): void {
        if (empty($this->lastIsActiveResult)) {
            $this->lastIsActiveResult = new TimerStartStopRange();
        }
        $this->lastIsActiveResult->setBeginning($dateStart);
        $this->lastIsActiveResult->setEnding($dateStop);
        $this->lastIsActiveResult->setResultExist($flag);
        $this->lastIsActiveTimestamp = $dateLikeEventZone->getTimestamp();
        $this->lastIsActiveParams = $params;
    }

    /**
     * @param DateTime $dateLikeEventZone
     * @param array $params
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

}