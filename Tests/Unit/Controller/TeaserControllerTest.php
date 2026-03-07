<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Controller\TeaserController;
use PwTeaserTeam\PwTeaser\Domain\Repository\CategoryRepository;
use PwTeaserTeam\PwTeaser\Domain\Repository\ContentRepository;
use PwTeaserTeam\PwTeaser\Domain\Repository\PageRepository;
use PwTeaserTeam\PwTeaser\Utility\Settings;
use ReflectionClass;
use ReflectionProperty;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

final class TeaserControllerTest extends TestCase
{
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
}
