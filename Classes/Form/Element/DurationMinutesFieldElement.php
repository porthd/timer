<?php

declare(strict_types=1);

namespace Porthd\Timer\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * default structure in the flexform with all parameters
 * the value here should be tested in the validate-method (see Timer-interface)
 *                     <config>
 *                         <type>user</type>
 *                         <required>1</required>
 *                         <renderType>durationMinutesField</renderType>
 *                         <default>60</default>
 *                         <parameters>
 *                             <!-- default warningZero is true (1) -->
 *                             <warningZero>0</warningZero>
 *                             <!-- default both flags are zero (0) -->
 *                             <flagHours>1</flagHours>
 *                             <flagDays>1</flagDays>
 *                             <rangeMinutes>
 *                                 <!-- default is range [-59,59] -->
 *                                 <lower>-59</lower>
 *                                 <upper>59</upper>
 *                                 <default>0</default>
 *                             </rangeMinutes>
 *                             <rangeHours>
 *                                 <lower>-23</lower>
 *                                 <upper>23</upper>
 *                                 <default>0</default>
 *                             </rangeHours>
 *                             <rangeDays>
 *                                 <lower>-320</lower>
 *                                 <upper>320</upper>
 *                                 <default>0</default>
 *                             </rangeDays>
 *                         </parameters>
 *                     </config>
 *
 *
 *
 *
 *
 *
 */
/**
 * inspired by https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/User/Index.html
 */
