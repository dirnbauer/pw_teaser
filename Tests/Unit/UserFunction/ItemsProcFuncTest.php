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
