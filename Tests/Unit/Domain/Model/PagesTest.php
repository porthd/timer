<?php
namespace Porthd\Timer\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Porthd\Timer\Domain\Model\Pages;
use ReflectionException;

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

class PagesTest extends TestCase
{
    /**
     * @var Pages
     */
    protected $subject = null;

    protected function setUp():void
    {
        parent::setUp();
        $this->subject = new Pages();
    }


    /**
     * the ultimate green test
     * @test
     */
    public function checkIfIAmGreen()
    {
        $this->assertEquals((true),(true), 'I should an evergreen, but I am incoplete! :)');
    }


    /**
     * @test
     */
    public function getTxTimerTimerReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getTxTimerTimer()
        );
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function setTxTimerTimerForStringSetsTxTimerTimer()
    {

        $this->subject->setTxTimerTimer('Conceived at T3CON10');
        $result =$this->subject->getTxTimerTimer();

        self::assertSame(
            'Conceived at T3CON10',
            $result,
            'the getter will start working correctly.'
        );
        $this->subject->setTxTimerTimer('Conceived at T3CON10 - test 2');
        $result =$this->subject->getTxTimerTimer();

        self::assertSame(
            'Conceived at T3CON10 - test 2',
            $result,
            'the getter will continue working correctly.'
        );
    }
}
