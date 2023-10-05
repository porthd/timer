<?php

declare(strict_types=1);

namespace Porthd\Timer\Tests\Unit\GlobalUtilities;

use PHPUnit\Framework\TestCase;
use Porthd\Timer\Utilities\ConfigurationUtility;

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


class ConfigurationUtilityTest extends TestCase
{
    /**
     * the ultimate green test
     * @test
     */
    public function checkIfIAmGreen()
    {
        $this->assertEquals((true), (true), 'I should an evergreen, but I am incomplete! :-)');
    }



    public function dataProviderExpandNestedArrayGenerateArrayAndReturnBooleanAboutActionSuccess()
    {
        return [
            [
                'message' => 'A empty array will be expanded and get an empty array as leaf.',
                [
                    'globals' => ['a' => ['b' => ['c' => ['d' => ['e' => []]]]]],
                    'flag' => true,
                ],
                [
                    'global' => [],
                    'rest' => [
                        ['a', 'b', 'c', 'd', 'e',], // 'nestList' =>
                    ],
                ],
            ],
            [
                'message' => 'A empty array will be expanded and put a string as  leaf.',
                [
                    'globals' => ['a' => ['b' => ['c' => ['d' => ['e' => 'hallo']]]]],
                    'flag' => true,
                ],
                [
                    'global' => [],
                    'rest' => [
                        ['a', 'b', 'c', 'd', 'e',], // 'nestList' =>
                        'hallo',
                    ],
                ],
            ],
            [
                'message' => 'A empty array will be expanded and put a array as  leaf.',
                [
                    'globals' => ['a' => ['b' => ['c' => ['d' => ['e' => ['klaus' => 'hallo']]]]]],
                    'flag' => true,
                ],
                [
                    'global' => [],
                    'rest' => [
                        ['a', 'b', 'c', 'd', 'e',], // 'nestList' =>
                        ['klaus' => 'hallo'],
                    ],
                ],
            ],
            [
                'message' => 'A not empty ant not fitting array will be expanded and put a string as  leaf.',
                [
                    'globals' => [
                        'x' => [],
                        'y' => ['z' => []],
                        'a' => ['b' => ['c' => ['d' => ['e' => 'hallo']]]],
                    ],
                    'flag' => true,
                ],
                [
                    'global' => ['x' => [],
                        'y' => ['z' => []],
                    ],
                    'rest' => [
                        ['a', 'b', 'c', 'd', 'e',], // 'nestList' =>
                        'hallo',
                    ],
                ],
            ],
            [
                'message' => 'A not empty ant partial array will be expanded and put a string as  leaf.',
                [
                    'globals' => [
                        'y' => ['z' => []],
                        'a' => ['b' => ['c' => ['d' => ['e' => 'hallo']]]],
                    ],
                    'flag' => true,
                ],
                [
                    'global' => [
                        'y' => ['z' => []],
                        'a' => [],
                    ],
                    'rest' => [
                        ['a', 'b', 'c', 'd', 'e',], // 'nestList' =>
                        'hallo',
                    ],
                ],
            ],
            [
                'message' => 'A not empty ant partial array will be expanded and put a string as  leaf.',
                [
                    'globals' => [
                        'y' => ['z' => []],
                        'a' => ['b' => ['c' => ['d' => ['e' => 'hallo']]]],
                    ],
                    'flag' => true,
                ],
                [
                    'global' => [
                        'y' => ['z' => []],
                        'a' => ['b' => []],
                    ],
                    'rest' => [
                        ['a', 'b', 'c', 'd', 'e',], // 'nestList' =>
                        'hallo',
                    ],
                ],
            ],

            [
                'message' => 'A not array will not be cahnged and return a flase-Flag.',
                [
                    'globals' => 'hallo',
                    'flag' => false,
                ],
                [
                    'global' => 'hallo',
                    'rest' => [
                        ['a', 'b', 'c', 'd', 'e',], // 'nestList' =>
                    ],
                ],
            ],
            [
                'message' => 'A nested filled array will not be executed and returns a false-flag.',
                [
                    'globals' => ['a' => ['b' => ['c' => ['d' => ['e' => ['klaus' => 'hallo']]]]]],
                    'flag' => false,
                ],
                [
                    'global' => ['a' => ['b' => ['c' => ['d' => ['e' => ['klaus' => 'hallo']]]]]],
                    'rest' => [
                        ['a', 'b', 'c', 'd', 'e',], // 'nestList' =>
                        ['horst' => 'hallo'],
                    ],
                ],
            ],
            [
                'message' => 'A nested array with an unfilled leaf will not be executed and returns a true-flag.',
                [
                    'globals' => ['a' => ['b' => ['c' => ['d' => ['e' => ['horst' => 'hallo']]]]]],
                    'flag' => true,
                ],
                [
                    'global' => ['a' => ['b' => ['c' => ['d' => ['e' => []]]]]],
                    'rest' => [
                        ['a', 'b', 'c', 'd', 'e',], // 'nestList' =>
                        ['horst' => 'hallo'],
                    ],
                ],
            ],

        ];
    }

    /**
     * Id on't work currently, because of dependencys to TYPO3-Framework 20190315
     *
     * @dataProvider dataProviderExpandNestedArrayGenerateArrayAndReturnBooleanAboutActionSuccess
     * @test
     */
    public function expandNestedArrayGenerateArrayAndReturnBooleanAboutActionSuccess($message, $expects, $params)
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or emopty dataprovider');
        } else {
            $myGlobal = $params['global'];
            $flag = ConfigurationUtility::expandNestedArray($myGlobal, ...$params['rest']);
            $this->assertSame(
                json_encode($expects['globals']),
                json_encode($myGlobal),
                $message
            );
            $this->assertSame(
                $expects['flag'],
                $flag,
                $message
            );
        }
    }
}
