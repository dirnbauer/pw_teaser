<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Settings;

/**
 * Where the teaser takes its pages from (FlexForm setting "source").
 */
enum TeaserSource: string
{
    case ThisChildren = 'thisChildren';
    case ThisChildrenRecursively = 'thisChildrenRecursively';
    case Custom = 'custom';
    case CustomChildren = 'customChildren';
    case CustomChildrenRecursively = 'customChildrenRecursively';

    public function isRecursive(): bool
    {
        return $this === self::ThisChildrenRecursively || $this === self::CustomChildrenRecursively;
    }

    public function usesCustomPages(): bool
    {
        return $this !== self::ThisChildren && $this !== self::ThisChildrenRecursively;
    }
}
