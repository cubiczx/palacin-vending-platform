<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class RestockProductCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $machines,
    ) {
    }

    public function __invoke(RestockProductCommand $command): void
    {
        $machine = $this->machines->find($command->machineId)
            ?? throw new \RuntimeException("Machine \"{$command->machineId}\" not found.");

        $machine->restockProduct($command->sku, $command->quantity);
        $this->machines->save($machine);
    }
}
