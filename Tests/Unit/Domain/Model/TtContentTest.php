<?php

declare(strict_types=1);

namespace Porthd\Timer\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use Porthd\Timer\Domain\Model\TtContent;
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

class TtContentTest extends UnitTestCase
{
    /**
     * @var TtContent
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TtContent();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
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
