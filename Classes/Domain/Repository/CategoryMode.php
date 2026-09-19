<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Domain\Repository;

/**
 * How the selected categories constrain the page result (FlexForm setting "categoryMode").
 */
enum CategoryMode: int
{
    case Or = 1;
    case And = 2;
    case OrNot = 3;
    case AndNot = 4;

    public function isAnd(): bool
    {
        return $this === self::And || $this === self::AndNot;
    }

    public function isNegated(): bool
    {
        return $this === self::OrNot || $this === self::AndNot;
    }
}
