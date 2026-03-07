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
use PwTeaserTeam\PwTeaser\Domain\Model\Content;
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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\View\TemplatePaths;

/**
 * Controller for the teaser object
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TeaserController extends ActionController
{
    /** @var array<string, mixed> */
    protected array $settings = [];

    protected int $currentPageUid = 0;

    /** @var array<string, mixed> */
    protected array $viewSettings = [];

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
                'paginationClass' => '',
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
                    $this->getIntSetting('recursionDepthFrom'),
                    $this->getIntSetting('recursionDepth', 255)
                );
                break;

            case 'custom':
                $rootPageUids = $this->getStringSetting('customPages');
                $pages = $this->pageRepository->findByPidList(
                    $this->getStringSetting('customPages'),
                    !empty($this->settings['orderByPlugin'])
                );
                break;

            case 'customChildren':
                $rootPageUids = $this->getStringSetting('customPages');
                $pages = $this->pageRepository->findChildrenByPidList($this->getStringSetting('customPages'));
                break;

            case 'customChildrenRecursively':
                $rootPageUids = $this->getStringSetting('customPages');
                $pages = $this->pageRepository->findChildrenRecursivelyByPidList(
                    $this->getStringSetting('customPages'),
                    $this->getIntSetting('recursionDepthFrom'),
                    $this->getIntSetting('recursionDepth', 255)
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
                $pageUid = $page->getUid();
                if ($pageUid !== null) {
                    /** @var array<Content> $contents */
                    $contents = $this->contentRepository->findByPid($pageUid)->toArray();
                    $page->setContents($contents);
                }
            }
        }

        if ($this->settings['pageMode'] === 'nested') {
            $pages = $this->convertFlatToNestedPagesArray($pages, $rootPageUids);
        }

        /** @var ModifyPagesEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyPagesEvent($pages, $this));
        $this->view->assign('pages', $event->getPages());

        if (!empty($this->settings['enablePagination'])) {
            $itemsPerPage = $this->getIntSetting('itemsPerPage', 10);
            $currentPageArg = $this->request->getArgument('currentPage');
            $currentPage = max(1, is_numeric($currentPageArg) ? (int)$currentPageArg : 1);
            $paginator = GeneralUtility::makeInstance(ArrayPaginator::class, $event->getPages(), $currentPage, $itemsPerPage, $this->getIntSetting('limit'), 0);
            $paginationClass = $this->settings['paginationClass'] ?? null;
            $pagination = $this->getPagination($paginator, is_string($paginationClass) ? $paginationClass : null);
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
    protected function setOrderingAndLimitation(): void
    {
        $orderBy = $this->getStringSetting('orderBy');
        if ($orderBy !== '') {
            if ($orderBy === 'customField') {
                $this->pageRepository->setOrderBy($this->getStringSetting('orderByCustomField'));
            } else {
                $this->pageRepository->setOrderBy($orderBy);
            }
        }

        $orderDirection = $this->getStringSetting('orderDirection');
        if ($orderDirection !== '') {
            $this->pageRepository->setOrderDirection($orderDirection);
        }

        $limit = $this->getIntSetting('limit');
        if ($limit > 0 && $orderBy !== 'random') {
            $this->pageRepository->setLimit($limit);
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
        $templateTypeRaw = $this->viewSettings['templateType'] ?? '';
        $templateType = is_scalar($templateTypeRaw) ? (string)$templateTypeRaw : '';
        $templateFileRaw = $this->viewSettings['templateRootFile'] ?? '';
        $templateFile = is_scalar($templateFileRaw) ? (string)$templateFileRaw : '';
        $layoutRootPaths = $this->resolveViewPaths('layoutRootPaths', 'layoutRootPath');
        $partialRootPaths = $this->resolveViewPaths('partialRootPaths', 'partialRootPath');
        $templateRootPaths = $this->resolveViewPaths('templateRootPaths', 'templateRootPath');

        $presetKey = $this->viewSettings['templatePreset'] ?? null;
        if ($templateType === 'preset' && is_string($presetKey) && $presetKey !== '') {
            $allPresets = $this->viewSettings['presets'] ?? [];
            $currentPreset = is_array($allPresets) ? ($allPresets[$presetKey] ?? null) : null;
            if (is_array($currentPreset)) {
                $presetPartials = $currentPreset['partialRootPaths'] ?? null;
                if (is_array($presetPartials) && $presetPartials !== []) {
                    $partialRootPaths = array_values(array_filter($presetPartials, 'is_string'));
                }
                $presetLayouts = $currentPreset['layoutRootPaths'] ?? null;
                if (is_array($presetLayouts) && $presetLayouts !== []) {
                    $layoutRootPaths = array_values(array_filter($presetLayouts, 'is_string'));
                }
                $templateType = 'file';
                $presetTemplate = $currentPreset['templateRootFile'] ?? '';
                $templateFile = is_scalar($presetTemplate) ? (string)$presetTemplate : '';
            }
        }

        if ($templateType !== 'preset' && $templateRootPaths !== []) {
            $firstPath = reset($templateRootPaths);
            if (!file_exists(GeneralUtility::getFileAbsFileName($firstPath))) {
                throw new Exception('Template folder "' . $firstPath . '" not found!');
            }
            $this->getViewTemplatePaths()->setTemplateRootPaths($templateRootPaths);
        }

        if ($layoutRootPaths !== []) {
            $firstPath = reset($layoutRootPaths);
            if (!file_exists(GeneralUtility::getFileAbsFileName($firstPath))) {
                throw new Exception('Layout folder "' . $firstPath . '" not found!');
            }
            $this->getViewTemplatePaths()->setLayoutRootPaths($layoutRootPaths);
        }
        if ($partialRootPaths !== []) {
            $firstPath = reset($partialRootPaths);
            if (!file_exists(GeneralUtility::getFileAbsFileName($firstPath))) {
                throw new Exception('Partial folder "' . $firstPath . '" not found!');
            }
            $this->getViewTemplatePaths()->setPartialRootPaths($partialRootPaths);
        }
        if ($templateType === 'file'
            && $templateFile !== ''
            && file_exists(GeneralUtility::getFileAbsFileName($templateFile))
        ) {
            $this->getViewTemplatePaths()->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateFile));
            return true;
        }

        $tpafRaw = $this->viewSettings['templatePathAndFilename'] ?? '';
        $templatePathAndFilename = is_scalar($tpafRaw) ? (string)$tpafRaw : '';
        if ($templateType === '' && $templatePathAndFilename !== ''
            && file_exists(GeneralUtility::getFileAbsFileName($templatePathAndFilename))) {
            $this->getViewTemplatePaths()->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFilename));
            return true;
        }
        return false;
    }

    private function getViewTemplatePaths(): TemplatePaths
    {
        $view = $this->view;
        if ($view instanceof \TYPO3Fluid\Fluid\View\AbstractTemplateView) {
            return $view->getRenderingContext()->getTemplatePaths();
        }
        throw new \RuntimeException('View is not an AbstractTemplateView instance');
    }

    private function getStringSetting(string $key, string $default = ''): string
    {
        $value = $this->settings[$key] ?? $default;
        return is_scalar($value) ? (string)$value : $default;
    }

    private function getIntSetting(string $key, int $default = 0): int
    {
        $value = $this->settings[$key] ?? $default;
        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * @return array<int, string>
     */
    private function resolveViewPaths(string $pluralKey, string $singularKey): array
    {
        $paths = $this->viewSettings[$pluralKey] ?? null;
        if (is_array($paths) && $paths !== []) {
            return array_values(array_filter($paths, 'is_string'));
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
        $this->pageRepository->setShowNavHiddenItems($this->getStringSetting('showNavHiddenItems') === '1');
        $this->pageRepository->setFilteredDokType(
            GeneralUtility::intExplode(
                ',',
                $this->getStringSetting('showDoktypes'),
                true
            )
        );

        if ($this->getStringSetting('hideCurrentPage') === '1') {
            $this->pageRepository->setIgnoreOfUid($this->currentPageUid);
        }

        $ignoreUidsStr = $this->getStringSetting('ignoreUids');
        if ($ignoreUidsStr !== '') {
            $ignoringUids = GeneralUtility::trimExplode(',', $ignoreUidsStr, true);
            foreach ($ignoringUids as $uid) {
                $this->pageRepository->setIgnoreOfUid((int)$uid);
            }
        }

        $categoriesList = $this->getStringSetting('categoriesList');
        $categoryMode = $this->getIntSetting('categoryMode');
        if ($categoriesList !== '' && $categoryMode > 0) {
            $categories = [];
            foreach (GeneralUtility::intExplode(',', $categoriesList, true) as $categoryUid) {
                $categories[] = $this->categoryRepository->findByUid($categoryUid);
            }

            $isAnd = match ($categoryMode) {
                PageRepository::CATEGORY_MODE_OR, PageRepository::CATEGORY_MODE_OR_NOT => false,
                default => true,
            };
            $isNot = match ($categoryMode) {
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
     * @return array<int, Page>
     */
    protected function performSpecialOrderings(array $pages): array
    {
        $orderBy = $this->getStringSetting('orderBy');
        $limit = $this->getIntSetting('limit');

        if ($orderBy === 'random') {
            shuffle($pages);
            if ($limit > 0) {
                $pages = array_slice($pages, 0, $limit);
            }
        }

        if ($orderBy === 'sorting' && str_contains($this->getStringSetting('source'), 'Recursively')) {
            usort($pages, $this->sortByRecursivelySorting(...));
            if (strtolower($this->getStringSetting('orderDirection')) === strtolower(QueryInterface::ORDER_DESCENDING)) {
                $pages = array_reverse($pages);
            }
            if ($limit > 0) {
                $pages = array_slice($pages, 0, $limit);
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

    protected function getPagination(PaginatorInterface $paginator, ?string $paginationClass = null): PaginationInterface
    {
        if ($paginationClass !== null && $paginationClass !== '' && class_exists($paginationClass)) {
            $instance = GeneralUtility::makeInstance($paginationClass, $paginator);
            assert($instance instanceof PaginationInterface);
            return $instance;
        }

        return GeneralUtility::makeInstance(SimplePagination::class, $paginator);
    }
}
