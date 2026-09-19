<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Controller\TeaserController;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use PwTeaserTeam\PwTeaser\Domain\Repository\ContentRepository;
use PwTeaserTeam\PwTeaser\Domain\Repository\PageRepository;
use PwTeaserTeam\PwTeaser\Settings\SettingsRenderer;
use PwTeaserTeam\PwTeaser\Settings\TeaserSettings;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Frontend\Page\PageInformation;

final class TeaserControllerTest extends TestCase
{
    private function createController(): TeaserController
    {
        $configurationManager = self::createStub(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')->willReturn([]);

        return new TeaserController(
            (new \ReflectionClass(PageRepository::class))->newInstanceWithoutConstructor(),
            (new \ReflectionClass(ContentRepository::class))->newInstanceWithoutConstructor(),
            new SettingsRenderer($configurationManager)
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createSettings(array $settings, int $currentPageUid = 1): TeaserSettings
    {
        return TeaserSettings::fromArray(array_replace(TeaserSettings::DEFAULTS, $settings), $currentPageUid);
    }

    private function createPage(int $uid, ?int $pid = null, string $title = '', int $sorting = 0): Page
    {
        $page = new Page();
        $page->_setProperty('uid', $uid);
        if ($pid !== null) {
            $page->_setProperty('pid', $pid);
        }
        $page->setTitle($title !== '' ? $title : 'Page ' . $uid);
        $page->setSorting($sorting);
        return $page;
    }

    #[Test]
    public function initializeActionAppliesDefaultsAndRendersViewSettings(): void
    {
        $subject = $this->createController();

        $frameworkConfigurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $frameworkConfigurationManager->expects($this->once())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)
            ->willReturn(['view' => ['templateType' => 'preset', 'presets' => ['default' => ['label' => 'Default']]]]);
        $this->writeProperty($subject, 'configurationManager', $frameworkConfigurationManager);

        $request = self::createStub(RequestInterface::class);
        $request->method('getAttribute')->willReturn(null);
        $this->writeProperty($subject, 'request', $request);
        $this->writeProperty($subject, 'settings', ['loadContents' => '1']);

        $subject->initializeAction();

        $settings = $this->readProperty($subject, 'settings');
        self::assertIsArray($settings);
        self::assertSame('thisChildren', $settings['source']);
        self::assertSame('1', $settings['loadContents']);
        self::assertSame(255, $settings['recursionDepth']);
        self::assertSame(1, $settings['enablePagination']);
        self::assertSame(
            ['templateType' => 'preset', 'presets' => ['default' => ['label' => 'Default']]],
            $this->readProperty($subject, 'viewSettings')
        );
    }

    #[Test]
    public function resolveCurrentPageUidReadsThePageInformationAttribute(): void
    {
        $subject = $this->createController();
        $pageInformation = new PageInformation();
        $pageInformation->setId(123);
        $request = self::createStub(RequestInterface::class);
        $request->method('getAttribute')->willReturnCallback(
            static fn(string $name): ?PageInformation => $name === 'frontend.page.information' ? $pageInformation : null
        );
        $this->writeProperty($subject, 'request', $request);

        self::assertSame(123, (new \ReflectionMethod($subject, 'resolveCurrentPageUid'))->invoke($subject));
    }

    #[Test]
    public function resolveCurrentPageUidReturnsZeroOutsideOfAPageRequest(): void
    {
        $subject = $this->createController();
        $request = self::createStub(RequestInterface::class);
        $request->method('getAttribute')->willReturn(null);
        $this->writeProperty($subject, 'request', $request);

        self::assertSame(0, (new \ReflectionMethod($subject, 'resolveCurrentPageUid'))->invoke($subject));
    }

    #[Test]
    public function randomOrderingShufflesAndLimitsThePages(): void
    {
        $subject = $this->createController();
        $pages = array_map(fn(int $uid): Page => $this->createPage($uid), range(1, 10));

        $method = new \ReflectionMethod($subject, 'applySpecialOrdering');
        $all = $method->invoke($subject, $pages, $this->createSettings(['orderBy' => 'random']));
        $limited = $method->invoke($subject, $pages, $this->createSettings(['orderBy' => 'random', 'limit' => '3']));

        self::assertIsArray($all);
        self::assertCount(10, $all);
        self::assertIsArray($limited);
        self::assertCount(3, $limited);
    }

    #[Test]
    public function databaseOrderingsPassThroughUnchanged(): void
    {
        $subject = $this->createController();
        $pages = [$this->createPage(1, title: 'B'), $this->createPage(2, title: 'A')];

        $result = (new \ReflectionMethod($subject, 'applySpecialOrdering'))
            ->invoke($subject, $pages, $this->createSettings(['orderBy' => 'title', 'limit' => '1']));

        self::assertSame($pages, $result);
    }

    #[Test]
    public function sortingOfNonRecursiveSourcesIsLeftToTheDatabase(): void
    {
        $subject = $this->createController();
        $pages = [$this->createPage(1, sorting: 512), $this->createPage(2, sorting: 256)];

        $result = (new \ReflectionMethod($subject, 'applySpecialOrdering'))
            ->invoke($subject, $pages, $this->createSettings(['orderBy' => 'sorting', 'source' => 'thisChildren']));

        self::assertSame($pages, $result);
    }

    #[Test]
    public function pageTreeAttachesChildrenSortedBySortingRecursively(): void
    {
        $subject = $this->createController();
        $parent = $this->createPage(1);
        $childB = $this->createPage(3, 1, 'Child B', 512);
        $childA = $this->createPage(2, 1, 'Child A', 256);
        $grandchild = $this->createPage(4, 2, 'Grandchild', 256);

        (new \ReflectionMethod($subject, 'attachChildPages'))->invoke($subject, $parent, [$grandchild, $childB, $childA]);

        $children = $parent->getChildPages();
        self::assertCount(2, $children);
        self::assertSame('Child A', $children[0]->getTitle());
        self::assertSame('Child B', $children[1]->getTitle());
        self::assertSame([$grandchild], $children[0]->getChildPages());
        self::assertSame([], $children[1]->getChildPages());
    }

    #[Test]
    public function paginationUsesSimplePaginationByDefault(): void
    {
        $subject = $this->createController();
        $request = self::createStub(RequestInterface::class);
        $request->method('hasArgument')->willReturn(true);
        $request->method('getArgument')->willReturn('2');
        $this->writeProperty($subject, 'request', $request);
        $pages = array_map(fn(int $uid): Page => $this->createPage($uid), range(1, 5));

        $result = (new \ReflectionMethod($subject, 'buildPagination'))
            ->invoke($subject, $pages, $this->createSettings(['itemsPerPage' => '2']));

        self::assertIsArray($result);
        self::assertSame(2, $result['currentPage']);
        self::assertInstanceOf(ArrayPaginator::class, $result['paginator']);
        self::assertSame(2, $result['paginator']->getCurrentPageNumber());
        self::assertSame(3, $result['paginator']->getNumberOfPages());
        self::assertInstanceOf(SimplePagination::class, $result['pagination']);
    }

    #[Test]
    public function paginationUsesTheConfiguredPaginationClass(): void
    {
        $subject = $this->createController();
        $request = self::createStub(RequestInterface::class);
        $request->method('hasArgument')->willReturn(false);
        $this->writeProperty($subject, 'request', $request);

        $result = (new \ReflectionMethod($subject, 'buildPagination'))
            ->invoke($subject, [$this->createPage(1)], $this->createSettings(['paginationClass' => SlidingWindowPagination::class]));

        self::assertIsArray($result);
        self::assertSame(1, $result['currentPage']);
        self::assertInstanceOf(SlidingWindowPagination::class, $result['pagination']);
    }

    #[Test]
    public function paginationIgnoresUnknownPaginationClassesAndInvalidPageArguments(): void
    {
        $subject = $this->createController();
        $request = self::createStub(RequestInterface::class);
        $request->method('hasArgument')->willReturn(true);
        $request->method('getArgument')->willReturn('-7');
        $this->writeProperty($subject, 'request', $request);

        $result = (new \ReflectionMethod($subject, 'buildPagination'))
            ->invoke($subject, [$this->createPage(1)], $this->createSettings(['paginationClass' => 'Vendor\\Missing']));

        self::assertIsArray($result);
        self::assertSame(1, $result['currentPage']);
        self::assertInstanceOf(SimplePagination::class, $result['pagination']);
    }

    private function writeProperty(object $subject, string $propertyName, mixed $value): void
    {
        (new \ReflectionProperty($subject, $propertyName))->setValue($subject, $value);
    }

    private function readProperty(object $subject, string $propertyName): mixed
    {
        return (new \ReflectionProperty($subject, $propertyName))->getValue($subject);
    }
}
