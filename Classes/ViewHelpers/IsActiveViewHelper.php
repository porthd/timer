<?php

declare(strict_types=1);

namespace Porthd\Timer\ViewHelpers;

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
use DateTimeZone;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Services\ListOfTimerService;
use Porthd\Timer\Utilities\DateTimeUtility;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

class IsActiveViewHelper extends AbstractConditionViewHelper
{
    // parameter for the viewhelper
    protected const ARGUMENT_PARAM_LIST = 'paramlist';
    protected const ARGUMENT_FLEXFORM_STRING = 'flexformstring';
    protected const ARGUMENT_REF_TIMESTAMP = 'timestamp';
    protected const ARGUMENT_SELECTOR = 'selector';
    protected const ARGUMENT_ACTIVEZONE = TimerConst::ARGUMENT_ACTIVEZONE;

    /**
     * Initializes the arguments for the viewHelper
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            self::ARGUMENT_PARAM_LIST,
            'array',
            'The simple array contain all needed parameters of the timer. You will find the names of the keys ' .
            'for the array in the flexform-definition of the timer.' .
            'One of the two parameters `' . self::ARGUMENT_FLEXFORM_STRING . '` or `' . self::ARGUMENT_PARAM_LIST . '` must be defined.',
            false
        );
        $this->registerArgument(
            self::ARGUMENT_FLEXFORM_STRING,
            'string',
            'The flexform-string with definition for selected timer.' .
            'One of the two parameters `' . self::ARGUMENT_FLEXFORM_STRING . '` or `' . self::ARGUMENT_PARAM_LIST . '` must be defined.',
            false
        );
        $this->registerArgument(
            self::ARGUMENT_SELECTOR,
            'string',
            'selector of timer-model',
            true
        );
        $this->registerArgument(
            self::ARGUMENT_REF_TIMESTAMP,
            'string',
            'A timestamp is used for the reference date. Defaults to current time.',
            false
        );
        $this->registerArgument(
            self::ARGUMENT_ACTIVEZONE,
            'string',
            'Name of a valid timezone, which represent the time-zone of the user.',
            false
        );
    }


    /**
     * @param array<mixed> $arguments
     * @param RenderingContextInterface $renderingContext
     * @return bool
     * @throws TimerException
     */
    public static function verdict(array $arguments, RenderingContextInterface $renderingContext)
    {
        try {
            if (!empty($arguments[self::ARGUMENT_FLEXFORM_STRING])) {
                $paramsString = $arguments[self::ARGUMENT_FLEXFORM_STRING];
                $flexParams = GeneralUtility::xml2array($paramsString);
                $params = TcaUtility::flexformArrayFlatten($flexParams);
            } else {
                if (array_key_exists(self::ARGUMENT_PARAM_LIST, $arguments)) {
                    $params = $arguments[self::ARGUMENT_PARAM_LIST];
                } else {
                    throw new TimerException(
                        'The isActive-viewhelper requires the following parameters: (`' .
                        self::ARGUMENT_FLEXFORM_STRING . '` xor `' . self::ARGUMENT_PARAM_LIST . '`) and `' . self::ARGUMENT_SELECTOR . '. ' .
                        'The arguments are missing and cause this exception.',
                        1601990316
                    );
                }
            }
            $selector = $arguments[self::ARGUMENT_SELECTOR];

            $activeZone = (
                (empty($arguments[self::ARGUMENT_ACTIVEZONE])) ?
                date_default_timezone_get() :
                $arguments[self::ARGUMENT_ACTIVEZONE]
            );

            /** @var ListOfTimerService $timerList */
            $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);
            $flagAnalyse = false;
            if ($timerList->validate($selector, $params)) {
                $flagAnalyse = true;
                $timestamp = $arguments[self::ARGUMENT_REF_TIMESTAMP] ?? '';
                if (MathUtility::canBeInterpretedAsInteger($timestamp)) {
                    $dateValue = new DateTime('@' . $timestamp);
                    $dateValue->setTimezone(new DateTimeZone($activeZone));
                } else {
                    $dateValue = DateTimeUtility::getCurrentExecTime() ?: new DateTime('now');
                    $dateValue->setTimezone(new DateTimeZone($activeZone));
                }

                $flagActive = $timerList->isAllowedInRange($selector, $dateValue, $params);
                $flagActive = $flagActive && $timerList->isActive($selector, $dateValue, $params);
            }
        } catch (Exception $exception) {
            throw new TimerException(
                'The isActive-viewhelper requires the following parameters: (`' .
                self::ARGUMENT_FLEXFORM_STRING . '` xor `' . self::ARGUMENT_PARAM_LIST . '`) and `' . self::ARGUMENT_SELECTOR . '. ' .
                'The arguments `' . self::ARGUMENT_REF_TIMESTAMP . '`(default: current date) and `' . self::ARGUMENT_ACTIVEZONE .
                '`(default: value of server). ' .
                'The wrongly defined arguments or their missing cause this exception. Please check the following values: `' .
                "\n" . print_r($arguments) . "\n" . '`. Please check although the previous message: ' . $exception->getMessage(),
                1601990312
            );
        }
        if (!$flagAnalyse) {
            throw new TimerException(
                'The parameter `' . print_r($params, true) . '` for the timer `' . $selector . '` is not valid. ' .
                'Please check your definition in the backend or in the template. ',
                1600000001
            );
        }

        return $flagActive;
    }
}
