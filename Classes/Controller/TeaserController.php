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

    protected PageRepository $pageRepository;

    protected ContentRepository $contentRepository;

    protected CategoryRepository $categoryRepository;

    protected Settings $settingsUtility;

    /**
     * @var TemplateView
     */
    protected $view;

    /**
     * @var array
     */
    protected $viewSettings = [];

    public function __construct(
        PageRepository $pageRepository,
        ContentRepository $contentRepository,
        CategoryRepository $categoryRepository,
        Settings $settingsUtility
    ) {
        $this->pageRepository = $pageRepository;
        $this->contentRepository = $contentRepository;
        $this->categoryRepository = $categoryRepository;
        $this->settingsUtility = $settingsUtility;
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
        $this->settings = $this->settingsUtility->renderConfigurationArray($this->settings);

        $frameworkSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
        $viewSettings = $frameworkSettings['view'];
        $presets = $viewSettings['presets'] ?? [];
        unset($viewSettings['presets']);
        $this->viewSettings = $this->settingsUtility->renderConfigurationArray($viewSettings, 'view.');
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

        /** @var $page \PwTeaserTeam\PwTeaser\Domain\Model\Page */
        foreach ($pages as $page) {
            if ($page->getUid() === $this->currentPageUid) {
                $page->setIsCurrentPage(true);
            }

            // Load contents if enabled in configuration
            if ($this->settings['loadContents'] == '1') {
                $page->setContents($this->contentRepository->findByPid($page->getUid()));
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

        if (!empty($this->settings['limit']) && $this->settings['orderBy'] !== 'random') {
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
    protected function performTemplatePathAndFilename()
    {
        $templateType = $this->viewSettings['templateType'] ?? '';
        $templateFile = $this->viewSettings['templateRootFile'] ?? '';
        $layoutRootPaths = $this->viewSettings['layoutRootPaths'] ?? null ?: [$this->viewSettings['layoutRootPath'] ?? null ?: null];
        $partialRootPaths = $this->viewSettings['partialRootPaths'] ?? null ?: [$this->viewSettings['partialRootPath'] ?? null ?: null];
        $templateRootPaths = $this->viewSettings['templateRootPaths'] ?? null ?: [$this->viewSettings['templateRootPath'] ?? null ?: null];

        $preset = $this->viewSettings['templatePreset'] ?? null;
        if ($templateType === 'preset' && !empty($preset)) {
            $currentPreset = $this->viewSettings['presets'][$preset];
            if (array_key_exists('partialRootPaths', $currentPreset) && !empty($currentPreset['partialRootPaths'])) {
                $partialRootPaths = $currentPreset['partialRootPaths'];
            }
            if (array_key_exists('layoutRootPaths', $currentPreset) && !empty($currentPreset['layoutRootPaths'])) {
                $layoutRootPaths = $currentPreset['layoutRootPaths'];
            }
            $templateType = 'file';
            $templateFile = $currentPreset['templateRootFile'];
        }

        if ($templateType !== 'preset' && $templateRootPaths !== [null] && !empty($templateRootPaths)) {
            if (!file_exists(GeneralUtility::getFileAbsFileName(reset($templateRootPaths)))) {
                throw new Exception('Template folder "' . reset($templateRootPaths) . '" not found!');
            }
            $this->view->setTemplateRootPaths($templateRootPaths);
        }

        if ($layoutRootPaths !== [null] && !empty($layoutRootPaths)) {
            if (!file_exists(GeneralUtility::getFileAbsFileName(reset($layoutRootPaths)))) {
                throw new Exception('Layout folder "' . reset($layoutRootPaths) . '" not found!');
            }
            $this->view->setLayoutRootPaths($layoutRootPaths);
        }
        if ($partialRootPaths !== [null] && !empty($partialRootPaths)) {
            if (!file_exists(GeneralUtility::getFileAbsFileName(reset($partialRootPaths)))) {
                throw new Exception('Partial folder "' . reset($partialRootPaths) . '" not found!');
            }
            $this->view->setPartialRootPaths($partialRootPaths);
        }
        if ($templateType === 'file' &&
            !empty($templateFile) &&
            file_exists(GeneralUtility::getFileAbsFileName($templateFile))
        ) {
            $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateFile));
            return true;
        }

        $templatePathAndFilename = $this->viewSettings['templatePathAndFilename'] ?? '';
        if ($templateType === null && !empty($templatePathAndFilename)
            && file_exists(GeneralUtility::getFileAbsFileName($templatePathAndFilename))) {
            $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFilename));
            return true;
        }
        return false;
    }

    /**
     * Performs configurations from plugin settings (flexform)
     *
     * @return void
     */
    protected function performPluginConfigurations()
    {
        // Set ShowNavHiddenItems to TRUE
        $this->pageRepository->setShowNavHiddenItems(($this->settings['showNavHiddenItems'] == '1'));
        $this->pageRepository->setFilteredDokType(
            GeneralUtility::trimExplode(
                ',',
                $this->settings['showDoktypes'],
                true
            )
        );

        if ($this->settings['hideCurrentPage'] ?? null == '1') {
            $this->pageRepository->setIgnoreOfUid($this->currentPageUid);
        }

        if ($this->settings['ignoreUids'] ?? null) {
            $ignoringUids = GeneralUtility::trimExplode(',', $this->settings['ignoreUids'], true);
            array_map($this->pageRepository->setIgnoreOfUid(...), $ignoringUids);
        }

        if (($this->settings['categoriesList'] ?? null) && $this->settings['categoryMode'] ?? null) {
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
        if ($this->settings['orderBy'] === 'random') {
            shuffle($pages);
            if (!empty($this->settings['limit'])) {
                $pages = array_slice($pages, 0, $this->settings['limit']);
            }
        }

        if ($this->settings['orderBy'] === 'sorting' && str_contains((string)$this->settings['source'], 'Recursively')) {
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
     * Converts given pages array (flat) to nested one
     *
     * @param array<Page> $pages
     * @param string $rootPageUids Comma separated list of page uids
     * @return array<Page>
     */
    protected function convertFlatToNestedPagesArray($pages, $rootPageUids)
    {
        $rootPageUidArray = GeneralUtility::intExplode(',', $rootPageUids);
        $rootPages = [];
        foreach ($rootPageUidArray as $rootPageUid) {
            $page = $this->pageRepository->findByUid($rootPageUid);
            $this->fillChildPagesRecursivley($page, $pages);
            $rootPages[] = $page;
        }
        return $rootPages;
    }

    /**
     * Fills given parentPage's childPages attribute recursively with pages
     *
     * @param Page $parentPage
     * @param array $pages
     * @return Page
     */
    protected function fillChildPagesRecursivley($parentPage, array $pages)
    {
        $childPages = [];
        /** @var $page \PwTeaserTeam\PwTeaser\Domain\Model\Page */
        foreach ($pages as $page) {
            if ($page->getPid() === $parentPage->getUid()) {
                $this->fillChildPagesRecursivley($page, $pages);
                $childPages[] = $page;
            }
        }

        usort($childPages, fn(Page $a, Page $b) => $a->getSorting() <=> $b->getSorting());

        $parentPage->setChildPages($childPages);
        return $parentPage;
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
