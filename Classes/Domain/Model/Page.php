<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Domain\Model;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 *  |     2016 Tim Klein-Hitpass <tim.klein-hitpass@diemedialen.de>
 *  |     2016 Kai Ratzeburg <kai.ratzeburg@diemedialen.de>
 */
use PwTeaserTeam\PwTeaser\Database\RecordRowLoader;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A teased page. Modelled columns have typed getters; every other column of
 * the pages table is available through {page.get.columnName}.
 */
class Page extends AbstractEntity
{
    protected int $doktype = 0;

    protected bool $isCurrentPage = false;

    protected string $title = '';

    protected string $subtitle = '';

    protected string $navTitle = '';

    protected ?string $keywords = null;

    protected string $description = '';

    protected string $abstract = '';

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $media;

    protected int $sorting = 0;

    protected int $creationDate = 0;

    protected int $tstamp = 0;

    protected int $lastUpdated = 0;

    protected int $starttime = 0;

    protected int $endtime = 0;

    protected int $newUntil = 0;

    protected string $author = '';

    protected string $authorEmail = '';

    /** @var array<Content>|null */
    protected ?array $contents = null;

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $categories;

    protected int $l18nConfiguration = 0;

    /** @var array<Page> */
    protected array $childPages = [];

    /** @var array<string, mixed> */
    protected array $customAttributes = [];

    /** @var array<string, mixed>|null Raw pages row with lowerCamelCase keys */
    protected ?array $pageRow = null;

    public function __construct()
    {
        $this->categories = new ObjectStorage();
        $this->media = new ObjectStorage();
    }

    public function setCustomAttribute(string $key, mixed $value): void
    {
        $this->customAttributes[$key] = $value;
    }

    public function getCustomAttribute(string $key): mixed
    {
        return $this->customAttributes[$key] ?? null;
    }

    public function hasCustomAttribute(string $key): bool
    {
        return isset($this->customAttributes[$key]);
    }

    /**
     * All columns of the pages row (lowerCamelCase keys) plus the custom attributes.
     * Used in Fluid as {page.get.columnName}.
     *
     * @return array<string, mixed>
     */
    public function getGet(): array
    {
        $this->pageRow ??= GeneralUtility::makeInstance(RecordRowLoader::class)->loadByUid('pages', (int)$this->getUid());
        return array_merge($this->customAttributes, $this->pageRow);
    }

    /**
     * @deprecated Use getGet()['columnName'] instead; Fluid never calls this method.
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (str_starts_with($name, 'get') && strlen($name) > 3) {
            return $this->getGet()[lcfirst(substr($name, 3))] ?? null;
        }
        return null;
    }

    /**
     * @param array<Content>|null $contents
     */
    public function setContents(?array $contents): void
    {
        $this->contents = $contents;
    }

    /**
     * @return array<Content>|null
     */
    public function getContents(): ?array
    {
        return $this->contents;
    }

    public function getIsCurrentPage(): bool
    {
        return $this->isCurrentPage;
    }

    public function setIsCurrentPage(bool $isCurrentPage): void
    {
        $this->isCurrentPage = $isCurrentPage;
    }

