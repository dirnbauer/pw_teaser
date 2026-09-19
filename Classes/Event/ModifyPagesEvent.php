<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Event;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2022 Armin Vieweg <armin@v.ieweg.de>
 */
use PwTeaserTeam\PwTeaser\Controller\TeaserController;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;

/**
 * Dispatched after the pages of a teaser have been loaded and before they are
 * passed to the view. Listeners may filter, sort or enrich the pages.
 */
final class ModifyPagesEvent
{
    /**
     * @param list<Page> $pages
     */
    public function __construct(private array $pages, private readonly TeaserController $teaserController) {}

    /**
     * @return list<Page>
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * @param list<Page> $pages
     */
    public function setPages(array $pages): void
    {
        $this->pages = $pages;
    }

    public function getTeaserController(): TeaserController
    {
        return $this->teaserController;
    }
}
