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

use DateTimeInterface;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\DataProcessing\Trait\GeneralDataProcessorTrait;
use Porthd\Timer\DataProcessing\Trait\GeneralDataProcessorTraitInterface;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\CsvYamlJsonMapperUtility;
use Psr\Log\LoggerAwareTrait;
use ReflectionMethod;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
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
class BetterMappingProcessor implements DataProcessorInterface, GeneralDataProcessorTraitInterface
{
    use GeneralDataProcessorTrait;
    use LoggerAwareTrait;


    // optional Attribute perhaps with main defaults
    protected const ATTR_FLEX_INPUT_FIELD = 'inputfield';
    protected const DEFAULT_FLEX_FIELD = 'holidayList';

    protected const ATTR_GENERIC_DOT = 'generic.';
    protected const ATTR_MAPPING_DOT = 'mapping.';
    protected const ATTR_DOT_LEAF_PRETEXT = 'pretext';
    protected const ATTR_DOT_LEAF_POSTTEXT = 'posttext';
    protected const ATTR_DOT_LEAF_TYPE = 'type';
    protected const DEFAULT_GENERIC_TYPE = self::VAL_LEAF_TYPE_CONSTANT;
    protected const VAL_LEAF_TYPE_DATETIME = 'datetime';
    protected const VAL_LEAF_TYPE_CONSTANT = 'constant';
    protected const VAL_LEAF_TYPE_INDEX_SHORT = 'index';
    protected const VAL_LEAF_TYPE_INDEX = 'includeindex';
    protected const VAL_LEAF_TYPE_TRANSLATE = 'translate';
    protected const VAL_LEAF_TYPE_USERFUNC = 'userfunc';
    protected const VAL_LEAF_TYPE_VALUE_SHORT = 'value';
    protected const VAL_LEAF_TYPE_VALUE = 'includevalue';
    protected const ATTR_DOT_LEAF_FORMAT = 'format';
    protected const DEFAULT_DATETIME_FORMAT = 'Y-m-d\TH:i:s';
    protected const ATTR_DOT_LEAF_INFIELD = 'inField';
    protected const ATTR_DOT_LEAF_OUTFIELD = 'outField';
    protected const ATTR_DOT_LEAF_USERFUNC = 'userFunc';
    public const ATTR_PARAM_USERFUNC_INTERNAL = 'params';
    public const ATTR_PARAM_USERFUNC_MAPKEY = 'mapKey';
    public const ATTR_PARAM_USERFUNC_MAPITEM = 'mapItem';
    public const ATTR_PARAM_USERFUNC_START = 'start';
    protected const ATTR_OUTPUT_FORMAT = 'outputFormat';
    protected const VAL_OUTPUT_FORMAT_YAML = 'yaml';
    protected const VAL_OUTPUT_FORMAT_JSON = 'json';
    protected const VAL_OUTPUT_FORMAT_ARRAY = 'array';
    protected const DEFAULT_OUTPUT_FORMAT = self::VAL_OUTPUT_FORMAT_JSON;
    protected const ATTR_FLEX_YAMLSTARTKEY = 'yamlStartKey';
    protected const DEFAULT_OUTPUTFIELD = 'betterMappingJson';

    /**
     * @var ContentDataProcessor
     */
    protected $contentDataProcessor;

    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @var CacheService
     */
    protected $cacheManager;

