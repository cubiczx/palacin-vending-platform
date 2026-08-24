<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final readonly class RestockProductRequest
{
    public function __construct(
        public int $quantity,
    ) {
    }
}
