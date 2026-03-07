<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\ViewHelpers\StripTagsViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;

final class StripTagsViewHelperTest extends TestCase
{
    #[Test]
    public function renderStripsHtmlFromArgument(): void
    {
        $subject = new StripTagsViewHelper();
        $subject->setRenderingContext(new RenderingContext());
        $subject->initializeArguments();
        $subject->setArguments(['string' => '<p>Hello <strong>World</strong></p>']);

        self::assertSame('Hello World', $subject->render());
    }

    #[Test]
    public function renderStripsHtmlFromChildContent(): void
    {
        $subject = new StripTagsViewHelper();
        $subject->setRenderingContext(new RenderingContext());
        $subject->initializeArguments();
        $subject->setArguments(['string' => null]);
        $subject->setRenderChildrenClosure(static fn(): string => '<div>&amp; foo</div>');

        self::assertSame('& foo', $subject->render());
    }

    #[Test]
    public function renderReturnsEmptyStringForNullChildContent(): void
    {
        $subject = new StripTagsViewHelper();
        $subject->setRenderingContext(new RenderingContext());
        $subject->initializeArguments();
        $subject->setArguments(['string' => null]);
        $subject->setRenderChildrenClosure(static fn(): string => '');

        self::assertSame('', $subject->render());
    }
}
