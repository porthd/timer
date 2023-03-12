<?php

namespace Porthd\Timer\CustomTimer;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2022 Dr. Dieter Porthd <info@mobger.de>
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


use DateTime;
use DateTimeZone;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Interfaces\ValidateYamlInterface;
use Porthd\Timer\Utilities\CustomTimerUtility;
use Porthd\Timer\Utilities\GeneralTimerUtility;
use Porthd\Timer\Utilities\TcaUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *
 */
class PeriodListTimer implements TimerInterface, LoggerAwareInterface, ValidateYamlInterface
{
    use LoggerAwareTrait;

    use GeneralTimerTrait;

    protected const YAML_LIST_ITEM_SELECTOR = 'selector';

    protected const EXAMPLE_STRUCTUR_YAML = <<<EXAMPLE
periodlist:
  -
    title: 'Winterferien Niedersachsen'
    data:
      description: '- free to fill -'
      moreCustomkeys: '...'
    start: '2022-01-31 00:00:00'
    stop: '2022-02-01 23:59:59'
    zone: 'Europe/Berlin'

EXAMPLE;
    protected const INFO_STRUCTUR_YAML = <<<INFOSYNTAX
# The fields `start` and `stop` are required.
#  The must contain an date in the format `<year>-<month>-<date> <hour>:<minute>:<second>`.
#  The year must have four digits. The otherparts must have two digits.
# The attributes `title`, `data` and `zone` are optional .
#  If `title` set, it must be not empty.
#  If the `zone` is set, it must be an allowed timezone.
#  if `data` is set, it should not be empty.
INFOSYNTAX;
    public const YAML_MAIN_KEY_PERIODLIST = 'periodlist';
    public const YAML_ITEMS_KEY_START = 'start';
    public const YAML_ITEMS_KEY_STOP = 'stop';
    public const YAML_ITEMS_KEY_DATA = 'data';
    public const YAML_ITEMS_KEY_TITLE = 'title';
    public const YAML_ITEMS_KEY_ZONE = 'zone';


    public const TIMER_NAME = 'txTimerPeriodList';

