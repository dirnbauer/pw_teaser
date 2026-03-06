<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Event;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2022 Armin Vieweg <armin@v.ieweg.de>
 */
use PwTeaserTeam\PwTeaser\Controller\TeaserController;

final class ModifyPagesEvent
{
    /**
     * @var array<int, mixed>
     */
    private array $pages;

    private TeaserController $teaserController;

    /**
     * @param array<int, mixed> $pages
     */
    public function __construct(array $pages, TeaserController $teaserController)
    {
        $this->pages = $pages;
        $this->teaserController = $teaserController;
    }

    /**
     * @return array<int, mixed>
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * @param array<int, mixed> $pages
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
