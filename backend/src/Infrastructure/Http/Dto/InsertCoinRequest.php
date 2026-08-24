<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final readonly class InsertCoinRequest
{
    public function __construct(
        public int $cents,
    ) {
    }
}
