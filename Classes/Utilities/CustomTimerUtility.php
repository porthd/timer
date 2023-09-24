<?php

declare(strict_types=1);

namespace Porthd\Timer\Utilities;

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

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\ValidateYamlInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\DataProcessing\FilesProcessor;

/**
 * The concept of the DateTime-Object in Frameworks is horrible. You can selectect bewtween a PHP
 * You have an Editor in New York, who wants to add his date in Local time.
 * You Store the date
 *
 *       //Enter your code here, enjoy!
 *
 *       $defDate = new \DateTime('1970/03/05 00:00:00'); // Contain the TimeZomne of the Server
 *                                                        // Bad Coding, because you Don't know
 *       $myDate = new \DateTime('1970/03/05 00:00:00', new \DateTimeZone('Pacific/Honolulu'));
 *                                                        // Contain the local time of Honolulu
 *
 *       // an later in Code
 *       echo($myDate->format('Y/m/d H:i:s')); // No surprise. You see 1970/03/05 00:00:00
 *       echo('<pre>'."\n");
 *       var_dump($defDate); // local Version of PHP-Server (cloud,...)
 *       echo('<pre>'."\n");
 *       var_dump($myDate);  // local Version of Honolulu (although in the Cloud)
 *       echo('<pre>'."\n");
 *       $myDate->setTimezone(new DateTimeZone( 'UTC')); // rebuild the time for the new timezone
 *       var_dump($myDate);
 *       echo($myDate->format('Y/m/d H:i:s')); // Real Horror. You don't see 1970/03/05 00:00:00, because Time is recalculated
 *
 * Problem you cant use Format, to generate the Output for a wisched Time-Zone
 * Class DateTimeUtility
 * @package Porthd\Timer\Utilities
 */
class CustomTimerUtility
{
    protected const DEFAULT_BEGIN_PERIOD = "1753-01-01 00:00:00";  // Because of calandar-relaunch (Julian, modern-counting
    protected const DEFAULT_ENDING_PERIOD = "9999-12-31 23:59:59";


