<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use OpenApi\Attributes as OA;

final readonly class CoinsResponse
{
    /** @param array<string, int> $coins Coin value in euros (as string, e.g. "0.25") => quantity */
    public function __construct(
        #[OA\Property(
            description: 'Map currency value in euros (as string, in. "0.25") to returned amount',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'integer'),
            example: ['0.25' => 1, '0.10' => 1],
        )]
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
