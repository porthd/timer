<?php

declare(strict_types=1);

namespace Porthd\Timer\Constants;

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
    /**
     * used in different place
     */
    public const EXTENSION_NAME = 'timer'; // used on many other places too

    public const MARK_OF_EXT_FOLDER_IN_FILEPATH = 'EXT:';  // There no constant defined in the TYPO3 core for it
    public const MARK_OF_FILE_EXT_FOLDER_IN_FILEPATH = 'FILE:EXT:';  // There no constant defined in the TYPO3 core for it

    /**
     * Usage in Ext_localconf for registration of custion timer
     *
     *used in
     * - ListOfTimerService
     * - ConfigurationUtility
     * - TcaUtility
     */
    public const GLOBALS_SUBKEY_CUSTOMTIMER = 'customTimer';
    public const GLOBALS_SUBKEY_EXCLUDE = 'removeTimer';
    public const HOOK_CHANGE_LIST_OF_TIMEZONES = 'changeListOfTimezones'; // modify the list of uesed timezone-codes with your own hook-Method

    /**
     * needed in repositorys and repository-related classes
     * and needed in cronjob for updating startime and endtime
     *
     */
    public const TIMER_FIELD_FLEX_ACTIVE = 'tx_timer_timer';
    public const TIMER_FIELD_SCHEDULER = 'tx_timer_scheduler';
    public const TIMER_FIELD_SELECTOR = 'tx_timer_selector';
    public const TIMER_FIELD_UID = 'uid';
    public const TIMER_FIELD_PID = 'pid';
    public const TIMER_FIELD_ENDTIME = 'endtime';
    public const TIMER_FIELD_STARTTIME = 'starttime';
    public const TIMER_RELATION_TABLE = 'relation_table';
    public const TIMER_RELATION_UID = 'relation_uid';
    public const TIMER_NEEDED_FIELDS = [
        self::TIMER_FIELD_FLEX_ACTIVE,
        self::TIMER_FIELD_SELECTOR,
        self::TIMER_FIELD_UID,
        self::TIMER_FIELD_PID,
        self::TIMER_FIELD_ENDTIME,
        self::TIMER_FIELD_STARTTIME,
    ];

    // for constants for some classes
    // - varoius dataprocessor in this extension
    // - viewhelper `ìsActiveViewHelper
    // - helperservice `listOfEventsService`
    public const ARGUMENT_IF_DOT = 'if.';
    public const ARGUMENT_CACHE = 'cache';
    public const ARGUMENT_FIELDNAME = 'fieldName';
    public const ARGUMENT_AS = 'as';
    public const ARGUMENT_DATETIME_FORMAT = 'datetimeFormat';
    public const ARGUMENT_REVERSE = 'reverse';
    public const ARGUMENT_ACTIVEZONE = 'timezone';
    public const ARGUMENT_DATETIME_START = 'datetimeStart';
    public const ARGUMENT_MAX_LATE = 'maxLate';
    public const ARGUMENT_MAX_GAP = 'maxGap';
    public const ARGUMENT_MAX_COUNT = 'maxCount';
    public const ARGUMENT_HOOK_CUSTOM_EVENT_COMPARE = 'userRangeCompare';

    public const CACHE_IDENT_TIMER_YAMLLIST = 'timer_yamllist';

    public const DEFAULT_TIMEZONE = 'Europe/Berlin';
    public const INTERNAL_TIMEZONE = 'UTC';

    public const ADDITIONAL_CALENDAR_JULIAN = 'julian';
    public const FAKE_CALENDAR_JULIAN_BY_GREGORIAN = 'gregorian';

    public const DEFAULT_FLATTEN_KEYS_LIST = ['data', 'general', 'timer', 'sDEF', 'lDEF', 'vDEF',];

    public const LIST_SEASON_OF_YEAR = ['spring', 'summer', 'autumn', 'winter'];

    public const TCA_ITEMS_LABEL = 'label';
    public const TCA_ITEMS_VALUE = 'value';
    public const TCA_ITEMS = 'items';


}
