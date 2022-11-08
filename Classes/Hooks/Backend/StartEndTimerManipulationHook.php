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
use Porthd\Timer\Utilities\DateTimeUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * see https://github.com/georgringer/news/issues/268 visited 20201006
 *
 * Class FlexformManipulationHook
 * @package Porthd\Timer\Hooks\Backend
 */
class StartEndTimerManipulationHook
{

    /**
     * after saving the changs for the defintion of the periods reset start and endtime to zero
     *
     * @param string $status not used
     * @param string $table not used
     * @param mixed $id used for remarkable identifier of
     * @param array $fieldArray contains the change field in the save array
     * @param object $selfDatamapper not used
     */
    public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, $selfDatamapper)
    {
        if ((
                (isset($fieldArray[TimerConst::TIMER_FIELD_SELECT])) ||
                (isset($fieldArray[TimerConst::TIMER_FIELD_FLEX_ACTIVE]))
            ) 
        ) {
            // Reset the values of starttiem and endtime
            // Dont change them here, This is job of the scheduler
            $fieldArray[TimerConst::TIMER_FIELD_STARTTIME] = 0;
            $fieldArray[TimerConst::TIMER_FIELD_ENDTIME] = 0;
            /** @var FlashMessage $message */
            $message = GeneralUtility::makeInstance(
                FlashMessage::class,
                LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:timer.startendtimermanipulationhook.hook.postProcess.infoOfResetStartEndtime.title',
                    TimerConst::EXTENSION_NAME
                ),
                LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:timer.startendtimermanipulationhook.hook.postProcess.infoOfResetStartEndtime.message',
                    TimerConst::EXTENSION_NAME
                ),
                AbstractMessage::INFO,
                true
            );
            /** @var FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var FlashMessageQueue $myFlashService */
            $myFlashService = $flashMessageService->getMessageQueueByIdentifier();
            $myFlashService->addMessage($message);
        }
    }

}