    /**
     * @param string $startDateTime
     * @param string $stopDateTime
     * @return string|null
     */
    public static function generateGeneralBeginEnd(string $startDateTime, string $stopDateTime)
    {
        if ($startDateTime === self::DEFAULT_BEGIN_PERIOD) {
            if ($stopDateTime === self::DEFAULT_ENDING_PERIOD) {
                $result = LocalizationUtility::translate(
                    'content.timer.periodMessage.general.beginEnd.default',
                    TimerConst::EXTENSION_NAME
                );
            } else {
                $result = LocalizationUtility::translate(
                    'content.timer.periodMessage.general.beginEnd.stop.1',
                    TimerConst::EXTENSION_NAME,
                    [
                        $stopDateTime,
                    ]
                );
            }
        } else {
            if ($stopDateTime === self::DEFAULT_ENDING_PERIOD) {
                $result = LocalizationUtility::translate(
                    'content.timer.periodMessage.general.beginEnd.start.1',
                    TimerConst::EXTENSION_NAME,
                    [
                        $startDateTime,
                    ]
                );
            } else {
                $result = LocalizationUtility::translate(
                    'content.timer.periodMessage.general.beginEnd.2',
                    TimerConst::EXTENSION_NAME,
                    [
                        $startDateTime,
                        $stopDateTime,
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * @param int $checkMin
     * @return mixed|string|null
     */
    public static function generateReadbleTimeFromMin(int $checkMin)
    {
        $minutes = abs($checkMin);
        $result['min'] = floor($minutes % 60);
        $hourMin = floor($minutes / 60);
        $result['hour'] = floor($hourMin % 24);
        $dayHour = floor($hourMin / 24);
        $result['day'] = floor($dayHour % 7);
        $result['week'] = floor($dayHour / 7);
        $cascade = [];
        foreach (['week' => 'w', 'day' => 'd', 'hour' => 'h', 'min' => 'min'] as $key => $unit) {
            if ($result[$key] > 1) {
                $cascade[] = (LocalizationUtility::translate(
                    'content.timer.periodMessage.general.timeparts.' . $key . '.many.1',
                    TimerConst::EXTENSION_NAME,
                    [
                        $result[$key],
                    ]
                ) ?? $result[$key] . ' ' . $unit);
            } elseif ($result[$key] > 0) {
                $cascade[] = (LocalizationUtility::translate(
                    'content.timer.periodMessage.general.timeparts.' . $key . '.single',
                    TimerConst::EXTENSION_NAME
                ) ?? $result[$key] . ' ' . $unit);
            }
        }
        if (empty($cascade)) {
            return (LocalizationUtility::translate(
                'content.timer.periodMessage.general.timeparts.zero',
                TimerConst::EXTENSION_NAME
            ) ?? '0 min');
        }
        if (count($cascade) === 1) {
            return array_pop($cascade);
        }

        $last = array_pop($cascade);
        $rest = implode(', ', $cascade);
        return (LocalizationUtility::translate(
            'content.timer.periodMessage.general.timeparts.combine.2',
            TimerConst::EXTENSION_NAME,
            [$rest, $last]
        ) ?? $rest . ', ' . $last
        );
    }

    /**
     * @param string|int|null $activeWeekday
     * @return int
     */
    public static function getParameterActiveWeekday($activeWeekday)
    {
        $result = 127;
        if ((isset($activeWeekday)) &&
            (is_numeric($activeWeekday))
        ) {
            $value = (int)$activeWeekday;
            $diff = $activeWeekday - $value;
            // <127 because at least one weekday schuold not be set
            if (($diff === 0) && ($value > 0) && ($value < 128)) {
                return $value;
            }
        }

        return $result;
    }

    /**
     * @param string $yamlFalParam
     * @param string $relationTable
     * @param int $relationUid
     * @param YamlFileLoader $yamlFileLoader
     * @return array<mixed>
     */
    public static function readListsFromFalFiles(
        string $yamlFalParam,
        string $relationTable,
        int $relationUid,
        YamlFileLoader $yamlFileLoader,
        ?LoggerInterface $logger = null
    ): array {
        if ($yamlFalParam < 1) {
            return [];
        }
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $fileObjects = $fileRepository->findByRelation(
            $relationTable,
            TimerConst::TIMER_FIELD_FLEX_ACTIVE,
            $relationUid
        );
        $result = [];

        /** @var FileReference $fileObject */
        foreach ($fileObjects as $fileObject) {
            $filePathInStorage = $fileObject->getProperty('identifier');
            $pathStorage = $fileObject->getStorage()->getConfiguration()['basePath'];
            $filePath = Environment::getPublicPath() . DIRECTORY_SEPARATOR .
                trim($pathStorage, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                trim($filePathInStorage, DIRECTORY_SEPARATOR);
            if (file_exists($filePath)) {
                $result[] = self::readFilePathIntoArray($filePath, $yamlFileLoader);
            } else {
                if ($logger !== null) {
                    $logger->warning(
                        'The file `' . $filePath . '` did not exist. ' .
                        'Check your yaml-definition or the existence of the server or the existence of the file ' .
                        'on the foreign server.',
                        [print_r($fileObject, true)]
                    );
                }
            }
        }
        return $result;
    }

    /**
     * @param string $filePath
     * @param YamlFileLoader $yamlFileLoader
     * @param ValidateYamlInterface|null $validatorObject
     * @param LoggerInterface|null $logger
     * @return array<mixed>
     * @throws TimerException
     */
    public static function readListFromFileOrUrl(
        string $filePath,
        YamlFileLoader $yamlFileLoader,
        ?ValidateYamlInterface $validatorObject = null,
        ?LoggerInterface $logger = null
    ): array {
        if (file_exists($filePath)) {
            $filePathNew = realpath($filePath);
            if (!file_exists($filePathNew)) {
                return [];
            }
            $result = self::readFilePathIntoArray($filePathNew, $yamlFileLoader);
        } else {
            // Don't allow relative pathes or pathes with '//' or pathes with '\\'
            if (GeneralUtility::validPathStr($filePath)) {
                if (strpos($filePath, 'FILE:') === 0) {
                    $filePath = substr($filePath, strlen('FILE:'));
                }
                $filePathNew = GeneralUtility::getFileAbsFileName($filePath);
                if (!file_exists($filePathNew)) {
                    return [];
                }
                $result = self::readFilePathIntoArray($filePathNew, $yamlFileLoader);
            } elseif (preg_match('@^http[s]://@i', $filePath) === 1) {     // open accessible url
                $fileData = file_get_contents($filePath);
                if ($fileData === false) {
                    if ($logger !== null) {
                        $logger->warning(
                            'The file `' . $filePath . '` could not be found. Perhaps the server is down.' .
                            'Check your yaml-definition or the existence of the file on the foreign server.',
                            [$filePath, 'open accessible url']
                        );
                    }
                    return [];
                }
                $result = self::parseFileDateIntoArray($filePath, $fileData);
            } elseif (preg_match('@^(.+):(.+):http[s]://@i', $filePath) === 1) { // url secured by server password
                $splitList = array_filter(
                    array_map(
                        'trim',
                        explode(':', $filePath, 3)
                    )
                );
                if ((count($splitList) !== 3) ||
                    (empty($splitList[0])) ||
                    (empty($splitList[1])) ||
                    (strpos($splitList[2], 'https://') === 0) ||
                    (strpos($filePath, 'http://') === 0)
                ) {
                    throw new TimerException(
                        'The parameter `' . $filePath . '` is not correctly defined. ' .
                        'If you want to get a yaml-file from a password-protected server, you must use the structure ' .
                        '`<username>:<password>:<url>`. There is no colon allowed in the username and in the password. ' .
                        'The `<username>` and the `<password>` must not empty. There is no colon allowed in the ' .
                        'username and in the password. The `<url>` must begin with `http://` or `https://`. ' .
                        'Check your yaml-definition or the values for the password on the foreign server.',
                        1669453312
                    );
                }
                $context = stream_context_create([
                    'http' => [
                        'header' => 'Authorization: Basic ' . base64_encode("$splitList[0]:$splitList[1]"),
                    ],
                ]);
                $fileData = file_get_contents($splitList[2], false, $context);
                if ($fileData === false) {
                    if ($logger !== null) {
                        $logger->warning(
                            'The file `' . $filePath . '` did not exist. ' .
                            'Check your yaml-definition or the existence of the server or the existence of the file ' .
                            'on the foreign server.',
                            [$filePath, 'by server-password secured url']
                        );
                    }
                    return [];
                }
                $result = self::parseFileDateIntoArray($filePath, $fileData);
            } else {
                throw new TimerException(
                    'Your parameter `' . $filePath . '` could not resolved. ' .
                    'Allowed are one of the following file-allocation-types. You can firstly define an existing path on the ' .
                    'server, which may contain relativ definitions. You can secondly use the TYPO3-filepath-definition ' .
                    'containing the prefix `EXT:`. You can thirdly use a simple URL beginning with `http://` or `https://`, if ' .
                    'the file is not secured by an serverpassword. You can forth use a string with the three parameter ' .
                    'with the structure `<username>:<password>:<url>`. Then `<username>` and `<password>` can not ' .
                    'contain a colon and the `<url>` mus beginn with `http://`or `https://`. ' .
                    'Check your yaml-definition .',
                    1669453491
                );
            }
        }
        // validate the yaml-structure or throw an exception
        $implements = class_implements($validatorObject);
        if (($validatorObject !== null) &&
            ($implements !== false) &&
            (in_array(ValidateYamlInterface::class, $implements))
        ) {
            $validatorObject->validateYamlOrException($result, ($filePathNew ?? '-- undefined --'));
        }
        return $result;
    }

    /**
     * @param $filePathNew
     * @param YamlFileLoader $yamlFileLoader
     * @param string $filePath
     * @return array|string
     * @throws TimerException
     */
    protected static function readFilePathIntoArray($filePathNew, YamlFileLoader $yamlFileLoader)
    {
        $infos = pathinfo($filePathNew);
        switch ($infos['extension']) {
            case 'yaml':
            case 'yml':
                $flags = YamlFileLoader::PROCESS_PLACEHOLDERS | YamlFileLoader::PROCESS_IMPORTS;
            $result = $yamlFileLoader->load($filePathNew, $flags);
                break;
            case 'csv':
                $csvString = CsvYamlJsonMapperUtility::readCsvFile($filePathNew);
                $rawCsvArray = CsvYamlJsonMapperUtility::mapCsvToRawArray($csvString);
                $result = CsvYamlJsonMapperUtility::reorganizeSimpleArrayByHeadline($rawCsvArray);
                break;
            case 'json':
                $result = CsvYamlJsonMapperUtility::readJsonFile($filePathNew);
                break;
            default:
                throw new TimerException(
                    'The file `' . $filePathNew . '`(reorganized) must have the extenion `yaml`,`yml`,`csv` or `json`. ' .
                    'The extension is needed, to detect the dateformat (yaml, csv, json) of the file. ' .
                    'Other formats are not supported. check the spellings of the path. If you think, ' .
                    'that some runs wrong, then make a screenshot ' .
                    'and inform the wewbmaster ',
                    1669454592
                );
        }
        return $result;
    }

    /**
     * @param string $filePath
     * @param string $fileData
     * @return mixed|mixed[]|string
     * @throws TimerException
     */
    protected static function parseFileDateIntoArray(string $filePath, string $fileData)
    {
        $hashPos = strrpos($filePath, '#');
        $format = '';
        if ($hashPos > 0) {
            $format = substr($filePath, $hashPos);
        }
        switch ($format) {
            case 'csv':
                $rawCsvArray = CsvYamlJsonMapperUtility::mapCsvToRawArray($fileData);
                $result = CsvYamlJsonMapperUtility::reorganizeSimpleArrayByHeadline($rawCsvArray);
                break;
            case 'json' :
                $result = CsvYamlJsonMapperUtility::mapJsonToArray($fileData);
                break;
            default:
                $result = Yaml::parse($fileData);
                break;
        }
        return $result;
    }
}
