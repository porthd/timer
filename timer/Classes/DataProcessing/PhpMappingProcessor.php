<?php

declare(strict_types=1);

namespace Porthd\Timer\DataProcessing;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2022 Dr. Dieter Porth <info@mobger.de>
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

use Porthd\Timer\DataProcessing\Trait\GeneralDataProcessorTrait;
use Porthd\Timer\DataProcessing\Trait\GeneralDataProcessorTraitInterface;
use Porthd\Timer\Exception\MappingException;
use Porthd\Timer\Utilities\CsvYamlJsonMapperUtility;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Fetch records from the database, using the default .select syntax from TypoScript.
 *
 * This way, e.g. a FLUIDTEMPLATE cObject can iterate over the array of records.
 *
 * Example TypoScript configuration:
 *
 */
class PhpMappingProcessor implements DataProcessorInterface, GeneralDataProcessorTraitInterface
{
    use GeneralDataProcessorTrait;
    use LoggerAwareTrait;

    protected const ATTR_OUTPUT_VAR = 'as'; // allowed only part of self::VAL_OUTPUT_FORMAT_LIST
    protected const ATTR_OUTPUT_FORMAT = 'outputformat'; // allowed only part of self::VAL_OUTPUT_FORMAT_LIST
    protected const VAL_OUTPUT_FORMAT_YAML = 'yaml';
    protected const VAL_OUTPUT_FORMAT_JSON = 'json';
    protected const VAL_OUTPUT_FORMAT_ARRAY = 'array';
    protected const VAL_OUTPUT_FORMAT_LIST = [
        self::VAL_OUTPUT_FORMAT_YAML,
        self::VAL_OUTPUT_FORMAT_JSON,
        self::VAL_OUTPUT_FORMAT_ARRAY,
    ];

    protected const DEFAULT_LIMITER_INPUT = self::CHECKER_FUNC_PART;

    protected const DEFAULT_LIMITER_DATA = [

        self::ATTR_LIMITER_SUB_PATH => self::CHECKER_FUNC_PATH,
        self::ATTR_LIMITER_SUB_PART => self::CHECKER_FUNC_PART,
        self::ATTR_LIMITER_SUB_PARAMS => self::CHECKER_FUNC_PARAMETER,
    ];
    protected const ATTR_LIMITER_MAIN = 'limiter';
    protected const ATTR_LIMITER_INPUTPART = 'inputPart';
    protected const ATTR_LIMITER_OUTPUT = 'output';
    protected const ATTR_INPUT_TYPE = 'inputType';
    protected const ATTR_LIMITER_TYPE_LIST = [
        self::VALUE_LIMITER_TYPE_RECORD,
        self::VALUE_LIMITER_TYPE_ROWS,
    ];
    protected const ATTR_INPUT_ORIGIN = 'inputOrigin';
    protected const DEFAULT_INPUT_ORIGIN = '_all';
    protected const ATTR_CONFIG_FILE = 'configFile';
    protected const ATTR_MAPPING_OUTPUT = 'output';

    protected const VALUE_LIMITER_TYPE_RECORD = 'record';
    protected const VALUE_LIMITER_TYPE_ROWS = 'rows';
    protected const ATTR_LIMITER_SUB_PATH = 'path';
    protected const ATTR_LIMITER_SUB_PART = 'part';
    protected const ATTR_LIMITER_SUB_DEFAULTPART = 'defpart';
    protected const ATTR_LIMITER_SUB_PARAMS = 'params';
    protected const ATTR_LIMITER_SUB_START = 'start';
    protected const ATTR_LIMITER_SUB_END = 'end';
    protected const ATTR_LIMITER_SUB_ESCAPE = 'escape';
    protected const ATTR_LIMITER_SUB_DYNFUNC = 'dynfunc';
    protected const ATTR_LIMITER_SUB_STATFUNC = 'statfunc';
    protected const CHECKER_FUNC_PATH = '@'; // only one allowed, but escapable by '\,' in strings
    protected const CHECKER_FUNC_PART = '.'; // only one allowed, but escapable by '\,' in strings
    protected const CHECKER_FUNC_PATHDEFAULT = '#'; // only one allowed, but escapable by '\,' in strings
    protected const CHECKER_FUNC_PARAMETER = ','; // only one allowed, but escapable by '\,' in strings
    protected const CHECKER_FUNC_START = '('; // only one allowed, but escapable by '\(' in strings
    protected const CHECKER_FUNC_END = ')'; // only one allowed, but escapable by '\)' in strings
    protected const CHECKER_GENERAL_ESCAPE = '\\'; // only one allowed, but escapable by '\(' in strings
    protected const CHECKER_FUNC_DYNAMIC = '->'; // only one allowed, but escapable by '\::' in strings
    protected const CHECKER_FUNC_STATIC = '::'; // only one allowed, but escapable by '\::' in strings
    protected const OUTPUT_REMAPPED_DATA = 'remapped';


