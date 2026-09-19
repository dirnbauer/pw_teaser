<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\UserFunction;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 */
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Fills the "Template preset" dropdown of the plugin with the presets
 * configured in plugin.tx_pwteaser.view.presets.
 */
final readonly class ItemsProcFunc
{
    public function __construct(private ConfigurationManagerInterface $configurationManager) {}

    /**
     * @param array<string, mixed> $parameters
     */
    public function getAvailableTemplatePresets(array &$parameters): void
    {
        $typoScript = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $presets = $typoScript['plugin.']['tx_pwteaser.']['view.']['presets.'] ?? null;

        if (!isset($parameters['items']) || !is_array($parameters['items'])) {
            $parameters['items'] = [];
        }
        foreach (is_array($presets) ? $presets : [] as $key => $preset) {
            if (!is_array($preset)) {
                continue;
            }
            $key = rtrim((string)$key, '.');
            $label = $preset['label'] ?? null;
            $parameters['items'][] = [
                'label' => is_string($label) && $label !== '' ? $label : $key,
                'value' => $key,
            ];
        }
    }
}
