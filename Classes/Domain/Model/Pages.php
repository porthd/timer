<?php

namespace Porthd\Timer\Domain\Model;

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

use Porthd\Timer\Domain\Model\Interfaces\TimerModellInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * getter/setter model for the extended table/model pages
 */
class Pages extends AbstractEntity implements TimerModellInterface
{
    /**
     * txTimerSelector
     *
     * @var string
     */
    protected $txTimerSelector = '';

    /**
     * txTimerTimer
     *
     * @var string
     */
    protected $txTimerTimer = '';

    /**
     * Returns the txTimerSelector
     *
     * @return string $txTimerSelector
     */
    public function getTxTimerSelector()
    {
        return $this->txTimerSelector;
    }

    /**
     * Sets the txTimerSelector
     *
     * @param string $txTimerSelector
     * @return void
     */
    public function setTxTimerSelector($txTimerSelector)
    {
        $this->txTimerSelector = $txTimerSelector;
    }

    /**
     * Returns the txTimerTimer
     *
     * @return string $txTimerTimer
     */
    public function getTxTimerTimer()
    {
        return $this->txTimerTimer;
    }

    /**
     * Sets the txTimerTimer
     *
     * @param string $txTimerTimer
     * @return void
     */
    public function setTxTimerTimer($txTimerTimer)
    {
        $this->txTimerTimer = $txTimerTimer;
    }
}
