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

    public function getAvailableTemplatePresets(array &$parameters): void
    {
        $config = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $presets = $config['plugin.']['tx_pwteaser.']['view.']['presets.'] ?? [];
        foreach ($presets as $key => $preset) {
            $parameters['items'][] = ['label' => $preset['label'], 'value' => rtrim((string)$key, '.')];
        }
    }
}
