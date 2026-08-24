<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final readonly class ErrorResponse
{
    public function __construct(
        public string $error,
        public string $message,
    ) {
    }
}
