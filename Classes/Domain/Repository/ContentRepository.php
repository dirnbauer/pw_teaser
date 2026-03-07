<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Domain\Repository;

use PwTeaserTeam\PwTeaser\Domain\Model\Page;
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
     * @return QueryResultInterface All found objects, will be
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
        return $query->execute();
    }

    /**
     * Returns all objects of this repository which are located inside the
     * given pages
     *
     * @param array<Page> $pages Pages to get content elements
     * @param array<Page> $pages
     * @return QueryResultInterface All found objects, will be
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

        return $query->execute();
    }
}
