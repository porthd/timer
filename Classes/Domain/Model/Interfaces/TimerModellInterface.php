<?php

declare(strict_types=1);

namespace Porthd\Timer\Domain\Model\Interfaces;

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


/**
 * interface for general needed function in getter/setter models to tables, which should be used by the timer-extension
 * it may be helpful to extend i.e. the table for the news-extension
 */
interface TimerModellInterface
{
    /**
     * Returns the txTimerSelector
     *
     * @return string $txTimerSelector
     */
    public function getTxTimerSelector();

    /**
     * Sets the txTimerSelector
     *
     * @param string $txTimerSelector
     * @return void
     */
    public function setTxTimerSelector($txTimerSelector);

    /**
     * Returns the txTimerTimer
     *
     * @return string $txTimerTimer
     */
    public function getTxTimerTimer();

    /**
     * Sets the txTimerTimer
     *
     * @param string $txTimerTimer
     * @return void
     */
    public function setTxTimerTimer($txTimerTimer);
}
