<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Domain\Repository\CategoryMode;

final class CategoryModeTest extends TestCase
{
    #[Test]
    public function valuesMatchTheFlexFormOptions(): void
    {
        self::assertSame(CategoryMode::Or, CategoryMode::from(1));
        self::assertSame(CategoryMode::And, CategoryMode::from(2));
        self::assertSame(CategoryMode::OrNot, CategoryMode::from(3));
        self::assertSame(CategoryMode::AndNot, CategoryMode::from(4));
    }

    #[Test]
    public function andAndNegationFlagsFollowTheMode(): void
    {
        self::assertFalse(CategoryMode::Or->isAnd());
        self::assertFalse(CategoryMode::Or->isNegated());
        self::assertTrue(CategoryMode::And->isAnd());
        self::assertFalse(CategoryMode::And->isNegated());
        self::assertFalse(CategoryMode::OrNot->isAnd());
        self::assertTrue(CategoryMode::OrNot->isNegated());
        self::assertTrue(CategoryMode::AndNot->isAnd());
        self::assertTrue(CategoryMode::AndNot->isNegated());
    }
}
