<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Domain\Model\VendingResult;

final readonly class VendingResponse
{
    public function __construct(
        public string $product,
        public CoinsResponse $change,
    ) {
    }

    public static function fromResult(VendingResult $result): self
    {
        return new self(
            product: $result->product->value,
            change: CoinsResponse::fromCents($result->changeReturned),
        );
    }
}
