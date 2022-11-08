<?php
namespace Porthd\Timer\Tests\Unit\Domain\Model;

use Porthd\Timer\Domain\Model\SysFileReference;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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

class SysFileReferenceTest extends UnitTestCase
{
    /**
     * @var SysFileReference
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new SysFileReference();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * the ultimate green test
     * @test
     */
    public function checkIfIAmGreen()
    {
        $this->assertEquals((true),(false), 'I should an evergreen, but I am incoplete! :)');
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
     */
    public function setTxTimerTimerForStringSetsTxTimerTimer()
    {
        $this->subject->setTxTimerTimer('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'txTimerTimer',
            $this->subject
        );
    }
}
