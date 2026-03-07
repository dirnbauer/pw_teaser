<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Domain\Model\Content;

final class ContentTest extends TestCase
{
    #[Test]
    public function defaultValuesAreInitialized(): void
    {
        $subject = new Content();

        self::assertSame('', $subject->getCtype());
        self::assertSame(0, $subject->getColPos());
        self::assertSame('', $subject->getHeader());
        self::assertSame('', $subject->getBodytext());
    }

    #[Test]
    public function settersAndGettersWorkCorrectly(): void
    {
        $subject = new Content();
        $subject->setCtype('textmedia');
        $subject->setColPos(2);
        $subject->setHeader('Test Header');
        $subject->setBodytext('Test body');

        self::assertSame('textmedia', $subject->getCtype());
        self::assertSame(2, $subject->getColPos());
        self::assertSame('Test Header', $subject->getHeader());
        self::assertSame('Test body', $subject->getBodytext());
    }

    #[Test]
    public function contentRowIsNullByDefault(): void
    {
        $subject = new Content();

        self::assertNull($subject->getContentRow());
    }
}
