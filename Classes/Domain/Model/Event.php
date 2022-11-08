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
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Timings
 */
class Event extends AbstractEntity implements TimerModellInterface
{

    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * description
     *
     * @var string
     */
    protected $description = '';


    /**
     * teaserSlogan
     *
     * @var string
     */
    protected $teaserSlogan = '';

    /**
     * teaserInfotext
     *
     * @var string
     */
    protected $teaserInfotext = '';

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
     * flagTest
     *
     * @var bool
     */
    protected $flagTest = '';

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description
     *
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }


    /**
     * Returns the teaserSlogan
     *
     * @return string $teaserSlogan
     */
    public function getTeaserSlogan()
    {
        return $this->teaserSlogan;
    }

    /**
     * Sets the teaserSlogan
     *
     * @param string $teaserSlogan
     * @return void
     */
    public function setTeaserSlogan($teaserSlogan)
    {
        $this->teaserSlogan = $teaserSlogan;
    }

    /**
     * Returns the teaserInfotext
     *
     * @return string $teaserInfotext
     */
    public function getTeaserInfotext()
    {
        return $this->teaserInfotext;
    }

    /**
     * Sets the teaserInfotext
     *
     * @param string $teaserInfotext
     * @return void
     */
    public function setTeaserInfotext($teaserInfotext)
    {
        $this->teaserInfotext = $teaserInfotext;
    }

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

    /**
     * @return bool
     */
    public function getFlagTest(): bool
    {
        return $this->flagTest;
    }

    /**
     * @return bool
     */
    public function hasFlagTest(): bool
    {
        return $this->flagTest;
    }

    /**
     * @param bool $flagTest
     */
    public function setFlagTest($flagTest = true): void
    {
        $this->flagTest = $flagTest;
    }

}
