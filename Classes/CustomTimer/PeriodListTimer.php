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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *
 */
class PeriodListTimer implements TimerInterface, LoggerAwareInterface, ValidateYamlInterface
{

    use LoggerAwareTrait;

    protected const YAML_LIST_ITEM_SELECTOR = 'selector';
    protected const YAML_LIST_ITEM_PARAMS = 'params';

    protected const MAX_TIME_LIMIT_ACTIVE_COUNT = 10; // count of loops to check for overlapping active ranges

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

    protected const MAX_TIME_LIMIT_MERGE_COUNT = 4; // count of loops to check for overlapping ranges


    // needed as default-value in `Porthd\Timer\Services\ListOfTimerService`
    public const TIMER_FLEXFORM_ITEM = [
        self::TIMER_NAME => 'FILE:EXT:timer/Configuration/FlexForms/TimerDef/PeriodListTimer.flexform',
    ];

    protected const ARG_REQ_LIST = [
        self::ARG_ULTIMATE_RANGE_BEGINN,
        self::ARG_ULTIMATE_RANGE_END,
        self::ARG_YAML_PERIOD_FILE_PATH,
    ];
    protected const ARG_OPT_LIST = [
        self::ARG_USE_ACTIVE_TIMEZONE,
    ];

    /**
     * @var TimerStartStopRange|null
     */
    protected $lastIsActiveResult;

    /**
     * @var int
     */
    protected $lastIsActiveTimestamp = 0; // = 1.1.1970 00:00:00

    /**
     * @var array
     */
    protected $lastIsActiveParams = [];

    /**
     * @var YamlFileLoader
     */
    protected $yamlFileLoader;
//
//    /**
//     * @param YamlFileLoader $yamlFileLoader
//     */
//    public function injectYamlFileLoader(YamlFileLoader $yamlFileLoader)
//    {
//        $this->yamlFileLoader = $yamlFileLoader;
//    }

    /**
     * @var FrontendInterface|null
     */
    private $cache;
//
//    /**
//     * @param FrontendInterface|null $cache
//     */
//    public function injectCache(?FrontendInterface $cache)
//    {
//        $this->cache = $cache;
//    }

    public function __construct()
    {
        $this->yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $this->cache = $cacheManager->getCache(TimerConst::CACHE_IDENT_TIMER_YAMLLIST);

    }


    /**
     * tested 20221007
     * +
     *
     *
     * @return string
     */
    public static function selfName(): string
    {
        return self::TIMER_NAME;
    }


    /**
     * tested
     * @return array
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
     * @param array $params
     * @return string
     */
    public function getTimeZoneOfEvent($activeZoneName, array $params = []): string
    {
        return GeneralTimerUtility::getTimeZoneOfEvent($activeZoneName, $params);
    }

    /**
     * tested 20221009
     *
     * @return array
     */
    public static function getFlexformItem(): array
    {
        return self::TIMER_FLEXFORM_ITEM;
    }

