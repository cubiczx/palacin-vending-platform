<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Model\Coin;

/**
 * The coin has already been validated as an accepted denomination by the
 * HTTP layer (Coin::fromCentsOrFail) before this command is built.
 */
final readonly class InsertCoinCommand
{
    public function __construct(
        public string $machineId,
        public Coin $coin,
    ) {
    }
}
