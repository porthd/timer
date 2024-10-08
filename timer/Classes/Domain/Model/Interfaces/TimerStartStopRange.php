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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use DateInterval;
use DateTime;

/**
 * TimerStartStopRange contains getter and setter für data-exchanges in this extension `timer`
 */
final class TimerStartStopRange
{
    /**
     * flag for Result
     *
     * @var boolean
     */
    protected $resultExist = true;

    /**
     * beginning
     *
     * @var DateTime
     */
    protected $beginning;

    /**
     * ending
     *
     * @var DateTime
     */
    protected $ending;

    /**
     * TimerStartStopRange constructor.
     */
    public function __construct()
    {
        $currentTStamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp') ?? time();
        $now = new DateTime('@' . $currentTStamp);
        $this->reMilleniumActive($now);
    }

    /**
     *
     */
    public function setZero(): void
    {
        $this->beginning = new DateTime('@0');
        $this->ending = new DateTime('@0');
    }

    /**
     * @param DateTime $dateBelowNextActive
     */
    public function reMilleniumActive(DateTime $dateBelowNextActive): void
    {
        $this->beginning = clone $dateBelowNextActive;
        $this->beginning->sub(new DateInterval('P10000Y'));
        $this->ending = clone $dateBelowNextActive;
        $this->ending->add(new DateInterval('P10000Y'));
        $this->resultExist = true;
    }

    /**
     * @param DateTime $dateBelowNextActive
     */
    public function failOnlyPrevActive(DateTime $dateBelowNextActive): void
    {
        $this->beginning = clone $dateBelowNextActive;
        $this->beginning->sub(new DateInterval('P10000Y'));
        $this->ending = clone $dateBelowNextActive;
        $this->ending->sub(new DateInterval('PT1S'));
        $this->resultExist = false;
    }

    /**
     * @param DateTime $dateAbovePrevActive
     */
    public function failOnlyNextActive(DateTime $dateAbovePrevActive): void
    {
        $this->beginning = clone $dateAbovePrevActive;
        $this->beginning->add(new DateInterval('PT1S'));
        $this->ending = clone $dateAbovePrevActive;
        $this->ending->add(new DateInterval('P10000Y'));
        $this->resultExist = false;
    }

    /**
     * @param DateTime $referenceDateForFailActive
     * @return void
     */
    public function failAllActive(DateTime $referenceDateForFailActive): void
    {
        $this->beginning = clone $referenceDateForFailActive;
        $this->beginning->add(new DateInterval('PT1S'));
        $this->ending = clone $referenceDateForFailActive;
        $this->ending->sub(new DateInterval('PT1S'));
        $this->resultExist = false;
    }

    /**
     * Returns the beginning
     *
     * @return DateTime $beginning
     */
    public function getBeginning()
    {
        return $this->beginning;
    }

    /**
     * Sets the beginning
     *
     * @param DateTime $beginning
     * @return void
     */
    public function setBeginning(DateTime $beginning)
    {
        $this->beginning = clone $beginning;
    }

    /**
     * Returns the ending
     *
     * @return DateTime $ending
     */
    public function getEnding()
    {
        return $this->ending;
    }

    /**
     * Sets the ending
     *
     * @param DateTime $ending
     * @return void
     */
    public function setEnding(DateTime $ending)
    {
        $this->ending = clone $ending;
    }

    /**
     * Returns the resultExist
     *
     * @return bool $resultExist
     */
    public function getResultExist()
    {
        return $this->resultExist;
    }

    /**
     * alias
     * Returns the resultExist
     *
     * @return bool $resultExist
     */
    public function hasResultExist()
    {
        return $this->getResultExist();
    }

    /**
     * Sets the resultExist
     *
     * @param bool $resultExist
     * @return void
     */
    public function setResultExist(bool $resultExist)
    {
        $this->resultExist = $resultExist;
    }
}
