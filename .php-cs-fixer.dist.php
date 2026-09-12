<?php

declare(strict_types=1);

use TYPO3\CodingStandards\CsFixerConfig;

$config = CsFixerConfig::create();
$config->getFinder()
    ->in(__DIR__ . '/Classes')
    ->in(__DIR__ . '/Configuration')
    ->in(__DIR__ . '/Tests')
    ->append([
        __DIR__ . '/ext_emconf.php',
        __DIR__ . '/ext_localconf.php',
        __DIR__ . '/fractor.php',
        __DIR__ . '/rector.php',
    ]);

return $config;
