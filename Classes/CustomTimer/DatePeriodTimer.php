<?php

declare(strict_types=1);

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
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\DateTimeUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class DatePeriodTimer implements TimerInterface
{
    use GeneralTimerTrait;

    protected const TIMER_NAME = 'txTimerDatePeriod';
    protected const ARG_REQ_START_TIME = 'startDateTime';
    protected const ARG_REQ_OLDSTART_TIME = 'startTimeSeconds';
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_REQ_PERIOD_LENGTH = 'periodLength';
    protected const ARG_REQ_PERIOD_UNIT = 'periodUnit';

    protected const KEY_PREFIX_TIME = 'T';
    protected const KEY_PREFIX_DATE = 'D';

    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,

        self::ARG_REQ_DURATION_MINUTES,
        self::ARG_REQ_PERIOD_LENGTH,
        self::ARG_REQ_PERIOD_UNIT,
    ];
    protected const ARG_OPT_LIST = [
    ];

    protected const ARG_REQ_DURMIN_MIN = -462240;
    protected const ARG_REQ_DURMIN_FORBIDDEN = 0;
    protected const ARG_REQ_DURMIN_MAX = 462240;

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
     * @return array<mixed>
     */
    public static function getSelectorItem(): array
    {
        return [
            TimerConst::TCA_ITEMS_LABEL => 'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerDatePeriod.select.name',
            TimerConst::TCA_ITEMS_VALUE => self::TIMER_NAME,
        ];
    }

    /**
     * tested 20221009
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
     * tested 20201228
     *
     * @return array<mixed>
     */
    public static function getFlexformItem(): array
    {
        return [
            self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/DatePeriodTimer.flexform',
        ];
    }

    /**
     * tested special 20221115
     * tested general 20201228
     *
     * The method test, if the parameter are valid or not
     * remark: This method must not be tested, if the sub-methods are valid.
     *
     * @param array<mixed> $params
     * @return bool
     */
    public function validate(array $params = []): bool
    {
        $flag = $this->validateZone($params);
        $flag = $flag && $this->validateFlagZone($params);
        $flag = $flag && $this->validateUltimate($params);
        $flag = $flag && $this->validateChangeArguments($params);
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
     * @param array<mixed> $params
     * @return int
     */
    public function validateArguments(array $params = []): int
    {
        return $this->countParamsInList(self::ARG_REQ_LIST, $params);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    public function validateChangeArguments(array $params = []): bool
    {
        return array_key_exists(self::ARG_REQ_OLDSTART_TIME, $params) ||
            (array_key_exists(self::ARG_REQ_START_TIME, $params))
            //            ((array_key_exists(self::ARG_REQ_OLDSTART_TIME, $params)) && (!array_key_exists(self::ARG_REQ_START_TIME, $params))) ||
            //            ((!array_key_exists(self::ARG_REQ_OLDSTART_TIME, $params)) && (array_key_exists(self::ARG_REQ_START_TIME, $params)))
        ;
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateStartTime(array $params = []): bool
    {
        if (empty($params[self::ARG_REQ_START_TIME])) {
            if (empty($params[self::ARG_REQ_OLDSTART_TIME])) {
                return false;
            }
            $startTimeString = $params[self::ARG_REQ_OLDSTART_TIME];
            if (MathUtility::canBeInterpretedAsInteger($startTimeString)) {
                $startTimeString = (new \DateTime())->setTimestamp((int)$startTimeString)->format(self::TIMER_FORMAT_DATETIME);
            }
            return
                \DateTime::createFromFormat(
                    self::TIMER_FORMAT_DATETIME,
                    $startTimeString
                )
             !== false
            ;
        }
        $startTimeString = $params[self::ARG_REQ_START_TIME];
        if (MathUtility::canBeInterpretedAsInteger($startTimeString)) {
            $startTimeString = (new \DateTime())->setTimestamp((int)$startTimeString)->format(self::TIMER_FORMAT_DATETIME);
        }
        return \DateTime::createFromFormat(
            self::TIMER_FORMAT_DATETIME,
            $startTimeString
        ) !== false
        ;
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
        return
            $flagCheck &&
            ($number >= self::ARG_REQ_DURMIN_MIN) &&
            ($number !== self::ARG_REQ_DURMIN_FORBIDDEN) &&
            ($number <= self::ARG_REQ_DURMIN_MAX)
        ;
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validatePeriodLength(array $params = []): bool
    {
        $number = (int)$params[self::ARG_REQ_PERIOD_LENGTH];
        return is_int($number) && ($number > 0);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validatePeriodUnit(array $params = []): bool
    {
        // Cast to string before strtoupper(): an int/null value (e.g. periodUnit === 2 or an
        // unset/null field) would otherwise raise a PHP 8 TypeError instead of being rejected.
        return in_array(strtoupper((string)($params[self::ARG_REQ_PERIOD_UNIT] ?? '')), ['TM', 'TH', 'DD', 'DW', 'DM', 'DY']);
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
     * tested 20201228
     *
     * @param \DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return bool
     */
    public function isAllowedInRange(\DateTime $dateLikeEventZone, $params = []): bool
    {
        // use of the trait-function
        return $this->generalIsAllowedInRange($dateLikeEventZone, $params);
    }

    /**
     * tested 20220910
     *
     * check, if the timer is for this time active
     *
     * @param \DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return bool
     */
    public function isActive(\DateTime $dateLikeEventZone, $params = []): bool
    {
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            $result = new TimerStartStopRange();
            $result->failAllActive($dateLikeEventZone);
            $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
            return $result->getResultExist();
        }

        $delayMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];

        $timeString = $this->resolveStartTimeString($params);
        // if you use this, the UTC-timestamp will be one hour less relativ to the time below, if I had interpreted the results correctly???
        // The calculation of the summertime in de DateTime-Object is mysteriously to me! I don`t get it.
        //        $startTime = DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME,
        //            $timeString,
        //            $dateLikeEventZone->getTimezone()
        //        );

        $startTime = \DateTime::createFromFormat(
            self::TIMER_FORMAT_DATETIME,
            $timeString,
            $dateLikeEventZone->getTimezone()
        );
        if ($startTime === false) {
            $result = new TimerStartStopRange();
            $result->failAllActive($dateLikeEventZone);
            $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
            return $result->getResultExist();
        }

        if ($delayMin >= 0) {
            $startLimit = clone $startTime;
            $stopLimit = $this->shiftedByWallClockMinutes($startTime, $delayMin, false);
            $flag = $this->detectPeriodForBorder($startLimit, $stopLimit, $params, $dateLikeEventZone);
        } else {
            $startLimit = $this->shiftedByWallClockMinutes($startTime, $delayMin, true);
            $stopLimit = clone $startTime;
            $flag = $this->detectPeriodForBorder($startLimit, $stopLimit, $params, $dateLikeEventZone);
        }

        return $flag;
    }

    /**
     * tested 20201230
     *
     * @param \DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function nextActive(\DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        [$delayMin, $startTime, $unitValue, $unitPrefix, $unit] = $this->getParameterFromFlexParams(
            $params,
            $dateLikeEventZone->getTimezone()
        );
        if ($startTime === false) {
            return $this->failedRange($dateLikeEventZone);
        }
        if ($unitValue > 0) {
            return $this->nextFittingPeriodRange(
                $startTime,
                $unitPrefix,
                $unitValue,
                $unit,
                $delayMin,
                $dateLikeEventZone
            );
        }
        // event happens only once
        $result = $this->nextFittingPeriodRange(
            $startTime,
            $unitPrefix,
            $unitValue,
            $unit,
            $delayMin,
            $dateLikeEventZone
        );
        if ($result->getBeginning() < $dateLikeEventZone) {
            $result->setResultExist(false);
        }

        return $this->validateUltimateRangeForNextRange($result, $params, $dateLikeEventZone);
    }

    /**
     * @param \DateTime $startTime
     * @param string $unitPrefix
     * @param int $unitValue
     * @param string $unit
     * @param int $delayMin
     * @param \DateTime $dateLikeEventZone
     * @return TimerStartStopRange
     * @throws \Exception
     */
    protected function nextFittingPeriodRange(
        $startTime,
        $unitPrefix,
        int $unitValue,
        $unit,
        $delayMin,
        \DateTime $dateLikeEventZone
    ): TimerStartStopRange {
        $flag = false;
        $timeUnitCode = (string)(($unitPrefix === self::KEY_PREFIX_TIME) ? self::KEY_PREFIX_TIME : self::KEY_PREFIX_DATE) . $unit;
        if ($unitValue > 0) {
            $periodsBelow = DateTimeUtility::diffPeriod(
                $startTime,
                $dateLikeEventZone,
                $unitValue,
                $timeUnitCode
            ) - 2;  // I think, `-1` should although work.
        } else {
            $periodsBelow = 0; // event hapens only once
        }
        if ($periodsBelow > 0) {
            // find arange near actual starttime
            $startTime->add(new \DateInterval('P' . $unitPrefix .
                ($periodsBelow * $unitValue) . $unit));
        } else {
            $startTime->sub(new \DateInterval('P' . $unitPrefix .
                (abs($periodsBelow) * $unitValue) . $unit));
        }
        $flowCout = 0;
        do {
            if ($flag) {
                $startTime->add(new \DateInterval('P' . $unitPrefix .
                    $unitValue . $unit));
            }
            $dateBorder = clone $startTime;
            if ($delayMin >= 0) {
                $startLimit = clone $dateBorder;
                $stopLimit = $this->shiftedByWallClockMinutes($dateBorder, $delayMin, false);
            } else {
                $startLimit = $this->shiftedByWallClockMinutes($dateBorder, $delayMin, true);
                $stopLimit = clone $dateBorder;
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
     * @param \DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function prevActive(\DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        [$delayMin, $startTime, $unitValue, $unitPrefix, $unit] = $this->getParameterFromFlexParams(
            $params,
            $dateLikeEventZone->getTimezone()
        );
        if ($startTime === false) {
            return $this->failedRange($dateLikeEventZone);
        }
        if ($unitValue > 0) {
            return $this->prevFittingPeriodRange(
                $startTime,
                $unitPrefix,
                $unitValue,
                $unit,
                $delayMin,
                $dateLikeEventZone
            );
        }
        // event happens only once
        $result = $this->prevFittingPeriodRange(
            $startTime,
            $unitPrefix,
            $unitValue,
            $unit,
            $delayMin,
            $dateLikeEventZone
        );
        if ($result->getEnding() > $dateLikeEventZone) {
            $result->setResultExist(false);
        }

        return $this->validateUltimateRangeForPrevRange($result, $params, $dateLikeEventZone);
    }

    // PURPOSE: Read the start of the period from the current key or, for old records, from the legacy key.
    //
    // ADVANTAGES:
    //   - One place for the fallback instead of two copies in isActive() and getParameterFromFlexParams().
    //   - Never raises "Undefined array key": a FlexForm saved without any start field yields ''.
    //
    // DISADVANTAGES / TRADE-OFFS:
    //   - '' is not a date. Callers have to treat the failed DateTime conversion as "no range".
    //
    // PRECONDITIONS (data requirements):
    //   - None; both keys are optional here. validateStartTime() is the place that rejects the record.
    //
    // EDGE CASES:
    //   - Both keys missing or empty (tx_timer_domain_model_listing uid 2 and 3 in the local database):
    //     returns '' and the timer is rendered as having no next or previous range.
    //   - '0' in startDateTime counts as empty, exactly as the former empty() check did.
    /**
     * @param array<mixed> $params
     */
    protected function resolveStartTimeString(array $params): string
    {
        return (string)(($params[self::ARG_REQ_START_TIME] ?? '') ?: ($params[self::ARG_REQ_OLDSTART_TIME] ?? ''));
    }

    protected function failedRange(\DateTime $dateLikeEventZone): TimerStartStopRange
    {
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);
        return $result;
    }

    /**
     * @param array<mixed> $params
     * @param \DateTimeZone $timeZone
     * @return array<mixed> the start time is false if the parameters carry no readable start
     */
    protected function getParameterFromFlexParams(array $params, \DateTimeZone $timeZone): array
    {
        $delayMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];

        $timeString = $this->resolveStartTimeString($params);
        if (MathUtility::canBeInterpretedAsInteger($timeString)) {
            $startTime = new \DateTime('@' . $timeString);
            $startTime->setTimezone($timeZone);
        } else {

            $startTime = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $timeString,
                $timeZone
            );
        }
        $unitValue = (int)$params[self::ARG_REQ_PERIOD_LENGTH];
        $unitValue = abs($unitValue);

        $unitRaw = strtoupper($params[self::ARG_REQ_PERIOD_UNIT]);
        $unitPrefix = strtoupper($unitRaw[0]);
        $unitPrefix = (($unitPrefix !== self::KEY_PREFIX_TIME) ? '' : $unitPrefix);
        $unit = (string)$unitRaw[1];
        return [$delayMin, $startTime, $unitValue, $unitPrefix, $unit];
    }

    /**
     * @param \DateTime $startTime
     * @param string $unitPrefix
     * @param int $unitValue
     * @param string $unit
     * @param int $delayMin
     * @param \DateTime $dateLikeEventZone
     * @return TimerStartStopRange
     * @throws \Exception
     */
    protected function prevFittingPeriodRange(
        $startTime,
        $unitPrefix,
        int $unitValue,
        $unit,
        $delayMin,
        \DateTime $dateLikeEventZone
    ): TimerStartStopRange {
        $flag = false;
        $timeUnitCode = (string)(($unitPrefix === self::KEY_PREFIX_TIME) ? self::KEY_PREFIX_TIME : self::KEY_PREFIX_DATE) . $unit;
        if ($unitValue > 0) {
            $periodsAfter = DateTimeUtility::diffPeriod(
                $startTime,
                $dateLikeEventZone,
                $unitValue,
                $timeUnitCode
            ) + 3;  // I think, `-1` should although work.
        } else {
            $periodsAfter = 0; // event hapens only once
        }
        if ($periodsAfter > 0) {
            // find arange near actual starttime
            $startTime->add(new \DateInterval('P' . $unitPrefix .
                ($periodsAfter * $unitValue) . $unit));
        } else {
            $startTime->sub(new \DateInterval('P' . $unitPrefix .
                (abs($periodsAfter) * $unitValue) . $unit));
        }
        $flowCout = 0;
        do {
            if ($flag) {
                $startTime->sub(new \DateInterval('P' . $unitPrefix .
                    $unitValue . $unit));
            }
            $dateBorder = clone $startTime;
            if ($delayMin >= 0) {
                $startLimit = clone $dateBorder;
                $stopLimit = $this->shiftedByWallClockMinutes($dateBorder, $delayMin, false);
            } else {
                $startLimit = $this->shiftedByWallClockMinutes($dateBorder, $delayMin, true);
                $stopLimit = clone $dateBorder;
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

    /**
     * Shift a datetime by a duration given in minutes while preserving the
     * wall-clock time across daylight-saving-time boundaries.
     *
     * PURPOSE: apply the `durationMinutes` offset as nominal wall-clock minutes,
     * so the range ending keeps the same H:i:s as if no DST switch happened.
     *
     * ADVANTAGES:
     *   - consistent with the period stepping (P..W / P..Y), which PHP already
     *     treats DST-neutrally, so beginning and ending stay in sync.
     *
     * DISADVANTAGES / TRADE-OFFS:
     *   - the offset is no longer an exact number of elapsed real minutes when the
     *     span crosses a DST switch (off by the DST offset in real seconds).
     *
     * PRECONDITIONS:
     *   - $date carries a valid timezone; $minutes is a whole number of minutes.
     *
     * EDGE CASES:
     *   - spans crossing CET<->CEST: a plain `DateTime::add('PT..M')` would shift the
     *     wall-clock ending by one hour (e.g. 144000 min over 100 days -> 14:00
     *     instead of 13:00); doing the arithmetic in UTC and re-stamping the original
     *     timezone avoids that.
     *
     * @param \DateTime $date
     * @param int $minutes
     * @param bool $subtract
     * @return \DateTime
     */
    protected function shiftedByWallClockMinutes(\DateTime $date, int $minutes, bool $subtract): \DateTime
    {
        $interval = new \DateInterval('PT' . abs($minutes) . 'M');
        $neutral = new \DateTime($date->format(self::TIMER_FORMAT_DATETIME), new \DateTimeZone('UTC'));
        if ($subtract) {
            $neutral->sub($interval);
        } else {
            $neutral->add($interval);
        }
        return new \DateTime($neutral->format(self::TIMER_FORMAT_DATETIME), $date->getTimezone());
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
     * @param \DateTime $startLimit
     * @param \DateTime $stopLimit
     * @param array<mixed> $params
     * @param \DateTime $dateLikeEventZone
     * @return bool
     */
    protected function detectPeriodForBorder(
        \DateTime $startLimit,
        \DateTime $stopLimit,
        array $params,
        \DateTime $dateLikeEventZone
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
        }

        [$testStart, $testStop] = $this->getRangeWithIncludeProbility(
            $startLimit,
            $stopLimit,
            ((int)($factor * $length)),
            $prefix,
            $unit
        );
        $flag = ($testStart <= $dateLikeEventZone) && ($dateLikeEventZone <= $testStop);
        $this->setIsActiveResult($testStart, $testStop, $flag, $dateLikeEventZone, $params);
        return $this->lastIsActiveResult->getResultExist();
    }

    /**
     * tested:
     *
     * @param \DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult(\DateTime $dateLikeEventZone, array $params = []): TimerStartStopRange
    {
        return $this->getLastIsActiveResult($dateLikeEventZone, $params);
    }

    /**
     * @param \DateTime $startLimit
     * @param \DateTime $stopLimit
     * @param int $pLength
     * @param string $prefix
     * @param string $unit
     * @return \DateTime[]
     * @throws \Exception
     */
    protected function getRangeWithIncludeProbility(
        \DateTime $startLimit,
        \DateTime $stopLimit,
        int $pLength,
        string $prefix,
        string $unit
    ): array {
        $testStart = clone $startLimit;
        $testStop = clone $stopLimit;
        if ($pLength > 0) {
            $testStart->add(new \DateInterval('P' . $prefix . $pLength . $unit));
            $testStop->add(new \DateInterval('P' . $prefix . $pLength . $unit));
        } else {
            $testStart->sub(new \DateInterval('P' . $prefix . abs($pLength) . $unit));
            $testStop->sub(new \DateInterval('P' . $prefix . abs($pLength) . $unit));
        }
        return [$testStart, $testStop];
    }

    /**
     * @param \DateTime $dateStart
     * @param \DateTime $dateStop
     * @param bool $flag
     * @param \DateTime $dateLikeEventZone
     * @param array<mixed> $params
     */
    protected function setIsActiveResult(
        \DateTime $dateStart,
        \DateTime $dateStop,
        bool $flag,
        \DateTime $dateLikeEventZone,
        array $params = []
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
     * @param \DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    protected function getLastIsActiveResult(\DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
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
