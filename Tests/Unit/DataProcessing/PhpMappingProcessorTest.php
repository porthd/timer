<?php

namespace Porthd\Timer\Tests\Unit\DataProcessing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\DataProcessing\PhpMappingProcessor;
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
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $yamlFileLoader = $this->getMockBuilder(YamlFileLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cache = new NullFrontend('Testing');
        $this->subject = new PhpMappingProcessor($cache, $yamlFileLoader);
        //        error_reporting(E_ALL);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected static function getMethod($name): \ReflectionMethod
    {
        // $method->setAccessible(true); // Use this if you are running PHP older than 8.1.0
        return (new \ReflectionClass(PhpMappingProcessor::class))->getMethod($name);
    }

    // PURPOSE: Invoke a reflected method whose signature declares by-reference
    //          parameters (e.g. `&$origin`, `&$refPath`) without triggering the
    //          PHP 8 "must be passed by reference, value given" warning.
    //
    // ADVANTAGES:
    //   - Binds every argument to the caller's own array storage, so
    //     ReflectionMethod::invokeArgs() receives real references.
    //   - Works uniformly for the mixed value/reference signatures used here
    //     (passing a reference to a by-value parameter is harmless).
    //
    // PRECONDITIONS (data requirements):
    //   - $params order/keys match the target method's parameter list, exactly
    //     as ReflectionMethod::invokeArgs() expects.
    //
    // EDGE CASES:
    //   - Empty $params returns whatever the method yields for no arguments.
    private function invokeArgsByRef(\ReflectionMethod $method, array $params)
    {
        $refArgs = [];
        foreach ($params as $key => $ignored) {
            $refArgs[$key] = &$params[$key];
        }
        return $method->invokeArgs($this->subject, $refArgs);
    }

    /**
     * the ultimate green test
     */
    #[Test]
    public function checkIfIAmGreen()
    {
        self::assertEquals((true), (true), 'I should an evergreen, but I am incomplete! :-)');
    }

    public static function dataProviderSolveMappingTestedToExtractDataFromOriginArrayOrGetterSetterOrObject()
    {
        $result = [];
        $helpGetterSetter = new GetterSetterClass();
        $helpGetterSetterParent = new GetterSetterParentClass();
        $helpObj = new \stdClass();
        $helpObj->value = CHECK_VALUE;
        $helpObj->parent = new \stdClass();
        $helpObj->parent->value = CHECK_VALUE;
        $helpAry = [
            'value' => CHECK_VALUE,
            'parent' => ['value' => CHECK_VALUE],
        ];

        $dummyPath = 'meinPath.doof.hello';
        foreach (['my origin', ['my origin'], $helpGetterSetter, $helpObj] as $myOrigin) {
            $item = [
                'The finish-step will return the value `' . print_r($myOrigin, true) . '`.',
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
                'The last step in the pathsearch will return the value `' . CHECK_VALUE . '` defined by in path .`' .
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
                'The last step in the pathsearch will return the value `' . CHECK_VALUE . '` defined by in path .`' .
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

    #[DataProvider('dataProviderSolveMappingTestedToExtractDataFromOriginArrayOrGetterSetterOrObject')]
    #[Test]
    public function solveMappingTestedToExtractDataFromOriginArrayOrGetterSetterOrObject(
        $message,
        $expects,
        $params
    ) {
        if (!isset($expects) && empty($expects)) {
            self::assertTrue(true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $reflMethod = self::getMethod('solveMapping');
            if ($params['default'] === null) {
                unset($params['default']);
            }
            $result = $this->invokeArgsByRef($reflMethod, $params);
            self::assertEquals(
                $expects['result'],
                $result,
                'Step 1: ' . $message
            );

        }
    }

    public static function dataProviderGetStringFromResolvedDatasAndStringTestWithVariousString()
    {
        $helpGetterSetterParent = new GetterSetterParentClass();
        $helpObj = new \stdClass();
        $helpObj->value = CHECK_VALUE;
        $helpObj->parent = new \stdClass();
        $helpObj->parent->value = CHECK_VALUE;
        $helpAry = [
            'value' => CHECK_VALUE,
            'parent' => ['value' => CHECK_VALUE],
        ];

        $result = [];
        $result[] = [
            'An empty string of input result in an empty string.',
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
                ['input' => $extend[0] . '@parent.value@' . $extend[1], 'origin' => $helpAry, 'output' => $extend[0] . CHECK_VALUE . $extend[1]],
                ['input' => $extend[0] . '@parent.value@' . $extend[1], 'origin' => $helpObj, 'output' => $extend[0] . CHECK_VALUE . $extend[1]],
                ['input' => $extend[0] . '@parent.value@' . $extend[1], 'origin' => $helpGetterSetterParent, 'output' => $extend[0] . CHECK_VALUE . $extend[1]],
            ] as $mySet) {
                $item = [
                    'The step has to resolve a simple string `' . $mySet['input'] .
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
                ['input' => $extend[0] . '@parent.value@' . $extend[1] . '@parent.value@' . $extend[1], 'origin' => $helpAry, 'output' => $extend[0] . CHECK_VALUE . $extend[1] . CHECK_VALUE . $extend[1]],
                ['input' => $extend[0] . '@parent.value@' . $extend[1] . '@parent.value@' . $extend[1], 'origin' => $helpObj, 'output' => $extend[0] . CHECK_VALUE . $extend[1] . CHECK_VALUE . $extend[1]],
                ['input' => $extend[0] . '@parent.value@' . $extend[1] . '@parent.value@' . $extend[1], 'origin' => $helpGetterSetterParent, 'output' => $extend[0] . CHECK_VALUE . $extend[1] . CHECK_VALUE . $extend[1]],
            ] as $mySet) {
                $item = [
                    'The step has to resolve a simple string `' . $mySet['input'] .
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

    #[DataProvider('dataProviderGetStringFromResolvedDatasAndStringTestWithVariousString')]
    #[Test]
    public function getStringFromResolvedDatasAndStringTestWithVariousString(
        $message,
        $expects,
        $params
    ) {
        if (!isset($expects) && empty($expects)) {
            self::assertTrue(true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $reflMethod = self::getMethod('getDataOrStringFromResolvedDatasAndString');
            $result = $this->invokeArgsByRef($reflMethod, $params);
            self::assertEquals(
                $expects['result'],
                $result,
                'Step 2: ' . $message
            );

        }
    }

    public static function dataProviderExecuteDefinitionOfMethodFromStringTestWithVariousStrings()
    {
        $helpGetterSetterParent = new GetterSetterParentClass();
        $helpObj = new \stdClass();
        $helpObj->value = CHECK_VALUE;
        $helpObj->timestamp = CHECK_TSTAMP_TWO;
        $helpObj->parent = new \stdClass();
        $helpObj->parent->value = CHECK_VALUE;
        $helpObj->parent->timestamp = CHECK_TSTAMP;
        $helpAry = [
            'value' => CHECK_VALUE,
            'timestamp' => CHECK_TSTAMP_TWO,
            'parent' => ['value' => CHECK_VALUE, 'timestamp' => CHECK_TSTAMP],
        ];

        $result = [];
        foreach ([$helpAry, $helpObj, $helpGetterSetterParent] as $key => $origin) {
            $result[] = [
                'Resolve a nested function',
                'expects' => [
                    'result' => (new \DateTime('@' . CHECK_TSTAMP_TWO))->format('Y-m-d H:i:s'),
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
                'Resolve a nested function with nested origina',
                'expects' => [
                    'result' => (new \DateTime('@' . CHECK_TSTAMP))->format('Y-m-d H:i:s'),
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

    #[Test]
    #[DataProvider('dataProviderExecuteDefinitionOfMethodFromStringTestWithVariousStrings')]
    public function executeDefinitionOfMethodFromStringTestWithVariousStrings(
        $message,
        $expects,
        $params
    ) {
        if (!isset($expects) && empty($expects)) {
            self::assertTrue(true, 'empty-data at the end of the provider or empty data-provider');
        } else {
            $reflMethod = self::getMethod('executeDefinitionOfMethodFromString');
            $result = $this->invokeArgsByRef($reflMethod, $params);
            self::assertEquals(
                $expects['result'],
                $result,
                'Step 3: ' . $message
            );

        }
    }
}
