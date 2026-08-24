<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Command;

use App\Application\Command\RestockProductCommand;
use App\Application\Command\RestockProductCommandHandler;
use App\Domain\Model\ProductSku;
use App\Tests\Support\InMemoryVendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use PHPUnit\Framework\TestCase;

final class RestockProductCommandHandlerTest extends TestCase
{
    public function testRestocksProductAndPersistsTheChange(): void
    {
        $repository = new InMemoryVendingMachineRepository();
        $repository->seed(VendingMachineFixture::withDefaultCatalog(sodaStock: 2));
        $handler = new RestockProductCommandHandler($repository);

        $handler(new RestockProductCommand('machine-01', ProductSku::SODA, 8));

        $soda = current(array_filter(
            $repository->find('machine-01')->products(),
            static fn ($p) => $p->sku() === ProductSku::SODA,
        ));
        self::assertSame(10, $soda->stock());
    }

    public function testThrowsWhenMachineDoesNotExist(): void
    {
        $handler = new RestockProductCommandHandler(new InMemoryVendingMachineRepository());

        $this->expectException(\RuntimeException::class);

        $handler(new RestockProductCommand('unknown-machine', ProductSku::SODA, 5));
    }

    public function testThrowsWhenProductDoesNotExistInTheMachinesCatalog(): void
    {
        $machine = \App\Domain\Model\VendingMachine::create(
            id: 'machine-01',
            products: [
                new \App\Domain\Model\Product(ProductSku::SODA, 'Soda', \App\Domain\Model\Money::fromCents(150), 5),
            ],
            changeInventory: VendingMachineFixture::plentifulChange(),
        );
        $repository = new InMemoryVendingMachineRepository();
        $repository->seed($machine);
        $handler = new RestockProductCommandHandler($repository);

        $this->expectException(\App\Domain\Exception\ProductNotFoundException::class);

        $handler(new RestockProductCommand('machine-01', ProductSku::WATER, 5));
    }
}
