<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use OpenApi\Attributes as OA;

final readonly class UpdateProductPriceRequest
{
    public function __construct(
        #[OA\Property(type: 'number', format: 'float', example: 1.50, minimum: 0.0)]
        public float $price,
    ) {
    }
}
