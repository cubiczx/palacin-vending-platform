<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use OpenApi\Attributes as OA;

final readonly class ErrorResponse
{
    public function __construct(
        #[OA\Property(
            description: 'Stable error code, for programmatic handling',
            type: 'string',
            enum: [
                'PRODUCT_NOT_FOUND', 'OUT_OF_STOCK', 'INSUFFICIENT_FUNDS', 'EXACT_CHANGE_UNAVAILABLE',
                'INVALID_COIN', 'INVALID_REQUEST_BODY', 'INVALID_CHANGE_QUANTITY',
                'INVALID_RESTOCK_QUANTITY', 'INVALID_PRODUCT_FILTER', 'INVALID_PRODUCT_PRICE', 'DOMAIN_ERROR',
            ],
            example: 'OUT_OF_STOCK',
        )]
        public string $error,
        #[OA\Property(description: 'Descriptive message readable to humans', type: 'string')]
        public string $message,
    ) {
    }
}