    /**
     * @param FrontendInterface $cache
     * @param CacheService $cacheManager
     * @param ContentDataProcessor $contentDataProcessor
     */
    public function __construct(
        FrontendInterface    $cache,
        CacheService         $cacheManager,
        ContentDataProcessor $contentDataProcessor
    )
    {
        $this->cache = $cache;
        $this->cacheManager = $cacheManager;
        $this->contentDataProcessor = $contentDataProcessor;
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
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    )
    {
        // Reasons to stop this dataprocessor
        if (array_key_exists(self::ATTR_FLEX_INPUT_FIELD, $processorConfiguration)) {
            $inputFieldName = $cObj->stdWrapValue(self::ATTR_FLEX_INPUT_FIELD, $processorConfiguration, false);
        } else {
            $inputFieldName = self::DEFAULT_FLEX_FIELD;
        }
        if (array_key_exists(TimerConst::ARGUMENT_AS, $processorConfiguration)) {
            $outputFieldName = $cObj->stdWrapValue(
                TimerConst::ARGUMENT_AS,
                $processorConfiguration,
                self::DEFAULT_OUTPUTFIELD
            );
        } else {
            $outputFieldName = self::DEFAULT_OUTPUTFIELD;
        }
        if ((empty($processedData[$inputFieldName])) ||
            (!is_array($processedData[$inputFieldName])) ||
            (
                (array_key_exists(TimerConst::ARGUMENT_IF_DOT, $processorConfiguration)) &&
                (!$cObj->checkIf($processorConfiguration[TimerConst::ARGUMENT_IF_DOT]))
            )
        ) {
            return $processedData;
        }

        // prepare caching
        [$pageUid, $pageContentOrElementUid, $cacheIdentifier] = $this->generateCacheIdentifier(
            $processedData,
            $outputFieldName
        );
        $myResult = $this->cache->get($cacheIdentifier);
        $yamlStartKey = '';
        if ($myResult === false) {
            [$cacheTime, $cacheCalc] = $this->detectCacheTimeSet($cObj, $processorConfiguration);

            if (array_key_exists(self::ATTR_OUTPUT_FORMAT, $processorConfiguration)) {
                $outputFormat = $cObj->stdWrapValue(self::ATTR_OUTPUT_FORMAT, $processorConfiguration, self::DEFAULT_OUTPUT_FORMAT);
            } else {
                $outputFormat = self::DEFAULT_OUTPUT_FORMAT;
            }
            if (array_key_exists($outputFieldName, $processedData)) {
                throw new TimerException(
                    'The new fieldname `' . $outputFieldName . '` is already defined. ' .
                    'Check the typoscript-configuration of your dataprocessor `' . self::class . '`.',
                    1668758629
                );
            }
            if (array_key_exists(self::ATTR_FLEX_YAMLSTARTKEY, $processorConfiguration)) {
                $yamlStartKey = $cObj->stdWrapValue(self::ATTR_FLEX_YAMLSTARTKEY, $processorConfiguration, '');
            }

            // Define the mapping-process
            // 1. run all generic processes
            $result = [];
            foreach ($processedData[$inputFieldName] as $mapKey => $mapItem) {
                $result[$mapKey] = [];
            }
            foreach (($processorConfiguration[self::ATTR_GENERIC_DOT] ?? []) as $newFieldDotName => $genericConfig) {

                $outField = $genericConfig[self::ATTR_DOT_LEAF_OUTFIELD];
                if (empty($outField)) {
                    throw new TimerException(
                        'The outputfield in the part `' . self::ATTR_GENERIC_DOT . '` is not defined in the typoScript. ' .
                        'Check your typoscript or the spelling. If that does not work for you, ' .
                        'make a Screenshot and inform the webmaster.',
                        1677948663
                    );
                }
                $pretext = ((!empty($genericConfig[self::ATTR_DOT_LEAF_PRETEXT])) ? $genericConfig[self::ATTR_DOT_LEAF_PRETEXT] : '');
                $posttext = ((!empty($genericConfig[self::ATTR_DOT_LEAF_POSTTEXT])) ? $genericConfig[self::ATTR_DOT_LEAF_POSTTEXT] : '');
                $dateFormat = ((!empty($genericConfig[self::ATTR_DOT_LEAF_FORMAT])) ? $genericConfig[self::ATTR_DOT_LEAF_FORMAT] : self::DEFAULT_DATETIME_FORMAT);
                // translate the Definitions
                foreach (['pretext', 'posttext', 'dateFormat',] as $myName) {
                    if (substr($$myName, 0, strlen('LLL:EXT:')) === 'LLL:EXT:') {
                        $$myName = LocalizationUtility::translate($$myName);
                    }
                }
                $type = ((!empty($genericConfig[self::ATTR_DOT_LEAF_TYPE])) ? $genericConfig[self::ATTR_DOT_LEAF_TYPE] : self::DEFAULT_GENERIC_TYPE);
                foreach ($processedData[$inputFieldName] as $mapKey => $mapItem) {
                    switch ($type) {
                        case self::VAL_LEAF_TYPE_DATETIME:
                            $help = $this->getInFieldValue(
                                $mapItem,
                                $genericConfig[self::ATTR_DOT_LEAF_INFIELD],
                            );
                            if ($help instanceof DateTimeInterface) {
                                $inValue = $pretext . $help->format($dateFormat) . $posttext;
                            } else {
                                throw new TimerException(
                                    'The given type in the inField `' . $genericConfig[self::ATTR_DOT_LEAF_INFIELD] .
                                    '` must be an objectwhich implements the DateTimeInterface. ' .
                                    'Check the spellings in your typoscript-configuration of your dataprocessor `'
                                    . self::class . '`. If it does not work, make a screenshot ' .
                                    'and inform the webmaster.',
                                    1677949535
                                );
                            }
                            break;
                        case self::VAL_LEAF_TYPE_INDEX_SHORT:
                        case self::VAL_LEAF_TYPE_INDEX:
                            $inValue = $pretext . $mapKey . $posttext;
                            break;
                        case self::VAL_LEAF_TYPE_USERFUNC:
                            $startValue = $this->getInFieldValue(
                                $mapItem,
                                $genericConfig[self::ATTR_DOT_LEAF_INFIELD],
                            );
                            $userFunc = $genericConfig[self::ATTR_DOT_LEAF_USERFUNC] ?? '';
                            if (empty($userFunc)) {
                                throw new TimerException(
                                    'The userfunction is not defined. Check your typoscript. ' .
                                    'If this did not work, make a screenshot and inform the webmaster.',
                                    1678907347
                                );
                            }
                            $parameter = [
                                self::ATTR_PARAM_USERFUNC_INTERNAL => $genericConfig,
                                self::ATTR_PARAM_USERFUNC_MAPKEY => $mapKey,
                                self::ATTR_PARAM_USERFUNC_MAPITEM => $mapItem,
                                self::ATTR_PARAM_USERFUNC_START => $startValue,
                            ];
                            $inValue = GeneralUtility::callUserFunction($userFunc, $parameter, $this);
                            if (!is_scalar($inValue)) {
                                throw new \RuntimeException(
                                    'The expected userFunc "' . $userFunc . '" must return a scalar ' .
                                    '(boolean, integer, float, string). It gave back: ' . print_r($inValue, true) . '.',
                                    1678907644
                                );
                            }
                            break;
                        case self::VAL_LEAF_TYPE_TRANSLATE:
                            $lllKey = trim(
                                $this->getInFieldValue(
                                    $mapItem,
                                    $genericConfig[self::ATTR_DOT_LEAF_INFIELD],
                                )
                            );
                            $inValue = LocalizationUtility::translate(
                                $lllKey,
                                TimerConst::EXTENSION_NAME
                            );
                            if ($inValue === null) {
                                throw new TimerException(
                                    'The language-key `' . $lllKey . '` from the inputfield `' .
                                    $genericConfig[self::ATTR_DOT_LEAF_INFIELD] .
                                    '` could not resolved. Clear the cache and try it once more. Extend the Language-file ' .
                                    'and check the definitionkey for the translation. Alternatively you can remove the ' .
                                    'event with the key above from your list. ' .
                                    'If it does not work for you, make a screenshot and inform the webmaster.',
                                    1678548749
                                );

                            }
                            break;
                        case self::VAL_LEAF_TYPE_VALUE_SHORT:
                        case self::VAL_LEAF_TYPE_VALUE:
                            $help = $this->getInFieldValue(
                                $mapItem,
                                $genericConfig[self::ATTR_DOT_LEAF_INFIELD],
                            );
                            if (!is_scalar($help)) {
                                throw new TimerException(
                                    'The given type in the inField `' . $genericConfig[self::ATTR_DOT_LEAF_INFIELD] .
                                    '` must be a scalar. Check your typoscript-configuration of your dataprocessor `'
                                    . self::class . '`. If it does not work for you, make a screenshot ' .
                                    'and inform the webmaster.',
                                    1677949304
                                );
                            }
                            $inValue = $pretext . $help . $posttext;
                            break;
                        case self::VAL_LEAF_TYPE_CONSTANT:
                            $inValue = $pretext . $posttext;
                            break;
                        default:
                            throw new TimerException(
                                'The value of the inField `' . $type . '` is unknown. for the mapping of `'
                                . $outputFieldName . '`. ' .
                                'Check the typoscript-configuration of your dataprocessor `' . self::class . '`.',
                                1668757843
                            );
                    }
                    $this->setOutFieldValueByReference($result[$mapKey], $outField, $inValue);
                }
            }
            // 2. run all mapping processes
            foreach (($processorConfiguration[self::ATTR_MAPPING_DOT] ?? []) as $key => $mappingText) {
                $inField = $mappingText[self::ATTR_DOT_LEAF_INFIELD];
                $outField = $mappingText[self::ATTR_DOT_LEAF_OUTFIELD];
                if ((empty($outField)) || (empty($inField))) {
                    throw new TimerException(
                        'The outputfield in the part `' . self::ATTR_GENERIC_DOT . '`.`' . $key .
                        '`is not defined correctly in the typoScript. Check your typoscript or the spelling. ' .
                        'If that do not work for you, then make a screenshot and inform the webmaster.',
                        1677948663
                    );
                }
                foreach ($processedData[$inputFieldName] as $mapKey => $mapItem) {
                    $help = $this->getInFieldValue($mapItem, $inField);
                    if (is_scalar($help)) {
                        $this->setOutFieldValueByReference($result[$mapKey], $outField, $help);
                    } elseif (is_array($help)) {
                        $myHelp = array_unshift($help);
                        if (!is_scalar($myHelp)) {
                            throw new TimerException(
                                'The mapping could not be fullfilled. ' .
                                'The first value in the first part of the array of the inputfield `' . $inField . '` is not a scalar. ' .
                                'Rethink the mapping-definition in your typoscript. If you don\'t see the mistake, ' .
                                'make a screenshot and inform the webmaster. The current value is:' .
                                print_r($myHelp, true) . '.',
                                1678528289
                            );
                        }
                        $myTemplate = $result[$mapKey];
                        $this->setOutFieldValueByReference($result[$mapKey], $outField, $myHelp);
                        if (!empty($help)) {
                            foreach ($help as $index => $myItem) {
                                if (!is_scalar($myItem)) {
                                    throw new TimerException(
                                        'The mapping could not be fullfilled. ' .
                                        'The ' . ($index + 1) . 'th value in the array of the inputfield `' . $inField . '` is not a scalar. ' .
                                        'Rethink the mapping-definition in your typoscript. If you don\'t see the mistake, ' .
                                        'make a screenshot and inform the webmaster. The current value is:' .
                                        print_r($myItem, true) . '.',
                                        1678516935
                                    );
                                }
                                $myResult = $myTemplate;
                                $this->setOutFieldValueByReference($myResult, $outField, $myItem);
                                $result[] = $myResult;
                            }
                        }

                    } else {
                        throw new TimerException(
                            'The mapping could not be fullfilled. ' .
                            'The value of the inputfield `' . $inField . '` is not a scalar or an array. ' .
                            'Rethink the mapping-definition in your typoscript. If you don\'t see the mistake, ' .
                            'make a screenshot and inform the webmaster. The current value is:' .
                            print_r($help, true) . '.',
                            1678547479
                        );

                    }
                }
            }

            // the caching-times is defined or depends on default-value
            if (($cacheCalc !== false) ||
                ($cacheTime > 0)
            ) {
                $myTags = [
                    'pages_' . $pageUid,
                    'pages',
                    'betterMapping_' . $pageContentOrElementUid,
                    'betterMapping',
                ];
                $myResult = [
                    'as' => $outputFieldName,
                    'format' => $outputFormat,
                    'betterMapping' => $result,
                ];
                // clear page-cache
                // todo build a singleton, to call this only once in a request
                if ($cacheTime > 0) {
                    $this->cache->set($cacheIdentifier, $myResult, $myTags, $cacheTime);
                } else {
                    $this->cache->set($cacheIdentifier, $myResult, $myTags);
                }
            }
        }
        // 3. realize the output format for the resulting array
        switch ($myResult['format']) {
            case self::VAL_OUTPUT_FORMAT_JSON:
                $processedData[$myResult['as']] = json_encode(
                    $myResult['betterMapping'],
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                );
                break;
            case self::VAL_OUTPUT_FORMAT_ARRAY:
                $processedData[$myResult['as']] = $myResult['betterMapping'];
                break;
            case self::VAL_OUTPUT_FORMAT_YAML:
                $processedData[$myResult['as']] = CsvYamlJsonMapperUtility::mapAssoativeArrayToYaml(
                    $myResult['betterMapping'],
                    $yamlStartKey
                );
                break;
            default:
                throw new TimerException(
                    'The outputformat `' . $myResult['format'] . '` is not correctly defined. ' .
                    'Allowed are only one of the case-sensitive list `' . self::VAL_OUTPUT_FORMAT_JSON . ', ' . self::VAL_OUTPUT_FORMAT_ARRAY . '`.' .
                    'Check the typoscript-configuration of your dataprocessor `' . self::class . '`.',
                    1668762815
                );
        }

        // allow the call of a Dataprocessor in a dataprocessor
        return $processedData;
    }

