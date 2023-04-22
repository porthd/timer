<?php

namespace Porthd\Timer\Hooks\Backend;

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

use DateTime;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\DefaultTimer;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\DateTimeUtility;

/**
 * see https://github.com/georgringer/news/issues/268 visited 20201006
 *
 * @package Porthd\Timer\Hooks\Backend\FlexformManipulationHook
 */
class FlexformManipulationHook
{
    /**
     * @param array<mixed> $dataStructure
     * @return array<mixed>
     */
    protected function recursiveDefaultAnalytic(array $dataStructure)
    {
        if (empty($dataStructure)) {
            return [];
        }
        $result = [];
        foreach ($dataStructure as $key => $item) {
            if ($key !== 'config') {
                if (is_array($item)) {
                    $result[$key] = $this->recursiveDefaultAnalytic($item);
                } else {
                    $result[$key] = $item;
                }
            } else {
                if ((array_key_exists('renderType', $item)) &&
                    ($item['renderType'] === 'inputDateTime') &&
                    array_key_exists(DefaultTimer::TIMER_NAME, $item)
                ) {
                    try {
                        // remark probelm with Format and timezone
                        // https://stackoverflow.com/questions/32109936/php-datetime-format-does-not-respect-timezones
                        $dateValue = new DateTime($item[DefaultTimer::TIMER_NAME]);
                        if ($dateValue !== false) {
                            $item[DefaultTimer::TIMER_NAME] = DateTimeUtility::formatForZone($dateValue, TimerInterface::TIMER_FORMAT_DATETIME);
                        }
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
                if ((array_key_exists('type', $item)) &&
                    ($item['type'] === 'datetime') &&
                    array_key_exists(DefaultTimer::TIMER_NAME, $item)
                ) {
                    try {
                        // remark probelm with Format and timezone
                        // https://stackoverflow.com/questions/32109936/php-datetime-format-does-not-respect-timezones
                        $dateValue = new DateTime($item[DefaultTimer::TIMER_NAME]);
                        if ($dateValue !== false) {
                            $item[DefaultTimer::TIMER_NAME] = DateTimeUtility::formatForZone($dateValue, TimerInterface::TIMER_FORMAT_DATETIME);
                        }
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
                $result[$key] = $item;
            }
        }
        return $result;
    }

    /**
     * @param array<mixed> $dataStructure
     * @param array<mixed> $identifier
     * @return array<mixed>
     */
    public function parseDataStructureByIdentifierPostProcess(array $dataStructure, array $identifier): array
    {
        if (($identifier['type'] === 'tca') &&
            ($identifier['fieldName'] === TimerConst::TIMER_FIELD_FLEX_ACTIVE)
        ) {
            $dataStructure = $this->recursiveDefaultAnalytic($dataStructure);
        }
        return $dataStructure;
    }
}
