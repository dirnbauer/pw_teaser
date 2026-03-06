<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\ViewHelpers\StripTagsViewHelper;

final class StripTagsViewHelperTest extends TestCase
{
    #[Test]
    public function renderStripsHtmlFromProvidedString(): void
    {
        $subject = new StripTagsViewHelper();

        self::assertSame('Hello World', trim($subject->render('<p>Hello <strong>World</strong></p>')));
    }
}
