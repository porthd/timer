<?php

declare(strict_types=1);

namespace Porthd\Timer\Domain\Repository;

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
use Ddeboer\Imap\Connection;
use Doctrine\DBAL\ParameterType;
use Exception;
use Porthd\Timer\Domain\Repository\TimerRepositoryInterface;
use PDO;
use Doctrine\DBAL\Exception as DbalException;
use Porthd\Timer\Command\UpdateTimerCommand;
use Porthd\Timer\Constants\TimerConst;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The repository for Iconlists
 */
class GeneralRepository implements TimerRepositoryInterface
{
    public const GENERAL_ROW_IDENTIFIER = 'uid';
    public const GENERAL_PARENT_IDENTIFIER = 'pid';


    /**
     * @param string $tableName
     * @return bool
     */
    public function tableExists($tableName): bool
    {
        // Try a select statement against the table
        // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
        try {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = (GeneralUtility::makeInstance(ConnectionPool::class))->getConnectionForTable($tableName)
                ->createQueryBuilder();
            $queryBuilder->count(self::GENERAL_ROW_IDENTIFIER)->from($tableName);
            return (bool)($queryBuilder->executeQuery()->fetchOne());
        } catch (Exception $e) {
            // We got an exception == table not found
            return false;
        }
    }

    /**
     * @param array<mixed> $listOfFields
     * @param string $genericTable
     * @param DateTime $refTime
     * @param array<mixed> $pidList
     * @param array<mixed> $whereInfos
     * @return array<mixed>
     * @throws DbalException
     */
    public function getTxTimerInfos(
        array $listOfFields,
        string $genericTable,
        DateTime $refTime,
        array $pidList = [],
        array $whereInfos = []
    ): array {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = (GeneralUtility::makeInstance(ConnectionPool::class))
            ->getQueryBuilderForTable($genericTable);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $contraints = [
            $queryBuilder->expr()->eq(TimerConst::TIMER_FIELD_SCHEDULER, 1),
            $queryBuilder->expr()->neq(
                TimerConst::TIMER_FIELD_FLEX_ACTIVE,
                '""'
            ),
            $queryBuilder->expr()->isNotNull(TimerConst::TIMER_FIELD_FLEX_ACTIVE),
            $queryBuilder->expr()->isNotNull(TimerConst::TIMER_FIELD_SELECTOR),
            $queryBuilder->expr()->neq(
                TimerConst::TIMER_FIELD_SELECTOR,
                '""'
            ),
            $queryBuilder->expr()->lte(
                TimerConst::TIMER_FIELD_STARTTIME,
                $queryBuilder->createNamedParameter($refTime->getTimestamp(), ParameterType::INTEGER)
            ),
            $queryBuilder->expr()->lte(
                TimerConst::TIMER_FIELD_ENDTIME,
                $queryBuilder->createNamedParameter($refTime->getTimestamp(), ParameterType::INTEGER)
            ),
        ];

        if (!empty($whereInfos)) {
            $this->addWhereConditionsToQueryBuilder($whereInfos, $queryBuilder);
        }
        if (count($pidList) > 0) {
            $contraints[] = $queryBuilder->expr()->in(self::GENERAL_PARENT_IDENTIFIER, $pidList);
        }

        $queryBuilder->select(...$listOfFields)
            ->from($genericTable)
            ->where(...$contraints);
        return $queryBuilder->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param string $tableName
     * @param array<mixed> $listOfFields
     * @return array<mixed>
     */
    public static function listAllInTable(string $tableName, array $listOfFields)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = (GeneralUtility::makeInstance(ConnectionPool::class))->getConnectionForTable($tableName)
            ->createQueryBuilder();
        $queryBuilder->select(...$listOfFields)
            ->from($tableName);
        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }


    /**
     * @param array<mixed> $whereInfos
     * @param QueryBuilder $queryBuilder
     */
    protected function addWhereConditionsToQueryBuilder(array $whereInfos, QueryBuilder $queryBuilder): void
    {
        foreach ($whereInfos as $condition) {
            $field = $condition[UpdateTimerCommand::YAML_SUBWHERE_FIELD];
            if (!empty($field)) {
                $value = $condition[UpdateTimerCommand::YAML_SUBWHERE_VALUE] ?: '';
                $compare = $condition[UpdateTimerCommand::YAML_SUBWHERE_COMPARE] ?: 'eq';
                if (array_key_exists(UpdateTimerCommand::YAML_SUBWHERE_TYPE, $condition) &&
                    ($condition[UpdateTimerCommand::YAML_SUBWHERE_TYPE] === 'int')
                ) {
                    $type = ParameterType::INTEGER;
                } else {
                    $type = ParameterType::STRING;
                }
                $queryBuilder->expr()->$compare(
                    $field,
                    $queryBuilder->createNamedParameter($value, $type)
                );
            }
        }
    }
}
