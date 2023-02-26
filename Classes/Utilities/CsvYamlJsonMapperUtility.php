<?php

namespace Porthd\Timer\Utilities;

use Porthd\Timer\Exception\TimerException;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2023 Dr. Dieter Porth <info@mobger.de>
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
class CsvYamlJsonMapperUtility
{

    protected const INDENT = "    ";
    protected const NEW_LINE = "\n";
    protected const COLON = ': ';
    protected const COLON_NL = self::COLON . self::NEW_LINE;
    protected const ARY_ITEM = '- ';
    protected const KEY_SPECIAL = 'COMMA';
    protected const SINGLE_QUOTE = "'";
    protected const SINGLE_QUOTE_ESCAPE = self::SINGLE_QUOTE . '"' . self::SINGLE_QUOTE . '"' . self::SINGLE_QUOTE;

    /**
     *
     * see example https://www.php.net/manual/en/function.file-get-contents.php for POST-requests
     *
     * @param string $realFilePathOrUri
     * @param mixed|null $context
     * @return string
     */
    public static function readCsvFile(string $realFilePathOrUri, $context = null)
    {
        if (
            ($result = file_get_contents($realFilePathOrUri, false, $context))
        ) {
            return $result;
        }
        return '';
    }

    /**
     *
     * @param string $realFilePathOrUri
     * @param mixed|null $context
     * @return string
     */
    public static function readJsonFile(string $realFilePathOrUri, $context = null)
    {
        if (
            ($result = file_get_contents($realFilePathOrUri, false, $context))
        ) {
            return $result;
        }
        return '';
    }

    /**
     *
     * @param array $rawListWithHeadline
     * @param string $separator
     * @param string $analyseLeaf
     * @return array
     */
    public static function reorganizeSimpleArrayByHeadline(
        array $rawListWithHeadline,
        string $separator = '.',
        string $analyseLeaf = self::KEY_SPECIAL
    ): array {
        $headline = array_shift($rawListWithHeadline);
        $result = [];
        $template = [];
        $mapToTemplate = [];
        // build the Array-structure of a row in the template
        foreach ($headline as $index => $item) {
            $cascadeList = array_filter(
                array_map(
                    'trim',
                    explode($separator, $item)
                )
            );
            $pointPartTemplate = &$template;
            foreach ($cascadeList as $level) {
                if ($level !== self::KEY_SPECIAL) {
                    if (!array_key_exists($level, $pointPartTemplate)) {
                        $pointPartTemplate[$level] = [];
                    }
                    // importan & - the variable points to the place in the template
                    $pointCurrentPlace = &$pointPartTemplate[$level];
                    // walk recursive into the deep
                    $pointPartTemplate = &$pointPartTemplate[$level];
                } else {
                    break;
                }
            }
            // save the point to the current position in the template-array
            $mapToTemplate[$index] = &$pointCurrentPlace;
            unset($pointCurrentPlace);
        }
        // rebuild the array-structure for every row in the result
        $check = '.' . self::KEY_SPECIAL;
        $len = -strlen($check);
        foreach ($rawListWithHeadline as $row) {
            foreach ($row as $index => $item) {
                $help = &$mapToTemplate[$index];
                // interpret the string under the special key 'COMMA' as comma-separated list and convert it to an array
                if (substr($headline[$index], $len) === $check) {
                    $help = array_filter(
                        array_map(
                            'trim',
                            explode(',', $item)
                        )
                    );
                } else {
                    $help = $item;
                }
                unset($help);
            }
            $result[] = self::cloneArray($template);
        }
        return $result;
    }


    public static function removeEmptyRowCsv(array $ary, int $checkColumnForEmpty = -1): array
    {
        if ($checkColumnForEmpty >= 0) {
            $result = array_filter($ary, function ($value) use ($checkColumnForEmpty) {
                $flag = (!empty($value[$checkColumnForEmpty]));
                return (!empty($value[$checkColumnForEmpty]));
            });
            return $result;
        }
        return $ary;
    }


    /**
     * @param string $csvString
     * @param string $separator
     * @param string $enclosure
     * @param string $escape
     * @return array
     */
    public static function mapCsvToRawArray(
        string $csvString,
        string $separator = ',',
        string $enclosure = '"',
        string $escape = '\\'
    ): array {
        //parse the rows
        // perhaps Bug https://github.com/php/php-src/issues/10566
        //        $data = str_getcsv($csvString, "\n", $enclosure, $escape);
        $data = explode("\n", $csvString);
        $firstCount = count(
            str_getcsv($data[0], $separator, $enclosure, $escape)
        );
        //parse the items in rows
        $removeLines = [];
        foreach ($data as $line => &$row) {
            if ((empty($row)) || (preg_match('/^(\s|,)*$/', $row) === 1)) {
                $removeLines[] = $line;
            } else {
                $row = str_getcsv($row, $separator, $enclosure, $escape);

                if (count($row) !== $firstCount) {
                    throw new TimerException(
                        'The number of columns does not match the expected number. ' .
                        'The error occurred in the line (index: ' . $line . '). ' .
                        'Check whether a text field illegally contains a line break. If necessary, check your Excel or ' .
                        'Calc file. If necessary, delete the line in question and its successor and create the CSV again. ' .
                        'If nothing helps, the make an error-issue with the remaining csv-file on the mainpage ' .
                        'of this extension.',
                        1676121588
                    );
                }
            }
        }
        // remove empty or unfilled lines
        foreach ($removeLines as $key) {
            unset($data[$key]);
        }
        // remove empty lines; no error-detection
        return array_filter($data);
    }

    /**
     * @param array $array
     * @return string
     */
    public static function mapArrayToJson(array $array): string
    {
        return json_encode($array, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * @param array $array
     * @return array<mixed>
     */
    public static function mapJsonToArray(string $jsonString): string
    {
        return json_decode($jsonString, true);
    }

    /**
     * build a simple yaml-string define by a multilayered assoative array
     *
     * @param array $array
     * @param string $startAttribute
     * @param string $indent
     * @return string
     */
    public static function mapNotativeCsvArrayToYaml(
        array $array,
        string $startAttribute = 'mapped',
        string $indent = self::INDENT
    ): string {
        if (empty($array)) {
            return '';
        }
        $yaml = ((empty($startAttribute)) ?
            '' :
            $startAttribute . self::COLON_NL
        );
        foreach ($array as $key => $item) {
            if (is_int($key)) {
                $raw = $indent . self::ARY_ITEM;
            } else {
                $raw = $indent . $key . self::COLON;
            }

            if (is_int($item)) {
                $raw .= $item . self::NEW_LINE;
            } elseif (is_string($item)) {
                $item = str_replace(self::SINGLE_QUOTE, self::SINGLE_QUOTE_ESCAPE, $item);
                $raw .= self::SINGLE_QUOTE . $item . self::SINGLE_QUOTE . self::NEW_LINE;
            } elseif (is_array($item)) {
                $raw .= self::NEW_LINE;
                $raw .= self::mapNotativeCsvArrayToYaml($item, '', $indent . self::INDENT);
            }

            $yaml .= $raw;
        }
        return $yaml;
    }

    /**
     * @param array $arr
     * @return array
     */
    protected static function cloneArray(array $arr): array
    {
        $clone = [];
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $clone[$k] = self::cloneArray($v);
            } //If a subarray
            else {
                if (is_object($v)) {
                    $clone[$k] = clone $v;
                } //If an object
                else {
                    $clone[$k] = $v;
                }
            } //Other primitive types.
        }
        return $clone;
    }

}