    /**
     * @var string[]
     */
    protected $lim = [
        self::ATTR_LIMITER_SUB_START => self::CHECKER_FUNC_START,
        self::ATTR_LIMITER_SUB_END => self::CHECKER_FUNC_END,
        self::ATTR_LIMITER_SUB_ESCAPE => self::CHECKER_GENERAL_ESCAPE,
        self::ATTR_LIMITER_SUB_DYNFUNC => self::CHECKER_FUNC_DYNAMIC,
        self::ATTR_LIMITER_SUB_STATFUNC => self::CHECKER_FUNC_STATIC,
        self::ATTR_LIMITER_SUB_PATH => self::CHECKER_FUNC_PATH,
        self::ATTR_LIMITER_SUB_PART => self::CHECKER_FUNC_PART,
        self::ATTR_LIMITER_SUB_PARAMS => self::CHECKER_FUNC_PARAMETER,
        self::ATTR_LIMITER_SUB_DEFAULTPART => self::CHECKER_FUNC_PATHDEFAULT,
    ];
    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @var ContentObjectRenderer
     */
    protected $cObj;

    /**
     * @var mixed
     */
    protected $origin;

    /**
     * @var YamlFileLoader
     */
    protected $yamlFileLoader;

    /**
     * @var bool
     */
    protected $flagSwitch = false;

    /**
     * @var array<mixed>
     */
    protected $config = [];

    /**
     * @param FrontendInterface $cache
     * @param YamlFileLoader $yamlFileLoader
     */
    public function __construct(
        FrontendInterface $cache,
        YamlFileLoader    $yamlFileLoader
    )
    {
        $this->cache = $cache;
        $this->yamlFileLoader = $yamlFileLoader;
    }

