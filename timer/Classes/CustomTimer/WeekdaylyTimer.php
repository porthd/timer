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
use Porthd\Timer\CustomTimer\GeneralTimerTrait;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\GeneralTimerUtility;

class WeekdaylyTimer implements TimerInterface
{
    use GeneralTimerTrait;

    protected const TIMER_NAME = 'txTimerWeekdayly';
    protected const ARG_REQ_ACTIVE_WEEKDAY = 'activeWeekday';
    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,

        self::ARG_REQ_ACTIVE_WEEKDAY,
    ];
    protected const ARG_OPT_LIST = [];

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
     * tested 20210102
     *
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }

    /**
     * tested 20210102
     *
     * @return array<mixed>
     */
    public static function getSelectorItem(): array
    {
        return [
            TimerConst::TCA_ITEMS_LABEL => 'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerWeekdayly.select.name',
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
     * tested 20210102
     *
     * @return array<mixed>
     */
    public static function getFlexformItem(): array
    {
        return [
            self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/WeekdaylyTimer.flexform',
        ];
    }


    /**
     * tested special 20221115
     * tested general 20210102
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
        $flag = $flag && $this->validateActiveWeekday($params);
        $flag = $flag && $this->validateUltimate($params);
        $countRequired = $this->validateArguments($params);
        $countOptional = $this->validateOptional($params);
        return $flag &&
            ($countRequired === count(self::ARG_REQ_LIST)) &&
            (($countOptional >= 0) && ($countOptional <= count(self::ARG_OPT_LIST)));
    }


    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateActiveWeekday(array $params = []): bool
    {
        $flag = true;
        if (array_key_exists(self::ARG_REQ_ACTIVE_WEEKDAY, $params)) {
            $flag = false;
            if (is_numeric($params[self::ARG_REQ_ACTIVE_WEEKDAY])) {
                $value = (int)$params[self::ARG_REQ_ACTIVE_WEEKDAY];
                $diff = $params[self::ARG_REQ_ACTIVE_WEEKDAY] - $value;
                // <127 because at least one weekday schuold not be set
                if (($diff === 0) && ($value > 0) && ($value < 128)) {
                    $flag = true;
                }
            }
        }
        return $flag;
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
     * @return int
     */
    protected function validateOptional(array $params = []): int
    {
        return $this->countParamsInList(self::ARG_OPT_LIST, $params);
    }

    /**
     * tested 20201226
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
     * tested 20220910
     *
     * check, if the timer ist for this time active
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone in paramas
     * @param array<mixed> $params
     * @return bool
     */
    public function isActive(DateTime $dateLikeEventZone, $params = []): bool
    {
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            $result = new TimerStartStopRange();
            $result->failAllActive($dateLikeEventZone);
            $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
            return $result->getResultExist();
        }

        $bitsOfWeekdays = $this->getParameterActiveWeekday($params);
        $weekDayNumber = 2 ** ($dateLikeEventZone->format('N') - 1); // MO = 1, ... So = 7
        $flag = (($bitsOfWeekdays & $weekDayNumber) === $weekDayNumber);
        $start = clone $dateLikeEventZone;
        $start->setTime(0, 0);
        $stop = clone $dateLikeEventZone;
        $stop->setTime(23, 59, 59);
        $this->setIsActiveResult($start, $stop, $flag, $dateLikeEventZone, $params);
        return $this->lastIsActiveResult->getResultExist();
    }

    /**
     * tested:
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
     * tested 20210102
     *
     * @param DateTime $dateBelowNextActive lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateBelowNextActive, $params = []): TimerStartStopRange
    {
        $bitsOfWeekdays = $this->getParameterActiveWeekday($params);
        $count = 0;
        $testDate = clone $dateBelowNextActive; // the current dat may be part of an active Subb
        do {
            $testDate->add(new DateInterval('P1D'));
            $weekDayNumber = 2 ** ($testDate->format('N') - 1); // MO = 1, ... So = 7
            if ($count++ > 7) {
                throw new TimerException(
                    'The loop für detecting the next Weekday will fail. ' .
                    'Check your yaml-definition or the reason for the weekdaynumber `' . $weekDayNumber . '`.',
                    1602368962
                );
            }
        } while (($bitsOfWeekdays & $weekDayNumber) !== $weekDayNumber);
        $testDate->setTime(0, 0);

        /** @var TimerStartStopRange $nextRange */
        $nextRange = new TimerStartStopRange();
        $nextRange->setBeginning($testDate);
        $testDate->setTime(23, 59, 59, 999);  // the last microsecond of the day is not active
        $nextRange->setEnding($testDate);

        return $this->validateUltimateRangeForNextRange($nextRange, $params, $dateBelowNextActive);
    }

    /**
     * tested 20210116
     *
     * @param DateTime $dateAbovePrevActive
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateAbovePrevActive, $params = []): TimerStartStopRange
    {
        $bitsOfWeekdays = $this->getParameterActiveWeekday($params);
        $count = 0;
        $testDate = clone $dateAbovePrevActive; // the current dat may be part of an active Subb
        do {
            $testDate->sub(new DateInterval('P1D'));
            $weekDayNumber = 2 ** ($testDate->format('N') - 1); // MO = 1, ... So = 7
            if ($count++ > 7) {
                throw new TimerException(
                    'The loop für detecting the next Weekday will fail. ' .
                    'Check your yaml-definition or the reason for the weekdaynumber `' . $weekDayNumber . '`.',
                    1602368962
                );
            }
        } while (($bitsOfWeekdays & $weekDayNumber) !== $weekDayNumber);
        $testDate->setTime(0, 0);

        /** @var TimerStartStopRange $prevRange */
        $prevRange = new TimerStartStopRange();
        $prevRange->setBeginning($testDate);
        $testDate->setTime(23, 59, 59, 999);
        $prevRange->setEnding($testDate);

        return $this->validateUltimateRangeForPrevRange($prevRange, $params, $dateAbovePrevActive);
    }


    /**
     * @param array<mixed> $params
     * @return int
     */
    protected function getParameterActiveWeekday(array $params)
    {
        $result = 127;
        if ((array_key_exists(self::ARG_REQ_ACTIVE_WEEKDAY, $params)) &&
            (is_numeric($params[self::ARG_REQ_ACTIVE_WEEKDAY]))
        ) {
            $value = (int)$params[self::ARG_REQ_ACTIVE_WEEKDAY];
            $diff = $params[self::ARG_REQ_ACTIVE_WEEKDAY] - $value;
            if (($diff === 0) && ($value > 0) && ($value < 128)) {
                return $value;
            }
        }

        return $result;
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
        $this->lastIsActiveResult->setResultExist($flag);
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
}
