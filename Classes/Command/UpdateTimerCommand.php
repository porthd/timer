<?php

namespace Porthd\Timer\Command;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 Dr. Dieter Porthd <info@mobger.de>
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
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Domain\Repository\GeneralRepository;
use Porthd\Timer\Domain\Repository\TimerRepositoryInterface;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Services\ListOfTimerService;
use Porthd\Timer\Utilities\TcaUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use function Wikimedia\Parsoid\Wt2Html\TT\array_flatten;

/**
 * Class UpdateTimesByTimerCommand
 * @package Porthd\Timer\Command
 *
 * command-task to update the values in starttime and endtime
 *   if the datarow contains timerdefinition used by this extension
 */
class UpdateTimerCommand extends Command implements LoggerAwareInterface
{
    use LogDataTrait;
    use LoggerAwareTrait;


    protected const SIGNAL_MODIFY_DATA_BEFORE_DATAHANDLER = 'modifyDataBeforeDatahandler';

    protected const ARGUMENT_YAML_TABLE_LIST = 'yamlfile';
    public const FAILURE = 1;
    public const SUCCESS = 0;
    protected const LANG_PATH = 'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:';

    protected const YAML_MAINGROUP_TABLELIST = 'tablelist';
    protected const YAML_SUBGROUP_LIST_COUNT = 4;
    protected const YAML_SUBGROUP_LIST = [
        self::YAML_SUBGROUP_LIST_ROOTLINE,
        self::YAML_SUBGROUP_WHERE,
        self::YAML_SUBGROUP_TEXT_TABLE,
        self::YAML_SUBGROUP_REPOSITORY,
    ];
    protected const YAML_SUBGROUP_REPOSITORY = 'repository';
    protected const YAML_SUBGROUP_LIST_ROOTLINE = 'rootlinePid';
    protected const YAML_SUBGROUP_TEXT_TABLE = 'table';
    public const YAML_SUBGROUP_WHERE = 'where';
    public const YAML_SUBWHERE_FIELD = 'field';
    public const YAML_SUBWHERE_TYPE = 'type';
    public const YAML_SUBWHERE_VALUE = 'type';
    public const YAML_SUBWHERE_COMPARE = 'compare';

    protected $currentPidList = [];
    /** @var ?DataHandler $dataHandler */
    private ?DataHandler $dataHandler = null;


