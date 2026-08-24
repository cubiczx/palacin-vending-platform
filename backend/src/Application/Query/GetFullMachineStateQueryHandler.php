<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\ReadModel\FullMachineStateView;
use App\Application\ReadModel\FullProductView;
use App\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class GetFullMachineStateQueryHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $machines,
    ) {
    }

    public function __invoke(GetFullMachineStateQuery $query): FullMachineStateView
    {
        $machine = $this->machines->find($query->machineId)
            ?? throw new \RuntimeException("Machine \"{$query->machineId}\" not found.");

        $products = array_map(
            static fn ($product) => new FullProductView(
                sku: $product->sku(),
                name: $product->name(),
                price: $product->price(),
                stock: $product->stock(),
            ),
            $machine->products(),
        );

        return new FullMachineStateView($products, $machine->changeInventory()->toArray());
    }
}
