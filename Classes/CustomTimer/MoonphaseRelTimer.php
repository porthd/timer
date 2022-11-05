<?php

namespace Porthd\Timer\CustomTimer;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 Dr. Dieter Porthd <info@mobger.de>
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
use Porthd\Timer\CustomTimer\StrangerCode\MoonPhase\Solaris\MoonPhase;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use SebastianBergmann\Timer\Timer;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class MoonphaseRelTimer implements TimerInterface
{
    public const TIMER_NAME = 'txTimerMoonphaseRel';
    protected const ARG_MOON_PHASE = 'moonPhase';
    protected const AVG_SECONDS_MOON_PHASE = 2551443;
    protected const LIST_MOON_PHASE = [
        'new_moon',
        'first_quarter',
        'full_moon',
        'last_quarter',
    ];
    protected const ARG_REL_MIN_TO_EVENT = 'relMinToSelectedTimerEvent';
    protected const ARG_REQ_REL_TO_MIN = -28800;
    protected const ARG_REQ_REL_TO_MAX = 28800;
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_DURMIN_MIN = -28800;
    protected const ARG_DURMIN_MAX = 28800;
    protected const ARG_EVER_TIME_ZONE_OF_EVENT = TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT;
    protected const ARG_USE_ACTIVE_TIMEZONE =TimerConst::ARG_USE_ACTIVE_TIMEZONE;
    protected const ARG_ULTIMATE_RANGE_BEGINN = TimerConst::ARG_ULTIMATE_RANGE_BEGINN;
    protected const ARG_ULTIMATE_RANGE_END = TimerConst::ARG_ULTIMATE_RANGE_END;
    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/MoonphaseRelTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_MOON_PHASE,
        self::ARG_REL_MIN_TO_EVENT,
        self::ARG_REQ_DURATION_MINUTES,
        TimerConst::ARG_ULTIMATE_RANGE_BEGINN,
        TimerConst::ARG_ULTIMATE_RANGE_END,
    ];
    protected const ARG_OPT_LIST = [
        TimerConst::ARG_USE_ACTIVE_TIMEZONE,
        TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT,
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
     * tested 20210116
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }

    /**
     * tested 20210116
     *
     * @return array
     */
    public static function getSelectorItem(): array
    {
        return [
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerMoonphaseRel.select.name',
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
     * tested 20210116
     *
     * @return array
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
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
        return ($params[TimerConst::ARG_ULTIMATE_RANGE_BEGINN] <= $dateLikeEventZone->format('Y-m-d H:i:s')) &&
            ($dateLikeEventZone->format('Y-m-d H:i:s') <= $params[TimerConst::ARG_ULTIMATE_RANGE_END]);
    }

    /**
     * tested general 20210116
     * tested special 20210117
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
        $flag = $flag && $this->validateMoonPhase($params);
        $flag = $flag && $this->validateDurationMinutes($params);
        $flag = $flag && $this->validateRelMinToEvent($params);
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
        return !(isset($params[TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT]))||
            TcaUtility::isTimeZoneInList(
                $params[TimerConst::ARG_EVER_TIME_ZONE_OF_EVENT]
            );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateUltimate(array $params = []): bool
    {
        $flag = (!empty($params[TimerConst::ARG_ULTIMATE_RANGE_BEGINN]));
        $flag = $flag && (false !== date_create_from_format(
                    TimerConst::TIMER_FORMAT_DATETIME,
                    $params[TimerConst::ARG_ULTIMATE_RANGE_BEGINN]
                ));
        $flag = $flag && (!empty($params[TimerConst::ARG_ULTIMATE_RANGE_END]));
        return ($flag && (false !== date_create_from_format(
                    TimerConst::TIMER_FORMAT_DATETIME,
                    $params[TimerConst::ARG_ULTIMATE_RANGE_END]
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
     * @return int
     */
    protected function validateMoonPhase(array $params = []): bool
    {
        $string = $params[self::ARG_MOON_PHASE];
        return ((!empty($string)) &&
            (is_string($string)) &&
            (in_array($string, self::LIST_MOON_PHASE))
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
        return is_int($number) && ($number !== 0) && (($number - $value) === 0) &&
            ($number >= self::ARG_DURMIN_MIN) && ($number <= self::ARG_DURMIN_MAX);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateRelMinToEvent(array $params = []): bool
    {
        $value = (isset($params[self::ARG_REL_MIN_TO_EVENT]) ?
            $params[self::ARG_REL_MIN_TO_EVENT] :
            0
        );
        $number = (int)$value;
        return is_int($number) && (($number - $value) === 0) &&
            ($number >= self::ARG_REQ_REL_TO_MIN) && ($number <= self::ARG_REQ_REL_TO_MAX);
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
     * tested
     *
     * check, if calculate the active- by starting at the current date, so the parameter must used in a negative way.
     *
     * definition. relativ to the date of moonphase ist e gap of active time. the current time must be part of the interval
     * [Time(Moonphase)+Time(rel)+0;Time(Moonphase)+Time(rel)+TimeGape];
     * retrospective-View of the problem. relative to the current  date is e gap of active time. the time of the nearest moonphase must be part of the interval
     * [Time(current)-Time(rel)-TimeGape, Time(current)-Time(rel)-0];
     * The method use the resprective-Way of Check.
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas
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

        $utcDateTime = new DateTime('@' .
            ($dateLikeEventZone->getTimestamp() - (int)($params[self::ARG_REL_MIN_TO_EVENT] ?? 0) * 60),
            new DateTimeZone('UTC')
        );

        /** the result in  $moonPhaseCalculator is the GMT-timestamp relative to the calculation */
        $moonPhaseCalculator = new MoonPhase($utcDateTime);
        $moonPhase = $params[self::ARG_MOON_PHASE];
        $moonPhaseTStamp = $moonPhaseCalculator->get_phase($moonPhase);
        $rangeSec = (int)$params[self::ARG_REQ_DURATION_MINUTES] * 60;
        if ($rangeSec > 0) {
            $higher = $utcDateTime->getTimestamp();
            $lower = $higher - $rangeSec;
        } else {
            if ($rangeSec < 0) {
                $lower = $utcDateTime->getTimestamp();
                $higher = $lower - $rangeSec; // - abs($rangeSec);
            } else {
                // unallowed duration => will bringe everytime the result false
                $lower = $utcDateTime->getTimestamp();
                $higher = $lower - 1;
            }
        }
        $flag = (($lower <= $moonPhaseTStamp) && ($moonPhaseTStamp <= $higher));
        $this->setIsActiveResult(
            $lower,
            $higher,
            $flag,
            $dateLikeEventZone,
            $params
        );
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
     * tested 20220925
     *
     * calculate the next range related to moon-shifts relative to a given date
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $relSeconds = (int)$params[self::ARG_REL_MIN_TO_EVENT] * 60;
        $baseTStamp = $dateLikeEventZone->getTimestamp() - $relSeconds;
        $utcDateTime = new DateTime('@' . $baseTStamp, new DateTimeZone('UTC'));
        $moonPhaseCalculator = new MoonPhase($utcDateTime);
        $moonPhase = $params[self::ARG_MOON_PHASE];
        $moonPhaseTStamp = $moonPhaseCalculator->get_phase($moonPhase);

        $rangeSec = (int)$params[self::ARG_REQ_DURATION_MINUTES] * 60;
        [$upper, $lower] = $this->caluculateReverseRange($rangeSec, $baseTStamp, $utcDateTime->getTimestamp());
        $result = GeneralUtility::makeInstance(TimerStartStopRange::class);
        if (($upper > $lower) && ($rangeSec !== 0)) {
            if ($moonPhaseTStamp > $upper) {
                [$lowerLimit, $upperLimit] = $this->calculateRangeRoundToMinute(
                    $rangeSec,
                    $moonPhaseTStamp,
                    $relSeconds,
                    $dateLikeEventZone
                );
            } else {
                $nextMoonPhase = 'next_' . $moonPhase;
                $startStamp = $moonPhaseCalculator->get_phase($nextMoonPhase);
                [$lowerLimit, $upperLimit] = $this->calculateRangeRoundToMinute(
                    $rangeSec,
                    $startStamp,
                    $relSeconds,
                    $dateLikeEventZone
                );
            }
            if (($this->isAllowedInRange($lowerLimit, $params)) &&
                ($this->isAllowedInRange($upperLimit, $params))
            ) {
                $result->setBeginning($lowerLimit);
                $result->setEnding($upperLimit);
                $result->setResultExist(true);
            } else {
                $result->failOnlyNextActive($dateLikeEventZone);
            }
        } else {
            $result->failOnlyNextActive($dateLikeEventZone);
        }
        return $result;
    }

    /**
     * tested 20220730
     *
     * calculate the previous range related to moon-shifts relative to a given date
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $relSeconds = (int)$params[self::ARG_REL_MIN_TO_EVENT] * 60;
        $rangeSec = (int)$params[self::ARG_REQ_DURATION_MINUTES] * 60;
        $moonPhase = $params[self::ARG_MOON_PHASE];
        $origRefStamp = $dateLikeEventZone->getTimestamp() - $relSeconds; // recalulate the current date back to the mooning-timestamps
//        $refStamp = $origRefStamp - self::AVG_SECONDS_MOON_PHASE; // recalulate the current date back to the mooning-timestamps
        $refStamp = $origRefStamp; // recalulate the current date back to the mooning-timestamps

        $result = GeneralUtility::makeInstance(TimerStartStopRange::class);
        [$upperMooning, $lowerMooning] = $this->caluculateReverseRange($rangeSec, $refStamp,
            $dateLikeEventZone->getTimestamp());
        for ($i = 0; $i < 4; $i++) {
            $refMooningDate = new DateTime('@' . $refStamp);
            $moonPhaseCalculator = new MoonPhase($refMooningDate);
            $moonPhaseTStamp = (int)$moonPhaseCalculator->get_phase($moonPhase);

            if ($lowerMooning > $moonPhaseTStamp) {
                [$lowerLimit, $upperLimit] = $this->calculateRangeRoundToMinute(
                    $rangeSec,
                    $moonPhaseTStamp,
                    $relSeconds,
                    $dateLikeEventZone
                );
                // calculated the current mooncycle
                break;
            }
            // check previous moon-cycle
            $refStamp = $moonPhaseTStamp - self::AVG_SECONDS_MOON_PHASE;
        }
        if ((isset($lowerMooning, $upperMooning)) &&
            ($lowerMooning < $upperMooning) &&
            ($rangeSec !== 0)
        ) {

            if (($this->isAllowedInRange($lowerMooning, $params)) &&
                ($this->isAllowedInRange($upperMooning, $params))
            ) {
                $result->setBeginning($lowerMooning);
                $result->setEnding($upperMooning);
                $result->setResultExist(true);
            } else {
                $result->failOnlyNextActive($dateLikeEventZone);
            }
        } else {
            $result->failOnlyNextActive($dateLikeEventZone);
        }
        return $result;
    }

    /**
     * @param $rangeSec
     * @param float|null $moonPhaseTStamp
     * @param $relSeconds
     * @param DateTime $dateLikeEventZone
     * @return DateTime[]
     * @throws Exception
     */
    protected function calculateRangeRoundToMinute(
        $rangeSec,
        ?float $moonPhaseTStamp,
        $relSeconds,
        DateTime $dateLikeEventZone
    ): array {
        if ($rangeSec > 0) {
            $lower = ceil($moonPhaseTStamp) + $relSeconds;
            $upper = $lower + $rangeSec;
        } else {
            $upper = ceil($moonPhaseTStamp) + $relSeconds;
            $lower = $upper + $rangeSec; // $rangeSec is negative
        }
        $lower = $lower - $lower % 60; // Normalize the seconds down to zero in the dateTime-format
        $upper = $upper + (60 - $upper % 60); // Normalize the seconds up to zero in the dateTime-format
        $lowerLimit = new DateTime('@' . $lower);
        $lowerLimit->setTimezone($dateLikeEventZone->getTimezone());
        $upperLimit = new DateTime('@' . $upper);
        $upperLimit->setTimezone($dateLikeEventZone->getTimezone());
        return [$lowerLimit, $upperLimit];
    }

    /**
     * @param $rangeSec
     * @param $baseTStamp
     * @param DateTime $utcDateTime
     * @return array
     */
    protected function caluculateReverseRange($rangeSec, $baseTStamp, $currentStamp = 0): array
    {
        if ($rangeSec > 0) {
            $upper = $baseTStamp;
            $lower = $baseTStamp - $rangeSec;
        } else {
            if ($rangeSec < 0) {
                $lower = $baseTStamp;
                $upper = $baseTStamp - $rangeSec;
            } else {
                // unallowed duration
                $upper = $currentStamp;
                $lower = $upper + 1;
            }
        }
        return [$upper, $lower];
    }

    /**
     * @param int $dateStartStamp
     * @param int $dateStopStamp
     * @param bool $flag
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @throws Exception
     */
    protected function setIsActiveResult(
        int $dateStartStamp,
        int $dateStopStamp,
        bool $flag,
        DateTime $dateLikeEventZone,
        $params = []
    ): void {
        $dateStart = new DateTime('@' . $dateStartStamp, $dateLikeEventZone->getTimezone());
        $dateStop = new DateTime('@' . $dateStopStamp, $dateLikeEventZone->getTimezone());

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