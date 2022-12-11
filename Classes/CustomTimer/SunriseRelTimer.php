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
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\GeneralTimerUtility;

class SunriseRelTimer implements TimerInterface
{

    use GeneralTimerTrait;

    public const TIMER_NAME = 'txTimerSunriseRel';
    protected const ARG_DURATION_NATURAL = 'durationNatural';
    protected const ARG_SUN_POSITION = 'sunPosition';
    protected const ITEM_DURATION_NATURAL_DEFAULT = 'defined';
    protected const LIST_SUN_POSITION = [
        'sunrise',
        'sunset',
        'transit',
        'civil_twilight_begin',
        'civil_twilight_end',
        'nautical_twilight_begin',
        'nautical_twilight_end',
        'astronomical_twilight_begin',
        'astronomical_twilight_end',
    ];
    protected const LIST_DURATION_NATURAL_ADD = [self::ITEM_DURATION_NATURAL_DEFAULT,];
    protected const ARG_REQ_DURATION_MINUTES = 'durationMinutes';
    protected const ARG_REQ_DURMIN_MIN = -1340;
    protected const ARG_REQ_DURMIN_MAX = 1340;
    protected const ARG_LATITUDE = 'latitude';
    protected const DEFAULT_LATITUDE = 47.599329;// see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const ARG_LATITUDE_MAX = 90;// see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const ARG_LATITUDE_MIN = -90;// see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const ARG_LONGITUDE = 'longitude';
    protected const DEFAULT_LONGITUDE = 3.534787; // see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const ARG_LONGITUDE_MAX = 180; // see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const ARG_LONGITUDE_MIN = -180; // see geolocation of anus in the wolrd https://www.gps-latitude-longitude.com/gps-coordinates-of-anus visited 2020-12-04
    protected const ARG_REL_TO_TIMEREVENT = 'relMinToSelectedTimerEvent';
    protected const ARG_REQ_RELTOEVENT_MIN = -1340;
    protected const ARG_REQ_RELTOEVENT_MAX = 1340;


    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/SunriseRelTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,

