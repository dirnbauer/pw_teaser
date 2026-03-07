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
use Exception;
use Psr\Http\Message\ResponseInterface;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use PwTeaserTeam\PwTeaser\Domain\Repository\CategoryRepository;
use PwTeaserTeam\PwTeaser\Domain\Repository\ContentRepository;
use PwTeaserTeam\PwTeaser\Domain\Repository\PageRepository;
use PwTeaserTeam\PwTeaser\Event\ModifyPagesEvent;
use PwTeaserTeam\PwTeaser\Utility\Settings;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Controller for the teaser object
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TeaserController extends ActionController
{
    protected array $settings = [];

    protected int $currentPageUid = 0;

    /**
     * @var TemplateView
     */
    protected $view;

    /**
     * @var array
     */
    protected $viewSettings = [];

    public function __construct(protected PageRepository $pageRepository, protected ContentRepository $contentRepository, protected CategoryRepository $categoryRepository, protected Settings $settingsUtility)
    {
    }

    /**
     * Initialize Action will get performed before each action will be executed
     *
     * @return void
     */
    public function initializeAction(): void
    {
        $contentObject = $this->request->getAttribute('currentContentObject');
        $this->settingsUtility->setContentObject(
            $contentObject instanceof ContentObjectRenderer ? $contentObject : null
        );
        $this->settings = array_replace(
            [
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
            ],
            $this->settingsUtility->renderConfigurationArray($this->settings)
        );

        $frameworkSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
        $viewSettings = is_array($frameworkSettings['view'] ?? null) ? $frameworkSettings['view'] : [];
        $presets = is_array($viewSettings['presets'] ?? null) ? $viewSettings['presets'] : [];
        unset($viewSettings['presets']);
        $this->viewSettings = $viewSettings !== []
            ? $this->settingsUtility->renderConfigurationArray($viewSettings, 'view.')
            : [];
        $this->viewSettings['presets'] = $presets;
    }

    /**
     * Displays teasers
     *
     */
    public function indexAction(): ResponseInterface
    {
        $this->currentPageUid = $this->resolveCurrentPageUid();

        $this->performTemplatePathAndFilename();
        $this->setOrderingAndLimitation();
        $this->performPluginConfigurations();

        switch ($this->settings['source']) {
            default:
            case 'thisChildren':
                $rootPageUids = $this->currentPageUid;
                $pages = $this->pageRepository->findByPid($this->currentPageUid);
                break;

            case 'thisChildrenRecursively':
                $rootPageUids = $this->currentPageUid;
                $pages = $this->pageRepository->findByPidRecursively(
                    $this->currentPageUid,
                    (int)$this->settings['recursionDepthFrom'],
                    (int)$this->settings['recursionDepth']
                );
                break;

            case 'custom':
                $rootPageUids = $this->settings['customPages'];
                $pages = $this->pageRepository->findByPidList(
                    $this->settings['customPages'],
                    $this->settings['orderByPlugin']
                );
                break;

            case 'customChildren':
                $rootPageUids = $this->settings['customPages'];
                $pages = $this->pageRepository->findChildrenByPidList($this->settings['customPages']);
                break;

            case 'customChildrenRecursively':
                $rootPageUids = $this->settings['customPages'];
                $pages = $this->pageRepository->findChildrenRecursivelyByPidList(
                    $this->settings['customPages'],
                    (int)$this->settings['recursionDepthFrom'],
                    (int)$this->settings['recursionDepth']
                );
                break;
        }

        if ($this->settings['pageMode'] !== 'nested') {
            $pages = $this->performSpecialOrderings($pages);
        }

        /** @var Page $page */
        foreach ($pages as $page) {
            if ($page->getUid() === $this->currentPageUid) {
                $page->setIsCurrentPage(true);
            }

            if ($this->settings['loadContents'] === '1') {
                $page->setContents($this->contentRepository->findByPid($page->getUid())->toArray());
            }
        }

        if ($this->settings['pageMode'] === 'nested') {
            $pages = $this->convertFlatToNestedPagesArray($pages, $rootPageUids);
        }

        /** @var ModifyPagesEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyPagesEvent($pages, $this));
        $this->view->assign('pages', $event->getPages());

        if ($this->settings['enablePagination'] ?? true) {
            $itemsPerPage = $this->settings['itemsPerPage'] ?? 10;
            $currentPage = max(1, $this->request->hasArgument('currentPage') ? (int)$this->request->getArgument('currentPage') : 1);
            $paginator = GeneralUtility::makeInstance(ArrayPaginator::class, $event->getPages(), $currentPage, $itemsPerPage, (int)($this->settings['limit'] ?? 0), 0);
            $pagination = $this->getPagination($paginator);
            $this->view->assign('pagination', [
                'currentPage' => $currentPage,
                'paginator' => $paginator,
                'pagination' => $pagination,
            ]);
        }

        return $this->htmlResponse();
    }

    protected function resolveCurrentPageUid(): int
    {
        $pageInformation = $this->request->getAttribute('frontend.page.information');
        if (is_object($pageInformation) && method_exists($pageInformation, 'getId')) {
            return (int)$pageInformation->getId();
        }

        $routing = $this->request->getAttribute('routing');
        if (is_object($routing) && method_exists($routing, 'getPageId')) {
            return (int)$routing->getPageId();
        }

        return 0;
    }

    /**
     * Function to sort given pages by recursiveRootLineOrdering string
     *
     * @param Page $a
     * @param Page $b
     * @return integer
     */
    protected function sortByRecursivelySorting(Page $a, Page $b)
    {
        return $a->getRecursiveRootLineOrdering() <=> $b->getRecursiveRootLineOrdering();
    }

    /**
     * Sets ordering and limitation settings from $this->settings
     *
     * @return void
     */
    protected function setOrderingAndLimitation()
    {
        if (!empty($this->settings['orderBy'])) {
            if ($this->settings['orderBy'] === 'customField') {
                $this->pageRepository->setOrderBy($this->settings['orderByCustomField']);
            } else {
                $this->pageRepository->setOrderBy($this->settings['orderBy']);
            }
        }

        if (!empty($this->settings['orderDirection'])) {
            $this->pageRepository->setOrderDirection($this->settings['orderDirection']);
        }

        if (!empty($this->settings['limit']) && ($this->settings['orderBy'] ?? '') !== 'random') {
            $this->pageRepository->setLimit(intval($this->settings['limit']));
        }
    }

    /**
     * Sets the fluid template to file if file is selected in flexform
     * configuration and file exists
     *
     * @return boolean Returns TRUE if templateType is file and exists,
     *         otherwise returns FALSE
     */
    protected function performTemplatePathAndFilename(): bool
    {
        $templateType = (string)($this->viewSettings['templateType'] ?? '');
        $templateFile = (string)($this->viewSettings['templateRootFile'] ?? '');
        $layoutRootPaths = $this->resolveViewPaths('layoutRootPaths', 'layoutRootPath');
        $partialRootPaths = $this->resolveViewPaths('partialRootPaths', 'partialRootPath');
        $templateRootPaths = $this->resolveViewPaths('templateRootPaths', 'templateRootPath');

        $preset = $this->viewSettings['templatePreset'] ?? null;
        if ($templateType === 'preset' && !empty($preset)) {
            $currentPreset = $this->viewSettings['presets'][$preset];
            if (!empty($currentPreset['partialRootPaths'])) {
                $partialRootPaths = $currentPreset['partialRootPaths'];
            }
            if (!empty($currentPreset['layoutRootPaths'])) {
                $layoutRootPaths = $currentPreset['layoutRootPaths'];
            }
            $templateType = 'file';
            $templateFile = $currentPreset['templateRootFile'];
        }

        if ($templateType !== 'preset' && $templateRootPaths !== []) {
            $firstPath = reset($templateRootPaths);
            if (!file_exists(GeneralUtility::getFileAbsFileName($firstPath))) {
                throw new Exception('Template folder "' . $firstPath . '" not found!');
            }
            $this->view->getRenderingContext()->getTemplatePaths()->setTemplateRootPaths($templateRootPaths);
        }

        if ($layoutRootPaths !== []) {
            $firstPath = reset($layoutRootPaths);
            if (!file_exists(GeneralUtility::getFileAbsFileName($firstPath))) {
                throw new Exception('Layout folder "' . $firstPath . '" not found!');
            }
            $this->view->getRenderingContext()->getTemplatePaths()->setLayoutRootPaths($layoutRootPaths);
        }
        if ($partialRootPaths !== []) {
            $firstPath = reset($partialRootPaths);
            if (!file_exists(GeneralUtility::getFileAbsFileName($firstPath))) {
                throw new Exception('Partial folder "' . $firstPath . '" not found!');
            }
            $this->view->getRenderingContext()->getTemplatePaths()->setPartialRootPaths($partialRootPaths);
        }
        if ($templateType === 'file'
            && $templateFile !== ''
            && file_exists(GeneralUtility::getFileAbsFileName($templateFile))
        ) {
            $this->view->getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateFile));
            return true;
        }

        $templatePathAndFilename = (string)($this->viewSettings['templatePathAndFilename'] ?? '');
        if ($templateType === '' && $templatePathAndFilename !== ''
            && file_exists(GeneralUtility::getFileAbsFileName($templatePathAndFilename))) {
            $this->view->getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFilename));
            return true;
        }
        return false;
    }

    /**
     * @return array<int, string>
     */
    private function resolveViewPaths(string $pluralKey, string $singularKey): array
    {
        $paths = $this->viewSettings[$pluralKey] ?? null;
        if (is_array($paths) && $paths !== []) {
            return $paths;
        }
        $singlePath = $this->viewSettings[$singularKey] ?? null;
        if (is_string($singlePath) && $singlePath !== '') {
            return [$singlePath];
        }
        return [];
    }

    /**
     * Performs configurations from plugin settings (flexform)
     *
     * @return void
     */
    protected function performPluginConfigurations()
    {
        // Set ShowNavHiddenItems to TRUE
        $this->pageRepository->setShowNavHiddenItems((string)($this->settings['showNavHiddenItems'] ?? '') === '1');
        $this->pageRepository->setFilteredDokType(
            GeneralUtility::trimExplode(
                ',',
                (string)($this->settings['showDoktypes'] ?? ''),
                true
            )
        );

        if (($this->settings['hideCurrentPage'] ?? '') === '1') {
            $this->pageRepository->setIgnoreOfUid($this->currentPageUid);
        }

        if (!empty($this->settings['ignoreUids'])) {
            $ignoringUids = GeneralUtility::trimExplode(',', (string)$this->settings['ignoreUids'], true);
            foreach ($ignoringUids as $uid) {
                $this->pageRepository->setIgnoreOfUid((int)$uid);
            }
        }

        if (!empty($this->settings['categoriesList']) && !empty($this->settings['categoryMode'])) {
            $categories = [];
            foreach (GeneralUtility::intExplode(',', $this->settings['categoriesList'], true) as $categoryUid) {
                $categories[] = $this->categoryRepository->findByUid($categoryUid);
            }

            $isAnd = match ((int)$this->settings['categoryMode']) {
                PageRepository::CATEGORY_MODE_OR, PageRepository::CATEGORY_MODE_OR_NOT => false,
                default => true,
            };
            $isNot = match ((int)$this->settings['categoryMode']) {
                PageRepository::CATEGORY_MODE_AND_NOT, PageRepository::CATEGORY_MODE_OR_NOT => true,
                default => false,
            };
            $this->pageRepository->addCategoryConstraint($categories, $isAnd, $isNot);
        }

        if ($this->settings['source'] === 'custom') {
            $this->settings['pageMode'] = 'flat';
        }

        if ($this->settings['pageMode'] === 'nested') {
            $this->settings['recursionDepthFrom'] = 0;
            $this->settings['orderBy'] = 'uid';
            $this->settings['limit'] = 0;
        }
    }

    /**
     * Performs special orderings like "random" or "sorting"
     *
     * @param array<Page> $pages
     * @return array
     */
    protected function performSpecialOrderings(array $pages)
    {
        // Make random if selected on queryResult, cause Extbase doesn't support it
        if (($this->settings['orderBy'] ?? '') === 'random') {
            shuffle($pages);
            if (!empty($this->settings['limit'])) {
                $pages = array_slice($pages, 0, $this->settings['limit']);
            }
        }

        if (($this->settings['orderBy'] ?? '') === 'sorting' && str_contains((string)($this->settings['source'] ?? ''), 'Recursively')) {
            usort($pages, $this->sortByRecursivelySorting(...));
            if (strtolower((string)$this->settings['orderDirection']) === strtolower(QueryInterface::ORDER_DESCENDING)) {
                $pages = array_reverse($pages);
            }
            if (!empty($this->settings['limit'])) {
                $pages = array_slice($pages, 0, $this->settings['limit']);
                return $pages;
            }
            return $pages;
        }
        return $pages;
    }

    /**
     * @param array<Page> $pages
     * @param string|int $rootPageUids Comma separated list of page uids
     * @return array<Page>
     */
    protected function convertFlatToNestedPagesArray(array $pages, string|int $rootPageUids): array
    {
        $rootPageUidArray = GeneralUtility::intExplode(',', (string)$rootPageUids);
        $rootPages = [];
        foreach ($rootPageUidArray as $rootPageUid) {
            $page = $this->pageRepository->findByUid($rootPageUid);
            if ($page instanceof Page) {
                $this->fillChildPagesRecursively($page, $pages);
                $rootPages[] = $page;
            }
        }
        return $rootPages;
    }

    /**
     * @param array<Page> $pages
     */
    protected function fillChildPagesRecursively(Page $parentPage, array $pages): void
    {
        $childPages = [];
        foreach ($pages as $page) {
            if ($page->getPid() === $parentPage->getUid()) {
                $this->fillChildPagesRecursively($page, $pages);
                $childPages[] = $page;
            }
        }

        usort($childPages, fn(Page $a, Page $b) => $a->getSorting() <=> $b->getSorting());

        $parentPage->setChildPages($childPages);
    }

    /**
     * @param PaginatorInterface $paginator
     * @param string|null $paginationClass
     * @return PaginationInterface
     */
    protected function getPagination($paginator, $paginationClass = null)
    {
        if (!empty($paginationClass) && class_exists($paginationClass)) {
            return GeneralUtility::makeInstance($paginationClass, $paginator);
        }

        return GeneralUtility::makeInstance(SimplePagination::class, $paginator);
    }
}
