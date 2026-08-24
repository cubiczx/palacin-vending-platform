<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final readonly class CoinsResponse
{
    /** @param array<string, int> $coins Coin value in euros (as string, e.g. "0.25") => quantity */
    public function __construct(
        public array $coins,
    ) {
    }

    /** @param array<int, int> $centsToQuantity Coin value in cents => quantity */
    public static function fromCents(array $centsToQuantity): self
    {
        $coins = [];
        foreach ($centsToQuantity as $cents => $quantity) {
            $coins[number_format($cents / 100, 2)] = $quantity;
        }

        return new self($coins);
    }
}
