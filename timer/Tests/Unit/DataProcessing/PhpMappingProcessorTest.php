<?php

namespace Porthd\Timer\Tests\Unit\DataProcessing;

use DateTime;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\DataProcessing\PhpMappingProcessor;
use ReflectionClass;
use ReflectionMethod;
use stdClass;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;

const CHECK_VALUE = 'myValue in Test';
const CHECK_TSTAMP = 1720465033;
const CHECK_TSTAMP_TWO = 1720551433;
const CHECK_DEFAULT = 'myDefault in Test';
class GetterSetterClass
{
    /**
     * @var string
     */
    protected $value = CHECK_VALUE;
    protected $timestamp = CHECK_TSTAMP;

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue(string $value): GetterSetterClass
    {
        $this->value = $value;
        return $this;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

}

class GetterSetterParentClass
{
    /**
     * @var GetterSetterClass
     */
    protected $parent;
    protected $timestamp = CHECK_TSTAMP_TWO;

    public function __construct()
    {
        $this->parent = new GetterSetterClass();
    }

    public function getParent(): GetterSetterClass
    {
        return $this->parent;
    }

    public function setParent(GetterSetterClass $parent): void
    {
        $this->parent = $parent;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }


}

class PhpMappingProcessorTest extends TestCase
{
    /**
     * @var PhpMappingProcessor
     */
    protected $subject = null;


