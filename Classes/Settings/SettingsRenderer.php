<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Settings;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Turns the Extbase settings of the plugin into plain values:
 *
 * - empty FlexForm values fall back to the TypoScript value of the same key
 *   ("[Defined by TypoScript]" in the plugin form)
 * - TypoScript content objects (e.g. `customPages = CONTENT` with `customPages { ... }`)
 *   are rendered with the current ContentObjectRenderer
 */
final readonly class SettingsRenderer
{
    public function __construct(private ConfigurationManagerInterface $configurationManager) {}

    /**
     * @param array<string, mixed> $settings Extbase settings (FlexForm merged over TypoScript)
     * @param string $section TypoScript section below plugin.tx_pwteaser, e.g. 'settings.' or 'view.'
     * @return array<string, mixed>
     */
    public function render(array $settings, string $section, ?ContentObjectRenderer $contentObject): array
    {
        $typoScript = $this->withTypoScriptDefaults($this->toTypoScriptArray($settings), $section);
        return $this->renderTypoScriptArray($typoScript, $contentObject);
    }

    /**
     * @param array<string, mixed> $typoScript Settings in TypoScript notation (`key` and `key.`)
     * @return array<string, mixed>
     */
    private function renderTypoScriptArray(array $typoScript, ?ContentObjectRenderer $contentObject): array
    {
        $result = [];
        foreach ($typoScript as $key => $value) {
            if (!str_ends_with($key, '.')) {
                if (!array_key_exists($key . '.', $typoScript)) {
                    $result[$key] = $value;
                }
                continue;
            }

            $plainKey = substr($key, 0, -1);
            if (!is_array($value)) {
                continue;
            }
            if (!array_key_exists($plainKey, $typoScript)) {
                $result[$plainKey] = $this->renderTypoScriptArray($value, $contentObject);
                continue;
            }
            $contentObjectName = $typoScript[$plainKey];
            if ($contentObject !== null && is_string($contentObjectName)) {
                $result[$plainKey] = $contentObject->cObjGetSingle($contentObjectName, $value);
            }
        }
        return $result;
    }

    /**
     * Replaces empty values with the TypoScript value (and its `key.` configuration) of the same key.
     *
     * @param array<string, mixed> $typoScript
     * @return array<string, mixed>
     */
    private function withTypoScriptDefaults(array $typoScript, string $section): array
    {
        $fullTypoScript = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $defaults = $fullTypoScript['plugin.']['tx_pwteaser.'][$section] ?? null;
        if (!is_array($defaults)) {
            return $typoScript;
        }

        foreach ($typoScript as $key => $value) {
            if ($value !== '' || !array_key_exists($key, $defaults)) {
                continue;
            }
            $typoScript[$key] = $defaults[$key];
            if (isset($defaults[$key . '.'])) {
                $typoScript[$key . '.'] = $defaults[$key . '.'];
            }
        }
        return $typoScript;
    }

    /**
     * Converts the Extbase array notation back to TypoScript notation, so
     * content objects can be rendered with cObjGetSingle():
     *
     * Before: ['customPages' => ['_typoScriptNodeValue' => 'CONTENT', 'table' => 'pages']]
     * After:  ['customPages' => 'CONTENT', 'customPages.' => ['table' => 'pages', ...]]
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function toTypoScriptArray(array $settings): array
    {
        $typoScript = [];
        foreach ($settings as $key => $value) {
            if (!is_array($value)) {
                $typoScript[$key] = $value;
                continue;
            }
            if (array_key_exists('_typoScriptNodeValue', $value)) {
                $typoScript[$key] = $value['_typoScriptNodeValue'];
            }
            $typoScript[$key . '.'] = $this->toTypoScriptArray($value);
        }
        return $typoScript;
    }
}
