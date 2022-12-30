<?php

namespace Porthd\Timer\CustomTimer;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2022 Dr. Dieter Porth <info@mobger.de>
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
use Porthd\Timer\Constants\JewishHolidayConst;
use Porthd\Timer\Constants\JewishHolidayConstTrait;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use Porthd\Timer\Utilities\JewishDateUtility;

class JewishHolidayTimer extends JewishHolidayConst implements TimerInterface
{
    use GeneralTimerTrait;


    public const TIMER_NAME = 'txTimerJewishHoliday';

    protected const ARG_REL_MIN_TO_SELECTED_TIMER_EVENT = 'relMinToSelectedTimerEvent';
    protected const ARG_REQ_REL_TO_MIN = -475200;
    protected const ARG_REQ_REL_TO_MAX = 475200;
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_REQ_DURMIN_MIN = -475200;
    protected const ARG_REQ_DURMIN_FORBIDDEN = 0;
    protected const ARG_REQ_DURMIN_MAX = 475200;

    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/JewsihHolidayTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,

        self::ARG_NAMED_DATE_MIDNIGHT,
        self::ARG_REQ_DURATION_MINUTES,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT,
    ];


    /**
     * @var TimerStartStopRange|null
     */
    protected $lastIsActiveResult;

    /**
     * @var int|null
     */
    protected $lastIsActiveTimestamp = null;

    /**
     * @var array<mixed>
     */
    protected $lastIsActiveParams = [];

    /**
     * tested 20221229
     *
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }

    /**
     * tested 20221229
     *
     * @return array<mixed>
     */
    public static function getSelectorItem(): array
    {
        return [
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerJewishHoliday.select.name',
            self::TIMER_NAME,
        ];
    }

    /**
     * tested 20221229
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
     * tested 20221229
     *
     * @return array<mixed>
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
    }


    /**
     * tested special
     * tested general 20221229
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
        $countRequired = $this->validateArguments($params);
        $flag = $flag && ($countRequired === count(self::ARG_REQ_LIST));
        $flag = $flag && $this->validateDurationMinutes($params);
        $flag = $flag && $this->validateNamedDateMidnight($params);
        $flag = $flag && $this->validateRelMinToSelectedTimerEvent($params);
        $countOptions = $this->validateOptional($params);
        return $flag && ($countOptions >= 0) &&
            ($countOptions <= count(self::ARG_OPT_LIST));
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
    protected function validateDurationMinutes(array $params = []): bool
    {
        $number = (int)($params[self::ARG_REQ_DURATION_MINUTES] ?: 0); // what will happen with float
        $floatNumber = (float)($params[self::ARG_REQ_DURATION_MINUTES] ?: 0);
        return (
            ($number - $floatNumber == 0) &&
            ($number >= self::ARG_REQ_DURMIN_MIN) &&
            ($number !== self::ARG_REQ_DURMIN_FORBIDDEN) &&
            ($number <= self::ARG_REQ_DURMIN_MAX)
        );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateNamedDateMidnight(array $params = []): bool
    {
        $key = $params[self::ARG_NAMED_DATE_MIDNIGHT] ?: self::ARG_NAMED_DATE_MIDNIGHT_DEFAULT;
        return in_array($key, self::ARG_NAMED_DATE_LIST, true);
    }


    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateRelMinToSelectedTimerEvent(array $params = []): bool
    {
        $number = (int)$params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] ?: 0; // what will happen with float
        $floatNumber = (float)$params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] ?: 0;
        return (
            ($number - $floatNumber == 0) &&
            ($number >= self::ARG_REQ_REL_TO_MIN) &&
            ($number <= self::ARG_REQ_REL_TO_MAX)
        );
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
     * tested 20221229
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return bool
     */
    public function isAllowedInRange(DateTime $dateLikeEventZone, $params = []): bool
    {
        return ($params[self::ARG_ULTIMATE_RANGE_BEGINN] <= $dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME)) &&
            ($dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME) <= $params[self::ARG_ULTIMATE_RANGE_END]);
    }

    /**
     * tested
     *
     * check, if the timer ist for this time active
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas
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

        $testRanges = $this->calcDefinedRangesByStartDateTime($dateLikeEventZone, $params);

        $flag = false;
        $start = clone $dateLikeEventZone;
        $start->sub(new DateInterval('PT30S'));
        $stop = clone $dateLikeEventZone;
        $stop->add(new DateInterval('PT30S'));
        $flagFirst = true;
        foreach ($testRanges as $testrange) {
            if ($testrange['begin'] <= $dateLikeEventZone) {
                if ($flagFirst) {
                    $flagFirst = false;
                    $start = clone $testrange['begin'];
                    $stop = clone $testrange['end'];
                }
                if (($dateLikeEventZone <= $testrange['end'])) {
                    $flag = true;
                    $start = clone $testrange['begin'];
                    $stop = clone $testrange['end'];
                    break;
                }
            }
        }
        $this->setIsActiveResult($start, $stop, $flag, $dateLikeEventZone, $params);
        return $this->lastIsActiveResult->getResultExist();
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
     * tested
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            return $result;
        }


        $start = 3;
        $refDate = clone $dateLikeEventZone;
        // loop for the constructed, that the range is not enough?
        while ($start > 0) {
            $testRanges = $this->calcDefinedRangesByStartDateTime($refDate, $params);
            if(empty($testRanges)) {
                throw new TimerException(
                    'Unexpected Error: The $testRanges in the method `'.__CLASS__.'nextActive` are empty. '.
                    'Please make a screenshot and inform the webmaster.',
                    1672424857
                );
            }
            foreach ($testRanges as $testrange) {
                if ($testrange['begin'] > $dateLikeEventZone) {
                    $result->setBeginning($testrange['begin']);
                    $result->setEnding($testrange['end']);
                    $result->setResultExist(true);
                    break 2;
                }
            }
            $start--;
            $refDate = clone $testRanges[array_key_last($testRanges)]['end'];
        }

        return $this->validateUltimateRangeForNextRange($result, $params, $dateLikeEventZone);
    }

    /**
     * tested
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            return $result;
        }


        $start = 3;
        $refDate = clone $dateLikeEventZone;
        // loop for the constructed, that the range is not enough?
        while ($start > 0) {
            $testRanges = array_reverse(
                $this->calcDefinedRangesByStartDateTime($refDate, $params)
            );
            if(empty($testRanges)) {
                throw new TimerException(
                    'Unexpected Error: The $testRanges in the method `'.__CLASS__.'prevActive` are empty. '.
                    'Please make a screenshot and inform the webmaster.',
                    1672424973
                );
            }
            foreach ($testRanges as $testrange) {
                if ($testrange['end'] < $dateLikeEventZone) {
                    $result->setBeginning($testrange['begin']);
                    $result->setEnding($testrange['end']);
                    $result->setResultExist(true);
                    break 2;
                }
            }
            $start--;
            $refDate = clone $testRanges[array_key_first($testRanges)]['begin'];
        }

        return $this->validateUltimateRangeForNextRange($result, $params, $dateLikeEventZone);
    }

    /**
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return array<mixed>
     * @throws Exception
     */
    protected function calcDefinedRangesByStartDateTime(DateTime $dateLikeEventZone, array $params): array
    {
        $relToDateMin = (int)(
            isset($params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT]) ?
            $params[self::ARG_REL_MIN_TO_SELECTED_TIMER_EVENT] :
            0
        );
        $relInterval = new DateInterval('PT' . abs($relToDateMin) . 'M');
        $durationMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        $durInterval = new DateInterval('PT' . abs($durationMin) . 'M');
        $startDateRanges = JewishDateUtility::getJewishHolidayByName(
            $params[self::ARG_NAMED_DATE_MIDNIGHT],
            $dateLikeEventZone
        );
        $ranges = [];
        foreach ($startDateRanges as $index => $startDateRange) {
            if ($relToDateMin >= 0) {
                $startDateRange->add($relInterval);
            } else {
                $startDateRange->sub($relInterval);
            }
            if ($durationMin > 0) {
                $ranges[$index]['begin'] = clone $startDateRange;
                $startDateRange->add($durInterval);
                $ranges[$index]['end'] = clone $startDateRange;
            } else {
                $ranges[$index]['end'] = clone $startDateRange;
                $startDateRange->sub($durInterval);
                $ranges[$index]['begin'] = clone $startDateRange;
            }
        }

        return $ranges;
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
        bool $flag,
        DateTime $dateLikeEventZone,
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
