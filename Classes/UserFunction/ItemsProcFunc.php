<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\UserFunction;

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 */
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

final readonly class ItemsProcFunc
{
    public function __construct(private ConfigurationManagerInterface $configurationManager) {}

    /**
     * @param array<string, mixed> &$parameters
     */
    public function getAvailableTemplatePresets(array &$parameters): void
    {
        $config = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $pluginConfig = is_array($config['plugin.'] ?? null) ? $config['plugin.'] : [];
        $extConfig = is_array($pluginConfig['tx_pwteaser.'] ?? null) ? $pluginConfig['tx_pwteaser.'] : [];
        $viewConfig = is_array($extConfig['view.'] ?? null) ? $extConfig['view.'] : [];
        $presets = is_array($viewConfig['presets.'] ?? null) ? $viewConfig['presets.'] : [];

        if (!isset($parameters['items']) || !is_array($parameters['items'])) {
            $parameters['items'] = [];
        }
        foreach ($presets as $key => $preset) {
            if (!is_array($preset)) {
                continue;
            }
            $label = is_string($preset['label'] ?? null) ? $preset['label'] : (string)$key;
            $parameters['items'][] = ['label' => $label, 'value' => rtrim((string)$key, '.')];
        }
    }
}
