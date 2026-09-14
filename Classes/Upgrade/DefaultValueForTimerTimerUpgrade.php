<?php

declare(strict_types=1);

namespace Porthd\Timer\Upgrade;

use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[\TYPO3\CMS\Core\Attribute\UpgradeWizard('timer_defaultValueForTimerTimerUpgrade')]
final class DefaultValueForTimerTimerUpgrade implements \TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface
{
    private const TYPO3_VERSION_ALLOWED = 12;
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
        return 'EXT timer: Set `default` in empty field `tx_timer_selector`';
    }

    /**
     * Return the description for this wizard
     */
    public function getDescription(): string
    {
        return 'TYPO3 ' . self::TYPO3_VERSION_ALLOWED . ' will cause an exception, if the selectorfield for a flex-field is empty. ' .
            'Empty field will be filled with the identfier of the `default`-timer.';
    }

    /**
     * define default-Values
     */
    public function executeUpdate(): bool
    {
        foreach (['tt_content' => $this->ttContent,
            'pages' => $this->pages,
            'sys_file_reference' => $this->sysFileReference,
            'tx_timer_domain_model_listing' => $this->txTimerDomainModelListing,
            'tx_timer_domain_model_event' => $this->txTimerDomainModelEvent,
        ] as $table => $queryBuilder) {

            $queryBuilder
                ->update($table)
                ->set('tx_timer_selector', 'default')
                ->where(
                    $queryBuilder->expr()->isNotNull('tx_timer_selector'),
                    $queryBuilder->expr()->notLike(
                        'tx_timer_selector',
                        '""'
                    )
                );
            $queryBuilder
                ->executeQuery();
        }
        return true;
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
        return (int)$typo3Version->getMajorVersion() === self::TYPO3_VERSION_ALLOWED;
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
            \TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite::class,
        ];
    }
}
