<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Domain\Repository;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 *  |     2016 Tim Klein-Hitpass <tim.klein-hitpass@diemedialen.de>
 *  |     2016 Kai Ratzeburg <kai.ratzeburg@diemedialen.de>
 */
use PwTeaserTeam\PwTeaser\Database\RecordRowLoader;
use PwTeaserTeam\PwTeaser\Domain\Model\Content;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Loads the content elements of a teased page (plugin setting "loadContents").
 *
 * @extends Repository<Content>
 */
final class ContentRepository extends Repository
{
    public function __construct(private readonly RecordRowLoader $rowLoader)
    {
        parent::__construct();
    }

    /**
     * Content elements are read from the teased page, never from the storage pid.
     * Built per query so every query sees the language of the current request.
     *
     * @return QueryInterface<Content>
     */
    public function createQuery(): QueryInterface
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        return $query;
    }

    /**
     * @return list<Content> Content elements of the page in their backend sorting
     */
    public function findByPid(int $pid): array
    {
        $query = $this->createQuery();
        $query->matching($query->equals('pid', $pid));
        $query->setOrderings(['sorting' => QueryInterface::ORDER_ASCENDING]);
        $contents = array_values($query->execute()->toArray());

        $rows = $this->rowLoader->loadByUids(
            'tt_content',
            array_map(static fn(Content $content): int => (int)$content->getUid(), $contents)
        );
        foreach ($contents as $content) {
            $content->setContentRow($rows[(int)$content->getUid()] ?? []);
        }
        return $contents;
    }
}
