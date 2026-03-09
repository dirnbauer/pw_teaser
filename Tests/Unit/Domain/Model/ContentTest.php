<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Domain\Model\Content;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final class ContentTest extends TestCase
{
    #[Test]
    public function constructorInitializesObjectStorages(): void
    {
        $subject = new Content();

        self::assertInstanceOf(ObjectStorage::class, $subject->getImage());
        self::assertInstanceOf(ObjectStorage::class, $subject->getAssets());
        self::assertInstanceOf(ObjectStorage::class, $subject->getCategories());
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

        $storage = new ObjectStorage();
        $storage->attach($cat1);
        $storage->attach($cat2);

        $subject->setCategories($storage);
        self::assertCount(2, $subject->getCategories());
    }

    #[Test]
    public function imageCollectionCanBeReplacedWithObjectStorage(): void
    {
        $subject = new Content();
        $storage = new ObjectStorage();

        $subject->setImage($storage);
        self::assertSame($storage, $subject->getImage());
    }

    #[Test]
    public function assetsCollectionCanBeReplacedWithObjectStorage(): void
    {
        $subject = new Content();
        $storage = new ObjectStorage();

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
}
