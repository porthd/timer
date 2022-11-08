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
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


class WeekdaylyTimer implements TimerInterface
{

    protected const TIMER_NAME = 'txTimerWeekdayly';
    protected const ARG_REQ_ACTIVE_WEEKDAY = 'activeWeekday';
    protected const ARG_REQ_LIST = [
        self::ARG_REQ_ACTIVE_WEEKDAY,
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_EVER_TIME_ZONE_OF_EVENT,
        self::ARG_USE_ACTIVE_TIMEZONE,
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
     * @return array
     */
    public static function getSelectorItem(): array
    {
        return [
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerWeekdayly.select.name',
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
     * tested 20210102
     *
     * @return array
     */
    public static function getFlexformItem(): array
    {
        return [
            self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/WeekdaylyTimer.flexform',
        ];
    }


    /**
     * tested special 20210102
     * tested general 20210102
     *
     * The method test, if the parameter are valid or not
     * remark: This method must not be tested, if the sub-methods are valid.
     * @param array $params
     * @return bool
     */
    public function validate(array $params = []): bool
    {
        $flag = true;
        $flag = $flag && $this->validateZone($params);
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
     * @param array $params
     * @return bool
     */
    protected function validateActiveWeekday(array $params = []): bool
    {
        $flag = true;
        if (isset($params[self::ARG_REQ_ACTIVE_WEEKDAY])) {
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
     * @param array $params
     * @return bool
     */
    protected function validateZone(array $params = []): bool
    {
        return !(isset($params[self::ARG_EVER_TIME_ZONE_OF_EVENT]))||
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
     * tested 20201226
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return bool
     */
    public function isAllowedInRange(DateTime $dateLikeEventZone, $params = []): bool
    {
        return ($params[self::ARG_ULTIMATE_RANGE_BEGINN] <= $dateLikeEventZone->format('Y-m-d H:i:s')) &&
            ($dateLikeEventZone->format('Y-m-d H:i:s') <= $params[self::ARG_ULTIMATE_RANGE_END]);
    }

    /**
     * tested 20220910
     *
     * check, if the timer ist for this time active
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone in paramas
     * @param array $params
     * @return bool
     */
    public function isActive(DateTime $dateLikeEventZone, $params = []): bool
    {
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            $result = GeneralUtility::makeInstance(TimerStartStopRange::class);
            $result->failAllActive($dateLikeEventZone);
            $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
            return $result;
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
     * @param array $params
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        return $this->getLastIsActiveResult($dateLikeEventZone, $params);
    }

    /**
     * tested 20210102
     *
     * @param DateTime $dateBelowNextActive lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array $params
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
        $nextRange = GeneralUtility::makeInstance(TimerStartStopRange::class);
        $nextRange->setBeginning($testDate);
        $testDate->setTime(23, 59, 59, 999);  // the last microsecond of the day is not active
        $nextRange->setEnding($testDate);
        if ((!$this->isAllowedInRange($nextRange->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($nextRange->getEnding(), $params))
        ) {
            $nextRange->failOnlyNextActive($dateBelowNextActive);
        }

        return $nextRange;
    }

    /**
     * tested 20210116
     *
     * @param DateTime $dateAbovePrevActive
     * @param array $params
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

        /** @var TimerStartStopRange $nextRange */
        $prevRange = GeneralUtility::makeInstance(TimerStartStopRange::class);
        $prevRange->setBeginning($testDate);
        $testDate->setTime(23, 59, 59, 999);
        $prevRange->setEnding($testDate);

        if ((!$this->isAllowedInRange($prevRange->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($prevRange->getEnding(), $params))
        ) {
            $prevRange->failOnlyNextActive($dateAbovePrevActive);
        }
        return $prevRange;
    }


    protected function getParameterActiveWeekday($params)
    {
        $result = 127;
        if ((isset($params[self::ARG_REQ_ACTIVE_WEEKDAY])) &&
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
            $this->lastIsActiveResult = GeneralUtility::makeInstance(TimerStartStopRange::class);
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
            $this->lastIsActiveResult = GeneralUtility::makeInstance(TimerStartStopRange::class);
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