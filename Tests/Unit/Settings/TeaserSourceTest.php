<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Settings;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Settings\TeaserSource;

final class TeaserSourceTest extends TestCase
{
    #[Test]
    public function recursiveSourcesAreDetected(): void
    {
        self::assertTrue(TeaserSource::ThisChildrenRecursively->isRecursive());
        self::assertTrue(TeaserSource::CustomChildrenRecursively->isRecursive());
        self::assertFalse(TeaserSource::ThisChildren->isRecursive());
        self::assertFalse(TeaserSource::Custom->isRecursive());
        self::assertFalse(TeaserSource::CustomChildren->isRecursive());
    }

    #[Test]
    public function customSourcesUseTheSelectedPages(): void
    {
        self::assertTrue(TeaserSource::Custom->usesCustomPages());
        self::assertTrue(TeaserSource::CustomChildren->usesCustomPages());
        self::assertTrue(TeaserSource::CustomChildrenRecursively->usesCustomPages());
        self::assertFalse(TeaserSource::ThisChildren->usesCustomPages());
        self::assertFalse(TeaserSource::ThisChildrenRecursively->usesCustomPages());
    }
}