    /**
     * @param DataHandler $dataHandler
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function injectDataHandler(DataHandler $dataHandler)
    {
        $this->dataHandler = $dataHandler;
        $this->dataHandler->setLogger($this->logger);
    }

    /**
     * define the required argument `yamlfile`, which contains the list of updatable models (tt_content, ...)
     */
    public function configure()
    {
        $this->setDescription(LocalizationUtility::translate(
            'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:task.timer.updateTimesByTimer.title',
            TimerConst::EXTENSION_NAME)
        )->setHelp(LocalizationUtility::translate(
            'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:task.timer.updateTimesByTimer.help',
            TimerConst::EXTENSION_NAME)
        )->addArgument(
            self::ARGUMENT_YAML_TABLE_LIST,
            InputArgument::OPTIONAL,
            LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:task.timer.updateTimesByTimer.yamltablelist',
                TimerConst::EXTENSION_NAME
            ),
            'EXT:timer/Configuration/Yaml/UpdateTimeByTimer.yaml'
        );
    }


    /**
     * the controller for the updates of `starttime` and `endtime` in the selected models
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {

            $yamlFilePath = $this->getMyArgument($input);
            $flags = YamlFileLoader::PROCESS_PLACEHOLDERS | YamlFileLoader::PROCESS_IMPORTS;
            /** @var YamlFileLoader $yamlLoader */
            $yamlLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
            $yamlConfig = $yamlLoader->load($yamlFilePath, $flags);
            // @todo Throw an error-message, if there was an table not successful (no changes)
            $flagSuccessTable = $this->updateTables($yamlConfig);
            $flagSuccessGeneral = array_reduce($flagSuccessTable, function ($carry, $item) {
                return $carry && $item;
            }, true);
        } catch (Exception $e) {
            // @todo use flashmessages, to make infos on the scheduler
            $output->writeln(
                $e->getMessage()
            );
            return self::FAILURE;
        }
        // @todo use flashmessages, to make infos on the scheduler
        if ($flagSuccessGeneral) {
            return self::SUCCESS;
        }

        foreach ($flagSuccessTable??[] as $key => $flag) {
            if (!empty($flag)) {
                $message = LocalizationUtility::translate(
                    self::LANG_PATH . 'task.timer.warning.yamlNotAllUpdated.3',
                    TimerConst::EXTENSION_NAME,
                    [
                        $key,
                        $flag[self::YAML_SUBGROUP_TEXT_TABLE],
                        $flag[self::YAML_SUBGROUP_LIST_ROOTLINE],
                    ]
                );

                $output->writeln(
                    $message
                );
            }
        }
        return self::SUCCESS;
    }

    /**
     * get the path of the defined yaml-file
     *
     * @param InputInterface $input
     * @return string
     */
    protected function getMyArgument(InputInterface $input): string
    {
        if (empty($input->hasArgument(self::ARGUMENT_YAML_TABLE_LIST))) {
            throw new TimerException(
                LocalizationUtility::translate(
                    self::LANG_PATH . 'task.timer.exception.yamlNotExit.1',
                    TimerConst::EXTENSION_NAME,
                    [self::ARGUMENT_YAML_TABLE_LIST]
                ),
                1602153934
            );
        }
        return $input->getArgument(self::ARGUMENT_YAML_TABLE_LIST);
    }

    /**
     * @param array $pidList
     * @return array
     */

    protected function allowedPids($pidList)
    {
        // PageTreeRepository is marked as intern // I try this problem
        /** @var PageTreeRepository $myPageRepository */
        $myPageRepository = GeneralUtility::makeInstance(PageTreeRepository::class);
        $result = [];
        foreach ($pidList as $pid) {
            $myTree = $myPageRepository->getTree($pid);
            array_walk_recursive($myTree, function ($value, $key) use (&$result) {
                if (($key === 'pid')||($key === 'uid')) {
                    $result[(int)$value] = (int)$value;
                }
            }, $result);
        }
        return $result;
    }

    /**
     * @param array $yamlConfig
     * @return array
     * @throws TimerException
     */
    protected function updateTables(array $yamlConfig)
    {
        $flagSuccess = [];
        $refDateTime = new DateTime('now');
        foreach ($yamlConfig[self::YAML_MAINGROUP_TABLELIST] as $key => $tableConfig) {
            $this->validateYamlDefinition($tableConfig, $key);
            $listPids = [];
            if (isset($tableConfig[self::YAML_SUBGROUP_LIST_ROOTLINE])) {
                $listPids = $this->allowedPids($tableConfig[self::YAML_SUBGROUP_LIST_ROOTLINE]);
            }
            $flagSuccess[$key] = $this->updateTable($tableConfig, $refDateTime, $listPids);
        }
        return $flagSuccess;
    }


    /**
     * the main updater are splittet in tow steps:
     * 1. Get the datas from the database (allow Signal for custom intervention - extend more fields)
     * 2. Update the data with the datamapper (allow Signals for custom intervention - change text in update fields )
     *
     * @param array $yamlTableConfig
     * @param DateTime $refDateTime
     * @param array $listPids
     * @return int
     */
    protected function updateTable(array $yamlTableConfig, DateTime $refDateTime, $listPids = [])
    {
        if (!class_exists($yamlTableConfig[self::YAML_SUBGROUP_REPOSITORY]) ||
            (!in_array(
                TimerRepositoryInterface::class,
                (class_implements($yamlTableConfig[self::YAML_SUBGROUP_REPOSITORY]) ?: [])
            ))
        ) {
            throw new TimerException(
                'The repository is not correctly defined in the yaml-file. Check, if the class exist.' .
                ' Check ist the class implements the interface ' . TimerRepositoryInterface::class . '.',
                1667149694
            );

        }


        /** @var TimerRepositoryInterface $repository */
        $repository = new $yamlTableConfig[self::YAML_SUBGROUP_REPOSITORY];
        if (!$repository->tableExists($yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE])) {
            return self::FAILURE;
        }
