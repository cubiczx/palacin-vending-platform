<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class ReturnCoinsCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $machines,
    ) {
    }

    /** @return array<int, int> Coin value in cents => quantity returned */
    public function __invoke(ReturnCoinsCommand $command): array
    {
        $machine = $this->machines->find($command->machineId)
            ?? throw new \RuntimeException("Machine \"{$command->machineId}\" not found.");

        $returned = $machine->returnCoins();
        $this->machines->save($machine);

        return $returned;
    }
}
