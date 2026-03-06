<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use ReflectionProperty;

final class PageTest extends TestCase
{
    #[Test]
    public function getKeywordsReturnsEmptyArrayForMissingKeywords(): void
    {
        $subject = new Page();
        $property = new ReflectionProperty($subject, 'keywords');
        $property->setAccessible(true);
        $property->setValue($subject, null);

        self::assertSame([], $subject->getKeywords());
    }

    #[Test]
    public function getKeywordsSplitsCommaSeparatedKeywords(): void
    {
        $subject = new Page();
        $subject->setKeywords('foo, bar ,baz');

        self::assertSame(['foo', 'bar', 'baz'], $subject->getKeywords());
    }
}