        self::ARG_SUN_POSITION,
        self::ARG_REQ_DURATION_MINUTES,
        self::ARG_LATITUDE,
        self::ARG_LONGITUDE,
        self::ARG_DURATION_NATURAL,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_REL_TO_TIMEREVENT,
        self::ARG_USE_ACTIVE_TIMEZONE,
    ];
    const DAY_IN_SECONDS = 86400;
    const MAXIMUM_DAYS_FOR_CALCULATE = 366;

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
     *
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
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerSunriseRel.select.name',
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
        return ($params[self::ARG_ULTIMATE_RANGE_BEGINN] <= $dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME)) &&
            ($dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME) <= $params[self::ARG_ULTIMATE_RANGE_END]);
    }

    /**
     * tested general 20221115
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
        $flag = $flag && $this->validateFlagZone($params);
        $flag = $flag && $this->validateUltimate($params);
        $countRequired = $this->validateArguments($params);
        $flag = $flag && $this->validateSunPosition($params);
        $flag = $flag && $this->validateDurationMinutes($params);
        $flag = $flag && $this->validateDurationNatural($params);
        $flag = $flag && $this->validateRelToEvent($params);
        $flag = $flag && ($countRequired === count(self::ARG_REQ_LIST));
        $flag = $flag && $this->validateLatitude($params);
        $flag = $flag && $this->validateLongitude($params);
        $countOptions = $this->validateOptional($params);
        return $flag && ($countOptions >= 0) &&
            ($countOptions <= count(self::ARG_OPT_LIST));
    }


    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateSunPosition(array $params = []): bool
    {
        $string = ((isset($params[self::ARG_SUN_POSITION])) ?
            $params[self::ARG_SUN_POSITION] :
            ''
        );
        return ((!empty($string)) &&
            (is_string($string)) &&
            (in_array($string, self::LIST_SUN_POSITION))
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
            ($number >= self::ARG_REQ_DURMIN_MIN) && ($number <= self::ARG_REQ_DURMIN_MAX);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateDurationNatural(array $params = []): bool
    {
        $value = (isset($params[self::ARG_DURATION_NATURAL]) ?
            $params[self::ARG_DURATION_NATURAL] :
            'fail'
        );
        return in_array((string)$value, array_merge(self::LIST_SUN_POSITION, self::LIST_DURATION_NATURAL_ADD));
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateRelToEvent(array $params = []): bool
    {
        $value = (isset($params[self::ARG_REL_TO_TIMEREVENT]) ?
            $params[self::ARG_REL_TO_TIMEREVENT] :
            0
        );
        $number = (int)$value;
        return is_int($number) && (($number - $value) === 0) &&
            ($number >= self::ARG_REQ_RELTOEVENT_MIN) && ($number <= self::ARG_REQ_RELTOEVENT_MAX);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateLatitude(array $params = []): bool
    {
        $number = (float)($params[self::ARG_LATITUDE] ?: self::DEFAULT_LATITUDE);
        return is_float($number) && ($number >= self::ARG_LATITUDE_MIN) && ($number <= self::ARG_LATITUDE_MAX);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateLongitude(array $params = []): bool
    {
        $number = (float)($params[self::ARG_LONGITUDE] ?: self::DEFAULT_LATITUDE);
        return is_float($number) && ($number >= self::ARG_LONGITUDE_MIN) && ($number <= self::ARG_LONGITUDE_MAX);
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
    public function validateOptional(array $params = []): int
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
     * tested 20220910
     *
     * check, if the timer ist for this time active
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
            return $result->getResultExist();
        }

        $tStamp = $dateLikeEventZone->getTimestamp();
        [$latitude, $longitude] = $this->defineLongitudeLatitudeByParams($params, $dateLikeEventZone->getOffset());
        if (($sunInfoList = date_sun_info($tStamp, $latitude, $longitude)) === false) {
            return false;
        }

        $sunPosTStamp = $this->getSunstatusOrThrowExcept($params, $sunInfoList);

        if ($sunPosTStamp === false) {
            return false;
        }
        [$lowerLimit, $upperLimit] = $this->getUpperLowerRangeRelToSunPos(
            $params,
            $sunPosTStamp,
            $dateLikeEventZone,
            $sunInfoList
        );
        if ($dateLikeEventZone < $lowerLimit) {
            // test the previous day = subtract 86400 seconds
            $tStamp -= self::DAY_IN_SECONDS;
            $flagDateInLimits = false;
        } elseif ($upperLimit < $dateLikeEventZone) {
            // test the next day = add 86400 seconds
            $tStamp += self::DAY_IN_SECONDS;
            $flagDateInLimits = false;
        } else {
            $flagDateInLimits = true;

        }

        if ($flagDateInLimits) {
            $this->setIsActiveResult($lowerLimit, $upperLimit, $flagDateInLimits, $dateLikeEventZone, $params);
            $flag = $this->lastIsActiveResult->getResultExist();
        } else {
            $flag = $this->secondTestForActiveInclusion($tStamp, $latitude, $longitude, $params, $dateLikeEventZone);
        }
        return $flag;
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
     * tested
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $flagNext = false;
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();

        $tStamp = $dateLikeEventZone->getTimestamp();
        if (($params[self::ARG_REL_TO_TIMEREVENT] ?? 0)>0) {
            $tStamp -= self::DAY_IN_SECONDS;
        }
        [$latitude, $longitude] = $this->defineLongitudeLatitudeByParams($params, $dateLikeEventZone->getOffset());
        if (($sunInfoList = date_sun_info($tStamp, $latitude, $longitude)) === false) {
            $noDate = clone $dateLikeEventZone;
            $noDate->sub(new DateInterval('P1D'));
            $result->failOnlyPrevActive($noDate);
            $flagNext = true;
        }
        if (!$flagNext) {
            $sunPosTStamp = $this->getSunstatusOrThrowExcept($params, $sunInfoList);
            if ($sunPosTStamp !== false) {

                [$lowerLimit, $upperLimit] = $this->getUpperLowerRangeRelToSunPos(
                    $params,
                    $sunPosTStamp,
                    $dateLikeEventZone,
                    $sunInfoList
                );

                if ($dateLikeEventZone < $lowerLimit) {
                    $result->setBeginning($lowerLimit);
                    $result->setEnding($upperLimit);
                    $result->setResultExist(true);
                    $flagNext = true;
                }
            }
            $countAgainstInfinity = 0;
            while (($flagNext === false) &&
                ($countAgainstInfinity <= self::MAXIMUM_DAYS_FOR_CALCULATE) &&
                (
                    ($lowerLimit <= $dateLikeEventZone) ||
                    ($sunPosTStamp === false)
                )
            ) {
                $tStamp += self::DAY_IN_SECONDS;
                $sunInfoList = date_sun_info($tStamp, $latitude, $longitude);
                $sunPosTStamp = $sunInfoList[$params[self::ARG_SUN_POSITION]];
                if ($sunPosTStamp !== false) {
                    [$lowerLimit, $upperLimit] = $this->getUpperLowerRangeRelToSunPos(
                        $params,
                        $sunPosTStamp,
                        $dateLikeEventZone,
                        $sunInfoList
                    );
                    if ($dateLikeEventZone < $lowerLimit) {
                        $result->setBeginning($lowerLimit);
                        $result->setEnding($upperLimit);
                        $result->setResultExist(true);
                        $flagNext = true;
                        break;
                    }
                }
                $countAgainstInfinity++;
            }
            if ($flagNext === false) {
                $noDate = clone $dateLikeEventZone;
                $noDate->sub(new DateInterval('P1D'));
                $result->failOnlyPrevActive($noDate);
            }
        }

        return $this->validateUltimateRangeForNextRange($result, $params, $dateLikeEventZone);
    }

    /**
     * tested
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $flagPrev = false;
        /** @var TimerStartStopRange $result */
        $result = new TimerStartStopRange();
        $tStamp = $dateLikeEventZone->getTimestamp();
