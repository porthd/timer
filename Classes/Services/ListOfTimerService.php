<?php

namespace Porthd\Timer\Services;

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
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\DefaultTimer;
use Porthd\Timer\CustomTimer\TimerInterface;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class ListOfTimerService implements SingletonInterface
{

    // Hold the class instance.F
    private $list = null;


    /**
     * @throws TimerException
     */
    public function __construct()
    {
        $this->generateList();
    }

    /**
     * @param string|array $orderList
     * @return array|string[]
     */
    public function mergeSelectorItems($orderList = DefaultTimer::TIMER_NAME)
    {
        if ( (!is_array($this->list))) {
            return DefaultTimer::TIMER_SELECTOR_DEFAULT;
        }
        if(is_array($orderList)){
            $orderListRaw = array_shift($orderList);
            $arrayList = $this->buildArrayByInterfaceFunctions(
                'getSelectorItem',
                $orderListRaw);
        } else {
            $arrayList = $this->buildArrayByInterfaceFunctions(
                'getSelectorItem',
                $orderList);
        }
        $result = array_values($arrayList);
        array_unshift($result, DefaultTimer::TIMER_SELECTOR_DEFAULT);
        return $result;
    }

    /**
     * @param string $selector
     * @param string $activeZoneName
     * @param array $params
     * @return string
     */
    public function getTimeZoneOfEvent(string $selector, string $activeZoneName, array $params = [] ): string
    {
        return $this->list[$selector]->getTimeZoneOfEvent($activeZoneName, $params);
    }

    /**
     * @param string|array $orderList
     * @return array|string[]
     */
    public function mergeFlexformItems($orderList = DefaultTimer::TIMER_NAME)
    {
        if ((!is_array($this->list))) {
            return DefaultTimer::TIMER_FLEXFORM_ITEM;
        }
        if( is_array($orderList)) {
            $orderListRaw = array_unshift($orderList);
            $result = array_values($this->buildArrayByInterfaceFunctions(
                'getFlexformItem',
                $orderListRaw));
        } else {

            $result = array_values($this->buildArrayByInterfaceFunctions(
                'getFlexformItem',
                $orderList));
        }
        return array_merge(DefaultTimer::TIMER_FLEXFORM_ITEM, ...$result);
    }

    /**
     * validate the parameter of the timer
     *
     * @param string $selector
     * @param array $params
     * @return bool
     */
    public function validate(string $selector, $params = [])
    {
        if ((!is_array($this->list)) ||
            (!isset($this->list[$selector]))
        ) {
            return false;
        }
        return $this->list[$selector]->validate($params);
    }

    /**
     * @param $selector
     * @param DateTime $checkDate
     * @param array $params
     * @return false
     */
    public function isAllowedInRange($selector, DateTime $checkDate, $params = [])
    {
        if ((!is_array($this->list)) ||
            (!isset($this->list[$selector]))
        ) {
            return false;
        }
        return $this->list[$selector]->isAllowedInRange($checkDate, $params);

    }

    /**
     * validate the parameter of the timer
     *
     * @param string $selector
     * @param DateTime $checkDate contains the time-zone of the current User or the timezone of the CLI-Process
     * @param array $params
     * @return bool
     */
    public function isActive($selector, DateTime $checkDate, $params = []): bool
    {
        if ((!is_array($this->list)) ||
            (!isset($this->list[$selector]))
        ) {
            return false;
        }
        $activeZoneName= $checkDate->getTimezone()->getName();
        $list = TcaUtility::listBaseZoneItems();
        if (!in_array($activeZoneName, $list))  {
            throw new TimerException(
                'There is an unknown/unallowed timeZone-definition `' . $activeZoneName .
                '`. Check the spelling (upper&lower cases). Check the parameters of the timer `'.$selector.'`.' ,
                123456797
            );
        }
        $eventTimeZoneName = $this->getTimeZoneOfEvent($selector,  $activeZoneName,$params);
        $dateLikeEventZone = new DateTime('@'.$checkDate->getTimestamp(), new DateTimeZone( 'UTC'));
        $dateLikeEventZone->setTimezone( new DateTimeZone( $eventTimeZoneName));
        return $this->list[$selector]->isActive($dateLikeEventZone, $params);
    }

    /**
     * tested:
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult($selector, DateTime $checkDate, $params = []): TimerStartStopRange
    {
        if ((!is_array($this->list)) ||
            (!isset($this->list[$selector]))
        ) {
            $failAll = GeneralUtility::makeInstance(TimerStartStopRange::class);
            $failAll->failAllActive($checkDate);
            return $failAll;
        }
        $activeZoneName= $checkDate->getTimezone()->getName();
        $list = TcaUtility::listBaseZoneItems();
        if (!in_array($activeZoneName, $list))  {
            throw new TimerException(
                'There is an unknown/unallowed timeZone-definition `' . $activeZoneName .
                '` for the method `'.__FUNCTION__.'`. Check the spelling (upper&lower cases). Check the parameters of the timer `'.$selector.'`.' ,
                123456797
            );
        }
        $eventTimeZoneName = $this->getTimeZoneOfEvent($selector,  $activeZoneName,$params);
        $dateLikeEventZone = new DateTime('@'.$checkDate->getTimestamp(), new DateTimeZone( 'UTC'));
        $dateLikeEventZone->setTimezone( new DateTimeZone( $eventTimeZoneName));
        return $this->list[$selector]->getLastIsActiveRangeResult($dateLikeEventZone, $params);
    }

    /**
     * validate the parameter of the timer
     *
     * @param string $selector
     * @param DateTime $eventTimeZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function nextActive($selector, DateTime $eventTimeZone, $params = []): TimerStartStopRange
    {
        return $this->rangeActive($selector, 'nextActive', $eventTimeZone, $params);
    }

    /**
     * validate the parameter of the timer
     *
     * @param string $selector
     * @param DateTime $eventTimeZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function prevActive($selector, DateTime $eventTimeZone, $params = []): TimerStartStopRange
    {
        return $this->rangeActive($selector, 'prevActive', $eventTimeZone, $params);
    }

    /**
     * validate the parameter of the timer
     *
     * @param string $selector
     * @param string $rangeAction take one of the values: 'prevRange' or'nextRange'
     * @param DateTime $eventTimeZone
     * @param array $params
     * @return TimerStartStopRange
     */
    public function rangeActive($selector, $rangeAction, DateTime $eventTimeZone, $params = []): TimerStartStopRange
    {
        if (!in_array($eventTimeZone->getTimezone()->getName(), TcaUtility::listBaseZoneItems())) {
            throw new TimerException(
                'There is an unknown timeZone-definition `' . $eventTimeZone->getTimezone()->getName() .
                '` for `' . $rangeAction . '`. Check the spelling, Upper&Lower cases. Check if it is allowed.',
                123457867
            );
        }
        if ((!is_array($this->list)) ||
            (!isset($this->list[$selector]))
        ) {
            /** @var TimerStartStopRange $timerStartStop */
            $timerStartStop = GeneralUtility::makeInstance(TimerStartStopRange::class);
            $timerStartStop->setZero();
            return $timerStartStop;
        }
        return clone $this->list[$selector]->$rangeAction($eventTimeZone, $params);
    }

    /**
     * @throws TimerException
     */
    private function generateList()
    {
        if (!is_array($this->list)) {
            $this->list = [];
            // Call post-processing function for constructor:
            if ((!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][TimerConst::GLOBALS_SUBKEY_CUSTOMTIMER])) &&
                (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][TimerConst::GLOBALS_SUBKEY_CUSTOMTIMER])) &&
                (count($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][TimerConst::GLOBALS_SUBKEY_CUSTOMTIMER]) > 0)
            ) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][TimerConst::GLOBALS_SUBKEY_CUSTOMTIMER] as $className) {
                    $classInterface = class_implements($className);
                    if (in_array(TimerInterface::class, $classInterface)) {
                        /** @var TimerInterface $classObject */
                        $classObject = GeneralUtility::makeInstance($className);
                        $this->list[$classObject::selfName()] = $classObject;
                    }
                }
                if ((!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][TimerConst::GLOBALS_SUBKEY_EXCLUDE])) &&
                    (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][TimerConst::GLOBALS_SUBKEY_EXCLUDE])) &&
                    (count($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][TimerConst::GLOBALS_SUBKEY_EXCLUDE]) > 0)
                ) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TimerConst::EXTENSION_NAME][TimerConst::GLOBALS_SUBKEY_EXCLUDE] as $className) {
                        if (method_exists($className, 'selfName')) {
                            /** @var TimerInterface $classObject */
                            $classObject = GeneralUtility::makeInstance($className);
                            if (isset($this->list[$classObject::selfName()])) {
                                unset($this->list[$classObject::selfName()]);
                            }
                        }
                    }
                }
            } else {
                throw new TimerException(
                    'There is NO timer-class defined. Something in the configuration went wrong. Check you ext_localconf.php.',
                    123456897
                );

            }
        }
    }

    /**
     * @param string $interfaceMethod
     * @param string|array $orderList
     * @return array
     */
    protected function buildArrayByInterfaceFunctions(string $interfaceMethod, $orderList = TimerConst::EXTENSION_NAME): array
    {
        if (is_array($orderList)) {
            $orderListRaw = array_shift($orderList);
            $preSorted = array_filter(
                array_map(
                    'trim',
                    explode(',', $orderListRaw)
                )
            );

        } else {
            $preSorted = array_filter(
                array_map(
                    'trim',
                    explode(',', $orderList)
                )
            );

        }
        $arrayList = [];
        if (count($preSorted) > 0) {
            foreach ($preSorted as $timerIdent) {
                if (isset($this->list[$timerIdent])) {
                    /** @var TimerInterface $instance */
                    $instance = $this->list[$timerIdent];
                    $arrayList[$timerIdent] = $instance->$interfaceMethod();
                }
            }
        }
        /**
         * @var string $key
         * @var TimerInterface $instance
         */
        foreach ($this->list as $key => $instance) {
            if (!in_array($key, $preSorted)) {
                $arrayList[$key] = $instance->$interfaceMethod();
            }
        }
        return $arrayList;
    }
}
