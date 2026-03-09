<?php

declare(strict_types=1);

namespace PwTeaserTeam\PwTeaser\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PwTeaserTeam\PwTeaser\Controller\TeaserController;
use PwTeaserTeam\PwTeaser\Domain\Model\Page;
use PwTeaserTeam\PwTeaser\Event\ModifyPagesEvent;

final class ModifyPagesEventTest extends TestCase
{
    #[Test]
    public function pagesCanBeReadAndReplaced(): void
    {
        $controller = $this->createMock(TeaserController::class);
        $event = new ModifyPagesEvent(['page1', 'page2'], $controller);

        self::assertSame(['page1', 'page2'], $event->getPages());

        $event->setPages(['page3']);
        self::assertSame(['page3'], $event->getPages());
    }

    #[Test]
    public function getTeaserControllerReturnsInjectedController(): void
    {
        $controller = $this->createMock(TeaserController::class);
        $event = new ModifyPagesEvent([], $controller);

        self::assertSame($controller, $event->getTeaserController());
    }

    #[Test]
    public function eventCanBeConstructedWithEmptyPages(): void
    {
        $controller = $this->createMock(TeaserController::class);
        $event = new ModifyPagesEvent([], $controller);

        self::assertSame([], $event->getPages());
    }

    #[Test]
    public function pagesCanBeFilteredViaEventListener(): void
    {
        $controller = $this->createMock(TeaserController::class);

        $page1 = new Page();
        $page1->setTitle('Visible');
        $page1->setDoktype(1);

        $page2 = new Page();
        $page2->setTitle('Shortcut');
        $page2->setDoktype(4);

        $event = new ModifyPagesEvent([$page1, $page2], $controller);

        $filtered = array_values(array_filter(
            $event->getPages(),
            static fn(Page $p) => $p->getDoktype() === 1
        ));
        $event->setPages($filtered);

        self::assertCount(1, $event->getPages());
        self::assertSame('Visible', $event->getPages()[0]->getTitle());
    }

    #[Test]
    public function pagesCanBeEnrichedViaEventListener(): void
    {
        $controller = $this->createMock(TeaserController::class);

        $page = new Page();
        $page->setTitle('Original');

        $event = new ModifyPagesEvent([$page], $controller);

        foreach ($event->getPages() as $p) {
            if ($p instanceof Page) {
                $p->setCustomAttribute('enriched', true);
            }
        }

        self::assertTrue($event->getPages()[0]->getCustomAttribute('enriched'));
    }
}
