<?php

use Porthd\Timer\Constants\TimerConst;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') || die();

ExtensionManagementUtility::addStaticFile(
    TimerConst::EXTENSION_NAME,
    'Configuration/TypoScript',
    'Timer (content-element `timersimul` as example for usage of viewhelpers and different timers)'
);


/**
 * Default TypoScript
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    TimerConst::EXTENSION_NAME,
    'Configuration/TypoScript/Periodlist',
    'Timer (content-element `timersimul` as example for usages of `PeriodlistTimer` to generate a list from datas in a file)'
);
