<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\View\TemplatePaths;

/**
 * Resolves the three template modes of the plugin (preset, file, directory)
 * into Fluid template paths.
 */
final readonly class TemplateConfiguration
{
    /**
     * @param list<string> $templateRootPaths
     * @param list<string> $layoutRootPaths
     * @param list<string> $partialRootPaths
     * @param string $templateFile A single template file; '' lets Fluid resolve Teaser/Index.html
     */
    public function __construct(
        public array $templateRootPaths = [],
        public array $layoutRootPaths = [],
        public array $partialRootPaths = [],
        public string $templateFile = '',
    ) {}

    /**
     * @param array<string, mixed> $viewSettings Rendered plugin.tx_pwteaser.view (FlexForm merged over TypoScript)
     */
    public static function fromViewSettings(array $viewSettings): self
    {
        $templateRootPaths = self::paths($viewSettings, 'templateRootPaths', 'templateRootPath');
        $layoutRootPaths = self::paths($viewSettings, 'layoutRootPaths', 'layoutRootPath');
        $partialRootPaths = self::paths($viewSettings, 'partialRootPaths', 'partialRootPath');
        $templateFile = '';

        switch (self::string($viewSettings, 'templateType')) {
            case 'file':
                $templateFile = self::string($viewSettings, 'templateRootFile');
                break;
            case 'preset':
                $presets = is_array($viewSettings['presets'] ?? null) ? $viewSettings['presets'] : [];
                $preset = $presets[self::string($viewSettings, 'templatePreset')] ?? null;
                if (!is_array($preset)) {
                    // Unknown preset: keep Fluid's default template of the extension
                    $templateRootPaths = [];
                    break;
                }
                $templateFile = self::string($preset, 'templateRootFile');
                $layoutRootPaths = self::paths($preset, 'layoutRootPaths') ?: $layoutRootPaths;
                $partialRootPaths = self::paths($preset, 'partialRootPaths') ?: $partialRootPaths;
                break;
        }

        return new self($templateRootPaths, $layoutRootPaths, $partialRootPaths, $templateFile);
    }

    /**
     * @throws \RuntimeException when a configured directory does not exist
     */
    public function applyTo(TemplatePaths $templatePaths): void
    {
        if ($this->templateRootPaths !== []) {
            $this->assertDirectoryExists($this->templateRootPaths[0], 'Template');
            $templatePaths->setTemplateRootPaths($this->templateRootPaths);
        }
        if ($this->layoutRootPaths !== []) {
            $this->assertDirectoryExists($this->layoutRootPaths[0], 'Layout');
            $templatePaths->setLayoutRootPaths($this->layoutRootPaths);
        }
        if ($this->partialRootPaths !== []) {
            $this->assertDirectoryExists($this->partialRootPaths[0], 'Partial');
            $templatePaths->setPartialRootPaths($this->partialRootPaths);
        }
        if ($this->templateFile !== '') {
            $file = GeneralUtility::getFileAbsFileName($this->templateFile);
            if ($file !== '' && file_exists($file)) {
                $templatePaths->setTemplatePathAndFilename($file);
            }
        }
    }

    private function assertDirectoryExists(string $path, string $kind): void
    {
        $absolutePath = GeneralUtility::getFileAbsFileName($path);
        if ($absolutePath === '' || !is_dir($absolutePath)) {
            throw new \RuntimeException($kind . ' folder "' . $path . '" not found!', 1758200000);
        }
    }

    /**
     * Reads a list of paths from the TypoScript plural key (e.g. templateRootPaths.10 = ...)
     * or, when missing, the single FlexForm path.
     *
     * @param array<string, mixed> $settings
     * @return list<string>
     */
    private static function paths(array $settings, string $pluralKey, ?string $singularKey = null): array
    {
        $paths = $settings[$pluralKey] ?? null;
        if (is_array($paths)) {
            $paths = array_values(array_filter($paths, static fn(mixed $path): bool => is_string($path) && $path !== ''));
            if ($paths !== []) {
                return $paths;
            }
        }
        $singlePath = $singularKey !== null ? self::string($settings, $singularKey) : '';
        return $singlePath !== '' ? [$singlePath] : [];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function string(array $settings, string $key): string
    {
        $value = $settings[$key] ?? null;
        return is_scalar($value) ? trim((string)$value) : '';
    }
}