    /**
     * Fetches records from the database as an array
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array<mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<mixed> $processorConfiguration The configuration of this processor
     * @param array<mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     *
     * @return array<mixed> the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array                 $contentObjectConfiguration,
        array                 $processorConfiguration,
        array                 $processedData
    )
    {
        $this->setParameter($cObj);

        // import the config from a file
        $configFile = $this->getArgument($processorConfiguration, self::ATTR_CONFIG_FILE);
        if (!empty($configFile)) {
            $this->config = CustomTimerUtility::readListFromFileOrUrl($configFile, $this->yamlFileLoader);
            if (empty($this->config)) {
                throw new MappingException(
                    'There were not found any instructions for config in the file `' . $configFile . '`. ',
                    1719728202
                );

            }
        }

        // Main level of configuration

        $outputFieldName = (string)$this->getArgument($processorConfiguration, self::ATTR_OUTPUT_VAR, self::OUTPUT_REMAPPED_DATA);
        // prepare caching
        [$pageUid, $pageContentOrElementUid, $cacheIdentifier] = $this->generateCacheIdentifier(
            $processedData,
            $outputFieldName
        );
        $myResult = $this->cache->get($cacheIdentifier);
        $yamlStartKey = '';
        $cacheCalc = false;
        $cacheTime = 0;
        if ($myResult === false) {
            [$cacheTime, $cacheCalc] = $this->detectCacheTimeSet($cObj, $processorConfiguration);
        }
        // generate all needed attributes
        $outputFormat = (string)$this->getArgument($processorConfiguration, self::ATTR_OUTPUT_FORMAT, self::VAL_OUTPUT_FORMAT_JSON);
        if (!in_array($outputFormat, self::VAL_OUTPUT_FORMAT_LIST, true)) {
            throw new MappingException(
                'The format for `' . self::ATTR_OUTPUT_VAR . '` must contain one of the following values: ' .
                print_r(self::VAL_OUTPUT_FORMAT_LIST, true),
                1719727884
            );
        }
        $dataType = $this->getArgument($processorConfiguration, self::ATTR_INPUT_TYPE);
        [$limiterInput, $limiterData,] = $this->getLimiterArgument($processorConfiguration);
        if (!empty($limiterData)) {
            // override the default-definition
            $this->lim = array_merge($this->lim, $limiterData);
        }
        $inputKey = (string)$this->getArgument($processorConfiguration, self::ATTR_INPUT_ORIGIN, self::DEFAULT_INPUT_ORIGIN);
        if ((empty($inputKey)) ||
            (
                ($inputKey !== self::DEFAULT_INPUT_ORIGIN) &&
                (!isset($processedData[$inputKey]))
            )
        ) {
            $pdKeys = array_keys($processedData);
            throw new MappingException(
                'The parameter `' . self::ATTR_INPUT_ORIGIN . '` is missing or ' .
                'There are not fount any data with the key `' . print_r($inputKey, true) . '`. ' .
                'The dataprocessor only knows the folloging keys: ' . print_r($pdKeys, true) . '. ' .
                'Check your typoscript. Error in Writing, Typecasting ...? ',
                1719728122
            );
        }

        $mapping = $this->getArgument($processorConfiguration, self::ATTR_MAPPING_OUTPUT);
        // ich gehe davon aus, dass keinen Punkt.Notation bei den Key zu finden ist.
        if ($mapping === null) {
            throw new MappingException(
                'A definition (`' . self::ATTR_MAPPING_OUTPUT . '`) is missing which defines the mapping in typoscript or in the yaml-file.',
                1719728152
            );
        }


        // use variable by reference to prevent the array-copy-actions of PHP bei definig the input-data
        $listInput = array_filter(
            array_map(
                'trim',
                explode($limiterInput, $inputKey)
            )
        );
        $data = &$processedData;
        if ($inputKey !== self::DEFAULT_INPUT_ORIGIN) {
            foreach ($listInput as $inputKey) {
                if (!isset($data[$inputKey])) {
                    throw new MappingException(
                        'The inputpath `' . $limiterInput . '` failt for the key `' . $inputKey . '`. ' .
                        'Check your typoScript and the expected data-structure.',
                        1719728217
                    );

                }
                $data = &$data[$inputKey];
            }
        }
        // reference of $data has changed


        // the results derived from the mapping and the origin-datas
        $result = [];
        // The data are type of record or list of rows
        if ($dataType === self::VALUE_LIMITER_TYPE_RECORD) {

            $result = $this->resolveMapping($mapping, $data);
        } elseif ($dataType === self::VALUE_LIMITER_TYPE_ROWS) {
            foreach ($data as $key => $dataRow) {
                $result[$key] = $this->resolveMapping($mapping, $dataRow);
            }
        } else {
            throw new MappingException(
                'The type of input `' . self::ATTR_INPUT_TYPE .
                '` is not definied (`' . print_r($dataType, true) . '`) or the type does not fit the allowed ' .
                'entities:  ' . print_r(self::ATTR_LIMITER_TYPE_LIST, true),
                1719728258
            );

        }


        // the caching-times is defined or depends on default-value
        if (($cacheCalc !== false) ||
            ($cacheTime > 0)
        ) {
            $myTags = [
                'pages_' . $pageUid,
                'pages',
                'simpleMapping_' . $pageContentOrElementUid,
                'simpleMapping',
            ];
            $myResult = [
                'as' => $outputFieldName,
                'format' => $outputFormat,
                'simpleMapping' => $result,
            ];
            // clear page-cache
            // todo build a singleton, to call this only once in a request
            if ($cacheTime > 0) {
                $this->cache->set($cacheIdentifier, $myResult, $myTags, $cacheTime);
            } else {
                $this->cache->set($cacheIdentifier, $myResult, $myTags);
            }
        }
        // 3. realize the output format for the resulting array
        switch ($myResult['format']) {
            case self::VAL_OUTPUT_FORMAT_JSON:
                $processedData[$myResult['as']] = json_encode(
                    $myResult['simpleMapping'],
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                );
                break;
            case self::VAL_OUTPUT_FORMAT_ARRAY:
                $processedData[$myResult['as']] = $myResult['simpleMapping'];
                break;
            case self::VAL_OUTPUT_FORMAT_YAML:
                $processedData[$myResult['as']] = CsvYamlJsonMapperUtility::mapAssoativeArrayToYaml(
                    $myResult['simpleMapping'],
                    $yamlStartKey
                );
                break;
            default:
                throw new MappingException(
                    'The outputformat `' . $myResult['format'] . '` is not correctly defined. ' .
                    'Allowed are only one of the case-sensitive list `' . self::VAL_OUTPUT_FORMAT_JSON . ', ' . self::VAL_OUTPUT_FORMAT_ARRAY . '`.' .
                    'Check the typoscript-configuration of your dataprocessor `' . self::class . '`.',
                    1719728271
                );
        }

        // allow the call of a Dataprocessor in a dataprocessor
        return $processedData;
    }

    /**
     * @param ContentObjectRenderer $cObj
     * @return void
     */
    protected function setParameter(ContentObjectRenderer $cObj)
    {
        $this->cObj = $cObj;
    }


