<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Domain\Model\Content;
use PwTeaserTeam\PwTeaser\ViewHelpers\GetContentViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;

final class GetContentViewHelperTest extends TestCase
{
    #[Test]
    public function renderReturnsEmptyStringForNullContents(): void
    {
        $subject = $this->createSubject();
        $subject->setArguments([
            'contents' => null,
            'as' => 'content',
            'colPos' => 0,
            'cType' => null,
            'index' => null,
        ]);

        self::assertSame('', $subject->render());
    }

    #[Test]
    public function renderSkipsInvalidNonContentEntries(): void
    {
        $subject = $this->createSubject();
        $subject->setArguments([
            'contents' => [
                'invalid',
                $this->createContent('image', 0, 'Valid image'),
                42,
            ],
            'as' => 'content',
            'colPos' => 0,
            'cType' => 'image',
            'index' => null,
        ]);

        self::assertSame('Valid image', $subject->render());
    }

    #[Test]
    public function renderFiltersContentsByTypeAndColumnPosition(): void
    {
        $subject = $this->createSubject();
        $subject->setArguments([
            'contents' => [
                $this->createContent('text', 0, 'Text'),
                $this->createContent('image', 1, 'Image in other column'),
                $this->createContent('image', 0, 'Matching image'),
            ],
            'as' => 'content',
            'colPos' => 0,
            'cType' => 'image',
            'index' => null,
        ]);

        self::assertSame('Matching image', $subject->render());
    }

    #[Test]
    public function renderAppliesIndexAfterFilteringByTypeAndColumnPosition(): void
    {
        $subject = $this->createSubject();
        $subject->setArguments([
            'contents' => [
                $this->createContent('image', 1, 'Image in other column'),
                $this->createContent('image', 0, 'First matching image'),
                $this->createContent('image', 0, 'Second matching image'),
            ],
            'as' => 'content',
            'colPos' => 0,
            'cType' => 'image',
            'index' => 0,
        ]);

        self::assertSame('First matching image', $subject->render());
    }

    private function createSubject(): GetContentViewHelper
    {
        $renderingContext = new RenderingContext();
        $subject = new GetContentViewHelper();
        $subject->initializeArguments();
        $subject->setRenderingContext($renderingContext);
        $subject->setRenderChildrenClosure(static function () use ($renderingContext): string {
            $content = $renderingContext->getVariableProvider()->get('content');

            return $content instanceof Content ? (string)$content->getHeader() : '';
        });

        return $subject;
    }

    private function createContent(string $ctype, int $colPos, string $header): Content
    {
        $content = new Content();
        $content->setCtype($ctype);
        $content->setColPos($colPos);
        $content->setHeader($header);

        return $content;
    }
}
