<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Domain\Repository;

use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Persistence\Repository;

final class CategoryRepository extends Repository
{
    public function __construct()
    {
        $this->objectType = Category::class;
    }
}
