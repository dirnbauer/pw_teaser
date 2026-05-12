<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Domain\Repository;

use PwTeaserTeam\PwTeaser\Domain\Model\Content;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 *  |     2016 Tim Klein-Hitpass <tim.klein-hitpass@diemedialen.de>
 *  |     2016 Kai Ratzeburg <kai.ratzeburg@diemedialen.de>
 */

/**
 * Repository for Content model
 *
 * @extends Repository<Content>
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class ContentRepository extends Repository
{

    /**
     * Initializes the repository.
     *
     * @return void
     */
    public function initializeObject(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Returns all objects of this repository which matches the given pid. This
     * overwritten method exists, to perform sorting
     *
     * @param integer $pid Pid to search for
     * @return QueryResultInterface<int, Content> All found objects, will be
     *         empty if there are no objects
     */
    public function findByPid(int $pid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('pid', $pid));
        $query->setOrderings(
            [
                'sorting' => QueryInterface::ORDER_ASCENDING
            ]
        );
        $result = $query->execute();
        $this->enrichWithContentRows($result);
        return $result;
    }

    /**
     * Returns all objects of this repository which are located inside the
     * given pages
     *
     * @param array<Page> $pages Pages to get content elements
     * @return QueryResultInterface<int, Content> All found objects, will be
     *         empty if there are no objects
     */
    public function findByPages(array $pages): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [];

        foreach ($pages as $page) {
            $constraints[] = $query->equals('pid', $page->getUid());
        }

        if ($constraints === []) {
            $query->matching($query->equals('pid', -1));
            return $query->execute();
        }

        $query->matching($query->logicalOr(...$constraints));

        $result = $query->execute();
        $this->enrichWithContentRows($result);
        return $result;
    }

    /**
     * Bulk-loads raw tt_content rows and sets contentRow on each model,
     * so __call() doesn't need separate DB queries.
     */
    protected function enrichWithContentRows(QueryResultInterface $result): void
    {
        $items = $result->toArray();
        if ($items === []) {
            return;
        }

        $uids = [];
        foreach ($items as $content) {
            $uid = $content->getUid();
            if ($uid !== null) {
                $uids[] = $uid;
            }
        }
        if ($uids === []) {
            return;
        }

        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $pool->getQueryBuilderForTable('tt_content');
        $rows = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $rowsByUid = [];
        foreach ($rows as $row) {
            $rowsByUid[(int)$row['uid']] = $row;
        }

        foreach ($items as $content) {
            $uid = $content->getUid();
            if ($uid !== null && isset($rowsByUid[$uid])) {
                $content->setContentRow($rowsByUid[$uid]);
            }
        }
    }
}
