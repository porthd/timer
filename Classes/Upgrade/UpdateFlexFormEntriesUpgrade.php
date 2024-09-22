<?php

declare(strict_types=1);

namespace Porthd\Timer\Upgrade;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('timer_updateFlexFormEntriesUpgrade')]
final class UpdateFlexFormEntriesUpgrade implements UpgradeWizardInterface
{
    private const TYPO3_VERSION_ALLOWED = 13;

    private QueryBuilder $pages;
    private QueryBuilder $ttContent;
    private QueryBuilder $sysFileReference;
    private QueryBuilder $txTimerDomainModelEvent;
    private QueryBuilder $txTimerDomainModelListing;

    public function __construct()
    {
        $this->pages = (GeneralUtility::makeInstance(ConnectionPool::class))->getQueryBuilderForTable('pages');
        $this->ttContent = (GeneralUtility::makeInstance(ConnectionPool::class))->getQueryBuilderForTable('tt_content');
        $this->sysFileReference = (GeneralUtility::makeInstance(ConnectionPool::class))->getQueryBuilderForTable('sys_file_reference');
        $this->txTimerDomainModelEvent = (GeneralUtility::makeInstance(ConnectionPool::class))->getQueryBuilderForTable('tx_timer_domain_model_event');
        $this->txTimerDomainModelListing = (GeneralUtility::makeInstance(ConnectionPool::class))->getQueryBuilderForTable('tx_timer_domain_model_listing');
    }

    /**
     * Return the speaking name of this wizard
     */
    public function getTitle(): string
    {
        return 'EXT timer: Update current flexform-definitions in field `tx_timer_timer`';
    }

    /**
     * Return the description for this wizard
     */
    public function getDescription(): string
    {
        return 'TYPO3 ' . self::TYPO3_VERSION_ALLOWED . ' will cause an error after update, if the flexform-field `tx_timer_timer` contains the superfluid tag <TCEFORM>.';
    }

    /**
     * define default-Values
     *
     */
    public function executeUpdate(): bool
    {
        /**
         * @var  string $table
         * @var  QueryBuilder $queryBuilder
         */
        $success = true;
        foreach (['tt_content' => $this->ttContent,
                     'pages' => $this->pages,
                     'sys_file_reference' => $this->sysFileReference,
                     'tx_timer_domain_model_listing' => $this->txTimerDomainModelListing,
                     'tx_timer_domain_model_event' => $this->txTimerDomainModelEvent,
                 ] as $table => $queryBuilder) {
            $queryBuilder->select('uid', 'tx_timer_timer')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->isNotNull('tx_timer_timer'),
                    $queryBuilder->expr()->like(
                        'tx_timer_timer',
                        '""'
                    )
                );
            $list = $queryBuilder->executeQuery()->fetchAllAssociative();
            if (!empty($list)) {
                foreach ($list as [$uid, $flexformString]) {
                    $helpQuery = (GeneralUtility::makeInstance(ConnectionPool::class))
                        ->getQueryBuilderForTable($table);
                    $help = preg_replace('/\<TCEFORM>/i', '', $flexformString);
                    $newFlexformString = preg_replace('/\</TCEFORM>/i', '', $help);
                    $helpQuery->update($table)
                        ->set('tx_timer_timer', $newFlexformString)
                        ->where(
                            $helpQuery->expr()->eq(
                                'uid',
                                $helpQuery->createNamedParameter($uid, Connection::PARAM_INT)
                            )
                        );
                    $success = $success && ($helpQuery->executeStatement() > 0);
                    unset($helpQuery);
                }
            }
            unset($queryBuilder);
        }
        return $success;
    }

    /**
     * There are only empty values, which will be removed. The upgrade will only define the default-case in a better way.
     * Therefor the upgrade is required/necessary
     *
     *
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        /** @var Typo3Version $typo3Version */
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        return ((int)$typo3Version->getMajorVersion() === self::TYPO3_VERSION_ALLOWED);
    }

    /**
     * Returns an array of class names of prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}
