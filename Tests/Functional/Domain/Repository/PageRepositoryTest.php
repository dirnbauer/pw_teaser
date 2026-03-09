<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use PwTeaserTeam\PwTeaser\Domain\Repository\PageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['extbase', 'fluid', 'frontend'];

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/pw_teaser',
    ];

    private PageRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->subject = $this->get(PageRepository::class);
    }

    #[Test]
    public function collectRecursivePageIdsSkipsDeletedPages(): void
    {
        $method = new \ReflectionMethod(PageRepository::class, 'collectRecursivePageIds');
        $method->setAccessible(true);

        $pageIds = $method->invoke($this->subject, 1, 1, 2);
        sort($pageIds);

        self::assertSame([2, 3, 4], $pageIds);
    }

    #[Test]
    public function pageHasTranslationIgnoresDeletedTranslationRows(): void
    {
        $method = new \ReflectionMethod(PageRepository::class, 'pageHasTranslation');
        $method->setAccessible(true);

        self::assertTrue($method->invoke($this->subject, 2, 1));
        self::assertFalse($method->invoke($this->subject, 3, 1));
    }

    #[Test]
    public function findByPidReturnsDirectChildren(): void
    {
        $result = $this->subject->findByPid(1);

        $titles = array_map(static fn(Page $p) => $p->getTitle(), $result);
        sort($titles);

        self::assertContains('Child A', $titles);
        self::assertContains('Child B', $titles);
        self::assertNotContains('Deleted Child', $titles);
    }

    #[Test]
    public function findByPidReturnsEmptyForNonExistentPid(): void
    {
        $result = $this->subject->findByPid(9999);

        self::assertSame([], $result);
    }

    #[Test]
    public function findByPidListReturnsMatchingPages(): void
    {
        $result = $this->subject->findByPidList('2,3');

        $uids = array_map(static fn(Page $p) => $p->getUid(), $result);
        sort($uids);

        self::assertSame([2, 3], $uids);
    }

    #[Test]
    public function findByPidListReturnsEmptyForEmptyInput(): void
    {
        $result = $this->subject->findByPidList('');

        self::assertSame([], $result);
    }

    #[Test]
    public function findByPidListWithPluginOrderingPreservesOrder(): void
    {
        $result = $this->subject->findByPidList('3,2', true);

        self::assertCount(2, $result);
        self::assertSame(3, $result[0]->getUid());
        self::assertSame(2, $result[1]->getUid());
    }

    #[Test]
    public function findChildrenByPidListReturnsChildrenOfMultipleParents(): void
    {
        $result = $this->subject->findChildrenByPidList('1');

        $titles = array_map(static fn(Page $p) => $p->getTitle(), $result);

        self::assertContains('Child A', $titles);
        self::assertContains('Child B', $titles);
        self::assertNotContains('Deleted Child', $titles);
    }

    #[Test]
    public function findChildrenByPidListReturnsEmptyForEmptyInput(): void
    {
        $result = $this->subject->findChildrenByPidList('');

        self::assertSame([], $result);
    }

    #[Test]
    public function findByPidRecursivelyReturnsDescendants(): void
    {
        $result = $this->subject->findByPidRecursively(1, 0, 2);

        $titles = array_map(static fn(Page $p) => $p->getTitle(), $result);

        self::assertContains('Child A', $titles);
        self::assertContains('Grandchild', $titles);
    }

    #[Test]
    public function setOrderByIgnoresRandomValue(): void
    {
        $property = new \ReflectionProperty(PageRepository::class, 'orderBy');
        $property->setAccessible(true);

        $this->subject->setOrderBy('title');
        self::assertSame('title', $property->getValue($this->subject));

        $this->subject->setOrderBy('random');
        self::assertSame('title', $property->getValue($this->subject));
    }

    #[Test]
    public function setOrderDirectionSetsDescending(): void
    {
        $property = new \ReflectionProperty(PageRepository::class, 'orderDirection');
        $property->setAccessible(true);

        $this->subject->setOrderDirection('desc');
        self::assertSame('DESC', $property->getValue($this->subject));

        $this->subject->setOrderDirection('asc');
        self::assertSame('ASC', $property->getValue($this->subject));
    }

    #[Test]
    public function setOrderDirectionAcceptsIntegerValues(): void
    {
        $property = new \ReflectionProperty(PageRepository::class, 'orderDirection');
        $property->setAccessible(true);

        $this->subject->setOrderDirection(1);
        self::assertSame('DESC', $property->getValue($this->subject));

        $this->subject->setOrderDirection(0);
        self::assertSame('ASC', $property->getValue($this->subject));
    }

    #[Test]
    public function setShowNavHiddenItemsFiltersNavHidePages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages-nav-hidden.csv');

        $this->subject->setShowNavHiddenItems(false);
        $resultWithoutHidden = $this->subject->findByPid(100);

        $subject2 = $this->get(PageRepository::class);
        $subject2->setShowNavHiddenItems(true);
        $resultWithHidden = $subject2->findByPid(100);

        self::assertGreaterThanOrEqual(count($resultWithoutHidden), count($resultWithHidden));
    }
}