//        $tStamp -= self::DAY_IN_SECONDS;
        [$latitude, $longitude] = $this->defineLongitudeLatitudeByParams($params, $dateLikeEventZone->getOffset());
        if (($sunInfoList = date_sun_info($tStamp, $latitude, $longitude)) === false) {
            $noDate = clone $dateLikeEventZone;
            $noDate->add(new DateInterval('P1D'));
            $result->failOnlyNextActive($noDate);
            $flagPrev = true;
        }

        if ($flagPrev === false) {
            $sunPosTStamp = $this->getSunstatusOrThrowExcept($params, $sunInfoList);
            if ($sunPosTStamp !== false) {

                [$lowerLimit, $upperLimit] = $this->getUpperLowerRangeRelToSunPos(
                    $params,
                    $sunPosTStamp,
                    $dateLikeEventZone,
                    $sunInfoList
                );

                if ($upperLimit < $dateLikeEventZone) {
                    $result->setBeginning($lowerLimit);
                    $result->setEnding($upperLimit);
                    $result->setResultExist(true);
                    $flagPrev = true;
                }
            }
            $countAgainstInfinity = 0;
            while (($flagPrev === false) &&
                ($countAgainstInfinity <= self::MAXIMUM_DAYS_FOR_CALCULATE) &&
                (
                    ($sunPosTStamp === false) ||
                    ($dateLikeEventZone <= $upperLimit)
                )
            ) {
                $tStamp -= self::DAY_IN_SECONDS;
                $sunInfoList = date_sun_info($tStamp, $latitude, $longitude);
                $sunPosTStamp = $sunInfoList[$params[self::ARG_SUN_POSITION]];
                if ($sunPosTStamp !== false) {
                    [$lowerLimit, $upperLimit] = $this->getUpperLowerRangeRelToSunPos(
                        $params,
                        $sunPosTStamp,
                        $dateLikeEventZone,
                        $sunInfoList
                    );
                    if ($upperLimit < $dateLikeEventZone) {
                        $result->setBeginning($lowerLimit);
                        $result->setEnding($upperLimit);
                        $result->setResultExist(true);
                        $flagPrev = true;
                        break;
                    }
                }
                $countAgainstInfinity++;
            }
            if ($flagPrev === false) {
                $noDate = clone $dateLikeEventZone;
                $noDate->add(new DateInterval('P1D'));
                $result->failOnlyNextActive($noDate);
            }
        }

        return $this->validateUltimateRangeForPrevRange($result, $params, $dateLikeEventZone);
    }

    /**
     * @param $params
     * @param $sunPosTStamp
     * @param DateTime $eventZone
     * @param $sunInfoList
     * @return DateTime[]
     * @throws Exception
     */
    protected function getUpperLowerRangeRelToSunPos(
        $params,
        $sunPosTStamp,
        DateTime $dateTimeEventZone,
        $sunInfoList
    ): array {
        $relMin = $params[self::ARG_REL_TO_TIMEREVENT] ?? 0;
        $durationMin = $params[self::ARG_REQ_DURATION_MINUTES] ?? 0;
        $durationNatural = $params[self::ARG_DURATION_NATURAL] ?? self::ITEM_DURATION_NATURAL_DEFAULT;
        if ($durationNatural === self::ITEM_DURATION_NATURAL_DEFAULT) {
            [$lowerLimit, $upperLimit] = $this->getFixedUpperLowerRangeRelToSunPos($durationMin, $sunPosTStamp,
                $dateTimeEventZone->getTimezone(), $relMin);
        } else { // natural duration must be set self::ITEM_DURATION_NATURAL_DEFAULT is NO
            [$lowerLimit, $upperLimit] = $this->getNaturalUpperLowerRangeRelToSunPos($params, $sunInfoList,
                $sunPosTStamp, $dateTimeEventZone, $relMin);
        }
        $lowerLimit->setTime($lowerLimit->format('G'), $lowerLimit->format('i'), 00);
        $upperLimit->setTime($upperLimit->format('G'), $upperLimit->format('i'), 00);
        $upperLimit->add(new DateInterval('PT1M'));
        return [$lowerLimit, $upperLimit];
    }

    /**
     * @param int $tStamp
     * @param float $latitude
     * @param int $longitude
     * @param $durationMin
     * @param DateTime $dateLikeEventZone
     * @return bool
     * @throws Exception
     */
    protected function secondTestForActiveInclusion(
        int $tStamp,
        float $latitude,
        int $longitude,
        array $params,
        DateTime $dateLikeEventZone
    ): bool {
        $sunInfoList = date_sun_info($tStamp, $latitude, $longitude);
        $sunPosTStamp = $sunInfoList[$params[self::ARG_SUN_POSITION]];
        if ($sunPosTStamp === false) {
            return false;
        }
        [$lowerLimit, $upperLimit] = $this->getUpperLowerRangeRelToSunPos(
            $params,
            $sunPosTStamp,
            $dateLikeEventZone,
            $sunInfoList
        );
        $flag = (($lowerLimit <= $dateLikeEventZone) &&
            ($dateLikeEventZone <= $upperLimit));
        $this->setIsActiveResult($lowerLimit, $upperLimit, $flag, $dateLikeEventZone, $params);

        return $flag;
    }

    /**
     * @param array $params
     * @param int $gap
     * @return array
     */
    protected function defineLongitudeLatitudeByParams(array $params, int $gap): array
    {
        $latitude = (((isset($params[self::ARG_LATITUDE])) && ($params[self::ARG_LATITUDE] >= -90) && ($params[self::ARG_LATITUDE] <= 90)) ?
            ((float)$params[self::ARG_LATITUDE]) :
            (self::DEFAULT_LATITUDE)
        );
        $longitude = self::DEFAULT_LONGITUDE;
        if ((isset($params[self::ARG_LONGITUDE])) &&
            ($params[self::ARG_LONGITUDE] >= -180) &&
            ($params[self::ARG_LONGITUDE] <= 180)
        ) {
            $longitude = $params[self::ARG_LONGITUDE];
        } else {
            if ((isset($params[self::ARG_USE_ACTIVE_TIMEZONE])) &&
                (!empty($params[self::ARG_USE_ACTIVE_TIMEZONE]))
            ) {
                // the timezone Pacific/Auckland  has an offset of 46800 s relativ to UTC

                $longitude = $gap / 240;  // =360/86400
            } else {
                $longitude = self::DEFAULT_LONGITUDE;
            }
        }
        return [$latitude, $longitude];
    }

    /**
     * @param array $params
     * @param bool|array $sunInfoList
     * @return mixed
     * @throws TimerException
     */
    protected function getSunstatusOrThrowExcept(array $params, $sunInfoList)
    {
        if (!in_array(
            $params[self::ARG_SUN_POSITION],
            self::LIST_SUN_POSITION
        )
        ) {
            throw new TimerException(
                'The status of `sunposition` with the value `' . $params[self::ARG_SUN_POSITION] . '` is not ' .
                'correctly defined. ' .
                'Allowed are these variations: `' . implode('`, `', self::LIST_SUN_POSITION) . '`. ',
                1607249332
            );
        }
        return $sunInfoList[$params[self::ARG_SUN_POSITION]];
    }

    /**
     * @param array $params
     * @param bool|array $sunInfoList
     * @return mixed
     * @throws TimerException
     */
    protected function getDurationstatusOrThrowExcept(array $params, $sunInfoList)
    {
        if (!in_array(
            $params[self::ARG_DURATION_NATURAL],
            self::LIST_SUN_POSITION
        )
        ) {
            throw new TimerException(
                'The status of `sunposition` with the value `' . $params[self::ARG_DURATION_NATURAL] . '` is not ' .
                'correctly defined. ' .
                'Allowed are these variations: `' . implode('`, `', self::LIST_SUN_POSITION) . '`. ',
                1607249332
            );
        }
        return $sunInfoList[$params[self::ARG_DURATION_NATURAL]];
    }

    /**
     * @param $durationMin
     * @param int $sunPosTStamp
     * @param DateTimeZone $eventZone
     * @param $relMin
     * @return DateTime[]
     * @throws Exception
     */
    protected function getFixedUpperLowerRangeRelToSunPos(
        $durationMin,
        int $sunPosTStamp,
        DateTimeZone $eventZone,
        $relMin
    ): array {
        if (($durationMinutes = (int)$durationMin) > 0) {
            $lowerLimit = new DateTime('@' . $sunPosTStamp);
            $lowerLimit->setTimezone($eventZone);
            if (($relToEventInMinutes = (int)$relMin) > 0) {
                $lowerLimit->add(new DateInterval('PT' . $relToEventInMinutes . 'M'));
            } else {
                $lowerLimit->sub(new DateInterval('PT' . abs($relToEventInMinutes) . 'M'));
            }
            $upperLimit = clone $lowerLimit;
            $upperLimit->add(new DateInterval('PT' . $durationMinutes . 'M'));
        } else {
             $upperLimit = new DateTime('@' . $sunPosTStamp);
            $upperLimit->setTimezone($eventZone);
            if (($relToEventInMinutes = (int)$relMin) > 0) {
                $upperLimit->add(new DateInterval('PT' . $relToEventInMinutes . 'M'));
            } else {
                $upperLimit->sub(new DateInterval('PT' . abs($relToEventInMinutes) . 'M'));
            }
            $lowerLimit = clone $upperLimit;
            $lowerLimit->sub(new DateInterval('PT' . abs($durationMinutes) . 'M'));
        }
        return [$lowerLimit, $upperLimit];
    }

    /**
     * @param $params
     * @param $sunInfoList
     * @param $sunPosTStamp
     * @param DateTime $dateTimeEventZone
     * @param int $relMin
     * @return DateTime[]
     * @throws TimerException
     */
    protected function getNaturalUpperLowerRangeRelToSunPos(
        $params,
        $sunInfoList,
        $sunPosTStamp,
        DateTime $dateTimeEventZone,
        int $relMin
    ): array {
        $nextSunStatusStamp = $this->getDurationstatusOrThrowExcept($params, $sunInfoList);
        if ($nextSunStatusStamp <= $sunPosTStamp) {

            [$latitude, $longitude] = $this->defineLongitudeLatitudeByParams($params, $dateTimeEventZone->getOffset());
            if (($nextSunInfoList = date_sun_info(($sunPosTStamp + self::DAY_IN_SECONDS), $latitude, $longitude)) === false) {
                $nextSunStatusStamp = ($sunPosTStamp + self::DAY_IN_SECONDS);
            } else {
                $nextSunStatusStamp = $this->getDurationstatusOrThrowExcept($params, $nextSunInfoList);
            }
            if (($nextSunStatusStamp === true) || ($nextSunStatusStamp === false)) {
                $nextSunStatusStamp = ($sunPosTStamp + self::DAY_IN_SECONDS);
            }
        }
        // now $nextSunStatusStamp > $sunPosTStamp
        $lowerLimit = new DateTime('@' . (int)$sunPosTStamp);
        $lowerLimit->setTimezone($dateTimeEventZone->getTimezone());
        if (($relToEventInMinutes = $relMin) > 0) {
            $lowerLimit->add(new DateInterval('PT' . $relToEventInMinutes . 'M'));
        } else {
            $lowerLimit->sub(new DateInterval('PT' . abs($relToEventInMinutes) . 'M'));
        }
        $upperLimit = new DateTime('@' . (int)$nextSunStatusStamp);
        $upperLimit->setTimezone($dateTimeEventZone->getTimezone());
        if (($relToEventInMinutes = $relMin) > 0) {
            $upperLimit->add(new DateInterval('PT' . $relToEventInMinutes . 'M'));
        } else {
            $upperLimit->sub(new DateInterval('PT' . abs($relToEventInMinutes) . 'M'));
        }
        return [$lowerLimit, $upperLimit];
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
        $this->lastIsActiveResult->setResultExist($flag && (($dateStart <= $dateLikeEventZone) && ($dateLikeEventZone <= $dateStop)));
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