    public const ARG_YAML_PERIOD_FILE_PATH = 'yamlPeriodFilePath';
    public const ARG_YAML_PERIOD_FAL_INFO = 'yamlPeriodFalRelation';


    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/PeriodListTimer.flexform',
    ];

    protected const ARG_OPT_LIST = [
        self::ARG_YAML_PERIOD_FILE_PATH,
        self::ARG_YAML_PERIOD_FAL_INFO,

    ];
    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_USE_ACTIVE_TIMEZONE,
        self::ARG_EVER_TIME_ZONE_OF_EVENT,

    ];

    /**
     * @var TimerStartStopRange|null
     */
    protected $lastIsActiveResult;

    /**
     * the null is a flag, that no range have generated after the instantiation of this object
     * @var int|null
     */
    protected $lastIsActiveTimestamp = null; // = 1.1.1970 00:00:00

    /**
     * @var array<mixed>
     */
    protected $lastIsActiveParams = [];

    /**
     * @var YamlFileLoader
     */
    protected $yamlFileLoader;

    public function __construct()
    {
        $this->yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
    }


    /**
     * tested 20221007
     *
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }


    /**
     * tested 20221114
     *
     * @return array<mixed>
     */
    public static function getSelectorItem(): array
    {
        return [
            'LLL:EXT:timer/Resources/Private/Language/locallang_flex.xlf:tca.txTimerSelector.txTimerPeriodList.select.name',
            self::TIMER_NAME,
        ];
    }

    /**
     * tested 20221009
     *
     * @param string $activeZoneName
     * @param array<mixed> $params
     * @return string
     */
    public function getTimeZoneOfEvent($activeZoneName, array $params = []): string
    {
        return GeneralTimerUtility::getTimeZoneOfEvent($activeZoneName, $params);
    }

    /**
     * tested 20221114
     *
     * @return array<mixed>
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
    }

    /**
     * tested 20221009
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return bool
     */
    public function isAllowedInRange(DateTime $dateLikeEventZone, $params = []): bool
    {
        return ($params[self::ARG_ULTIMATE_RANGE_BEGINN] <= $dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME)) &&
            ($dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME) <= $params[self::ARG_ULTIMATE_RANGE_END]);
    }

    /**
     * tested general 20221115
     * tested special 20221120
     *
     * The method test, if the parameter are valid or not
     * remark: This method must not be tested, if the sub-methods are valid.
     * @param array<mixed> $params
     * @return bool
     */
    public function validate(array $params = []): bool
    {
        $flag = $this->validateZone($params);
        $flag = $flag && $this->validateFlagZone($params);
        $flag = $flag && $this->validateUltimate($params);
        $countRequired = $this->validateCountArguments($params);
        $flag = ($flag && ($countRequired === count(self::ARG_REQ_LIST)));
        $flag = $flag && $this->validateYamlFilePath($params);
        $countOptions = $this->validateOptional($params);
        return $flag && ($countOptions >= 0) &&
            ($countOptions <= count(self::ARG_OPT_LIST));
    }

    /**
     *
     * The method test, if the parameter in the yaml for the periodlist are okay
     * remark: This method must not be tested, if the sub-methods are valid.
     *
     * The method will implicitly called in `readPeriodListFromYamlFile(array $params): array`
     *
     * @param array<mixed> $yamlArray
     * @param string $infoAboutYamlFile
     * @throws TimerException
     */
    public function validateYamlOrException(array $yamlArray, string $infoAboutYamlFile = ''): void
    {
        $flag = true;
        if (!array_key_exists(self::YAML_MAIN_KEY_PERIODLIST, $yamlArray)) {
            throw new TimerException(
                'The yaml-file has not the correct syntax. It must contain the attribute ' .
                self::YAML_MAIN_KEY_PERIODLIST . ' at the starting level. Other attributes will be ignored at the starting-level. ' .
                'Check the structure of your YAML-file `' . $infoAboutYamlFile . '` for your `periodListTimer`.',
                1668234195
            );
        };
        $timeZone = TcaUtility::getListOfTimezones();
        foreach ($yamlArray[self::YAML_MAIN_KEY_PERIODLIST] as $item) {
            $start = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $item[self::YAML_ITEMS_KEY_START]
            );
            $stop = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $item[self::YAML_ITEMS_KEY_STOP]
            );
            // required fields
            $flag = array_key_exists(self::YAML_ITEMS_KEY_START, $item);
            $flag = $flag && array_key_exists(self::YAML_ITEMS_KEY_START, $item) &&
                ($start !== false);
            $flag = $flag && array_key_exists(self::YAML_ITEMS_KEY_STOP, $item) &&
                ($stop !== false);
            $flag = $flag && ((!array_key_exists(self::YAML_ITEMS_KEY_TITLE, $item)) ||
                    (!empty($item[self::YAML_ITEMS_KEY_TITLE])));
            $flag = $flag && ((!array_key_exists(self::YAML_ITEMS_KEY_DATA, $item)) ||
                    (!empty($item[self::YAML_ITEMS_KEY_DATA])));
            if (!$flag) {
                throw new TimerException(
                    'The item in yaml-file `' . $infoAboutYamlFile . '` for your `periodListTimer` has not the correct syntax.' .
                    'Check the items in your YAML-file. Check the correct form of the start and end-date (format: '.
                    TimerInterface::TIMER_FORMAT_DATETIME. '). A given attribute `'.self::YAML_ITEMS_KEY_TITLE .
                    '` or a given array `'.self::YAML_ITEMS_KEY_DATA.'` must filled at least with one char or one entry. '.
                    'The following items caused the exception: ' .
                    print_r($item, true) . "\n\n==============\n" . self::INFO_STRUCTUR_YAML .
                    "\n\n==============\n\nexample:\n--------------\n" . self::EXAMPLE_STRUCTUR_YAML .
                    "\n\n--------------\n",
                    1668236285
                );
            }
            $flag = ((!array_key_exists(self::YAML_ITEMS_KEY_ZONE, $item)) ||
                    (in_array($item[self::YAML_ITEMS_KEY_ZONE], $timeZone)));
            if (!$flag) {
                throw new TimerException(
                    'The item in yaml-file `' . $infoAboutYamlFile . '` for your `periodListTimer` has not the correct timezone.' .
                    'Check the items in your YAML-file and the definitions of your timezones. The following items caused the exception: ' .
                    print_r(
                        $item,
                        true
                    ) . "\n\n==============\n\n<br>allowed timezones:<br>\n~~~~~~~~~~~~~~\n<br>" . implode(
                        ',',
                        $timeZone
                    ),
                    1668236285
                );
            }
            if ($start > $stop) {
                throw new TimerException(
                    'The starttime `' . $start->format(TimerInterface::TIMER_FORMAT_DATETIME) . '` is ' .
                    'greater than the stoptime `' . $stop->format(TimerInterface::TIMER_FORMAT_DATETIME) .
                    '`  in your yaml-file `' . $infoAboutYamlFile . '`. This is not correct. ' .
                    'Check the items in your YAML-file. The following items caused the exception: ' .
                    print_r($item, true),
                    1668236285
                );
            }
        }
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return int
     */
    protected function validateCountArguments(array $params = []): int
    {
        return $this->countParamsInList(self::ARG_REQ_LIST, $params);
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return int
     */
    protected function validateOptional(array $params = []): int
    {
        return $this->countParamsInList(self::ARG_OPT_LIST, $params);
    }


    /**
     * This method are introduced for easy build of unittests
     * @param array<mixed> $params
     * @return bool
     */
    protected function validateYamlFilePath(array $params = []): bool
    {
        $filePath = (
            array_key_exists(self::ARG_YAML_PERIOD_FILE_PATH, $params) ?
                $params[self::ARG_YAML_PERIOD_FILE_PATH] :
                ''
        );
        if (!empty($filePath)) {
            if (strpos($filePath, TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH) === 0) {
                $extPath = $this->getExtentionPathByEnviroment();
                $filePath = substr($filePath, strlen(TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH));
                $flag = file_exists($extPath . DIRECTORY_SEPARATOR . $filePath);
            } else {
                $rootPath = $this->getPublicPathByEnviroment();
                $flag = file_exists($rootPath . DIRECTORY_SEPARATOR . $filePath);
            }
        } else {
            $flag = false;
        }

        return $flag;
    }

    /**
     * tested 20221120
     *
     * check, if the timer ist for this time active
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return bool
     */
    public function isActive(DateTime $dateLikeEventZone, $params = []): bool
    {
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            $this->setIsActiveResult($result->getBeginning(), $result->getEnding(), false, $dateLikeEventZone, $params);
            return $result->getResultExist();
        }
        $flag = false;
        $flagTimeZoneByFrontend = empty($params['useTimeZoneOfFrontend']) ? false : true;
        // the method will validate the yaml-file with a internal callback-method, so that the upload of the yaml-file
        //     fails with an exception, if the syntax of the yaml is somehow wrong.
        $listOfSeparatedDates = $this->readPeriodListFromYamlFile($params);
        $timeZone = $dateLikeEventZone->getTimezone();
        foreach ($listOfSeparatedDates as $singleDate) {
            if ($flagTimeZoneByFrontend) {
                $timeZone = new DateTimeZone($singleDate[self::YAML_ITEMS_KEY_ZONE]);
            }
            $start = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $singleDate[self::YAML_ITEMS_KEY_START],
                $timeZone
            );
            $stop = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $singleDate[self::YAML_ITEMS_KEY_STOP],
                $timeZone
            );
            if (($start <= $dateLikeEventZone) &&
                ($stop >= $dateLikeEventZone)
            ) {
                $result->setEnding($stop);
                $result->setBeginning($start);
                $flag = true;
                $result->setResultExist($flag);
                $this->setIsActiveResult($start, $stop, $flag, $dateLikeEventZone, $params);
                break;
            }
        }
        return $flag;
    }

    /**
     * tested
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult(DateTime $dateLikeEventZone, array $params = []): TimerStartStopRange
    {
        return $this->getLastIsActiveResult($dateLikeEventZone, $params);
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested 20221120
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function nextActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            return $result;
        }
        // the method will validate the yaml-file with a internal callback-method, so that the upload of the yaml-file
        //     fails with an exception, if the syntax of the yaml is somehow wrong.
        $listOfSeparatedDates = $this->readPeriodListFromYamlFile($params);
        $oldStart = null;
        $flag = true;
        $flagTimeZoneByFrontend = empty($params['useTimeZoneOfFrontend']) ? false : true;
        $timeZone = $dateLikeEventZone->getTimezone();
        foreach ($listOfSeparatedDates as $singleDate) {
            if ($flagTimeZoneByFrontend) {
                $timeZone = new DateTimeZone($singleDate[self::YAML_ITEMS_KEY_ZONE]);
            }
            $start = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $singleDate[self::YAML_ITEMS_KEY_START],
                $timeZone
            );

            if (($start > $dateLikeEventZone) &&
                (
                    $flag ||
                    ($oldStart > $start)
                )
            ) {
                $stop = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $singleDate[self::YAML_ITEMS_KEY_STOP],
                    $timeZone
                );
                $flag = false;
                $oldStart = clone $start;
                $result->setEnding($stop);
                $result->setBeginning($start);
                $result->setResultExist(true);
            }
        }
        return $this->validateUltimateRangeForNextRange($result, $params, $dateLikeEventZone);
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested 20221120
     *
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    public function prevActive(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        $result = new TimerStartStopRange();
        $result->failAllActive($dateLikeEventZone);
        if (!$this->isAllowedInRange($dateLikeEventZone, $params)) {
            return $result;
        }
        // the method will validate the yaml-file with a internal callback-method, so that the upload of the yaml-file
        //     fails with an exception, if the syntax of the yaml is somehow wrong.
        $listOfSeparatedDates = $this->readPeriodListFromYamlFile($params);
        $oldStop = null;
        $flag = true;
        $flagTimeZoneByFrontend = empty($params['useTimeZoneOfFrontend']) ? false : true;
        $timeZone = $dateLikeEventZone->getTimezone();
        foreach ($listOfSeparatedDates as $singleDate) {
            if ($flagTimeZoneByFrontend) {
                $timeZone = new DateTimeZone($singleDate[self::YAML_ITEMS_KEY_ZONE]);
            }
            $stop = date_create_from_format(
                TimerInterface::TIMER_FORMAT_DATETIME,
                $singleDate[self::YAML_ITEMS_KEY_STOP],
                $timeZone
            );
            if (($stop < $dateLikeEventZone) &&
                (
                    $flag ||
                    ($oldStop < $stop)
                )
            ) {
                $start = date_create_from_format(
                    TimerInterface::TIMER_FORMAT_DATETIME,
                    $singleDate[self::YAML_ITEMS_KEY_START],
                    $timeZone
                );
                $flag = false;
                $oldStop = clone $stop;
                $result->setEnding($stop);
                $result->setBeginning($start);

                $result->setResultExist(true);
            }
        }

        return $this->validateUltimateRangeForPrevRange($result, $params, $dateLikeEventZone);
    }

    /**
     * @param array<mixed> $params
     * @return array<mixed>
     * @throws TimerException
     */

    protected function readPeriodListFromYamlFile(array $params): array
    {
        if ((!array_key_exists(self::ARG_YAML_PERIOD_FILE_PATH, $params)) &&
            ($params[self::ARG_YAML_PERIOD_FAL_INFO] < 1)
        ) {
            return [];
        }
        // $this must allow the usage of the method `validateYamlOrException`
        $fileResult = CustomTimerUtility::readListFromFileOrUrl(
            $params[self::ARG_YAML_PERIOD_FILE_PATH],
            $this->yamlFileLoader,
            $this,
            $this->logger
        );
        $fileResult = $fileResult[self::YAML_MAIN_KEY_PERIODLIST] ?? [];
        $falRawResult = CustomTimerUtility::readListsFromFalFiles(
            $params[self::ARG_YAML_PERIOD_FAL_INFO],
            ($params[TimerConst::TIMER_RELATION_TABLE] ?? ''),
            ($params[TimerConst::TIMER_RELATION_UID] ?? 0),
            $this->yamlFileLoader,
            $this->logger
        );
        $rawResultFal = array_column($falRawResult, PeriodListTimer::YAML_MAIN_KEY_PERIODLIST);

        return array_merge($fileResult, ...$rawResultFal);
    }

    /**
     * @return string
     */
    protected function getExtentionPathByEnviroment(): string
    {
        return Environment::getExtensionsPath();
    }

    /**
     * @return string
     */
    protected function getPublicPathByEnviroment(): string
    {
        return Environment::getPublicPath();
    }


    /**
     * @param DateTime $dateStart
     * @param DateTime $dateStop
     * @param bool $flag
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return void
     */
    protected function setIsActiveResult(
        DateTime $dateStart,
        DateTime $dateStop,
        bool $flag,
        DateTime $dateLikeEventZone,
        array $params = []
    ): void {
        if (empty($this->lastIsActiveResult)) {
            $this->lastIsActiveResult = new TimerStartStopRange();
        }
        $this->lastIsActiveResult->setBeginning($dateStart);
        $this->lastIsActiveResult->setEnding($dateStop);
        $this->lastIsActiveResult->setResultExist($flag && (($dateStart <= $dateLikeEventZone) && ($dateLikeEventZone <= $dateStop)));
        $this->lastIsActiveTimestamp = $dateLikeEventZone->getTimestamp();
        $this->lastIsActiveParams = $params;
    }

    /**
     * @param DateTime $dateLikeEventZone
     * @param array<mixed> $params
     * @return TimerStartStopRange
     */
    protected function getLastIsActiveResult(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        if (empty($this->lastIsActiveResult)) {
            $this->lastIsActiveResult = new TimerStartStopRange();
            $this->lastIsActiveTimestamp = $dateLikeEventZone->getTimestamp() + 1; // trigger isActive() in the next step
        }

        if ((is_null($this->lastIsActiveTimestamp)) ||
            ($this->lastIsActiveTimestamp !== $dateLikeEventZone->getTimestamp()) ||
            (md5(json_encode($this->lastIsActiveParams)) !== md5(json_encode($params)))
        ) {
            $this->isActive($dateLikeEventZone, $params);
        }
        return clone $this->lastIsActiveResult;
    }
}
