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

//all testetd 20201228
// open periodDescription
use DateInterval;
use DateTime;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


/**
 * @package DailyTimer
 */
class DailyTimer implements TimerInterface
{

    protected const TIMER_NAME = 'txTimerDaily';

    protected const ARG_REQ_START_TIME = 'startTimeSeconds';
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_REQ_DURMIN_MIN = -1439;
    protected const ARG_REQ_DURMIN_MAX = 1439;
    protected const ARG_REQ_LIST = [
        self::ARG_REQ_START_TIME,
        self::ARG_REQ_DURATION_MINUTES,
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
    ];
    protected const ARG_OPT_ACTIVE_WEEKDAY = 'activeWeekday';

    protected const ARG_OPT_LIST = [
        self::ARG_OPT_ACTIVE_WEEKDAY,
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
     * tested 20201225
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }

    /**
     * tested 20201016/20201225
     * @return array
     */
    public static function getSelectorItem(): array
    {
        return [
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerDaily.select.name',
            self::TIMER_NAME,
        ];
    }


    /**
     * tested
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
     * tested 20201016
     * @return array
     */
    public static function getFlexformItem(): array
    {
        return [
            self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/DailyTimer.flexform',
        ];
    }


    /**
     * tested special 20201228
     * tested general 20201228
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
        $flag = $flag && $this->validateUltimate($params);
        $countRequired = $this->validateArguments($params);
        $flag = $flag && ($countRequired === count(self::ARG_REQ_LIST));
        $flag = $flag && $this->validateStartTime($params);
        $flag = $flag && $this->validateDurationMinutes($params);
        $flag = $flag && $this->validateActiveWeekday($params);
        $countOptions = $this->validateOptional($params);
        return $flag && ($countOptions >= 0) &&
            ($countOptions <= count(self::ARG_OPT_LIST));
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
    protected function validateArguments(array $params = []): int
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
    protected function validateStartTime(array $params = []): bool
    {
        return is_numeric($params[self::ARG_REQ_START_TIME]);
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
        return is_int($number) && ($number !== 0) && (($number - $value) === 0) &&
            ($number >= self::ARG_REQ_DURMIN_MIN) && ($number <= self::ARG_REQ_DURMIN_MAX);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateActiveWeekday(array $params = []): bool
    {
        $flag = true;
        if (isset($params[self::ARG_OPT_ACTIVE_WEEKDAY])) {
            $flag = false;
            if (is_numeric($params[self::ARG_OPT_ACTIVE_WEEKDAY])) {
                $value = (int)$params[self::ARG_OPT_ACTIVE_WEEKDAY];
                $diff = $params[self::ARG_OPT_ACTIVE_WEEKDAY] - $value;
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
     * check, if the timer it for this time active
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

        $bitsOfWeekdays = CustomTimerUtility::getParameterActiveWeekday(
            $params[self::ARG_OPT_ACTIVE_WEEKDAY]
        );
        $delayMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        $startTimeSeconds = ((empty($params[self::ARG_REQ_START_TIME])) ?
            0 :
            ((int)$params[self::ARG_REQ_START_TIME] % 86400)
        ); // seconds starting at 00:00
        $hours = floor($startTimeSeconds / 3600);
        $minutes = floor(($startTimeSeconds % 3600) / 60);
        $seconds = floor($startTimeSeconds % 60);
        $startTimerString = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        $dateTestString = $dateLikeEventZone->format('H:i:s');
        if ($startTimerString <= $dateTestString) {
            if ($delayMin >= 0) {
                $dateStartString = $dateLikeEventZone->format(self::TIMER_FORMAT_DATE) . ' ' . $startTimerString;
                $dateStart = DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME,
                    $dateStartString,
                    $dateLikeEventZone->getTimezone()
                );
                $dateStop = clone $dateStart;
                $dateStop->add(new DateInterval('PT' . abs($delayMin) . 'M'));
                $weekDayNumber = 2 ** ($dateStart->format('N') - 1); // MO = 1, ... So = 7
            } else {
                $dateStopString = $dateLikeEventZone->format(self::TIMER_FORMAT_DATE) . ' ' . $startTimerString;
                $dateStop = DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME,
                    $dateStopString,
                    $dateLikeEventZone->getTimezone()
                );
                if ($startTimerString < $dateTestString) {
                    $dateStop->add(new DateInterval('P1D'));
                }
                $dateStart = clone $dateStop;

                $dateStart->sub(new DateInterval('PT' . abs($delayMin) . 'M'));
                $weekDayNumber = 2 ** ($dateStop->format('N') - 1); // MO = 1, ... So = 7
            }
        } else { // remeber $startTimerString is ever greater than $dateTestString
            if ($delayMin >= 0) {
                $dateStartString = $dateLikeEventZone->format(self::TIMER_FORMAT_DATE) . ' ' . $startTimerString;
                $dateStart = DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME,
                    $dateStartString,
                    $dateLikeEventZone->getTimezone()
                );
                $dateStart->sub(new DateInterval('P1D'));
                $dateStop = clone $dateStart;
                $dateStop->add(new DateInterval('PT' . abs($delayMin) . 'M'));
                $weekDayNumber = 2 ** ($dateStart->format('N') - 1); // MO = 1, ... So = 7
            } else {
                $dateStopString = $dateLikeEventZone->format(self::TIMER_FORMAT_DATE) . ' ' . $startTimerString;
                $dateStop = DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME,
                    $dateStopString,
                    $dateLikeEventZone->getTimezone()
                );
                $dateStart = clone $dateStop;
                $dateStart->sub(new DateInterval('PT' . abs($delayMin) . 'M'));
                $weekDayNumber = 2 ** ($dateStop->format('N') - 1); // MO = 1, ... So = 7
            }
        }
        $flag = (($bitsOfWeekdays & $weekDayNumber) === $weekDayNumber) &&
            (($dateStart <= $dateLikeEventZone) && ($dateLikeEventZone <= $dateStop));

        $this->setIsActiveResult($dateStart, $dateStop, $flag, $dateLikeEventZone, $params);
        return $this->lastIsActiveResult->getResultExist();
    }

    /**
     * tested: 20221009
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
     * tested 20201227
     *
     * @param DateTime $dateBelowNextActive lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateBelowNextActive, $params = []): TimerStartStopRange
    {
        [$bitsOfWeekdays, $delayMin, $startTimeUtc] = $this->getParameterFromFlexform($params);

        $testTag = clone $dateBelowNextActive;

        /** @var TimerStartStopRange $nextRange */
        $nextRange = GeneralUtility::makeInstance(TimerStartStopRange::class);
        $count = 0;
        do {
            $dateBorder = DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME,
                $testTag->format(self::TIMER_FORMAT_DATE) . ' ' . $startTimeUtc->format(self::TIMER_FORMAT_TIME),
                $dateBelowNextActive->getTimezone()
            );

