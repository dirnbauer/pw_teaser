<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'ext-pwteaser-wizard-icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pw_teaser/Resources/Public/Icons/Plugin.svg',
    ],
];
