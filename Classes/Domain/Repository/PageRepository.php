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
use PwTeaserTeam\PwTeaser\Database\RecordRowLoader;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Loads the pages of a teaser. Every find method builds its own query from the
 * given PageFilter, so the repository holds no request state.
 *
 * @extends Repository<Page>
 */
final class PageRepository extends Repository
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly Context $context,
        private readonly RecordRowLoader $rowLoader,
    ) {
        parent::__construct();
    }

    /**
     * Teaser pages live anywhere in the page tree, never below the storage pid.
     * Built per query so every query sees the language of the current request.
     *
     * @return QueryInterface<Page>
     */
    public function createQuery(): QueryInterface
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        return $query;
    }

    /**
     * Direct children of the given pages.
     *
     * @param list<int> $parentUids
     * @return list<Page>
     */
    public function findChildren(array $parentUids, PageFilter $filter): array
    {
        if ($parentUids === []) {
            return [];
        }
        $query = $this->createQuery();
        return $this->execute($query, $query->in('pid', $parentUids), $filter);
    }

    /**
     * Descendants of the given pages between the two depth levels (1 = direct children).
     * With $depthFrom = 0 the given pages themselves are part of the result.
     *
     * @param list<int> $rootUids
     * @return list<Page>
     */
    public function findDescendants(array $rootUids, int $depthFrom, int $depth, PageFilter $filter): array
    {
        $uids = $this->collectDescendantUids($rootUids, $depthFrom, $depth);
        if ($uids === []) {
            return [];
        }
        $query = $this->createQuery();
        return $this->execute($query, $query->in('uid', $this->translateUids($uids)), $filter);
    }

    /**
     * The given pages themselves, ordered by the filter or - with $keepGivenOrder - as listed.
     *
     * @param list<int> $uids
     * @return list<Page>
     */
    public function findByUids(array $uids, bool $keepGivenOrder, PageFilter $filter): array
    {
        if ($uids === []) {
            return [];
        }
        $query = $this->createQuery();
        $pages = $this->execute($query, $query->in('uid', $this->translateUids($uids)), $filter, !$keepGivenOrder);
        return $keepGivenOrder ? $this->sortByUidList($pages, $uids) : $pages;
    }

    /**
     * @param QueryInterface<Page> $query
     * @return list<Page>
     */
    private function execute(QueryInterface $query, ConstraintInterface $selection, PageFilter $filter, bool $orderInDatabase = true): array
    {
        $query->matching($query->logicalAnd($selection, ...$this->filterConstraints($query, $filter)));
        if ($orderInDatabase) {
            $query->setOrderings([
                $filter->orderBy => $filter->descending ? QueryInterface::ORDER_DESCENDING : QueryInterface::ORDER_ASCENDING,
            ]);
        }
        if ($filter->limit > 0) {
            $query->setLimit($filter->limit);
        }

        $pages = $this->filterByTranslationVisibility($query->execute()->toArray());
        $this->attachRawRows($pages);
        return $pages;
    }

    /**
     * @param QueryInterface<Page> $query
     * @return list<ConstraintInterface>
     */
    private function filterConstraints(QueryInterface $query, PageFilter $filter): array
    {
        $constraints = [];
        if (!$filter->showNavHiddenItems) {
            $constraints[] = $query->equals('nav_hide', 0);
        }
        if ($filter->doktypes !== []) {
            $constraints[] = $query->in('doktype', $filter->doktypes);
        }
        foreach ($filter->ignoredUids as $uid) {
            $constraints[] = $query->logicalNot($query->equals('uid', $uid));
            $constraints[] = $query->logicalNot($query->equals('l10n_parent', $uid));
        }
        if ($filter->categoryUids !== [] && $filter->categoryMode !== null) {
            $categoryConstraints = array_map(
                static fn(int $categoryUid): ConstraintInterface => $query->contains('categories', $categoryUid),
                $filter->categoryUids
            );
            $combined = $filter->categoryMode->isAnd()
                ? $query->logicalAnd(...$categoryConstraints)
                : $query->logicalOr(...$categoryConstraints);
            $constraints[] = $filter->categoryMode->isNegated() ? $query->logicalNot($combined) : $combined;
        }
        return $constraints;
    }

    /**
     * Applies the l18n_cfg flags of each page for the current language, like the page tree does.
     *
     * @param array<int, Page> $pages
     * @return list<Page>
     */
    private function filterByTranslationVisibility(array $pages): array
    {
        $languageUid = $this->context->getAspect('language')->getId();
        if ($languageUid === 0) {
            return array_values(array_filter(
                $pages,
                static fn(Page $page): bool => !$page->getTranslationVisibility()->shouldBeHiddenInDefaultLanguage()
            ));
        }

        $uidsRequiringTranslation = [];
        foreach ($pages as $page) {
            if ($page->getTranslationVisibility()->shouldHideTranslationIfNoTranslatedRecordExists()) {
                $uidsRequiringTranslation[] = (int)$page->getUid();
            }
        }
        $translated = $this->findTranslations($uidsRequiringTranslation, $languageUid);

        return array_values(array_filter(
            $pages,
            static fn(Page $page): bool => !$page->getTranslationVisibility()->shouldHideTranslationIfNoTranslatedRecordExists()
                || isset($translated[(int)$page->getUid()])
        ));
    }

    /**
     * Replaces default-language uids with the uid of their translation in the current
     * language, so translated pages are found when selecting pages by uid. Page
     * translations keep the pid of their original, so pids are never translated.
     *
     * @param list<int> $uids
     * @return list<int>
     */
    private function translateUids(array $uids): array
    {
        $languageUid = $this->context->getAspect('language')->getId();
        if ($languageUid === 0) {
            return $uids;
        }
        $translations = $this->findTranslations($uids, $languageUid);
        return array_map(static fn(int $uid): int => $translations[$uid] ?? $uid, $uids);
    }

    /**
     * @param list<int> $defaultLanguageUids
     * @return array<int, int> Translated uid, indexed by default-language uid
     */
    private function findTranslations(array $defaultLanguageUids, int $languageUid): array
    {
        if ($defaultLanguageUids === []) {
            return [];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $rows = $queryBuilder
            ->select('uid', 'l10n_parent')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($defaultLanguageUids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                )
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        $translations = [];
        foreach ($rows as $row) {
            $translations[self::int($row['l10n_parent'] ?? 0)] ??= self::int($row['uid'] ?? 0);
        }
        return $translations;
    }

    /**
     * Uids of all (not deleted, not hidden) default-language descendants, level by level.
     *
     * @param list<int> $rootUids
     * @return list<int>
     */
    private function collectDescendantUids(array $rootUids, int $depthFrom, int $depth): array
    {
        $uids = $depthFrom === 0 ? $rootUids : [];
        $level = $rootUids;
        for ($currentDepth = 1; $currentDepth <= $depth && $level !== []; $currentDepth++) {
            $level = $this->findChildUids($level);
            if ($currentDepth >= $depthFrom) {
                $uids = [...$uids, ...$level];
            }
        }
        return array_values(array_unique($uids));
    }

    /**
     * @param list<int> $parentUids
     * @return list<int>
     */
    private function findChildUids(array $parentUids): array
    {
        if ($parentUids === []) {
            return [];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $childUids = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($parentUids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchFirstColumn();

        return array_map(self::int(...), $childUids);
    }

    /**
     * @param list<Page> $pages
     */
    private function attachRawRows(array $pages): void
    {
        $rows = $this->rowLoader->loadByUids(
            'pages',
            array_map(static fn(Page $page): int => (int)$page->getUid(), $pages)
        );
        foreach ($pages as $page) {
            $page->setPageRow($rows[(int)$page->getUid()] ?? []);
        }
    }

    /**
     * @param list<Page> $pages
     * @param list<int> $uids
     * @return list<Page>
     */
    private function sortByUidList(array $pages, array $uids): array
    {
        $pagesByUid = [];
        foreach ($pages as $page) {
            $pagesByUid[(int)$page->getUid()] = $page;
        }
        $sorted = [];
        foreach ($uids as $uid) {
            if (isset($pagesByUid[$uid])) {
                $sorted[] = $pagesByUid[$uid];
            }
        }
        return $sorted;
    }

    private static function int(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }
}
