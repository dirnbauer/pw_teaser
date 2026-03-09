<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Controller\TeaserController;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use PwTeaserTeam\PwTeaser\Domain\Repository\CategoryRepository;
use PwTeaserTeam\PwTeaser\Domain\Repository\ContentRepository;
use PwTeaserTeam\PwTeaser\Domain\Repository\PageRepository;
use PwTeaserTeam\PwTeaser\Utility\Settings;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

final class TeaserControllerTest extends TestCase
{
    private function createController(array $settings = []): TeaserController
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->willReturn([]);

        $pageRepository = (new ReflectionClass(PageRepository::class))->newInstanceWithoutConstructor();
        $contentRepository = (new ReflectionClass(ContentRepository::class))->newInstanceWithoutConstructor();

        $subject = new TeaserController(
            $pageRepository,
            $contentRepository,
            new CategoryRepository(),
            new Settings($configurationManager)
        );

        if ($settings !== []) {
            $this->writeProperty($subject, 'settings', $settings);
        }

        return $subject;
    }

    #[Test]
    public function initializeActionAppliesDefaultsWhenViewConfigurationIsMissing(): void
    {
        $settingsConfigurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $settingsConfigurationManager->expects(self::once())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn([]);

        $pageRepository = (new ReflectionClass(PageRepository::class))->newInstanceWithoutConstructor();
        $contentRepository = (new ReflectionClass(ContentRepository::class))->newInstanceWithoutConstructor();

        $subject = new TeaserController(
            $pageRepository,
            $contentRepository,
            new CategoryRepository(),
            new Settings($settingsConfigurationManager)
        );

        $frameworkConfigurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $frameworkConfigurationManager->expects(self::once())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)
            ->willReturn([]);
        $this->writeProperty($subject, 'configurationManager', $frameworkConfigurationManager);

        $request = $this->createMock(RequestInterface::class);
        $request->method('getAttribute')
            ->with('currentContentObject')
            ->willReturn(null);

        $this->writeProperty($subject, 'request', $request);
        $this->writeProperty($subject, 'settings', ['loadContents' => '1']);

        $subject->initializeAction();

        $settings = $this->readProperty($subject, 'settings');
        $viewSettings = $this->readProperty($subject, 'viewSettings');

        self::assertSame('thisChildren', $settings['source']);
        self::assertSame('1', $settings['loadContents']);
        self::assertSame('', $settings['showDoktypes']);
        self::assertSame(255, $settings['recursionDepth']);
        self::assertSame(1, $settings['enablePagination']);
        self::assertSame(['presets' => []], $viewSettings);
    }

    #[Test]
    public function getStringSettingReturnsStringValue(): void
    {
        $subject = $this->createController(['myKey' => 'myValue']);

        $method = new ReflectionMethod($subject, 'getStringSetting');
        $method->setAccessible(true);

        self::assertSame('myValue', $method->invoke($subject, 'myKey'));
    }

    #[Test]
    public function getStringSettingReturnsDefaultForMissingKey(): void
    {
        $subject = $this->createController([]);

        $method = new ReflectionMethod($subject, 'getStringSetting');
        $method->setAccessible(true);

        self::assertSame('', $method->invoke($subject, 'missing'));
        self::assertSame('fallback', $method->invoke($subject, 'missing', 'fallback'));
    }

    #[Test]
    public function getStringSettingCastsNonScalarToDefault(): void
    {
        $subject = $this->createController(['arr' => ['nested']]);

        $method = new ReflectionMethod($subject, 'getStringSetting');
        $method->setAccessible(true);

        self::assertSame('', $method->invoke($subject, 'arr'));
    }

    #[Test]
    public function getStringSettingCastsIntToString(): void
    {
        $subject = $this->createController(['num' => 42]);

        $method = new ReflectionMethod($subject, 'getStringSetting');
        $method->setAccessible(true);

        self::assertSame('42', $method->invoke($subject, 'num'));
    }

    #[Test]
    public function getIntSettingReturnsIntValue(): void
    {
        $subject = $this->createController(['limit' => 25]);

        $method = new ReflectionMethod($subject, 'getIntSetting');
        $method->setAccessible(true);

        self::assertSame(25, $method->invoke($subject, 'limit'));
    }

    #[Test]
    public function getIntSettingReturnsDefaultForMissingKey(): void
    {
        $subject = $this->createController([]);

        $method = new ReflectionMethod($subject, 'getIntSetting');
        $method->setAccessible(true);

        self::assertSame(0, $method->invoke($subject, 'missing'));
        self::assertSame(10, $method->invoke($subject, 'missing', 10));
    }

    #[Test]
    public function getIntSettingCastsNumericStringToInt(): void
    {
        $subject = $this->createController(['limit' => '15']);

        $method = new ReflectionMethod($subject, 'getIntSetting');
        $method->setAccessible(true);

        self::assertSame(15, $method->invoke($subject, 'limit'));
    }

    #[Test]
    public function getIntSettingReturnsDefaultForNonNumeric(): void
    {
        $subject = $this->createController(['limit' => 'abc']);

        $method = new ReflectionMethod($subject, 'getIntSetting');
        $method->setAccessible(true);

        self::assertSame(0, $method->invoke($subject, 'limit'));
    }

    #[Test]
    public function performSpecialOrderingsShufflesForRandom(): void
    {
        $subject = $this->createController(['orderBy' => 'random', 'limit' => '0', 'source' => 'thisChildren']);

        $pages = [];
        for ($i = 0; $i < 20; $i++) {
            $page = new Page();
            $page->setTitle('Page ' . $i);
            $pages[] = $page;
        }

        $method = new ReflectionMethod($subject, 'performSpecialOrderings');
        $method->setAccessible(true);
        $result = $method->invoke($subject, $pages);

        self::assertCount(20, $result);
    }

    #[Test]
    public function performSpecialOrderingsRespectsLimitForRandom(): void
    {
        $subject = $this->createController(['orderBy' => 'random', 'limit' => '3', 'source' => 'thisChildren']);

        $pages = [];
        for ($i = 0; $i < 10; $i++) {
            $page = new Page();
            $page->setTitle('Page ' . $i);
            $pages[] = $page;
        }

        $method = new ReflectionMethod($subject, 'performSpecialOrderings');
        $method->setAccessible(true);
        $result = $method->invoke($subject, $pages);

        self::assertCount(3, $result);
    }

    #[Test]
    public function performSpecialOrderingsPassesThroughForNonSpecialOrder(): void
    {
        $subject = $this->createController(['orderBy' => 'title', 'limit' => '0', 'source' => 'thisChildren']);

        $page1 = new Page();
        $page1->setTitle('B');
        $page2 = new Page();
        $page2->setTitle('A');
        $pages = [$page1, $page2];

        $method = new ReflectionMethod($subject, 'performSpecialOrderings');
        $method->setAccessible(true);
        $result = $method->invoke($subject, $pages);

        self::assertCount(2, $result);
        self::assertSame('B', $result[0]->getTitle());
    }

    #[Test]
    public function resolveViewPathsReturnsPluralPaths(): void
    {
        $subject = $this->createController();
        $this->writeProperty($subject, 'viewSettings', [
            'templateRootPaths' => ['/path/one', '/path/two'],
        ]);

        $method = new ReflectionMethod($subject, 'resolveViewPaths');
        $method->setAccessible(true);
        $result = $method->invoke($subject, 'templateRootPaths', 'templateRootPath');

        self::assertSame(['/path/one', '/path/two'], $result);
    }

    #[Test]
    public function resolveViewPathsFallsBackToSingularPath(): void
    {
        $subject = $this->createController();
        $this->writeProperty($subject, 'viewSettings', [
            'templateRootPath' => '/single/path',
        ]);

        $method = new ReflectionMethod($subject, 'resolveViewPaths');
        $method->setAccessible(true);
        $result = $method->invoke($subject, 'templateRootPaths', 'templateRootPath');

        self::assertSame(['/single/path'], $result);
    }

    #[Test]
    public function resolveViewPathsReturnsEmptyArrayWhenNothingConfigured(): void
    {
        $subject = $this->createController();
        $this->writeProperty($subject, 'viewSettings', []);

        $method = new ReflectionMethod($subject, 'resolveViewPaths');
        $method->setAccessible(true);
        $result = $method->invoke($subject, 'templateRootPaths', 'templateRootPath');

        self::assertSame([], $result);
    }

    #[Test]
    public function fillChildPagesRecursivelySortsChildrenBySorting(): void
    {
        $subject = $this->createController();

        $parent = new Page();
        $this->writeUid($parent, 1);

        $childB = new Page();
        $this->writeUid($childB, 3);
        $this->writePid($childB, 1);
        $childB->setTitle('Child B');
        $childB->setSorting(512);

        $childA = new Page();
        $this->writeUid($childA, 2);
        $this->writePid($childA, 1);
        $childA->setTitle('Child A');
        $childA->setSorting(256);

        $method = new ReflectionMethod($subject, 'fillChildPagesRecursively');
        $method->setAccessible(true);
        $method->invoke($subject, $parent, [$childB, $childA]);

        $children = $parent->getChildPages();
        self::assertCount(2, $children);
        self::assertSame('Child A', $children[0]->getTitle());
        self::assertSame('Child B', $children[1]->getTitle());
    }

    private function writeProperty(object $subject, string $propertyName, mixed $value): void
    {
        $property = new ReflectionProperty($subject, $propertyName);
        $property->setAccessible(true);
        $property->setValue($subject, $value);
    }

    private function readProperty(object $subject, string $propertyName): mixed
    {
        $property = new ReflectionProperty($subject, $propertyName);
        $property->setAccessible(true);

        return $property->getValue($subject);
    }

    private function writeUid(object $entity, int $uid): void
    {
        $property = new ReflectionProperty($entity, 'uid');
        $property->setAccessible(true);
        $property->setValue($entity, $uid);
    }

    private function writePid(object $entity, int $pid): void
    {
        $property = new ReflectionProperty($entity, 'pid');
        $property->setAccessible(true);
        $property->setValue($entity, $pid);
    }
}
