<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use PwTeaserTeam\PwTeaser\Domain\Repository\PageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['extbase', 'fluid', 'frontend'];

    protected array $pathsToLinkInTestInstance = [
        __DIR__ . '/../../../../' => 'typo3conf/ext/pw_teaser',
    ];

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/pw_teaser',
    ];

    private PageRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->subject = $this->get(PageRepository::class);
    }

    #[Test]
    public function collectRecursivePageIdsSkipsDeletedPages(): void
    {
        $method = new \ReflectionMethod(PageRepository::class, 'collectRecursivePageIds');
        $method->setAccessible(true);

        $pageIds = $method->invoke($this->subject, 1, 1, 2);
        sort($pageIds);

        self::assertSame([2, 3, 4], $pageIds);
    }

    #[Test]
    public function pageHasTranslationIgnoresDeletedTranslationRows(): void
    {
        $method = new \ReflectionMethod(PageRepository::class, 'pageHasTranslation');
        $method->setAccessible(true);

        self::assertTrue($method->invoke($this->subject, 2, 1));
        self::assertFalse($method->invoke($this->subject, 3, 1));
    }
}
