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
use Porthd\Timer\CustomTimer\StrangerCode\MoonOfDay\MoonRiseSet;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\GeneralTimerUtility;

class MoonriseRelTimer implements TimerInterface
{
    use GeneralTimerTrait;

    public const TIMER_NAME = 'txTimerMoonriseRel';

    protected const ARG_MOON_STATUS = 'moonStatus';
    protected const LIST_MOON_STATUS = ['moonrise', 'moonset'];
    protected const ARG_REL_MIN_TO_EVENT = 'relMinToSelectedTimerEvent';
    protected const ARG_REQ_REL_TO_MIN = -1439;
    protected const ARG_REQ_REL_TO_MAX = 1439;
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_REQ_DURMIN_MIN = -1439;
    protected const ARG_REQ_DURMIN_FORBIDDEN = 0;
    protected const ARG_REQ_DURMIN_MAX = 1439;
    protected const ARG_LATITUDE = 'latitude';
    protected const ARG_LONGITUDE = 'longitude';
    protected const DEFAULT_LATITUDE = 47.599329;// see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const DEFAULT_LONGITUDE = 3.534787; // see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const ARG_LATITUDE_MAX = 90;// see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const ARG_LONGITUDE_MAX = 180; // see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const ARG_LATITUDE_MIN = -90;// see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const ARG_LONGITUDE_MIN = -180; // see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04

    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/MoonriseRelTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,

