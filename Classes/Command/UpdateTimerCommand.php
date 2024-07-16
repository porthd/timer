<?php

declare(strict_types=1);

namespace Porthd\Timer\Command;

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

use DateTime;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Domain\Model\Interfaces\TimerStartStopRange;
use Porthd\Timer\Domain\Repository\GeneralRepository;
use Porthd\Timer\Domain\Repository\TimerRepositoryInterface;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Services\ListOfTimerService;
use Porthd\Timer\Utilities\CustomTimerUtility;
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

    public const YAML_SUBGROUP_WHERE = 'where';
    public const YAML_SUBWHERE_FIELD = 'field';
    public const YAML_SUBWHERE_TYPE = 'type';
    public const YAML_SUBWHERE_VALUE = 'type';
    public const YAML_SUBWHERE_COMPARE = 'compare';


    protected const ARGUMENT_YAML_TABLE_LIST = 'yamlfile';

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

    /** @var DataHandler $dataHandler */
    private DataHandler $dataHandler;

    /** @var ListOfTimerService $timerService */
    private ListOfTimerService $timerService;

    /** @var YamlFileLoader $yamlLoader */
    private YamlFileLoader $yamlLoader;

    /** @var PageTreeRepository $pageTreeRepository */
    private PageTreeRepository $pageTreeRepository;

    /**
     * @param ListOfTimerService $listOfTimerService
     * @param DataHandler $dataHandler
     */
    public function __construct(
        ListOfTimerService $listOfTimerService,
        DataHandler $dataHandler,
        YamlFileLoader $yamlLoader,
        PageTreeRepository $pageTreeRepository
    ) {
        parent::__construct();
        $this->timerService = $listOfTimerService;
        $this->dataHandler = $dataHandler;
        $this->yamlLoader = $yamlLoader;
        $this->pageTreeRepository = $pageTreeRepository;
    }

    /**
     * define the required argument `yamlfile`, which contains the list of updatable models (tt_content, ...)
     *
     * @return void
     */
    public function configure(): void
    {
        $this->setDescription(
            LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:task.timer.updateTimesByTimer.title',
                TimerConst::EXTENSION_NAME
            )
        )->setHelp(
            LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_db.xlf:task.timer.updateTimesByTimer.help',
                TimerConst::EXTENSION_NAME
            )
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
    public function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        try {
            $this->dataHandler->setLogger($this->logger);

            $yamlFilePath = $this->getMyArgument($input);
            if (empty($yamlFilePath)) {
                throw new TimerException(
                    ' The yaml-list with the holidays is not found. There may be an error in the typoscript.  ' .
                    'Make a screenshot and inform the webmaster.',
                    1677394183
                );
            }
            $yamlConfig = CustomTimerUtility::readListFromFileOrUrl($yamlFilePath, $this->yamlLoader);
            if (array_key_exists(self::YAML_MAINGROUP_TABLELIST, $yamlConfig)) {
                $yamlConfig = $yamlConfig[self::YAML_MAINGROUP_TABLELIST];
            } // else $yamlConfig without yaml-help-layer
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
            return Command::FAILURE;
        }
        // @todo use flashmessages, to make infos on the scheduler
        if ($flagSuccessGeneral) {
            return Command::SUCCESS;
        }
        if (!empty($flagSuccessTable)) {
            foreach ($flagSuccessTable as $key => $flag) {
                if (!empty($flag)) {
                    $message = LocalizationUtility::translate(
                        self::LANG_PATH . 'task.timer.warning.yamlNotAllUpdated.1',
                        TimerConst::EXTENSION_NAME,
                        [
                            $flag,
                        ]
                    );

                    $output->writeln(
                        $message
                    );
                }
            }
        }
        return Command::SUCCESS;
    }

    /**
     * get the path of the defined yaml-file
     *
     * @param InputInterface $input
     * @return string
     */
    protected function getMyArgument(
        InputInterface $input
    ): string {
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
     * @param array<mixed> $pidList
     * @return array<mixed>
     */

    protected function allowedPids(
        $pidList
    ) {
        $result = [];
        foreach ($pidList as $pid) {
            $myTree = $this->pageTreeRepository->getTree($pid);
            array_walk_recursive($myTree, function ($value, $key) use (&$result) {
                if (($key === 'pid') || ($key === 'uid')) {
                    $result[(int)$value] = (int)$value;
                }
            }, $result);
        }
        return $result;
    }

    /**
     * @param array<mixed> $yamlConfig
     * @return array<mixed>
     * @throws TimerException
     */
    protected function updateTables(
        array $yamlConfig
    ) {
        $flagSuccess = [];
        $refDateTime = new DateTime('now');
        foreach ($yamlConfig as $key => $tableConfig) {
            $this->validateYamlDefinition($tableConfig, (string)$key);
            $listPids = [];
            if (array_key_exists(self::YAML_SUBGROUP_LIST_ROOTLINE, $tableConfig)) {
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
     * @param array<mixed> $yamlTableConfig
     * @param DateTime $refDateTime
     * @param array<mixed> $listPids
     * @return int
     */
    protected function updateTable(
        array $yamlTableConfig,
        DateTime $refDateTime,
        $listPids = []
    ): int {
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


        $className = (
            (!empty($yamlTableConfig[self::YAML_SUBGROUP_REPOSITORY])) ?
            $yamlTableConfig[self::YAML_SUBGROUP_REPOSITORY] :
            GeneralRepository::class
        );
        /** @var TimerRepositoryInterface $repository */
        $repository = GeneralUtility::makeInstance($className);
        if (!$repository->tableExists($yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE])) {
            return Command::FAILURE;
        }

        $listOfFields = TimerConst::TIMER_NEEDED_FIELDS;
        $whereInfos = $yamlTableConfig[self::YAML_SUBGROUP_WHERE] ?? [];
        if (!method_exists($repository, 'getTxTimerInfos')) {
            throw new TimerException(
                'The method `getTxTimerInfos` in the class `$className` did not exist. '.
                'Check the configuration of Classname in your yaml-files and/or in your database-definitions. '.
                'Check your php-code of the class and the autoload-file.',
                1672262471
            );
        }
        //!!!! `$listOfFields` may be changed in getTxTimerInfos by signal
        $listOfRows = $repository->getTxTimerInfos(
            $listOfFields,
            $yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE],
            $refDateTime,
            $listPids,
            $whereInfos
        );
        if ((!is_array($listOfRows)) || (empty($listOfRows))) {
            return Command::FAILURE;
        }
        $firstKey = array_key_first($listOfRows);
        if ((!array_key_exists(TimerConst::TIMER_FIELD_SELECTOR, $listOfRows[$firstKey])) ||
            (!array_key_exists(TimerConst::TIMER_FIELD_FLEX_ACTIVE, $listOfRows[$firstKey])) ||
            (!array_key_exists(TimerConst::TIMER_FIELD_UID, $listOfRows[$firstKey])) ||
            (!array_key_exists(TimerConst::TIMER_FIELD_PID, $listOfRows[$firstKey])) ||
            (!array_key_exists(TimerConst::TIMER_FIELD_STARTTIME, $listOfRows[$firstKey])) ||
            (!array_key_exists(TimerConst::TIMER_FIELD_ENDTIME, $listOfRows[$firstKey]))
        ) {
            $arrayString = implode('`, `', TimerConst::TIMER_NEEDED_FIELDS);
            throw new TimerException(
                'The need fields [`' . $arrayString .
                '`] are not defined in the row `' . print_r(
                    $listOfRows[$firstKey],
                    true
                ) . '`. The table of the query was `' . $yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE] .
                '`. The mistake is unexpected. ' .
                1602319674
            );
        }

        $count = 0;

        foreach ($listOfRows as $timerUpRow) {
            $xmlParam = GeneralUtility::xml2array($timerUpRow[TimerConst::TIMER_FIELD_FLEX_ACTIVE]);
            $normParams = TcaUtility::flexformArrayFlatten($xmlParam);
            if ((!is_array($normParams)) ||
                (!is_array($yamlTableConfig))
            ) {
                $hello = 'ups';
                continue;
            }
            // include informations about the relations for FAL-files in Flexform-Array for each timer
            $normParams[TimerConst::TIMER_RELATION_TABLE] = $yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE];
            $normParams[TimerConst::TIMER_RELATION_UID] = $timerUpRow[TimerConst::TIMER_FIELD_UID];

            if ($this->timerService->validate(
                $timerUpRow[TimerConst::TIMER_FIELD_SELECTOR],
                $normParams
            )) {
                /** @var TimerStartStopRange $timerRange */
                $timerRange = $this->timerService->nextActive(
                    $timerUpRow[TimerConst::TIMER_FIELD_SELECTOR],
                    $refDateTime,
                    $normParams
                );
                $dataChanges = [
                    TimerConst::TIMER_FIELD_STARTTIME => $timerRange->getBeginning()->getTimestamp(),
                    TimerConst::TIMER_FIELD_ENDTIME => $timerRange->getEnding()->getTimestamp(),
                ];
                $data[$yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE]][$timerUpRow[TimerConst::TIMER_FIELD_UID]] = $dataChanges;

                // @todo Install Event for custom changes before update

                $this->dataHandler->start($data, []);
                $this->dataHandler->process_datamap();
                $this->dataHandler->log(
                    $yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE],
                    $timerUpRow[TimerConst::TIMER_FIELD_UID],
                    2, // = 'update',
                    $timerUpRow[TimerConst::TIMER_FIELD_PID],
                    0,
                    'update starttime and endtime by timer-definition'
                );
            } else {
                if (!array_key_exists($yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE], $this->dataHandler->errorLog)) {
                    $this->dataHandler->errorLog[$yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE]] = [];
                }

                $this->dataHandler->errorLog[$yamlTableConfig[self::YAML_SUBGROUP_TEXT_TABLE]][] = [
                    'yaml' => $yamlTableConfig,
                    'data' => $timerUpRow,
                ];
            }
            $count++;
        }

        return (($count > 0) ? Command::SUCCESS : Command::FAILURE);
    }

    /**
     * some validation
     *
     * @param array<mixed> $tableConfig
     * @param string $tableKey
     * @return bool result is an exception or true
     * @throws TimerException
     */
    protected function validateYamlDefinition(
        array $tableConfig,
        string $tableKey
    ): bool {
        if (!array_key_exists(self::YAML_SUBGROUP_TEXT_TABLE, $tableConfig)) {
            throw new TimerException(
                'The table is not defined in your ' . $tableKey . 'th definition. Please check your yaml-file.',
                1602228674
            );
        }
        if ((!array_key_exists(self::YAML_SUBGROUP_REPOSITORY, $tableConfig)) ||
            (!is_string($tableConfig[self::YAML_SUBGROUP_REPOSITORY]))) {
            throw new TimerException(
                'The yaml-file must contain the namespace of the used repository in your ' . $tableKey .
                'th definition. Please check your yaml-file.',
                1602228775
            );
        } else {
            if ((!class_exists($tableConfig[self::YAML_SUBGROUP_REPOSITORY])) ||
                (!in_array(
                    TimerRepositoryInterface::class,
                    class_implements($tableConfig[self::YAML_SUBGROUP_REPOSITORY])
                ))
            ) {
                throw new TimerException(
                    'The repository, defined in yaml-file, must implement the interface `' . TimerRepositoryInterface::class .
                    '`. Please check your code of the repository-class `' . $tableConfig[self::YAML_SUBGROUP_REPOSITORY] .
                    '` in your ' . $tableKey . 'th definition.',
                    1602228776
                );
            }
        }

        if (!array_key_exists(self::YAML_SUBGROUP_LIST_ROOTLINE, $tableConfig)) {
            throw new TimerException(
                'The pid-list of the rootline must be a string, an array  or an integer in your ' . $tableKey .
                'th definition. Please check your yaml-file.',
                1602228685
            );
        } else {
            if (!is_array($tableConfig[self::YAML_SUBGROUP_LIST_ROOTLINE])) {
                throw new TimerException(
                    'The paramater for rootline is not an array. Please check your yaml-file',
                    1602239675
                );
            }
        }


        if (array_key_exists(self::YAML_SUBGROUP_WHERE, $tableConfig)) {
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
        return true;
    }
}
