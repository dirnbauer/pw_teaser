<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Utility;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 */
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class provides some methods to prepare and render given extension settings
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class Settings
{
    private ?ContentObjectRenderer $contentObject = null;

    public function __construct(private readonly ConfigurationManagerInterface $configurationManager) {}

    public function setContentObject(?ContentObjectRenderer $contentObject): void
    {
        $this->contentObject = $contentObject;
    }

    /**
     * Renders a given typoscript configuration and returns the whole array with
     * calculated values.
     *
     * @param array<string, mixed> $settings the typoscript configuration array
     * @param string $section
     * @return array<string, mixed> the configuration array with the rendered typoscript
     */
    public function renderConfigurationArray(array $settings, string $section = 'settings.'): array
    {
        $settings = $this->enhanceSettingsWithTypoScript($this->makeConfigurationArrayRenderable($settings), $section);
        $result = [];

        foreach ($settings as $key => $value) {
            if (str_ends_with((string)$key, '.')) {
                $keyWithoutDot = substr((string)$key, 0, -1);
                if (array_key_exists($keyWithoutDot, $settings)) {
                    if ($this->contentObject === null) {
                        continue;
                    }
                    $name = $settings[$keyWithoutDot] ?? '';
                    if (is_string($name) && is_array($value)) {
                        $result[$keyWithoutDot] = $this->contentObject->cObjGetSingle($name, $value);
                    }
                } elseif (is_array($value)) {
                    /** @var array<string, mixed> $value */
                    $result[$keyWithoutDot] = $this->renderConfigurationArray($value);
                }
            } else {
                if (!array_key_exists($key . '.', $settings)) {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Overwrite flexform values with typoscript if flexform value is empty and typoscript value exists.
     *
     * @param array<string, mixed> $settings Settings from flexform
     * @param string $section
     * @param string $extKey
     * @return array<string, mixed> enhanced settings
     */
    protected function enhanceSettingsWithTypoScript(
        array $settings,
        string $section = 'settings.',
        string $extKey = 'tx_pwteaser'
    ): array {
        $typoscript = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $typoscript = $typoscript['plugin.'][$extKey . '.'][$section] ?? [];
        foreach ($settings as $key => $setting) {
            if ($setting === '' && is_array($typoscript) && array_key_exists($key, $typoscript)) {
                $settings[$key] = $typoscript[$key];
            }
        }
        return $settings;
    }

    /**
     * Formats a given array with typoscript syntax, recursively. After the
     * transformation it can be rendered with cObjGetSingle.
     *
     * Example:
     * Before: $array['level1']['level2']['finalLevel'] = 'hello kitty'
     * After:  $array['level1.']['level2.']['finalLevel'] = 'hello kitty'
     *           $array['level1'] = 'TEXT'
     *
     * @param array<string, mixed> $configuration settings array to make renderable
     * @return array<string, mixed> the renderable settings
     */
    protected function makeConfigurationArrayRenderable(array $configuration): array
    {
        $dottedConfiguration = [];
        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                if (array_key_exists('_typoScriptNodeValue', $value)) {
                    $dottedConfiguration[$key] = $value['_typoScriptNodeValue'];
                }
                $dottedConfiguration[$key . '.'] = $this->makeConfigurationArrayRenderable($value);
            } else {
                $dottedConfiguration[$key] = $value;
            }
        }
        return $dottedConfiguration;
    }
}
