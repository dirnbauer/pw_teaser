<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\UserFunction;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\UserFunction\ItemsProcFunc;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

final class ItemsProcFuncTest extends TestCase
{
    /**
     * @param array<string, mixed> $presets plugin.tx_pwteaser.view.presets in TypoScript notation
     */
    private function createSubject(?array $presets): ItemsProcFunc
    {
        $configurationManager = self::createStub(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->willReturn($presets === null ? [] : ['plugin.' => ['tx_pwteaser.' => ['view.' => ['presets.' => $presets]]]]);

        return new ItemsProcFunc($configurationManager);
    }

    #[Test]
    public function presetsAreAddedAsItems(): void
    {
        $parameters = ['items' => [['label' => 'Keep me', 'value' => '']]];

        $this->createSubject([
            'default.' => ['label' => 'Default Template'],
            'grid.' => ['label' => 'Grid Layout'],
        ])->getAvailableTemplatePresets($parameters);

        self::assertSame([
            ['label' => 'Keep me', 'value' => ''],
            ['label' => 'Default Template', 'value' => 'default'],
            ['label' => 'Grid Layout', 'value' => 'grid'],
        ], $parameters['items']);
    }

    #[Test]
    public function itemsAreInitializedWhenMissing(): void
    {
        $parameters = [];

        $this->createSubject(['default.' => ['label' => 'Default']])->getAvailableTemplatePresets($parameters);

        self::assertSame([['label' => 'Default', 'value' => 'default']], $parameters['items']);
    }

    #[Test]
    public function scalarPresetEntriesAreSkipped(): void
    {
        $parameters = ['items' => []];

        $this->createSubject([
            'valid.' => ['label' => 'Valid'],
            'scalarValue' => 'not-an-array',
        ])->getAvailableTemplatePresets($parameters);

        self::assertSame([['label' => 'Valid', 'value' => 'valid']], $parameters['items']);
    }

    #[Test]
    public function presetKeyIsTheLabelFallback(): void
    {
        $parameters = ['items' => []];

        $this->createSubject([
            'noLabel.' => ['templateRootFile' => 'some/path'],
            'emptyLabel.' => ['label' => ''],
        ])->getAvailableTemplatePresets($parameters);

        self::assertSame([
            ['label' => 'noLabel', 'value' => 'noLabel'],
            ['label' => 'emptyLabel', 'value' => 'emptyLabel'],
        ], $parameters['items']);
    }

    #[Test]
    public function missingTypoScriptAddsNothing(): void
    {
        $parameters = ['items' => []];

        $this->createSubject(null)->getAvailableTemplatePresets($parameters);

        self::assertSame([], $parameters['items']);
    }
}
