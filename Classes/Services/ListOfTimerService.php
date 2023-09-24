<?php

declare(strict_types=1);

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
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ListOfTimerService implements SingletonInterface
{
    // Hold the class instance of the various timers
    /** @var array<mixed> */
    private $list = [];


    /**
     * @throws TimerException
     */
    public function __construct()
    {
        $this->generateList();
    }

    /**
     * @param string $selector
     * @return mixed
     */
    public function selfName(string $selector)
    {
        return $this->list[$selector]->selfName();
    }

    /**
     * @return array<mixed>
     */
    public function mergeSelectorItems(): array
    {
        if ((!is_array($this->list))) {
            return DefaultTimer::TIMER_SELECTOR_DEFAULT;
        }
        $result = array_values(
            $this->buildArrayByInterfaceFunctions('getSelectorItem')
        );
        $flag = false;
        foreach ($result as $item) {
            $flag = $flag || (
                    DefaultTimer::TIMER_SELECTOR_DEFAULT[TimerConst::TCA_ITEMS_VALUE] === $item[TimerConst::TCA_ITEMS_VALUE]
                );
        }
        if (!$flag) {
            array_unshift($result, DefaultTimer::TIMER_SELECTOR_DEFAULT);
        }
        return $result;
    }

    /**
     * @param string $selector
     * @param string $activeZoneName
     * @param array<mixed> $params
     * @return string
     */
    public function getTimeZoneOfEvent(string $selector, string $activeZoneName, array $params = []): string
    {
        return $this->list[$selector]->getTimeZoneOfEvent($activeZoneName, $params);
    }

    /**
     * @return array<mixed>
     */
    public function mergeFlexformItems()
    {
        if ((!is_array($this->list))) {
            return DefaultTimer::TIMER_FLEXFORM_ITEM;
        }
        $result = array_values(
            $this->buildArrayByInterfaceFunctions('getFlexformItem')
        );
        return array_merge(DefaultTimer::TIMER_FLEXFORM_ITEM, ...$result);
    }

    /**
     * validate the parameter of the timer and check, if the selector defined an installed timer-object
     *
     * @param string $selector
     * @param array<mixed> $params
     * @return bool
     */
    public function validate(string $selector, $params = []): bool
    {
        if ((!is_array($this->list)) ||
            (!$this->validateSelector($selector))
        ) {
            return false;
        }
        return $this->list[$selector]->validate($params);
    }


    /**
     * @param string $selectorName
     * @return bool
     */
    public function validateSelector(string $selectorName): bool
    {
        return !empty($this->list[$selectorName]);
    }

    /**
     * @param string $selector
     * @param DateTime $checkDate
     * @param array<mixed> $params
     * @return bool
     */
    public function isAllowedInRange(string $selector, DateTime $checkDate, array $params = []): bool
    {
        if ((!is_array($this->list)) ||
            (!array_key_exists($selector, $this->list))
        ) {
            return false;
        }
        return $this->list[$selector]->isAllowedInRange($checkDate, $params);
    }

    /**
     *  check, if the range is active in the range defined by the selector
     *
     * @param string $selector
     * @param DateTime $checkDate contains the time-zone of the current User or the timezone of the CLI-Process
     * @param array<mixed> $params
     * @return bool
     */
    public function isActive($selector, DateTime $checkDate, $params = []): bool
    {
        if (!array_key_exists($selector, $this->list)) {
            return false;
        }
        $activeZoneName = $checkDate->getTimezone()->getName();
        $list = TcaUtility::listBaseZoneItems();
        if (!in_array($activeZoneName, $list)) {
            throw new TimerException(
                'There is an unknown/unallowed timeZone-definition `' . $activeZoneName .
                '`. Check the spelling (upper&lower cases). Check the parameters of the timer `' . $selector . '`.',
                123456797
            );
        }
        $eventTimeZoneName = $this->getTimeZoneOfEvent($selector, $activeZoneName, $params);
        $dateLikeEventZone = new DateTime('@' . $checkDate->getTimestamp(), new DateTimeZone('UTC'));
        $dateLikeEventZone->setTimezone(new DateTimeZone($eventTimeZoneName));
        return $this->list[$selector]->isActive($dateLikeEventZone, $params);
    }

    /**
     * @param string $selector
     * @param DateTime $checkDate
     * @param array<mixed> $params
     * @return TimerStartStopRange
     * @throws TimerException
     */
    public function getLastIsActiveRangeResult(
        string $selector,
        DateTime $checkDate,
        array $params = []
    ): TimerStartStopRange {
        if (!array_key_exists($selector, $this->list)) {
            $failAll = new TimerStartStopRange();
            $failAll->failAllActive($checkDate);
            return $failAll;
        }
        // @todo construct the fullrange for the lastactive result
        $activeZoneName = $checkDate->getTimezone()->getName();
        $list = TcaUtility::listBaseZoneItems();
        if (!in_array($activeZoneName, $list)) {
            throw new TimerException(
                'There is an unknown/unallowed timeZone-definition `' . $activeZoneName .
                '` for the method `' . __FUNCTION__ . '`. Check the spelling (upper&lower cases). Check the parameters of the timer `' . $selector . '`.',
                123456797
            );
        }
        $eventTimeZoneName = $this->getTimeZoneOfEvent($selector, $activeZoneName, $params);
        $dateLikeEventZone = new DateTime('@' . $checkDate->getTimestamp(), new DateTimeZone('UTC'));
        $dateLikeEventZone->setTimezone(new DateTimeZone($eventTimeZoneName));
        return $this->list[$selector]->getLastIsActiveRangeResult($dateLikeEventZone, $params);
    }

    /**
     * validate the parameter of the timer
     *
     * @param string $selector
     * @param DateTime $eventTimeZone
     * @param array<mixed> $params
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
     * @param array<mixed> $params
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
     * @param array<mixed> $params
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
        if (!array_key_exists($selector, $this->list)) {
            /** @var TimerStartStopRange $timerStartStop */
            $timerStartStop = new TimerStartStopRange();
            $timerStartStop->failAllActive($eventTimeZone);
            return $timerStartStop;
        }
        return clone $this->list[$selector]->$rangeAction($eventTimeZone, $params);
    }

    /**
     * @throws TimerException
     */
    private function generateList(): void
    {
        if (
            (!is_array($this->list)) ||
            (empty($this->list))
        ) {
            $this->list = [];
            if (empty(TcaUtility::$timerConfig)) {
                TcaUtility::$timerConfig = GeneralUtility::makeInstance(
                    ExtensionConfiguration::class
                )->get(TimerConst::EXTENSION_NAME);
            }
            $configTimers = TcaUtility::$timerConfig;

            // Call post-processing function for constructor:
            if ((!empty($configTimers[TimerConst::GLOBALS_SUBKEY_CUSTOMTIMER])) &&
                (is_array($configTimers[TimerConst::GLOBALS_SUBKEY_CUSTOMTIMER]))
            ) {
                foreach ($configTimers[TimerConst::GLOBALS_SUBKEY_CUSTOMTIMER] as $className) {
                    $classInterface = class_implements($className);
                    if (in_array(TimerInterface::class, $classInterface)) {
                        /** @var TimerInterface $classObject */
                        $classObject = GeneralUtility::makeInstance($className);
                        /** @phpstan-ignore-line */
                        $this->list[$classObject::selfName()] = $classObject;
                    } else {
                        throw new TimerException(
                            'The class did not implement the TimerInterface for the included timeractions. Something in the configuration went wrong. Check you ext_localconf.php.',
                            1668083631
                        );
                    }
                }
                if ((!empty($configTimers[TimerConst::GLOBALS_SUBKEY_EXCLUDE])) &&
                    (is_array($configTimers[TimerConst::GLOBALS_SUBKEY_EXCLUDE]))
                ) {
                    foreach ($configTimers[TimerConst::GLOBALS_SUBKEY_EXCLUDE] as $className) {
                        $classInterface = class_implements($className);
                        if (in_array(TimerInterface::class, $classInterface)) {
                            /** @var TimerInterface $classObject */
                            $classObject = GeneralUtility::makeInstance($className);
                            /** @phpstan-ignore-line */
                            if (array_key_exists($classObject::selfName(), $this->list)) {
                                unset($this->list[$classObject::selfName()]);
                            }
                        } else {
                            throw new TimerException(
                                'The class did not implement the TimerInterface for the excluded timer-actions. Something in the configuration went wrong. Check you ext_localconf.php.',
                                1668082741
                            );
                        }
                    }
                }
            } else {
                throw new TimerException(
                    'There is NO timer-class defined. Something in the configuration went wrong. Check you ext_localconf.php.',
                    1668081531
                );
            }
        }
    }

    /**
     * @param string $interfaceMethod
     * @return array<mixed>
     */
    protected function buildArrayByInterfaceFunctions(
        string $interfaceMethod
    ): array {
        $arrayList = [];
        // Add default-timer
        /** @var TimerInterface $instance */
        $instance = $this->list[DefaultTimer::TIMER_NAME];
        $arrayList[DefaultTimer::TIMER_NAME] = $instance->$interfaceMethod();
        // add the needed infos from all other timers
        /**
         * @var string $key
         * @var TimerInterface $instance
         */
        foreach ($this->list as $key => $instance) {
            if ($key !== DefaultTimer::TIMER_NAME) {
                $arrayList[$key] = $instance->$interfaceMethod();
            }
        }
        return $arrayList;
    }
}
