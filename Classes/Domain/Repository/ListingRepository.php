<?php
namespace Porthd\Timer\Domain\Repository;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dr. Dieter Porthd <info@mobger.de>
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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ListingRepository
{
    protected const TABLE = 'tx_timer_domain_model_listing';

    public function findByCommaList($commaList = '')
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);

        $queryBuilder->select('*')
            ->from(self::TABLE)
            ->where(
            $queryBuilder->expr()->in('uid',$commaList)
        );
        return $queryBuilder->execute()->fetchAllAssociative();
//        //@todo remove EXTbasequery
//        $query = $this->createQuery();
//        $query->matching(
//            $query->in(
//                'uid',$commaList
//            )
//        );
//        return $query->execute()->toArray();

    }

}