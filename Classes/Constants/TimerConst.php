<?php

namespace Porthd\Timer\Constants;

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

use Porthd\Svt\Domain\Model\TimerBaseParameter;
use Porthd\Svt\Service\Timer\CrontimeCheckService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


/**
 * Class for extension-wide constants
 *
 */
class TimerConst
{
    public const ARG_USE_ACTIVE_TIMEZONE = 'useTimeZoneOfFrontend';
    public const ARG_EVER_TIME_ZONE_OF_EVENT = 'timeZoneOfEvent';
    public const ARG_ULTIMATE_RANGE_BEGINN = 'ultimateBeginningTimer';
    public const ARG_ULTIMATE_RANGE_END = 'ultimateEndingTimer';

    public const MARK_OF_EXT_FOLDER_IN_FILEPATH = 'EXT:';

    public static $LIMIT_EVENTS_COUNT = 1000;
    public static $LIMIT_EVENTS_NEVER_YEAR = 10000;


    /** needed parameter in flexform of timer **/
    public const DEFAULT_TIME_ZONE = 'UTC';

    public const EXTENSION_NAME = 'timer';
    public const LANGUAGE_PREFIX_TYPO3 = 'LLL:';


    public const PERIOD_DESCRIPT_JSON = 0;
    public const PERIOD_DESCRIPT_TEXT = 1;
    public const PERIOD_DESCRIPT_FALLBACK = 2;

    public const TIMER_FIELD_FLEX_ACTIVE = 'tx_timer_timer';
    public const TIMER_FIELD_SCHEDULER = 'tx_timer_scheduler';
    public const TIMER_FIELD_SELECT = 'tx_timer_selector';
    public const GETTER_TIMER_FIELD_FLEX_ACTIVE = 'getTxTimerTimer';
    public const GETTER_TIMER_FIELD_SELECT = 'getTxTimerSelector';
    public const TIMER_FIELD_UID = 'uid';
    public const TIMER_FIELD_PID = 'pid';
    public const TIMER_FIELD_DELETED = 'deleted';
    public const TIMER_FIELD_ENDTIME = 'endtime';
    public const TIMER_FIELD_STARTTIME = 'starttime';
    public const TIMER_NEEDED_FIELDS = [
        self::TIMER_FIELD_FLEX_ACTIVE,
        self::TIMER_FIELD_SELECT,
        self::TIMER_FIELD_UID,
        self::TIMER_FIELD_PID,
        self::TIMER_FIELD_ENDTIME,
        self::TIMER_FIELD_STARTTIME,
    ];

    public const GLOBALS_SUBKEY_CUSTOMTIMER = 'customTimer';
    public const GLOBALS_SUBKEY_EXCLUDE = 'removeTimer';

    public const ARGUMENT_MAX_LATE = 'maxLate';
    public const ARGUMENT_MAX_COUNT = 'maxCount';
    public const ARGUMENT_COUNT_HARD_BREAK = 'hartBreak';
    public const ARGUMENT_HOOK_CUSTOM_EVENT_COMPARE = 'userRangeCompare';
    public const DEFAULT_MAX_COUNT_SORTED = 25;
    public const DEFAULT_MAX_COUNT = 25;
    public const SAVE_LIMIT_MAX_EVENTS = 500;
    public const SAVE_LIMIT_NEVER_YEAR = 1000;
    public const DEFAULT_DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const TIMER_FORMAT_DATE = 'Y-m-d';
    public const TIMER_FORMAT_TIME = 'H:i:s';
    public const TIMER_FORMAT_DATETIME = self::TIMER_FORMAT_DATE . ' ' . self::TIMER_FORMAT_TIME;

    public const ARGUMENT_DATETIME_FORMAT = 'datetimeFormat';
    public const ARGUMENT_REVERSE = 'reverse';
    public const ARGUMENT_ACTIVEZONE = 'zoneInFrontend';
    public const ARGUMENT_SELECTOR = 'selector';
    public const ARGUMENT_DATETIME_START = 'datetimeStart';

    public const KEY_PREFIX_TIME = 'T';
    public const KEY_PREFIX_DATE = 'D';
    public const KEY_UNIT_MINUTE = 'TM';
    public const KEY_UNIT_HOUR = 'TH';
    public const KEY_UNIT_DAY = 'DD';
    public const KEY_UNIT_WEEK = 'DW';
    public const KEY_UNIT_MONTH = 'DM';
    public const KEY_UNIT_YEAR = 'DY';

    public const KEY_EVENT_LIST_GAP = 'gap';
    public const KEY_EVENT_LIST_RANGE = 'range';
    public const KEY_EVENT_LIST_TIMER = 'timer';
}