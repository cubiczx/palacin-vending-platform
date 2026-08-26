<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Domain\Model\Coin;
use OpenApi\Attributes as OA;

final readonly class InsertCoinRequest
{
    public function __construct(
        #[OA\Property(
            description: 'Value of the currency in cents.',
            type: 'integer',
            enum: [Coin::FIVE_CENTS, Coin::TEN_CENTS, Coin::TWENTY_FIVE_CENTS, Coin::ONE_EURO],
            example: Coin::TWENTY_FIVE_CENTS,
        )]
        public int $cents,
    ) {
    }
}
