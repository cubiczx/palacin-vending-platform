<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\ReadModel\MachineStateView;
use App\Application\ReadModel\ProductView;
use App\Domain\Repository\VendingMachineRepositoryInterface;

final readonly class GetMachineStateQueryHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $machines,
    ) {
    }

    public function __invoke(GetMachineStateQuery $query): MachineStateView
    {
        $machine = $this->machines->find($query->machineId)
            ?? throw new \RuntimeException("Machine \"{$query->machineId}\" not found.");

        $products = array_map(
            static fn ($product) => new ProductView(
                sku: $product->sku(),
                name: $product->name(),
                price: $product->price(),
                inStock: $product->isInStock(),
            ),
            $machine->products(),
        );

        return new MachineStateView($products, $machine->insertedAmount());
    }
}