class DurationMinutesFieldElement extends AbstractFormElement
{
    /**
     * @return array<mixed>
     */
    public function render(): array
    {
        // todo default-value
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $flagWarningZero = (
        (isset($parameterArray['fieldConf']['config']['parameters']['warningZero'])) ?
            $parameterArray['fieldConf']['config']['parameters']['warningZero'] :
            1
        );
        $defaultMinutes = (
        (isset($parameterArray['fieldConf']['config']['parameters']['rangeMinutes']['default'])) ?
            $parameterArray['fieldConf']['config']['parameters']['rangeMinutes']['default'] :
            0
        );
        $minMinutes = (
        (isset($parameterArray['fieldConf']['config']['parameters']['rangeMinutes']['lower'])) ?
            $parameterArray['fieldConf']['config']['parameters']['rangeMinutes']['lower'] :
            -59
        );
        $maxMinutes = (
        (isset($parameterArray['fieldConf']['config']['parameters']['rangeMinutes']['upper'])) ?
            $parameterArray['fieldConf']['config']['parameters']['rangeMinutes']['upper'] :
            +59
        );
        $flagHours = (
        (isset($parameterArray['fieldConf']['config']['parameters']['flagHours'])) ?
            (!empty($parameterArray['fieldConf']['config']['parameters']['flagHours'])) :
            false
        );
        $flagDays = (
        (isset($parameterArray['fieldConf']['config']['parameters']['flagDays'])) ?
            (!empty($parameterArray['fieldConf']['config']['parameters']['flagDays'])) :
            false
        );
        $minHours = (
        (isset($parameterArray['fieldConf']['config']['parameters']['rangeHours']['lower'])) ?
            $parameterArray['fieldConf']['config']['parameters']['rangeHours']['lower'] :
            -23
        );
        $maxHours = (
        (isset($parameterArray['fieldConf']['config']['parameters']['rangeHours']['upper'])) ?
            $parameterArray['fieldConf']['config']['parameters']['rangeHours']['upper'] :
            23
        );
        $defaultHours = (
        (isset($parameterArray['fieldConf']['config']['parameters']['rangeHours']['default'])) ?
            $parameterArray['fieldConf']['config']['parameters']['rangeHours']['default'] :
            0
        );
        $minDays = (
        (isset($parameterArray['fieldConf']['config']['parameters']['rangeHours']['lower'])) ?
            $parameterArray['fieldConf']['config']['parameters']['rangeHours']['lower'] :
            -10
        );
        $maxDays = (
        (isset($parameterArray['fieldConf']['config']['parameters']['rangeHours']['upper'])) ?
            $parameterArray['fieldConf']['config']['parameters']['rangeHours']['upper'] :
            10
        );
        $defaultDays = (
        (isset($parameterArray['fieldConf']['config']['parameters']['rangeDays']['default'])) ?
            $parameterArray['fieldConf']['config']['parameters']['rangeDays']['default'] :
            0
        );


        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($this->initializeResultArray(), $fieldInformationResult, false);

        $fieldId = StringUtility::getUniqueId('formengine-textarea-') . '-' . ($row['uid'] + 7894);

        $attributes = [
            'id' => $fieldId,
            'name' => htmlspecialchars($parameterArray['itemFormElName']),
            'size' => 5,
            'data-formengine-input-name' => htmlspecialchars($parameterArray['itemFormElName']),
        ];

        $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($GLOBALS['BE_USER']);
        $labelMinutes = $languageService->sL(
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:module.backend.flexform.field.durationMinutes.minutes'
        );
        $labelHours = $languageService->sL(
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:module.backend.flexform.field.durationMinutes.hours'
        );
        $labelDays = $languageService->sL(
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:module.backend.flexform.field.durationMinutes.days'
        );
        $labelMain = $languageService->sL(
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:module.backend.flexform.field.durationMinutes.main'
        );


        $attributes['placeholder'] =
            htmlspecialchars(trim($row['tx_timer_timer']['data']['timer']['lDEF']['durationMinutes']['vDEF']));
        $classes = [
            'form-control',
        ];
        $itemValue = $parameterArray['itemFormElValue'];
        if (empty($itemValue)) {
            $itemValue = '0';
            $curMinutes = $defaultMinutes;
            $curHours = $defaultHours;
            $curDays = $defaultDays;

        } else {
            $value = (int)$itemValue;
            $curMinutes = ($value % 60);
            $curHours = (int)(($value % 1440 - $curMinutes) / 60);
            $curDays = (int)($value / 1440);
        }
        $attributes['class'] = implode(' ', $classes);
        $classesString = $attributes['class'];
        $internalId = 'porthd-timer-backend-durationminutes-' . $fieldId;
        $html = [];
        $html[] = '<div id="' . $internalId . '" class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] = '<div class="form-wizards-wrap"  >';
        $html[] = '    <div class="form-wizards-element" style="display:flex;">';
        $html[] = '        <div class="form-control-wrap"  style="margin-left:1rem;">';
        $html[] = '            <label>' . $labelMain . '</label>';
        $html[] = '            <input type="text" value="' . htmlspecialchars($itemValue, ENT_QUOTES) . '" ';
        $html[] = '                   ' . GeneralUtility::implodeAttributes($attributes, true);
        $html[] = '                   readonly="readonly" size="10" data-id="result" style="background-color:#ddd;" ';
        $html[] = '            />';
        $html[] = '        </div>';
        if ($flagDays) {
            $html[] = <<<DAYFORSPECIAL
        <div class="form-control-wrap"  style="margin-left:1rem;">
            <label>$labelDays</label>
            <input  class="$classesString"
                    name="day" data-id="day" data-type="changer"
                    placeholder="$defaultDays"
                    value="$curDays" type="number"
                    min="$minDays" max="$maxDays" size="4"
            />
        </div>
DAYFORSPECIAL;

        } else {
            $html[] = '<input name="day" data-id="day" value="0" type="hidden" />';
        }// days-input by flag
        if ($flagHours) {
            $html[] = <<<HOURFORSPECIAL
        <div class="form-control-wrap" style="margin-left:1rem;">
            <label>$labelHours</label>
            <input class="$classesString" name="hour"
                    data-id="hour" data-type="changer"
                    value="$curHours" type="number"
                    placeholder="$defaultHours"
                    min="$minHours" max="$maxHours" size="4"
            />
        </div>
HOURFORSPECIAL;
        } else {
            $html[] = '<input name="hour" data-id="hour" value="0" type="hidden" />';
        }// hours-input by flag
        $html[] = <<<MINUTEFORSPECIAL
        <div class="form-control-wrap"  style="margin-left:1rem;">
            <label>$labelMinutes</label>
            <input  class="$classesString"  name="minute"
                    data-id="minute" data-type="changer"
                    placeholder="$defaultMinutes"
                    value="$curMinutes" type="number"
                    min="$minMinutes" max="$maxMinutes" size="4"
            />
        </div>
MINUTEFORSPECIAL;
        $html[] = '    </div>';
        $html[] = '</div>';

        $scriptFragement = '';
        if ($flagWarningZero) {
            $scriptFragement = '<!-- toDo build script to flash a warning -->';
        }
        $html[] = <<<SCRIPTFORSPECIAL
<script>

        (function () {
            let maxResult = 143999;
            let minResult = -143999;
            let scope = document.getElementById('$internalId');
            console.log(scope);
            let resultOut = scope.querySelector('input[data-id="result"]');
            let minuteIn = scope.querySelector('input[data-id="minute"]');
            let hourIn = scope.querySelector('input[data-id="hour"]');
            let dayIn = scope.querySelector('input[data-id="day"]');
            let changer = scope.querySelectorAll('input[data-type="changer"]');
            changer.forEach((node) => {
                node.addEventListener('change', () => {
            console.log('change-event');
                   let minutes =  (!minuteIn)? 0: minuteIn.value;
                   let hours =  (!hourIn)? 0: hourIn.value;
                    let days =  (!dayIn)? 0: dayIn.value;
                    console.log('m-h-d : '+ minutes + '-'+ hours + '-'+ days + '.' )
                   let result = parseInt(minutes,10)+parseInt(hours,10)*60+parseInt(days,10)*1440;
                   if (result === 0) {
                       // show warning
                   } else {
                       if (result > maxResult) {
                           result = maxResult;
                       } else if (result < minResult) {
                           result = minResult;
                       }
                   }
                    resultOut.value = result;
               } )
            });
        })();


    </script>
SCRIPTFORSPECIAL;
        $html[] = '</div>';
        $resultArray['html'] = implode(PHP_EOL, $html);

        return $resultArray;
    }
}
