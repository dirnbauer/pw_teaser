<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Settings;

use PwTeaserTeam\PwTeaser\Domain\Repository\CategoryMode;
use PwTeaserTeam\PwTeaser\Domain\Repository\PageFilter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Typed view of the plugin settings (FlexForm merged over TypoScript) after the
 * plugin rules have been applied: "custom" pages are always flat, nested mode
 * ignores recursionDepthFrom, orderBy and limit.
 */
final readonly class TeaserSettings
{
    /**
     * Raw defaults, merged below the rendered settings so templates can rely on every key.
     *
     * @var array<string, string|int>
     */
    public const DEFAULTS = [
        'source' => 'thisChildren',
        'customPages' => '',
        'recursionDepthFrom' => 0,
        'recursionDepth' => 255,
        'orderByPlugin' => '',
        'loadContents' => '',
        'pageMode' => '',
        'enablePagination' => 1,
        'itemsPerPage' => 10,
        'orderBy' => '',
        'orderByCustomField' => '',
        'orderDirection' => '',
        'limit' => '',
        'showNavHiddenItems' => '',
        'hideCurrentPage' => '',
        'showDoktypes' => '',
        'ignoreUids' => '',
        'categoriesList' => '',
        'categoryMode' => '',
        'paginationClass' => '',
    ];

    /**
     * @param list<int> $customPageUids
     * @param string $orderBy Requested ordering ('' = default, 'random' and 'sorting' are partly applied in PHP)
     * @param int $limit Requested limit; the database limit lives in $filter
     */
    private function __construct(
        public TeaserSource $source,
        public array $customPageUids,
        public int $recursionDepthFrom,
        public int $recursionDepth,
        public bool $orderByPlugin,
        public bool $loadContents,
        public bool $nested,
        public bool $enablePagination,
        public int $itemsPerPage,
        public string $paginationClass,
        public string $orderBy,
        public bool $descending,
        public int $limit,
        public PageFilter $filter,
    ) {}

    /**
     * @param array<string, mixed> $settings
     */
    public static function fromArray(array $settings, int $currentPageUid): self
    {
        $source = TeaserSource::tryFrom(self::string($settings, 'source')) ?? TeaserSource::ThisChildren;
        $nested = $source !== TeaserSource::Custom && self::string($settings, 'pageMode') === 'nested';

        $orderBy = $nested ? '' : self::string($settings, 'orderBy');
        $descending = strtolower(self::string($settings, 'orderDirection')) === 'desc';
        $limit = $nested ? 0 : self::int($settings, 'limit');

        $ignoredUids = self::intList($settings, 'ignoreUids');
        if (self::bool($settings, 'hideCurrentPage')) {
            $ignoredUids[] = $currentPageUid;
        }
        $categoryUids = self::intList($settings, 'categoriesList');
        $categoryMode = CategoryMode::tryFrom(self::int($settings, 'categoryMode'));

        $filter = new PageFilter(
            orderBy: self::databaseOrderBy($orderBy, self::string($settings, 'orderByCustomField')),
            descending: $descending,
            limit: $orderBy === 'random' ? 0 : $limit,
            showNavHiddenItems: self::bool($settings, 'showNavHiddenItems'),
            doktypes: self::intList($settings, 'showDoktypes'),
            ignoredUids: $ignoredUids,
            categoryUids: $categoryUids,
            categoryMode: $categoryUids === [] ? null : $categoryMode,
        );

        return new self(
            source: $source,
            customPageUids: self::intList($settings, 'customPages'),
            recursionDepthFrom: $nested ? 0 : self::int($settings, 'recursionDepthFrom'),
            recursionDepth: self::int($settings, 'recursionDepth', 255),
            orderByPlugin: self::bool($settings, 'orderByPlugin'),
            loadContents: self::bool($settings, 'loadContents'),
            nested: $nested,
            enablePagination: self::bool($settings, 'enablePagination'),
            itemsPerPage: self::int($settings, 'itemsPerPage', 10),
            paginationClass: self::string($settings, 'paginationClass'),
            orderBy: $orderBy,
            descending: $descending,
            limit: $limit,
            filter: $filter,
        );
    }

    /**
     * Root pages of the nested tree.
     *
     * @return list<int>
     */
    public function rootPageUids(int $currentPageUid): array
    {
        return $this->source->usesCustomPages() ? $this->customPageUids : [$currentPageUid];
    }

    private static function databaseOrderBy(string $orderBy, string $customField): string
    {
        return match ($orderBy) {
            '', 'random' => 'uid',
            'customField' => $customField !== '' ? $customField : 'uid',
            default => $orderBy,
        };
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function string(array $settings, string $key): string
    {
        $value = $settings[$key] ?? null;
        return is_scalar($value) ? trim((string)$value) : '';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function int(array $settings, string $key, int $default = 0): int
    {
        $value = $settings[$key] ?? null;
        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function bool(array $settings, string $key): bool
    {
        $value = $settings[$key] ?? null;
        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * Comma separated uids. Non-numeric and non-positive entries are dropped,
     * because neither a page uid nor a doktype nor a category uid can be 0.
     *
     * @param array<string, mixed> $settings
     * @return list<int>
     */
    private static function intList(array $settings, string $key): array
    {
        $values = GeneralUtility::intExplode(',', self::string($settings, $key), true);
        return array_values(array_filter($values, static fn(int $value): bool => $value > 0));
    }
}
