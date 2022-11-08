<?php

namespace Porthd\Timer\Utilities;

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

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\TimerInterface;
use Porthd\Timer\Exception\TimerException;

class ConfigurationUtility
{


    /**
     * Add your timer-classes to the list by using the unique index-name of your timer.
     * Your timer-class must implement interface Porthd\Timer\CustomTimer\TimerInterface
     * used in `ext_localconf.php`
     *
     * example for input:
     * $listOfCustomTimerClasses[YearlyTimer::timerIndexValue()] = DailyTimer::class;
     *
     * @param array $listOfTimerClasses
     * @api
     *
     */
    public static function mergeCustomTimer(array $listOfTimerClasses)
    {
        // add hooks for the datadigger to the configuration
        foreach ($listOfTimerClasses as $key => $className) {
            self::expandNestedArray(
                $GLOBALS,
                [
                    'TYPO3_CONF_VARS',
                    'EXTCONF',
                    TimerConst::EXTENSION_NAME,
                    TimerConst::GLOBALS_SUBKEY_CUSTOMTIMER,
                    $key,
                ],
                $className,
                true
            );
        }
    }

    /**
     * The method use a path to add into a nested array a new leaf or into an empty array, if the path is free.
     * Existing parts won't be overridden. Otherwise it flags a FALSE.
     *
     * similiar solution see the array-fraework (https://github.com/minwork/array)
     * @testing 20191223
     *
     * @param mixed &$check
     * @param array $nestList
     * @param null $leaf
     * @return bool
     */
    public static function expandNestedArray(&$check, array $nestList, $leaf = null, $flagException = false)
    {
        $helper = &$check;  // the & is needed to get a point on a array getting by reference
        $flag = true;
        foreach ($nestList as $nestStage) {
            if (is_array($helper)) {
                if (!isset($helper[$nestStage])) {
                    $helper[$nestStage] = [];
                }
                $helper = &$helper[$nestStage];
            } else {
                $flag = false;
                break;
            }
        }
        if (($leaf !== null) && ($flag)) {
            if ((is_array($helper)) &&
                (empty($helper))
            ) {
                $helper = $leaf;
            } else {
                if ($helper !== $leaf) {
                    $flag = false;
                }
            }  // else entry is olready set - a double call happens for example in TYPO3 9.5 by the extensionmanger after activating an extension
        }
        if ((!$flag) && ($flagException)) {
            throw new TimerException(
                'The path `' . implode('/', $nestList) . '` will override information the assoziative array. ' .
                'The original entry `' . print_r($helper, true) . '` will be overridden by `' .
                print_r($leaf, true) . '`. Perhaps some definitions in `ext_localconf.php`, `ext_tables.php` or in ' .
                'an `[extension]/Configuration/Overrides/*.php` are wrong.',
                1601881723
            );
        }
        return $flag;
    }


    /**
     * @param int $addTimerFlags
     * @param string $selfNameFunc
     */
    public static function addExtLocalconfTimerAdding(
        int $addTimerFlags,
        array $listOfTimerClasses,
        bool $throwException = true
    ): void {
        $count = 0;
        foreach ($listOfTimerClasses as $classString) {
            // check interface, throw exception, if something is wrong
            $interfaces = class_implements($classString);
            if ($interfaces && in_array(TimerInterface::class, $interfaces)) {
                if ($addTimerFlags & (2 ** $count++)) {
                    // mechanism to include additional timer-classes
                    $listOfCustomTimerClasses[$classString::selfName()] = $classString;
                }
            } else {
                if ($throwException === true) {
                    throw new TimerException(
                        'The class `'.$classString. '` did not implements the interface ``.', 1601245879);

                }
            }
        }
        self::mergeCustomTimer(
            $listOfCustomTimerClasses
        );
    }

}

