<?php

declare(strict_types=1);

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 */

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'pw_teaser',
    'Pi1',
    [
        \PwTeaserTeam\PwTeaser\Controller\TeaserController::class => 'index',
    ]
);

$rootLineFields = GeneralUtility::trimExplode(
    ',',
    $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'],
    true
);
$rootLineFields[] = 'sorting';
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = implode(',', $rootLineFields);

/** @var IconRegistry $iconRegistry */
$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
$iconRegistry->registerIcon(
    'ext-pwteaser-wizard-icon',
    BitmapIconProvider::class,
    ['source' => 'EXT:pw_teaser/Resources/Public/Icons/Extension_x2.png']
);

ExtensionManagementUtility::addPageTSConfig('
    mod.wizards.newContentElement.wizardItems.plugins.elements.pwteaser {
        iconIdentifier = ext-pwteaser-wizard-icon
        title = LLL:EXT:pw_teaser/Resources/Private/Language/locallang.xlf:newContentElementWizardTitle
        description = LLL:EXT:pw_teaser/Resources/Private/Language/locallang.xlf:newContentElementWizardDescription
        tt_content_defValues {
            CType = list
            list_type = pwteaser_pi1
        }
    }
');
