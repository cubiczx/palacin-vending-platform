<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Model\ChangeInventory;
use App\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class SetChangeInventoryCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $machines,
    ) {
    }

    public function __invoke(SetChangeInventoryCommand $command): void
    {
        $machine = $this->machines->find($command->machineId)
            ?? throw new \RuntimeException("Machine \"{$command->machineId}\" not found.");

        $machine->setChangeInventory(ChangeInventory::fromCounts($command->counts));
        $this->machines->save($machine);
    }
}