    /**
     * A recursive approach to get variables in a structure.
     *
     * @param mixed $origin
     * @param mixed $fieldReference
     * @return mixed
     * @throws TimerException
     * @throws \ReflectionException
     */
    protected function getInFieldValue(&$origin, $fieldReference)
    {
        if (is_array($fieldReference)) {
            $fieldList = $fieldReference;
        } elseif ((!empty($fieldReference)) && (is_string($fieldReference))) {
            $fieldList = array_filter(
                explode('.', $fieldReference)
            );
        } else {
            throw new TimerException(
                'There is a problem with namepart `' . print_r($fieldReference, true) .
                '`. Please fix your typoscript and check the structure of your incomming values. ' .
                'If that did not work for you, then make a screenshot and inform the webmaster.',
                1677953576
            );
        }

        if (is_array($origin)) {
            $firstKey = array_shift($fieldList);
            if (array_key_exists($firstKey, $origin)) {
                if (empty($fieldList)) {
                    return $origin[$firstKey];
                }
                return $this->getInFieldValue($origin[$firstKey], $fieldList);
            }
            throw new TimerException(
                'There is a problem with resolving the namepart `' . $fieldReference .
                '`. Please fix your typoscript and check the structure of your incomming values. ' .
                'If that did not work for you, then make a screenshot and inform the webmaster.',
                1677951995
            );
        } elseif (is_object($origin)) {
            $firstKey = array_shift($fieldList);
            if ($origin->$firstKey ?? false) {
                if (empty($fieldList)) {
                    return $origin->$firstKey;
                }
                return $this->getInFieldValue($origin->$firstKey, $fieldList);

            } elseif (method_exists($origin, ($methodName = 'get' . ucfirst($firstKey)))) {
                $reflection = new ReflectionMethod($origin, $methodName);
                if (!$reflection->isPublic()) {
                    throw new TimerException(
                        'There is a problem with resolving the namepart `' . $firstKey .
                        '` to an accesseble method `' . $firstKey . '`. It seems, that the method is protected. ' .
                        'This is a clearly programming bug. Please make a screenshot and inform the webmaster.',
                        1677952836
                    );
                }
                if (empty($fieldList)) {
                    return $origin->$methodName();
                }
                // todo: check, if this is correct. the logic seems to be difficult
                $helpValue = $origin->$methodName();
                return $this->getInFieldValue($helpValue, $fieldList);
            }
            throw new TimerException(
                'There is a problem with resolving the namepart `' . $fieldReference .
                '`. Please fix your typoscript and check the structure of your incomming values. ' .
                'If that did not work for you, then make a screenshot and inform the webmaster.',
                1677950884
            );

        }
        throw new TimerException(
            'The origin is not an object or an array. `' . print_r($origin, true) .
            '`. May be, there is a bug in your typoscript. or in the incomming structure. Please check this.' .
            'If that did not work for you, then make a screenshot and inform the webmaster.',
            1677953152
        );
    }

