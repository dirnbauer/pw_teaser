<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use PwTeaserTeam\PwTeaser\Domain\Model\Content;
use PwTeaserTeam\PwTeaser\Domain\Repository\ContentRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContentRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['extbase', 'fluid', 'frontend'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/pw_teaser'];

    #[Test]
    public function findByPidReturnsContentElementsInSortingOrderWithTheirRawRow(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $subject = $this->get(ContentRepository::class);

        $result = $subject->findByPid(2);

        self::assertSame(['First element', 'Second element'], array_map(static fn(Content $content): string => $content->getHeader(), $result));
        self::assertSame('image', $result[0]->getCtype());
        self::assertSame(3, (int)$result[0]->getGet()['layout']);
        self::assertSame([], $subject->findByPid(9999));
    }
}