    /**
     * tested 20221009
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
     * @return bool
     */
    public function isAllowedInRange(DateTime $dateLikeEventZone, $params = []): bool
    {
        return ($params[self::ARG_ULTIMATE_RANGE_BEGINN] <= $dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME)) &&
            ($dateLikeEventZone->format(TimerInterface::TIMER_FORMAT_DATETIME) <= $params[self::ARG_ULTIMATE_RANGE_END]);
    }

    /**
     * tested general 20221009
     * tested special 20221011
     *
     * The method test, if the parameter are valid or not
     * remark: This method must not be tested, if the sub-methods are valid.
     * @param array $params
     * @return bool
     */
    public function validate(array $params = []): bool
    {
        $flag = true;
        $flag = $flag && $this->validateZone($params);
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
     * @param array $yamlArray
     * @param string $pathOfYamlFile
     * @throws TimerException
     */
    public function validateYamlOrException(array $yamlArray, $pathOfYamlFile): void
    {
        $flag = true;
        if (!isset($yamlArray[self::YAML_MAIN_KEY_PERIODLIST])) {
            throw new TimerException(
                'The yaml-file has not the correct syntax. It must contain the attribute ' .
                self::YAML_MAIN_KEY_PERIODLIST . ' at the starting level. Other attributes will be ignored at the starting-level. ' .
                'Check the structure of your YAML-file `' . $pathOfYamlFile . '` for your `periodListTimer`.',
                1668234195
            );
        };
        $timeZone = TcaUtility::getListOfTimezones();
        foreach ($yamlArray[self::YAML_MAIN_KEY_PERIODLIST] as $item) {
            // required fields
            $flag = $flag && isset($item[self::YAML_ITEMS_KEY_START]) &&
                (($start = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME,
                        $item[self::YAML_ITEMS_KEY_START])) !== false);
            $flag = $flag && isset($item[self::YAML_ITEMS_KEY_STOP]) &&
                (($stop = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME,
                        $item[self::YAML_ITEMS_KEY_STOP])) !== false);
            $flag = $flag && ((!isset($item[self::YAML_ITEMS_KEY_TITLE])) ||
                    (!empty($item[self::YAML_ITEMS_KEY_TITLE])));
            $flag = $flag && ((!isset($item[self::YAML_ITEMS_KEY_DATA])) ||
                    (!empty($item[self::YAML_ITEMS_KEY_DATA])));
            if (!$flag) {
                throw new TimerException(
                    'The item in yaml-file `' . $pathOfYamlFile . '` for your `periodListTimer` has not the correct syntax.' .
                    'Check the items in your YAML-file. The following items caused the exception: ' .
                    print_r($item, true) . "\n\n==============\n" . self::INFO_STRUCTUR_YAML .
                    "\n\n==============\n\nexample:\n--------------\n" . self::EXAMPLE_STRUCTUR_YAML .
                    "\n\n--------------\n",
                    1668236285
                );
            }
            $flag = $flag && ((!isset($item[self::YAML_ITEMS_KEY_ZONE])) ||
                    (in_array($item[self::YAML_ITEMS_KEY_ZONE], $timeZone)));
            if (!$flag) {
                throw new TimerException(
                    'The item in yaml-file `' . $pathOfYamlFile . '` for your `periodListTimer` has not the correct timezone.' .
                    'Check the items in your YAML-file and the definitions of your timezones. The following items caused the exception: ' .
                    print_r($item, true) . "\n\n==============\n\n<br>allowed timezones:<br>\n~~~~~~~~~~~~~~\n<br>" . implode(',', $timeZone),
                    1668236285
                );
            }
            $flag = $flag && ($start < $stop);
            if ($start > $stop) {
                throw new TimerException(
                    'The starttime `' . $start->format(TimerInterface::TIMER_FORMAT_DATETIME) . '` is ' .
                    'greater than the stoptime `' . $stop->format(TimerInterface::TIMER_FORMAT_DATETIME) .
                    '`  in your yaml-file `' . $pathOfYamlFile . '`. This is not correct. ' .
                    'Check the items in your YAML-file. The following items caused the exception: ' .
                    print_r($item, true),
                    1668236285
                );

            }

        }
    }


    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateZone(array $params = []): bool
    {
        return !(isset($params[self::ARG_EVER_TIME_ZONE_OF_EVENT])) ||
            TcaUtility::isTimeZoneInList(
                $params[self::ARG_EVER_TIME_ZONE_OF_EVENT]
            );
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateUltimate(array $params = []): bool
    {
        $flag = (!empty($params[self::ARG_ULTIMATE_RANGE_BEGINN]));
        $flag = $flag && (false !== date_create_from_format(
                    self::TIMER_FORMAT_DATETIME,
                    $params[self::ARG_ULTIMATE_RANGE_BEGINN]
                ));
        $flag = $flag && (!empty($params[self::ARG_ULTIMATE_RANGE_END]));
        return ($flag && (false !== date_create_from_format(
                    self::TIMER_FORMAT_DATETIME,
                    $params[self::ARG_ULTIMATE_RANGE_END]
                )));
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateCountArguments(array $params = []): int
    {
        $count = 0;
        foreach (self::ARG_REQ_LIST as $key) {
            if (isset($params[$key])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return bool
     */
    protected function validateOptional(array $params = []): int
    {
        $count = 0;
        foreach (self::ARG_OPT_LIST as $key) {
            if (isset($params[$key])) {
                $count++;
            }
        }
        return $count;
    }


    /**
     * This method are introduced for easy build of unittests
     * @param array $params
     * @return int
     */
    protected function validateYamlFilePath(array $params = []): bool
    {
        $flag = true;

        $filePath = (isset($params[self::ARG_YAML_PERIOD_FILE_PATH]) ?
            $params[self::ARG_YAML_PERIOD_FILE_PATH] :
            ''
        );
        if (!empty($filePath)) {
            if (strpos($filePath, TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH) === 0) {
                $extPath = $this->getExtentionPathByEnviroment();
                $filePath = substr($filePath, strlen(TimerConst::MARK_OF_EXT_FOLDER_IN_FILEPATH));
                $flag = $flag && file_exists($extPath . DIRECTORY_SEPARATOR . $filePath);
            } else {
                $rootPath = $this->getPublicPathByEnviroment();
                $flag = $flag && file_exists($rootPath . DIRECTORY_SEPARATOR . $filePath);
            }
        } else {
            $flag = false;
        }


        return $flag;
    }

    /**
     * tested
     *
     * check, if the timer ist for this time active
     *
     * @param DateTime $dateLikeEventZone convention: the datetime is normalized to the timezone by paramas
     * @param array $params
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
        // the method will validate the yaml-file with a internal callback-method, so that the upload of the yaml-file
        //     fails with an exception, if the syntax of the yaml is somehow wrong.
        $listOfSeparatedDates = $this->readPeriodListFromYamlFile($params);
        foreach ($listOfSeparatedDates as $singleDate) {
            $start = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME,
                $singleDate[self::YAML_ITEMS_KEY_START]);
            $stop = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME,
                $singleDate[self::YAML_ITEMS_KEY_STOP]);
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
     * @param array $params
     * @return TimerStartStopRange
     */
    public function getLastIsActiveRangeResult(DateTime $dateLikeEventZone, $params = []): TimerStartStopRange
    {
        return $this->getLastIsActiveResult($dateLikeEventZone, $params);
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested
     *
     * @param DateTime $dateLikeEventZone lower or equal to the next starttime & convention: the datetime is normalized to the timezone by paramas
     * @param array $params
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
        foreach ($listOfSeparatedDates as $singleDate) {
            $start = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME,
                $singleDate[self::YAML_ITEMS_KEY_START]);

            if (($start > $dateLikeEventZone) &&
                (
                    $flag ||
                    ($oldStart > $start)
                )
            ) {
                $stop = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME,
                    $singleDate[self::YAML_ITEMS_KEY_STOP]);
                $flag = false;
                $oldStart = clone $start;
                $result->setEnding($stop);
                $result->setBeginning($start);
                $result->setResultExist(true);
            }
        }
        return $result;
    }

    /**
     * find the next free range depending on the defined list
     *
     * tested
     *
     * @param DateTime $dateLikeEventZone
     * @param array $params
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
        foreach ($listOfSeparatedDates as $singleDate) {
            $stop = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME,
                $singleDate[self::YAML_ITEMS_KEY_STOP]);
            if (($stop < $dateLikeEventZone) &&
                (
                    $flag ||
                    ($oldStop < $stop)
                )
            ) {
                $start = date_create_from_format(TimerInterface::TIMER_FORMAT_DATETIME,
                    $singleDate[self::YAML_ITEMS_KEY_START]);
                $flag = false;
                $oldStop = clone $stop;
                $result->setEnding($stop);
                $result->setBeginning($start);

                $result->setResultExist(true);
            }
        }
        return $result;
    }

    /**
     * @param array $params
     * @return array
     * @throws TimerException
     */
    protected function readPeriodListFromYamlFile(array $params): array
    {
        if (!isset($params[self::ARG_YAML_PERIOD_FILE_PATH])) {
            return [];
        }
        // $this must allow the usage of the method `validateYamlOrException`
        $result = CustomTimerUtility::readListFromYamlFile(
            $params[self::ARG_YAML_PERIOD_FILE_PATH],
            $this->yamlFileLoader,
            $this,
            $this->cache
        );
        return $result[self::YAML_MAIN_KEY_PERIODLIST] ?? [];

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
     * @param $dateStart
     * @param $dateStop
     * @param bool $flag
     * @param DateTime $dateLikeEventZone
     */
    protected function setIsActiveResult(
        $dateStart,
        $dateStop,
        bool $flag,
        DateTime $dateLikeEventZone,
        $params = []
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
     * @param array $params
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