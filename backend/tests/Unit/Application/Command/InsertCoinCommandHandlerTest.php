<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Command;

use App\Application\Command\InsertCoinCommand;
use App\Application\Command\InsertCoinCommandHandler;
use App\Domain\Model\Coin;
use App\Tests\Support\InMemoryVendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use PHPUnit\Framework\TestCase;

final class InsertCoinCommandHandlerTest extends TestCase
{
    public function testInsertsCoinAndReturnsUpdatedBalance(): void
    {
        $repository = new InMemoryVendingMachineRepository();
        $repository->seed(VendingMachineFixture::withDefaultCatalog());
        $handler = new InsertCoinCommandHandler($repository);

        $balance = $handler(new InsertCoinCommand('machine-01', Coin::TWENTY_FIVE_CENTS));

        $machine = $repository->find('machine-01');
        self::assertNotNull($machine);
        self::assertSame(25, $balance->cents());
        self::assertSame(25, $machine->insertedAmount()->cents());
    }

    public function testThrowsWhenMachineDoesNotExist(): void
    {
        $handler = new InsertCoinCommandHandler(new InMemoryVendingMachineRepository());

        $this->expectException(\RuntimeException::class);

        $handler(new InsertCoinCommand('unknown-machine', Coin::TEN_CENTS));
    }
}
