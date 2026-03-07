<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

$pluginSignature = ExtensionUtility::registerPlugin(
    'pw_teaser',
    'Pi1',
    'LLL:EXT:pw_teaser/Resources/Private/Language/locallang.xlf:newContentElementWizardTitle',
    'ext-pwteaser-wizard-icon',
    'default',
    'LLL:EXT:pw_teaser/Resources/Private/Language/locallang.xlf:newContentElementWizardDescription'
);
ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Configuration,pi_flexform,',
    $pluginSignature,
    'after:subheader'
);
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:pw_teaser/Configuration/FlexForms/flexform_teaser.xml',
    $pluginSignature
);
