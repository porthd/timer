<?php

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

use Closure;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\ConfigurationUtility;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * example for Usage of the viewhelper `flex`
 * If the  attribute `flattenkeys` is missing, the default is the list `data,general,timer,sDEF,lDEF,vDEF`.
 *    The values `timer` and `general` of the previous list are names of sheets in my flexform-definitions for my customTimers.
 *
 *      <timer:flex flexstring="{data.tx_timer_timer}"
 *                  as="timerflex"
 *                  flattenkeys="data,general,timer,sDEF,lDEF,vDEF"
 *      >
 *          <f:for each="{timerflex}" as="value" key="key">
 *              <tr>
 *                  <td>{key}</td>
 *                  <td>{value}</td>
 *              </tr>
 *          </f:for>
 *      </timer:flex>

 */

class FlexViewHelper extends AbstractViewHelper
{

    use CompileWithRenderStatic;

    protected const ATTR_FLEXFORM_STRING = 'flexstring';
    protected const ATTR_RESULT_AS = 'as';
    protected const ATTR_FLATTEN_KEYS = 'flattenkeys';

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    //   the attributes `timer` and `general` are used as sheet-names in my customTimer-flexforms
    protected const DEFAULT_FLATTEN_KEYS = 'data,general,timer,sDEF,lDEF,vDEF';

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument(self::ATTR_FLEXFORM_STRING,
            'string',
            'The string with the flexform',
            true
        );
        $this->registerArgument(self::ATTR_RESULT_AS,
            'string',
            'The name of the array variable with the flexform-entries',
            true
        );
        $this->registerArgument(self::ATTR_FLATTEN_KEYS,
            'string',
            'Comma-separated list of keys, which are remove to flatten the array-structure. (Remove in the frontend not Flexform-parts)',
            false,
            self::DEFAULT_FLATTEN_KEYS
        );


    }

    /**
     * @param array $arguments
     * @param Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws TimerException
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $templateVariableContainer = $renderingContext->getVariableProvider();
        if (!isset($arguments[self::ATTR_FLEXFORM_STRING])) {
            return '';
        }

        if (!is_string($arguments[self::ATTR_FLEXFORM_STRING])) {
            throw new TimerException(
                'FlexViewHelper only supports flex-fields or JSON-String and transform them to arrays. Your argument is not a string.',
                1601245879);
        }

        $stringFlatKeys = ((!empty($arguments[self::ATTR_FLATTEN_KEYS])) ?
            $arguments[self::ATTR_FLATTEN_KEYS] :
            self::DEFAULT_FLATTEN_KEYS
        );
        $singleElementRaw = GeneralUtility::xml2array($arguments[self::ATTR_FLEXFORM_STRING]);
        $flagError = (((is_string($singleElementRaw)) && (substr($singleElementRaw, 0, strlen('Line ')) === 'Line ')) ?
            'The string could not decode as xml/flexform. ' :
            '');
        $listFlatKeys = array_filter(
            array_map(
                'trim',
                explode(',', $stringFlatKeys)
            )
        );
        if (empty($listFlatKeys)) {
            $singleElement= $singleElementRaw;
        } else {
            $singleElement = TcaUtility::flexformArrayFlatten($singleElementRaw, $listFlatKeys);
        }

        if (!empty($flagError)) {
            throw new TimerException(
                'The flexViewHelper failed on the value `' . $arguments[self::ATTR_FLEXFORM_STRING] . '` ' . $flagError .
                'Is your viewhelper-configuration correct? Check your datas.',
                1601245979
            );

        }
        $templateVariableContainer->add($arguments[self::ATTR_RESULT_AS], $singleElement);
        $output = $renderChildrenClosure();
        $templateVariableContainer->remove($arguments[self::ATTR_RESULT_AS]);
        return $output;
    }
}

