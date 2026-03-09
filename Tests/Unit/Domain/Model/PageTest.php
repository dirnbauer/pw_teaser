<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Domain\Model\Content;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use ReflectionProperty;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final class PageTest extends TestCase
{
    #[Test]
    public function constructorInitializesObjectStorages(): void
    {
        $subject = new Page();

        self::assertInstanceOf(ObjectStorage::class, $subject->getMedia());
        self::assertInstanceOf(ObjectStorage::class, $subject->getCategories());
        self::assertCount(0, $subject->getMedia());
        self::assertCount(0, $subject->getCategories());
    }

    #[Test]
    public function defaultPropertyValues(): void
    {
        $subject = new Page();

        self::assertSame(0, $subject->getDoktype());
        self::assertFalse($subject->getIsCurrentPage());
        self::assertSame('', $subject->getTitle());
        self::assertSame('', $subject->getSubtitle());
        self::assertSame('', $subject->getNavTitle());
        self::assertSame('', $subject->getDescription());
        self::assertSame('', $subject->getAbstract());
        self::assertSame('', $subject->getAlias());
        self::assertSame('', $subject->getAuthor());
        self::assertSame('', $subject->getAuthorEmail());
        self::assertSame(0, $subject->getSorting());
        self::assertSame(0, $subject->getCreationDate());
        self::assertSame(0, $subject->getTstamp());
        self::assertSame(0, $subject->getLastUpdated());
        self::assertSame(0, $subject->getStarttime());
        self::assertSame(0, $subject->getEndtime());
        self::assertSame(0, $subject->getNewUntil());
        self::assertSame(0, $subject->getL18nConfiguration());
        self::assertNull($subject->getContents());
        self::assertNull($subject->getPageRow());
        self::assertSame([], $subject->getChildPages());
    }

    #[Test]
    public function getKeywordsReturnsEmptyArrayForMissingKeywords(): void
    {
        $subject = new Page();
        $property = new ReflectionProperty($subject, 'keywords');
        $property->setAccessible(true);
        $property->setValue($subject, null);

        self::assertSame([], $subject->getKeywords());
    }

    #[Test]
    public function getKeywordsSplitsCommaSeparatedKeywords(): void
    {
        $subject = new Page();
        $subject->setKeywords('foo, bar ,baz');

        self::assertSame(['foo', 'bar', 'baz'], $subject->getKeywords());
    }

    #[Test]
    public function getKeywordsAsStringReturnsRawValue(): void
    {
        $subject = new Page();
        $subject->setKeywords('foo, bar');

        self::assertSame('foo, bar', $subject->getKeywordsAsString());
    }

    #[Test]
    public function getKeywordsAsStringReturnsNullWhenNotSet(): void
    {
        $subject = new Page();

        self::assertNull($subject->getKeywordsAsString());
    }

    #[Test]
    public function customAttributesCanBeSetAndRetrieved(): void
    {
        $subject = new Page();

        self::assertFalse($subject->hasCustomAttribute('myKey'));
        self::assertNull($subject->getCustomAttribute('myKey'));

        $subject->setCustomAttribute('myKey', 'myValue');

        self::assertTrue($subject->hasCustomAttribute('myKey'));
        self::assertSame('myValue', $subject->getCustomAttribute('myKey'));
    }

    #[Test]
    public function getCustomAttributeReturnsNullForEmptyKey(): void
    {
        $subject = new Page();
        $subject->setCustomAttribute('', 'value');

        self::assertNull($subject->getCustomAttribute(''));
    }

    #[Test]
    public function customAttributesAcceptMixedValues(): void
    {
        $subject = new Page();

        $subject->setCustomAttribute('int', 42);
        $subject->setCustomAttribute('array', ['a', 'b']);
        $subject->setCustomAttribute('bool', true);
        $subject->setCustomAttribute('null', null);

        self::assertSame(42, $subject->getCustomAttribute('int'));
        self::assertSame(['a', 'b'], $subject->getCustomAttribute('array'));
        self::assertTrue($subject->getCustomAttribute('bool'));
    }

    #[Test]
    public function isCurrentPageCanBeToggled(): void
    {
        $subject = new Page();
        self::assertFalse($subject->getIsCurrentPage());

        $subject->setIsCurrentPage(true);
        self::assertTrue($subject->getIsCurrentPage());

        $subject->setIsCurrentPage(false);
        self::assertFalse($subject->getIsCurrentPage());
    }

    #[Test]
    public function getIsNewReturnsFalseWhenNewUntilIsZero(): void
    {
        $subject = new Page();
        $subject->setNewUntil(0);

        self::assertFalse($subject->getIsNew());
    }

    #[Test]
    public function getIsNewReturnsTrueWhenNewUntilIsInThePast(): void
    {
        $subject = new Page();
        $subject->setNewUntil(time() - 3600);

        self::assertTrue($subject->getIsNew());
    }

    #[Test]
    public function getIsNewReturnsFalseWhenNewUntilIsInTheFuture(): void
    {
        $subject = new Page();
        $subject->setNewUntil(time() + 3600);

        self::assertFalse($subject->getIsNew());
    }

    #[Test]
    public function contentsCanBeSetAndRetrieved(): void
    {
        $subject = new Page();

        $content1 = new Content();
        $content1->setHeader('First');
        $content2 = new Content();
        $content2->setHeader('Second');

        $subject->setContents([$content1, $content2]);

        $contents = $subject->getContents();
        self::assertNotNull($contents);
        self::assertCount(2, $contents);
        self::assertSame('First', $contents[0]->getHeader());
    }

    #[Test]
    public function contentsCanBeSetToNull(): void
    {
        $subject = new Page();
        $subject->setContents([new Content()]);
        $subject->setContents(null);

        self::assertNull($subject->getContents());
    }

    #[Test]
    public function childPagesCanBeSetAndRetrieved(): void
    {
        $subject = new Page();
        $child1 = new Page();
        $child1->setTitle('Child 1');
        $child2 = new Page();
        $child2->setTitle('Child 2');

        $subject->setChildPages([$child1, $child2]);

        self::assertCount(2, $subject->getChildPages());
        self::assertSame('Child 1', $subject->getChildPages()[0]->getTitle());
    }

    #[Test]
    public function categoryCollectionOperations(): void
    {
        $subject = new Page();
        $category = new Category();
        $category->setTitle('Test Category');

        $subject->addCategory($category);
        self::assertCount(1, $subject->getCategories());

        $subject->removeCategory($category);
        self::assertCount(0, $subject->getCategories());
    }

    #[Test]
    public function categoriesCanBeReplacedWithNewObjectStorage(): void
    {
        $subject = new Page();
        $cat1 = new Category();
        $cat2 = new Category();

        $storage = new ObjectStorage();
        $storage->attach($cat1);
        $storage->attach($cat2);

        $subject->setCategories($storage);
        self::assertCount(2, $subject->getCategories());
    }

    #[Test]
    public function settersAndGettersForAllStringProperties(): void
    {
        $subject = new Page();

        $subject->setTitle('My Title');
        $subject->setSubtitle('My Subtitle');
        $subject->setNavTitle('My Nav');
        $subject->setDescription('My Description');
        $subject->setAbstract('My Abstract');
        $subject->setAlias('my-alias');
        $subject->setAuthor('John Doe');
        $subject->setAuthorEmail('john@example.com');

        self::assertSame('My Title', $subject->getTitle());
        self::assertSame('My Subtitle', $subject->getSubtitle());
        self::assertSame('My Nav', $subject->getNavTitle());
        self::assertSame('My Description', $subject->getDescription());
        self::assertSame('My Abstract', $subject->getAbstract());
        self::assertSame('my-alias', $subject->getAlias());
        self::assertSame('John Doe', $subject->getAuthor());
        self::assertSame('john@example.com', $subject->getAuthorEmail());
    }

    #[Test]
    public function settersAndGettersForAllIntProperties(): void
    {
        $subject = new Page();

        $subject->setDoktype(4);
        $subject->setSorting(256);
        $subject->setCreationDate(1700000000);
        $subject->setTstamp(1700000001);
        $subject->setLastUpdated(1700000002);
        $subject->setStarttime(1700000003);
        $subject->setEndtime(1700000004);
        $subject->setNewUntil(1700000005);
        $subject->setL18nConfiguration(2);

        self::assertSame(4, $subject->getDoktype());
        self::assertSame(256, $subject->getSorting());
        self::assertSame(1700000000, $subject->getCreationDate());
        self::assertSame(1700000001, $subject->getTstamp());
        self::assertSame(1700000002, $subject->getLastUpdated());
        self::assertSame(1700000003, $subject->getStarttime());
        self::assertSame(1700000004, $subject->getEndtime());
        self::assertSame(1700000005, $subject->getNewUntil());
        self::assertSame(2, $subject->getL18nConfiguration());
    }

    #[Test]
    #[DataProvider('l18nConstantsProvider')]
    public function l18nConstantsHaveExpectedValues(int $expected, int $actual): void
    {
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function l18nConstantsProvider(): array
    {
        return [
            'SHOW_ALWAYS' => [0, Page::L18N_SHOW_ALWAYS],
            'HIDE_DEFAULT_LANGUAGE' => [1, Page::L18N_HIDE_DEFAULT_LANGUAGE],
            'HIDE_IF_NO_TRANSLATION_EXISTS' => [2, Page::L18N_HIDE_IF_NO_TRANSLATION_EXISTS],
            'HIDE_ALWAYS_BUT_TRANSLATION_EXISTS' => [3, Page::L18N_HIDE_ALWAYS_BUT_TRANSLATION_EXISTS],
        ];
    }
}
