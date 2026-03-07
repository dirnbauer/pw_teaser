<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Utility\Settings;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

final class SettingsTest extends TestCase
{
    #[Test]
    public function renderConfigurationArrayReturnsPlainValues(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn([]);

        $subject = new Settings($configurationManager);

        $result = $subject->renderConfigurationArray(['foo' => 'bar', 'baz' => '42']);

        self::assertSame(['foo' => 'bar', 'baz' => '42'], $result);
    }

    #[Test]
    public function renderConfigurationArrayFallsBackToTypoScriptForEmptyValues(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn([
                'plugin.' => [
                    'tx_pwteaser.' => [
                        'settings.' => [
                            'limit' => '5',
                        ],
                    ],
                ],
            ]);

        $subject = new Settings($configurationManager);

        $result = $subject->renderConfigurationArray(['limit' => '', 'source' => 'thisChildren']);

        self::assertSame('5', $result['limit']);
        self::assertSame('thisChildren', $result['source']);
    }

    #[Test]
    public function renderConfigurationArraySkipsCObjRenderingWithoutContentObject(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn([]);

        $subject = new Settings($configurationManager);

        $input = [
            'title' => [
                '_typoScriptNodeValue' => 'TEXT',
                'value' => 'Hello',
            ],
        ];
        $result = $subject->renderConfigurationArray($input);

        self::assertArrayNotHasKey('title', $result);
    }

    #[Test]
    public function renderConfigurationArrayHandlesNestedArrays(): void
    {
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            ->willReturn([]);

        $subject = new Settings($configurationManager);

        $input = [
            'nested' => [
                'key' => 'value',
            ],
        ];
        $result = $subject->renderConfigurationArray($input);

        self::assertSame('value', $result['nested']['key']);
    }
}
