<?php

declare(strict_types=1);

namespace Porthd\Timer\Upgrade;

use Porthd\Timer\CustomTimer\DefaultTimer;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('timer_defaultValueForTimerTimerUpgrade')]
final class DefaultValueForTimerTimerUpgrade implements UpgradeWizardInterface
{
    protected Connection $pages;
    protected Connection $ttContent;
    protected Connection $sysFileReference;
    protected Connection $txTimerDomainModelEvent;
    protected Connection $txTimerDomainModelListing;

    public function __construct()
    {
        $this->pages = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $this->ttContent = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');
        $this->sysFileReference = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_reference');
        $this->txTimerDomainModelEvent = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_timer_domain_model_event');
        $this->txTimerDomainModelListing = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_timer_domain_model_listing');
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
        return 'TYPO3 12.3 will cause an exception, if the selectorfield for a flex-field is empty. ' .
            'Empty field will be filled with the identfier of the `default`-timer.';
    }

    /**
     * define default-Values
     *
     */
    public function executeUpdate(): bool
    {

        $this->ttContent
            ->prepare('UPDATE tt_content SET tx_timer_selector = ' . DefaultTimer::TIMER_NAME .
                ' WHERE tx_timer_selector = "";')
            ->execute();
        $this->pages
            ->prepare('UPDATE pages SET tx_timer_selector = ' . DefaultTimer::TIMER_NAME .
                ' WHERE tx_timer_selector = "";')
            ->execute();
        $this->sysFileReference
            ->prepare('UPDATE sys_file_reference SET tx_timer_selector = ' . DefaultTimer::TIMER_NAME .
                ' WHERE tx_timer_selector = "";')
            ->execute();
        $this->txTimerDomainModelListing
            ->prepare('UPDATE tx_timer_domain_model_listing SET tx_timer_selector = ' . DefaultTimer::TIMER_NAME .
                ' WHERE tx_timer_selector = "";')
            ->execute();
        $this->txTimerDomainModelEvent
            ->prepare('UPDATE tx_timer_domain_model_event SET tx_timer_selector = ' . DefaultTimer::TIMER_NAME .
                ' WHERE tx_timer_selector = "";')
            ->execute();
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
        return true;
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
