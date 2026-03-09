<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Domain\Repository;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 *  |     2016 Tim Klein-Hitpass <tim.klein-hitpass@diemedialen.de>
 *  |     2016 Kai Ratzeburg <kai.ratzeburg@diemedialen.de>
 */
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for Page model
 *
 * @extends Repository<Page>
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class PageRepository extends Repository
{
    /** Category Mode: Or */
    public const CATEGORY_MODE_OR = 1;
    /** Category Mode: And */
    public const CATEGORY_MODE_AND = 2;
    /** Category Mode: Or Not */
    public const CATEGORY_MODE_OR_NOT = 3;
    /** Category Mode: And Not */
    public const CATEGORY_MODE_AND_NOT = 4;

    protected string $orderBy = 'uid';

    protected string $orderDirection = QueryInterface::ORDER_ASCENDING;

    /** @var QueryInterface<Page>|null */
    protected ?QueryInterface $query = null;

    /**
     * @var list<ConstraintInterface>
     */
    protected array $queryConstraints = [];

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
        $this->query = $this->createQuery();
    }

    /**
     * Returns all objects of this repository which match the pid
     *
     * @param integer $pid the pid to search for
     * @return array<int, Page> All found pages, will be empty if the result is empty
     */
    public function findByPid(int $pid): array
    {
        assert($this->query !== null);
        $translatedPid = $this->translatePids([$pid]);

        $this->addQueryConstraint($this->query->equals('pid', (int)reset($translatedPid)));
        return $this->executeQuery();
    }

    /**
     * Returns all objects of this repository which are children of the matched
     * pid (recursively)
     *
     * @param integer $pid the pid to search for recursively
     * @param integer $recursionDepthFrom Start of recursion depth
     * @param integer $recursionDepth Depth of recursion
     * @return array<int, Page> All found pages, will be empty if the result is empty
     */
    public function findByPidRecursively(int $pid, int $recursionDepthFrom, int $recursionDepth): array
    {
        return $this->findChildrenRecursivelyByPidList((string)$pid, $recursionDepthFrom, $recursionDepth);
    }

    /**
     * Returns all objects of this repository which are in the pidlist
     *
     * @param string $pidlist comma seperated list of pids to search for
     * @param boolean $orderByPlugin setting of ordering by plugin
     * @return array<int, Page> All found pages, will be empty if the result is empty
     */
    public function findByPidList(string $pidlist, bool $orderByPlugin = false): array
    {
        assert($this->query !== null);
        $pagePids = GeneralUtility::intExplode(',', $pidlist, true);

        // early return when list is empty to prevent sql exception
        if (empty($pagePids)) {
            return [];
        }

        $query = $this->query;
        $this->addQueryConstraint($query->in('uid', $this->translatePids($pagePids)));
        $query->matching($query->logicalAnd(...$this->queryConstraints));

        if ($orderByPlugin === false) {
            $this->handleOrdering($query);
            $results = $query->execute();
            $this->resetQuery();
            return $this->handlePageLocalization($results);
        } else {
            $results = $query->execute();
            $this->resetQuery();
            return $this->orderByPlugin($pagePids, $this->handlePageLocalization($results));
        }
    }

    /**
     * Creates array of result items, with the order of given pagePids
     *
     * @param array<int> $pagePids pagePids to order for
     * @param array<int, Page> $results results to reorder
     * @return array<int, Page> results ordered by plugin
     */
    protected function orderByPlugin(array $pagePids, array $results): array
    {
        $sortedResults = [];
        foreach ($pagePids as $pagePid) {
            foreach ($results as $result) {
                if ($pagePid === $result->getUid()) {
                    $sortedResults[] = $result;
                    continue;
                }
            }
        }
        return $sortedResults;
    }

    /**
     * Returns all objects of this repository which are in the pidlist
     *
     * @param string $pidlist comma seperated list of pids to search for
     * @return array<int, Page> All found pages, will be empty if the result is empty
     */
    public function findChildrenByPidList(string $pidlist): array
    {
        assert($this->query !== null);
        $pagePids = GeneralUtility::intExplode(',', $pidlist, true);

        // early return when list is empty to prevent sql exception
        if (empty($pagePids)) {
            return [];
        }

        $this->addQueryConstraint($this->query->in('pid', $this->translatePids($pagePids)));
        return $this->executeQuery();
    }

    /**
     * Returns all objects of this repository which are children of pages in the
     * pidlist (recursively)
     *
     * @param string $pidlist comma seperated list of pids to search for
     * @param integer $recursionDepthFrom Start level for recursion
     * @param integer $recursionDepth Depth of recursion
     * @return array<int, Page> All found pages, will be empty if the result is empty
     */
    public function findChildrenRecursivelyByPidList(string $pidlist, int $recursionDepthFrom, int $recursionDepth): array
    {
        assert($this->query !== null);
        $pagePids = $this->getRecursivePageList($pidlist, $recursionDepthFrom, $recursionDepth);
        $translatedPids = $this->translatePids($pagePids);
        if (!empty($translatedPids)) {
            $this->addQueryConstraint($this->query->in('uid', $translatedPids));
        } else {
            $this->addQueryConstraint($this->query->in('uid', $pagePids));
        }

        return $this->executeQuery();
    }

    /**
     * @param array<int> $pidList
     * @return array<int>
     */
    protected function translatePids(array $pidList, ?int $languageUid = null): array
    {
        if (empty($pidList)) {
            return $pidList;
        }

        if ($languageUid === null) {
            /** @var Context $context */
            $context = GeneralUtility::makeInstance(Context::class);
            $languageUid = $context->getPropertyFromAspect('language', 'id');
        }

        /** @var ConnectionPool $pool */
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);

        $translatedPidList = [];
        foreach ($pidList as $pid) {
            $queryBuilder = $pool->getQueryBuilderForTable('pages');
            $translatedRow = $queryBuilder->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'l10n_parent',
                        $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'deleted',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
            if ($translatedRow) {
                $uid = $translatedRow['uid'];
                $translatedPidList[$pid] = is_int($uid) ? $uid : (is_string($uid) || is_float($uid) ? (int) $uid : $pid);
            } else {
                $translatedPidList[$pid] = $pid;
            }
        }

        return array_values($translatedPidList);
    }

    /**
     * Adds query constraint to array
     *
     * @param ConstraintInterface $constraint Constraint to add
     * @return void
     */
    protected function addQueryConstraint(ConstraintInterface $constraint): void
    {
        $this->queryConstraints[] = $constraint;
    }

    /**
     * Add category constraint
     *
     * @param array<int, mixed> $categories
     * @param boolean $isAnd If TRUE categories get a logicalAnd. Otherwise a logicalOr.
     * @param boolean $isNot If TRUE categories get a logicalNot operator. Otherwise not.
     * @return void
     */
    public function addCategoryConstraint(array $categories, bool $isAnd = true, bool $isNot = false): void
    {
        assert($this->query !== null);
        if ($isAnd === true && $isNot === false) {
            $this->queryConstraints[] = $this->query->logicalAnd(...$this->buildCategoryConstraint($categories));
        }
        if ($isAnd === true && $isNot === true) {
            $this->queryConstraints[] = $this->query->logicalNot(
                $this->query->logicalAnd(
                    ...$this->buildCategoryConstraint($categories)
                )
            );
        }
        if ($isAnd === false && $isNot === false) {
            $this->queryConstraints[] = $this->query->logicalOr(...$this->buildCategoryConstraint($categories));
        }
        if ($isAnd === false && $isNot === true) {
            $this->queryConstraints[] = $this->query->logicalNot(
                $this->query->logicalOr(
                    ...$this->buildCategoryConstraint($categories)
                )
            );
        }
    }

    /**
     * Build category constraint for each category (contains)
     *
     * @param array<int, mixed> $categories
     * @return list<ConstraintInterface>
     */
    protected function buildCategoryConstraint(array $categories): array
    {
        assert($this->query !== null);
        $constraints = [];
        foreach ($categories as $category) {
            $constraints[] = $this->query->contains('categories', $category);
        }
        return $constraints;
    }

    /**
     * Finalize given query constraints and executes the query
     *
     * @return array<Page> Result of query
     */
    protected function executeQuery(): array
    {
        assert($this->query !== null);
        $query = $this->query;
        $query->matching($query->logicalAnd(...$this->queryConstraints));
        $this->handleOrdering($query);

        $queryResult = $query->execute();
        $this->resetQuery();

        $queryResult = $this->handlePageLocalization($queryResult);
        return $queryResult;
    }

    /**
     * Handles page localization
     *
     * @param QueryResultInterface<int, Page> $pages
     * @return array<int, Page>
     */
    protected function handlePageLocalization(QueryResultInterface $pages): array
    {
        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $currentLangUid = $context->getPropertyFromAspect('language', 'id');
        $displayedPages = [];

        /** @var Page $page */
        foreach ($pages as $page) {
            if ($currentLangUid === 0) {
                if ($page->getL18nConfiguration() !== Page::L18N_HIDE_DEFAULT_LANGUAGE &&
                    $page->getL18nConfiguration() !== Page::L18N_HIDE_ALWAYS_BUT_TRANSLATION_EXISTS) {
                    $displayedPages[] = $page;
                }
            } else {
                $pageUid = $page->getUid();
                $langUid = is_int($currentLangUid) ? $currentLangUid : (is_string($currentLangUid) || is_float($currentLangUid) ? (int) $currentLangUid : 0);
                $translationExists = $pageUid !== null && $this->pageHasTranslation($pageUid, $langUid);
                $requiresTranslation = in_array(
                    $page->getL18nConfiguration(),
                    [
                        Page::L18N_HIDE_IF_NO_TRANSLATION_EXISTS,
                        Page::L18N_HIDE_ALWAYS_BUT_TRANSLATION_EXISTS,
                    ],
                    true
                );

                if (!$requiresTranslation || $translationExists) {
                    $displayedPages[] = $page;
                }
            }
        }
        return $displayedPages;
    }

    /**
     * Get subpages recursivley of given pid(s).
     *
     * @param string $pidlist List of pageUids to get subpages of. May contain a single uid.
     * @param integer $recursionDepthFrom Start of recursion depth
     * @param integer $recursionDepth Depth of recursion
     * @return array<int> Found subpages, recursivley
     */
    protected function getRecursivePageList(string $pidlist, int $recursionDepthFrom, int $recursionDepth): array
    {
        $pagePids = [];
        $pids = GeneralUtility::intExplode(',', $pidlist, true);
        foreach ($pids as $pid) {
            $pageList = $this->collectRecursivePageIds($pid, (int)$recursionDepthFrom, (int)$recursionDepth);
            $pagePids = array_merge($pagePids, $pageList);
            if ($recursionDepthFrom === 0) {
                array_unshift($pagePids, $pid);
            }
        }
        return array_unique($pagePids);
    }

    /**
     * Sets the order by which is used by all find methods
     *
     * @param string $orderBy property to order by
     * @return void
     */
    public function setOrderBy(string $orderBy): void
    {
        if ($orderBy !== 'random') {
            $this->orderBy = $orderBy;
        }
    }

    /**
     * Sets the order direction which is used by all find methods
     *
     * @param string $orderDirection the direction to order, may be desc or asc
     * @return void
     */
    public function setOrderDirection(string|int $orderDirection): void
    {
        if ($orderDirection === 'desc' || $orderDirection === 1) {
            $this->orderDirection = QueryInterface::ORDER_DESCENDING;
        } else {
            $this->orderDirection = QueryInterface::ORDER_ASCENDING;
        }
    }

    /**
     * Sets the query limit
     *
     * @param integer $limit The limit of elements to show
     * @return void
     */
    public function setLimit(int $limit): void
    {
        assert($this->query !== null);
        $this->query->setLimit($limit);
    }

    /**
     * Sets the nav_hide_state flag
     *
     * @param boolean $showNavHiddenItems If TRUE lets show items which should not be visible in navigation.
     *        Default is FALSE.
     * @return void
     */
    public function setShowNavHiddenItems(bool $showNavHiddenItems): void
    {
        assert($this->query !== null);
        if ($showNavHiddenItems === true) {
            $this->addQueryConstraint($this->query->in('nav_hide', [0, 1]));
        } else {
            $this->addQueryConstraint($this->query->in('nav_hide', [0]));
        }
    }

    /**
     * Sets doktypes to filter for
     *
     * @param array<int> $dokTypesToFilterFor doktypes as array, may be empty
     * @return void
     */
    public function setFilteredDokType(array $dokTypesToFilterFor): void
    {
        assert($this->query !== null);
        if (count($dokTypesToFilterFor) > 0) {
            $this->addQueryConstraint($this->query->in('doktype', $dokTypesToFilterFor));
        }
    }

    /**
     * Ignores given uid
     *
     * @param integer $currentPageUid Uid to ignore
     * @return void
     */
    public function setIgnoreOfUid(int $currentPageUid): void
    {
        assert($this->query !== null);
        $query = $this->query;
        $this->addQueryConstraint($query->logicalNot($query->equals('uid', $currentPageUid)));
        $this->addQueryConstraint($query->logicalNot($query->equals('l10n_parent', $currentPageUid)));
    }

    /**
     * Adds handle of ordering to query object
     *
     * @param QueryInterface<Page> $query
     * @return void
     */
    protected function handleOrdering(QueryInterface $query): void
    {
        $query->setOrderings([$this->orderBy => $this->orderDirection]);
    }

    /**
     * Resets query and queryConstraints after execution
     *
     * @return void
     */
    protected function resetQuery(): void
    {
        unset($this->query);
        $this->query = $this->createQuery();
        unset($this->queryConstraints);
        $this->queryConstraints = [];
    }

    protected function pageHasTranslation(int $pageUid, int $languageUid): bool
    {
        /** @var ConnectionPool $pool */
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $pool->getQueryBuilderForTable('pages');

        $translatedRow = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return $translatedRow !== false;
    }

    /**
     * @return array<int>
     */
    protected function collectRecursivePageIds(int $rootPid, int $recursionDepthFrom, int $recursionDepth): array
    {
        if ($recursionDepth < 1) {
            return [];
        }

        $pageIds = [];
        $this->collectDescendantPageIds($rootPid, 1, $recursionDepthFrom, $recursionDepth, $pageIds);

        return $pageIds;
    }

    /**
     * @param array<int> $pageIds
     */
    protected function collectDescendantPageIds(
        int $parentPid,
        int $currentDepth,
        int $recursionDepthFrom,
        int $recursionDepth,
        array &$pageIds
    ): void {
        if ($currentDepth > $recursionDepth) {
            return;
        }

        foreach ($this->getDirectChildPageIds($parentPid) as $childPid) {
            if ($currentDepth >= $recursionDepthFrom) {
                $pageIds[] = $childPid;
            }
            $this->collectDescendantPageIds($childPid, $currentDepth + 1, $recursionDepthFrom, $recursionDepth, $pageIds);
        }
    }

    /**
     * @return array<int>
     */
    protected function getDirectChildPageIds(int $parentPid): array
    {
        /** @var ConnectionPool $pool */
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $pool->getQueryBuilderForTable('pages');

        $childPageIds = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($parentPid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchFirstColumn();

        return array_map(static function (mixed $v): int {
            return is_int($v) ? $v : (is_string($v) || is_float($v) ? (int) $v : 0);
        }, $childPageIds);
    }
}
