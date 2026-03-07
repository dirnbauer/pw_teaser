<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\ViewHelpers\RemoveWhitespacesViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;

final class RemoveWhitespacesViewHelperTest extends TestCase
{
    #[Test]
    public function renderRemovesTabsNewlinesAndCarriageReturns(): void
    {
        $subject = new RemoveWhitespacesViewHelper();
        $subject->setRenderingContext(new RenderingContext());
        $subject->setRenderChildrenClosure(static fn(): string => "<div>\t\n\tHello\r\n</div>");

        self::assertSame('<div>Hello</div>', $subject->render());
    }

    #[Test]
    public function renderHandlesNullChildren(): void
    {
        $subject = new RemoveWhitespacesViewHelper();
        $subject->setRenderingContext(new RenderingContext());
        $subject->setRenderChildrenClosure(static fn(): ?string => null);

        self::assertSame('', $subject->render());
    }
}
