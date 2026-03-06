<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/ext_localconf.php',
    ])
    ->withSkip([
        __DIR__ . '/Resources',
        __DIR__ . '/Tests',
    ])
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        Typo3LevelSetList::UP_TO_TYPO3_13,
        Typo3SetList::TYPO3_13,
    ])
    ->withImportNames();
