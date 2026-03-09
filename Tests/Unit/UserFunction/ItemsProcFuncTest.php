<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\UserFunction;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\UserFunction\ItemsProcFunc;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

final class ItemsProcFuncTest extends TestCase
{
    #[Test]
    public function constructorAcceptsNullForFlexFormInstantiation(): void
    {
        $constructor = new \ReflectionMethod(ItemsProcFunc::class, '__construct');
        $params = $constructor->getParameters();

        self::assertCount(1, $params);
        self::assertTrue($params[0]->allowsNull(), 'ConfigurationManager parameter must be nullable');
        self::assertTrue($params[0]->isDefaultValueAvailable(), 'ConfigurationManager parameter must have a default value');
        self::assertNull($params[0]->getDefaultValue(), 'ConfigurationManager default must be null');
    }

    #[Test]
    public function constructorAcceptsExplicitConfigurationManager(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $subject = new ItemsProcFunc($configurationManager);

        self::assertInstanceOf(ItemsProcFunc::class, $subject);
    }

    #[Test]
    public function getAvailableTemplatePresetsAddsPresetsToItems(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn([
                'plugin.' => [
                    'tx_pwteaser.' => [
                        'view.' => [
                            'presets.' => [
                                'default.' => ['label' => 'Default Template'],
                                'grid.' => ['label' => 'Grid Layout'],
                            ],
                        ],
                    ],
                ],
            ]);

        $subject = new ItemsProcFunc($configurationManager);
        $parameters = ['items' => []];
        $subject->getAvailableTemplatePresets($parameters);

        self::assertCount(2, $parameters['items']);
        self::assertSame(['label' => 'Default Template', 'value' => 'default'], $parameters['items'][0]);
        self::assertSame(['label' => 'Grid Layout', 'value' => 'grid'], $parameters['items'][1]);
    }

    #[Test]
    public function getAvailableTemplatePresetsInitializesItemsWhenNotSet(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->willReturn([
                'plugin.' => [
                    'tx_pwteaser.' => [
                        'view.' => [
                            'presets.' => [
                                'default.' => ['label' => 'Default'],
                            ],
                        ],
                    ],
                ],
            ]);

        $subject = new ItemsProcFunc($configurationManager);
        $parameters = [];
        $subject->getAvailableTemplatePresets($parameters);

        self::assertArrayHasKey('items', $parameters);
        self::assertCount(1, $parameters['items']);
    }

    #[Test]
    public function getAvailableTemplatePresetsSkipsNonArrayPresets(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->willReturn([
                'plugin.' => [
                    'tx_pwteaser.' => [
                        'view.' => [
                            'presets.' => [
                                'valid.' => ['label' => 'Valid'],
                                'scalarValue' => 'not-an-array',
                            ],
                        ],
                    ],
                ],
            ]);

        $subject = new ItemsProcFunc($configurationManager);
        $parameters = ['items' => []];
        $subject->getAvailableTemplatePresets($parameters);

        self::assertCount(1, $parameters['items']);
        self::assertSame('Valid', $parameters['items'][0]['label']);
    }

    #[Test]
    public function getAvailableTemplatePresetsUsesKeyAsLabelFallback(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->willReturn([
                'plugin.' => [
                    'tx_pwteaser.' => [
                        'view.' => [
                            'presets.' => [
                                'noLabel.' => ['templateRootFile' => 'some/path'],
                            ],
                        ],
                    ],
                ],
            ]);

        $subject = new ItemsProcFunc($configurationManager);
        $parameters = ['items' => []];
        $subject->getAvailableTemplatePresets($parameters);

        self::assertCount(1, $parameters['items']);
        self::assertSame('noLabel.', $parameters['items'][0]['label']);
        self::assertSame('noLabel', $parameters['items'][0]['value']);
    }

    #[Test]
    public function getAvailableTemplatePresetsHandlesMissingConfiguration(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn([]);

        $subject = new ItemsProcFunc($configurationManager);
        $parameters = ['items' => []];
        $subject->getAvailableTemplatePresets($parameters);

        self::assertSame([], $parameters['items']);
    }
}
