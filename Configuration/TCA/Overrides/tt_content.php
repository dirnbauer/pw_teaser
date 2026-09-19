<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

$flexForm = 'FILE:EXT:pw_teaser/Configuration/FlexForms/flexform_teaser.xml';
$isTypo3V14 = (new Typo3Version())->getMajorVersion() >= 14;

$arguments = [
    'pw_teaser',
    'Pi1',
    'LLL:EXT:pw_teaser/Resources/Private/Language/locallang.xlf:newContentElementWizardTitle',
    'ext-pwteaser-wizard-icon',
    'default',
    'LLL:EXT:pw_teaser/Resources/Private/Language/locallang.xlf:newContentElementWizardDescription',
];
if ($isTypo3V14) {
    // TYPO3 14 registers the data structure and the plugin tab through registerPlugin() itself.
    $arguments[] = $flexForm;
}
$pluginSignature = ExtensionUtility::registerPlugin(...$arguments);

if (!$isTypo3V14) {
    // TYPO3 13 has no FlexForm argument and still needs the data structure in the "ds" map.
    ExtensionManagementUtility::addPiFlexFormValue('*', $flexForm, $pluginSignature);
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:plugin,pi_flexform,',
        $pluginSignature,
        'after:subheader'
    );
}
