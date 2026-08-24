<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Money;
use App\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class InsertCoinCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $machines,
    ) {
    }

    public function __invoke(InsertCoinCommand $command): Money
    {
        $machine = $this->machines->find($command->machineId)
            ?? throw new \RuntimeException("Machine \"{$command->machineId}\" not found.");

        $machine->insertCoin($command->coin);
        $this->machines->save($machine);

        return $machine->insertedAmount();
    }
}
