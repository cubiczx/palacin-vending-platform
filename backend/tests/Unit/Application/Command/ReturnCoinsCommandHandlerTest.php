<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Command;

use App\Application\Command\ReturnCoinsCommand;
use App\Application\Command\ReturnCoinsCommandHandler;
use App\Domain\Model\Coin;
use App\Tests\Support\InMemoryVendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use PHPUnit\Framework\TestCase;

final class ReturnCoinsCommandHandlerTest extends TestCase
{
    public function testReturnsAllInsertedCoinsAndResetsBalance(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::TEN_CENTS);
        $machine->insertCoin(Coin::TEN_CENTS);

        $repository = new InMemoryVendingMachineRepository();
        $repository->seed($machine);
        $handler = new ReturnCoinsCommandHandler($repository);

        $returned = $handler(new ReturnCoinsCommand('machine-01'));

        $machine = $repository->find('machine-01');
        self::assertNotNull($machine);
        self::assertSame([10 => 2], $returned);
        self::assertTrue($machine->insertedAmount()->isZero());
    }
}
