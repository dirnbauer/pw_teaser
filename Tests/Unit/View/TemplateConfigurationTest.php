<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\View;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\View\TemplateConfiguration;

final class TemplateConfigurationTest extends TestCase
{
    /** @var array<string, array<string, mixed>> */
    private const PRESETS = [
        'default' => [
            'label' => 'Default',
            'templateRootFile' => 'EXT:pw_teaser/Resources/Private/Templates/Teaser/Index.html',
            'partialRootPaths' => [10 => 'EXT:pw_teaser/Resources/Private/Partials'],
            'layoutRootPaths' => [10 => 'EXT:pw_teaser/Resources/Private/Layouts'],
        ],
        'bare' => [
            'templateRootFile' => 'EXT:site/Bare.html',
        ],
    ];

    #[Test]
    public function emptySettingsLeaveEverythingToFluid(): void
    {
        $configuration = TemplateConfiguration::fromViewSettings([]);

        self::assertSame([], $configuration->templateRootPaths);
        self::assertSame([], $configuration->layoutRootPaths);
        self::assertSame([], $configuration->partialRootPaths);
        self::assertSame('', $configuration->templateFile);
    }

    #[Test]
    public function presetModeUsesTheFileAndPathsOfTheSelectedPreset(): void
    {
        $configuration = TemplateConfiguration::fromViewSettings([
            'templateType' => 'preset',
            'templatePreset' => 'default',
            'presets' => self::PRESETS,
            'templateRootPaths' => [0 => 'EXT:site/Templates'],
        ]);

        self::assertSame('EXT:pw_teaser/Resources/Private/Templates/Teaser/Index.html', $configuration->templateFile);
        self::assertSame(['EXT:pw_teaser/Resources/Private/Partials'], $configuration->partialRootPaths);
        self::assertSame(['EXT:pw_teaser/Resources/Private/Layouts'], $configuration->layoutRootPaths);
        self::assertSame(['EXT:site/Templates'], $configuration->templateRootPaths);
    }

    #[Test]
    public function presetWithoutPathsFallsBackToTheConfiguredPaths(): void
    {
        $configuration = TemplateConfiguration::fromViewSettings([
            'templateType' => 'preset',
            'templatePreset' => 'bare',
            'presets' => self::PRESETS,
            'partialRootPaths' => [10 => 'EXT:site/Partials'],
            'layoutRootPath' => 'EXT:site/Layouts',
        ]);

        self::assertSame('EXT:site/Bare.html', $configuration->templateFile);
        self::assertSame(['EXT:site/Partials'], $configuration->partialRootPaths);
        self::assertSame(['EXT:site/Layouts'], $configuration->layoutRootPaths);
    }

    #[Test]
    public function unknownPresetKeepsTheDefaultTemplateOfTheExtension(): void
    {
        $configuration = TemplateConfiguration::fromViewSettings([
            'templateType' => 'preset',
            'templatePreset' => 'missing',
            'presets' => self::PRESETS,
            'templateRootPath' => 'EXT:site/Templates',
            'templateRootFile' => 'EXT:site/Stale.html',
        ]);

        self::assertSame([], $configuration->templateRootPaths);
        self::assertSame('', $configuration->templateFile);
    }

    #[Test]
    public function fileModeUsesTheTemplateRootFile(): void
    {
        $configuration = TemplateConfiguration::fromViewSettings([
            'templateType' => 'file',
            'templateRootFile' => ' EXT:site/Teaser.html ',
            'partialRootPath' => 'EXT:site/Partials/',
        ]);

        self::assertSame('EXT:site/Teaser.html', $configuration->templateFile);
        self::assertSame(['EXT:site/Partials/'], $configuration->partialRootPaths);
        self::assertSame([], $configuration->layoutRootPaths);
    }

    #[Test]
    public function directoryModeUsesTheRootPaths(): void
    {
        $configuration = TemplateConfiguration::fromViewSettings([
            'templateType' => 'directory',
            'templateRootPath' => 'EXT:site/Templates/',
            'templateRootFile' => 'EXT:site/Ignored.html',
            'layoutRootPath' => 'EXT:site/Layouts/',
        ]);

        self::assertSame(['EXT:site/Templates/'], $configuration->templateRootPaths);
        self::assertSame(['EXT:site/Layouts/'], $configuration->layoutRootPaths);
        self::assertSame('', $configuration->templateFile);
    }

    #[Test]
    public function typoScriptPathListsWinOverTheSingleFlexFormPath(): void
    {
        $configuration = TemplateConfiguration::fromViewSettings([
            'templateType' => 'directory',
            'templateRootPaths' => [0 => 'EXT:base/Templates', 10 => 'EXT:site/Templates', 20 => '', 30 => null],
            'templateRootPath' => 'EXT:flexform/Templates',
        ]);

        self::assertSame(['EXT:base/Templates', 'EXT:site/Templates'], $configuration->templateRootPaths);
    }

    #[Test]
    public function emptyPathListsFallBackToTheSingleFlexFormPath(): void
    {
        $configuration = TemplateConfiguration::fromViewSettings([
            'templateType' => 'directory',
            'templateRootPaths' => [],
            'templateRootPath' => 'EXT:flexform/Templates',
        ]);

        self::assertSame(['EXT:flexform/Templates'], $configuration->templateRootPaths);
    }
}