    /**
     * @param array<mixed> $configurationPart
     * @param string $name
     * @param string|null $default
     * @param bool $flagRecursive
     * @return bool|int|mixed|string|null
     */
    protected function getArgument(array &$configurationPart, string $name, string|null $default = null, bool $flagRecursive = true)
    {
        // check the data from the confifile, if it exist.
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        // Reasons to stop this dataprocessor
        $dotName = $name . '.';
        if (array_key_exists($name, $configurationPart)) {
            if ($flagRecursive) {
                $inputFieldName = $this->cObj->stdWrapValue($name, $configurationPart, $default);
            } else {
                $inputFieldName = ((empty($configurationPart[$name]) ? $default : $configurationPart[$name]));
            }
        } elseif (array_key_exists($dotName, $configurationPart)) {
            if ($flagRecursive) {
                $inputFieldName = $this->cObj->stdWrapValue($dotName, $configurationPart, $default);
            } else {
                $inputFieldName = ((empty($configurationPart[$dotName]) ? $default : $configurationPart[$dotName]));
            }
        } else {
            $inputFieldName = $default;
        }
        return $inputFieldName;
    }

    /**
     * @param array<mixed> $processorConfiguration
     * @return array<mixed>
     */
    protected function getLimiterArgument(array &$processorConfiguration): array
    {

        $limiterRaw = $this->getArgument($processorConfiguration, self::ATTR_LIMITER_MAIN);
        if ($limiterRaw === null) {
            $limiterInput = self::DEFAULT_LIMITER_INPUT;
            $limiterData = self::DEFAULT_LIMITER_DATA;
        } else {
            $limiterInput = $this->getArgument($limiterRaw, self::ATTR_LIMITER_INPUTPART, self::CHECKER_FUNC_PART);
            $limiterDataRaw = $this->getArgument($limiterRaw, self::ATTR_LIMITER_OUTPUT);
            $limiterData = self::DEFAULT_LIMITER_DATA;
            if ($limiterDataRaw !== null) {
                $limiterData[self::ATTR_LIMITER_SUB_PARAMS] = ($this->getArgument($limiterData, self::ATTR_LIMITER_SUB_PARAMS) ?? self::CHECKER_FUNC_PARAMETER);
                $limiterData[self::ATTR_LIMITER_SUB_PART] = ($this->getArgument($limiterData, self::ATTR_LIMITER_SUB_PART) ?? self::CHECKER_FUNC_PART);
                $limiterData[self::ATTR_LIMITER_SUB_PATH] = ($this->getArgument($limiterData, self::ATTR_LIMITER_SUB_PATH) ?? self::CHECKER_FUNC_PATH);
            }
        }
        return [$limiterInput, $limiterData];
    }

    /**
     * @param string[] $limiter
     * @return void
     */
    public function updateLimiter(array $limiter)
    {
        $this->lim = array_merge($this->lim, $limiter);
    }

    /**
     * @return string[]
     */
    public function getLimiter(): array
    {
        return $this->lim;
    }

