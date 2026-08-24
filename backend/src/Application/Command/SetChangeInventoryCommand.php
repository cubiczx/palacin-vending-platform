<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class SetChangeInventoryCommand
{
    /** @param array<int, int> $counts Coin value in cents => quantity */
    public function __construct(
        public string $machineId,
        public array $counts,
    ) {
    }
}
