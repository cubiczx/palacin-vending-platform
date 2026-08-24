<?php

declare(strict_types=1);

namespace App\Application\ReadModel;

final readonly class FullMachineStateView
{
    /** @param list<FullProductView> $products */
    /** @param array<int, int> $changeInventory Coin value in cents => quantity */
    public function __construct(
        public array $products,
        public array $changeInventory,
    ) {
    }
}