        self::ARG_MOON_STATUS,
        self::ARG_REL_MIN_TO_EVENT,
        self::ARG_REQ_DURATION_MINUTES,
        self::ARG_LATITUDE,
        self::ARG_LONGITUDE,
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
     * test 20210116
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }

    /**
     * test 20210116
     * @return array<mixed>
     */
    public static function getSelectorItem(): array
    {
        return [
            TimerConst::TCA_ITEMS_LABEL => 'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerMoonriseRel.select.name',
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
     * test 20210116
     *
     * @return array<mixed>
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
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
     * tested general 20221115
     * tested special 20210116
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
        $flag = $flag && $this->validateMoonStatus($params);
        $flag = $flag && $this->validateDurationMinutes($params);
        $flag = $flag && $this->validateRelMinToEvent($params);
        $flag = $flag && $this->validateLatitude($params);
        $flag = $flag && $this->validateLongitude($params);
        $flag = $flag && $this->validateRelMinToEvent($params);
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
    protected function validateMoonStatus(array $params = []): bool
    {
        $string = (string)$params[self::ARG_MOON_STATUS];
        return ((!empty($string)) &&
            (in_array($string, self::LIST_MOON_STATUS))
        );
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
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateRelMinToEvent(array $params = []): bool
    {
        $number = (int)(
        array_key_exists(self::ARG_REL_MIN_TO_EVENT, $params) ?
            $params[self::ARG_REL_MIN_TO_EVENT] :
            0
        );
        return (
            ($number >= self::ARG_REQ_REL_TO_MIN) &&
            ($number <= self::ARG_REQ_REL_TO_MAX)
        );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateLatitude(array $params = []): bool
    {
        $number = (float)($params[self::ARG_LATITUDE] ?: self::DEFAULT_LATITUDE);
        return (
            ($number >= self::ARG_LATITUDE_MIN) &&
            ($number <= self::ARG_LATITUDE_MAX)
        );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateLongitude(array $params = []): bool
    {
        $number = (float)($params[self::ARG_LONGITUDE] ?: self::DEFAULT_LATITUDE);
        return (
            ($number >= self::ARG_LONGITUDE_MIN) &&
            ($number <= self::ARG_LONGITUDE_MAX)
        );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return int
     */
    protected function validateOptional(array $params = []): int
    {
        // part of trait
        return $this->countParamsInList(self::ARG_OPT_LIST, $params);
    }

    /**
     * check, if the timer ist for this time active
     *
     * tested: 20221004
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

        [$latitude, $longitude] = $this->defineLongitudeLatitudeByParams($params, $dateLikeEventZone->getOffset());
        // iterate to the current range from below // duration and relative limited to one day. Minus 3d should enough for the iteration-range
        // calculation is not optimal
        $timerRange = $this->calculateRangeRelToMoonStatus(
            $dateLikeEventZone,
            $latitude,
            $longitude,
            $params
        );

        $flag = (
            ($timerRange !== null) &&
            ($timerRange->getBeginning() <= $dateLikeEventZone) &&
            ($dateLikeEventZone <= $timerRange->getEnding())
        );
        $this->setIsActiveResult(
            $timerRange->getBeginning(),
            $timerRange->getEnding(),
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
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult(DateTime $dateLikeEventZone, array $params = []): TimerStartStopRange
    {
        return $this->getLastIsActiveResult($dateLikeEventZone, $params);
    }

    /**
     * tested: 20221005
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        [$latitude, $longitude] = $this->defineLongitudeLatitudeByParams($params, $dateLikeEventZone->getOffset());

        $utcDateTime = clone $dateLikeEventZone;
        $utcDateTime->setTimezone(new DateTimeZone('UTC'));
        $moonInfoList = $this->getMoonDatasForDefinedDate(
            $params[self::ARG_REQ_DURATION_MINUTES],
            $utcDateTime,
            $latitude,
            $longitude
        );
        $rangeMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];

        if ((!in_array($params[self::ARG_MOON_STATUS], self::LIST_MOON_STATUS)) ||
            (!array_key_exists($params[self::ARG_MOON_STATUS], $moonInfoList)) ||
            (!array_key_exists('flag' . ucfirst($params[self::ARG_MOON_STATUS]), $moonInfoList)) ||
            ($rangeMin === 0)
        ) {
            throw new TimerException(
                'The moonposition `' . $params[self::ARG_MOON_STATUS] . '` or the range `' . $rangeMin .
                '` is not correctly defined.' . ' Allowed are these variations in `nextActive`: `' . implode(
                    '`, `',
                    self::LIST_MOON_STATUS
                ) . '` and the range must not zero. ',
                1607249334
            );
        }

        $timerRange = new TimerStartStopRange();
        if ($moonInfoList[('flag' . ucfirst($params[self::ARG_MOON_STATUS]))] !== false) {
            [$lower, $upper] = $this->defineRangesFromMoonDates(
                $moonInfoList[$params[self::ARG_MOON_STATUS]],
                $params
            );
            $flagActive = true;
            $testTimestamp = $dateLikeEventZone->getTimestamp();
            $addDays = 1;
            while ($lower <= $testTimestamp) {
                $utcDateTime = clone $dateLikeEventZone;
                $utcDateTime->setTimezone(new DateTimeZone('UTC'));
                $utcDateTime->add(new DateInterval('P' . $addDays . 'D')); // the next lower moonrise moon set should on day below
                $moonInfoList = $this->getMoonDatasForDefinedDate(
                    $params[self::ARG_REQ_DURATION_MINUTES],
                    $utcDateTime,
                    $latitude,
                    $longitude
                );
                $flagActive = false;
                if ($moonInfoList[('flag' . ucfirst($params[self::ARG_MOON_STATUS]))] !== false) {
                    [$lower, $upper] = $this->defineRangesFromMoonDates(
                        $moonInfoList[$params[self::ARG_MOON_STATUS]],
                        $params
                    );
                    $flagActive = true;
                }
                $addDays++;
                if ($addDays > 15) {
                    $flagActive = false;
                    break;
                }
            }

            $lowerDateTime = new DateTime('@' . $lower);
            $lowerDateTime->setTimezone($dateLikeEventZone->getTimezone());
            $upperDateTime = new DateTime('@' . $upper);
            $upperDateTime->setTimezone($dateLikeEventZone->getTimezone());

            $timerRange->setBeginning($lowerDateTime);
            $timerRange->setEnding($upperDateTime);
            $timerRange->setResultExist($flagActive);

            return $this->validateUltimateRangeForNextRange($timerRange, $params, $dateLikeEventZone);
        }

        $timerRange->failAllActive($dateLikeEventZone);
        return $timerRange;
    }

    /**
     * tested: 20221005
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        [$latitude, $longitude] = $this->defineLongitudeLatitudeByParams($params, $dateLikeEventZone->getOffset());

        $utcDateTime = clone $dateLikeEventZone;
        $utcDateTime->setTimezone(new DateTimeZone('UTC'));
        $moonInfoList = $this->getMoonDatasForDefinedDate(
            $params[self::ARG_REQ_DURATION_MINUTES],
            $utcDateTime,
            $latitude,
            $longitude
        );
        $rangeMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        if ((!in_array($params[self::ARG_MOON_STATUS], self::LIST_MOON_STATUS)) ||
            (!array_key_exists($params[self::ARG_MOON_STATUS], $moonInfoList)) ||
            (!array_key_exists('flag' . ucfirst($params[self::ARG_MOON_STATUS]), $moonInfoList)) ||
            ($rangeMin === 0)
        ) {
            throw new TimerException(
                'The moonposition `' . $params[self::ARG_MOON_STATUS] . '` or the range `' . $rangeMin .
                '` is not correctly defined.' . ' Allowed are these variations in `prevActive`: `' . implode(
                    '`, `',
                    self::LIST_MOON_STATUS
                ) . '` and the range must not zero. ',
                1607249335
            );
        }
        $timerRange = new TimerStartStopRange();
        if ($moonInfoList[('flag' . ucfirst($params[self::ARG_MOON_STATUS]))] !== false) {
            [$lower, $upper] = $this->defineRangesFromMoonDates(
                $moonInfoList[$params[self::ARG_MOON_STATUS]],
                $params
            );
            $flagActive = true;
            $testTimestamp = $dateLikeEventZone->getTimestamp();
            $subDays = 1;
            while ($upper >= $testTimestamp) {
                $utcDateTime = clone $dateLikeEventZone;
                $utcDateTime->setTimezone(new DateTimeZone('UTC'));
                $utcDateTime->sub(new DateInterval('P' . $subDays . 'D')); // the next lower moonrise moon set should on day below
                $moonInfoList = $this->getMoonDatasForDefinedDate(
                    $params[self::ARG_REQ_DURATION_MINUTES],
                    $utcDateTime,
                    $latitude,
                    $longitude
                );
                $flagActive = false;
                if ($moonInfoList[('flag' . ucfirst($params[self::ARG_MOON_STATUS]))] !== false) {
                    [$lower, $upper] = $this->defineRangesFromMoonDates(
                        $moonInfoList[$params[self::ARG_MOON_STATUS]],
                        $params
                    );
                    $flagActive = true;
                }
                $subDays++;
                if ($subDays > 15) {
                    $flagActive = false;
                    break;
                }
            }

            $lowerDateTime = new DateTime('@' . $lower);
            $lowerDateTime->setTimezone($dateLikeEventZone->getTimezone());
            $upperDateTime = new DateTime('@' . $upper);
            $upperDateTime->setTimezone($dateLikeEventZone->getTimezone());
            $timerRange->setBeginning($lowerDateTime);
            $timerRange->setEnding($upperDateTime);
            $timerRange->setResultExist($flagActive);

            return $this->validateUltimateRangeForPrevRange($timerRange, $params, $dateLikeEventZone);
        }

        $timerRange->failAllActive($dateLikeEventZone);
        return $timerRange;
    }


    /**
     * @param array<mixed> $params
     * @param int $gap
     * @return float[]
     */
    protected function defineLongitudeLatitudeByParams(array $params, int $gap): array
    {
        $latitude = (float)(
        (((array_key_exists(self::ARG_LATITUDE, $params)) &&
            ($params[self::ARG_LATITUDE] >= self::ARG_LATITUDE_MIN) &&
            $params[self::ARG_LATITUDE] <= self::ARG_LATITUDE_MAX)) ?
            ($params[self::ARG_LATITUDE]) :
            (self::DEFAULT_LATITUDE)
        );
        $longitude = (float)self::DEFAULT_LONGITUDE;
        if ((array_key_exists(self::ARG_USE_ACTIVE_TIMEZONE, $params)) &&
            (!empty($params[self::ARG_USE_ACTIVE_TIMEZONE]))
        ) {
            // the timezone Pacific/Auckland  has an offset of 46800 s relativ to UTC
            $longitude = $gap / 240.0;  // =360/86400
        } else {
            if ((array_key_exists(self::ARG_LONGITUDE, $params)) &&
                ($params[self::ARG_LONGITUDE] >= self::ARG_LONGITUDE_MIN) &&
                ($params[self::ARG_LONGITUDE] <= self::ARG_LONGITUDE_MAX)
            ) {
                $longitude = (float)$params[self::ARG_LONGITUDE];
            }
        }
        return [$latitude, $longitude];
    }

    /**
     * try to calculate for a given date a specific
     * @param DateTime $dateLikeEventZone
     * @param float $latitude
     * @param float $longitude
     * @param array<mixed> $params
     * @return TimerStartStopRange
     * @throws TimerException
     */
    protected function calculateRangeRelToMoonStatus(
        DateTime $dateLikeEventZone,
        $latitude,
        $longitude,
        array $params
    ): TimerStartStopRange {
        $utcDateTime = clone $dateLikeEventZone;
        $utcDateTime->setTimezone(new DateTimeZone('UTC'));
        $moonInfoList = $this->getMoonDatasForDefinedDate(
            $params[self::ARG_REQ_DURATION_MINUTES],
            $utcDateTime,
            $latitude,
            $longitude
        );
        $rangeMin = (int)$params[self::ARG_REQ_DURATION_MINUTES];
        if ((!in_array($params[self::ARG_MOON_STATUS], self::LIST_MOON_STATUS)) ||
            (!array_key_exists($params[self::ARG_MOON_STATUS], $moonInfoList)) ||
            (!array_key_exists('flag' . ucfirst($params[self::ARG_MOON_STATUS]), $moonInfoList)) ||
            ($rangeMin === 0)
        ) {
            throw new TimerException(
                'The moonposition `' . $params[self::ARG_MOON_STATUS] . '` or the range `' . $rangeMin .
                '` is not correctly defined.' . ' Allowed are these variations in `calculateRangeRelToMoonStatus`: `' . implode(
                    '`, `',
                    self::LIST_MOON_STATUS
                ) . '` and the range must not zero. ',
                1607249333
            );
        }
        $timerRange = new TimerStartStopRange();
        $timerRange->failAllActive($dateLikeEventZone);
        if ($moonInfoList[('flag' . ucfirst($params[self::ARG_MOON_STATUS]))] !== false) {
            [$lower, $upper] = $this->defineRangesFromMoonDates(
                $moonInfoList[$params[self::ARG_MOON_STATUS]],
                $params
            );
            $flagActive = true;
            $testTimestamp = $dateLikeEventZone->getTimestamp();
            if ($lower > $testTimestamp) {
                $utcDateTime = clone $dateLikeEventZone;
                $utcDateTime->setTimezone(new DateTimeZone('UTC'));
                $utcDateTime->sub(new DateInterval('P1D')); // the next lower moonrise moon set should on day below
                $moonInfoList = $this->getMoonDatasForDefinedDate(
                    $params[self::ARG_REQ_DURATION_MINUTES],
                    $utcDateTime,
                    $latitude,
                    $longitude
                );
                $flagActive = false;
                if ($moonInfoList[('flag' . ucfirst($params[self::ARG_MOON_STATUS]))] !== false) {
                    [$lower, $upper] = $this->defineRangesFromMoonDates(
                        $moonInfoList[$params[self::ARG_MOON_STATUS]],
                        $params
                    );
                }
            } elseif ($upper < $testTimestamp) {
                $utcDateTime = clone $dateLikeEventZone;
                $utcDateTime->setTimezone(new DateTimeZone('UTC'));
                $utcDateTime->add(new DateInterval('P1D')); // the next lower moonrise moon set should on day below
                $moonInfoList = $this->getMoonDatasForDefinedDate(
                    $params[self::ARG_REQ_DURATION_MINUTES],
                    $utcDateTime,
                    $latitude,
                    $longitude
                );
                $flagActive = false;
                if ($moonInfoList[('flag' . ucfirst($params[self::ARG_MOON_STATUS]))] !== false) {
                    [$lower, $upper] = $this->defineRangesFromMoonDates(
                        $moonInfoList[$params[self::ARG_MOON_STATUS]],
                        $params
                    );
                }
            }

            $lowerDateTime = new DateTime('@' . $lower);
            $lowerDateTime->setTimezone($dateLikeEventZone->getTimezone());
            $upperDateTime = new DateTime('@' . $upper);
            $upperDateTime->setTimezone($dateLikeEventZone->getTimezone());
            $timerRange->setBeginning($lowerDateTime);
            $timerRange->setEnding($upperDateTime);
            $timerRange->setResultExist($flagActive);
        }
        return $timerRange;
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
     * @param int|float $moonInfoList
     * @param array<mixed> $params
     * @return array<mixed>
     */
    protected function defineRangesFromMoonDates($moonInfoList, array $params): array
    {
        $rangeMin = (int)$params[self::ARG_REQ_DURATION_MINUTES] * 60; // *60 => min to seconds
        $relToEvent = (int)$params[self::ARG_REL_MIN_TO_EVENT] * 60; // * 60 => min to seconds
        $moonStatTStamp = (int)$moonInfoList;
        $moonStatTStamp = $moonStatTStamp + $relToEvent;

        if ($rangeMin > 0) {
            $lower = $moonStatTStamp;
            $upper = $moonStatTStamp + $rangeMin;
        } else {
            $upper = $moonStatTStamp;
            $lower = $moonStatTStamp + $rangeMin;
        }
        return [$lower, $upper];
    }

    /**
     * @param int|float $relTime
     * @param DateTime $utcDateTime
     * @param float $latitude
     * @param float $longitude
     * @return array<mixed>
     * @throws Exception
     */
    protected function getMoonDatasForDefinedDate(
        $relTime,
        DateTime $utcDateTime,
        float $latitude,
        float $longitude
    ): array
    {
        $relTime = (int)$relTime;
        if ($relTime > 0) {
            $utcDateTime->sub(new DateInterval('PT' . $relTime . 'M'));
        } else {
            $utcDateTime->add(new DateInterval('PT' . abs($relTime) . 'M'));
        }
        // the object contains the attributes 'flagMoonrise','moonrise','flagMoonset','moonset'
        return MoonRiseSet::calculateMoonTimes(
            (int)$utcDateTime->format('m'),
            (int)$utcDateTime->format('d'),
            (int)$utcDateTime->format('Y'),
            $latitude,
            $longitude
        );
    }
}