    public function setAuthorEmail(string $authorEmail): void
    {
        $this->authorEmail = $authorEmail;
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    public function setKeywords(?string $keywords): void
    {
        $this->keywords = $keywords;
    }

    /**
     * @return array<int, string>
     */
    public function getKeywords(): array
    {
        return GeneralUtility::trimExplode(',', (string)$this->keywords, true);
    }

    public function getKeywordsAsString(): ?string
    {
        return $this->keywords;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setNavTitle(string $navTitle): void
    {
        $this->navTitle = $navTitle;
    }

    public function getNavTitle(): string
    {
        return $this->navTitle;
    }

    public function setAbstract(string $abstract): void
    {
        $this->abstract = $abstract;
    }

    public function getAbstract(): string
    {
        return $this->abstract;
    }

    public function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param ObjectStorage<FileReference> $media
     */
    public function setMedia(ObjectStorage $media): void
    {
        $this->media = $media;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getMedia(): ObjectStorage
    {
        return $this->media;
    }

    public function addMedium(FileReference $medium): void
    {
        $this->media->attach($medium);
    }

    public function removeMedium(FileReference $medium): void
    {
        $this->media->detach($medium);
    }

    public function setNewUntil(int $newUntil): void
    {
        $this->newUntil = $newUntil;
    }

    public function getNewUntil(): int
    {
        return $this->newUntil;
    }

    public function getIsNew(): bool
    {
        return $this->newUntil !== 0 && $this->newUntil > time();
    }

    public function setCreationDate(int $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    public function setTstamp(int $tstamp): void
    {
        $this->tstamp = $tstamp;
    }

    public function getTstamp(): int
    {
        return $this->tstamp;
    }

    public function setLastUpdated(int $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }

    public function getLastUpdated(): int
    {
        return $this->lastUpdated;
    }

    public function setStarttime(int $starttime): void
    {
        $this->starttime = $starttime;
    }

    public function getStarttime(): int
    {
        return $this->starttime;
    }

    public function getEndtime(): int
    {
        return $this->endtime;
    }

    public function setEndtime(int $endtime): void
    {
        $this->endtime = $endtime;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getDoktype(): int
    {
        return $this->doktype;
    }

    public function setDoktype(int $doktype): void
    {
        $this->doktype = $doktype;
    }

    /**
     * Raw l18n_cfg bitmask of the page.
     */
    public function getL18nConfiguration(): int
    {
        return $this->l18nConfiguration;
    }

    public function setL18nConfiguration(int $l18nConfiguration): void
    {
        $this->l18nConfiguration = $l18nConfiguration;
    }

    public function getTranslationVisibility(): PageTranslationVisibility
    {
        return new PageTranslationVisibility($this->l18nConfiguration);
    }

    /**
     * @return ObjectStorage<Category>
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    /**
     * @param ObjectStorage<Category> $categories
     */
    public function setCategories(ObjectStorage $categories): void
    {
        $this->categories = $categories;
    }

    public function addCategory(Category $category): void
    {
        $this->categories->attach($category);
    }

    public function removeCategory(Category $category): void
    {
        $this->categories->detach($category);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRootLine(): array
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $rootline = GeneralUtility::makeInstance(RootlineUtility::class, (int)$this->getUid(), '', $context);
        return $rootline->get();
    }

    public function getRootLineDepth(): int
    {
        return count($this->getRootLine());
    }

    /**
     * Sorting key that orders pages like the page tree: the zero-padded sorting
     * values from the root page down to this page, joined with "-".
     */
    public function getRecursiveRootLineOrdering(): string
    {
        $ordering = [];
        foreach ($this->getRootLine() as $rootLinePage) {
            $sorting = $rootLinePage['sorting'] ?? 0;
            array_unshift($ordering, str_pad(is_scalar($sorting) ? (string)$sorting : '0', 11, '0', STR_PAD_LEFT));
        }
        return implode('-', $ordering);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPageRow(): ?array
    {
        return $this->pageRow;
    }

    /**
     * Pre-populates the raw row (lowerCamelCase keys, see RecordRowLoader) so
     * getGet() needs no query of its own.
     *
     * @param array<string, mixed> $pageRow
     */
    public function setPageRow(array $pageRow): void
    {
        $this->pageRow = $pageRow;
    }

    public function getSorting(): int
    {
        return $this->sorting;
    }

    public function setSorting(int $sorting): void
    {
        $this->sorting = $sorting;
    }

    /**
     * @return array<Page>
     */
    public function getChildPages(): array
    {
        return $this->childPages;
    }

    /**
     * @param array<Page> $childPages
     */
    public function setChildPages(array $childPages): void
    {
        $this->childPages = $childPages;
    }
}
