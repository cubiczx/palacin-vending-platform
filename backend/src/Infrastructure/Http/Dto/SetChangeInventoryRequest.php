<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final readonly class SetChangeInventoryRequest
{
    /** @param array<string, int> $counts Coin value in cents (as string) => quantity */
    public function __construct(
        public array $counts,
    ) {
    }
}
