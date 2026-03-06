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

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    $pluginSignature,
    'FILE:EXT:' . 'pw_teaser' . '/Configuration/FlexForms/flexform_teaser.xml'
);
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:' . 'pw_teaser' . '/Configuration/FlexForms/flexform_teaser.xml',
    $pluginSignature
);
ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,pi_flexform',
    $pluginSignature,
    'after:palette:headers'
);
