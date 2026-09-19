<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Controller;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 *  |     2016 Tim Klein-Hitpass <tim.klein-hitpass@diemedialen.de>
 *  |     2016 Kai Ratzeburg <kai.ratzeburg@diemedialen.de>
 */
use Psr\Http\Message\ResponseInterface;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use PwTeaserTeam\PwTeaser\Domain\Repository\ContentRepository;
use PwTeaserTeam\PwTeaser\Domain\Repository\PageRepository;
use PwTeaserTeam\PwTeaser\Event\ModifyPagesEvent;
use PwTeaserTeam\PwTeaser\Settings\SettingsRenderer;
use PwTeaserTeam\PwTeaser\Settings\TeaserSettings;
use PwTeaserTeam\PwTeaser\Settings\TeaserSource;
use PwTeaserTeam\PwTeaser\View\TemplateConfiguration;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3Fluid\Fluid\View\TemplatePaths;

/**
 * Renders the page teaser plugin.
 */
class TeaserController extends ActionController
{
    /** @var array<string, mixed> */
    protected array $settings = [];

    /** @var array<string, mixed> */
    protected array $viewSettings = [];

    public function __construct(
        protected readonly PageRepository $pageRepository,
        protected readonly ContentRepository $contentRepository,
        protected readonly SettingsRenderer $settingsRenderer,
    ) {}

    public function initializeAction(): void
    {
        $contentObject = $this->request->getAttribute('currentContentObject');
        $contentObject = $contentObject instanceof ContentObjectRenderer ? $contentObject : null;

        $this->settings = array_replace(
            TeaserSettings::DEFAULTS,
            $this->settingsRenderer->render($this->settings, 'settings.', $contentObject)
        );

        $frameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
        $viewSettings = is_array($frameworkConfiguration['view'] ?? null) ? $frameworkConfiguration['view'] : [];
        $this->viewSettings = $this->settingsRenderer->render($viewSettings, 'view.', $contentObject);
    }

    public function indexAction(): ResponseInterface
    {
        $currentPageUid = $this->resolveCurrentPageUid();
        $settings = TeaserSettings::fromArray($this->settings, $currentPageUid);

        TemplateConfiguration::fromViewSettings($this->viewSettings)->applyTo($this->getTemplatePaths());

        $pages = $this->findPages($settings, $currentPageUid);
        if (!$settings->nested) {
            $pages = $this->applySpecialOrdering($pages, $settings);
        }
        foreach ($pages as $page) {
            if ($page->getUid() === $currentPageUid) {
                $page->setIsCurrentPage(true);
            }
            if ($settings->loadContents) {
                $page->setContents($this->contentRepository->findByPid((int)$page->getUid()));
            }
        }
        if ($settings->nested) {
            $pages = $this->buildPageTree($pages, $settings->rootPageUids($currentPageUid));
        }

        $event = new ModifyPagesEvent($pages, $this);
        $this->eventDispatcher->dispatch($event);
        $pages = $event->getPages();

        $this->view->assign('pages', $pages);
        if ($settings->enablePagination) {
            $this->view->assign('pagination', $this->buildPagination($pages, $settings));
        }

        return $this->htmlResponse();
    }

    protected function resolveCurrentPageUid(): int
    {
        $pageInformation = $this->request->getAttribute('frontend.page.information');
        return $pageInformation instanceof PageInformation ? $pageInformation->getId() : 0;
    }

    /**
     * @return list<Page>
     */
    protected function findPages(TeaserSettings $settings, int $currentPageUid): array
    {
        return match ($settings->source) {
            TeaserSource::ThisChildren => $this->pageRepository->findChildren([$currentPageUid], $settings->filter),
            TeaserSource::ThisChildrenRecursively => $this->pageRepository->findDescendants(
                [$currentPageUid],
                $settings->recursionDepthFrom,
                $settings->recursionDepth,
                $settings->filter
            ),
            TeaserSource::Custom => $this->pageRepository->findByUids(
                $settings->customPageUids,
                $settings->orderByPlugin,
                $settings->filter
            ),
            TeaserSource::CustomChildren => $this->pageRepository->findChildren($settings->customPageUids, $settings->filter),
            TeaserSource::CustomChildrenRecursively => $this->pageRepository->findDescendants(
                $settings->customPageUids,
                $settings->recursionDepthFrom,
                $settings->recursionDepth,
                $settings->filter
            ),
        };
    }

    /**
     * Orderings the database cannot provide: "random", and "sorting" across
     * several levels of a recursive source (ordered like the page tree).
     *
     * @param list<Page> $pages
     * @return list<Page>
     */
    protected function applySpecialOrdering(array $pages, TeaserSettings $settings): array
    {
        if ($settings->orderBy === 'random') {
            shuffle($pages);
        } elseif ($settings->orderBy === 'sorting' && $settings->source->isRecursive()) {
            usort($pages, static fn(Page $a, Page $b): int => $a->getRecursiveRootLineOrdering() <=> $b->getRecursiveRootLineOrdering());
            if ($settings->descending) {
                $pages = array_reverse($pages);
            }
        } else {
            return $pages;
        }
        return $settings->limit > 0 ? array_slice($pages, 0, $settings->limit) : $pages;
    }

    /**
     * Nested page mode: returns the root pages with their descendants attached as child pages.
     *
     * @param list<Page> $pages
     * @param list<int> $rootPageUids
     * @return list<Page>
     */
    protected function buildPageTree(array $pages, array $rootPageUids): array
    {
        $rootPages = [];
        foreach ($rootPageUids as $rootPageUid) {
            $rootPage = $this->pageRepository->findByUid($rootPageUid);
            if ($rootPage instanceof Page) {
                $this->attachChildPages($rootPage, $pages);
                $rootPages[] = $rootPage;
            }
        }
        return $rootPages;
    }

    /**
     * @param list<Page> $pages
     */
    protected function attachChildPages(Page $parentPage, array $pages): void
    {
        $childPages = array_values(array_filter(
            $pages,
            static fn(Page $page): bool => $page->getPid() === $parentPage->getUid()
        ));
        usort($childPages, static fn(Page $a, Page $b): int => $a->getSorting() <=> $b->getSorting());
        foreach ($childPages as $childPage) {
            $this->attachChildPages($childPage, $pages);
        }
        $parentPage->setChildPages($childPages);
    }

    /**
     * @param list<Page> $pages
     * @return array{currentPage: int, paginator: ArrayPaginator, pagination: PaginationInterface}
     */
    protected function buildPagination(array $pages, TeaserSettings $settings): array
    {
        $currentPageArgument = $this->request->hasArgument('currentPage') ? $this->request->getArgument('currentPage') : 1;
        $currentPage = max(1, is_numeric($currentPageArgument) ? (int)$currentPageArgument : 1);
        $paginator = new ArrayPaginator($pages, $currentPage, $settings->itemsPerPage);

        $paginationClass = $settings->paginationClass;
        $pagination = $paginationClass !== '' && is_a($paginationClass, PaginationInterface::class, true)
            ? new $paginationClass($paginator)
            : new SimplePagination($paginator);

        return [
            'currentPage' => $currentPage,
            'paginator' => $paginator,
            'pagination' => $pagination,
        ];
    }

    private function getTemplatePaths(): TemplatePaths
    {
        if (!$this->view instanceof FluidViewAdapter) {
            throw new \RuntimeException('pw_teaser requires a Fluid view', 1758200001);
        }
        return $this->view->getRenderingContext()->getTemplatePaths();
    }
}
