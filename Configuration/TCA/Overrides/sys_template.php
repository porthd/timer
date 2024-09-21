<?php

defined('TYPO3') or die();

call_user_func(function () {
    $extensionKey = 'myextension';

    /**
     * Default TypoScript
     */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'timer',
        'Configuration/TypoScript/Holidaycalendar',
        'holidaycalendar - an example for a content-element with a holiday-calendar'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'timer',
        'Configuration/TypoScript/Timersimul',
        'holidaycalendar - an example for a content-element with using the viewhelpers and dataprocessors of timer'
    );
});