    /**
     * @param array<mixed> $origin
     * @param mixed $fieldReference
     * @param string|int|bool $stringValue
     * @return void
     * @throws TimerException
     */
    protected function setOutFieldValueByReference(
        array &$origin,
        $fieldReference,
        $stringValue
    )
    {
        if (is_array($fieldReference)) {
            $fieldList = $fieldReference;
        } elseif ((!empty($fieldReference)) && (is_string($fieldReference))) {
            $fieldList = array_filter(
                explode('.', $fieldReference)
            );
        } else {
            throw new TimerException(
                'There is a problem with namepart `' . print_r($fieldReference, true) .
                '`. Please fix your typoscript and check the structure of your incomming values. ' .
                'If that did not work for you, then make a screenshot and inform the webmaster.',
                1677951489
            );
        }

        // fill array by reference
        $firstKey = array_shift($fieldList);
        if (array_key_exists($firstKey, $origin)) {
            if (empty($fieldList)) {
                throw new TimerException(
                    'There is a problem with resolving the namepart `' . $fieldReference .
                    '`. Please fix your typoscript and check the structure of your incomming values. ' .
                    'If that did not work for you, then make a screenshot and inform the webmaster.',
                    1677950884
                );
            }
            $this->setOutFieldValueByReference(
                $origin[$firstKey],
                $fieldList,
                $stringValue
            );
        } else {
            if (empty($fieldList)) {
                $origin[$firstKey] = $stringValue;
            } else {
                $origin[$firstKey] = [];
                $this->setOutFieldValueByReference(
                    $origin[$firstKey],
                    $fieldList,
                    $stringValue
                );
            }
        }

    }
}
