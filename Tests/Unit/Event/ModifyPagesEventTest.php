<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Controller\TeaserController;
use PwTeaserTeam\PwTeaser\Event\ModifyPagesEvent;

final class ModifyPagesEventTest extends TestCase
{
    #[Test]
    public function pagesCanBeReadAndReplaced(): void
    {
        $controller = $this->getMockBuilder(TeaserController::class)
            ->disableOriginalConstructor()
            ->getMock();

        $initialPages = [['uid' => 1]];
        $updatedPages = [['uid' => 2]];

        $subject = new ModifyPagesEvent($initialPages, $controller);
        $subject->setPages($updatedPages);

        self::assertSame($updatedPages, $subject->getPages());
        self::assertSame($controller, $subject->getTeaserController());
    }
}
