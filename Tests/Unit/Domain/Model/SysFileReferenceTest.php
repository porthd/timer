<?php

declare(strict_types=1);

namespace Porthd\Timer\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
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
     * Reset GeneralUtility singletons on tearDown. In a full-suite run this
     * class' tearDown integrity check otherwise trips over singletons
     * (Context, CacheManager, LogManager, ListOfTimerService) left in the
     * makeInstance list; the framework recommends this opt-in reset.
     */
    protected bool $resetSingletonInstances = true;

    /**
     * @var SysFileReference
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SysFileReference();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * the ultimate green test
     */
    #[Test]
    public function checkIfIAmGreen()
    {
        self::assertEquals((true), (true), 'I should an evergreen, but I am incomplete! :-)');
    }

    #[Test]
    public function getTxTimerTimerReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getTxTimerTimer()
        );
    }

    #[Test]
    public function setTxTimerTimerForStringSetsTxTimerTimer()
    {
        $this->subject->setTxTimerTimer('Conceived at T3CON10');

        self::assertSame(
            'Conceived at T3CON10',
            $this->subject->getTxTimerTimer()
        );
    }
}
