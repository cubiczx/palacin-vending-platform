<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Command;

use App\Application\Command\SetChangeInventoryCommand;
use App\Application\Command\SetChangeInventoryCommandHandler;
use App\Domain\Model\Coin;
use App\Tests\Support\InMemoryVendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use PHPUnit\Framework\TestCase;

final class SetChangeInventoryCommandHandlerTest extends TestCase
{
    public function testReplacesTheEntireChangeInventory(): void
    {
        $repository = new InMemoryVendingMachineRepository();
        $repository->seed(VendingMachineFixture::withDefaultCatalog());
        $handler = new SetChangeInventoryCommandHandler($repository);

        $handler(new SetChangeInventoryCommand('machine-01', [5 => 0, 10 => 0, 25 => 0, 100 => 1]));

        $machine = $repository->find('machine-01');
        self::assertNotNull($machine);

        $inventory = $machine->changeInventory();
        self::assertSame(1, $inventory->quantityOf(Coin::ONE_EURO));
        self::assertSame(0, $inventory->quantityOf(Coin::TWENTY_FIVE_CENTS));
    }

    public function testThrowsWhenMachineDoesNotExist(): void
    {
        $handler = new SetChangeInventoryCommandHandler(new InMemoryVendingMachineRepository());

        $this->expectException(\RuntimeException::class);

        $handler(new SetChangeInventoryCommand('unknown-machine', [100 => 5]));
    }
}
