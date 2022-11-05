<?php

use Porthd\Timer\Constants\TimerConst;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') || die();


ExtensionManagementUtility::addStaticFile(
    TimerConst::EXTENSION_NAME,
    'Configuration/TypoScript',
    'LLL:EXT:checker/Resources/Private/Language/locallang_db.xlf:backend.contentElement.event.title');