//        $signalSlotDispatcher = GeneralUtility::makeInstance(ObjectManager::class)->get(Dispatcher::class);

        $listOfFields = TimerConst::TIMER_NEEDED_FIELDS;
        $whereInfos = $yamlTableConfig[self::YAML_SUBGROUP_WHERE] ?? [];
        //!!!! `$listOfFields` may be changed in getTxTimerInfos by signal
        $listOfRows = $repository->getTxTimerInfos(
            $listOfFields,
            $yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE],
            $refDateTime,
            $listPids,
            $whereInfos
        );
        if ((!is_array($listOfRows)) || (empty($listOfRows))) {
            return self::FAILURE;
        }
        $firstKey = array_key_first($listOfRows);
        if ((!isset($listOfRows[$firstKey][TimerConst::TIMER_FIELD_SELECT])) ||
            (!isset($listOfRows[$firstKey][TimerConst::TIMER_FIELD_FLEX_ACTIVE])) ||
            (!isset($listOfRows[$firstKey][TimerConst::TIMER_FIELD_UID])) ||
            (!isset($listOfRows[$firstKey][TimerConst::TIMER_FIELD_PID])) ||
            (!isset($listOfRows[$firstKey][TimerConst::TIMER_FIELD_STARTTIME])) ||
            (!isset($listOfRows[$firstKey][TimerConst::TIMER_FIELD_ENDTIME]))
        ) {
            throw new TimerException(
                'The need fields [`' . implode('`, `', TimerConst::TIMER_NEEDED_FIELDS) .
                '`] are not defined in the row `' . print_r($listOfRows[$firstKey],
                    true) . '`. The table of the query was `' . $yamlTableConfig .
                '`. The mistake is unexpected. Perhaps your slot for the signal `' . GeneralRepository::SIGNAL_MODIFY_GENERIC_REQUEST .
                '` is wrongly defined.' .
                1602319674
            );
        }

        $count = 0;
        /** @var ListOfTimerService $timerList */
        $timerList = GeneralUtility::makeInstance(ListOfTimerService::class);

        foreach ($listOfRows as $timerUpRow) {
            $xmlParam = GeneralUtility::xml2array($timerUpRow[TimerConst::TIMER_FIELD_FLEX_ACTIVE]);
            $normParams = TcaUtility::flexformArrayFlatten($xmlParam);
//            $normParams = array_merge(...$normParams);
            if ($timerList->validate(
                    $timerUpRow[TimerConst::TIMER_FIELD_SELECT],
                $normParams
                )) {
                /** @var TimerStartStopRange $timerRange */
                $timerRange = $timerList->nextActive(
                    $timerUpRow[TimerConst::TIMER_FIELD_SELECT],
                    $refDateTime,
                    $normParams
                );
                $dataChanges = [
                    TimerConst::TIMER_FIELD_STARTTIME => $timerRange->getBeginning()->getTimestamp(),
                    TimerConst::TIMER_FIELD_ENDTIME => $timerRange->getEnding()->getTimestamp(),
                ];
                $data[$yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE]][$timerUpRow[TimerConst::TIMER_FIELD_UID]] = $dataChanges;

//                // custom changes one more fields of the model
//                [$data, $yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE],] = $signalSlotDispatcher->dispatch(
//                    __CLASS__,
//                    self::SIGNAL_MODIFY_DATA_BEFORE_DATAHANDLER,
//                    [$data, $yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE],]
//                );

                $this->dataHandler->start($data, []);
                $this->dataHandler->process_datamap();
                $this->dataHandler->log($yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE],
                    $timerUpRow[TimerConst::TIMER_FIELD_UID],
                    'update',
                    $timerUpRow[TimerConst::TIMER_FIELD_PID],
                    0,
                    'update starttime and endtime by timer-definition'

                );
            } else {
                if (!isset($this->dataHandler->errorLog[$yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE]])) {
                    $this->dataHandler->errorLog[$yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE]] = [];
                }

                $this->dataHandler->errorLog[$yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE]][] = [
                    'yaml' => $yamlTableConfig,
                    'data' => $timerUpRow,
                ];
            }
            $count++;
        }
        return (($count > 0) ? self::SUCCESS : self::FAILURE);
    }

    /**
     * some validation
     *
     * @param $tableConfig
     * @throws TimerException
     */
    /**
     * @param $tableConfig
     * @param $tableKey
     * @throws TimerException
     */
    protected function validateYamlDefinition($tableConfig, $tableKey)
    {
        if (!isset($tableConfig[self::YAML_SUBGROUP_TEXT_TABLE])) {
            throw new TimerException(
                'The table is not defined in your ' . $tableKey . 'th definition. Please check your yaml-file.',
                1602228674
            );
        }
        if ((!isset($tableConfig[self::YAML_SUBGROUP_REPOSITORY])) ||
            (!is_string($tableConfig[self::YAML_SUBGROUP_REPOSITORY]))) {
            throw new TimerException(
                'The yaml-file must contain the namespace of the used repository in your ' . $tableKey .
                'th definition. Please check your yaml-file.',
                1602228775
            );
        } else {
            if ((!class_exists($tableConfig[self::YAML_SUBGROUP_REPOSITORY])) ||
                (!in_array(TimerRepositoryInterface::class,
                    class_implements($tableConfig[self::YAML_SUBGROUP_REPOSITORY])))
            ) {
                throw new TimerException(
                    'The repository, defined in yaml-file, must implement the interface `' . TimerRepositoryInterface::class .
                    '`. Please check your code of the repository-class `' . $tableConfig[self::YAML_SUBGROUP_REPOSITORY] .
                    '` in your ' . $tableKey . 'th definition.',
                    1602228776
                );
            }
        }

        if (!isset($tableConfig[self::YAML_SUBGROUP_LIST_ROOTLINE])) {
            throw new TimerException(
                'The pid-list of the rootline must be a string, an array  or an integer in your ' . $tableKey .
                'th definition. Please check your yaml-file.',
                1602228685
            );
        } else {
            if ((isset($tableConfig[self::YAML_SUBGROUP_LIST_ROOTLINE])) &&
                (!is_array($tableConfig[self::YAML_SUBGROUP_LIST_ROOTLINE]))
            ) {
                throw new TimerException(
                    'The paramater for rootline is not an array. Please check your yaml-file',
                    1602239675
                );
            }
        }


        if (isset($tableConfig[self::YAML_SUBGROUP_WHERE])) {
            if (!is_array($tableConfig[self::YAML_SUBGROUP_WHERE])) {
                throw new TimerException(
                    'The where-part in the yaml-file must contain an array of fields in your ' . $tableKey .
                    'th definition. Please check your yaml-file.',
                    1602228879
                );
            }
            $listParam = array_keys($tableConfig[self::YAML_SUBGROUP_WHERE]);
            $intersect = array_intersect($listParam, self::YAML_SUBGROUP_LIST);
            $countIntersect = count($intersect);
            if ($countIntersect !== self::YAML_SUBGROUP_LIST_COUNT) {
                throw new TimerException(
                    'The where-part in the yaml-file must only contain the following attributes: ' .
                    implode(', ', self::YAML_SUBGROUP_LIST) . '.',
                    1667123054
                );
            }

        }
    }
}
