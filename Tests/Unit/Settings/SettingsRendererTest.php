<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Settings;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Settings\SettingsRenderer;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class SettingsRendererTest extends TestCase
{
    /**
     * @param array<string, mixed> $typoScript plugin.tx_pwteaser.settings in TypoScript notation
     */
    private function createSubject(array $typoScript = [], string $section = 'settings.'): SettingsRenderer
    {
        $configurationManager = self::createStub(ConfigurationManagerInterface::class);
        $configurationManager->method('getConfiguration')
            ->willReturn($typoScript === [] ? [] : ['plugin.' => ['tx_pwteaser.' => [$section => $typoScript]]]);

        return new SettingsRenderer($configurationManager);
    }

    #[Test]
    public function plainValuesAreReturnedUnchanged(): void
    {
        $result = $this->createSubject()->render(['foo' => 'bar', 'baz' => '42'], 'settings.', null);

        self::assertSame(['foo' => 'bar', 'baz' => '42'], $result);
    }

    #[Test]
    public function emptyValuesFallBackToTypoScript(): void
    {
        $subject = $this->createSubject(['limit' => '5', 'source' => 'custom']);

        $result = $subject->render(['limit' => '', 'source' => 'thisChildren'], 'settings.', null);

        self::assertSame(['limit' => '5', 'source' => 'thisChildren'], $result);
    }

    #[Test]
    public function typoScriptFallbackRendersContentObjects(): void
    {
        $subject = $this->createSubject([
            'customPages' => 'TEXT',
            'customPages.' => ['value' => '1,2,3'],
        ]);
        $contentObject = $this->createMock(ContentObjectRenderer::class);
        $contentObject->expects($this->once())
            ->method('cObjGetSingle')
            ->with('TEXT', ['value' => '1,2,3'])
            ->willReturn('1,2,3');

        $result = $subject->render(['customPages' => ''], 'settings.', $contentObject);

        self::assertSame(['customPages' => '1,2,3'], $result);
    }

    #[Test]
    public function typoScriptFallbackIsReadFromTheGivenSection(): void
    {
        $subject = $this->createSubject(['templateType' => 'file'], 'view.');

        self::assertSame(['templateType' => 'file'], $subject->render(['templateType' => ''], 'view.', null));
        self::assertSame(['templateType' => ''], $subject->render(['templateType' => ''], 'settings.', null));
    }

    #[Test]
    public function contentObjectsInExtbaseNotationAreRendered(): void
    {
        $contentObject = $this->createMock(ContentObjectRenderer::class);
        $contentObject->expects($this->once())
            ->method('cObjGetSingle')
            ->with('TEXT', ['_typoScriptNodeValue' => 'TEXT', 'value' => 'Hello'])
            ->willReturn('Hello');

        $result = $this->createSubject()->render([
            'title' => ['_typoScriptNodeValue' => 'TEXT', 'value' => 'Hello'],
            'plain' => 'kept',
        ], 'settings.', $contentObject);

        self::assertSame(['title' => 'Hello', 'plain' => 'kept'], $result);
    }

    #[Test]
    public function contentObjectsAreDroppedWithoutContentObjectRenderer(): void
    {
        $result = $this->createSubject()->render([
            'title' => ['_typoScriptNodeValue' => 'TEXT', 'value' => 'Hello'],
        ], 'settings.', null);

        self::assertArrayNotHasKey('title', $result);
    }

    #[Test]
    public function nestedArraysAreKeptAndNotMergedWithTypoScriptDefaults(): void
    {
        $subject = $this->createSubject(['label' => 'from settings']);

        $result = $subject->render([
            'presets' => [
                'default' => ['label' => '', 'templateRootFile' => 'EXT:foo/Default.html'],
            ],
        ], 'settings.', null);

        self::assertSame([
            'presets' => [
                'default' => ['label' => '', 'templateRootFile' => 'EXT:foo/Default.html'],
            ],
        ], $result);
    }
}
