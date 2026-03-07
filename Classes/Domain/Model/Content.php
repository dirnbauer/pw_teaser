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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Content model
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Content extends AbstractEntity
{
    protected string $ctype = '';

    protected int $colPos = 0;

    protected string $header = '';

    protected string $bodytext = '';

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $image;

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $assets;

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $categories;

    /** @var array<string, mixed>|null */
    protected ?array $contentRow = null;

    public function __construct()
    {
        $this->image = new ObjectStorage();
        $this->assets = new ObjectStorage();
        $this->categories = new ObjectStorage();
    }

    /**
     * @param ObjectStorage<FileReference> $image
     */
    public function setImage(ObjectStorage $image): void
    {
        $this->image = $image;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getImage(): ObjectStorage
    {
        return $this->image;
    }

    public function addImage(FileReference $image): void
    {
        $this->image->attach($image);
    }

    public function removeImage(FileReference $image): void
    {
        $this->image->detach($image);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getImageFiles(): array
    {
        $imageFiles = [];
        foreach ($this->getImage() as $image) {
            $imageFiles[] = $image->getOriginalResource()->toArray();
        }
        return $imageFiles;
    }

    /**
     * @param ObjectStorage<FileReference> $assets
     */
    public function setAssets(ObjectStorage $assets): void
    {
        $this->assets = $assets;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getAssets(): ObjectStorage
    {
        return $this->assets;
    }

    public function addAssets(FileReference $assets): void
    {
        $this->assets->attach($assets);
    }

    public function removeAssets(FileReference $assets): void
    {
        $this->assets->detach($assets);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAssetsFiles(): array
    {
        $assetsFiles = [];
        foreach ($this->getAssets() as $assets) {
            $assetsFiles[] = $assets->getOriginalResource()->toArray();
        }
        return $assetsFiles;
    }

    public function setBodytext(string $bodytext): void
    {
        $this->bodytext = $bodytext;
    }

    public function getBodytext(): string
    {
        return $this->bodytext;
    }

    public function setCtype(string $ctype): void
    {
        $this->ctype = $ctype;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    public function setColPos(int $colPos): void
    {
        $this->colPos = $colPos;
    }

    public function getColPos(): int
    {
        return $this->colPos;
    }

    public function setHeader(string $header): void
    {
        $this->header = $header;
    }

    public function getHeader(): string
    {
        return $this->header;
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
     * @deprecated Use typed getters instead. Falls back to raw database row.
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (str_starts_with(strtolower($name), 'get') && strlen($name) > 3) {
            $attributeName = lcfirst(substr($name, 3));

            if ($this->contentRow === null) {
                $pool = GeneralUtility::makeInstance(ConnectionPool::class);
                $queryBuilder = $pool->getQueryBuilderForTable('tt_content');
                $contentRow = $queryBuilder
                    ->select('*')
                    ->from('tt_content')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($this->getUid(), Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'deleted',
                            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                        )
                    )
                    ->setMaxResults(1)
                    ->executeQuery()
                    ->fetchAssociative() ?: [];
                $this->contentRow = [];
                foreach ($contentRow as $key => $value) {
                    $this->contentRow[GeneralUtility::underscoredToLowerCamelCase((string)$key)] = $value;
                }
            }
            return $this->contentRow[$attributeName] ?? null;
        }
        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContentRow(): ?array
    {
        return $this->contentRow;
    }
}
