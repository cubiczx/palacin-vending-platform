<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Command;

use App\Application\Command\SelectProductCommand;
use App\Application\Command\SelectProductCommandHandler;
use App\Domain\Event\ProductVendedEvent;
use App\Domain\Exception\InsufficientFundsException;
use App\Domain\Model\Coin;
use App\Domain\Model\ProductSku;
use App\Domain\Service\ChangeCalculator;
use App\Tests\Support\InMemoryVendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class SelectProductCommandHandlerTest extends TestCase
{
    public function testVendsProductAndDispatchesProductVendedEvent(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::ONE_EURO);

        $repository = new InMemoryVendingMachineRepository();
        $repository->seed($machine);

        $eventDispatcher = new EventDispatcher();
        $capturedEvents = [];
        $eventDispatcher->addListener(
            ProductVendedEvent::class,
            static function (ProductVendedEvent $event) use (&$capturedEvents): void {
                $capturedEvents[] = $event;
            },
        );

        $clock = new MockClock('2026-08-24T10:00:00+00:00');

        $handler = new SelectProductCommandHandler(
            $repository,
            new ChangeCalculator(),
            $eventDispatcher,
            $clock,
        );

        $result = $handler(new SelectProductCommand('machine-01', ProductSku::WATER));

        self::assertSame(ProductSku::WATER, $result->product);
        self::assertSame([25 => 1, 10 => 1], $result->changeReturned);
        self::assertCount(1, $capturedEvents);
        self::assertSame(ProductSku::WATER, $capturedEvents[0]->product);
    }


    public function testDoesNotDispatchAnEventWhenThePurchaseFails(): void
    {
        $repository = new InMemoryVendingMachineRepository();
        $repository->seed(VendingMachineFixture::withDefaultCatalog());

        $eventDispatcher = new EventDispatcher();
        $dispatchCount = 0;
        $eventDispatcher->addListener(
            ProductVendedEvent::class,
            static function () use (&$dispatchCount): void {
                $dispatchCount++;
            },
        );

        $handler = new SelectProductCommandHandler(
            $repository,
            new ChangeCalculator(),
            $eventDispatcher,
            new MockClock(),
        );

        $this->expectException(InsufficientFundsException::class);

        try {
            $handler(new SelectProductCommand('machine-01', ProductSku::SODA));
        } finally {
            self::assertSame(0, $dispatchCount);
        }
    }
}
