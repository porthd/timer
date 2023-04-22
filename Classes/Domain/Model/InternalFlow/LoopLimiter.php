<?php

namespace Porthd\Timer\Domain\Model\InternalFlow;

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
use DateTime;
use Exception;
use Porthd\Timer\Interfaces\TimerInterface;

/**
 * LoopLimiter is a helpful getter/setter-model for the datapocessors `RangeListQueryProcessor` and `SortListQueryProcessor`.
 * It prevents i.e. an infinite looping while the dataprocessor trys to merge overlapping ranges together.
 * It help to handle the usage of typoscript-arguments for these dataprocessors.
 */
class LoopLimiter
{
    /**
     * datetimeFormat
     *
     * @var string
     */
    protected $datetimeFormat = TimerInterface::TIMER_FORMAT_DATETIME;

    /**
     * flagReserve
     *
     * @var bool
     */
    protected $flagReserve = false;

    /**
     * flagMaxType
     *
     * @var bool
     */
    protected $flagMaxType = true;

    /**
     * flagMaxCount
     *
     * @var bool
     */
    protected $flagMaxCount = true;

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
        return GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp') ?: time();
    }

    /**
     * LoopLimiter constructor generate a stable set of valifd Parameters.
     *
     * @param DateTime|null $date
     * @throws Exception
     */
    public function __construct(DateTime $date = null)
    {
        $this->maxLate = (
            ($date !== null) ?
            $date :
            new DateTime('@' . self::getCurrentTStamp())
        );
    }

    /**
     * Returns the datetimeFormat
     *
     * @return string $datetimeFormat
     */
    public function getDatetimeFormat()
    {
        return $this->datetimeFormat;
    }

    /**
     * Sets the datetimeFormat
     *
     * @param string $datetimeFormat
     * @return void
     */
    public function setDatetimeFormat($datetimeFormat)
    {
        $this->datetimeFormat = (string)$datetimeFormat;
    }

    /**
     * Returns the flagReserve
     *
     * @return bool $flagReserve
     */
    public function getFlagReserve()
    {
        return $this->flagReserve;
    }

    /**
     * Returns the flagReserve
     *
     * @return bool $flagReserve
     */
    public function isFlagReserve()
    {
        return $this->getFlagReserve();
    }

    /**
     * Sets the flagReserve
     *
     * @param bool $flagReserve
     * @return void
     */
    public function setFlagReserve($flagReserve)
    {
        $this->flagReserve = (bool)$flagReserve;
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
        $this->flagMaxType = (bool)$flagMaxType;
    }

    /**
     * Returns the flagMaxCount
     *
     * @return bool $flagMaxCount
     */
    public function getFlagMaxCount()
    {
        return $this->flagMaxCount;
    }

    /**
     * Sets the flagMaxCount
     *
     * @param bool $flagMaxCount
     * @return void
     */
    public function setFlagMaxCount($flagMaxCount)
    {
        $this->flagMaxCount = (bool)$flagMaxCount;
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
        $this->maxCount = (int)$maxCount;
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
    public function setMaxLate(?DateTime $maxLate)
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