    protected function setUp(): void
    {
        parent::setUp();
        $yamlFileLoader = $this->getMockBuilder(YamlFileLoader::class)
            ->setMockClassName('YamlFileLoader')
            ->getMock();
        $cache = new NullFrontend('Testing');
        $this->subject = new PhpMappingProcessor($cache, $yamlFileLoader);
        //        error_reporting(E_ALL);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected static function getMethod($name): ReflectionMethod
    {
        // $method->setAccessible(true); // Use this if you are running PHP older than 8.1.0
        return (new ReflectionClass(PhpMappingProcessor::class))->getMethod($name);
    }

    /**
     * the ultimate green test
     * @test
     */
    public function checkIfIAmGreen()
    {
        $this->assertEquals((true), (true), 'I should an evergreen, but I am incomplete! :-)');
    }


    public static function dataProviderSolveMappingTestedToExtractDataFromOriginArrayOrGetterSetterOrObject()
    {
        $result = [];
        $helpGetterSetter = new GetterSetterClass();
        $helpGetterSetterParent = new GetterSetterParentClass();
        $helpObj = new stdClass();
        $helpObj->value = CHECK_VALUE;
        $helpObj->parent = new stdClass();
        $helpObj->parent->value = CHECK_VALUE;
        $helpAry = [
            'value' => CHECK_VALUE,
            'parent' => ['value' => CHECK_VALUE,],
        ];

        $dummyPath = 'meinPath.doof.hello';
        foreach (['my origin', ['my origin'], $helpGetterSetter, $helpObj] as $myOrigin) {
            $item = [
                'message' => 'The finish-step will return the value `' . print_r($myOrigin, true) . '`.',
                'expects' => [
                    'result' => $myOrigin,
                ],
                'params' => [
                    'path' => '',
                    'origin' => $myOrigin,
                    'refPath' => $dummyPath,
                    'default' => 'defaultValue',
                ],
            ];
            $result[] = $item;
            // lets remove default in the parameter
            $item['params']['default'] = null;
            $result[] = $item;
        }

        foreach ([
                     ['path' => 'value', 'origin' => $helpAry],
                     ['path' => 'value', 'origin' => $helpObj],
                     ['path' => 'value', 'origin' => $helpGetterSetter],
                 ] as $mySet) {
            $item = [
                'message' => 'The last step in the pathsearch will return the value `' . CHECK_VALUE . '` defined by in path .`' .
                    $mySet['path'] . '` in the object `' . print_r($mySet['origin'], true) . '`.',
                'expects' => [
                    'result' => CHECK_VALUE,
                ],
                'params' => [
                    'path' => $mySet['path'],
                    'origin' => $mySet['origin'],
                    'refPath' => $mySet['path'],
                    'default' => CHECK_DEFAULT,
                ],
            ];
            $result[] = $item;
            // lets remove default in the parameter
            $item['params']['default'] = null;
            $result[] = $item;
        }

        foreach ([
                     ['path' => 'parent.value', 'origin' => $helpAry],
                     ['path' => 'parent.value', 'origin' => $helpObj],
                     ['path' => 'parent.value', 'origin' => $helpGetterSetterParent],
                 ] as $mySet) {
            $item = [
                'message' => 'The last step in the pathsearch will return the value `' . CHECK_VALUE . '` defined by in path .`' .
                    $mySet['path'] . '` in the object `' . print_r($mySet['origin'], true) . '`.',
                'expects' => [
                    'result' => CHECK_VALUE,
                ],
                'params' => [
                    'path' => $mySet['path'],
                    'origin' => $mySet['origin'],
                    'refPath' => $mySet['path'],
                    'default' => CHECK_DEFAULT,
                ],
            ];
            $result[] = $item;
            // lets remove default in the parameter
            $item['params']['default'] = null;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @dataProvider dataProviderSolveMappingTestedToExtractDataFromOriginArrayOrGetterSetterOrObject
     * @test
     */
    public function solveMappingTestedToExtractDataFromOriginArrayOrGetterSetterOrObject(
        $message,
        $expects,
        $params
    )
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $reflMethod = self::getMethod('solveMapping');
            if ($params['default'] === null) {
                unset($params['default']);
            }
            $result = $reflMethod->invokeArgs($this->subject, $params);
            $this->assertEquals(
                $expects['result'],
                $result,
                'Step 1: ' . $message
            );

        }
    }

    public static function dataProviderGetStringFromResolvedDatasAndStringTestWithVariousString()
    {
        $helpGetterSetterParent = new GetterSetterParentClass();
        $helpObj = new stdClass();
        $helpObj->value = CHECK_VALUE;
        $helpObj->parent = new stdClass();
        $helpObj->parent->value = CHECK_VALUE;
        $helpAry = [
            'value' => CHECK_VALUE,
            'parent' => ['value' => CHECK_VALUE,],
        ];

        $result = [];
        $result[] = [
            'message' => 'An empty string of input result in an empty string.',
            'expects' => [
                'result' => '',
            ],
            'params' => [
                'input' => '',
                'origin' => '',
            ],
        ];
        foreach ([
                     ['', ''],
                     ['prefix', ''],
                     ['', 'postfix'],
                     ['prefix', 'postfix'],
                 ] as $extend) {
            foreach ([
                         ['input' => $extend[0] . '@parent.value@' . $extend[1], 'origin' => $helpAry, 'output' => $extend[0] . CHECK_VALUE . $extend[1],],
                         ['input' => $extend[0] . '@parent.value@' . $extend[1], 'origin' => $helpObj, 'output' => $extend[0] . CHECK_VALUE . $extend[1],],
                         ['input' => $extend[0] . '@parent.value@' . $extend[1], 'origin' => $helpGetterSetterParent, 'output' => $extend[0] . CHECK_VALUE . $extend[1],],
                     ] as $mySet) {
                $item = [
                    'message' => 'The step has to resolve a simple string `' . $mySet['input'] .
                        '` to the value `' . CHECK_VALUE . '` plus perhaps some additinal stuff. The ordigin is: ' .
                        print_r($mySet['origin'], true),
                    'expects' => [
                        'result' => $mySet['output'],
                    ],
                    'params' => [
                        'input' => $mySet['input'],
                        'origin' => $mySet['origin'],
                    ],
                ];
                $result[] = $item;
            }
        }
        foreach ([
                     ['', ''],
                     ['prefix', ''],
                     ['', 'postfix'],
                     ['prefix', 'postfix'],
                 ] as $extend) {
            foreach ([
                         ['input' => $extend[0] . '@parent.value@' . $extend[1] . '@parent.value@' . $extend[1], 'origin' => $helpAry, 'output' => $extend[0] . CHECK_VALUE . $extend[1] . CHECK_VALUE . $extend[1],],
                         ['input' => $extend[0] . '@parent.value@' . $extend[1] . '@parent.value@' . $extend[1], 'origin' => $helpObj, 'output' => $extend[0] . CHECK_VALUE . $extend[1] . CHECK_VALUE . $extend[1],],
                         ['input' => $extend[0] . '@parent.value@' . $extend[1] . '@parent.value@' . $extend[1], 'origin' => $helpGetterSetterParent, 'output' => $extend[0] . CHECK_VALUE . $extend[1] . CHECK_VALUE . $extend[1],],
                     ] as $mySet) {
                $item = [
                    'message' => 'The step has to resolve a simple string `' . $mySet['input'] .
                        '` to the value `' . CHECK_VALUE . '` plus perhaps some additinal stuff. The ordigin is: ' .
                        print_r($mySet['origin'], true),
                    'expects' => [
                        'result' => $mySet['output'],
                    ],
                    'params' => [
                        'input' => $mySet['input'],
                        'origin' => $mySet['origin'],
                    ],
                ];
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * @dataProvider dataProviderGetStringFromResolvedDatasAndStringTestWithVariousString
     * @test
     */
    public function getStringFromResolvedDatasAndStringTestWithVariousString(
        $message,
        $expects,
        $params
    )
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $reflMethod = self::getMethod('getStringFromResolvedDatasAndString');
            $result = $reflMethod->invokeArgs($this->subject, $params);
            $this->assertEquals(
                $expects['result'],
                $result,
                'Step 2: ' . $message
            );

        }
    }

    public static function dataProviderExecuteDefinitionOfMethodFromStringTestWithVariousStrings()
    {
        $helpGetterSetterParent = new GetterSetterParentClass();
        $helpObj = new stdClass();
        $helpObj->value = CHECK_VALUE;
        $helpObj->timestamp = CHECK_TSTAMP_TWO;
        $helpObj->parent = new stdClass();
        $helpObj->parent->value = CHECK_VALUE;
        $helpObj->parent->timestamp = CHECK_TSTAMP;
        $helpAry = [
            'value' => CHECK_VALUE,
            'timestamp' => CHECK_TSTAMP_TWO,
            'parent' => ['value' => CHECK_VALUE, 'timestamp' => CHECK_TSTAMP,],
        ];

        $result = [];
        foreach ([$helpAry, $helpObj, $helpGetterSetterParent] as $key => $origin) {
            $result[] = [
                'message' => 'Resolve a nested function',
                'expects' => [
                    'result' => (new DateTime('@' . CHECK_TSTAMP_TWO))->format('Y-m-d H:i:s'),
                ],
                'params' => [
                    'flagFunc' => true,
                    'input' => "date_format(date_create('\@@timestamp@'), 'Y-m-d H:i:s')",
                    'origin' => $origin,
                ],
            ];
        }
        foreach ([$helpAry, $helpObj, $helpGetterSetterParent] as $key => $origin) {
            $result[] = [
                'message' => 'Resolve a nested function with nested origina',
                'expects' => [
                    'result' => (new DateTime('@' . CHECK_TSTAMP))->format('Y-m-d H:i:s'),
                ],
                'params' => [
                    'flagFunc' => true,
                    'input' => "date_format(date_create('\@@parent.timestamp@'), 'Y-m-d H:i:s')",
                    'origin' => $origin,
                ],
            ];
        }
        return $result;
    }

    /**
     * @test
     * @dataProvider dataProviderExecuteDefinitionOfMethodFromStringTestWithVariousStrings
     */
    public function executeDefinitionOfMethodFromStringTestWithVariousStrings(
        $message,
        $expects,
        $params
    )
    {
        if (!isset($expects) && empty($expects)) {
            $this->assertSame(true, true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $reflMethod = self::getMethod('executeDefinitionOfMethodFromString');
            $result = $reflMethod->invokeArgs($this->subject, $params);
            $this->assertEquals(
                $expects['result'],
                $result,
                'Step 3: ' . $message
            );

        }
    }
}
