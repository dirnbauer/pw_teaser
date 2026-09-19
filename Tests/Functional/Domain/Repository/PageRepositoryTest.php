<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use PwTeaserTeam\PwTeaser\Domain\Repository\CategoryMode;
use PwTeaserTeam\PwTeaser\Domain\Repository\PageFilter;
use PwTeaserTeam\PwTeaser\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['extbase', 'fluid', 'frontend'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/pw_teaser'];

    private PageRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->subject = $this->get(PageRepository::class);
    }

    /**
     * @param list<Page> $pages
     * @return list<int>
     */
    private static function uids(array $pages): array
    {
        return array_map(static fn(Page $page): int => (int)$page->getUid(), $pages);
    }

    private function switchToGerman(): void
    {
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
    }

    #[Test]
    public function findChildrenReturnsVisibleDefaultLanguageChildrenOrderedByUid(): void
    {
        $result = $this->subject->findChildren([1], new PageFilter());

        // no deleted (5), nav_hide (8) or "hidden in default language" (10) pages, no translations (6)
        self::assertSame([2, 3, 9, 11, 12], self::uids($result));
    }

    #[Test]
    public function findChildrenReturnsEmptyForUnknownOrNoParents(): void
    {
        self::assertSame([], $this->subject->findChildren([9999], new PageFilter()));
        self::assertSame([], $this->subject->findChildren([], new PageFilter()));
    }

    #[Test]
    public function findChildrenAppliesOrderingAndLimit(): void
    {
        $result = $this->subject->findChildren([1], new PageFilter(orderBy: 'sorting', descending: true, limit: 2));

        self::assertSame([12, 11], self::uids($result));
    }

    #[Test]
    public function filterCanIncludeNavigationHiddenPages(): void
    {
        $result = $this->subject->findChildren([1], new PageFilter(showNavHiddenItems: true));

        self::assertContains(8, self::uids($result));
    }

    #[Test]
    public function filterRestrictsDoktypes(): void
    {
        $result = $this->subject->findChildren([1], new PageFilter(doktypes: [4]));

        self::assertSame([9], self::uids($result));
    }

    #[Test]
    public function filterIgnoresPages(): void
    {
        $result = $this->subject->findChildren([1], new PageFilter(ignoredUids: [2, 9]));

        self::assertSame([3, 11, 12], self::uids($result));
    }

    #[Test]
    public function filterByCategoriesSupportsAllFourModes(): void
    {
        $or = $this->subject->findChildren([1], new PageFilter(categoryUids: [1, 2], categoryMode: CategoryMode::Or));
        $and = $this->subject->findChildren([1], new PageFilter(categoryUids: [1, 2], categoryMode: CategoryMode::And));
        $orNot = $this->subject->findChildren([1], new PageFilter(categoryUids: [1, 2], categoryMode: CategoryMode::OrNot));
        $andNot = $this->subject->findChildren([1], new PageFilter(categoryUids: [1, 2], categoryMode: CategoryMode::AndNot));

        self::assertSame([2, 3, 12], self::uids($or));
        self::assertSame([12], self::uids($and));
        self::assertSame([9, 11], self::uids($orNot));
        self::assertSame([2, 3, 9, 11], self::uids($andNot));
    }

    #[Test]
    public function categoriesWithoutModeAreIgnored(): void
    {
        $result = $this->subject->findChildren([1], new PageFilter(categoryUids: [1]));

        self::assertSame([2, 3, 9, 11, 12], self::uids($result));
    }

    #[Test]
    public function findDescendantsIncludesTheRootWhenStartingAtDepthZero(): void
    {
        $result = $this->subject->findDescendants([1], 0, 2, new PageFilter());

        self::assertSame([1, 2, 3, 4, 9, 11, 12], self::uids($result));
    }

    #[Test]
    public function findDescendantsRespectsDepthRange(): void
    {
        $onlyGrandchildren = $this->subject->findDescendants([1], 2, 2, new PageFilter());
        $onlyChildren = $this->subject->findDescendants([1], 1, 1, new PageFilter());

        self::assertSame([4], self::uids($onlyGrandchildren));
        self::assertSame([2, 3, 9, 11, 12], self::uids($onlyChildren));
    }

    #[Test]
    public function findDescendantsReturnsEmptyWithoutDepthOrRoots(): void
    {
        self::assertSame([], $this->subject->findDescendants([1], 1, 0, new PageFilter()));
        self::assertSame([], $this->subject->findDescendants([], 0, 5, new PageFilter()));
    }

    #[Test]
    public function findByUidsOrdersByFilterOrByTheGivenList(): void
    {
        $byFilter = $this->subject->findByUids([3, 2, 9999], false, new PageFilter(orderBy: 'title'));
        $asGiven = $this->subject->findByUids([3, 2, 9999], true, new PageFilter(orderBy: 'title'));

        self::assertSame([2, 3], self::uids($byFilter));
        self::assertSame([3, 2], self::uids($asGiven));
        self::assertSame([], $this->subject->findByUids([], true, new PageFilter()));
    }

    #[Test]
    public function pagesCarryTheirRawRowForTemplateAccess(): void
    {
        $result = $this->subject->findChildren([1], new PageFilter(doktypes: [4]));

        self::assertCount(1, $result);
        self::assertSame(4, (int)$result[0]->getGet()['doktype']);
        self::assertSame('/shortcut', $result[0]->getGet()['slug']);
        self::assertSame(700, (int)$result[0]->getGet()['sorting']);
    }

    #[Test]
    public function inAForeignLanguagePagesRequiringATranslationNeedATranslatedRecord(): void
    {
        $this->switchToGerman();

        $result = $this->subject->findChildren([1], new PageFilter());

        // 10 is visible again (only hidden in default language), 11 has no translation and disappears.
        // 2 is sorted behind 3 because the database orders by the uid of its translation (6).
        self::assertSame([3, 2, 9, 10, 12], self::uids($result));
    }

    #[Test]
    public function inAForeignLanguagePagesAreOverlaidWithTheirTranslation(): void
    {
        $this->switchToGerman();

        $result = $this->subject->findByUids([2, 3], true, new PageFilter());

        // page 2 is selected through its translation 6, the translation of page 3 is deleted
        self::assertSame([2, 3], self::uids($result));
        self::assertSame('Child A DE', $result[0]->getTitle());
        self::assertSame('Child B', $result[1]->getTitle());
    }

    #[Test]
    public function inAForeignLanguageTheChildrenOfATranslatedPageAreStillFound(): void
    {
        $this->switchToGerman();

        // page 2 is translated by page 6; its child 4 still has pid 2, not pid 6
        $result = $this->subject->findChildren([2], new PageFilter());

        self::assertSame([4], self::uids($result));
    }

    #[Test]
    public function repositoryHoldsNoStateBetweenQueries(): void
    {
        $this->subject->findChildren([1], new PageFilter(doktypes: [4], limit: 1));

        $result = $this->subject->findChildren([1], new PageFilter());

        self::assertSame([2, 3, 9, 11, 12], self::uids($result));
    }
}
