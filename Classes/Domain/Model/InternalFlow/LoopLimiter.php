<?php

namespace Porthd\Timer\Domain\Model\InternalFlow;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 Dr. Dieter Porthd <info@mobger.de>
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
use Porthd\Timer\Domain\Model\Interfaces\TimerModellInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * LoopLimiter is a helpfull getter/setter-model for the RangeListDataprocessor.
 * It prevent an infinite looping while the rangeListdataprocessor trys to merge overlapping ranges together.
 */
class LoopLimiter
{

    /**
     * flagMaxType
     *
     * @var bool
     */
    protected $flagMaxType = true;

    /**
     * maxCount
     *
     * @var int
     */
    protected $maxCount = 10;

    /**
     * maxLate
     *
     * @var DateTime|null
     */
    protected $maxLate;

    /**
     * maxLate
     *
     * @var string
     */
    protected $userCompareFunction ='';

    /**
     * Define current Time by TYPO3-Variable or PHP-Systeme
     * @return int|mixed
     */
    protected static function getCurrentTStamp()
    {
        return $GLOBALS['EXEC_TIME'] ?: time();
    }

    /**
     * LoopLimiter constructor generate a stable set of valifd Parameters.
     *
     * @param DateTime|null $date
     * @throws Exception
     */
    public function __construct(DateTime $date = null)
    {
        $this->maxLate = (($date !== null) ?
            $date :
            new DateTime('@' . self::getCurrentTStamp())
        );
    }

    /**
     * Returns the flagMaxType
     *
     * @return bool $flagMaxType
     */
    public function getFlagMaxType()
    {
        return $this->flagMaxType;
    }

    /**
     * Returns the flagMaxType
     *
     * @return bool $flagMaxType
     */
    public function isFlagMaxType()
    {
        return $this->getFlagMaxType();
    }

    /**
     * Sets the flagMaxType
     *
     * @param bool $flagMaxType
     * @return void
     */
    public function setFlagMaxType($flagMaxType)
    {
        $this->flagMaxType = $flagMaxType;
    }

    /**
     * Returns the maxCount
     *
     * @return int $maxCount
     */
    public function getMaxCount()
    {
        return $this->maxCount;
    }

    /**
     * Sets the maxCount
     *
     * @param int $maxCount
     * @return void
     */
    public function setMaxCount($maxCount)
    {
        $this->maxCount = $maxCount;
    }


    /**
     * Returns the maxLate
     *
     * @return DateTime|null $maxLate
     */
    public function getMaxLate()
    {
        return $this->maxLate;
    }

    /**
     * Sets the maxLate
     *
     * @param DateTime|null $maxLate
     * @return void
     */
    public function setMaxLate($maxLate)
    {
        $this->maxLate = (($maxLate !== null) ?
            clone $maxLate :
            null);
    }

    /**
     * Returns the userCompareFunction
     *
     * @return string $userCompareFunction
     */
    public function getUserCompareFunction()
    {
        return $this->userCompareFunction;
    }

    /**
     * Sets the userCompareFunction
     *
     * @param string $userCompareFunction
     * @return void
     */
    public function setUserCompareFunction(string $userCompareFunction)
    {
        $this->userCompareFunction = $userCompareFunction;
    }

}
