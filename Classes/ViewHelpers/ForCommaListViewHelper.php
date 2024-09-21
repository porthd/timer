<?php

declare(strict_types=1);

namespace Porthd\Timer\ViewHelpers;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2023 Dr. Dieter Porth <info@mobger.de>
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

use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * CommaList-viewhelper is a loop ViewHelper which can be used to iterate over comma-separated list.
 * Implements what a basic PHP ``foreach()`` does.
 *
 * Examples
 * ========
 *
 * Simple Loop
 * -----------
 *
 * ::
 *
 *     <timer:forCommaList each="1,2,3,4" as="foo">{foo}</timer:forCommaList>
 *
 * Output::
 *
 *     1234
 *
 * Output array key
 * ----------------
 *
 * ::
 *
 *     <ul>
 *         <!-- respect whitespace -->
 *         <timer:forCommaList each="apple ,pear ,banana , cherry "
 *             trim="0" reverse="1"
 *             as="fruit" key="label"
 *         >
 *             <li>{label}: {fruit}</li>
 *         </timer:forCommaList>
 *     </ul>
 *
 * Output::
 *
 *     <ul>
 *         <li>0:  cherry </li>
 *         <li>1: banana </li>
 *         <li>2: pear </li>
 *         <li>3: apple </li>
 *     </ul>
 *
 * Iteration information
 * ---------------------
 *
 * ::
 *
 *     <ul>
 *         <timer:forCommaList each="1; 2; 3; 4 }" as="foo" limiter=";" iteration="fooIterator">
 *             <li>Index: {fooIterator.index} Cycle: {fooIterator.cycle} Total: {fooIterator.total}{f:if(condition: fooIterator.isEven, then: ' Even')}{f:if(condition: fooIterator.isOdd, then: ' Odd')}{f:if(condition: fooIterator.isFirst, then: ' First')}{f:if(condition: fooIterator.isLast, then: ' Last')}</li>
 *         </timer:forCommaList>
 *     </ul>
 *
 * Output::
 *
 *     <ul>
 *         <li>Index: 0 Cycle: 1 Total: 4 Odd First</li>
 *         <li>Index: 1 Cycle: 2 Total: 4 Even</li>
 *         <li>Index: 2 Cycle: 3 Total: 4 Odd</li>
 *         <li>Index: 3 Cycle: 4 Total: 4 Even Last</li>
 *     </ul>
 *
 * @api
 */
class ForCommaListViewHelper extends AbstractViewHelper
{

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('each', 'string', 'The list which will be converted to an arry to iterated over', true);
        $this->registerArgument('limiter', 'string', 'The char for the limiter of parts', false, ',');
        $this->registerArgument('trim', 'boolean', 'trim the entries in the list for whitespaces', false, true);
        $this->registerArgument('as', 'string', 'The name of the iteration variable', true);
        $this->registerArgument('key', 'string', 'Variable to assign array key to', false);
        $this->registerArgument('reverse', 'boolean', 'If TRUE, iterates in reverse', false, false);
        $this->registerArgument('iteration', 'string', 'The name of the variable to store iteration information (index, cycle, isFirst, isLast, isEven, isOdd)');
    }

    public function render()
    {
        $templateVariableContainer = $this->renderingContext->getVariableProvider();
        $flagTrim = ((!empty($this->arguments['trim'])) ? true : false);
        if (!isset($this->arguments['each']) || empty($this->arguments['each'])) {
            return '';
        }
        if (!is_string($this->arguments['each'])) {
            throw new Exception('CommaListViewHelper only supports list, which are separated by a comma or an other limiter', 1678601258);
        }
        if (!isset($this->arguments['limiter']) || empty($this->arguments['limiter'])) {
            $limiter = ',';
        } else {
            $limiter = $this->arguments['limiter'];
        }
        $myList = explode($limiter, $this->arguments['each']);
        if ($flagTrim) {
            $myList = array_filter(
                array_map(
                    'trim',
                    $myList
                )
            );
        }
        if ($this->arguments['reverse'] === true) {
            // array_reverse only supports arrays
            $myList = array_reverse($myList, true);
        }
        $iterationData = [
            'index' => 0,
            'cycle' => 1,
            'total' => count($myList),
        ];

        $output = '';
        foreach ($myList as $keyValue => $singleElement) {
            $templateVariableContainer->add($this->arguments['as'], $singleElement);
            if (isset($this->arguments['key'])) {
                $templateVariableContainer->add($this->arguments['key'], $keyValue);
            }
            if (isset($this->arguments['iteration'])) {
                $iterationData['isFirst'] = $iterationData['cycle'] === 1;
                $iterationData['isLast'] = $iterationData['cycle'] === $iterationData['total'];
                $iterationData['isEven'] = $iterationData['cycle'] % 2 === 0;
                $iterationData['isOdd'] = !$iterationData['isEven'];
                $templateVariableContainer->add($this->arguments['iteration'], $iterationData);
                $iterationData['index']++;
                $iterationData['cycle']++;
            }
            $output .= $this->renderChildren();
            $templateVariableContainer->remove($this->arguments['as']);
            if (isset($this->arguments['key'])) {
                $templateVariableContainer->remove($this->arguments['key']);
            }
            if (isset($this->arguments['iteration'])) {
                $templateVariableContainer->remove($this->arguments['iteration']);
            }
        }
        return $output;
    }
}
