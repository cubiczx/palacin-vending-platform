<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final readonly class UpdateProductPriceRequest
{
    public function __construct(
        public float $price,
    ) {
    }
}
