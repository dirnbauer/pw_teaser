<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Domain\Model\Content;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final class ContentTest extends TestCase
{
    #[Test]
    public function constructorInitializesObjectStorages(): void
    {
        $subject = new Content();

        self::assertCount(0, $subject->getImage());
        self::assertCount(0, $subject->getAssets());
        self::assertCount(0, $subject->getCategories());
    }

    #[Test]
    public function defaultValuesAreInitialized(): void
    {
        $subject = new Content();

        self::assertSame('', $subject->getCtype());
        self::assertSame(0, $subject->getColPos());
        self::assertSame('', $subject->getHeader());
        self::assertSame('', $subject->getBodytext());
    }

    #[Test]
    public function settersAndGettersWorkCorrectly(): void
    {
        $subject = new Content();
        $subject->setCtype('textmedia');
        $subject->setColPos(2);
        $subject->setHeader('Test Header');
        $subject->setBodytext('Test body');

        self::assertSame('textmedia', $subject->getCtype());
        self::assertSame(2, $subject->getColPos());
        self::assertSame('Test Header', $subject->getHeader());
        self::assertSame('Test body', $subject->getBodytext());
    }

    #[Test]
    public function contentRowIsNullByDefault(): void
    {
        $subject = new Content();

        self::assertNull($subject->getContentRow());
    }

    #[Test]
    public function categoryCollectionOperations(): void
    {
        $subject = new Content();
        $category = new Category();
        $category->setTitle('News');

        $subject->addCategory($category);
        self::assertCount(1, $subject->getCategories());

        $subject->removeCategory($category);
        self::assertCount(0, $subject->getCategories());
    }

    #[Test]
    public function categoriesCanBeReplacedWithObjectStorage(): void
    {
        $subject = new Content();
        $cat1 = new Category();
        $cat2 = new Category();

        $subject->setCategories(self::storage($cat1, $cat2));
        self::assertCount(2, $subject->getCategories());
    }

    #[Test]
    public function imageCollectionCanBeReplacedWithObjectStorage(): void
    {
        $subject = new Content();
        $storage = self::storage(new FileReference());

        $subject->setImage($storage);
        self::assertSame($storage, $subject->getImage());
    }

    #[Test]
    public function assetsCollectionCanBeReplacedWithObjectStorage(): void
    {
        $subject = new Content();
        $storage = self::storage(new FileReference());

        $subject->setAssets($storage);
        self::assertSame($storage, $subject->getAssets());
    }

    #[Test]
    public function colPosAcceptsVariousPositions(): void
    {
        $subject = new Content();

        $subject->setColPos(0);
        self::assertSame(0, $subject->getColPos());

        $subject->setColPos(1);
        self::assertSame(1, $subject->getColPos());

        $subject->setColPos(200);
        self::assertSame(200, $subject->getColPos());
    }

    #[Test]
    public function getGetReturnsThePreloadedRow(): void
    {
        $subject = new Content();
        $subject->setContentRow(['uid' => 7, 'layout' => 3]);

        self::assertSame(['uid' => 7, 'layout' => 3], $subject->getGet());
    }

    #[Test]
    public function deprecatedMagicGettersReadFromThePreloadedRow(): void
    {
        $subject = new Content();
        $subject->setContentRow(['layout' => 3]);

        // called through __call(), which is what Fluid does for {content.layout}
        self::assertSame(3, $subject->__call('getLayout', []));
        self::assertNull($subject->__call('getMissingColumn', []));
        self::assertNull($subject->__call('somethingElse', []));
    }

    /**
     * @template T of object
     * @param T ...$objects
     * @return ObjectStorage<T>
     */
    private static function storage(object ...$objects): ObjectStorage
    {
        /** @var ObjectStorage<T> $storage */
        $storage = new ObjectStorage();
        foreach ($objects as $object) {
            $storage->attach($object);
        }
        return $storage;
    }
}
