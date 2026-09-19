<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Functional\Database;

use PHPUnit\Framework\Attributes\Test;
use PwTeaserTeam\PwTeaser\Database\RecordRowLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordRowLoaderTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/pw_teaser'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Domain/Repository/Fixtures/pages.csv');
    }

    #[Test]
    public function rowsAreIndexedByUidWithLowerCamelCaseKeys(): void
    {
        $rows = $this->get(RecordRowLoader::class)->loadByUids('pages', [2, 3, 9999]);

        self::assertSame([2, 3], array_keys($rows));
        self::assertSame('Child A', $rows[2]['title']);
        self::assertSame(2, (int)$rows[2]['l10nParent'] + 2);
        self::assertArrayHasKey('sysLanguageUid', $rows[2]);
        self::assertArrayNotHasKey('sys_language_uid', $rows[2]);
        self::assertSame([], $this->get(RecordRowLoader::class)->loadByUids('pages', []));
    }

    #[Test]
    public function singleRowsCanBeLoadedThroughMakeInstance(): void
    {
        $loader = GeneralUtility::makeInstance(RecordRowLoader::class);

        self::assertSame('Grandchild', $loader->loadByUid('pages', 4)['title']);
        self::assertSame([], $loader->loadByUid('pages', 9999));
    }
}
