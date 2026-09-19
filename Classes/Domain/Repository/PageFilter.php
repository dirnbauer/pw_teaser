<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Domain\Repository;

/**
 * Constraints, ordering and limit that PageRepository applies to every page query.
 */
final readonly class PageFilter
{
    /**
     * @param string $orderBy Property (or pages column) to order by
     * @param list<int> $doktypes Allowed doktypes; empty allows all
     * @param list<int> $ignoredUids Pages (and their translations) to exclude
     * @param list<int> $categoryUids Categories to filter by; only used with a $categoryMode
     */
    public function __construct(
        public string $orderBy = 'uid',
        public bool $descending = false,
        public int $limit = 0,
        public bool $showNavHiddenItems = false,
        public array $doktypes = [],
        public array $ignoredUids = [],
        public array $categoryUids = [],
        public ?CategoryMode $categoryMode = null,
    ) {}
}