    /**
     * @param mixed $mapping
     * @param mixed $origin The parameter can have every type: object, array, scalar or null
     * @return array|bool|float|int|mixed|string
     * @throws MappingException
     */
    protected function resolveMapping($mapping, &$origin)
    {
        $result = [];
        if (is_array($mapping) || is_object($mapping)) {
            // if $mapping is a defined class, only the public(!) attribute will be used.
            // A stdClass-object will work like an assoziative array.
            foreach ($mapping as $key => $value) {
                $result[$key] = $this->resolveMapping($value, $origin);
            }
        } elseif (is_string($mapping)) {
            $result = $this->checkForFunktion($mapping, $origin);
        } elseif (is_scalar($mapping)) {
            $result = $mapping;
        }
        return $result;
    }

    /**
     * @param string $input
     * @param mixed $origin The parameter can have every type: object, array, scalar or null
     * @return bool|float|int|mixed|string
     * @throws MappingException
     */
    public function checkForFunktion(string $input, &$origin)
    {
        $flagFunc = $this->checkForFunctionDefinition($input);
        if ($flagFunc) {
            return $this->executeDefinitionOfMethodFromString($flagFunc, $input, $origin);
        }
        // You have no method. It is only an ordinary string.
        // The string may contain the pathinformation and/or string-informations

        // Get String with resolved Data
        return $this->getDataOrStringFromResolvedDatasAndString($input, $origin);

    }

    /**
     * @param string|null $path
     * @param mixed $origin The parameter can have every type: object, array, scalar or null
     * @param string $refPath
     * @param string|int|float|bool|null $default
     * @return mixed
     * @throws MappingException
     */
    protected function solveMapping(string|null $path, &$origin, string &$refPath, string|int|float|bool|null $default = null)
    {
        // resolve last step in recursion
        if (empty($path)) {
            if (is_object($origin)) {
                return clone $origin;
            }

            if ((is_string($origin)) &&
                (strpos($origin, 'LLL:') === 0)
            ) {
                return LocalizationUtility::translate($origin);
            }

            return $origin;
        }
        // resolve one step in recursion
        if ($origin === null) {
            throw new MappingException(
                'There is no origin defined. Something unexpected went wrong. ',
                1720122142
            );
        }
        if (strpos($path, $this->lim[self::ATTR_LIMITER_SUB_PART]) !== false) {
            $help = explode($this->lim[self::ATTR_LIMITER_SUB_PART], $path, 2);
            $rest = isset($help[1]) ? $help[1] : '';
            $partPath = $help[0];
        } else {
            $rest = '';
            $partPath = $path;
        }
        // origin as array
        if (is_array($origin)) {
            if (!isset($origin[$partPath])) {
                if ($default !== null) {
                    return $default;
                }
                throw new MappingException(
                    'The expected value does not exist. Check your path `' . $refPath . '` in your origin `' .
                    print_r($origin) . '`. ',
                    1720122692
                );

            }
            if (isset($origin[$partPath])) {
                return $this->solveMapping($rest, $origin[$partPath], $refPath, $default);
            }
            if ($default !== null) {
                return $default;
            }
            throw new MappingException(
                'The array didn`t contain the expected key. The mapping-instruction`' . $refPath .
                '` could not resolved. ',
                1720124652
            );
        }
        // origina as object - simple or getter(Setter)
        if ((is_object($origin))) {
            if (method_exists($origin, 'get' . ucfirst($partPath))) {
                $method = 'get' . ucfirst($partPath);
                $myOrigin = $origin->$method();
                return $this->solveMapping($rest, $myOrigin, $refPath, $default);
            }
            if (isset($origin->$partPath)) {
                return $this->solveMapping($rest, $origin->$partPath, $refPath, $default);
            }
        }
        // default defined
        if ($default !== null) {
            return $default;
        }
        //
        throw new MappingException(
            'The object wasnt  a getter,`a stdClass or an array. Ther wasn`t defined a default-value. ' .
            'The mapping-instruction`' . $refPath . '` could not resolved for the origin `' .
            print_r($origin, true) . '`. ',
            1720123995
        );
    }