            if ($delayMin >= 0) {
                $startLimit = $dateBorder;
                $calcWeekDay = 2 ** ($startLimit->format('N') - 1);
                $stopLimit = clone $dateBorder;
                $stopLimit->add(new DateInterval('PT' . abs($delayMin) . 'M'));
            } else {
                $startLimit = $dateBorder;
                $stopLimit = clone $dateBorder;
                $calcWeekDay = 2 ** ($stopLimit->format('N') - 1);
                $startLimit->sub(new DateInterval('PT' . abs($delayMin) . 'M'));
            }
            $nextRange->setBeginning($startLimit);
            $nextRange->setEnding($stopLimit);
            $testTag->add(new DateInterval('P1D'));
            if ($count++ > 10) {
                $nextRange->setResultExist(false);
                throw new TimerException(
                    'The loop to detect the timer-active-slot wont work correctly. ' .
                    'Check the definitions of the weekdays in the flexform of tx_timer_timer.',
                    1602358042
                );
            }

        } while (
            !(
                ($startLimit > $dateBelowNextActive) &&
                (($calcWeekDay & $bitsOfWeekdays) === $calcWeekDay) &&
                ($nextRange->hasResultExist() !== false)
            )
        );

        if (($this->isAllowedInRange($nextRange->getBeginning(), $params)) &&
            ($this->isAllowedInRange($nextRange->getEnding(), $params))
        ) {
            return $nextRange;
        }
        $nextRange->failOnlyNextActive($dateBelowNextActive);
        return $nextRange;

    }

    /**
     * tested 20201227
     *
     * @param DateTime $dateAbovePrevActive
     * @param array $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateAbovePrevActive, $params = []): TimerStartStopRange
    {
        [$bitsOfWeekdays, $delayMin, $startTimeUtc] = $this->getParameterFromFlexform($params);

        $testTag = clone $dateAbovePrevActive;

        /** @var TimerStartStopRange $nextRange */
        $prevRange = GeneralUtility::makeInstance(TimerStartStopRange::class);
        $count = 0;
        do {
            $dateBorder = DateTime::createFromFormat(self::TIMER_FORMAT_DATETIME,
                $testTag->format(self::TIMER_FORMAT_DATE) . ' ' . $startTimeUtc->format(self::TIMER_FORMAT_TIME),
                $dateAbovePrevActive->getTimezone()
            );

            if ($delayMin >= 0) {
                $startLimit = $dateBorder;
                $calcWeekDay = 2 ** ($startLimit->format('N') - 1);
                $stopLimit = clone $dateBorder;
                $stopLimit->add(new DateInterval('PT' . abs($delayMin) . 'M'));
            } else {
                $startLimit = $dateBorder;
                $stopLimit = clone $dateBorder;
                $calcWeekDay = 2 ** ($stopLimit->format('N') - 1);
                $startLimit->sub(new DateInterval('PT' . abs($delayMin) . 'M'));
            }
            $prevRange->setBeginning($startLimit);
            $prevRange->setEnding($stopLimit);
            $testTag->sub(new DateInterval('P1D'));
            if ($count++ > 10) {
                $prevRange->setResultExist(false);
                throw new TimerException(
                    'The loop to detect the timer-active-slot wont work correctly. ' .
                    'Check the definitions of the weekdays in the flexform of tx_timer_timer.',
                    1602358042
                );
            }

        } while (
            !(
                ($stopLimit < $dateAbovePrevActive) &&
                (($calcWeekDay & $bitsOfWeekdays) === $calcWeekDay) &&
                ($prevRange->hasResultExist() !== false)
            )
        );

        if ((!$this->isAllowedInRange($prevRange->getBeginning(), $params)) ||
            (!$this->isAllowedInRange($prevRange->getEnding(), $params))
        ) {
            $prevRange->failOnlyNextActive($dateAbovePrevActive);
        }
        return $prevRange;
    }

    /**
     * @param array $params
     * @return array
     */
    protected function getParameterFromFlexform(array $params): array
    {
        $bitsOfWeekdays = CustomTimerUtility::getParameterActiveWeekday($params[self::ARG_OPT_ACTIVE_WEEKDAY]);
        $delayMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        $startTime = new DateTime('@' . $params[self::ARG_REQ_START_TIME]);
        return [$bitsOfWeekdays, $delayMin, $startTime];
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
        return (clone $this->lastIsActiveResult);
    }

}
