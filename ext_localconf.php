<?php

declare(strict_types=1);

/*  | This extension is made with love for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2011-2022 Armin Vieweg <armin@v.ieweg.de>
 */

use PwTeaserTeam\PwTeaser\Controller\TeaserController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'pw_teaser',
    'Pi1',
    [
        TeaserController::class => 'index',
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Transitional: alias old list_type records to new CType until
// all installations have run the PwTeaserCTypeMigration wizard.
ExtensionManagementUtility::addTypoScript(
    'pw_teaser',
    'setup',
    'tt_content.list.20.pwteaser_pi1 =< tt_content.pwteaser_pi1',
    'defaultContentRendering'
);