    /**
     * @param bool $flagFunc
     * @param string $input
     * @param mixed $origin
     * @return mixed
     * @throws MappingException
     */
    protected function executeDefinitionOfMethodFromString(bool $flagFunc, string $input, &$origin)
    {
        if ((!$flagFunc) || (!$this->checkForFunctionDefinition($input))) {
            // this should not happen but who knows
            throw new MappingException(
                'The method is called and no lonely function is defined in the string `' . $input . '`. ' .
                'The function must contain Brackets and a name. It should have the structure: ' .
                'name(Params), namespaceClass->method(params) or namespaceClass::method(params). ' .
                'The check shows ' . (($this->checkForFunctionDefinition($input)) ? 'true' : 'false') . '.',
                1720465033
            );
        }
        // Extract definition of method from string
        $limiterStart = $this->lim[self::ATTR_LIMITER_SUB_START];
        $limiterEnd = $this->lim[self::ATTR_LIMITER_SUB_END];
        $funcParam = explode($limiterStart, $input, 2);
        // definition of method
        $methodString = $funcParam[0];
        // parameter for method
        $funcParameter = substr($funcParam[1], 0, strlen($funcParam[1]) - strlen($limiterEnd));
        $paramList = array_filter(array_map('trim', explode($this->lim[self::ATTR_LIMITER_SUB_PARAMS], $funcParameter))); // parameter for method
        $params = [];
        if (!empty($paramList)) {
            foreach ($paramList as $partParam) {
                //$partParam is filled with an not empty string because of array_filter abowe
                // the param may contain a method or static datas derived from mappingprocess
                $item = $this->checkForFunktion($partParam, $origin);
                if ((is_string($item)) &&
                    (
                        ((strpos($item, "'") === 0) && (strrpos($item, "'") === strlen($item) - 1)) ||
                        ((strpos($item, '"') === 0) && (strrpos($item, '"') === strlen($item) - 1))
                    )
                ) {
                    $item = substr($item, 1, (strlen($item) - 2));

                }
                $params[] = $item;
            }
        }
        try {
            if (strpos($methodString, $this->lim[self::ATTR_LIMITER_SUB_DYNFUNC]) !== false) {
                [$namespace, $method] = explode($this->lim[self::ATTR_LIMITER_SUB_DYNFUNC], $methodString, 2);
                /** @phpstan-ignore-next-line */
                $object = GeneralUtility::makeInstance($namespace);
                if ((method_exists($namespace, $method)) &&
                    (is_callable([$object, $method]))
                ) {
                    $result = $object->$method(...$params);
                } else {
                    throw new MappingException(
                        'The dynamic method . `' . $methodString . ' ' .
                        ((method_exists($namespace, $method)) ? 'does exist. ' : 'does NOT exist. ') .
                        (is_callable([$namespace, $method]) ? ' It is callable. ' : ' It is NOT callable. '),
                        1720854727
                    );
                }
            } elseif (strpos($methodString, $this->lim[self::ATTR_LIMITER_SUB_STATFUNC]) !== false) {
                $limiter = $this->lim[self::ATTR_LIMITER_SUB_STATFUNC];
                [$namespace, $method] = explode($limiter, $methodString, 2);
                if ((method_exists($namespace, $method)) &&
                    (is_callable([$namespace, $method]))
                ) {
                    $result = $namespace::$method(...$params);
                } else {
                    throw new MappingException(
                        'The static method . `' . $methodString . ' ' .
                        ((method_exists($namespace, $method)) ? 'does exist. ' : 'does NOT exist. ') .
                        ((is_callable([$namespace, $method])) ? ' It is callable. ' : ' It is NOT callable. '),
                        1720854676
                    );
                }

            } elseif (!empty($methodString)) {
                if ((function_exists($methodString)) &&
                    (is_callable($methodString))
                ) {
                    $result = $methodString(...$params);
                } else {
                    throw new MappingException(
                        'The PHP-method . `' . $methodString . ' ' .
                        ((function_exists($methodString)) ? 'does exist. ' : 'does NOT exist. ') .
                        ((is_callable($methodString)) ? ' It is callable. ' : ' It is NOT callable. '),
                        1720854964
                    );
                }
            } else {
                throw new MappingException(
                    'An anonymous function is not allowed. Check the string`' . $input . '`, ' .
                    'which may be only a part of your definition.',
                    1719947701
                );
            }
        } catch (\Exception $e) {
            throw new MappingException(
                'There occurs an unexpected exception while trying to call a method. The following string occurs: `' . $input . '`. ' .
                'Check your definition of the mapping-array. This is the exception: "' .
                $e->getMessage() . ' [' . $e->getCode() . ']"',
                1719949420
            );
        }
        // $result undefined
        return $result;
    }

