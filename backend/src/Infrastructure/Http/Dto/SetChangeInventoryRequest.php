<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use OpenApi\Attributes as OA;

final readonly class SetChangeInventoryRequest
{
    /**
     * @param array<string, int> $counts Coin value in cents (as string) => quantity
     */
    public function __construct(
        #[OA\Property(
            description: 'Map of coin value in cents (as string) to quantity',
            type: 'object',
            example: [
                '5' => 40,
                '10' => 40,
                '25' => 40,
                '100' => 40,
            ],
            additionalProperties: new OA\AdditionalProperties(type: 'integer')
        )]
        public array $counts,
    ) {
    }
}
