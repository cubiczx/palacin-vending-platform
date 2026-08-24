<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class UpdateProductPriceCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $machines,
    ) {
    }

    public function __invoke(UpdateProductPriceCommand $command): void
    {
        $machine = $this->machines->find($command->machineId)
            ?? throw new \RuntimeException("Machine \"{$command->machineId}\" not found.");

        $machine->updateProductPrice($command->sku, $command->newPrice);
        $this->machines->save($machine);
    }
}