    /**
     * @param string $input
     * @param mixed $origin The parameter can have every type: object, array, scalar or null
     * @return string
     * @throws MappingException
     */
    protected function getDataOrStringFromResolvedDatasAndString(string $input, &$origin)
    {
        $myInput = $input;
        // startID contains the char-number for the unused uni-code-character, which will represent the
        // escaped combination $this->lim[self::ATTR_LIMITER_SUB_ESCAPE] . $this->lim[self::ATTR_LIMITER_SUB_PATH]
        $startID = 128023;
        if (strpos($input, $this->lim[self::ATTR_LIMITER_SUB_ESCAPE] . $this->lim[self::ATTR_LIMITER_SUB_PATH]) !== false) {
            // replace the escaped character aby an unused character in the input-string
            // Beginn the code-definition with the elefant.
            do {
                $startID++;
                $replaceEscapeChar = mb_chr($startID);
                /** @phpstan-ignore-next-line */
                if ($replaceEscapeChar === false) {
                    throw new MappingException(
                        'The number ' . $startID . ' could not converted into a UTF-8-charcter. ' .
                        'The programm could not find a replacement-character for the escape-cahracter `' .
                        $this->lim[self::ATTR_LIMITER_SUB_PATH] . '` in `' . $input . '`. ' .
                        'Inform the programmer to find a better solution.',
                        1719948574
                    );
                }
            } while (mb_strpos($input, $replaceEscapeChar) !== false);
            $myInput = str_replace(
                $this->lim[self::ATTR_LIMITER_SUB_ESCAPE] . $this->lim[self::ATTR_LIMITER_SUB_PATH],
                $replaceEscapeChar,
                $input
            );
        }
        $parts = explode($this->lim[self::ATTR_LIMITER_SUB_PATH], $myInput);

        $resultList = [];
        $count = 0;
        foreach ($parts as $myPart) {
            if (($count % 2) === 1) {
                $pos = strrpos($myPart, self::CHECKER_FUNC_PATHDEFAULT);
                //                $pos = strrpos($myPart, $this->dim[self::ATTR_LIMITER_SUB_DEFAULTPART]);
                if ($pos !== false) {
                    $valueOrPath = substr($myPart, 0, $pos);
                    $default = substr($myPart, $pos + 1);
                    $resultList[] = $this->solveMapping($valueOrPath, $origin, $valueOrPath, $default);
                } else {
                    $resultList[] = $this->solveMapping($myPart, $origin, $myPart);
                }
            } else {
                if (!empty($myPart)) {
                    $resultList[] = $myPart;
                }
            }
            $count++;
        }
        if (count($resultList) > 1) {
            $result = str_replace(
                mb_chr($startID),
                $this->lim[self::ATTR_LIMITER_SUB_PATH],
                implode('', $resultList)
            );
        } else {
            $result = $resultList[0];
            if (is_string($result)) {
                $result = str_replace(
                    mb_chr($startID),
                    $this->lim[self::ATTR_LIMITER_SUB_PATH],
                    $result
                );
            }
        }

        return $result;
    }

    /**
     * @param string $input
     * @return bool
     */
    protected function checkForFunctionDefinition(string $input): bool
    {
        $input = trim($input);
        $splitPos = strpos($input, $this->lim[self::ATTR_LIMITER_SUB_START]);
        $splitPosNot = strpos($input, $this->lim[self::ATTR_LIMITER_SUB_ESCAPE] . $this->lim[self::ATTR_LIMITER_SUB_START]);

        $inputLen = strlen($input);
        $splitStop = strrpos($input, $this->lim[self::ATTR_LIMITER_SUB_END]);
        $splitStopNot = strrpos($input, $this->lim[self::ATTR_LIMITER_SUB_ESCAPE] . $this->lim[self::ATTR_LIMITER_SUB_END]);
        return (($splitPos > 0) &&
            ($splitStop === ($inputLen - 1)) && // ) at the end of the string &&
            (($splitStopNot === false) || ($splitStopNot < ($splitStop - 1))) &&
            (($splitPosNot === false) || ($splitPosNot > $splitPos))
        );
    }


}
