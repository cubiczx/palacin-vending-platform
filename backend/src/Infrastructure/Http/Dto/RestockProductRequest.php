<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use OpenApi\Attributes as OA;

final readonly class RestockProductRequest
{
    public function __construct(
        #[OA\Property(type: 'integer', example: 10, minimum: 1)]
        public int $quantity,
    ) {
    }
}
